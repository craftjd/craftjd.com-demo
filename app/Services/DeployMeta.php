<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class DeployMeta
{
    /**
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        return $this->read();
    }

    /**
     * @return list<array{date: string, datetime: string, summary: string}>
     */
    public function changelog(): array
    {
        $meta = $this->read();

        if ($meta === null) {
            return [];
        }

        $deployments = $meta['deployments'] ?? null;

        if (! is_array($deployments) || $deployments === []) {
            if (! is_string($meta['git_sha'] ?? null) || $meta['git_sha'] === '') {
                return [];
            }

            $deployments = [$meta];
        }

        return array_values(array_map(
            fn (array $entry) => $this->formatEntry($entry),
            $deployments,
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(): ?array
    {
        $path = storage_path('app/deploy-meta.json');

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array{date: string, datetime: string, summary: string}
     */
    private function formatEntry(array $entry): array
    {
        $summary = trim((string) ($entry['summary'] ?? ''));

        if ($summary === '') {
            $message = trim((string) ($entry['git_commit_message'] ?? ''));
            $lines = preg_split('/\R/', $message, 2) ?: [];
            $summary = trim($lines[1] ?? '');

            if ($summary === '') {
                $summary = trim($lines[0] ?? '') ?: 'Deployment';
            }
        }

        $deployedAt = (string) ($entry['deployed_at'] ?? '');

        try {
            $date = $deployedAt !== '' ? Carbon::parse($deployedAt)->format('Y-m-d') : '';
        } catch (\Throwable) {
            $date = $deployedAt;
        }

        return [
            'date' => $date,
            'datetime' => $deployedAt,
            'summary' => $summary,
        ];
    }
}
