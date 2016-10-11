<?php

require_once __DIR__.'/../lib/QueryTableParser.php';

class TestQueryTableParser extends PHPUnit_Framework_TestCase
{
    /**
     * @var QueryTableParser
     */
    protected $_QueryTableParser;

    public function setUp()
    {
        $this->_QueryTableParser = new QueryTableParser();
    }

    public function tearDown()
    {
        unset($this->_QueryTableParser);
    }

    public function testSelect()
    {
        $sql = 'SELECT col1 from table01';
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testSelectWithComments() {
        $sql                     = '/* 123 * 456 */ SELECT col1 from table01 #214241*/';
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testSelectWithWhitespace() {
        $sql                     = "\t\t    SELECT       \t\t\t   col1 \t\t\t from   \t\t table01 \t\t\n";
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testDelete() {
        $sql                     = 'DELETE FROM table01 WHERE id = 123';
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testInsert() {
        $sql                     = 'INSERT INTO table01 (col1, col2, col3) VALUES (1, 2, 3), (4, 5, 6)';
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testInsertSelect() {
        $sql                     = 'INSERT INTO table01 SELECT * FROM table02';
        $expectedResult = array('table01','table02');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testUpdate() {
        $sql                     = 'UPDATE table01 SET col1 = 123 WHERE id = 456';
        $expectedResult = array('table01');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }

    public function testJoin() {
        $sql                     = 'SELECT col1 from table01 INNER JOIN table02 on table01.col2=table02.col3';
        $expectedResult = array('table01','table02');
        $result = $this->_QueryTableParser->parse($sql);
        static::assertEquals($expectedResult, $result);
    }
}
