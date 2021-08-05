<?php

namespace Hail\Optimize\Adapter;

\defined('YAC_EXTENSION') || \define('YAC_EXTENSION', \class_exists('\Yac'));
if (!YAC_EXTENSION) {
    \define('YAC_MAX_KEY_LEN', 48);
}

use Hail\Optimize\AdapterInterface;

class Yac implements AdapterInterface
{
    private static int $pos;

    private \Yac $yac;

    public static function make(array $config): ?static
    {
        if (!YAC_EXTENSION) {
            return null;
        }

        static::$pos ??= \YAC_MAX_KEY_LEN - 1;
        return new static();
    }

    public function __construct()
    {
        $this->yac = new \Yac();
    }

    public function get(string $key): ?array
    {
        $ret = $this->yac->get(
            self::key($key)
        );

        if ($ret === false) {
            return null;
        }

        return $ret;
    }

    public function set(string $key, array $value, int $ttl = 0): bool
    {
        return $this->yac->set(self::key($key), $value, $ttl) !== false;
    }

    private static function key(string $key): string
    {
        if (isset($key[static::$pos])) {
            return \sha1($key);
        }

        return $key;
    }
}
