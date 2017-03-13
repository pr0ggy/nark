<?php

namespace Nark\Fixtures;

interface FooInterface
{
    public function doFoo(FooInterface $foo = null, $bar = 'foobar baz', $biz = 5.5, $baz = false);
}
