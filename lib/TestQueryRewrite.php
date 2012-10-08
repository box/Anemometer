<?php
require_once 'PHPUnit/Autoload.php';
require_once 'QueryRewrite.php';

class TestQueryRewrite extends PHPUnit_Framework_TestCase {
    protected $_QueryRewrite = null;

    public function setUp() {
        $this->_QueryRewrite = new QueryRewrite();
    }

    public function tearDown() {
        unset($this->_QueryRewrite);
    }

    public function testSelect() {
        $sql                     = 'SELECT 123';
        $expectedType            = QueryRewrite::SELECT;
        $expectedSelect          = 'SELECT 123';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testSelectWithComments() {
        $sql                     = '/* 123 * 456 */ SELECT 123 #214241*/';
        $expectedType            = QueryRewrite::SELECT;
        $expectedSelect          = 'SELECT 123';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testSelectWithWhitespace() {
        $sql                     = "\t\t    SELECT       \t\t\t   123\t\t\t    \t\t\t\n";
        $expectedType            = QueryRewrite::SELECT;
        $expectedSelect          = "SELECT       \t\t\t   123";
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testSelectBug1() {
        $sql                     = "/* Script : /home/site/XXXXXXXXXX.php Utilisateur : prod_outils */ SELECT SQL_SMALL_RESULT DISTINCT ID_CLIENT
FROM DB.CLIENT CL
WHERE CL.TELEPHONE = '0000000000'
LIMIT 1
";
        $expectedType            = QueryRewrite::SELECT;
        $expectedSelect          = "SELECT SQL_SMALL_RESULT DISTINCT ID_CLIENT FROM DB.CLIENT CL WHERE CL.TELEPHONE = '0000000000' LIMIT 1";
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testSelectBug2() {
        $sql 		  	 		 = "SELECT id FROM ips WHERE ip='192.168.0.1' AND type=1 LIMIT 1";
        $expectedType 	 		 = QueryRewrite::SELECT;
        $expectedSelect  	     = "SELECT id FROM ips WHERE ip='192.168.0.1' AND type=1 LIMIT 1";
        $expectedExplain 		 = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType, 				$this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect, 			$this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain, 			$this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain, 	$this->_QueryRewrite->asExtendedExplain());
    }

    public function testSelectBug3() {
        $sql                     = "INSERT INTO tbl_old(
SELECT * 
FROM tbl
WHERE id =123 )";
        $expectedType            = QueryRewrite::INSERTSELECT;
        $expectedSelect          = "SELECT *  FROM tbl WHERE id =123";
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testConstructor() {
        $sql                     = "SELECT id FROM ips WHERE ip='192.168.0.1' AND type=1 LIMIT 1";
        $expectedType            = QueryRewrite::SELECT;
        $expectedSelect          = "SELECT id FROM ips WHERE ip='192.168.0.1' AND type=1 LIMIT 1";
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $QRW = new QueryRewrite($sql);
        $this->assertEquals($expectedType,              $QRW->getType());
        $this->assertEquals($expectedSelect,            $QRW->toSelect());
        $this->assertEquals($expectedExplain,           $QRW->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $QRW->asExtendedExplain());
        unset($QRW);
    }

    public function testDelete() {
        $sql                     = 'DELETE FROM table WHERE id = 123';
        $expectedType            = QueryRewrite::DELETE;
        $expectedSelect          = 'SELECT 0 FROM table WHERE id = 123';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType, 				$this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect, 			$this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain, 			$this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain, 	$this->_QueryRewrite->asExtendedExplain());
    }

    public function testInsert() {
        $sql                     = 'INSERT INTO table (col1, col2, col3) VALUES (1, 2, 3), (4, 5, 6)';
        $expectedType            = QueryRewrite::INSERT;
        $expectedSelect          = NULL;
        $expectedExplain         = NULL;
        $expectedExtendedExplain = NULL;
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType, 				$this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect, 			$this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain, 			$this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain, 	$this->_QueryRewrite->asExtendedExplain());
    }

    public function testInsertSelect() {
        $sql                     = 'INSERT INTO table SELECT * FROM table2';
        $expectedType            = QueryRewrite::INSERTSELECT;
        $expectedSelect          = 'SELECT * FROM table2';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testInsertSelect1() {
        $sql                     = 'INSERT INTO table (SELECT * FROM table2)';
        $expectedType            = QueryRewrite::INSERTSELECT;
        $expectedSelect          = 'SELECT * FROM table2';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testUpdate() {
        $sql                     = 'UPDATE table SET col1 = 123 WHERE id = 456';
        $expectedType            = QueryRewrite::UPDATE;
        $expectedSelect          = 'SELECT col1 = 123 FROM table WHERE id = 456';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testAlter() {
        $sql                     = 'ALTER TABLE MODIFY col1 INT NOT NULL DEFAULT 0';
        $expectedType            = QueryRewrite::ALTER;
        $expectedSelect          = NULL;
        $expectedExplain         = NULL;
        $expectedExtendedExplain = NULL;
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testDrop() {
        $sql                     = 'DROP TABLE table';
        $expectedType            = QueryRewrite::DROP;
        $expectedSelect          = NULL;
        $expectedExplain         = NULL;
        $expectedExtendedExplain = NULL;
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,  $this->_QueryRewrite->asExtendedExplain());
    }

    public function testCreate() {
        $sql                     = 'CREATE TABLE table (id INT)';
        $expectedType            = QueryRewrite::CREATE;
        $expectedSelect          = NULL;
        $expectedExplain         = NULL;
        $expectedExtendedExplain = NULL;
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testDeleteMulti() {
        $sql                     = 'DELETE table1 FROM table1 JOIN table2 ON table1.id = table2.id WHERE table1.col1 = 123 AND table2.col2 = 456 ';
        $expectedType            = QueryRewrite::DELETEMULTI;
        $expectedSelect          = 'SELECT 0 FROM table1 JOIN table2 ON table1.id = table2.id WHERE table1.col1 = 123 AND table2.col2 = 456';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }

    public function testUnion() {
        $sql                     = '(SELECT 123) UNION (SELECT 456)';
        $expectedType            = QueryRewrite::UNION;
        $expectedSelect          = '(SELECT 123) UNION (SELECT 456)';
        $expectedExplain         = "EXPLAIN $expectedSelect";
        $expectedExtendedExplain = "EXPLAIN EXTENDED $expectedSelect";
        $this->_QueryRewrite->setQuery($sql);
        $this->assertEquals($expectedType,              $this->_QueryRewrite->getType());
        $this->assertEquals($expectedSelect,            $this->_QueryRewrite->toSelect());
        $this->assertEquals($expectedExplain,           $this->_QueryRewrite->asExplain());
        $this->assertEquals($expectedExtendedExplain,   $this->_QueryRewrite->asExtendedExplain());
    }
}
