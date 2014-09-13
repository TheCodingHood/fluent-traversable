<?php


namespace FluentTraversable;


use PhpOption\Option;

class FluentTraversableTest extends \PHPUnit_Framework_TestCase
{
    public function testCreationFromIterator()
    {
        $actual = FluentTraversable::from(new \ArrayObject(array(1, 2, 3)))
            ->toMap();

        $this->assertSame(array(1, 2, 3), $actual);
    }

    public function testMap()
    {
        $actual = FluentTraversable::from(array('A', 'b', 'C'))
            ->map(function($value, $index){
                return strtolower($value).$index;
            })
            ->toArray();

        $this->assertSame(array('a0', 'b1', 'c2'), $actual);
    }

    public function testFilter()
    {
        $test = $this;

        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5))
            ->filter(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 2 == 0;
            })
            ->toArray();

        $this->assertSame(array(2, 4), $actual);
    }

    public function testUnique()
    {
        $actual = FluentTraversable::from(array(1, 1, 2, 3, 2,  0, 1))
            ->unique()
            ->toArray();

        $this->assertSame(array(1, 2, 3, 0), $actual);
    }

    public function testJoin_givenEmptyArray_expectEmptyString()
    {
        $actual = FluentTraversable::from(array())
            ->join(',');

        $this->assertSame('', $actual);
    }

    public function testJoin_givenNotEmptyArray_expectJoinedArray()
    {
        $actual = FluentTraversable::from(array(1, 2, '3', new FluentTraversableTest_String('4')))
            ->join(',');

        $this->assertSame('1,2,3,4', $actual);
    }

    public function testFirst_givenEmptyArray_expectNoneOption()
    {
        $actual = FluentTraversable::from(array())
            ->first();

        $this->assertTrue($actual->isEmpty());
    }

    public function testFirst_givenArrayWithValues_expectSomeFirstValue()
    {
        $actual = FluentTraversable::from(array(3, 2, 1, 4))
            ->first();

        $this->assertOptionWithValue(3, $actual);
    }

    public function testFirst_givenArrayWithValues_givenKeysAreNotSequential_expectSomeFirstValue()
    {
        $actual = FluentTraversable::from(array(9 => 5, 0 => 2))
            ->first();

        $this->assertOptionWithValue(5, $actual);
    }

    public function testLast_givenEmptyArray_expectNoneOption()
    {
        $actual = FluentTraversable::from(array())
            ->last();

        $this->assertTrue($actual->isEmpty());
    }

    public function testLast_givenArrayWithValues_expectSomeLastValue()
    {
        $actual = FluentTraversable::from(array(3, 2, 1, 4))
            ->last();

        $this->assertOptionWithValue(4, $actual);
    }

    public function testLast_givenArrayWithValues_givenKeysAreNotSequential_expectSomeLastValue()
    {
        $actual = FluentTraversable::from(array(7 => 3, 3 => 1, 10 => 5))
            ->last();

        $this->assertTrue($actual->isDefined());
        $this->assertSame(5, $actual->get());
    }

    public function testOrder_preserveKeys()
    {
        $actual = FluentTraversable::from(array(4, 1, 3, 2))
            ->order()
            ->toMap();

        $this->assertSame(array(1 => 1, 3 => 2, 2 => 3, 0 => 4), $actual);
    }

    public function testOrder_givenComparator_expectOrderDefinedByComparator()
    {
        $actual = FluentTraversable::from(array(4, 1, 3, 2))
            ->order(function($a, $b){
                return $b - $a;
            })
            ->toMap();

        $this->assertSame(array(0 => 4, 2 => 3, 3 => 2, 1 => 1), $actual);
    }

    public function testMax()
    {
        $actual = FluentTraversable::from(array(3, 5, 1, 4))
            ->max();

        $this->assertOptionWithValue(5, $actual);
    }

    public function testMax_givenReversedComparator_expectMin()
    {
        $actual = FluentTraversable::from(array(3, 5, 1, 4))
            ->max(function($a, $b){
                return $b - $a;
            });

        $this->assertOptionWithValue(1, $actual);
    }

    public function testMax_givenEmptyArray_returnNone()
    {
        $actual = FluentTraversable::from(array())
            ->max();

        $this->assertTrue($actual->isEmpty());
    }

    public function testMaxBy()
    {
        $actual = FluentTraversable::from(array(
            array('age' => 5),
            array('age' => 3),
            array('age' => 24),
            array('age' => 8),
        ))->maxBy(function($value){
            return $value['age'];
        });

        $this->assertOptionWithValue(array('age' => 24), $actual);
    }

    public function testMin()
    {
        $actual = FluentTraversable::from(array(3, 5, 1, 4))
            ->min();

        $this->assertOptionWithValue(1, $actual);
    }

    public function testMin_givenReversedComparator_expectMax()
    {
        $actual = FluentTraversable::from(array(3, 5, 1, 4))
            ->min(function($a, $b){
                return $b - $a;
            });

        $this->assertOptionWithValue(5, $actual);
    }

    public function testMin_givenEmptyArray_returnNone()
    {
        $actual = FluentTraversable::from(array())
            ->min();

        $this->assertTrue($actual->isEmpty());
    }


    public function testMinBy()
    {
        $actual = FluentTraversable::from(array(
            array('age' => 5),
            array('age' => 3),
            array('age' => 24),
            array('age' => 8),
        ))->minBy(function($value){
            return $value['age'];
        });

        $this->assertOptionWithValue(array('age' => 3), $actual);
    }

    public function testFirstMatch_givenEmptyArray_returnNone()
    {
        $actual = FluentTraversable::from(array())
            ->firstMatch(function(){
                return true;
            });

        $this->assertTrue($actual->isEmpty());
    }

    public function testFirstMatch_givenArrayWithValues_noneValueMatches_returnNone()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4))
            ->firstMatch(function(){
                return false;
            });

        $this->assertTrue($actual->isEmpty());
    }

    public function testFirstMatch_givenArrayWithValues_fewValuesMatches_expectFirst()
    {
        $test = $this;

        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5, 6))
            ->firstMatch(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 2 === 0;
            });

        $this->assertOptionWithValue(2, $actual);
    }

    public function testReduce_givenEmptyArray_returnNone()
    {
        $actual = FluentTraversable::from(array())
            ->reduce(function($a, $b){
                return $a + $b;
            });

        $this->assertTrue($actual->isEmpty());
    }

    public function testReduce_givenArrayWithValue_returnProduce()
    {
        $actual = FluentTraversable::from(array(1, 2, 3))
            ->reduce(function($a, $b){
                return $a + $b;
            });

        $this->assertOptionWithValue(6, $actual);
    }

    public function testReduceFromIdentity_givenEmptyArray_expectIdentity()
    {
        $actual = FluentTraversable::from(array())
            ->reduceFromIdentity(5, function($a, $b){
                return $a + $b;
            });

        $this->assertEquals(5, $actual);
    }

    public function testReduceFromIdentity_givenArrayWithValues_expectProduct()
    {
        $actual = FluentTraversable::from(array(3, 2, 1))
            ->reduceFromIdentity(2, function($a, $b){
                return $a + $b;
            });

        $this->assertEquals(8, $actual);
    }

    public function testAllMatch_givenEmptyArray_expectTrue()
    {
        $actual = FluentTraversable::from(array())
            ->allMatch(function(){
                return true;
            });

        $this->assertTrue($actual);
    }

    public function testAllMatch_givenArrayWithValue_fewValuesMatch_expectFalse()
    {
        $test = $this;

        $actual = FluentTraversable::from(array(1, 2, 3, 4))
            ->allMatch(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 2 === 0;
            });

        $this->assertFalse($actual);
    }

    public function testAllMatch_givenArrayWithValue_allValuesMatch_expectTrue()
    {
        $actual = FluentTraversable::from(array(1, 1, 1, 1))
            ->allMatch(function($value){
                return $value === 1;
            });

        $this->assertTrue($actual);
    }

    public function testAnyMatch_givenEmptyArray_returnFalse()
    {
        $actual = FluentTraversable::from(array())
            ->anyMatch(function($value){
                return true;
            });

        $this->assertFalse($actual);
    }

    public function testAnyMatch_givenArrayWithValues_givenValuesDoNotMatch_returnFalse()
    {
        $actual = FluentTraversable::from(array(1, 3, 5))
            ->anyMatch(function($value){
                return $value % 2 === 0;
            });

        $this->assertFalse($actual);
    }

    public function testAnyMatch_givenArrayWithValues_someValuesMatch_returnTrue()
    {
        $test = $this;

        $actual = FluentTraversable::from(array(1, 2, 3))
            ->anyMatch(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 2 === 0;
            });

        $this->assertTrue($actual);
    }

    public function testNoneMatch_givenEmptyArray_expectTrue()
    {
        $actual = FluentTraversable::from(array())
            ->noneMatch(function($value){
                return false;
            });

        $this->assertTrue($actual);
    }

    public function testNoneMatch_givenArrayWithValue_allGivenValuesMatch_expectFalse()
    {
        $actual = FluentTraversable::from(array(2, 4, 6))
            ->noneMatch(function($value){
                return $value % 2 === 0;
            });

        $this->assertFalse($actual);
    }

    public function testNoneMatch_givenArrayWithValue_allGivenValuesDoNotMatch_expectTrue()
    {
        $actual = FluentTraversable::from(array(1, 3, 5))
            ->noneMatch(function($value){
                return $value % 2 === 0;
            });

        $this->assertTrue($actual);
    }

    public function testGroupBy()
    {
        $test = $this;

        $actual = FluentTraversable::from(range(1, 9))
            ->groupBy(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 3;
            })
            ->toMap();

        $this->assertSame(array(
            1 => array(1, 4, 7),
            2 => array(2, 5, 8),
            0 => array(3, 6, 9),
        ), $actual);
    }

    public function testIndexBy()
    {
        $test = $this;

        $actual = FluentTraversable::from(range(1, 4))
            ->indexBy(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value + 3;
            })
            ->toMap();

        $this->assertSame(array(
            4 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
        ), $actual);
    }

    public function testIndexBy_indexCollision_acceptLastElement()
    {
        $actual = FluentTraversable::from(range(1, 4))
            ->indexBy(function($value){
                return 1;
            })
            ->toMap();

        $this->assertSame(array(1 => 4), $actual);
    }

    public function testPartition_givenEmptyArray_returnTwoEmptyArrays()
    {
        $actual = FluentTraversable::from(array())
            ->partition(function(){
                return true;
            })
            ->toMap();

        $this->assertSame(array(
            array(),
            array(),
        ), $actual);
    }

    public function testPartition_givenArrayWithOneElement_givenElementSatisfiesPredicate_returnValidArray()
    {
        $actual = FluentTraversable::from(array(5))
            ->partition(function(){
                return true;
            })
            ->toMap();

        $this->assertSame(array(
            array(5),
            array(),
        ), $actual);
    }

    public function testPartition_givenArrayWithRangeOfNumbers_makeParityPartition()
    {
        $test = $this;

        $actual = FluentTraversable::from(range(1, 9))
            ->partition(function($value, $index) use($test){
                $test->assertEquals(1, $value - $index);

                return $value % 2 === 0;
            })
            ->toMap();

        $this->assertSame(array(
            array(2, 4, 6, 8),
            array(1, 3, 5, 7, 9),
        ), $actual);
    }

    public function testSkip_givenArrayWithFewElements_givenSkipGreaterThanArrayLength_expectEmptyArray()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4))
            ->skip(5)
            ->toArray();

        $this->assertEmpty($actual);
    }

    public function testSkip_givenArrayWithFewElements_givenSkipLessThanArrayLength_expectSubarray()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5))
            ->skip(2)
            ->toArray();

        $this->assertSame(array(3, 4, 5), $actual);
    }

    public function testSkip_givenSkipByZero_returnTheSameArray()
    {
        $actual = FluentTraversable::from(array(1, 2, 3))
            ->skip(0)
            ->toArray();

        $this->assertSame(array(1, 2, 3), $actual);
    }

    public function testLimit_givenLimitGreaterThanArrayLength_returnTheSameArray()
    {
        $actual = FluentTraversable::from(array(1, 2, 3))
            ->limit(10)
            ->toArray();

        $this->assertSame(array(1, 2, 3), $actual);
    }

    public function testLimit_givenZeroLimit_returnEmptyArray()
    {
        $actual = FluentTraversable::from(array(1, 2))
            ->limit(0)
            ->toArray();

        $this->assertEmpty($actual);
    }

    public function testLimit_givenLimitLessThanArrayLength_returnSubarray()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5))
            ->limit(3)
            ->toArray();

        $this->assertSame(array(1, 2, 3), $actual);
    }

    public function testIntersection()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5))
            ->intersection(array(3, 4, 6))
            ->toArray();

        $this->assertSame(array(3, 4), $actual);
    }

    public function testDifference()
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4))
            ->difference(array(2, 4, 6))
            ->toArray();

        $this->assertSame(array(1, 3), $actual);
    }

    public function testCollect()
    {
        $actual = FluentTraversable::from(array(1, 2, 3))
            ->collect(function($elements){
                return new \ArrayObject($elements);
            });

        $this->assertEquals(new \ArrayObject(array(1, 2, 3)), $actual);
    }

    public function testFlatMap()
    {
        $actual = FluentTraversable::from(array('some', 'text', 'to', 'flat', 'map'))
            ->flatMap(function($value){
                return str_split($value);
            })
            ->toArray();

        $this->assertSame(array('s', 'o', 'm', 'e', 't', 'e', 'x', 't', 't', 'o', 'f', 'l', 'a', 't', 'm', 'a', 'p'), $actual);
    }

    public function testFlatten()
    {
        $actual = FluentTraversable::from(array(
            array(1, 2),
            array(3, 4, array(5))
            ))
            ->flatten()
            ->toArray();

        $this->assertSame(array(1, 2, 3, 4, array(5)), $actual);
    }

    /**
     * @expectedException \LogicException
     */
    public function testFlatten_oneValueIsNotAnArray_throwEx()
    {
        FluentTraversable::from(array(array(3), 4))
            ->flatten()
            ->toArray();
    }

    public function testKeys()
    {
        $actual = FluentTraversable::from(array('key1' => 'value1', 'key2' => 'value2'))
            ->keys()
            ->toArray();

        $this->assertEquals(array('key1', 'key2'), $actual);
    }

    public function testTo_givenValidClass_createValidObject()
    {
        $result = FluentTraversable::from(array(1, 2, 3))
            ->to('ArrayObject');

        $this->assertInstanceOf('ArrayObject', $result);
        $this->assertEquals(array(1, 2, 3), $result->getArrayCopy());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTo_givenInvalidClass_throwEx()
    {
        FluentTraversable::from(array(1, 2, 3))
            ->to('InvalidClass');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTo_givenValidClass_givenClassConstructorHasNotArguments_throwEx()
    {
        FluentTraversable::from(array(1, 2, 3))
            ->to('stdClass');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTo_givenValidClass_givenConstructorHasFewRequiredArgs_throwEx()
    {
        FluentTraversable::from(array(1, 2, 3))
            ->to('FluentTraversable\\FluentTraversableTest_FewRequiredConstructorArgs');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTo_givenValidClass_givenConstructorHasOneNotFirstRequiredArrayArg_throwEx()
    {
        FluentTraversable::from(array(1, 2, 3))
            ->to('FluentTraversable\\FluentTraversableTest_FewOptionalAndOneArrayRequired');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTo_givenValidClass_givenConstructorHasOneClassHintedArg_throwEx()
    {
        FluentTraversable::from(array(1, 2, 3))
            ->to('FluentTraversable\\FluentTraversableTest_OneRequiredClassArg');
    }

    public function testGet_valueExists()
    {
        $actual = FluentTraversable::from(array(1, 2, 3))
            ->get(1);

        $this->assertOptionWithValue(2, $actual);
    }

    public function testGet_valueExistsButItIsFalse_expectedSome()
    {
        $actual = FluentTraversable::from(array(true, false))
            ->get(1);

        $this->assertOptionWithValue(false, $actual);
    }

    public function testGet_valueExistsButIsNull_expectedNone()
    {
        $actual = FluentTraversable::from(array(null, false))
            ->get(0);

        $this->assertFalse($actual->isDefined());
    }

    public function testGet_valueDoesNotExist_expectedNone()
    {
        $actual = FluentTraversable::from(array(null, false))
            ->get(3);

        $this->assertFalse($actual->isDefined());
    }

    public function testTo_givenValidClass_givenConstructorHasOneArrayHintedArg_ok()
    {
        $result = FluentTraversable::from(array(1, 2, 3))
            ->to('FluentTraversable\\FluentTraversableTest_OneOptionalArrayArg');

        $this->assertEquals(array(1, 2, 3), $result->arg1);
    }

    public function testZip_givenShorterTraversable_useNullOnMissingKeys()
    {
        $result = FluentTraversable::from(array(1, 2, 3))
            ->zip(array('a', 2 => 'c'))
            ->toMap();

        $this->assertEquals(array(array(1, 'a'), array(2, null), array(3, 'c')), $result);
    }

    public function testZip_givenCombiningFunction_useTheFunctionToCombineValues()
    {
        $result = FluentTraversable::from(array(1, 2, 3))
            ->zip(array('a', 2 => 'c'), function($a, $b){
                return $a.$b;
            })
            ->toMap();

        $this->assertEquals(array('1a', '2', '3c'), $result);
    }

    /**
     * @test
     * @dataProvider orderByProvider
     */
    public function testOrderBy_givenUnsortedValues_sortIt($values, $orderBy, $direction, $expected)
    {
        $actual = FluentTraversable::from($values)
            ->orderBy($orderBy, $direction)
            ->toArray();

        $this->assertSame($expected, $actual);
    }

    public function testMerge()
    {
        $actual = FluentTraversable::from(array('a' => 'A', 'b' => 'B', 1, 2, 3))
            ->merge(array('a' => 'C', 1, 5))
            ->toMap();

        $this->assertSame(array('a' => 'C', 'b' => 'B', 1, 2, 3, 1, 5), $actual);
    }

    /**
     * @test
     * @dataProvider resetArrayInternalPointerProvider
     */
    public function testResetArrayInternalPointer($method, $func = null)
    {
        $actual = FluentTraversable::from(array(1, 2, 3, 4, 5, 6))
            ->$method($func)
            ->first()
            ->getOrElse(null);

        $this->assertNotNull($actual);
    }

    public function resetArrayInternalPointerProvider()
    {
        return array(
            array(
                'map',
                function($value){
                    return $value;
                },
            ),
            array(
                'zip',
                array(1, 2, 3),
            ),
            array(
                'filter',
                function($value){
                    return true;
                },
            ),
            array(
                'groupBy',
                function($value){
                    return $value;
                },
            ),
            array(
                'indexBy',
                function($value){
                    return $value;
                },
            ),
            array(
                'partition',
                function($value){
                    return $value % 2 === 0;
                }
            )
        );
    }

    public function orderByProvider()
    {
        return array(
            array(
                array(
                    array('name' => 'T'),
                    array('name' => 'Z'),
                    array('name' => 'A'),
                ),
                function($element){
                    return $element['name'];
                },
                'ASC',
                array(
                    array('name' => 'A'),
                    array('name' => 'T'),
                    array('name' => 'Z'),
                ),
            ),
            array(
                array(
                    array('name' => 'T'),
                    array('name' => 'Z'),
                    array('name' => 'A'),
                ),
                function($element){
                    return $element['name'];
                },
                'DESC',
                array(
                    array('name' => 'Z'),
                    array('name' => 'T'),
                    array('name' => 'A'),
                ),
            ),
        );
    }

    private function assertOptionWithValue($expected, Option $actual)
    {
        $this->assertTrue($actual->isDefined());
        $this->assertSame($expected, $actual->get());
    }
}


class FluentTraversableTest_String
{
    private $string;

    function __construct($string)
    {
        $this->string = $string;
    }

    function __toString()
    {
        return $this->string;
    }
}

class FluentTraversableTest_FewRequiredConstructorArgs
{
    public function __construct($arg1, $arg2)
    {
    }
}

class FluentTraversableTest_FewOptionalAndOneArrayRequired
{
    public $arg1;
    public $arg2;
    public $arg3;

    public function __construct($arg1 = null, array $arg2, $arg3 = null)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
        $this->arg3 = $arg3;
    }
}

class FluentTraversableTest_OneRequiredClassArg
{
    public function __construct(\stdClass $arg1)
    {
    }
}

class FluentTraversableTest_OneOptionalArrayArg
{
    public function __construct(array $arg1 = array())
    {
        $this->arg1 = $arg1;
    }
}
