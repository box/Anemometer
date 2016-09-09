<?php

require_once 'lib/QueryExplain.php';

/**
 * Created by IntelliJ IDEA.
 * User: jwang
 * Date: 9/1/16
 * Time: 10:27 AM
 */

class queryResult
{
    private $result;

    function __construct($queryResult) {
        $this->result=$queryResult;
    }

    public function fetch_array() {
        return $this->result;
    }

    public function fetch_assoc() {
        $item= array_pop($this->result);
        return $item;
    }
}

class testMysqli
{
    private static $response=array("SHOW CREATE TABLE table01" => array(0,"CREATE TABLE `table01` (\n  `col01` int(11) DEFAULT NULL,\n  `col02` varchar(100) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8"),
                                   "SHOW TABLE STATUS LIKE 'table01'" => array(
                                       array(   "Name"=>"table01",
                                                "Engine"=>"InnoDB",
                                                "Version"=>"10",
                                                "Row_format"=>"Compact",
                                                "Rows"=>"0",
                                                "Avg_row_length"=>"0",
                                                "Data_length"=>"16384",
                                                "Max_data_length"=>"0",
                                                "Index_length"=>"0",
                                                "Data_free"=>"0",
                                                "Auto_increment"=>"",
                                                "Create_time"=>"2016-09-08 10:45:21",
                                                "Update_time"=>"",
                                                "Check_time"=>"",
                                                "Collation"=>"utf8_general_ci",
                                                "Checksum"=>"","Create_options"=>"",
                                                "Comment"=>""
                                            ),
                                   ),
                                   "EXPLAIN SELECT col01 from table01 where table01.col02=789" => array(
                                       array(
                                                "id" => "1",
                                                "select_type" => "SIMPLE",
                                                "table" => "table01",
                                                "type" => "ALL",
                                                "possible_keys" => "",
                                                "key" => "",
                                                "key_len" => "",
                                                "ref" => "",
                                                "rows" => "1",
                                                "Extra" => "Using where"),
                                            ),
                                   );
    public $errno;

    public function query($sql) {
        $queryResult=self::$response[$sql];
        return new queryResult($queryResult);
    }

    public function setErrno($no) {
        $this->errno=$no;
    }
}

class TestQueryExplain extends PHPUnit_Framework_TestCase
{
    protected $_QueryExplain = null;
    private $sample;
    private $callback;

    public function setUp()
    {
        $this->sample = array(
            "hostname_max" => "testhost",
            "database_max" => "testdatabase",
            "db" => "testdb",
            "password" => "testpass",
            "sample" => "SELECT col01 from table01 where table01.col02=789",
        );

        $this->callback = function(array $sample)
        {
              return array(
                'host'  => $sample['hostname_max'],
                'db'    => $sample['database_max'],
                'port'  => '3306',
                'user'  => 'username',
                'password' => 'password',
              );
        };

    }

    public function tearDown()
    {
        unset($this->_QueryExplain);
    }

    public function testGetTablesFromQuery()
    {
        $classToMock='QueryExplain';
        $methodsToMock = array('connect');
        $mock=$this->getMock($classToMock,$methodsToMock,array($this->callback,$this->sample),'',false,false );
        $mock->conf=call_user_func($this->callback, $this->sample);
        $mock->query = $this->sample['sample'];
        //$mock->expects($this->once())->method('connect')->will($this->returnValue(true));

        //$this->_QueryExplain = new QueryExplain($this->callback,$this->sample);
        //$sql = 'SELECT col1 from table01 INNER JOIN table02 on table01.col2=table02.col3';
        $expectedResult = array('table01');
        $result = $mock->get_tables_from_query();
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetCreate()
    {
        $classToMock='QueryExplain';
        $methodsToMock = array('connect');
        $mock=$this->getMock($classToMock,$methodsToMock,array($this->callback,$this->sample),'',false,false );
        $mock->conf=call_user_func($this->callback, $this->sample);
        $mock->query = $this->sample['sample'];
        $mock->mysqli = new testMysqli();
        $expectedResult = "CREATE TABLE `table01` (\n  `col01` int(11) DEFAULT NULL,\n  `col02` varchar(100) DEFAULT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $result = $mock->get_create();
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetTableStatus()
    {
        $classToMock='QueryExplain';
        $methodsToMock = array('connect');
        $mock=$this->getMock($classToMock,$methodsToMock,array($this->callback,$this->sample),'',false,false );
        $mock->conf=call_user_func($this->callback, $this->sample);
        $mock->query = $this->sample['sample'];
        $mock->mysqli = new testMysqli();
        $expectedResult = "                Name : table01\n              Engine : InnoDB\n             Version : 10\n          Row_format : Compact\n                Rows : 0\n      Avg_row_length : 0\n         Data_length : 16384\n     Max_data_length : 0\n        Index_length : 0\n           Data_free : 0\n      Auto_increment : \n         Create_time : 2016-09-08 10:45:21\n         Update_time : \n          Check_time : \n           Collation : utf8_general_ci\n            Checksum : \n      Create_options : \n             Comment : \n";
        $result = $mock->get_table_status();
        $this->assertEquals($expectedResult, $result);
    }

    public function testExplain()
    {
        $classToMock='QueryExplain';
        $methodsToMock = array('connect');
        $mock=$this->getMock($classToMock,$methodsToMock,array($this->callback,$this->sample),'',false,false );
        $mock->conf=call_user_func($this->callback, $this->sample);
        $mock->query = $this->sample['sample'];
        $mock->mysqli = new testMysqli();
        $mock->mysqli->setErrno(0);
        $expectedResult = "+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+\n| id | select_type | table   | type | possible_keys | key | key_len | ref | rows | Extra       |\n+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+\n| 1  | SIMPLE      | table01 | ALL  |               |     |         |     | 1    | Using where |\n+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+\n";
        $result = $mock->explain();
        $this->assertEquals($expectedResult, $result);
    }

}
