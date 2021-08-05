<?php

namespace Hail\Optimize\Adapter;

\defined('PHP_REDIS_EXTENSION') || \define('PHP_REDIS_EXTENSION', \extension_loaded('redis'));

use Hail\Optimize\AdapterInterface;

class Redis implements AdapterInterface
{
    private \Redis $redis;

    public static function make(array $config): ?static
    {
        if (!PHP_REDIS_EXTENSION || empty($config['redis'])) {
            return null;
        }

        try {
            return new static($config['redis']);
        } catch (\Throwable) {
            return null;
        }
    }

    public function __construct(string $config)
    {
        [$type, $redis] = \explode('://', $config, 2);

        if ($type !== 'unix' && $type !== 'tcp') {
            throw new \InvalidArgumentException('Redis host invalid!');
        }

        $arr = \explode('?', $redis, 2);
        $redis = $arr[0];

        $port = null;
        if ($type === 'tcp') {
            $tcp = \explode(':', $redis, 2);
            $redis = $tcp[0];
            $port = $tcp[1] ?? null;
        }

        $this->redis = new \Redis();

        if ($port === null) {
            $return = $this->redis->connect($redis);
        } else {
            $return = $this->redis->connect($redis, $port);
        }

        if (!$return) {
            throw new \RuntimeException('Redis connect failed!');
        }

        if (isset($arr[1])) {
            $params = [];
            \parse_str($arr[1], $params);

            foreach ($params as $k => $v) {
                if ($k === 'auth' || $k === 'select') {
                    $this->redis->$k($v);
                }
            }
        }
    }

    public function get(string $key): ?array
    {
        $ret = $this->redis->get($key);
        if ($ret === false) {
            return null;
        }

        return $ret;
    }

    public function set(string $key, array $value, int $ttl = 0): bool
    {
        if ($ttl > 0) {
            return $this->redis->setEx($key, $ttl, $value) === true;
        }

        return $this->redis->set($key, $value) === true;
    }
}
