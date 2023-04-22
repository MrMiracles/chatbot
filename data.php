<?php

/*
 *  data {
 * 
 *      get_responses()
 *      
 *      filter_by_keyword(<string>, <bool>)
 *      search(<string>)
 * 
 *  }
 * 
 */

 include_once('config.php');
 include_once('keyword.php');
 include_once('response.php');


 class data {

    private object $mysql_connection;
    private array $responses = array();
    private array $keywords_any = array();
    private array $keywords_contains = array();
    private ?string $search_query = null;
    private bool $new_search = true;


    public function __construct() {
        $this->mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
		if ($this->mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $this->mysql_connection->connect_error, E_USER_ERROR); 
		if($this->mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$this->mysql_connection->error, E_USER_ERROR);
		if($this->mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$this->mysql_connection->error, E_USER_ERROR);
    }

    /**
     * returns array of responses found using the givin keywords
     *
     * @return mixed array(array(id, response)) or false on failure (no rounds not found)
     * 
     */
    public function get_responses() : array {
        if($this->new_search == false) return $this->responses; // return the previous responses if the keyword hasn't changed

        if(count($this->keywords_any) <= 0 && count($this->keywords_contains) <= 0) {
            trigger_error('No keywords set, can\'t return anything.', E_USER_WARNING);
            $this->keywords_any[] = '';
        }

        if(count($this->keywords_contains) == 0) {
            $sql = '
            SELECT DISTINCT r.id, r.response FROM
            responses AS r LEFT JOIN keyword_x_responses AS x ON r.id=x.response_id
            LEFT JOIN keywords AS k ON k.id=x.keyword_id
            WHERE k.keyword IN ('.rtrim(str_repeat('?, ', count($this->keywords_any)), ", ").')
            ORDER BY r.id';
        } elseif(count($this->keywords_contains) > 1) {
            $sql = '
            SELECT DISTINCT r.id, r.response FROM
            responses AS r LEFT JOIN keyword_x_responses AS x ON r.id=x.response_id
            LEFT JOIN keywords AS k ON k.id=x.keyword_id
            WHERE k.keyword IN ('.rtrim(str_repeat('?, ', count($this->keywords_any)), ", ").') 
            AND r.id in (
                SELECT x.response_id as rid
                FROM 
                	keyword_x_responses AS x LEFT JOIN keywords AS k ON x.keyword_id=k.id
                WHERE
                	k.keyword IN ('.rtrim(str_repeat('?, ', count($this->keywords_contains)), ", ").')
                GROUP BY
                	rid
                HAVING count(x.response_id) >= '.count($this->keywords_contains).'
                )
            ORDER BY r.id';
        } else {
            $sql = '
            SELECT DISTINCT r.id, r.response FROM
            responses AS r LEFT JOIN keyword_x_responses AS x ON r.id=x.response_id
            LEFT JOIN keywords AS k ON k.id=x.keyword_id
            WHERE k.keyword IN ('.rtrim(str_repeat('?, ', count($this->keywords_any)), ", ").') 
            AND k.keyword IN ('.rtrim(str_repeat('?, ', count($this->keywords_contains)), ", ").')
            ORDER BY r.id';
        }

        $parameters = array_merge($this->keywords_any, $this->keywords_contains); // combine both array's in one

        if($this->search_query !== null) { // some searchquery givin, lets use it!
            $sql = "SELECT resp.response FROM (".$sql.") AS resp WHERE resp.response LIKE ?";
            $parameters[] = $this->search_query; // add search query to parameters
        }

         echo $sql;

        $mysql_prepare = $this->mysql_connection->prepare($sql);
        $mysql_prepare->bind_param(str_repeat('s', count($parameters)), ...$parameters);
        $mysql_prepare->execute();
        $mysql_result = $mysql_prepare->get_result();
        if($mysql_result->num_rows == 0) return false; // return false if no rows are being fetched
		
		while ($row = $mysql_result->fetch_assoc()) {
			$this->responses[] = $row;
		}
        $this->new_search = false; // next time responses are requested just return them without quering the database
        return $this->responses;
    }

    /**
     * Filter the responses by given keyword.
     *
     * @param string $keyword
     * @param bool $contains_keyword=false If this is set to true then only responses with this keyword will be returned.
     * 
     * @return bool This always returns true because nothing is checked.
     * 
     */
    public function filter_by_keyword(string $keyword, bool $contains_keyword=false) : bool {
        if($contains_keyword == false) {
            $this->keywords_any[] = $keyword;
        } else {
            $this->keywords_any[] = $keyword;
            $this->keywords_contains[] = $keyword;
        }
        $this->new_search = true; // make sure a new database request is done.
        return true;
    }

    /**
     * Filter the responses by given query. Use % as a wildcard.
     * This function is case insensitive (if the database is also case-insensitive).
     * 
     * For example: 
     * - %t returns everything that end's with a t: 'respect', 'flat', etc. 
     * - %e% returns everyting with an e in it: 'beer', 'camel', etc.
     * - m% returns everything that starts with a m: 'monkey', 'more', etc.
     * Responses are first filtert by keyword then by query.
     *
     * @param string $query
     * 
     * @return bool Returns always true, no checks are made.
     * 
     */
    public function search(string $query) : bool {
        $this->search_query = $query;
        return true;
    }




 }




?>