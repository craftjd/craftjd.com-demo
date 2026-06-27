<?php

namespace Tests\Feature;

use App\Models\JoesChangeFulfillment;
use App\Services\JoesFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class JoesFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_ID_42 = '11111111-1111-4111-8111-111111111111';

    private const PUBLIC_ID_43 = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('joes.main_site_webhook_url', 'https://craftjd.test/api/joes/requests');
        Config::set('joes.webhook_secret', 'test-secret');
        Config::set('joes.local_deploy_bypass_git', false);
    }

    public function test_claim_creates_pending_row(): void
    {
        $submittedAt = now()->subDay();
        $claim = app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, $submittedAt);

        $this->assertSame(self::PUBLIC_ID_42, $claim->main_request_public_id);
        $this->assertSame(42, $claim->main_request_id);
        $this->assertNull($claim->fulfilled_at);
        $this->assertNotNull($claim->claimed_at);
    }

    public function test_claim_from_main_site_rejects_completed_request(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42 => Http::response(
                $this->completedPreviewPayload(),
            ),
        ]);

        $this->expectException(\App\Exceptions\MainSiteRequestAlreadyCompleted::class);

        app(JoesFulfillmentService::class)->claimFromMainSite(self::PUBLIC_ID_42);
    }

    public function test_multiple_pending_claims_for_different_requests(): void
    {
        $service = app(JoesFulfillmentService::class);

        $first = $service->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $second = $service->claim(self::PUBLIC_ID_43, 43, now()->subDay());

        $this->assertSame(42, $first->main_request_id);
        $this->assertSame(43, $second->main_request_id);
        $this->assertCount(2, JoesChangeFulfillment::pending());
    }

    public function test_claim_is_idempotent_for_same_public_id(): void
    {
        $service = app(JoesFulfillmentService::class);
        $submittedAt = now()->subDay();

        $first = $service->claim(self::PUBLIC_ID_42, 42, $submittedAt);
        $second = $service->claim(self::PUBLIC_ID_42, 42, $submittedAt);

        $this->assertTrue($first->is($second));
        $this->assertCount(1, JoesChangeFulfillment::pending());
    }

    public function test_fulfill_requires_recorded_deploy_on_claim_row(): void
    {
        app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, now()->subDay());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No deploy recorded for this claim');

        app(JoesFulfillmentService::class)->fulfill(self::PUBLIC_ID_42, 'Updated summer hours.');
    }

    public function test_record_deploy_local_bypass_stores_deploy_metadata(): void
    {
        $service = app(JoesFulfillmentService::class);
        $claim = $service->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $sha = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD 2>/dev/null'));

        if ($sha === '') {
            $sha = 'abc1234567890abc1234567890abc1234567890';
            $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();
        }

        $recorded = $service->recordDeploy(self::PUBLIC_ID_42, $sha, now()->addMinute(), localBypassGit: true);

        $this->assertSame($sha, $recorded->deploy_git_sha);
        $this->assertNotNull($recorded->deploy_deployed_at);
    }

    public function test_fulfill_posts_to_main_site_after_local_record_deploy(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/fulfilled' => Http::response([
                'status' => 'fulfilled',
            ]),
        ]);

        $service = app(JoesFulfillmentService::class);
        $claim = $service->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $sha = $claim->claim_deploy_git_sha ?? 'abc1234567890abc1234567890abc1234567890';

        if ($claim->claim_deploy_git_sha === null) {
            $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();
        }

        $service->recordDeploy(self::PUBLIC_ID_42, $sha, now()->addMinute(), localBypassGit: true);

        $fulfillment = $service->fulfill(self::PUBLIC_ID_42, 'Updated summer hours on the contact section.', localBypassGit: true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/fulfilled'
                && $request->hasHeader('Authorization', 'Bearer test-secret')
                && $request['summary'] === 'Updated summer hours on the contact section.';
        });

        $this->assertNotNull($fulfillment->fulfilled_at);
        $this->assertSame('Updated summer hours on the contact section.', $fulfillment->summary);
        $this->assertSame($sha, $fulfillment->fulfill_deploy_git_sha);
        $this->assertCount(0, JoesChangeFulfillment::pending());
    }

    public function test_abandon_removes_pending_row_and_notifies_main_site(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/abandon' => Http::response(['status' => 'abandoned']),
        ]);

        app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, now()->subDay());

        app(JoesFulfillmentService::class)->abandon(self::PUBLIC_ID_42);

        Http::assertSent(fn ($request) => $request->url() === 'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/abandon');
        $this->assertNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID_42));
    }

    public function test_fulfilling_one_request_leaves_other_pending(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/fulfilled' => Http::response(['status' => 'fulfilled']),
        ]);

        $service = app(JoesFulfillmentService::class);
        $service->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $service->claim(self::PUBLIC_ID_43, 43, now()->subDay());

        $sha = 'abc1234567890abc1234567890abc1234567890';
        JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID_42)?->forceFill(['claim_deploy_git_sha' => $sha])->save();

        $service->recordDeploy(self::PUBLIC_ID_42, $sha, now()->addMinute(), localBypassGit: true);
        $service->fulfill(self::PUBLIC_ID_42, 'Done.', localBypassGit: true);

        $this->assertCount(1, JoesChangeFulfillment::pending());
        $this->assertSame(self::PUBLIC_ID_43, JoesChangeFulfillment::pending()->first()->main_request_public_id);
    }

    public function test_record_deploy_rejects_matching_claim_sha_without_local_bypass(): void
    {
        $sha = 'abc1234567890abc1234567890abc1234567890';
        $claim = app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy git SHA matches the SHA recorded at claim time');

        app(JoesFulfillmentService::class)->recordDeploy(
            self::PUBLIC_ID_42,
            $sha,
            now()->addMinute(),
        );
    }

    public function test_record_deploy_rejects_deploy_timestamp_before_claim(): void
    {
        $sha = 'def4567890abcdef4567890abcdef4567890ab';
        $claim = app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => 'abc1234567890abc1234567890abc1234567890'])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy timestamp predates this claim');

        app(JoesFulfillmentService::class)->recordDeploy(
            self::PUBLIC_ID_42,
            $sha,
            now()->subMinute(),
            localBypassGit: true,
        );
    }

    public function test_record_deploy_rejects_rollback_sha(): void
    {
        $head = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD 2>/dev/null'));
        $parent = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse HEAD^ 2>/dev/null'));

        if ($head === '' || $parent === '') {
            $this->markTestSkipped('Git history unavailable for rollback test.');
        }

        $claim = app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => $head])->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Deploy git SHA is not ahead of the claim SHA');

        app(JoesFulfillmentService::class)->recordDeploy(self::PUBLIC_ID_42, $parent, now()->addMinute());
    }

    public function test_fulfill_rejects_failed_webhook(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42.'/fulfilled' => Http::response(['error' => 'nope'], 500),
        ]);

        $service = app(JoesFulfillmentService::class);
        $sha = 'abc1234567890abc1234567890abc1234567890';
        $claim = $service->claim(self::PUBLIC_ID_42, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();
        $service->recordDeploy(self::PUBLIC_ID_42, $sha, now()->addMinute(), localBypassGit: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Main site webhook failed');

        $service->fulfill(self::PUBLIC_ID_42, 'Updated summer hours.', localBypassGit: true);
    }

    public function test_claim_link_starts_pending_claim(): void
    {
        $submittedAt = now()->subDay()->timestamp;
        $expires = now()->addHour()->timestamp;
        $signature = $this->claimSignature(self::PUBLIC_ID_42, $submittedAt, $expires);

        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42 => Http::response(
                $this->openPreviewPayload($submittedAt),
            ),
        ]);

        $this->get('/joes/requests/'.self::PUBLIC_ID_42.'/claim?number=42&submitted_at='.$submittedAt.'&expires='.$expires.'&signature='.$signature)
            ->assertOk()
            ->assertSee('Request #42 is claimed');

        $this->assertNotNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID_42));
    }

    public function test_claim_link_skips_main_site_when_pending_claim_exists(): void
    {
        $submittedAt = now()->subDay()->timestamp;
        $expires = now()->addHour()->timestamp;
        $signature = $this->claimSignature(self::PUBLIC_ID_42, $submittedAt, $expires);

        app(JoesFulfillmentService::class)->claim(self::PUBLIC_ID_42, 42, Carbon::createFromTimestampUTC($submittedAt));

        Http::fake();

        $this->get('/joes/requests/'.self::PUBLIC_ID_42.'/claim?number=42&submitted_at='.$submittedAt.'&expires='.$expires.'&signature='.$signature)
            ->assertOk()
            ->assertSee('Request #42 is claimed');

        Http::assertNothingSent();
    }

    public function test_claim_link_shows_already_fulfilled_for_completed_request(): void
    {
        $submittedAt = now()->subDay()->timestamp;
        $expires = now()->addHour()->timestamp;
        $signature = $this->claimSignature(self::PUBLIC_ID_42, $submittedAt, $expires);

        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID_42 => Http::response(
                $this->completedPreviewPayload($submittedAt),
            ),
        ]);

        $this->get('/joes/requests/'.self::PUBLIC_ID_42.'/claim?number=42&submitted_at='.$submittedAt.'&expires='.$expires.'&signature='.$signature)
            ->assertOk()
            ->assertSee('Request #42 is complete')
            ->assertSee('Updated summer hours.')
            ->assertSee('No further action is needed');

        $this->assertSame(0, JoesChangeFulfillment::query()->count());
    }

    public function test_claim_link_rejects_invalid_signature(): void
    {
        $this->get('/joes/requests/'.self::PUBLIC_ID_42.'/claim?number=42&submitted_at='.now()->subDay()->timestamp.'&expires='.now()->addHour()->timestamp.'&signature=invalid')
            ->assertForbidden();
    }

    public function test_claim_link_rejects_expired_signature(): void
    {
        $submittedAt = now()->subDay()->timestamp;
        $expires = now()->subMinute()->timestamp;
        $signature = $this->claimSignature(self::PUBLIC_ID_42, $submittedAt, $expires);

        $this->get('/joes/requests/'.self::PUBLIC_ID_42.'/claim?number=42&submitted_at='.$submittedAt.'&expires='.$expires.'&signature='.$signature)
            ->assertForbidden();
    }

    private function claimSignature(string $publicId, int $submittedAt, int $expires): string
    {
        return hash_hmac('sha256', "{$publicId}|{$submittedAt}|{$expires}", 'test-secret');
    }

    /**
     * @return array<string, mixed>
     */
    private function openPreviewPayload(int $submittedAt): array
    {
        return [
            'public_id' => self::PUBLIC_ID_42,
            'number' => 42,
            'client_name' => '',
            'client_email' => 'owner@example.com',
            'request' => 'Please update summer hours.',
            'submitted_at' => Carbon::createFromTimestampUTC($submittedAt)->toIso8601String(),
            'completed_at' => null,
            'fulfillment_summary' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completedPreviewPayload(?int $submittedAt = null): array
    {
        $submittedAt ??= now()->subDay()->timestamp;

        return [
            'public_id' => self::PUBLIC_ID_42,
            'number' => 42,
            'client_name' => '',
            'client_email' => 'owner@example.com',
            'request' => 'Please update summer hours.',
            'submitted_at' => Carbon::createFromTimestampUTC($submittedAt)->toIso8601String(),
            'completed_at' => now()->subMinutes(30)->toIso8601String(),
            'fulfillment_summary' => 'Updated summer hours.',
        ];
    }
}
