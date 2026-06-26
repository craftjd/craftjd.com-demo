<?php

namespace Tests\Feature;

use App\Models\JoesChangeFulfillment;
use App\Services\JoesFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class JoesFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('joes.main_site_webhook_url', 'https://craftjd.test/api/joes/requests');
        Config::set('joes.webhook_secret', 'test-secret');
    }

    public function test_claim_creates_pending_row(): void
    {
        $claim = app(JoesFulfillmentService::class)->claim(42);

        $this->assertSame(42, $claim->main_request_id);
        $this->assertNull($claim->fulfilled_at);
    }

    public function test_multiple_pending_claims_for_different_requests(): void
    {
        $service = app(JoesFulfillmentService::class);

        $first = $service->claim(42);
        $second = $service->claim(43);

        $this->assertSame(42, $first->main_request_id);
        $this->assertSame(43, $second->main_request_id);
        $this->assertCount(2, JoesChangeFulfillment::pending());
    }

    public function test_claim_is_idempotent_for_same_request(): void
    {
        $service = app(JoesFulfillmentService::class);

        $first = $service->claim(42);
        $second = $service->claim(42);

        $this->assertTrue($first->is($second));
        $this->assertCount(1, JoesChangeFulfillment::pending());
    }

    public function test_fulfill_requires_recorded_deploy_on_claim_row(): void
    {
        app(JoesFulfillmentService::class)->claim(42);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No deploy recorded for this claim');

        app(JoesFulfillmentService::class)->fulfill(42, 'Updated summer hours.');
    }

    public function test_fulfill_posts_to_main_site_and_marks_complete(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/42/fulfilled' => Http::response([
                'status' => 'fulfilled',
                'request_id' => 42,
            ]),
        ]);

        $service = app(JoesFulfillmentService::class);
        $claim = $service->claim(42);

        $claim->forceFill([
            'deploy_git_sha' => 'abc1234567890abc1234567890abc1234567890',
            'deploy_deployed_at' => now()->addMinute(),
            'claim_deploy_git_sha' => null,
        ])->save();

        $fulfillment = $service->fulfill(42, 'Updated summer hours on the contact section.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://craftjd.test/api/joes/requests/42/fulfilled'
                && $request->hasHeader('Authorization', 'Bearer test-secret')
                && $request['summary'] === 'Updated summer hours on the contact section.';
        });

        $this->assertNotNull($fulfillment->fulfilled_at);
        $this->assertSame('Updated summer hours on the contact section.', $fulfillment->summary);
        $this->assertCount(0, JoesChangeFulfillment::pending());
    }

    public function test_record_deploy_rejects_matching_claim_sha(): void
    {
        $sha = 'abc1234567890abc1234567890abc1234567890';
        $claim = app(JoesFulfillmentService::class)->claim(42);
        $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy git SHA matches the SHA recorded at claim time');

        app(JoesFulfillmentService::class)->recordDeploy(
            42,
            $sha,
            now()->addMinute(),
        );
    }

    public function test_record_deploy_rejects_rollback_sha(): void
    {
        $head = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD 2>/dev/null'));
        $parent = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD^ 2>/dev/null'));

        if ($head === '' || $parent === '') {
            $this->markTestSkipped('Git history unavailable for rollback test.');
        }

        $claim = app(JoesFulfillmentService::class)->claim(42);
        $claim->forceFill(['claim_deploy_git_sha' => $head])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy git SHA is not ahead of the claim SHA');

        app(JoesFulfillmentService::class)->recordDeploy(42, $parent, now()->addMinute());
    }

    public function test_claim_link_starts_pending_claim(): void
    {
        Config::set('joes.webhook_secret', 'test-secret');

        $service = app(JoesFulfillmentService::class);
        $expires = now()->addHour()->timestamp;
        $signature = hash_hmac('sha256', "42|{$expires}", 'test-secret');

        $this->get("/joes/requests/42/claim?expires={$expires}&signature={$signature}")
            ->assertOk()
            ->assertSee('Request #42 is claimed');

        $this->assertNotNull(JoesChangeFulfillment::pendingFor(42));
    }

    public function test_claim_link_rejects_invalid_signature(): void
    {
        Config::set('joes.webhook_secret', 'test-secret');

        $this->get('/joes/requests/42/claim?expires='.now()->addHour()->timestamp.'&signature=invalid')
            ->assertForbidden();
    }
}
