<?php

namespace Nark;

use PHPUnit\Framework\TestCase;
use Equip\Structure\UnorderedList;

class SequentialInvocationHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function returnsValuesAccordingToTheGivenSequenceOfReturnables()
    {
        $assertInvocationSequenceYieldsExpectedValues = function ($givenReturnables, $expectedReturnedValueSequence, $messageOnFailure) {
            $sut = new SequentialInvocationHandler(new UnorderedList($givenReturnables));
            $this->assertEquals($expectedReturnedValueSequence, [$sut(), $sut(), $sut()],
                $messageOnFailure);
        };

        $staticReturnableLists = [
            [1, 2, 3],
            [true, null, false],
            ['a', function () { return 'b'; }, 'c'],
            [[], [1, 2], new \stdClass()]
        ];
        foreach ($staticReturnableLists as $staticReturnableList) {
            $assertInvocationSequenceYieldsExpectedValues($staticReturnableList, $staticReturnableList,
                'Failed to return expected static values in sequence');
        }
        //---------------------------------------------------------------------
        $returnableListWithWrappedCallable = [
            1,
            2,
            new WrappedCallable(function () { return 4; })
        ];
        $assertInvocationSequenceYieldsExpectedValues($returnableListWithWrappedCallable, [1, 2, 4],
            'Failed to return value computed by wrapped callable');
    }

    /**
     * @test
     */
    public function returnsAFallbackValueAfterGivenSequenceOfReturnablesIsExtinguished()
    {
        $someReturnableSet = [1, 2, 3];
        $assertExpectedFallbackResult = function ($givenFallbackValue, $expectedFallbackResult, $messageOnFailure) use ($someReturnableSet) {
            $sut = new SequentialInvocationHandler(new UnorderedList($someReturnableSet), $givenFallbackValue);
            $returnedSequence = [$sut(), $sut(), $sut()];
            $this->assertEquals($expectedFallbackResult, $sut(),
                $messageOnFailure);
        };

        $staticFallbackValues = [
            null,
            false,
            function () { return false; },
            new \stdClass()
        ];
        foreach ($staticFallbackValues as $staticValue) {
            $assertExpectedFallbackResult($staticValue, $staticValue, 'Failed to return the expected fallback value');
        }
        //---------------------------------------------------------------------
        $fallbackCalculationCallable = new WrappedCallable(function () { return 'foobar'; });
        $assertExpectedFallbackResult($fallbackCalculationCallable, 'foobar', 'Failed to use a callable to compute and return the fallback value');
    }
}
