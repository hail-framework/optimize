<?php

namespace Hail\Optimize;

trait OptimizeTrait
{
    /**
     * @var Optimize
     */
    protected static $__optimizeInstance;

    /**
     * @var string
     */
    protected static $__optimizePrefix = '';

    /**
     * @var callable
     */
    protected static $__optimizeReader;

    protected static function optimizeInstance(Optimize $object = null): Optimize
    {
        return static::$__optimizeInstance = $object ?? Optimize::getInstance();
    }

    protected static function optimizePrefix(string $prefix)
    {
        static::$__optimizePrefix = $prefix;
    }

    protected static function optimizeReader(callable $callback)
    {
        static::$__optimizeReader = $callback;
    }

    protected static function optimizeInit(): array
    {
        $object = static::$__optimizeInstance ?? static::optimizeInstance();

        $prefix = static::class;
        if (static::$__optimizePrefix) {
            $prefix = static::$__optimizePrefix . '|' . $prefix;
        }

        return [$object, $prefix];
    }

    /**
     * @param string            $key
     * @param string|array|null $file
     *
     * @return mixed
     */
    protected static function optimizeGet(string $key, $file = null)
    {
        [$object, $prefix] = static::optimizeInit();

        return $object->get($prefix, $key, $file);
    }

    /**
     * @param string|array      $key
     * @param mixed             $value
     * @param string|array|null $file
     *
     * @return mixed
     */
    protected static function optimizeSet($key, $value = null, $file = null)
    {
        [$object, $prefix] = static::optimizeInit();

        return $object->set($prefix, $key, $value, $file);
    }

    protected static function optimizeSave(string $file)
    {
        [$name, $ext] = static::optimizeFileSplit($file);

        if (static::$__optimizeReader) {
            $value =  (static::$__optimizeReader)($file);
        } elseif ($ext === '.php') {
            $value = include $file;
        } elseif ($ext === '.json') {
            $value = \json_decode(\file_get_contents($file), true);
        } else {
            throw new \RuntimeException('File type can not support:' . $ext);
        }

        return static::optimizeSet($name, $value, $file);
    }

    protected static function optimizeLoad(string $file)
    {
        [$name] = static::optimizeFileSplit($file);

        return static::optimizeGet($name, $file);
    }

    protected static function optimizeFileSplit(string $file)
    {
        if (!\file_exists($file)) {
            throw new \InvalidArgumentException('File not exists');
        }

        $filename = \basename($file);
        $ext = \strrchr($filename, '.');

        $name = \substr($filename, 0, -\strlen($ext));

        return [$name, $ext];
    }
}
