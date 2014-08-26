<?php

namespace FluentTraversable;

use FluentTraversable\Exception\InvalidArgumentException;
use FluentTraversable\Exception\RuntimeException;
use FluentTraversable\Semantics\is;
use PhpOption\None;
use PhpOption\Option;

/**
 * Provides declarative way of array manipulation
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FluentTraversable implements TraversableFlow
{
    private $elements;

    protected function __construct($traversable)
    {
        $this->elements = is_array($traversable) ? $traversable : $this->convertToArray($traversable);
    }

    /**
     * Creates FluentTraversable from given array or traversable
     *
     * @param array|\Traversable $traversable
     * @return FluentTraversable
     */
    public static function from($traversable)
    {
        if($traversable instanceof FluentTraversable) {
            return $traversable;
        }

        self::ensureTraversable($traversable);

        return new static($traversable);
    }

    private function convertToArray($traversable)
    {
        $elements = array();

        foreach($traversable as $key => $value)
        {
            $elements[$key] = $value;
        }

        return $elements;
    }

    private static function getTypeOf($traversable)
    {
        return is_object($traversable) ? get_class($traversable) : gettype($traversable);
    }

    private static function ensureTraversable($traversable)
    {
        if (!self::isTraversable($traversable)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'FluentTraversable supports only arrays and Traversable objects, %s given',
                    self::getTypeOf($traversable)
                )
            );
        }
    }

    private static function isTraversable($traversable)
    {
        return is_array($traversable) || $traversable instanceof \Traversable;
    }

    //intermediate operations

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function map($func)
    {
        InvalidArgumentException::assertCallback($func, __METHOD__);

        foreach($this->elements as $index => &$value) {
            $value = call_user_func($func, $value, $index);
        }

        reset($this->elements);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function order($comparator = null)
    {
        InvalidArgumentException::assertCallbackIfNotNull($comparator, __METHOD__);

        if($comparator === null) {
            asort($this->elements);
        } else {
            uasort($this->elements, $comparator);
        }

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function orderBy($valFunction, $direction = 'ASC')
    {
        InvalidArgumentException::assertCallback($valFunction, __METHOD__);

        $direction = strtoupper($direction);
        $aGreaterValue = $direction === 'ASC' ? 1 : -1;

        return $this->order($this->comparatorFromProducer($valFunction, $aGreaterValue));
    }

    private function comparatorFromProducer($valFunction, $aGreaterValue)
    {
        return function ($a, $b) use ($valFunction, $aGreaterValue) {
            $aVal = $valFunction($a);
            $bVal = $valFunction($b);

            if ($aVal > $bVal) return $aGreaterValue;
            if ($aVal < $bVal) return -$aGreaterValue;
            return 0;
        };
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function append($value)
    {
        $this->elements[] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function filter($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        $elements = array();

        foreach($this->elements as $index => $value) {
            if(call_user_func($predicate, $value, $index)) {
                $elements[$index] = $value;
            }
        }

        $this->elements = $elements;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function unique()
    {
        $this->elements = array_unique($this->elements, SORT_REGULAR);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function groupBy($keyFunction)
    {
        InvalidArgumentException::assertCallback($keyFunction, __METHOD__);

        $elements = array();

        foreach($this->elements as $index => $value) {
            $key = call_user_func($keyFunction, $value, $index);
            $elements[$key][] = $value;
        }

        $this->elements = $elements;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function indexBy($indexFunction)
    {
        InvalidArgumentException::assertCallback($indexFunction, __METHOD__);

        $elements = array();

        foreach($this->elements as $index => $value) {
            $newIndex = call_user_func($indexFunction, $value, $index);

            if(array_key_exists($newIndex, $elements)) {
                throw new RuntimeException(
                    sprintf(
                        'Index collision occurred in indexBy function, two elements ("%s", "%s") with index "%s"',
                        self::getTypeOf($elements[$newIndex]),
                        self::getTypeOf($value),
                        $newIndex
                    )
                );
            }

            $elements[$newIndex] = $value;
        }

        $this->elements = $elements;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function partition($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        $this->groupBy(function($value, $index) use($predicate){
            return !call_user_func($predicate, $value, $index);
        });

        for($i=0; $i<2; $i++) {
            if(!isset($this->elements[$i])) {
                $this->elements[$i] = array();
            }
        }

        ksort($this->elements);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function skip($i)
    {
        $this->elements = array_slice($this->elements, $i);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function limit($i)
    {
        $this->elements = array_slice($this->elements, 0, $i);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function intersection($traversable)
    {
        $this->elements = array_intersect($this->elements, self::from($traversable)->toMap());

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function difference($traversable)
    {
        $this->elements = array_diff($this->elements, self::from($traversable)->toMap());

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function merge($traversable)
    {
        $this->elements = array_merge($this->elements, self::from($traversable)->toMap());

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function flatMap($func)
    {
        InvalidArgumentException::assertCallback($func, __METHOD__);

        return $this
            ->map($func)
            ->flatten();
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     * @throws \LogicException
     */
    public function flatten()
    {
        $elements = array();

        foreach($this->elements as $values) {
            if(!self::isTraversable($values)) {
                throw new \LogicException(
                    sprintf('"%s" can not be flatted, because it is not traversable', self::getTypeOf($values))
                );
            }
            foreach($values as $value) {
                $elements[] = $value;
            }
        }

        $this->elements = $elements;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return FluentTraversable
     */
    public function keys()
    {
        $this->elements = array_keys($this->elements);

        return $this;
    }

    //terminal operations

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function toArray()
    {
        return array_values($this->elements);
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function toMap()
    {
        return $this->elements;
    }

    /**
     * @inheritdoc
     */
    public function to($className)
    {
        $class = $this->getClassFromClassName($className);
        $parameters = $this->getConstructorParameters($class);

        $requiredParameters = self::from($parameters)
            ->filter(is::false('optional'));

        if($requiredParameters->size() > 1) {
            throw new InvalidArgumentException(sprintf('Constructor of "%s" has more than 1 required parameter', $className));
        }

        $constructorArgs = self::from($parameters)
            ->first()
            ->map($this->parameterToConstructorArgs())
            ->getOrElse(array());

        return $class->newInstanceArgs($constructorArgs);
    }

    private function getClassFromClassName($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist', $className));
        }

        return  new \ReflectionClass($className);
    }

    private function getConstructorParameters(\ReflectionClass $class)
    {
        $constructor = $class->getConstructor();

        if (!$constructor || !$constructor->isPublic()) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" has not defined public constructor', $class->getName())
            );
        }

        return $constructor->getParameters();
    }

    private function parameterToConstructorArgs()
    {
        $elements = $this->elements;
        return function(\ReflectionParameter $parameter) use($elements) {
            if($parameter->getClass() !== null) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Can not pass array as first constructor argument of "%s" class',
                        $parameter->getDeclaringClass()->getName()
                    )
                );
            }

            return array($elements);
        };
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function join($separator)
    {
        return implode($separator, $this->elements);
    }

    /**
     * @inheritdoc
     */
    public function first()
    {
        return Option::fromValue(current($this->elements), false);
    }

    /**
     * @inheritdoc
     */
    public function get($index)
    {
        return isset($this->elements[$index]) ? Option::fromValue($this->elements[$index]) : None::create();
    }

    /**
     * @inheritdoc
     */
    public function max($comparator = null)
    {
        InvalidArgumentException::assertCallbackIfNotNull($comparator, __METHOD__);

        if(!$this->elements) {
            return None::create();
        }

        if($comparator === null) {
            return Option::fromValue(max($this->elements));
        }

        $elements = $this->elements;
        usort($elements, $comparator);

        return Option::fromValue(end($elements));
    }

    /**
     * @inheritdoc
     */
    public function maxBy($valFunction)
    {
        InvalidArgumentException::assertCallbackIfNotNull($valFunction, __METHOD__);

        return $this->max($this->comparatorFromProducer($valFunction, 1));
    }

    /**
     * @inheritdoc
     */
    public function min($comparator = null)
    {
        InvalidArgumentException::assertCallbackIfNotNull($comparator, __METHOD__);

        if(!$this->elements) {
            return None::create();
        }

        if($comparator === null) {
            return Option::fromValue(min($this->elements));
        }

        $elements = $this->elements;
        usort($elements, $comparator);

        return Option::fromValue(current($elements));
    }

    /**
     * @inheritdoc
     */
    public function minBy($valFunction)
    {
        InvalidArgumentException::assertCallbackIfNotNull($valFunction, __METHOD__);

        return $this->min($this->comparatorFromProducer($valFunction, 1));
    }

    /**
     * @inheritdoc
     */
    public function last()
    {
        if(!$this->elements) {
            return None::create();
        }

        $value = end($this->elements);
        reset($this->elements);

        return Option::fromValue($value);
    }

    /**
     * @inheritdoc
     */
    public function firstMatch($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        foreach($this->elements as $index => $value) {
            if(call_user_func($predicate, $value, $index)) {
                return Option::fromValue($value);
            }
        }

        return None::create();
    }

    /**
     * @inheritdoc
     */
    public function reduce($biOperation)
    {
        InvalidArgumentException::assertCallback($biOperation, __METHOD__);

        if(!$this->elements) {
            return None::create();
        }

        return Option::fromValue(array_reduce($this->elements, $biOperation));
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function reduceFromIdentity($identity, $binaryOperation)
    {
        InvalidArgumentException::assertCallback($binaryOperation, __METHOD__);

        return array_reduce($this->elements, $binaryOperation, $identity);
    }

    /**
     * @inheritdoc
     */
    public function size()
    {
        return count($this->elements);
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function allMatch($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        foreach($this->elements as $index => $value) {
            if(!call_user_func($predicate, $value, $index)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function anyMatch($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        foreach($this->elements as $index => $value) {
            if(call_user_func($predicate, $value, $index)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function noneMatch($predicate)
    {
        InvalidArgumentException::assertCallback($predicate, __METHOD__);

        foreach($this->elements as $index => $value) {
            if(call_user_func($predicate, $value, $index)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function collect($collector)
    {
        InvalidArgumentException::assertCallback($collector, __METHOD__);

        return call_user_func($collector, $this->elements);
    }

    /**
     * Performs given function on each element
     *
     * @param callable $func
     *
     * @see map
     */
    public function each($func)
    {
        InvalidArgumentException::assertCallback($func, __METHOD__);

        foreach($this->elements as $index => $value) {
            call_user_func($func, $value, $index);
        }
    }
}