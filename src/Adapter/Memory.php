<?php

namespace Hail\Optimize\Adapter;

use Hail\Optimize\AdapterInterface;

class Memory implements AdapterInterface
{
    private array $cache = [];

    public static function make(array $config): ?static
    {
        return new static();
    }

    public function get(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        [$value, $expire] = $this->cache[$key];
        if ($expire > 0 && $expire < \time()) {
            return null;
        }

        return $value;
    }

    public function set(string $key, array $value, int $ttl = 0): bool
    {
        $expire = 0;
        if ($ttl > 0) {
            $expire = \time() + $ttl;
        }

        $this->cache[$key] = [$value, $expire];

        return true;
    }
}
