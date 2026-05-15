<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_returns_404_when_monitor_key_is_not_configured(): void
    {
        config(['starter.monitor.key' => null]);

        $this->get('/health')->assertNotFound();
    }

    public function test_returns_403_with_wrong_monitor_key(): void
    {
        config(['starter.monitor.key' => 'secret-key']);

        $this->get('/health')->assertForbidden();
        $this->get('/health', ['X-Monitor-Key' => 'wrong'])->assertForbidden();
    }

    public function test_returns_health_status_with_valid_monitor_key(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getPdo')->once()->andReturn(Mockery::mock(\PDO::class));
        DB::shouldReceive('connection')->once()->andReturn($connection);

        config([
            'starter.monitor.key' => 'secret-key',
            'client.contact_email' => 'owner@example.com',
        ]);

        File::put(storage_path('app/deploy-meta.json'), json_encode([
            'git_sha' => 'abc123',
            'deployed_at' => '2026-06-12T12:00:00Z',
        ]));

        $response = $this->get('/health', ['X-Monitor-Key' => 'secret-key']);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => [
                    'db' => true,
                    'storage' => true,
                    'mail' => true,
                ],
            ])
            ->assertJsonStructure(['client', 'env', 'deployed_at', 'git_sha']);
    }

    public function test_returns_degraded_when_contact_email_is_missing(): void
    {
        config([
            'starter.monitor.key' => 'secret-key',
            'client.contact_email' => null,
        ]);

        $this->get('/health', ['X-Monitor-Key' => 'secret-key'])
            ->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'checks' => [
                    'mail' => false,
                ],
            ]);
    }
}
