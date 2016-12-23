<?php

namespace Phaser;

use PHPUnit\Framework\TestCase;

class SpyClassGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function createsASpyClassThatExtendsAConcreteClass()
    {
        $concreteClassName = '\Phaser\Fixtures\FooClass';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($concreteClassName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertTrue(is_subclass_of($spyClassName, $concreteClassName), "spy class loaded, but doesn't extending concrete class");
    }

    /**
     * @test
     */
    public function createsASpyClassThatExtendsAnAbstractClass()
    {
        $abstractClassName = '\Phaser\Fixtures\FooAbstractClass';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($abstractClassName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertTrue(is_subclass_of($spyClassName, $abstractClassName), "spy class loaded, but doesn't extending abstract class");
    }

    /**
     * @test
     */
    public function createsASpyClassThatImplementsAnInterface()
    {
        $interfaceName = '\Phaser\Fixtures\FooInterface';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($interfaceName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertContains('Phaser\Fixtures\FooInterface', class_implements($spyClassName), "spy class loaded, but doesn't implement the expected interface");
    }
}
