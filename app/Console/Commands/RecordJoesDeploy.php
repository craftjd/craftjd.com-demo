<?php

namespace App\Console\Commands;

use App\Models\JoesChangeFulfillment;
use App\Services\JoesFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

class RecordJoesDeploy extends Command
{
    protected $signature = 'joes:record-deploy
        {publicId : Main site request public ID (UUID)}
        {gitSha : Deployed git SHA}
        {deployedAt : Deploy timestamp (ISO 8601)}
        {--local : Local testing — skip git descendant checks (no new commit required)}';

    protected $description = 'Record deploy metadata on a pending Joe\'s change fulfillment';

    public function handle(JoesFulfillmentService $fulfillments): int
    {
        $publicId = (string) $this->argument('publicId');
        $gitSha = (string) $this->argument('gitSha');
        $deployedAtRaw = (string) $this->argument('deployedAt');

        try {
            $deployedAt = Carbon::parse($deployedAtRaw);
        } catch (\Throwable) {
            $this->components->error('deployedAt must be a valid ISO 8601 timestamp.');

            return self::FAILURE;
        }

        $localBypassGit = (bool) $this->option('local');

        try {
            $fulfillment = $fulfillments->recordDeploy($publicId, $gitSha, $deployedAt, $localBypassGit);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Recorded deploy for request #{$fulfillment->main_request_id} ({$gitSha}).");

        if ($localBypassGit) {
            $this->line('Local mode: git descendant checks were skipped.');
        }

        $this->line('Next: task claim:fulfill PUBLIC_ID='.$publicId.' SUMMARY="summary of changes"');
        $this->line('Local: task dev:claim:fulfill PUBLIC_ID='.$publicId.' SUMMARY="summary of changes"');

        return self::SUCCESS;
    }
}
