<?php

namespace Hail\Optimize;

trait OptimizeTrait
{
    /**
     * @var Optimize
     */
    protected $optimize;

    public function optimize(): Optimize
    {
        if ($this->optimize === null) {
            $this->optimize = Optimize::getInstance();
        }

        return $this->optimize;
    }
}
