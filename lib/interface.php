<?php

namespace Nark;

use Equip\Structure\UnorderedList;
use Equip\Structure\Dictionary;
use Exception;
use InvalidArgumentException;

/**
 * Usage:
 *     // anonymous spy where all method invocations will return null
 *     $spy = Nark\createAnonymousSpy();
 *
 *     // or an anonymous spy with stubbed methods
 *     $spy = Nark\createAnonymousSpy([
 *         'doFoo' => Nark\returnsStaticValue('bar'),
 *         'doBaz' => Nark\throwsException(new InvalidArgumentException())
 *     ]);
 *
 * @param  array  $methodNameToResponderMap a map of method names to callbacks which will handle
 *                                          any invocation made to the associated method name
 * @return SpyBase an instance of the base Nark spy class which will behave according to the
 *                 given method name to responder map
 */
function createAnonymousSpy(array $methodNameToResponderMap = []) {
    return new SpyBase(
        new SpyReflector(),
        $methodNameToResponderMap,
        returnsStaticValue(null)
    );
}

/**
 * used to create a spy double for an existing class or interface
 *
 * Usage:
 *     // create spy where all method invocations will return null
 *     $spy = Nark\createSpyInstanceOf('\Foo\Bar');
 *
 *     // or a spy with stubbed methods
 *     $spy = Nark\createSpyInstanceOf('\Foo\Bar', [
 *         'doFoo' => Nark\returnsStaticValue('bar'),
 *         'doBaz' => Nark\throwsException(new InvalidArgumentException())
 *     ]);
 *
 * @param  string  $type                     the name of the existing class or interface to spy on
 * @param  array   $methodNameToResponderMap a map of method names to callbacks which will handle
 *                                           any invocation made to the associated method name
 * @return mixed an instance of the base Nark spy class which will extend the given class or
 *               implement the given behave according to the given method name to responder map
 */
function createSpyInstanceOf($type, array $methodNameToResponderMap = []) {
    $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($type);

    return new $spyClassName(
        new SpyReflector(),
        $methodNameToResponderMap,
        returnsStaticValue(null)
    );
}

/**
 * We have a similar idea here as the callCount() method, only this method accepts any number of
 * matcher expressions and returns whether or not a path can be traced vertically through the list
 * of invocation lists which connects a series of invocations which occurred in chronological order.
 *
 * Usage:
 *     // assert an invocation of 'anotherMethodName' was made with args ($arg1, $arg2) at some point
 *     // after an invocation with any args was made to 'methodName'
 *     assert( Nark\occurredChronologically(
 *         $spyInstance->reflector()->methodName,
 *         $spyInstance->reflector()->anotherMethodName($arg1, $arg2)
 *     ) );
 *
 * Note that using this method, there may be invocations of other methods not in the given invocation
 * record lists that occurred between the invocations contained in those lists.  All this function will
 * check is that at least one invocation in a given list occurred at a later point in time than at
 * least one invocation in the previous list.
 *
 * @param  array $sequenceOfMatchedInvocationRecordLists the list of invocation lists in which to check for
 *                                             a sequential invocation path
 * @return boolean true if a path as defined above can be traced through the list of invocation
 *                 lists, false if otherwise
 *
 * @throws \InvalidArgumentException if no invocation lists were given
 */
function occurredChronologically(...$sequenceOfMatchedInvocationRecordLists) {
    // expecting at least 1 matched invocation list
    $sequenceLength = count($sequenceOfMatchedInvocationRecordLists);
    if ($sequenceLength === 0) {
        throw new InvalidArgumentException(
            'Nark\occurredChronologically(...$sequenceOfMatchedInvocationRecordLists) expects at least one spy invocation matcher result'
        );
    }

    $findFirstInvocationOcurringAfter = function ($timestamp, $invocationRecordsToSearch) {
        foreach ($invocationRecordsToSearch as $potentialInvocation) {
            if ((int) $potentialInvocation->getValue('timestamp') > (int) $timestamp) {
                return $potentialInvocation;
            }
        }

        return null;
    };

    // check the actual sequence of specific invocations, not just that the given invocations occurred...
    // the first invocation in the first list of matched invocations is guaranteed to be 'in sequence'...
    $invocationRecordFromCurrentlyInspectedInvocationsThatOccurredInSequence =
        isset($sequenceOfMatchedInvocationRecordLists[0][0])
            ? $sequenceOfMatchedInvocationRecordLists[0][0]
            : null;
    // ...but only if it exists...an empty invocation list for a given matcher means we know no such sequence
    // path can be traced across the lists...
    if ($invocationRecordFromCurrentlyInspectedInvocationsThatOccurredInSequence === null) {
        return false;
    }

    // jump to the next list of matched invocations and verify that at least 1 invocation exists in that list that
    // occurred after the previously known invocation that occurred in sequence. repeat this until we're out of
    // invocation lists to check
    for ($i = 1; $i < $sequenceLength; ++$i) {
        $currentsequenceOfMatchedInvocationRecordListsBeingInspected = $sequenceOfMatchedInvocationRecordLists[$i];
        $invocationRecordFromCurrentlyInspectedInvocationsThatOccurredInSequence = $findFirstInvocationOcurringAfter(
            $invocationRecordFromCurrentlyInspectedInvocationsThatOccurredInSequence['timestamp'],
            $currentsequenceOfMatchedInvocationRecordListsBeingInspected
        );

        if ($invocationRecordFromCurrentlyInspectedInvocationsThatOccurredInSequence === null) {
            return false;
        }
    }

    return true;
}

/**
 * Similar to occurredChronologically, returns whether or not a path can be traced vertically
 * through the list of invocation lists which connects a series of invocations which occurred in
 * sequence (one immediately after the other with no other method calls in between).
 *
 * Usage:
 *     // assert an invocation of 'anotherMethodName' was made with args ($arg1, $arg2) immediately
 *     // after an invocation with any args was made to 'methodName'
 *     assert( Nark\occurredSequentially(
 *         $spyInstance->reflector()->methodName,
 *         $spyInstance->reflector()->anotherMethodName($arg1, $arg2)
 *     ) );
 *
 * @param  array $sequenceOfMatchedInvocationRecordLists the list of invocation lists in which to check for
 *                                             a sequential invocation path
 * @return boolean true if the given set of method calls occurred sequentially, false if otherwise
 *
 * @throws \InvalidArgumentException if no invocation lists were given
 */
function occurredSequentially(...$sequenceOfMatchedInvocationRecordLists) {
    // expecting at least 1 matched invocation list
    $sequenceLength = count($sequenceOfMatchedInvocationRecordLists);
    if ($sequenceLength === 0) {
        throw new InvalidArgumentException(
            'Nark\occurredInSequence(...$sequenceOfMatchedInvocationRecordLists) expects at least one spy invocation matcher result'
        );
    }

    /*
     * Because each invocation record stores the previously-recorded record, we need to loop across
     * the invocation record lists in reverse.  The idea is that we need to trace an _unbroken_ path
     * from the final invocation record in the final list up to the first invocation in the first
     * list by traversing through the records, following the previously-recorded invocation record
     * of the current invocation record.
     */
    $recordIsPartOfUnbrokenChainUpInvocationLists = function (Dictionary $record, UnorderedList $invocationLists) use ( &$recordIsPartOfUnbrokenChainUpInvocationLists ) {
        // if no other invocation lists to check, then the single record does constitute a chain
        if (count($invocationLists) === 0) {
            return true;
        }

        // if there are other invocation lists to check, but the record has no previous invocation,
        // we know the chain breaks
        if (isset($record['previouslyRecordedInvocationRecord']) === false) {
            return false;
        }

        $thePreviousInvocationRecordList = $invocationLists[count($invocationLists) - 1];
        if ($thePreviousInvocationRecordList->hasValue($record['previouslyRecordedInvocationRecord'])) {
            $allPrecedingRecordListsExceptLast = [];
            for ($i = 0; $i < (count($invocationLists) - 1); ++$i) {
                $allPrecedingRecordListsExceptLast[] = $invocationLists[$i];
            }
            $allPrecedingRecordListsExceptLast = new UnorderedList($allPrecedingRecordListsExceptLast);

            return $recordIsPartOfUnbrokenChainUpInvocationLists(
                $record['previouslyRecordedInvocationRecord'],
                $allPrecedingRecordListsExceptLast
            );
        }

        return false;
    };

    $finalInvocationRecordList = $sequenceOfMatchedInvocationRecordLists[$sequenceLength - 1];
    $finalInvocationListRecordCount = count($finalInvocationRecordList);
    $allOtherInvocationLists = new UnorderedList(array_slice($sequenceOfMatchedInvocationRecordLists, 0, -1));
    for ($i = ($finalInvocationListRecordCount - 1); $i >= 0; --$i) {
        $currentRecord = $finalInvocationRecordList[$i];
        if ($recordIsPartOfUnbrokenChainUpInvocationLists($currentRecord, $allOtherInvocationLists)) {
            return true;
        }
    }

    return false;
}

/**
 * Used in creating a spy instance with stubbed methods to indicate a method stub should always return
 * a given static value
 *
 * Usage:
 *     // create anonymous spy with a 'doFoo' method stub which always returns 'bar'
 *     $spy = Nark\createSpy([
 *         'doFoo' => Nark\returnsStaticValue('bar')
 *     ]);
 *
 * @param  mixed $staticValueToReturn the value to return each time the returned callback is invoked
 * @return callable a callback which will always return the given static value when invoked
 */
function returnsStaticValue($staticValueToReturn) {
    return function (...$args) use ($staticValueToReturn) {
        return $staticValueToReturn;
    };
}

/**
 * Used in creating a spy instance with stubbed methods to indicate a method stub should return the
 * given returnables in sequence with each successive invocation. Note that once the given returnable
 * sequence has been extinguished, the stub will return null for any further invocations made.
 *
 * Usage:
 *     // create anonymous spy with a 'doFoo' method stub which returns 'bar' on first invocation, then
 *     // 'baz' on the second invocation, then null on any further invocations
 *     $spy = Nark\createSpy([
 *         'doFoo' => Nark\returnsInSequence('bar', 'baz')
 *     ]);
 *
 * @param  array $sequenceOfValuesToReturn the sequence of returnables to return from sequential
 *                                         invocations of a method
 * @return callable a callable object which will ensure the correct returnable is used in each
 *                  sequential invocation
 */
function returnsInSequence(...$sequenceOfValuesToReturn) {
    return new SequentialInvocationHandler(new UnorderedList($sequenceOfValuesToReturn));
}

/**
 * Usage:
 *     // create anonymous spy with a 'doFoo' method stub which throws an InvalidArgumentException
 *     $spy = Nark\createSpy([
 *         'doFoo' => Nark\throwsException(new InvalidArgumentException())
 *     ]);
 *
 * @param  \Exception $exception the exception to throw each time the returned callback is invoked
 * @return callable a callback which will always throw the given exception when invoked
 */
function throwsException(Exception $exception) {
    return function (...$args) use ($exception) {
        throw $exception;
    };
}

/**
 * Function used to wrap functions given using the Nark\returnsInSequence(...) stub setup method.
 * This is needed because we have to distinguish callables/functions which we want returned raw with
 * those we want to actually be executed to determine the return value.
 *
 * Usage:
 *     // create anonymous spy with a 'doFoo' method stub which returns 10 on the first invocation,
 *     // then uses a given callable to compute the return value for the second invocation, then
 *     // returns null on any further invocations
 *     $spy = Nark\createSpy([
 *         'doFoo' => Nark\returnsInSequence(
 *             10,
 *             Nark\valueReturnedBy(function(...$args) { return count($args); })
*          )
 *     ]);
 *
 * @param  mixed $callable the callable to wrap
 * @return WrappedCallable the given callable wrapped in a callable class for distinguishing
 *                              it from standard functions, etc.
 *
 * @throws \InvalidArgumentException if the given argument is not callable
 */
function valueReturnedBy($callable) {
    return new WrappedCallable($callable);
}
