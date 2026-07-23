<?php

namespace App\Console\Commands;

use App\Exceptions\MainSiteRequestAlreadyCompleted;
use App\Services\JoesFulfillmentService;
use Illuminate\Console\Command;
use RuntimeException;

class ClaimJoesChangeRequest extends Command
{
    protected $signature = 'joes:claim {publicId : Main site request public ID (UUID)}';

    protected $description = 'Claim a main-site change request before editing and deploying';

    public function handle(JoesFulfillmentService $fulfillments): int
    {
        $publicId = (string) $this->argument('publicId');

        try {
            $claim = $fulfillments->claimFromMainSite($publicId);
        } catch (MainSiteRequestAlreadyCompleted $exception) {
            $preview = $exception->preview;
            $this->components->warn("Request #{$preview->number} was already fulfilled on the main site.");

            if ($preview->completedAt) {
                $this->line('Fulfilled: '.$preview->completedAt
                    ->timezone(config('app.timezone'))
                    ->format('M j, Y g:i A T'));
            }

            if ($preview->fulfillmentSummary) {
                $this->line('Summary: '.$preview->fulfillmentSummary);
            }

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Claimed main-site request #{$claim->main_request_id}.");
        $this->line('Public ID: '.$claim->main_request_public_id);
        $this->line('Next: edit Joe\'s, then REQUEST_PUBLIC_ID='.$publicId.' ./bin/internal/deploy.sh');
        $this->line('Production fulfill: task claim:fulfill PUBLIC_ID='.$publicId.' SUMMARY="summary of changes"');
        $this->line('Local: task dev:claim:deploy PUBLIC_ID='.$publicId);
        $this->line('Then:  task dev:claim:fulfill PUBLIC_ID='.$publicId.' SUMMARY="summary of changes"');
        $this->line('Mistake? task claim:abandon PUBLIC_ID='.$publicId.' (prod) or task dev:claim:abandon (local)');

        return self::SUCCESS;
    }
}
