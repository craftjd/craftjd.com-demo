<?php

namespace App\Services;

use App\Starter\Services\FlatFileStore;

class SiteContent
{
    public function __construct(
        protected FlatFileStore $store,
    ) {}

    public function get(string $name): array
    {
        return $this->store->all($name);
    }

    public function string(string $name, string $key, string $default = ''): string
    {
        $value = $this->get($name)[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    public function list(string $name, string $key = 'items'): array
    {
        $items = $this->get($name)[$key] ?? [];

        return is_array($items) ? $items : [];
    }

    public function categories(string $name): array
    {
        $categories = $this->get($name)['categories'] ?? [];

        if (is_array($categories) && $categories !== []) {
            return $categories;
        }

        $items = $this->list($name);

        return $items !== [] ? [['name' => null, 'items' => $items]] : [];
    }
}
