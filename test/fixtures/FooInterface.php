<?php

namespace Phaser\Fixtures;

interface FooInterface {
    public function doFoo(FooInterface $foo = null, $bar = 'foobar', $biz = 5.5, $baz = false);
}
