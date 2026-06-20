<?php

namespace Tests\Feature;

use App\Models\SampleChangeFulfillment;
use App\Services\SampleFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SampleFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(storage_path('app/deploy-meta.json'));

        Config::set('sample.main_site_webhook_url', 'https://craftjd.test/api/demo/requests');
        Config::set('sample.webhook_secret', 'test-secret');
    }

    public function test_claim_creates_pending_row(): void
    {
        $claim = app(SampleFulfillmentService::class)->claim(42);

        $this->assertSame(42, $claim->main_request_id);
        $this->assertNull($claim->fulfilled_at);
    }

    public function test_claim_rejects_second_pending_request(): void
    {
        app(SampleFulfillmentService::class)->claim(42);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request #42 is already pending');

        app(SampleFulfillmentService::class)->claim(43);
    }

    public function test_fulfill_requires_matching_deploy_metadata(): void
    {
        $this->writeDeployMeta([
            'git_sha' => 'abc1234567890abc1234567890abc1234567890',
            'deployed_at' => now()->addMinute()->toIso8601String(),
        ]);

        app(SampleFulfillmentService::class)->claim(42);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy metadata is missing request_id');

        app(SampleFulfillmentService::class)->fulfill(42, 'Updated summer hours.');
    }

    public function test_fulfill_posts_to_main_site_and_marks_complete(): void
    {
        Http::fake([
            'https://craftjd.test/api/demo/requests/42/fulfilled' => Http::response([
                'status' => 'fulfilled',
                'request_id' => 42,
            ]),
        ]);

        $service = app(SampleFulfillmentService::class);
        $claim = $service->claim(42);

        $this->writeDeployMeta([
            'git_sha' => 'abc1234567890abc1234567890abc1234567890',
            'deployed_at' => now()->addMinute()->toIso8601String(),
            'request_id' => 42,
        ]);

        $fulfillment = $service->fulfill(42, 'Updated summer hours on the contact section.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://craftjd.test/api/demo/requests/42/fulfilled'
                && $request->hasHeader('Authorization', 'Bearer test-secret')
                && $request['summary'] === 'Updated summer hours on the contact section.';
        });

        $this->assertNotNull($fulfillment->fulfilled_at);
        $this->assertSame('Updated summer hours on the contact section.', $fulfillment->summary);
        $this->assertNull(SampleChangeFulfillment::pending());
        $this->assertTrue($claim->fresh()->fulfilled_at !== null);
    }

    public function test_fulfill_rejects_deploy_tagged_for_different_request(): void
    {
        app(SampleFulfillmentService::class)->claim(42);

        $this->writeDeployMeta([
            'git_sha' => 'def4567890abcdef4567890abcdef4567890ab',
            'deployed_at' => now()->addMinute()->toIso8601String(),
            'request_id' => 99,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Latest deploy is tagged for request #99, not #42.');

        app(SampleFulfillmentService::class)->fulfill(42, 'Updated summer hours.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeDeployMeta(array $payload): void
    {
        $path = storage_path('app/deploy-meta.json');
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
