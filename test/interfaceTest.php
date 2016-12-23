<?php

namespace Phaser;

use PHPUnit\Framework\TestCase;
use Equip\Structure\UnorderedList;
use InvalidArgumentException;

class PhaserInterfaceTest extends TestCase
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
    public function occurredChronologically_returnsWhetherOrNotAGivenSequenceOfInvocationsOccurred()
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
            createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+100))
        ]],
        'return false when given a single list of invocations instead of true as expected');
        // --------------------------------------------------------------------
        $assertSequenceCheckReturns(true, [
            [
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), $timestamp),         // A, (T)
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+100))
            ],
            [
                createInvocationRecord(createInvocation('doBar', $someInvocationArgs), ($timestamp+300))    // B, (T+300)
            ],
            [
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), $timestamp-500),
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), ($timestamp+350))    // C, (T+350)
            ]
        ],
        'return false when a sequence does exist across the given invocation lists instead of true as expected');
        // --------------------------------------------------------------------
        $assertSequenceCheckReturns(false, [
            [
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), $timestamp),         // A, (T)
                createInvocationRecord(createInvocation('doFoo', $someInvocationArgs), ($timestamp+100))
            ],
            [
                createInvocationRecord(createInvocation('doBar', $someInvocationArgs), ($timestamp-10))    // NOT IN SEQUENCE (T-10)
            ],
            [
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), $timestamp-500),
                createInvocationRecord(createInvocation('doBaz', $someInvocationArgs), ($timestamp+350))    // C, (T+350)
            ]
        ],
        'return true when a sequence does NOT exist across the given invocation lists instead of false as expected');
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
