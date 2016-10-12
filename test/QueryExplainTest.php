<?php

require_once __DIR__.'/../lib/QueryExplain.php';

/**
 * Created by IntelliJ IDEA.
 * User: jwang
 * Date: 9/1/16
 * Time: 10:27 AM
 */

class queryResult
{
    private $result;

    public function __construct($queryResult) {
        $this->result=$queryResult;
    }

    public function fetch_array() {
        return $this->result;
    }

    public function fetch_assoc() {
        return array_pop($this->result);
    }
}

class testMysqli
{
    private static $response = array(
        'SHOW CREATE TABLE table01' => 
            array(
                0,
                'CREATE TABLE `table01` (
  `col01` int(11) DEFAULT NULL,
  `col02` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            ),
        'SHOW TABLE STATUS LIKE \'table01\'' => 
            array(
                array(
                    'Name' => 'table01',
                    'Engine' => 'InnoDB',
                    'Version' => '10',
                    'Row_format' => 'Compact',
                    'Rows' => '0',
                    'Data_length' => '16384',
                    'Max_data_length' => '0',
                    'Index_length' => '0',
                    'Data_free' => '0',
                    'Auto_increment' => 'NULL',
                    'Create_time' => '2016-10-11',
                    'Update_time' => 'NULL',
                    'Check_time' => 'NULL',
                    'Collation' => 'utf8_general_ci',
                    'Checksum' => 'NULL',
                    'Create_options' => '',
                    'Comment' => '',
                ),
            ),
        'EXPLAIN SELECT col01 from table01 where table01.col02=789' =>
            array(
                array(
                    'id' => '1',
                    'select_type' => 'SIMPLE',
                    'table' => 'table01',
                    'type' => 'ALL',
                    'possible_keys' => '',
                    'key' => '',
                    'key_len' => '',
                    'ref' => '',
                    'rows' => '1',
                    'Extra' => 'Using where'
                ),
            ),
        );
    public $errno;

    public function query($sql) {
        $queryResult = self::$response[$sql];
        return new queryResult($queryResult);
    }

    public function setErrno($no) {
        $this->errno=$no;
    }
}

class TestQueryExplain extends PHPUnit_Framework_TestCase
{
    protected $_QueryExplain;
    private $sample;
    private $callback;

    public function setUp()
    {
        $this->sample = array(
            'hostname_max' => 'testhost',
            'database_max' => 'testdatabase',
            'db' => 'testdb',
            'password' => 'testpass',
            'sample' => 'SELECT col01 from table01 where table01.col02=789',
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
        $query = $this->sample['sample'];

        $mock_QueryTableParser = $this->getMockBuilder(QueryTableParser::class)
            ->setMethods(['parse'])
            ->getMock();

        $mock_QueryTableParser->expects(static::once())
            ->method('parse')
            ->with(static::equalTo($query));

        /** @var QueryExplain $mock_QueryExplain */
        $mock_QueryExplain = $this->getMockBuilder(QueryExplain::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $mock_QueryExplain->query = $query;

        $mock_QueryExplain->get_tables_from_query($mock_QueryTableParser);

    }

    public function testGetCreate()
    {
        /** @var QueryExplain $mock_QueryExplain */
        $mock_QueryExplain = $this->getMockBuilder(QueryExplain::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $mock_QueryExplain->mysqli = new testMysqli();
        $mock_QueryExplain->query = $this->sample['sample'];

        $result = $mock_QueryExplain->get_create();
        static::assertEquals('CREATE TABLE `table01` (
  `col01` int(11) DEFAULT NULL,
  `col02` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8', $result);

    }

    public function testGetTableStatus()
    {
        /** @var QueryExplain $mock_QueryExplain */
        $mock_QueryExplain = $this->getMockBuilder(QueryExplain::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $mock_QueryExplain->mysqli = new testMysqli();
        $mock_QueryExplain->query = $this->sample['sample'];

        $result = $mock_QueryExplain->get_table_status();
        static::assertEquals('                Name : table01
              Engine : InnoDB
             Version : 10
          Row_format : Compact
                Rows : 0
         Data_length : 16384
     Max_data_length : 0
        Index_length : 0
           Data_free : 0
      Auto_increment : NULL
         Create_time : 2016-10-11
         Update_time : NULL
          Check_time : NULL
           Collation : utf8_general_ci
            Checksum : NULL
      Create_options : 
             Comment : 
', $result);

    }

    public function testExplain()
    {
        /** @var QueryExplain $mock_QueryExplain */
        $mock_QueryExplain = $this->getMockBuilder(QueryExplain::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $mock_QueryExplain->mysqli = new testMysqli();
        $mock_QueryExplain->query = $this->sample['sample'];

        $result = $mock_QueryExplain->explain();

        static::assertEquals('+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
| id | select_type | table   | type | possible_keys | key | key_len | ref | rows | Extra       |
+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
| 1  | SIMPLE      | table01 | ALL  |               |     |         |     | 1    | Using where |
+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
', $result);
    }

    public function testQueryWithComment()
    {
        /** @var QueryExplain $mock_QueryExplain */
        $mock_QueryExplain = $this->getMockBuilder(QueryExplain::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $mock_QueryExplain->mysqli = new testMysqli();
        $mock_QueryExplain->query = 'SELECT /* some comment */col01 from table01 where table01.col02=789';

        static::assertEquals('+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
| id | select_type | table   | type | possible_keys | key | key_len | ref | rows | Extra       |
+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
| 1  | SIMPLE      | table01 | ALL  |               |     |         |     | 1    | Using where |
+----+-------------+---------+------+---------------+-----+---------+-----+------+-------------+
', $mock_QueryExplain->explain());

    }
}
