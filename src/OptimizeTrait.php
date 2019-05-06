<?php

namespace Hail\Optimize;

use Hail\Optimize\Exception\{
    FileNotExistsException, FileTypeErrorException
};

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
    protected static $__optimizeReader = [];

    protected static function optimizeInstance(Optimize $object = null): Optimize
    {
        return static::$__optimizeInstance = $object ?? Optimize::getInstance();
    }

    protected static function optimizePrefix(string $prefix): void
    {
        static::$__optimizePrefix = $prefix;
    }

    protected static function optimizeReader(string $ext, callable $callback): void
    {
        if ($ext[0] !== '.') {
            $ext = '.' . $ext;
        }

        static::$__optimizeReader[$ext] = $callback;
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

    protected static function optimizeLoad(string $file)
    {
        if (!\file_exists($file)) {
            throw new FileNotExistsException('File not exists');
        }

        $filename = \basename($file);
        $ext = \strrchr($filename, '.');
        $name = \substr($filename, 0, -\strlen($ext));

        $data = static::optimizeGet($name, $file);

        if ($data === false) {
            if (isset(static::$__optimizeReader[$ext])) {
                $data = (static::$__optimizeReader[$ext])($file);
            } elseif ($ext === '.php') {
                $data = include $file;
            } elseif ($ext === '.json') {
                $data = \json_decode(
                    \file_get_contents($file), true
                );
            } else {
                throw new FileTypeErrorException('File type can not support:' . $ext);
            }

            static::optimizeSet($name, $data, $file);
        }

        return $data;
    }
}
