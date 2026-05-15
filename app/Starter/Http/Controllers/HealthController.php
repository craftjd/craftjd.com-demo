<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $key = config('starter.monitor.key');

        if (! $key || ! hash_equals($key, (string) $request->header('X-Monitor-Key', ''))) {
            abort($key ? 403 : 404);
        }

        $checks = [
            'db' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'mail' => $this->checkMail(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'client' => config('client.site_name'),
            'env' => config('app.env'),
            'deployed_at' => $this->deployMeta('deployed_at'),
            'git_sha' => $this->deployMeta('git_sha'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function checkStorage(): bool
    {
        return is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));
    }

    protected function checkMail(): bool
    {
        if (! config('client.contact_email')) {
            return false;
        }

        if (config('mail.default') === 'smtp' && ! config('mail.mailers.smtp.host')) {
            return false;
        }

        return true;
    }

    protected function deployMeta(string $key): ?string
    {
        $path = storage_path('app/deploy-meta.json');

        if (! File::exists($path)) {
            return null;
        }

        $meta = json_decode(File::get($path), true);

        return is_array($meta) ? ($meta[$key] ?? null) : null;
    }
}
