<?php

namespace Nark;

/**
 * Represents a spy object created by the library.  For anonymous spy objects, this class is directly
 * instantiated.  This is because anonymous spies are not expected to pass type hinting for a given
 * class or interface.  For spies that have to masquerade as instances of a specific type, we'll have
 * to dynamically muck with this definition a bit, which is why this class exists in its own file.
 *
 * @package Nark
 */
final class SpyBase
{
    private $reflector;
    private $methodNameToStubMap = [];
    private $fallbackInvocationResponse;

    /**
     * @param SpyReflector  $reflector                  the reflector object used for quering the spy
     *                                                  regarding method calls it has received
     * @param array         $methodNameToStubMap        map of method names to response handling functions
     * @param callable      $fallbackInvocationResponse the response handler to fall back to for any call
     *                                                  to a method which does not exist in $methodNameToStubMap
     */
    public function __construct(
        SpyReflector $reflector,
        array $methodNameToStubMap,
        callable $fallbackInvocationResponse
    ) {
        $this->reflector = $reflector;
        $this->methodNameToStubMap = $methodNameToStubMap;
        $this->fallbackInvocationResponse = $fallbackInvocationResponse;
    }

    /**
     * default method call handler which will record the call to the spy's reflector instance and return
     * a response according to the registered handler for the invoked method (or the fallback handler)
     *
     * @param  string $methodName the method name being invoked
     * @param  array  $args       the arguments of the invocation
     * @return mixed the return value defined by the registered handler for the invoked method (or the
     *               fallback handler)
     */
    public function __call($methodName, $args)
    {
        $invocationHandler = isset($this->methodNameToStubMap[$methodName])
            ? $this->methodNameToStubMap[$methodName]
            : $this->fallbackInvocationResponse;

        $this->reflector = $this->reflector->withAddedInvocationRecord(
            $methodName,
            createInvocationRecord(
                createInvocation($methodName, $args),
                microtime(true)
            )
        );

        return $invocationHandler(...$args);
    }

    /**
     * @return SpyReflector the reflector instance of the spy instance (no need for clonding as
     *                      reflector instances are immutable)
     */
    public function reflector()
    {
        return $this->reflector;
    }

    // END SPY BASE CODE
}
