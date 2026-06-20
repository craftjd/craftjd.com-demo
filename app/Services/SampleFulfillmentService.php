<?php

namespace App\Services;

use App\Models\SampleChangeFulfillment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SampleFulfillmentService
{
    public function claim(int $mainRequestId): SampleChangeFulfillment
    {
        $pending = SampleChangeFulfillment::pending();

        if ($pending !== null) {
            if ((int) $pending->main_request_id === $mainRequestId) {
                return $pending;
            }

            throw new RuntimeException(
                "Request #{$pending->main_request_id} is already pending. Fulfill or wait before claiming #{$mainRequestId}."
            );
        }

        $deployMeta = $this->readDeployMeta();

        return SampleChangeFulfillment::query()->create([
            'main_request_id' => $mainRequestId,
            'claimed_at' => now(),
            'claim_deploy_git_sha' => is_string($deployMeta['git_sha'] ?? null) ? $deployMeta['git_sha'] : null,
        ]);
    }

    public function fulfill(int $mainRequestId, string $summary): SampleChangeFulfillment
    {
        $pending = SampleChangeFulfillment::query()
            ->where('main_request_id', $mainRequestId)
            ->whereNull('fulfilled_at')
            ->first();

        if ($pending === null) {
            throw new RuntimeException(
                "No pending claim for request #{$mainRequestId}. Run: ./bin/fulfill start {$mainRequestId}"
            );
        }

        $deployMeta = $this->readDeployMeta();

        if ($deployMeta === null) {
            throw new RuntimeException(
                'No deploy metadata found. Run ./bin/deploy with REQUEST_ID='.$mainRequestId.' before fulfilling.'
            );
        }

        $requestId = $deployMeta['request_id'] ?? null;

        if (! is_int($requestId) && ! (is_string($requestId) && ctype_digit($requestId))) {
            throw new RuntimeException(
                'Deploy metadata is missing request_id. Run: REQUEST_ID='.$mainRequestId.' ./bin/deploy'
            );
        }

        if ((int) $requestId !== $mainRequestId) {
            throw new RuntimeException(
                "Latest deploy is tagged for request #{$requestId}, not #{$mainRequestId}."
            );
        }

        $deployedAt = $this->parseTimestamp($deployMeta['deployed_at'] ?? null);

        if ($deployedAt === null || $deployedAt->lte($pending->claimed_at)) {
            throw new RuntimeException(
                'Latest deploy predates this claim. Run REQUEST_ID='.$mainRequestId.' ./bin/deploy after ./bin/fulfill start.'
            );
        }

        $deployGitSha = is_string($deployMeta['git_sha'] ?? null) ? $deployMeta['git_sha'] : null;

        if ($deployGitSha !== null && $deployGitSha === $pending->claim_deploy_git_sha) {
            throw new RuntimeException(
                'Latest deploy matches the git SHA from when this request was claimed. Deploy your changes first.'
            );
        }

        $webhookUrl = config('sample.main_site_webhook_url');
        $secret = config('sample.webhook_secret');

        if (! is_string($webhookUrl) || $webhookUrl === '') {
            throw new RuntimeException('MAIN_SITE_WEBHOOK_URL is not configured.');
        }

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('DEMO_WEBHOOK_SECRET is not configured.');
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
            'fulfill_deploy_git_sha' => $deployGitSha,
            'summary' => $summary,
        ])->save();

        return $pending->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readDeployMeta(): ?array
    {
        $path = storage_path('app/deploy-meta.json');

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
