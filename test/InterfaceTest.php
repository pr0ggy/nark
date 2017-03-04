<?php

namespace Nark;

use PHPUnit\Framework\TestCase;
use Equip\Structure\UnorderedList;
use Equip\Structure\Dictionary;
use InvalidArgumentException;

class NarkInterfaceTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function occurredChronologically_throwsInvalidArgumentExceptionIfNoArgumentsGiven()
    {
        occurredChronologically();
    }

    /**
     * @test
     */
    public function occurredChronologically_returnsWhetherOrNotAGivenSequenceOfInvocationsOccurredInChronologicalOrder()
    {
        $timestamp = microtime(true);
        $assertSequenceCheckReturns = function ($expectedResult, array $listOfInvocationLists, $onFailureMessage) {
            $listOfInvocationUnorderedLists = array_map(function (array $list) {
                return new UnorderedList($list);
            }, $listOfInvocationLists);

            $this->assertEquals(
                $expectedResult,
                occurredChronologically(...$listOfInvocationUnorderedLists),
                $onFailureMessage
            );
        };

        $someInvocationArgs = [];

        $assertSequenceCheckReturns(false, [[]],
            'returned true when given a single empty list of invocations instead of false as expected');
        // --------------------------------------------------------------------
        $assertSequenceCheckReturns(true, [[
            createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), $timestamp),
            createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+0.1))
        ]],
        'return false when given a single list of invocations instead of true as expected');
        // --------------------------------------------------------------------
        $assertSequenceCheckReturns(true, [
            [
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), $timestamp),         // A, (T)
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+0.05))
            ],
            [
                createInvocationRecord(createInvocation('doBar', $someInvocationArgs), ($timestamp+0.03))    // B, (T+0.03)
            ],
            [
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), $timestamp-0.5),
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), ($timestamp+0.35))    // C, (T+0.35)
            ]
        ],
        'return false when a sequence does exist across the given invocation lists instead of true as expected');
        // --------------------------------------------------------------------
        $assertSequenceCheckReturns(false, [
            [
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), $timestamp),         // A, (T)
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+0.1))
            ],
            [
                createInvocationRecord(createInvocation('doBar', $someInvocationArgs), ($timestamp-10))    // NOT IN SEQUENCE (T-10)
            ],
            [
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), $timestamp-0.5),
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), ($timestamp+0.35))    // C, (T+0.35)
            ]
        ],
        'return true when a sequence does NOT exist across the given invocation lists instead of false as expected');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function occurredSequentially_throwsInvalidArgumentExceptionIfNoArgumentsGiven()
    {
        occurredSequentially();
    }

    /**
     * @test
     */
    public function occurredSequentially_returnsWhetherOrNotAGivenSequenceOfInvocationsOccurredSequentially()
    {
        $timestamp = microtime(true);
        $assertSequenceCheckReturns = function ($expectedResult, array $listOfInvocationLists, $onFailureMessage) {
            $listOfInvocationUnorderedLists = array_map(function (array $list) {
                return new UnorderedList($list);
            }, $listOfInvocationLists);

            $this->assertEquals(
                $expectedResult,
                occurredSequentially(...$listOfInvocationUnorderedLists),
                $onFailureMessage
            );
        };

        $someInvocationArgs = [];
        $someTimestamp = microtime(true);
        $createInvocationRecord = function (Dictionary $invocation) use ($someTimestamp) {
            return createInvocationRecord($invocation, $someTimestamp);
        };

        $assertSequenceCheckReturns(false, [[]],
            'returned true when given a single empty list of invocations instead of false as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs));
        $assertSequenceCheckReturns(true, [[
            $doFoo1
        ]],
        'return false when given a single list of invocations instead of true as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--X--X--
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', []));
        $doFoo2 = $createInvocationRecord(createInvocation('doFoo', [true]))->withValue('previouslyRecordedInvocationRecord', $doFoo1);
        $assertSequenceCheckReturns(false, [
            [$doFoo2],
            [$doFoo2]   // doFoo was only invoked once with TRUE, so checking to see if it was invoked twice with TRUE should return false
        ],
        'return true when given 2 matching lists of invocations when only 1 invocation made instead of false as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--X--
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs));
        $doFoo2 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo1);
        $assertSequenceCheckReturns(true, [[
            $doFoo1,
            $doFoo2
        ]],
        'return false when given a single list of invocations instead of true as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--X--------X--
         * doBar | --------X--------
         * doBaz | -----------X-----
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs));
        $doFoo2 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo1);
        $doBar1 = $createInvocationRecord(createInvocation('doBar', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo2);
        $doBaz1 = $createInvocationRecord(createInvocation('doBaz', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBar1);
        $doFoo3 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBaz1);
        $assertSequenceCheckReturns(true, [
            [
                $doFoo1,
                $doFoo2
            ],
            [
                $doBar1
            ],
            [
                $doBaz1
            ]
        ],
        'return false when given a list of sequential invocations instead of true as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--X--------X--
         * doBar | --------X--------
         * doBaz | -----------X-----
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs));
        $doFoo2 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo1);
        $doBar1 = $createInvocationRecord(createInvocation('doBar', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo2);
        $doBaz1 = $createInvocationRecord(createInvocation('doBaz', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBar1);
        $doFoo3 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBaz1);
        $assertSequenceCheckReturns(true, [
            [
                $doFoo3
            ]
        ],
        'return false when given a list of sequential invocations instead of true as expected');
        // --------------------------------------------------------------------
        /*
         * TIME ---->
         * doFoo | --X--X--------X--
         * doBar | --------X--------
         * doBaz | -----------X-----
         */
        $doFoo1 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs));
        $doFoo2 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo1);
        $doBar1 = $createInvocationRecord(createInvocation('doBar', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doFoo2);
        $doBaz1 = $createInvocationRecord(createInvocation('doBaz', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBar1);
        $doFoo3 = $createInvocationRecord(createInvocation('doFoo', $someInvocationArgs))->withValue('previouslyRecordedInvocationRecord', $doBaz1);
        $assertSequenceCheckReturns(false, [
            [
                $doFoo1,
                $doFoo2
            ],
            [
                $doBaz1
            ],
            [
                $doBar1
            ],
            [
                $doFoo3
            ]
        ],
        'return true when given a list of non-sequential invocations instead of false as expected');
    }

    /**
     * @test
     */
    public function returnsStaticValue_createsCallableThatReturnsAGivenStaticValue()
    {
        $errorMessage = 'the given static value was not returned as expected';

        $this->assertEquals( null, (returnsStaticValue(null))(), $errorMessage );
        // --------------------------------------------------------------------
        $this->assertEquals( false, (returnsStaticValue(false))(), $errorMessage );
        // --------------------------------------------------------------------
        $this->assertEquals( 'foo', (returnsStaticValue('foo'))(), $errorMessage );
        // --------------------------------------------------------------------
        $this->assertEquals( 'foo', (returnsStaticValue('foo'))('bar'), $errorMessage );
        // --------------------------------------------------------------------
        $obj = new \stdClass();
        $this->assertEquals( $obj, (returnsStaticValue($obj))(), $errorMessage );
        // --------------------------------------------------------------------
        $callable = function () {};
        $this->assertEquals( $callable, (returnsStaticValue($callable))(), $errorMessage );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsException_createsCallableThatThrowsAGivenException()
    {
        $e = new InvalidArgumentException();
        (throwsException($e))();
    }
}
