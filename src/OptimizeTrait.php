<?php

namespace Hail\Optimize;

trait OptimizeTrait
{
    protected Optimize $optimize;

    public function optimize(): Optimize
    {
        return $this->optimize ??= Optimize::getInstance();
    }
}
