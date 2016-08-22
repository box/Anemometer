<?php

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";

class TestHelpers extends PHPUnit_Framework_TestCase {

    public function testget_var(){

    }

    public function testprettyprint(){
        $words = "These are words";
        $expectedResult = "<pre class=\"prettyprint\">\n$words\n</pre>";
        $returnedValue = prettyprint($words);
        $this->assertEquals($expectedResult,$returnedValue);
    }

    public function testdec2hexValid(){
        $dec = "8777294269";
        $expectedResult = "20B2AE1BD";
        $returnedValue = dec2hex($dec);
        $this->assertEquals($expectedResult,$returnedValue);
    }
    
    public function testtrim_left_zeros_wEndZeroes(){
        $string = "000000000170";
        $expectedResult = "170";
        $returnedValue = trim_left_zeros($string);
        $this->assertEquals($expectedResult,$returnedValue);
    }

    public function testtrim_left_zeros_wNoExtraZeroes(){
        $string = "000000000177";
        $expectedResult = "177";
        $returnedValue = trim_left_zeros($string);
        $this->assertEquals($expectedResult,$returnedValue);
    }

    public function testtrim_left_zeros_woZeroes(){
        $string = "29";
        $expectedResult = "29";
        $returnedValue = trim_left_zeros($string);
        $this->assertEquals($expectedResult,$returnedValue);
    }
}
