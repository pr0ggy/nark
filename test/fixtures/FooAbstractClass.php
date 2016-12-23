<?php

namespace Phaser\Fixtures;

abstract class FooAbstractClass implements FooInterface {
    abstract public function doFoo(FooInterface $foo = null, $bar = 'foobar', $biz = 5.5, $baz = false);
}
