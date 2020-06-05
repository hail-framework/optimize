<?php

namespace Hail\Optimize;

use Hail\Optimize\Adapter\{
    Apcu, Redis, WinCache, Yac, Memory
};
use Hail\Optimize\Exception\{
    FileNotExistsException, FileTypeErrorException
};

/**
 * "Optimize" uses memory to cache file data or the results of complex operations,
 * reducing file system IO and improving performance.
 *
 * @package Hail\Optimize
 */
class Optimize
{
    private const ADAPTERS = [
        'apcu' => Apcu::class,
        'wincache' => WinCache::class,
        'yac' => Yac::class,
        'redis' => Redis::class,
        'memory' => Memory::class,
    ];

    /**
     * @var self
     */
    private static $instance;

    /**
     * @var AdapterInterface|null
     */
    private $adapter;

    /**
     * @var int
     */
    private $delay;

    public function __construct(array $config)
    {
        $adapter = $config['adapter'] ?? 'auto';
        if ($adapter === 'none') {
            return;
        }

        $this->delay = $config['delay'];

        $adapters = [];
        if ($adapter === 'auto') {
            $adapters = self::ADAPTERS;
        } elseif (isset(self::ADAPTERS[$adapter])) {
            $adapters = [self::ADAPTERS[$adapter]];
        } elseif (\is_a($adapter, AdapterInterface::class, true)) {
            $adapters = [$adapter];
        }

        if ($adapters === []) {
            $adapters = self::ADAPTERS;
        }

        foreach ($adapters as $class) {
            $adapter = $class::getInstance($config);
            if ($adapter !== null) {
                $this->adapter = $adapter;
                break;
            }
        }
    }

    public static function getInstance(): ?self
    {
        if (self::$instance === null) {
            self::$instance = new self([
                'adapter' => \getenv('HAIL_OPTIMIZE_ADAPTER') ?: null,
                'redis' => \getenv('HAIL_OPTIMIZE_REDIS') ?: null,
                'delay' => (int) \getenv('HAIL_OPTIMIZE_DELAY'),
            ]);
        }

        return self::$instance;
    }

    /**
     * @param string $key
     *
     * @return false|mixed
     */
    public function get(string $key)
    {
        if ($this->adapter === null) {
            return false;
        }

        return $this->adapter->get($key);
    }

    public function set(string $key, $value, int $ttl = 0): bool
    {
        if ($this->adapter === null) {
            return false;
        }

        return $this->adapter->set($key, $value, $ttl);
    }

    /**
     * @param string              $file
     * @param callable|mixed|null $reader
     *
     * @return mixed
     */
    private function read(string $file, $reader = null)
    {
        if ($reader !== null) {
            if (\is_callable($reader)) {
                return $reader($file);
            }

            return $reader;
        }
        $ext = \strrchr($file, '.');
        switch ($ext) {
            case '.php':
                return include $file;

            case '.json':
                return \json_decode(
                    \file_get_contents($file), true
                );

            default:
                throw new FileTypeErrorException('File type can not support:' . $ext);
        }
    }

    /**
     * @param string              $file
     * @param callable|mixed|null $reader
     *
     * @return mixed
     */
    public function load(string $file, $reader = null)
    {
        $file = \realpath($file);
        if ($file === false) {
            throw new FileNotExistsException('File not exists');
        }

        if ($this->adapter === null) {
            return $this->read($file, $reader);
        }

        $data = $this->adapter->get($file);

        if ($data !== false &&
            ($data[1] + $this->delay) >= ($now = time()) &&
            \filemtime($file) >= $data[1]
        ) {
            $data = false;
        }

        if ($data === false) {
            $data = $this->read($file, $reader);
            $this->adapter->set($file, [$data, $now ?? \time()]);
        } else {
            $data = $data[0];
        }

        return $data;
    }
}
