<?php

namespace App\Console\Commands;

use App\Services\JoesFulfillmentService;
use Illuminate\Console\Command;
use RuntimeException;

class ClaimJoesChangeRequest extends Command
{
    protected $signature = 'joes:claim {mainRequestId : Main site Joe\'s change request ID}';

    protected $description = 'Claim a main-site change request before editing and deploying';

    public function handle(JoesFulfillmentService $fulfillments): int
    {
        $mainRequestId = (int) $this->argument('mainRequestId');

        if ($mainRequestId < 1) {
            $this->components->error('mainRequestId must be a positive integer.');

            return self::FAILURE;
        }

        try {
            $claim = $fulfillments->claim($mainRequestId);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Claimed main-site request #{$claim->main_request_id}.");
        $this->line('Next: edit Joe\'s, then REQUEST_ID='.$mainRequestId.' ./bin/deploy');
        $this->line('Then: ./bin/fulfill '.$mainRequestId.' "summary of changes"');

        return self::SUCCESS;
    }
}
