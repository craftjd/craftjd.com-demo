<?php

namespace App\Support;

class JoesMainSiteUrl
{
    public static function resolve(?string $url, bool $runningInSail): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (! $runningInSail) {
            return $url;
        }

        $replacements = [
            '://localhost:' => '://host.docker.internal:',
            '://localhost/' => '://host.docker.internal/',
            '://127.0.0.1:' => '://host.docker.internal:',
            '://127.0.0.1/' => '://host.docker.internal/',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $url);
    }
}
