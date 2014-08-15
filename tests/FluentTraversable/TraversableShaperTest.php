<?php


namespace FluentTraversable;

use PhpOption\Option;

class TraversableShaperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function defineShaper_invokeItOnSomeArrays_itShouldProceedInput()
    {
        //given

        $shaper = TraversableShaper::create()
            ->map('strtoupper')
            ->filter(function($value){
                return $value[0] === 'A';
            })
            ->unique()
            ->toArray()
            ;

        //when

        $actual = $shaper(array('Asia', 'Apple', 'php', 'android', 'Android', 'tv', 'php'));

        //then

        $this->assertSame(array('ASIA', 'APPLE', 'ANDROID'), $actual);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function defineShaper_executeTwoTerminalOperations_throwException()
    {
        TraversableShaper::create()
            ->toArray()
            ->toMap();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function defineShaper_executeIntermediateOpAfterTerminalOp_throwException()
    {
        TraversableShaper::create()
            ->toArray()
            ->map('strtolower');
    }

    /**
     * @test
     */
    public function defineShaper_executeTerminalOpWithOptionAsResult()
    {
        $max = TraversableShaper::create();
        $max
            ->max()
            ->map(function($value){
                return 'max: '.$value;
            })
            ->orElse(Option::fromValue('max not found'))
            ->get();

        $this->assertEquals('max: 5', $max->apply(array(1, 5, 3)));
        $this->assertEquals('max not found', $max->apply(array()));
    }
}
 