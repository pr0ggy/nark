<?php

namespace Nark;

use InvalidArgumentException;

/**
 * Simple wrapper for a callable
 *
 * @package Nark
 */
class WrappedCallable
{
    private $callable;

    /**
     * @param callable|string $callable the callable to wrap
     */
    public function __construct($callable)
    {
        if (is_callable($callable) === false) {
            throw new InvalidArgumentException('Arg 1 of valueReturnedBy() must be callable');
        }

        $this->callable = $callable;
    }

    /**
     * When the instance is invoked, just invoke the wrapped callable
     *
     * @param  array $args the arguments passed to the invocation
     * @return mixed the value returned by the wrapped callable
     */
    public function __invoke(...$args)
    {
        $callable = $this->callable;
        return $callable(...$args);
    }
}
