<?php

namespace Nark;

use PHPUnit\Framework\TestCase;

class SpyClassGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function createsASpyClassThatExtendsAConcreteClass()
    {
        $concreteClassName = '\Nark\Fixtures\FooClass';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($concreteClassName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertTrue(is_subclass_of($spyClassName, $concreteClassName), "spy class loaded, but doesn't extending concrete class");
    }

    /**
     * @test
     */
    public function createsASpyClassThatExtendsAnAbstractClass()
    {
        $abstractClassName = '\Nark\Fixtures\FooAbstractClass';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($abstractClassName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertTrue(is_subclass_of($spyClassName, $abstractClassName), "spy class loaded, but doesn't extending abstract class");
    }

    /**
     * @test
     */
    public function createsASpyClassThatImplementsAnInterface()
    {
        $interfaceName = '\Nark\Fixtures\FooInterface';
        $spyClassName = SpyClassGenerator::generateSpyClassRepresenting($interfaceName);
        $this->assertTrue(class_exists($spyClassName), 'failed to create and load a spy class');
        $this->assertContains('Nark\Fixtures\FooInterface', class_implements($spyClassName), "spy class loaded, but doesn't implement the expected interface");
    }
}
