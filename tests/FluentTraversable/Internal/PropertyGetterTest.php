<?php


namespace FluentTraversable\Internal;


use FluentTraversable\Puppet;

class PropertyGetterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyGetter
     */
    private $propertyGetter;

    protected function setUp()
    {
        $this->propertyGetter = new PropertyGetter();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testGetValue($object, $property, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->propertyGetter->getValue($object, $property));
    }

    public function dataProvider()
    {
        return array(
            array(
                new PropertyGetterTest_Object('p1', 'p2'),
                'property1',
                'p1',
            ),
            array(
                new PropertyGetterTest_Object('p1', 'p2'),
                'property2',
                'p2',
            ),
            array(
                array('prop1' => 'p1'),
                'prop1',
                'p1',
            ),
            array(
                array('prop1' => 'p1'),
                'prop2',
                null,
            ),
            array(
                new PropertyGetterTest_Object(array('nestedProp' => 'value'), 'p2'),
                'property1.nestedProp',
                'value',
            ),
            array(
                new PropertyGetterTest_Object('p1', 'p2'),
                Puppet::object()->getProperty2(),
                'p2',
            ),
        );
    }
}

class PropertyGetterTest_Object
{
    public $property1;
    private $property2;

    function __construct($property1, $property2)
    {
        $this->property1 = $property1;
        $this->property2 = $property2;
    }

    public function getProperty2()
    {
        return $this->property2;
    }
}