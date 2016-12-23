<?php

namespace Phaser\Fixtures;

use PHPUnit\Framework\TestCase;
use Phaser;

class SpyUsageTest extends TestCase
{
    public function testAnonymousSpy()
    {
        $spy = Phaser\createAnonymousSpy([
            'doFoo' => Phaser\returnsStaticValue('foobar')
        ]);

        $this->assertEquals('foobar', $spy->doFoo(),
            'anonymous spy method call did not return based on the given stub handler');
        $this->assertEquals('foobar', $spy->doFoo($spy, 'some_string'),
            'anonymous spy method call did not return based on the given stub handler');

        $this->assertEquals(null, $spy->doBar(),
            'anonymous spy method call did not return null when no stub handler given');

        $spyReflector = $spy->reflector();
        $this->assertEquals(2, count($spyReflector->doFoo),
            'method calls not registered correctly on anonymous spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo()),
            'method calls not registered correctly on anonymous spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo($spy, 'some_string')),
            'method calls not registered correctly on anonymous spy reflector');
        $this->assertEquals(1, count($spyReflector->doBar),
            'method calls not registered correctly on anonymous spy reflector');
    }

    public function testInterfaceSpy()
    {
        $spy = Phaser\createSpyInstanceOf('\Phaser\Fixtures\FooInterface', [
            'doFoo' => Phaser\returnsStaticValue('foobar')
        ]);

        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooInterface $instance) {};
        $testTypeHintAcceptance($spy);

        $this->assertEquals('foobar', $spy->doFoo(),
            'interface spy method call did not return based on the given stub handler');
        $this->assertEquals('foobar', $spy->doFoo($spy, 'some_string'),
            'interface spy method call did not return based on the given stub handler');

        $this->assertEquals(null, $spy->doBar(),
            'interface spy method call did not return null when no stub handler given');

        $spyReflector = $spy->reflector();
        $this->assertEquals(2, count($spyReflector->doFoo),
            'method calls not registered correctly on interface spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo()),
            'method calls not registered correctly on interface spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo($spy, 'some_string')),
            'method calls not registered correctly on interface spy reflector');
        $this->assertEquals(1, count($spyReflector->doBar),
            'method calls not registered correctly on interface spy reflector');
    }

    public function testAbstractClassSpy()
    {
        $spy = Phaser\createSpyInstanceOf('\Phaser\Fixtures\FooAbstractClass', [
            'doFoo' => Phaser\returnsStaticValue('foobar')
        ]);

        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooAbstractClass $instance) {};
        $testTypeHintAcceptance($spy);
        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooInterface $instance) {};
        $testTypeHintAcceptance($spy);

        $this->assertEquals('foobar', $spy->doFoo(),
            'abstract class spy method call did not return based on the given stub handler');
        $this->assertEquals('foobar', $spy->doFoo($spy, 'some_string'),
            'abstract class spy method call did not return based on the given stub handler');

        $this->assertEquals(null, $spy->doBar(),
            'abstract class spy method call did not return null when no stub handler given');

        $spyReflector = $spy->reflector();
        $this->assertEquals(2, count($spyReflector->doFoo),
            'method calls not registered correctly on abstract class spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo()),
            'method calls not registered correctly on abstract class spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo($spy, 'some_string')),
            'method calls not registered correctly on abstract class spy reflector');
        $this->assertEquals(1, count($spyReflector->doBar),
            'method calls not registered correctly on abstract class spy reflector');
    }

    public function testConcreteClassSpy()
    {
        $spy = Phaser\createSpyInstanceOf('\Phaser\Fixtures\FooClass', [
            'doFoo' => Phaser\returnsStaticValue('foobar')
        ]);

        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooAbstractClass $instance) {};
        $testTypeHintAcceptance($spy);
        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooInterface $instance) {};
        $testTypeHintAcceptance($spy);
        $testTypeHintAcceptance = function (\Phaser\Fixtures\FooClass $instance) {};
        $testTypeHintAcceptance($spy);

        $this->assertEquals('foobar', $spy->doFoo(),
            'concrete class spy method call did not return based on the given stub handler');
        $this->assertEquals('foobar', $spy->doFoo($spy, 'some_string'),
            'concrete class spy method call did not return based on the given stub handler');

        $this->assertEquals(null, $spy->doBar(),
            'concrete class spy method call did not return null when no stub handler given');

        $spyReflector = $spy->reflector();
        $this->assertEquals(2, count($spyReflector->doFoo),
            'method calls not registered correctly on concrete class spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo()),
            'method calls not registered correctly on concrete class spy reflector');
        $this->assertEquals(1, count($spyReflector->doFoo($spy, 'some_string')),
            'method calls not registered correctly on concrete class spy reflector');
        $this->assertEquals(1, count($spyReflector->doBar),
            'method calls not registered correctly on concrete class spy reflector');
    }
}
