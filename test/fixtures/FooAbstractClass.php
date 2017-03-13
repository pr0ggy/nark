<?php

namespace Nark\Fixtures;

abstract class FooAbstractClass implements FooInterface
{
    public static function doStaticFoo(array $bars = []) {
        return count($bars);
    }

    public function __call($methodName, $args)
    {
        echo "Error: unknown method called: {$methodName}";
    }

    abstract public function doFoo(FooInterface $foo = null, $bar = 'foobar baz', $biz = 5.5, $baz = false);
}
