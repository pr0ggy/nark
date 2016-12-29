<?php

namespace Nark;

use PHPUnit\Framework\TestCase;
use Equip\Structure\Dictionary;
use Equip\Structure\UnorderedList;

class SpyReflectorTest extends TestCase
{
    /**
     * @test
     */
    public function requestingTheNameOfASpyMethodAsAMemberValueFromTheReflectorReturnsListOfAllMethodInvocationsWithThatName()
    {
        $someTimestamp = microtime(true);
        $assertDoFooInvocationListReturned = function (array $doFooInvocationRecords, $scenarioDescription) {
            $givenPreRecordedInvocations = new UnorderedList($doFooInvocationRecords);
            $sut = new SpyReflector(new Dictionary(['doFoo' => $givenPreRecordedInvocations]));
            $this->assertEquals($givenPreRecordedInvocations->toArray(), $sut->doFoo->toArray(), "expected invocation list not returned when {$scenarioDescription}");
        };
        // --------------------------------------------------------------------
        $assertDoFooInvocationListReturned([],
            'no invocations matching the given name');
        // --------------------------------------------------------------------
        $assertDoFooInvocationListReturned([
            createInvocationRecord(createInvocation('doFoo', [true, 10]), $someTimestamp)
        ],
        'one invocation matching the given name');
        // --------------------------------------------------------------------
        $assertDoFooInvocationListReturned([
            createInvocationRecord(createInvocation('doFoo', [true, 10]), $someTimestamp),
            createInvocationRecord(createInvocation('doFoo', [false, 5]), ($someTimestamp+100))
        ],
        'multiple invocations matching the given name');
    }

    /**
     * @test
     */
    public function callingASpyInterfaceMethodOnTheReflectorReturnsListOfAllMethodInvocationsMatchingThatNameAndArgs()
    {
        $assertDoFooInvocationListReturned = function (
            array $doFooInvocationRecordss,
            array $doFooMethodCallArgs,
            array $expectedMatchedInvocationRecords,
            $scenarioDescription
        ) {
            $expectedMatchedInvocationRecordList = new UnorderedList($expectedMatchedInvocationRecords);
            $sut = new SpyReflector(new Dictionary(['doFoo' => new UnorderedList($doFooInvocationRecordss)]));
            $this->assertEquals($expectedMatchedInvocationRecordList->toArray(), $sut->doFoo(...$doFooMethodCallArgs)->toArray(), "expected matching invocation list not returned when {$scenarioDescription}");
        };

        $someTimestamp = microtime(true);
        // --------------------------------------------------------------------
        $assertDoFooInvocationListReturned([], [] ,[],
            'no invocations exist');
        // --------------------------------------------------------------------
        $invocationRecordPool = [ createInvocationRecord(createInvocation('doFoo', [true, 10]), $someTimestamp) ];
        $reflectorMethodInvocationArgs = [false, 10];
        $expectedMatchedInvocationRecords = [];
        $assertDoFooInvocationListReturned($invocationRecordPool, $reflectorMethodInvocationArgs, $expectedMatchedInvocationRecords,
            'reflector invocation args do not match any recorded invocations');
        // --------------------------------------------------------------------
        $invocationToMatch = createInvocationRecord(createInvocation('doFoo', [false, 5]), $someTimestamp);
        $invocationRecordPool = [
            createInvocationRecord(createInvocation('doFoo', [true, 10]), $someTimestamp),
            $invocationToMatch
        ];
        $reflectorMethodInvocationArgs = [false, 5];
        $expectedMatchedInvocationRecords = [ $invocationToMatch ];
        $assertDoFooInvocationListReturned($invocationRecordPool, $reflectorMethodInvocationArgs, $expectedMatchedInvocationRecords,
            'reflector invocation args match a subset of recorded invocations');
        // --------------------------------------------------------------------
        $invocationToMatch = createInvocationRecord(createInvocation('doFoo', [false, 5]), $someTimestamp);
        $invocationRecordPool = [
            createInvocationRecord(createInvocation('doFoo', [true, 10]), $someTimestamp),
            createInvocationRecord(createInvocation('doBar', [false, 5]), $someTimestamp),
            $invocationToMatch
        ];
        $reflectorMethodInvocationArgs = [false, 5];
        $expectedMatchedInvocationRecords = [ $invocationToMatch ];
        $assertDoFooInvocationListReturned($invocationRecordPool, $reflectorMethodInvocationArgs, $expectedMatchedInvocationRecords,
            'reflector invocation args match a subset of recorded invocations where different methods were invoked with same args');
    }

    /**
     * @test
     */
    public function withAddedInvocationRecordReturnsNewSpyReflectorWithAddedInvocationRecord()
    {
        $initialMethodToInvocationsMap = new Dictionary();
        $initialReflector = new SpyReflector($initialMethodToInvocationsMap);

        $this->assertEquals(0, count($initialReflector->doFoo));

        $invocation1Time = microtime(true);
        $updatedOnceReflector = $initialReflector->withAddedInvocationRecord(
            'doFoo',
            createInvocationRecord(
                createInvocation('doFoo', [true, 10]),
                $invocation1Time
            )
        );

        $this->assertNotSame($initialReflector, $updatedOnceReflector);
        $this->assertEquals(1, count($updatedOnceReflector->doFoo));
        $this->assertEquals($updatedOnceReflector->doFoo->toArray(), [
            ['invocation' => ['methodName' => 'doFoo', 'args' => [true, 10]], 'timestamp' => $invocation1Time]
        ], 'invocation map not updated as expected');

        $invocation2Time = microtime(true);
        $updatedTwiceReflector = $updatedOnceReflector->withAddedInvocationRecord(
            'doFoo',
            createInvocationRecord(
                createInvocation('doFoo', [false, 5]),
                $invocation2Time
            )
        );

        $this->assertNotSame($updatedOnceReflector, $updatedTwiceReflector);
        $this->assertEquals(2, count($updatedTwiceReflector->doFoo));
        $this->assertEquals($updatedTwiceReflector->doFoo->toArray(), [
            ['invocation' => ['methodName' => 'doFoo', 'args' => [true, 10]], 'timestamp' => $invocation1Time],
            ['invocation' => ['methodName' => 'doFoo', 'args' => [false, 5]], 'timestamp' => $invocation2Time]
        ], 'invocation map not updated as expected');

        $invocation3Time = microtime(true);
        $updatedThriceReflector = $updatedTwiceReflector->withAddedInvocationRecord(
            'doBar',
            createInvocationRecord(
                createInvocation('doBar', []),
                $invocation3Time
            )
        );

        $this->assertNotSame($updatedTwiceReflector, $updatedThriceReflector);
        $this->assertEquals(2, count($updatedThriceReflector->doFoo));
        $this->assertEquals(1, count($updatedThriceReflector->doBar));
        $this->assertEquals($updatedThriceReflector->doBar->toArray(), [
            ['invocation' => ['methodName' => 'doBar', 'args' => []], 'timestamp' => $invocation3Time]
        ], 'invocation map not updated as expected');
    }
}
