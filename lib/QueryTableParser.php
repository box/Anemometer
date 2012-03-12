<?php

/**
 * class QueryTableParser
 * 
 * Very rough class to extract table names from a SQL query.
 * 
 * This class simply looks for specific tokens like FROM, JOIN, UPDATE, INTO
 * and collects a list of the very next token after those words.
 * 
 * It doesn't attempt to parse aliases, or any other query structure.
 * 
 * This probably doesn't handle table names with a space in it like `table name`
 * 
 * @package QueryTableParser
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-01-01
 * @license Please contact the author for licensing
 * 
 * @todo handle table names with spaces wrapped in backticks or quotes
 * @todo stop parsing early if possible -- after the JOIN clause (if any)
 * @todo ignore token values inside string literals or backticks
 */
class QueryTableParser {

    public $pos;
    public $query;
    public $len;
    public $table_tokens = array(
        'from',
        'join',
        'update',
        'into',
    );

    /**
     * parse a query and return an array of table names from it.
     * 
     * @param string $query     the sql query
     * @return array    the list of table names. 
     */
    public function parse($query) {
        $this->query = preg_replace("/\s+/s", " ", $query);
        $this->pos = 0;
        $this->len = strlen($this->query);
        //print "<pre>";
        //print "parsing {$this->query}; length {$this->len}\n";


        $tables = array();
        while ($this->has_next_token()) {
            $token = $this->get_next_token();
            //print "--> found $token\n";

            if (in_array(strtolower($token), $this->table_tokens)) {

                $table = $this->get_next_token();

                if (preg_match("/\w+/", $table)) {
                    $table = str_replace('`', '', $table);
                    $tables[$table]++;
                }
            }
        }
        //print "</pre>";

        return array_keys($tables);
    }

    /**
     * return true if we're not at the end of the string yet.
     * @return boolean true if there are more tokens to read
     */
    private function has_next_token() {
        // at end 
        if ($this->pos >= $this->len) {
            return false;
        }
        return true;
    }

    /**
     * returns the next whitespace separated string of characters
     * @return string   the token value 
     */
    private function get_next_token() {
        // get the pos of the next token boundary
        $pos = strpos($this->query, " ", $this->pos);
        //print "get next token {$this->pos} {$this->len} {$pos}\n";
        if ($pos === false) {
            $pos = $this->len;
        }

        // found next boundary
        $start = $this->pos;
        $len = $pos - $start;
        $this->pos = $pos + 1;
        return substr($this->query, $start, $len);
    }

}

?>