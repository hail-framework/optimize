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
        'yac' => Yac::class,
        'apcu' => Apcu::class,
        'wincache' => WinCache::class,
        'redis' => Redis::class,
        'memory' => Memory::class,
    ];

    private static Optimize $instance;

    private ?AdapterInterface $adapter = null;

    private int $delay;

    public function __construct(array $config)
    {
        $adapter = $config['adapter'] ?? 'auto';
        if ($adapter === 'none') {
            return;
        }

        $this->delay = (int) $config['delay'];

        $adapters = self::ADAPTERS;
        if ($adapter !== 'auto') {
            if (isset(self::ADAPTERS[$adapter])) {
                $adapters = [self::ADAPTERS[$adapter]];
            } elseif (\is_a($adapter, AdapterInterface::class, true)) {
                $adapters = [$adapter];
            }
        }

        foreach ($adapters as $class) {
            $adapter = $class::make($config);
            if ($adapter !== null) {
                $this->adapter = $adapter;
                break;
            }
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self(
            [
                'adapter' => \getenv('HAIL_OPTIMIZE_ADAPTER') ?: null,
                'redis' => \getenv('HAIL_OPTIMIZE_REDIS') ?: null,
                'delay' => (int) \getenv('HAIL_OPTIMIZE_DELAY'),
            ]
        );
    }

    public function get(string $key): ?array
    {
        return $this->adapter?->get($key);
    }

    public function set(string $key, array|string $value, int $ttl = 0): bool
    {
        if ($this->adapter === null) {
            return false;
        }

        return $this->adapter->set($key, [$value, \time()], $ttl);
    }

    private function read(string $file, callable $reader = null): array|string
    {
        if ($reader !== null) {
            return $reader($file);
        }

        $ext = \strrchr($file, '.');
        return match ($ext) {
            '.php' => include $file,
            '.json' => \json_decode(\file_get_contents($file), flags: \JSON_OBJECT_AS_ARRAY | \JSON_THROW_ON_ERROR),
            default => throw new FileTypeErrorException("File type can not support: $ext"),
        };
    }

    public function load(string $file, callable $reader = null): array|string
    {
        $path = \realpath($file);
        if ($path === false) {
            throw new FileNotExistsException("File not exists: $file");
        }

        $data = $this->get($path);

        if ($data !== null &&
            ($data[1] + $this->delay) >= \time() &&
            \filemtime($path) >= $data[1]
        ) {
            $data = null;
        }

        if ($data === null) {
            $data = $this->read($path, $reader);
            $this->set($path, $data);
        } else {
            $data = $data[0];
        }

        return $data;
    }
}
