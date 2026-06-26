<?php

namespace App\Services;

use App\Models\JoesChangeFulfillment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JoesFulfillmentService
{
    public function validateClaimSignature(int $mainRequestId, int $expires, string $signature): bool
    {
        if ($expires < time()) {
            return false;
        }

        $secret = config('joes.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $payload = "{$mainRequestId}|{$expires}";
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function claim(int $mainRequestId): JoesChangeFulfillment
    {
        $existing = JoesChangeFulfillment::pendingFor($mainRequestId);

        if ($existing !== null) {
            return $existing;
        }

        return JoesChangeFulfillment::query()->create([
            'main_request_id' => $mainRequestId,
            'claimed_at' => now(),
            'claim_deploy_git_sha' => $this->currentGitSha(),
        ]);
    }

    public function recordDeploy(int $mainRequestId, string $gitSha, Carbon $deployedAt): JoesChangeFulfillment
    {
        $pending = JoesChangeFulfillment::pendingFor($mainRequestId);

        if ($pending === null) {
            throw new RuntimeException(
                "No pending claim for request #{$mainRequestId}. Open the claim link from the main-site email first."
            );
        }

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

    public function fulfill(int $mainRequestId, string $summary): JoesChangeFulfillment
    {
        $pending = JoesChangeFulfillment::pendingFor($mainRequestId);

        if ($pending === null) {
            throw new RuntimeException(
                "No pending claim for request #{$mainRequestId}. Open the claim link from the main-site email first."
            );
        }

        if ($pending->deploy_git_sha === null || $pending->deploy_deployed_at === null) {
            throw new RuntimeException(
                'No deploy recorded for this claim. Run REQUEST_ID='.$mainRequestId.' ./bin/deploy after making your changes.'
            );
        }

        if ($pending->deploy_deployed_at->lte($pending->claimed_at)) {
            throw new RuntimeException(
                'Recorded deploy predates this claim. Run REQUEST_ID='.$mainRequestId.' ./bin/deploy after claiming.'
            );
        }

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

        $webhookUrl = config('joes.main_site_webhook_url');
        $secret = config('joes.webhook_secret');

        if (! is_string($webhookUrl) || $webhookUrl === '') {
            throw new RuntimeException('MAIN_SITE_WEBHOOK_URL is not configured.');
        }

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('JOES_WEBHOOK_SECRET is not configured.');
        }

        $endpoint = rtrim($webhookUrl, '/').'/'.$mainRequestId.'/fulfilled';

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post($endpoint, [
                'summary' => $summary,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Main site webhook failed ('.$response->status().'): '.$response->body()
            );
        }

        $pending->forceFill([
            'fulfilled_at' => now(),
            'fulfill_deploy_git_sha' => $pending->deploy_git_sha,
            'summary' => $summary,
        ])->save();

        return $pending->fresh();
    }

    private function currentGitSha(): ?string
    {
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
