<?php

namespace Tests\Unit;

use App\Support\JoesMainSiteUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JoesMainSiteUrlTest extends TestCase
{
    #[DataProvider('sailRewriteCases')]
    public function test_resolves_localhost_to_host_docker_internal_in_sail(string $input, string $expected): void
    {
        $this->assertSame($expected, JoesMainSiteUrl::resolve($input, runningInSail: true));
    }

    public static function sailRewriteCases(): array
    {
        return [
            'default port' => [
                'http://localhost/api/joes/requests',
                'http://host.docker.internal/api/joes/requests',
            ],
            'custom port' => [
                'http://localhost:8000/api/joes/requests',
                'http://host.docker.internal:8000/api/joes/requests',
            ],
            'loopback ip' => [
                'http://127.0.0.1/api/joes/requests',
                'http://host.docker.internal/api/joes/requests',
            ],
        ];
    }

    public function test_leaves_url_unchanged_outside_sail(): void
    {
        $url = 'http://localhost/api/joes/requests';

        $this->assertSame($url, JoesMainSiteUrl::resolve($url, runningInSail: false));
    }

    public function test_leaves_production_url_unchanged_in_sail(): void
    {
        $url = 'https://craftjd.com/api/joes/requests';

        $this->assertSame($url, JoesMainSiteUrl::resolve($url, runningInSail: true));
    }
}
