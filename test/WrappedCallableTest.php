<?php

namespace Nark;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class WrappedCallableTest extends TestCase
{
    /**
     * @test
     */
    public function wrapsAGivenCallableAndPassesThroughAnyInvocation()
    {
        $foo = 0;
        $callable = function () use (&$foo) {
            ++$foo;
        };

        $sut = new WrappedCallable($callable);

        $this->assertTrue(is_callable($sut), 'callable object not returned');

        $sut();
        $this->assertEquals(1, $foo, 'wrapped callable was not invoked when wrapper was invoked');
        $sut();
        $this->assertEquals(2, $foo, 'wrapped callable was not invoked when wrapper was invoked');
    }

    /**
     * @test
     */
    public function throwsInvalidArgumentExceptionIfNonCallableGivenDuringConstruction()
    {
        $ensureExceptionThrownWhenTryingToWrap = function ($description, $value) {
            try {
                $wrappedCallable = new WrappedCallable($value);
                $this->fail("exception not thrown when passing {$description}");
            } catch (InvalidArgumentException $exception) {
                return;
            }
        };

        $ensureExceptionThrownWhenTryingToWrap('null', null);
        // --------------------------------------------------------------------
        $ensureExceptionThrownWhenTryingToWrap('a boolean', true);
        // --------------------------------------------------------------------
        $ensureExceptionThrownWhenTryingToWrap('a number', 15);
        // --------------------------------------------------------------------
        $ensureExceptionThrownWhenTryingToWrap('a string not matching a loaded function name', 'bizbaz');
        // --------------------------------------------------------------------
        $obj = new \stdClass();
        $ensureExceptionThrownWhenTryingToWrap('an object', $obj);
    }
}
