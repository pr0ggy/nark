<?php

namespace Phaser;

use Equip\Structure\Dictionary;
use Equip\Structure\UnorderedList;

/**
 * @param  string $methodName the method name invoked
 * @param  array  $args       array of all arguments passed in the invocation
 * @return Equip\Structure\Dictionary immutable map containing the invocation data
 */
function createInvocation($methodName, array $args) {
    return new Dictionary([
        'methodName' => $methodName,
        'args' => new UnorderedList($args)
    ]);
}

/**
 * @param  Dictionary $invocation the invocation data to be recorded
 * @return Equip\Structure\Dictionary immutable map containing the invocation record data
 */
function createInvocationRecord(Dictionary $invocation, $timestamp) {
    return new Dictionary([
        'invocation' => $invocation,
        'timestamp' => $timestamp
    ]);
}

/**
 * @param  Dictionary $invocationA
 * @param  Dictionary $invocationB
 * @return bool true if the given invocations are considered 'equal', false if otherwise
 */
function invocationsMatch(Dictionary $invocationA, Dictionary $invocationB) {
    if ($invocationA['methodName'] !== $invocationB['methodName']) {
        return false;
    }

    if (count($invocationA['args']) !== count($invocationB['args'])) {
        return false;
    }

    /*
     * Phaser supports Hamcrest matchers, so we have to compare each individual argument to see if
     * a matcher instance was given.  if so, we need to check for argument match using the matcher
     * interface.  otherwise, we can just check raw equality.
     */
    $argMismatch = function ($argA, $argB) {
        if ($argA instanceof \Hamcrest\Matcher) {
            $matcherInstance = $argA;
            $argToMatch = $argB;
        } elseif ($argB instanceof \Hamcrest\Matcher) {
            $matcherInstance = $argB;
            $argToMatch = $argA;
        }

        $matcherTestFailure = ( isset($matcherInstance) && $matcherInstance->matches($argToMatch) === false );
        $argValueInequality = ( isset($matcherInstance) === false && $argA !== $argB );
        return ($matcherTestFailure || $argValueInequality);
    };

    foreach ($invocationA['args'] as $index => $arg) {
        $matchingInvocationBArg = $invocationB['args'][$index];
        if ($argMismatch($arg, $matchingInvocationBArg)) {
            return false;
        }
    }

    return true;
}
