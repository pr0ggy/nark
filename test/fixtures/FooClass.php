<?php

namespace Nark\Fixtures;

class FooClass extends FooAbstractClass {
    public function doFoo(FooInterface $foo = null, $bar = 'foobar baz', $biz = 5.5, $baz = false)
    {
        return "{$bar} bizbaz";
    }

    final public function doFoo2() {
        return 'foo2';
    }
}
