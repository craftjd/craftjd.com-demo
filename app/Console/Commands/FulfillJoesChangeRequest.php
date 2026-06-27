<?php

namespace App\Console\Commands;

use App\Data\JoesChangeRequestPreview;
use App\Services\JoesFulfillmentService;
use App\Services\MainSiteJoesClient;
use Illuminate\Console\Command;
use RuntimeException;

class FulfillJoesChangeRequest extends Command
{
    protected $signature = 'joes:fulfill
        {publicId : Main site request public ID (UUID)}
        {summary : Summary sent to the main site webhook}
        {--yes : Skip confirmation and fulfill immediately}';

    protected $description = 'Preview the main-site request, confirm, then notify the main site';

    public function handle(
        JoesFulfillmentService $fulfillments,
        MainSiteJoesClient $mainSite,
    ): int {
        $publicId = (string) $this->argument('publicId');
        $summary = trim((string) $this->argument('summary'));

        if ($summary === '') {
            $this->components->error('summary is required.');

            return self::FAILURE;
        }

        try {
            $preview = $mainSite->fetchRequest($publicId);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->displayPreview($preview);

        if ($this->option('yes')) {
            return $this->runFulfill($fulfillments, $publicId, $summary);
        }

        $choice = $this->choice(
            'Confirm next step',
            [
                'Fulfill this request on the main site',
                'Abandon on both sites (main site + Joe\'s pending row)',
                'Keep pending — do nothing',
            ],
            'Keep pending — do nothing',
        );

        if ($choice === 'Keep pending — do nothing') {
            $this->line('No changes made. Pending claim kept on Joe\'s.');

            return self::SUCCESS;
        }

        if ($choice === 'Abandon on both sites (main site + Joe\'s pending row)') {
            try {
                $fulfillments->abandon($publicId);
            } catch (RuntimeException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->components->warn('Request abandoned on the main site and Joe\'s pending claim removed.');

            return self::SUCCESS;
        }

        return $this->runFulfill($fulfillments, $publicId, $summary);
    }

    private function runFulfill(JoesFulfillmentService $fulfillments, string $publicId, string $summary): int
    {
        try {
            $fulfillment = $fulfillments->fulfill($publicId, $summary);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Request #{$fulfillment->main_request_id} fulfilled.");
        $this->line('Main site notified — check your inbox to reply to the requester.');

        return self::SUCCESS;
    }

    private function displayPreview(JoesChangeRequestPreview $preview): void
    {
        $this->newLine();
        $this->components->info('Request preview (from main site — not stored on Joe\'s)');

        $this->line("  Number:    #{$preview->number}");
        $this->line("  Public ID: {$preview->publicId}");
        $this->line('  Name:      '.($preview->clientName !== '' ? $preview->clientName : '(not provided)'));
        $this->line("  Email:     {$preview->clientEmail}");
        $this->line('  Submitted: '.$preview->submittedAt
            ->timezone(config('app.timezone'))
            ->format('M j, Y g:i A T'));
        $this->line('  Request:');
        $this->line('  '.str_replace("\n", "\n  ", $preview->requestBody));
        $this->newLine();
    }
}
