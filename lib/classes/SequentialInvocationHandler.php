<?php

namespace Phaser;

use Equip\Structure\UnorderedList;

/**
 * Invokable class which represents a stubbed method invocation handler which returns values from
 * sequential invocations according to a given sequence of returnables. If more invocations are made
 * than exist given returnables, a static value will be returned for all subsequent invocations.
 *
 * @package Phaser
 */
class SequentialInvocationHandler
{
    private $returnables = [];
    private $onNextInvocationReturnableIndex = 0;
    private $fallbackValueToReturn;

    /**
     * @param UnorderedList  $returnables           the sequence of returnables to use as responses
     * @param mixed          $fallbackValueToReturn the static value to return on all further invocations
     *                                              once all returnables have been used
     */
    public function __construct(UnorderedList $returnables, $fallbackValueToReturn = null)
    {
        $this->returnables = $returnables;
        $this->fallbackValueToReturn = $fallbackValueToReturn;
    }

    /**
     * @param  array $args the arguments given in the invocation of this instance
     * @return mixed the value represented by or returned by the next returnable in the sequence used
     *               to create the instance.  If the returnable sequence has been used up, then the
     *               static fallback value used to create the instance will be returned.
     */
    public function __invoke(...$args)
    {
        $returnableForThisInvocation = isset($this->returnables[$this->onNextInvocationReturnableIndex])
            ? $this->returnables[$this->onNextInvocationReturnableIndex]
            : $this->fallbackValueToReturn;

        ++$this->onNextInvocationReturnableIndex;

        if ($returnableForThisInvocation instanceof WrappedCallable) {
            return $returnableForThisInvocation(...$args);
        } else {
            return $returnableForThisInvocation;
        }
    }
}
