<?php

namespace App\Console\Commands;

use App\Services\SampleFulfillmentService;
use Illuminate\Console\Command;
use RuntimeException;

class FulfillSampleChangeRequest extends Command
{
    protected $signature = 'sample:fulfill {mainRequestId : Main site demo request ID} {summary : Summary sent to the main site webhook}';

    protected $description = 'Validate deploy metadata and notify the main site that a request is live';

    public function handle(SampleFulfillmentService $fulfillments): int
    {
        $mainRequestId = (int) $this->argument('mainRequestId');
        $summary = trim((string) $this->argument('summary'));

        if ($mainRequestId < 1) {
            $this->components->error('mainRequestId must be a positive integer.');

            return self::FAILURE;
        }

        if ($summary === '') {
            $this->components->error('summary is required.');

            return self::FAILURE;
        }

        try {
            $fulfillment = $fulfillments->fulfill($mainRequestId, $summary);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Request #{$fulfillment->main_request_id} fulfilled on the main site.");

        return self::SUCCESS;
    }
}
