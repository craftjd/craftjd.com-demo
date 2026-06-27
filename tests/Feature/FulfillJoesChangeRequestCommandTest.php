<?php

namespace Tests\Feature;

use App\Models\JoesChangeFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FulfillJoesChangeRequestCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('joes.main_site_webhook_url', 'https://craftjd.test/api/joes/requests');
        Config::set('joes.webhook_secret', 'test-secret');
        Config::set('joes.local_deploy_bypass_git', true);
    }

    public function test_fulfill_command_abandons_when_abandon_is_chosen(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID => Http::response($this->previewPayload()),
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/abandon' => Http::response(['status' => 'abandoned']),
        ]);

        app(\App\Services\JoesFulfillmentService::class)->claim(self::PUBLIC_ID, 42, now()->subDay());

        $this->artisan('joes:fulfill', [
            'publicId' => self::PUBLIC_ID,
            'summary' => 'Updated summer hours.',
        ])
            ->expectsChoice(
                'Confirm next step',
                'Abandon on both sites (main site + Joe\'s pending row)',
                [
                    'Fulfill this request on the main site',
                    'Abandon on both sites (main site + Joe\'s pending row)',
                    'Keep pending — do nothing',
                ],
            )
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/abandon');
        $this->assertNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID));
    }

    public function test_fulfill_command_keeps_pending_when_keep_is_chosen(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID => Http::response($this->previewPayload()),
        ]);

        app(\App\Services\JoesFulfillmentService::class)->claim(self::PUBLIC_ID, 42, now()->subDay());

        $this->artisan('joes:fulfill', [
            'publicId' => self::PUBLIC_ID,
            'summary' => 'Updated summer hours.',
        ])
            ->expectsChoice(
                'Confirm next step',
                'Keep pending — do nothing',
                [
                    'Fulfill this request on the main site',
                    'Abandon on both sites (main site + Joe\'s pending row)',
                    'Keep pending — do nothing',
                ],
            )
            ->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertNotNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID));
    }

    public function test_fulfill_command_fulfills_when_fulfill_is_chosen(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID => Http::response($this->previewPayload()),
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/fulfilled' => Http::response(['status' => 'fulfilled']),
        ]);

        $service = app(\App\Services\JoesFulfillmentService::class);
        $sha = 'abc1234567890abc1234567890abc1234567890';
        $claim = $service->claim(self::PUBLIC_ID, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();
        $service->recordDeploy(self::PUBLIC_ID, $sha, now()->addMinute(), localBypassGit: true);

        $this->artisan('joes:fulfill', [
            'publicId' => self::PUBLIC_ID,
            'summary' => 'Updated summer hours.',
        ])
            ->expectsChoice(
                'Confirm next step',
                'Fulfill this request on the main site',
                [
                    'Fulfill this request on the main site',
                    'Abandon on both sites (main site + Joe\'s pending row)',
                    'Keep pending — do nothing',
                ],
            )
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/fulfilled');
        $this->assertNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID));
    }

    public function test_fulfill_command_fulfills_when_yes_flag_is_passed(): void
    {
        Http::fake([
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID => Http::response($this->previewPayload()),
            'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/fulfilled' => Http::response(['status' => 'fulfilled']),
        ]);

        $service = app(\App\Services\JoesFulfillmentService::class);
        $sha = 'abc1234567890abc1234567890abc1234567890';
        $claim = $service->claim(self::PUBLIC_ID, 42, now()->subDay());
        $claim->forceFill(['claim_deploy_git_sha' => $sha])->save();
        $service->recordDeploy(self::PUBLIC_ID, $sha, now()->addMinute(), localBypassGit: true);

        $this->artisan('joes:fulfill', [
            'publicId' => self::PUBLIC_ID,
            'summary' => 'Updated summer hours.',
            '--yes' => true,
        ])->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://craftjd.test/api/joes/requests/'.self::PUBLIC_ID.'/fulfilled');
        $this->assertNull(JoesChangeFulfillment::pendingForPublicId(self::PUBLIC_ID));
    }

    /**
     * @return array<string, mixed>
     */
    private function previewPayload(): array
    {
        return [
            'public_id' => self::PUBLIC_ID,
            'number' => 42,
            'client_name' => 'Alex Guest',
            'client_email' => 'alex@example.com',
            'request' => 'Please update summer hours on the contact page.',
            'submitted_at' => now()->subDay()->toIso8601String(),
        ];
    }
}
