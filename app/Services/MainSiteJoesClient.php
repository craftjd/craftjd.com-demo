<?php

namespace App\Services;

use App\Data\JoesChangeRequestPreview;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MainSiteJoesClient
{
    public function fetchRequest(string $publicId): JoesChangeRequestPreview
    {
        $response = $this->request('GET', $this->endpoint($publicId));

        if ($response->status() === 404) {
            throw new RuntimeException("Main site has no request with public ID {$publicId}.");
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Main site request lookup failed ('.$response->status().'): '.$response->body()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Main site request lookup returned invalid JSON.');
        }

        return JoesChangeRequestPreview::fromArray($payload);
    }

    public function fulfill(string $publicId, string $summary, ?\Illuminate\Support\Carbon $claimedAt = null): void
    {
        $body = [
            'summary' => $summary,
        ];

        if ($claimedAt !== null) {
            $body['claimed_at'] = $claimedAt->utc()->toIso8601String();
        }

        $response = $this->request('POST', $this->endpoint($publicId.'/fulfilled'), $body);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Main site webhook failed ('.$response->status().'): '.$response->body()
            );
        }
    }

    public function abandon(string $publicId): void
    {
        $response = $this->request('POST', $this->endpoint($publicId.'/abandon'));

        if ($response->status() === 404) {
            throw new RuntimeException("Main site has no request with public ID {$publicId}.");
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Main site abandon failed ('.$response->status().'): '.$response->body()
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchCompleted(int $perPage = 20): array
    {
        $response = $this->request('GET', $this->endpoint('?per_page='.$perPage));

        if (! $response->successful()) {
            throw new RuntimeException(
                'Main site completed requests fetch failed ('.$response->status().'): '.$response->body()
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['data'])) {
            throw new RuntimeException('Main site completed requests returned invalid JSON.');
        }

        return $payload['data'];
    }

    private function endpoint(string $suffix): string
    {
        $base = config('joes.main_site_webhook_url');

        if (! is_string($base) || $base === '') {
            throw new RuntimeException('MAIN_SITE_WEBHOOK_URL is not configured.');
        }

        return rtrim($base, '/').'/'.ltrim($suffix, '/');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $url, array $body = []): \Illuminate\Http\Client\Response
    {
        $secret = config('joes.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('JOES_WEBHOOK_SECRET is not configured.');
        }

        $pending = Http::withToken($secret)->acceptJson();

        return match (strtoupper($method)) {
            'GET' => $pending->get($url),
            'POST' => $pending->post($url, $body),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };
    }
}
