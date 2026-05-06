<?php

namespace App\Starter\Services;

use Illuminate\Support\Facades\File;

class FlatFileStore
{
    public function all(string $name): array
    {
        $path = $this->path($name);

        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : [];
    }

    public function path(string $name): string
    {
        return rtrim(config('starter.data.path'), '/')."/{$name}.json";
    }
}
