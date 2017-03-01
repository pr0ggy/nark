<?php

namespace Nark;

use Equip\Structure\UnorderedList;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Generates code for a spy double extending a given class or implementing a given interface
 *
 * @package Nark
 * @see \Nark\SpyBase
 */
class SpyClassGenerator
{
    const CLASS_NAME_LINE_INDEX = 12;   // 0-indexed line number of the line containing the class name in SpyBase.php

    private static $baseClassCode = [];     // SpyBase.php contents as a collection of lines
    private static $loadedSpyClasses = [];  // we'll only can/need to create and load a spy class once, keep track of spy
                                            // classes that have already been created so we avoid unnecessary duplication

    /**
     * Generates and loads code for a spy double extending a given class or implementing a given
     * interface and returns the name of the generated class
     *
     * @param  string $type the name of the class or interface to spy on
     * @return mixed  a spy object which extends the given class or implements the given interface
     *
     * @throws \InvalidArgumentException if no classes or interfaces exist with the given name
     */
    public static function generateSpyClassRepresenting($type)
    {
        $spyClassName = str_replace('\\', '_', $type).'_NarkSpy';
        if (in_array($spyClassName, self::$loadedSpyClasses)) {
            return "\\Nark\\{$spyClassName}";
        }

        if (class_exists($type)) {
            $spyClassCode = self::createSpyClassExtendingClass($spyClassName, $type);
        } elseif (interface_exists($type)) {
            $spyClassCode = self::createSpyClassImplementingInterface($spyClassName, $type);
        } else {
            throw new InvalidArgumentException('Can only create a spy representing a single class or interface');
        }

        // var_export($spyClassCode);
        eval($spyClassCode);
        self::$loadedSpyClasses[] = $spyClassName;

        return "\\Nark\\{$spyClassName}";
    }

    /**
     * Returns an array of source lines for the SpyBase class. This class is used as the base code
     * for all generated spy classes.  This class is modified based on the interface or class we
     * want to spy on before it is loaded.
     *
     * @return array array of source lines for the SpyBase class
     */
    private static function getSpyBaseCode()
    {
        if (empty(self::$baseClassCode)) {
            $spyBaseClassFile = dirname(__FILE__).'/SpyBase.php';
            self::$baseClassCode = new UnorderedList(file($spyBaseClassFile, FILE_IGNORE_NEW_LINES));
        }

        return self::$baseClassCode->toArray();
    }

    /**
     * Creates the source for a spy class extending a given concerete or abstract class
     *
     * @param  string $spyClassName  the name to give the spy class
     * @param  string $baseClassName the name of the class to spy on (to extend)
     * @return string the source of the generated spy class
     */
    private static function createSpyClassExtendingClass($spyClassName, $baseClassName)
    {
        $spyClassCode = self::getSpyBaseCode();
        $spyClassCode[self::CLASS_NAME_LINE_INDEX] = str_replace('SpyBase', "{$spyClassName} extends {$baseClassName}", $spyClassCode[self::CLASS_NAME_LINE_INDEX]);

        $spyClassCode = self::getSpyClassCodeLinesWithOverriddenMethodsForClassOrInterface($spyClassCode, $baseClassName);

        // return the full class definition, minute the PHP tag on the opening line
        return implode("\n", array_slice($spyClassCode, 1));
    }

    /**
     * Loads an array of source code lines for the SpyBase class with method overrides from the given
     * class or interface name
     *
     * @param  array  $spyClassCode     an array of source code lines for the SpyBase class
     * @param  string $classOrInterface the name of the class or interface from which to pull reflected
     *                                  methods and inject them as source into the SpyBase class source code
     * @return array the SpyBase class source, with the reflected methods injected
     */
    private static function getSpyClassCodeLinesWithOverriddenMethodsForClassOrInterface(array $spyClassCode, $classOrInterface)
    {
        $reflection = new ReflectionClass($classOrInterface);
        $baseClassPublicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $baseClassMethodsThatMustBeOverridden = array_map('self::getReflectedMethodAsSpyClassCode', $baseClassPublicMethods);

        // insert and override methods from base class at end of spy class definition
        array_splice($spyClassCode, (count($spyClassCode)-1), 0, $baseClassMethodsThatMustBeOverridden);

        return $spyClassCode;
    }

    /**
     * Converts a given reflection method to source code to be injected into the SpyBase class
     * to ensure it passes as an instance of a given interface or class
     *
     * @param  ReflectionMethod $method the method to convert to source and inject into the SpyBase class
     * @return string the given ReflectionMethod converted to source to be injected into the SpyBase class
     */
    private static function getReflectedMethodAsSpyClassCode(ReflectionMethod $method)
    {
        $methodName = $method->getName();
        if ($methodName === '__construct') {
            // spy class already has a constructor defined...ignore the constructor of the class we want to spy on
            return "// can't override constructor in generated spy class";
        }

        // can't override final methods
        if ($method->isFinal()) {
            return "// can't override final methods in generated spy class";
        }

        $methodSignatureParameters = [];
        $methodParameterNames = [];
        foreach ($method->getParameters() as $parameter) {
            $methodParamType = '';
            if ($parameter->hasType()) {
                $methodParamType = ((string) $parameter->getType() === 'array' ? 'array' : "\\{$parameter->getType()}");
            }
            $methodParamName = "\${$parameter->getName()}";
            $methodParamDefaultValueString = $parameter->isDefaultValueAvailable()
                ? '= '.self::getMethodSignatureDefaultArgumentInProperFormat($parameter->getDefaultValue())
                : '';

            $methodSignatureParameters[] = trim("{$methodParamType} {$methodParamName} {$methodParamDefaultValueString}");
            $methodParameterNames[] = $methodParamName;
        }

        $methodSignatureParametersString = implode(', ', $methodSignatureParameters);
        $methodParameterNamesString = implode(', ', $methodParameterNames);
        $methodType = ($method->isStatic() ? 'static function' : 'function');

        return "    public {$methodType} {$methodName}({$methodSignatureParametersString}) { return \$this->__call('{$methodName}', func_get_args()); }";
    }

    /**
     * Converts default argument pulled from a ReflectionMethod instance to proper string format for
     * injecting into source code (ie. null --> 'null', 'some_string' --> "'some_string'")
     *
     * @param  string $rawDefaultArgument the raw default argument
     * @return string the default argument in proper string format for injecting into source code
     */
    private static function getMethodSignatureDefaultArgumentInProperFormat($rawDefaultArgument)
    {
        $defaultArgumentIsConstant = (is_array($rawDefaultArgument) === false && preg_match('/^[A-Z_]{3,}$/', $rawDefaultArgument));
        $defaultArgumentIsLiteral = in_array($rawDefaultArgument, [null, true, false], true);

        if (is_numeric($rawDefaultArgument) || $defaultArgumentIsConstant) {
            // use the raw argument format if the argument is numeric or a defined constant
            return "{$rawDefaultArgument}";
        } elseif (is_array($rawDefaultArgument)) {
            return '['.implode(', ', $rawDefaultArgument).']';
        } elseif ($defaultArgumentIsLiteral) {
            // for literals, have to explicitly convert to string
            if (is_null($rawDefaultArgument)) {
                return 'null';
            }

            return ($rawDefaultArgument ? 'true' : 'false');
        } else {
            // otherwise, assume the default argument is a string that must be quoted
            return "'{$rawDefaultArgument}'";
        }
    }

    /**
     * Creates the source for a spy class implementing a given interface
     *
     * @param  string $spyClassName  the name to give the spy class
     * @param  string $interfaceName the name of the interface to spy on (to implement)
     * @return string the source of the generated spy class
     */
    private static function createSpyClassImplementingInterface($spyClassName, $interfaceName)
    {
        $spyClassCode = self::getSpyBaseCode();
        $spyClassCode[self::CLASS_NAME_LINE_INDEX] = str_replace('SpyBase', "{$spyClassName} implements {$interfaceName}", $spyClassCode[self::CLASS_NAME_LINE_INDEX]);

        $spyClassCode = self::getSpyClassCodeLinesWithOverriddenMethodsForClassOrInterface($spyClassCode, $interfaceName);

        // return the full class definition, minute the PHP tag on the opening line
        return implode("\n", array_slice($spyClassCode, 1));
    }
}
