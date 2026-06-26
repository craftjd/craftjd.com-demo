<?php

namespace App\Console\Commands;

use App\Services\JoesFulfillmentService;
use Illuminate\Console\Command;
use RuntimeException;

class FulfillJoesChangeRequest extends Command
{
    protected $signature = 'joes:fulfill {mainRequestId : Main site Joe\'s change request ID} {summary : Summary sent to the main site webhook}';

    protected $description = 'Finalize a Joe\'s change request and notify the main site';

    public function handle(JoesFulfillmentService $fulfillments): int
    {
        $mainRequestId = (int) $this->argument('mainRequestId');
        $summary = (string) $this->argument('summary');

        if ($mainRequestId < 1) {
            $this->components->error('mainRequestId must be a positive integer.');

            return self::FAILURE;
        }

        try {
            $fulfillment = $fulfillments->fulfill($mainRequestId, $summary);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Request #{$fulfillment->main_request_id} fulfilled.");
        $this->line('Main site notified — check your inbox to reply to the requester.');

        return self::SUCCESS;
    }
}
