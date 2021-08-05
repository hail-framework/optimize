<?php

namespace Hail\Optimize;


interface AdapterInterface
{
    public static function make(array $config): ?static;

    public function get(string $key): ?array;

    public function set(string $key, array $value, int $ttl = 0): bool;
}
