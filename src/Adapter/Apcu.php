<?php

namespace Hail\Optimize\Adapter;

\defined('APCU_EXTENSION') || \define('APCU_EXTENSION', \extension_loaded('apcu'));

use Hail\Optimize\AdapterInterface;

class Apcu implements AdapterInterface
{
    public static function make(array $config): ?static
    {
        if (!APCU_EXTENSION) {
            return null;
        }

        return new static();
    }

    public function get(string $key): ?array
    {
        $ret = \apcu_fetch($key);
        if ($ret === false) {
            return null;
        }

        return $ret;
    }

    public function set(string $key, array $value, int $ttl = 0): bool
    {
        return \apcu_store($key, $value, $ttl) === true;
    }
}
