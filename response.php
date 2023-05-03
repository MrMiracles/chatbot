<?php

/**
 * response {
 * 
 *      set_response(<string>) : bool
 * 
 *      bind_response_to_keyword(<int>) : bool
 *      remove_bind_to_keyword(<int>) : bool
 *      
 *      get() : array
 *      get_id() : int
 *      get_response() : string
 *      get_keywords() : array : bool
 * 
 *      get_response_by_id(<int>) : bool
 *     
 *      save() : bool
 *      delete() : bool
 * 
 * }
 * 
**/

include_once('config.php');

if(!defined('MIN_RESPONSE_LENGTH')) trigger_error('Constant MIN_KEYWORD_LENGTH not set', E_USER_ERROR);

class response {

    private object $mysql_connection;
    private ?int $id = null;
    private ?string $response = null;
    private array $keyword_binds = array();
    private array $keyword_binds_remove = array();
    
    public function __construct() {
        $this->mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
		if ($this->mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $this->mysql_connection->connect_error, E_USER_ERROR); 
		if($this->mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$this->mysql_connection->error, E_USER_ERROR);
        if($this->mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$this->mysql_connection->error, E_USER_ERROR);
    }
        
    /**
     * Sets an response.
     * Returns always true because no futher checks er necesarry. Use save() to save the response to the database.
     *
     * @param string $response Response text.
     * 
     * @return bool Returns true;
     *              Use save() to save the response to the database.
     * 
     */
    public function set_response(string $response) : bool {
        $this->response = $response;
        return true;
    }

    /**
     * Binds the response to a keyword, use save() to save the connection to the database.
     *
     * @param int $keyword_id
     * 
     * @return bool Return true if keyword exists, false if not.
     * 
     */
    public function bind_response_to_keyword(int $keyword_id) : bool {
        // check if response exists
        $mysql_prepare = $this->mysql_connection->prepare('SELECT id FROM keywords WHERE id=? LIMIT 1');
        $mysql_prepare->bind_param('i', $keyword_id);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        if($mysql_prepare->num_rows() <= 0 ) return false; // return false if keyword is not found

        $this->keyword_binds[] = $keyword_id;
        return true;
    }

    /**
     * Removes a bind from the response to a keyword, use save() to save the removed connection to the database.
     *
     * @param int $keyword_id
     * 
     * @return bool Returns always true, no checks are made.
     * 
     */
    public function remove_bind_to_keyword(int $keyword_id) : bool {
        $this->keyword_binds_remove[] = $keyword_id;        
        return true;
    }

    /**
     * Returns id
     *
     * @return integer Returns the id of the response or null if not defined
     * 
     */
    public function get_id() : int {
        return $this->id;
    }

    /**
     * Returns keyword name
     *
     * @return string Returns the response as string or null if not defined
     * 
     */
    public function get_response() : string {
        return $this->response;
    }

    /**
     * Returns the keywords bound to this response
     *
     * @return array(id, keyword) or Boolean false when no keywords are found
     * 
     */
    public function get_keywords() : mixed {
        if($this->id === null) return false; // return false if there is no response id

        $mysql_prepare = $this->mysql_connection->prepare('SELECT k.id, k.keyword FROM keywords AS k LEFT JOIN keyword_x_responses AS x ON x.keyword_id = k.id WHERE x.response_id=?');
        $mysql_prepare->bind_param('i', $this->id);
        $mysql_prepare->execute();
        $mysql_result = $mysql_prepare->get_result();
        if($mysql_result->num_rows <= 0 ) return false; // return false if no keywords are found
        $keywords = array();
        while ($row = $mysql_result->fetch_assoc()) {
            $keywords[] = array('id' => $row['id'], 'keyword' => $row['keyword']);
        }
        return $keywords;
    }

    /**
     * Returns id and response as an array 
     *
     * @return array(int id, string response)
     * 
     */
    public function get() : array {
        return array('id'=>$this->id, 'response'=>$this->response);
    }
    
    /**
     * Get the response by an id
     * saves id and response in private $id and $response when found.
     *
     * @param int $id
     * 
     * @return bool returns true when succesful, false if response is not found.
     * 
     */
    public function get_response_by_id(int $id) : bool {
        $mysql_prepare = $this->mysql_connection->prepare('SELECT id, response FROM responses WHERE id=? LIMIT 1');
        $mysql_prepare->bind_param('i', $id);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        if($mysql_prepare->num_rows() <= 0 ) return false; // return false if response is not found
        $mysql_prepare->bind_result($this->id, $this->response);
        $mysql_prepare->fetch();
        return true;
    }
    
    /**
     * Saves the response to the database.
     * Inserts a new row when no ID is set (for new responses), updates a response when an ID is set.
     *
     * @return bool true on succes, false if response is shorter then set in MIN_RESPONSE_LENGTH.
     * 
     */
    public function save() : bool {
        if($this->id === null) { // new response, insert into database
            if(strlen($this->response) < MIN_RESPONSE_LENGTH) return false; // check if length of the response is long enough, return false if not
            $mysql_prepare = $this->mysql_connection->prepare('INSERT INTO responses (response) VALUES (?)');
            $mysql_prepare->bind_param('s', $this->response);
            $mysql_prepare->execute();
            $this->id = $this->mysql_connection->insert_id;
        } else { // existing response, update
            if(strlen($this->response) < MIN_RESPONSE_LENGTH) return false; // check if length of the response is long enough, return false if not
            $mysql_prepare = $this->mysql_connection->prepare('UPDATE responses SET response=? WHERE id=?');
            $mysql_prepare->bind_param('si', $this->response, $this->id);
            $mysql_prepare->execute();
        }

        if(count($this->keyword_binds) > 0) { // make some connections between this response and a keyword(s)
            $sql = 'INSERT INTO keyword_x_responses (keyword_id, response_id) VALUES '.rtrim(str_repeat('(?,?), ', count($this->keyword_binds)), ", ").' ON DUPLICATE KEY UPDATE keyword_id=keyword_id';
            $mysql_prepare = $this->mysql_connection->prepare($sql);
            $connections = array();
            foreach($this->keyword_binds as $keyword_id) {
                $connections[] = $keyword_id;
                $connections[] = $this->id;
            }
            
            $mysql_prepare->bind_param(str_repeat('i', count($connections)), ...$connections);
            $mysql_prepare->execute();
        }
        
        if(count($this->keyword_binds_remove) > 0) { // Remove some connections between this response and a keyword(s)
            $sql = 'DELETE FROM keyword_x_responses WHERE '.rtrim(str_repeat('(keyword_id=? AND response_id=?) OR ', count($this->keyword_binds_remove)), " OR ").'';
            $mysql_prepare = $this->mysql_connection->prepare($sql);
            $connections = array();
            foreach($this->keyword_binds_remove as $keyword_id) {
                $connections[] = $keyword_id;
                $connections[] = $this->id;
            }

            $mysql_prepare->bind_param(str_repeat('i', count($connections)), ...$connections);
            $mysql_prepare->execute();
        }

        return true;
    }

    /**
     * Deletes a response from the database
     *
     * @return bool Returns true on succes, false if no ID is given.
     *              Make sure the ID is set using function get().
     * 
     */
    public function delete() : bool {
        if($this->id === null) return false; // no ID set, return false
        $mysql_prepare = $this->mysql_connection->prepare('DELETE FROM responses WHERE id=?');
        $mysql_prepare->bind_param('i', $this->id);
        $mysql_prepare->execute();
        return true;

        // also delete all the connections with keywords
        $mysql_prepare = $this->mysql_connection->prepare('DELETE FROM keyword_x_responses WHERE response_id=?');
        $mysql_prepare->bind_param('i', $this->id);
        $mysql_prepare->execute();
        return true;
    }



}

?>