<?php

namespace App\Console\Commands;

use App\Models\JoesChangeFulfillment;
use Illuminate\Console\Command;

class JoesFulfillmentStatus extends Command
{
    protected $signature = 'joes:status
                            {--pending : Only show in-progress claims}';

    protected $description = 'Compact snapshot of change-request work on Joe\'s (no client PII)';

    public function handle(): int
    {
        $this->line('Joe\'s');

        $query = JoesChangeFulfillment::query()
            ->orderByRaw('fulfilled_at is not null')
            ->orderByDesc('claimed_at');

        if ($this->option('pending')) {
            $query->whereNull('fulfilled_at');
        }

        $rows = $query->get([
            'main_request_id',
            'main_request_public_id',
            'claimed_at',
            'deploy_deployed_at',
            'fulfilled_at',
            'created_at',
        ]);

        if ($rows->isEmpty()) {
            $this->line($this->option('pending') ? '  (no pending claims)' : '  (no claims)');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $this->line(sprintf(
                '  #%d  %-9s  %5s  %s',
                $row->main_request_id,
                $row->pipelineStep(),
                $row->pipelineAt()->diffForHumans(short: true),
                JoesChangeFulfillment::truncatePublicId((string) $row->main_request_public_id),
            ));
        }

        return self::SUCCESS;
    }
}
