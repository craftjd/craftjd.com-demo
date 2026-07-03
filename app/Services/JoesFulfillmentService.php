<?php

namespace App\Services;

use App\Exceptions\MainSiteRequestAlreadyCompleted;
use App\Models\JoesChangeFulfillment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class JoesFulfillmentService
{
    public function __construct(
        protected MainSiteJoesClient $mainSite,
    ) {}

    public function validateClaimSignature(
        string $publicId,
        int $submittedAt,
        int $expires,
        string $signature,
    ): bool {
        if (! Str::isUuid($publicId)) {
            return false;
        }

        if ($expires < time()) {
            return false;
        }

        $secret = config('joes.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $payload = "{$publicId}|{$submittedAt}|{$expires}";
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function claim(
        string $publicId,
        int $mainRequestNumber,
        Carbon $mainRequestSubmittedAt,
    ): JoesChangeFulfillment {
        $this->assertPublicId($publicId);

        $existing = JoesChangeFulfillment::pendingForPublicId($publicId);

        if ($existing !== null) {
            return $existing;
        }

        return JoesChangeFulfillment::query()->create([
            'main_request_public_id' => $publicId,
            'main_request_id' => $mainRequestNumber,
            'main_request_submitted_at' => $mainRequestSubmittedAt,
            'claimed_at' => now(),
            'claim_deploy_git_sha' => $this->currentGitSha(),
        ]);
    }

    public function claimFromMainSite(string $publicId): JoesChangeFulfillment
    {
        $preview = $this->mainSite->fetchRequest($publicId);

        if ($preview->isCompleted()) {
            throw new MainSiteRequestAlreadyCompleted($preview);
        }

        return $this->claim(
            $preview->publicId,
            $preview->number,
            $preview->submittedAt,
        );
    }

    public function claimFromSignedLink(
        string $publicId,
        int $mainRequestNumber,
        Carbon $mainRequestSubmittedAt,
    ): JoesChangeFulfillment {
        $existing = JoesChangeFulfillment::pendingForPublicId($publicId);

        if ($existing !== null) {
            return $existing;
        }

        $preview = $this->mainSite->fetchRequest($publicId);

        if ($preview->isCompleted()) {
            throw new MainSiteRequestAlreadyCompleted($preview);
        }

        return $this->claim(
            $preview->publicId,
            $preview->number,
            $preview->submittedAt,
        );
    }

    public function recordDeploy(
        string $publicId,
        string $gitSha,
        Carbon $deployedAt,
        bool $localBypassGit = false,
    ): JoesChangeFulfillment {
        $pending = $this->requirePending($publicId);

        $localBypassGit = $localBypassGit || (bool) config('joes.local_deploy_bypass_git');

        if (! $localBypassGit) {
            if ($gitSha === $pending->claim_deploy_git_sha) {
                throw new RuntimeException(
                    'Deploy git SHA matches the SHA recorded at claim time. Commit your changes, then deploy again.'
                );
            }

            if (! $this->isGitDescendantOf($pending->claim_deploy_git_sha, $gitSha)) {
                throw new RuntimeException(
                    'Deploy git SHA is not ahead of the claim SHA. Rollback deploys cannot satisfy this claim — claim again after rollback, then deploy new commits.'
                );
            }
        }

        if ($deployedAt->lte($pending->claimed_at)) {
            throw new RuntimeException(
                'Deploy timestamp predates this claim. Deploy again after claiming the request.'
            );
        }

        $pending->forceFill([
            'deploy_git_sha' => $gitSha,
            'deploy_deployed_at' => $deployedAt,
        ])->save();

        return $pending->fresh();
    }

    public function fulfill(
        string $publicId,
        string $summary,
        bool $localBypassGit = false,
    ): JoesChangeFulfillment {
        $pending = $this->requirePending($publicId);

        // Auto-record deploy if missing — running fulfill proves the deploy happened.
        if ($pending->deploy_git_sha === null || $pending->deploy_deployed_at === null) {
            $sha = $this->currentGitSha();
            $pending->forceFill([
                'deploy_git_sha' => $sha,
                'deploy_deployed_at' => now(),
            ])->save();
            $pending = $pending->fresh();
        }

        if ($pending->deploy_deployed_at->lte($pending->claimed_at)) {
            throw new RuntimeException(
                'Recorded deploy predates this claim. Run REQUEST_PUBLIC_ID='.$publicId.' ./bin/deploy after claiming.'
            );
        }

        $localBypassGit = $localBypassGit || (bool) config('joes.local_deploy_bypass_git');

        if (! $localBypassGit) {
            // Skip SHA checks when claim was made via web link (no git context).
            if ($pending->claim_deploy_git_sha !== null) {
                if ($pending->deploy_git_sha === $pending->claim_deploy_git_sha) {
                    throw new RuntimeException(
                        'Recorded deploy matches the git SHA from claim time. Deploy your changes first.'
                    );
                }

                if (! $this->isGitDescendantOf($pending->claim_deploy_git_sha, $pending->deploy_git_sha)) {
                    throw new RuntimeException(
                        'Recorded deploy is not ahead of the claim SHA. Rollback deploys cannot fulfill this claim — claim again, then deploy new commits.'
                    );
                }
            }
        }

        $this->mainSite->fulfill($publicId, $summary, $pending->claimed_at);

        $pending->forceFill([
            'fulfilled_at' => now(),
            'fulfill_deploy_git_sha' => $pending->deploy_git_sha,
            'summary' => $summary,
        ])->save();

        return $pending->fresh();
    }

    public function abandon(string $publicId): void
    {
        $this->assertPublicId($publicId);

        $this->mainSite->abandon($publicId);

        JoesChangeFulfillment::query()
            ->where('main_request_public_id', $publicId)
            ->whereNull('fulfilled_at')
            ->delete();
    }

    private function requirePending(string $publicId): JoesChangeFulfillment
    {
        $this->assertPublicId($publicId);

        $pending = JoesChangeFulfillment::pendingForPublicId($publicId);

        if ($pending === null) {
            throw new RuntimeException(
                "No pending claim for public ID {$publicId}. Open the claim link from the main-site email first."
            );
        }

        return $pending;
    }

    private function assertPublicId(string $publicId): void
    {
        if (! Str::isUuid($publicId)) {
            throw new RuntimeException('Request public ID must be a UUID.');
        }
    }

    private function currentGitSha(): ?string
    {
        // Read from deploy-meta.json (written each deploy, always available on prod).
        $metaPath = base_path('storage/app/deploy-meta.json');
        if (file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
            if (is_array($meta) && isset($meta['git_sha']) && is_string($meta['git_sha']) && $meta['git_sha'] !== '') {
                return $meta['git_sha'];
            }
        }

        // Fallback to git (works on local clones, fails in Deployer release dirs).
        $output = [];
        $code = 0;
        exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD 2>/dev/null', $output, $code);

        if ($code !== 0 || ! isset($output[0])) {
            return null;
        }

        $sha = trim($output[0]);

        return $sha !== '' ? $sha : null;
    }

    private function isGitDescendantOf(?string $ancestorSha, string $descendantSha): bool
    {
        if ($ancestorSha === null || $ancestorSha === '') {
            return true;
        }

        if ($ancestorSha === $descendantSha) {
            return false;
        }

        $code = 0;
        exec(
            'git -C '.escapeshellarg(base_path()).' merge-base --is-ancestor '
            .escapeshellarg($ancestorSha).' '.escapeshellarg($descendantSha).' 2>/dev/null',
            $output,
            $code
        );

        return $code === 0;
    }
}
