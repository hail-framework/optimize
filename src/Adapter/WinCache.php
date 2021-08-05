<?php

namespace Hail\Optimize\Adapter;

\defined('WINCACHE_EXTENSION') || \define('WINCACHE_EXTENSION', \extension_loaded('wincache'));

use Hail\Optimize\AdapterInterface;

class WinCache implements AdapterInterface
{
    public static function make(array $config): ?static
    {
        if (!WINCACHE_EXTENSION) {
            return null;
        }

        return new static();
    }

    public function get(string $key): ?array
    {
        $value = \wincache_ucache_get($key, $success);
        if ($success === false) {
            return null;
        }

        return $value;
    }

    public function set(string $key, array $value, int $ttl = 0): bool
    {
        return \wincache_ucache_set($key, $value, $ttl) !== false;
    }
}
