<?php

namespace Hail\Optimize;


interface AdapterInterface
{
    public static function getInstance(array $config): ?AdapterInterface;

    public function get(string $key);

    public function set(string $key, $value, int $ttl = 0): bool;
}
