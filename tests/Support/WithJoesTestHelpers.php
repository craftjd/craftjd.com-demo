<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Config;

trait WithJoesTestHelpers
{
    protected function setUpJoesConfig(): void
    {
        Config::set('joes.main_site_webhook_url', 'https://craftjd.test/api/joes/requests');
        Config::set('joes.webhook_secret', 'test-secret');
    }

    protected function claimSignature(string $publicId, int $submittedAt, int $expires): string
    {
        return hash_hmac('sha256', "{$publicId}|{$submittedAt}|{$expires}", 'test-secret');
    }
}
