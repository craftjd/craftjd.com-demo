<?php

if (! function_exists('starter_feature')) {
    function starter_feature(string $feature): bool
    {
        return (bool) config("starter.features.{$feature}", false);
    }
}

if (! function_exists('starter_config')) {
    function starter_config(string $key, mixed $default = null): mixed
    {
        $clientValue = config("client.{$key}");

        if ($clientValue !== null) {
            return $clientValue;
        }

        return config("starter.{$key}", $default);
    }
}
