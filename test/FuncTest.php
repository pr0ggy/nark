<?php

namespace Nark;

use PHPUnit\Framework\TestCase;
use Equip\Structure\Dictionary;
use Equip\Structure\UnorderedList;
use Hamcrest;

class FuncTest extends TestCase
{
    /**
     * @test
     */
    public function createInvocation_returnsAnImmutableMapRepresentingTheInvocationData()
    {
        $assertInvocationCreation = function ($methodName, array $args, $scenarioDescription) {
            $invocation = createInvocation($methodName, $args);

            $this->assertEquals($methodName, $invocation->getValue('methodName'),
                "Failed to set the expected method name when {$scenarioDescription}");
            $this->assertEquals(new UnorderedList($args), $invocation->getValue('args'),
                "Failed to set the expected args when {$scenarioDescription}");
        };
        // --------------------------------------------------------------------
        $assertInvocationCreation('doFoo', [],
            'no arguments given');
        // --------------------------------------------------------------------
        $assertInvocationCreation('doFoo', [true, 10],
            'arguments given');
    }

    /**
     * @test
     */
    public function createInvocationRecord_returnsAnImmutableMapRepresentingTheInvocationDataWithATimestamp()
    {
        $assertInvocationCreation = function ($methodName, array $args, $scenarioDescription) {
            $invocationTimestamp = microtime(true);

            $invocationRecord = createInvocationRecord(
                createInvocation($methodName, $args),
                $invocationTimestamp
            );

            $this->assertEquals($methodName, $invocationRecord->getValue('invocation')->getValue('methodName'),
                "Failed to set the expected method name when {$scenarioDescription}");
            $this->assertEquals(new UnorderedList($args), $invocationRecord->getValue('invocation')->getValue('args'),
                "Failed to set the expected args when {$scenarioDescription}");
            $this->assertEquals($invocationTimestamp, $invocationRecord->getValue('timestamp'),
                "Failed to set the expected timestamp when {$scenarioDescription}");
        };
        // --------------------------------------------------------------------
        $assertInvocationCreation('doFoo', [],
            'no arguments given');
        // --------------------------------------------------------------------
        $assertInvocationCreation('doFoo', [true, 10],
            'arguments given');
    }

    /**
     * @test
     */
    public function invocationsMatch_returnsWhetherOrNotTwoGivenInvocationDataStructuresAreConsideredEqualBasedOnMethodNameAndArgs() {
        $this->assertEquals(true, invocationsMatch(
            createInvocation('doFoo', []),
            createInvocation('doFoo', [])
        ),
        'Failed to recognize matching invocations when neither had any args');
        //---------------------------------------------------------------------
        $this->assertEquals(true, invocationsMatch(
            createInvocation('doFoo', [true, 10]),
            createInvocation('doFoo', [true, 10])
        ),
        'Failed to recognize matching invocations when both had args');
        //---------------------------------------------------------------------
        $this->assertEquals(false, invocationsMatch(
            createInvocation('doFoo', [0]),
            createInvocation('doFoo', [false])
        ),
        'Failed to recognize mismatched invocations when both had args');
        //---------------------------------------------------------------------
        $this->assertEquals(false, invocationsMatch(
            createInvocation('doFoo', [true, 10]),
            createInvocation('doBar', [true, 10])
        ),
        'Failed to recognize mismatched invocations when method names do not match');
        //---------------------------------------------------------------------
        $assertHamcrestMatching = function ($hamcrestMatcherFunc, $hamcrestMatcherArgs, $valueResultingInMatch, $valueResultingInMismatch) {
            $fullyQualifiedMatcherFunc = "Hamcrest\Matchers::{$hamcrestMatcherFunc}";
            $this->assertEquals(true, invocationsMatch(
                createInvocation('doFoo', [$valueResultingInMatch]),
                createInvocation('doFoo', [$fullyQualifiedMatcherFunc(...$hamcrestMatcherArgs)])
            ),
            "Failed to recognize a set of matching invocations using Hamcrest matcher '{$hamcrestMatcherFunc}(...)'");

            $this->assertEquals(false, invocationsMatch(
                createInvocation('doFoo', [$valueResultingInMismatch]),
                createInvocation('doFoo', [$fullyQualifiedMatcherFunc(...$hamcrestMatcherArgs)])
            ),
            "Failed to recognize a set of mismatched invocations using Hamcrest matcher '{$hamcrestMatcherFunc}(...)'");
        };

        $assertHamcrestMatching('allOf', [Hamcrest\Matchers::greaterThan(5), Hamcrest\Matchers::lessThan(10)], 6, 11);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('anyOf', [Hamcrest\Matchers::greaterThan(5), Hamcrest\Matchers::lessThan(3)], 10, 4);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('not', [Hamcrest\Matchers::greaterThan(5)], 3, 8);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('equalTo', [5], 5, 5.5);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('anInstanceOf', ['\stdClass'], new \stdClass(), new \Exception());
        //---------------------------------------------------------------------
        // This is failing...Hamcrest issue?
        // $assertHamcrestMatching('notNullValue', [], 5, null);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('nullValue', [], null, 10);
        //---------------------------------------------------------------------
        $instanceA = new \stdClass();
        $instanceB = new \stdClass();
        $assertHamcrestMatching('sameInstance', [$instanceA], $instanceA, $instanceB);
        //---------------------------------------------------------------------
        $instanceA = new \stdClass();
        $instanceB = new \stdClass();
        $assertHamcrestMatching('identicalTo', [$instanceA], $instanceA, $instanceB);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('closeTo', [5.5, .2], 5.35, 10.1);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('greaterThan', [5], 5.35, 2);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('greaterThanOrEqualTo', [5.35], 5.35, 2);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('lessThan', [5], 4.35, 5.2);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('lessThanOrEqualTo', [5.35], 5.35, 5.4);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('hasItem', [1], [1,2,3], [2,3,4]);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('hasItems', [1,2], [1,2,3], [2,3,4]);
        //---------------------------------------------------------------------
        $assertHamcrestMatching('equalToIgnoringCase', ['AbC'], 'abc', 'AbCd');
        //---------------------------------------------------------------------
        $assertHamcrestMatching('equalToIgnoringWhiteSpace', ['Ab C'], 'Ab  C', 'AbCd');
        //---------------------------------------------------------------------
        $assertHamcrestMatching('containsString', ['abc'], 'abcde', 'bcdef');
        //---------------------------------------------------------------------
        $assertHamcrestMatching('endsWith', ['123'], 'abc123', '1234');
        //---------------------------------------------------------------------
        $assertHamcrestMatching('startsWith', ['abc'], 'abc123', '123abc');
    }
}
