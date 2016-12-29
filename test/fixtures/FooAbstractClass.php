<?php

namespace Nark\Fixtures;

abstract class FooAbstractClass implements FooInterface {
    public static function doStaticFoo(array $bars = []) {
        return count($bars);
    }

    abstract public function doFoo(FooInterface $foo = null, $bar = 'foobar', $biz = 5.5, $baz = false);
}
