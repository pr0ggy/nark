<?php

namespace Nark\Fixtures;

class FooClass extends FooAbstractClass {
    public function doFoo(FooInterface $foo = null, $bar = 'foobar', $biz = 5.5, $baz = false)
    {
        return "{$bar} bizbaz";
    }
}
