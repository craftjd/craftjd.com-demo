<?php

namespace Tests\Feature;

use App\Models\JoesChangeFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoesFulfillmentStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_lists_pipeline_steps(): void
    {
        JoesChangeFulfillment::query()->create([
            'main_request_public_id' => '11111111-1111-4111-8111-111111111111',
            'main_request_id' => 42,
            'main_request_submitted_at' => now()->subDay(),
            'claimed_at' => now()->subHour(),
        ]);

        JoesChangeFulfillment::query()->create([
            'main_request_public_id' => '22222222-2222-4222-8222-222222222222',
            'main_request_id' => 43,
            'main_request_submitted_at' => now()->subDay(),
            'claimed_at' => now()->subHours(2),
            'deploy_git_sha' => 'abc1234567890abc1234567890abc1234567890',
            'deploy_deployed_at' => now()->subHour(),
        ]);

        JoesChangeFulfillment::query()->create([
            'main_request_public_id' => '33333333-3333-4333-8333-333333333333',
            'main_request_id' => 44,
            'main_request_submitted_at' => now()->subDays(2),
            'claimed_at' => now()->subDay(),
            'deploy_git_sha' => 'def1234567890def1234567890def1234567890',
            'deploy_deployed_at' => now()->subHours(20),
            'fulfilled_at' => now()->subHour(),
            'fulfill_deploy_git_sha' => 'def1234567890def1234567890def1234567890',
            'summary' => 'Updated summer hours.',
        ]);

        $this->artisan('joes:status')
            ->assertSuccessful()
            ->expectsOutputToContain('Joe\'s')
            ->expectsOutputToContain('#42  claimed')
            ->expectsOutputToContain('#43  deployed')
            ->expectsOutputToContain('#44  done');
    }
}
