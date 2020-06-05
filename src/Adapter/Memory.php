<?php

namespace Hail\Optimize\Adapter;

use Hail\Optimize\AdapterInterface;

class Memory implements AdapterInterface
{
    private static $instance;
    private $cache;

    public static function getInstance(array $config): ?AdapterInterface
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function get(string $key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        [$value, $expire] = $this->cache[$key];
        if ($expire > 0 && $expire < \time()) {
            return false;
        }

        return $value;
    }

    public function set(string $key, $value, int $ttl = 0): bool
    {
        $expire = 0;
        if ($ttl > 0) {
            $expire = \time() + $ttl;
        }

        $this->cache[$key] = [$value, $expire];

        return true;
    }
}
