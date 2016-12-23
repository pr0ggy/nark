# Phaser
Phaser is a lightweight spying/stubbing library for PHP >= 5.6 designed for use with PHPUnit or any other testing framework.  Its aim is to do one thing succinctly: provide a way of creating test double objects which can provide preprogrammed behavior and then report on how they were utilized.  Phaser strives for simplicity and practicality in the average use case rather than providing a massive interface and feature set for covering every possible scenario.

## Installation
    composer require --dev pr0ggy/phaser

## Usage
### Creating Anonymous Spies
An anonymous spy suits the bill for cases where you aren't worried about creating a test double of a particular type and just need an object that will respond to a method call in an expected way:
```php
// anonymous spy where all method invocations will return null
$spy = Phaser\createAnonymousSpy();

// or an anonymous spy with stubbed methods
$spy = Phaser\createAnonymousSpy([
    'doFoo' => Phaser\returnsStaticValue('bar'),
    'doBaz' => Phaser\returnsInSequence('fizz', 'fazz'),
    'doBiz' => Phaser\throwsException(new InvalidArgumentException()),
    'doBuzz' => function ($arg1, $arg2) { return ($arg1 + $arg2); }
]);
```

To stub methods on the created spy, simply pass a map of the format:  

    [method name] => [invocation handler callable]

The 3 Phaser convenience methods used to create handlers in the example above are explained in the [API documentation](#phaser-api) below. Invocation handlers can also be given as raw functions which will accept all invocation arguments, as in the 'doBuzz' handler above.

### Creating Typed Spies
The method of creating a test double to use in place of a given class or interface is very similar:
```php
// create spy of type \Foo\Bar where all method invocations will return null
$spy = Phaser\createSpyInstanceOf('\Foo\Bar');

// or a spy of type \Foo\Bar with stubbed methods
$spy = Phaser\createSpyInstanceOf('\Foo\Bar', [
    'doFoo' => Phaser\returnsStaticValue('bar'),
    'doBaz' => Phaser\returnsInSequence('fizz', 'fazz'),
    'doBiz' => Phaser\throwsException(new InvalidArgumentException()),
    'doBuzz' => function ($arg1, $arg2) { return ($arg1 + $arg2); }
]);
```

### Querying Spies
Phaser provides an extremely straightforward way of querying a test double about how it was utilized.  There are 2 queries available for a Phaser spy instance:
```php
$spy = Phaser\createAnonymousSpy();

doSomethingWith($spy);


$spyInvocations = $spy->reflector();
// 1. How many times was a given spy method invoked...
// ...with no arguments?
$this->assertTrue(count($spyInvocations->doFoo()) > 0);
// ...with a particular set of arguments?
$this->assertTrue(count($spyInvocations->doFoo('foo', false)) > 0);
// ...with particular categories of arguments using Hamcrest matchers?
$this->assertTrue(count($spyInvocations->doFoo(startsWith('foo'), anything())) > 0);
// ...or how many times a method was invoked with any arguments or no arguments
$this->assertTrue(count($spyInvocations->doFoo) > 0);

// 2. Did a given set of invocations occur in chronological order?
$this->assertTrue(Phaser\occurredChronologically([
    $spyInvocations->doFoo('foo'),
    $spyInvocations->doFoo('bar'),
    $spyInvocations->doFoo('baz')
]));
```

## Phaser API

The following functions are available in the `Phaser\` namespace and used to create spy instances:

### `createAnonymousSpy(array $methodNameToResponderMap = [])`

Creates an anonymous spy instance with optional method stubs. All unstubbed method invocations will return null.

---

### `createSpyInstanceOf($type, array $methodNameToResponderMap = [])`

Creates a spy instance of the given `$type` (class or interface) with optional method stubs. All unstubbed method invoctions will return null.

---

### `occurredChronologically(...$sequenceOfMatchedInvocationRecordLists)`

Accepts any given number of invocation sets queried from a given spy reflector (see [Spy Reflector API](#spyreflector-api) below) and returns true if one invocation from each of the given sets can be pulled into a new set such that this new set contains invocations that occurred in chronological order.

---

### `returnsStaticValue($staticValueToReturn)`

Returns a method stub invocation handler that always returns the given static value, as it is given to this function.

---

### `returnsInSequence(...$sequenceOfValuesToReturn)`

Returns a method stub invocation handler which will return the given sequence of values in sequential invocations.  The first argument corresponds to the desired return value for the first invocation, the second argument for the second invocation, etc.  Once the sequence has been used up, `null` will be returned for all subsequent calls.  Note that functions given as arguments to this function will be returned as raw functions during method invocations (they will not be invoked to generate the return value)...see `Phaser\valueReturnedBy` below to wrap functions which should be invoked rather than returned as raw values.

---

### `valueReturnedBy($callable)`

Used when passing functions to `Phaser\returnsInSequence` function to denote functions that should not be returned as raw values, but rather invoked to _calculate_ the return value of the invocation.

---

### `throwsException(Exception $exception)`

Returns a method stub invocation handler that throws the given exception when invoked.

---

## Spy API

In addition to any methods defined by virtue of implementing an interface or extending a class to spy on, a spy instance has the following methods:

### `$spy->reflector()`

Returns a spy object's `SpyReflector` instance, which can be used to query for invocations made on the spy (see the [Reflector API](#spyreflector-api) below).

---

## SpyReflector API
A `SpyReflector` instance doesn't have any concrete methods of its own.  Instead, it uses PHP magic methods to allow the user to invoke methods on the reflector as if they were being invoked on the spy itself. Doing so returns a set of recorded invocations made on the spy instance which match the call made to the reflector instance.  See [the code examples above](#querying-spies) for a concrete example.

---

## Testing

    ./vendor/bin/phpunit test/

## License
GNU Public V3
