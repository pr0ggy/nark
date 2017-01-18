<?php

namespace Nark;

use Equip\Structure\Dictionary;
use Equip\Structure\UnorderedList;

/**
 * A SpyReflector is designed to be held within a spy instance and used to register method invocations.
 * Registering on the reflector instance instead of directly on the spy instance itself allows for Nark's
 * unique way of querying spy instances for invocations made against them.
 */
class SpyReflector
{
    private $methodNameToInvocationRecordsMap;
    private $lastRecordedInvocationRecord;

    /**
     * @param Dictionary|null $methodNameToInvocationRecordsMap initial map of method names to
     *                                                          recorded invocations
     * @param Dictionary|null $lastRecordedInvocationRecord     the invocation record representing the
     *                                                          most recent invocation made on the spy
     *                                                          which owns this reflector instance
     */
    public function __construct(
        Dictionary $methodNameToInvocationRecordsMap = null,
        Dictionary $lastRecordedInvocationRecord = null
    ) {
        $this->methodNameToInvocationRecordsMap = ($methodNameToInvocationRecordsMap ?: new Dictionary());
        $this->lastRecordedInvocationRecord = $lastRecordedInvocationRecord;
    }

    /**
     * The __get() magic method is used to allow querying of all invocations of a given method name
     * by requesting it as a member from the reflector, ie.
     *     $doBazMethodInvocationList = $spyInstance->reflector()->doBaz;
     *
     * @param  string $methodName method name for which to return a list of all recorded invocations
     * @return UnorderedList list of all invocations made to the given method name
     */
    public function __get($methodName)
    {
        $allInvocationRecordsMatchingGivenMethodName = $this->methodNameToInvocationRecordsMap->getValue($methodName);
        return (isset($allInvocationRecordsMatchingGivenMethodName) ? $allInvocationRecordsMatchingGivenMethodName : new UnorderedList());
    }

    /**
     * The __call() magic method is used to allow querying of invocations of a given method name
     * made with specific arguments by performing the invocation on the reflector itself, ie.
     *     $doBazMethodInvocationMatchingArgList = $spyInstance->reflector()->doBaz(true, 10);
     *
     * @param  string $methodName     method name for which to search for matching invocations
     * @param  array  $invocationArgs the arguments to match against to filter invocations matching
     *                                the given method name
     * @return UnorderedList list of all invocations made to the given method name which match the
     *                       arguments used in the invocation to the reflector
     */
    public function __call($methodName, $invocationArgs = [])
    {
        $allInvocationRecordsMatchingGivenMethodName = $this->$methodName;
        if (count($allInvocationRecordsMatchingGivenMethodName) === 0) {
            return $allInvocationRecordsMatchingGivenMethodName;
        }

        $invocationToMatch = createInvocation($methodName, $invocationArgs);
        $matchingInvocationRecords = [];
        foreach ($allInvocationRecordsMatchingGivenMethodName as $invocationRecord) {
            if (invocationsMatch($invocationRecord['invocation'], $invocationToMatch)) {
                $matchingInvocationRecords[] = $invocationRecord;
            }
        }

        return new UnorderedList($matchingInvocationRecords);
    }

    /**
     * Mutative update method which returns a new _SpyReflector instance containing this instance's
     * invocation map, plus the invocation data given
     *
     * @param  string $methodName              the method name of the invocation to add to create
     *                                         the new _SpyReflector instance
     * @param  Dictionary $newInvocationRecord the new invocation record to add
     * @return _SpyReflector a new _SpyReflector instance loaded with all existing data,
     *                                  plus the new data given
     */
    public function withAddedInvocationRecord($methodName, Dictionary $newInvocationRecord) {
        $recordedInvocationsOfGivenMethod =
            isset($this->methodNameToInvocationRecordsMap[$methodName])
                ? $this->methodNameToInvocationRecordsMap[$methodName]
                : new UnorderedList();

        $invocationRecordToAdd = $newInvocationRecord->withValue(
            'previouslyRecordedInvocationRecord',
            $this->lastRecordedInvocationRecord
        );

        return new static(
            $this->methodNameToInvocationRecordsMap->withValue(
                $methodName,
                $recordedInvocationsOfGivenMethod->withValue(
                    $invocationRecordToAdd
                )
            ),
            $invocationRecordToAdd
        );
    }
}
