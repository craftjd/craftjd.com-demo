<?php

namespace App\Console\Commands;

use App\Services\JoesFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

class RecordJoesDeploy extends Command
{
    protected $signature = 'joes:record-deploy
        {mainRequestId : Main site Joe\'s change request ID}
        {gitSha : Deployed git SHA}
        {deployedAt : Deploy timestamp (ISO 8601)}';

    protected $description = 'Record deploy metadata on a pending Joe\'s change fulfillment';

    public function handle(JoesFulfillmentService $fulfillments): int
    {
        $mainRequestId = (int) $this->argument('mainRequestId');
        $gitSha = (string) $this->argument('gitSha');
        $deployedAtRaw = (string) $this->argument('deployedAt');

        if ($mainRequestId < 1) {
            $this->components->error('mainRequestId must be a positive integer.');

            return self::FAILURE;
        }

        try {
            $deployedAt = Carbon::parse($deployedAtRaw);
        } catch (\Throwable) {
            $this->components->error('deployedAt must be a valid ISO 8601 timestamp.');

            return self::FAILURE;
        }

        try {
            $fulfillment = $fulfillments->recordDeploy($mainRequestId, $gitSha, $deployedAt);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Recorded deploy for request #{$fulfillment->main_request_id} ({$gitSha}).");
        $this->line('Next: ./bin/fulfill '.$mainRequestId.' "summary of changes"');

        return self::SUCCESS;
    }
}
