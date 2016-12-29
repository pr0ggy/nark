<?php

namespace Nark;

use PHPUnit\Framework\TestCase;

class SpyBaseTest extends TestCase
{
    /**
     * @test
     */
    public function callingMethodCorrectlyRecordsMethodCallOnInternalReflectorInstance()
    {
        $sut = $this->createSpyBaseSUT();

        $sut->doFoo(true, 10);

        // verify method invocations recorded correctly
        $this->assertCount(1, $sut->reflector()->doFoo,
            'no "doFoo" invocation recorded as expected');
        $this->assertCount(1, $sut->reflector()->doFoo(true, 10),
            '"doFoo" invocation recorded, but not with expected arguments');
        $this->assertCount(0, $sut->reflector()->doFoo(false, 5),
            'args given in "doFoo" invocation were not done so in a way that distinguishes the invocation');

        // new method call
        $sut->doBar(false);

        // verify records of previous invocations still present
        $this->assertCount(1, $sut->reflector()->doFoo,
            'after recording an invocation of a different method, "doFoo" invocation recorded disappeared');
        $this->assertCount(1, $sut->reflector()->doFoo(true, 10),
            'after recording an invocation of a different method, unable to recall "doFoo" invocation with specific arguments');
        $this->assertCount(0, $sut->reflector()->doFoo(false, 5),
            'after recording an invocation of a different method, args given in "doFoo" invocation with specific args unabled to be distinguished');

        // verify new method invocation recorded correctly
        $this->assertCount(1, $sut->reflector()->doBar,
            'second invocation ("doBar") not recorded as expected');
        $this->assertCount(1, $sut->reflector()->doBar(false),
            'second invocation ("doBar") recorded, but not with expected arguments');
        $this->assertCount(0, $sut->reflector()->doBar(true),
            'args given in second invocation ("doBar") were not done so in a way that distinguishes the invocation');

        // repeated method call, different args
        $sut->doBar(true);

        // verify new method invocation recorded correctly
        $this->assertCount(2, $sut->reflector()->doBar,
            'when invoking the same method multiple times with different args, unable to recall the aggregate count of invocations on that method (regardless of args)');
        $this->assertCount(1, $sut->reflector()->doBar(false),
            'when invoking the same method multiple times with different args, unable to distinguish invocations by their arguments');
        $this->assertCount(1, $sut->reflector()->doBar(true),
            'when invoking the same method multiple times with different args, unable to distinguish invocations by their arguments');
    }

    private function createSpyBaseSUT($methodNameToStubMap = [], $defaultMethodInvocationResponse = null)
    {
        return new SpyBase(
            new SpyReflector(),
            $methodNameToStubMap,
            function (...$args) use ($defaultMethodInvocationResponse) { return $defaultMethodInvocationResponse; }
        );
    }

    /**
     * @test
     */
    public function callingMethodReturnsResponseFromDefaultInvocationHandlerIfMethodIsNotPresentInStubMap()
    {
        $defaultMethodInvocationResponse = 'DEFAULT_HANDLER_RESPONSE';
        $emptyMethodNameToStupMap = [];
        $sut = $this->createSpyBaseSUT($emptyMethodNameToStupMap, $defaultMethodInvocationResponse);

        $this->assertEquals($defaultMethodInvocationResponse, $sut->doFoo(),
            'calling method without an explicitly defined invocation handler did not return response from default invocation handler as expected');
        $this->assertEquals($defaultMethodInvocationResponse, $sut->doBar(),
            'calling method without an explicitly defined invocation handler did not return response from default invocation handler as expected');
    }

    /**
     * @test
     */
    public function callingMethodReturnsResponseFromSpecifiedInvocationHandlerIfMethodIsPresentInStubMap()
    {
        $doFooMethodInvocationResponse = 'FOO_HANDLER_RESPONSE';
        $defaultMethodInvocationResponse = 'DEFAULT_HANDLER_RESPONSE';
        $methodNameToStupMap = ['doFoo' => returnsStaticValue($doFooMethodInvocationResponse)];
        $sut = $this->createSpyBaseSUT($methodNameToStupMap, $defaultMethodInvocationResponse);

        $this->assertEquals($doFooMethodInvocationResponse, $sut->doFoo(),
            'calling method with an explicitly defined invocation handler did not return the response generated by that handler');
        $this->assertEquals($defaultMethodInvocationResponse, $sut->doBar(),
            'calling method without an explicitly defined invocation handler did not return response from default invocation handler as expected');
    }
}
