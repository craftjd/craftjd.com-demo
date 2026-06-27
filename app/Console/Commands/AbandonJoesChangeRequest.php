<?php

namespace App\Console\Commands;

use App\Data\JoesChangeRequestPreview;
use App\Services\JoesFulfillmentService;
use App\Services\MainSiteJoesClient;
use Illuminate\Console\Command;
use RuntimeException;

class AbandonJoesChangeRequest extends Command
{
    protected $signature = 'joes:abandon
        {publicId : Main site request public ID (UUID)}
        {--force : Skip confirmation}';

    protected $description = 'Release a claimed request on the main site and remove Joe\'s pending claim';

    public function handle(
        JoesFulfillmentService $fulfillments,
        MainSiteJoesClient $mainSite,
    ): int {
        $publicId = (string) $this->argument('publicId');

        try {
            $preview = $mainSite->fetchRequest($publicId);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->displayPreview($preview);

        if (! $this->option('force') && ! $this->confirm('Abandon this request on both sites?', false)) {
            $this->line('No changes made.');

            return self::SUCCESS;
        }

        try {
            $fulfillments->abandon($publicId);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->warn('Request abandoned on the main site and Joe\'s pending claim removed.');

        return self::SUCCESS;
    }

    private function displayPreview(JoesChangeRequestPreview $preview): void
    {
        $this->newLine();
        $this->components->info('Request preview (from main site — not stored on Joe\'s)');
        $this->line("  Number:    #{$preview->number}");
        $this->line("  Public ID: {$preview->publicId}");
        $this->line("  Name:      {$preview->clientName}");
        $this->line("  Email:     {$preview->clientEmail}");
        $this->line('  Submitted: '.$preview->submittedAt
            ->timezone(config('app.timezone'))
            ->format('M j, Y g:i A T'));
        $this->newLine();
    }
}
