<?php

/**
 * keyword {
 * 
 *      set_keyword(<string>) : bool
 * 
 *      bind_keyword_to_response(<int>) : bool
 *      remove_bind_to_response(<int>) : bool
 * 
 *      get(): array
 *      get_id() : int
 *      get_keyword() : string
 * 
 *      get_keyword_by_id(<string>) : bool
 *      get_keyword_by_name(<string>) : bool
 * 
 *      save() : bool
 *      delete() : bool
 * 
 * }
 * 
**/

include_once('config.php');

if(!defined('MIN_KEYWORD_LENGTH')) trigger_error('Constant MIN_KEYWORD_LENGTH not set', E_USER_ERROR);

class keyword {

    private object $mysql_connection;
    private ?int $id = null;
    private ?string $keyword = null;
    private array $response_binds = array();
    private array $response_binds_remove = array();

    public function __construct() {
        $this->mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
		if ($this->mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $this->mysql_connection->connect_error, E_USER_ERROR); 
		if($this->mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$this->mysql_connection->error, E_USER_ERROR);
        if($this->mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$this->mysql_connection->error, E_USER_ERROR);
    }
        
    /**
     * Sets the keyword.
     * Checks for max length of 50, returning false if > 50.
     * Returns true if name is set. Use save() to save the keyword to the database.
     *
     * @param string $keyword keyword with max length of 50.
     * 
     * @return bool Returns true if name is set, returning false if > 50. 
     *              Use save() to save the keyword to the database.
     * 
     */
    public function set_keyword(string $keyword) : bool {
        if(strlen($keyword) > 50) return false;
        $this->keyword = $keyword;

        return true;
    }

    /**
     * Binds the keyword to a response, use save() to save the connection to the database.
     *
     * @param int $response_id
     * 
     * @return bool Return true if response exists, false if not.
     * 
     */
    public function bind_keyword_to_response(int $response_id) : bool {
        // check if response exists
        $mysql_prepare = $this->mysql_connection->prepare('SELECT id FROM responses WHERE id=? LIMIT 1');
        $mysql_prepare->bind_param('i', $response_id);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        if($mysql_prepare->num_rows() <= 0 ) return false; // return false if response is not found

        $this->response_binds[] = $response_id;
        return true;
    }

    /**
     * Removes a bind from the keyword to a response, use save() to save the removed connection to the database.
     *
     * @param int $response_id
     * 
     * @return bool Returns always true, no checks are made.
     * 
     */
    public function remove_bind_to_response(int $response_id) : bool {
        $this->response_binds_remove[] = $response_id;        
        return true;
    }

    /**
     * Returns id
     *
     * @return integer Returns the id of the keyword or null if not defined
     * 
     */
    public function get_id() : int {
        return $this->id;
    }

    /**
     * Returns keyword name
     *
     * @return string Returns the keyword as string or null if not defined
     * 
     */
    public function get_keyword() : string {
        return $this->keyword;
    }

    /**
     * Returns id and keyword as an array 
     *
     * @return array(int id, string keyword)
     * 
     */
    public function get() : array {
        return array('id'=>$this->id, 'keyword'=>$this->keyword);
    }
    
    /**
     * Get the keyword by an id.
     * Saves id and keyword in private $id and $keyword when found.
     *
     * @param int $id
     * 
     * @return bool Returns true when succesful, false if keyword is not found.
     * 
     */
    public function get_keyword_by_id(int $id) : bool {
        $mysql_prepare = $this->mysql_connection->prepare('SELECT id, keyword FROM keywords WHERE id=? LIMIT 1');
        $mysql_prepare->bind_param('i', $id);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        if($mysql_prepare->num_rows() <= 0 ) return false; // return false if keyword is not found
        $mysql_prepare->bind_result($this->id, $this->keyword);
        $mysql_prepare->fetch();
        return true;
    }

    /**
     * Get the keyword by searching for a name.
     * Saves id and keyword in private $id and $keyword when found.
     *
     * @param string $name Keyword with max length of 50.
     * 
     * @return bool Returns true when succesful, false if keyword is not found.
     * 
     */
    public function get_keyword_by_name(string $name) : bool {
        $mysql_prepare = $this->mysql_connection->prepare('SELECT id, keyword FROM keywords WHERE keyword=? LIMIT 1');
        $mysql_prepare->bind_param('s', $name);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        if($mysql_prepare->num_rows() <= 0 ) return false; // return false if keyword is not found
        $mysql_prepare->bind_result($this->id, $this->keyword);
        $mysql_prepare->fetch();
        return true;
    }
    
    /**
     * Saves the keyword to the database.
     * Inserts a new row when no ID is set (for new keywords), updates a keyword when an ID is set.
     *
     * @return bool Returns true on succes, false if keyword is shorter then set in MIN_KEYWORD_LENGTH or longer then 50 characters.
     *              Will return false in case the keyword already exists in the database, user get_keyword_by_name() to find it.
     * 
     */
    public function save() : bool {
        if($this->id === null) { // new keyword, insert into database
            if(strlen($this->keyword) < MIN_KEYWORD_LENGTH) return false; // check if length of the keyword is long enough, return false if not
            if(strlen($this->keyword) > 50) return false; // check if length of the keyword is too long, return false if that is the case
            $mysql_prepare = $this->mysql_connection->prepare('INSERT INTO keywords (keyword) VALUES (?) ON DUPLICATE KEY UPDATE keyword=keyword');
            $mysql_prepare->bind_param('s', $this->keyword);
            $mysql_prepare->execute();
            if($mysql_prepare->affected_rows == 0) { // no rows changed (probably because keyword allready exists)
                return false;
            } else {
                $this->id = $this->mysql_connection->insert_id;
            }
        } else { // existing keyword, update
            if(strlen($this->keyword) < MIN_KEYWORD_LENGTH) return false; // check if length of the keyword is long enough, return false if not
            if(strlen($this->keyword) > 50) return false; // check if length of the keyword is too long, return false if that is the case
            $mysql_prepare = $this->mysql_connection->prepare('UPDATE keywords SET keyword=? WHERE id=?');
            $mysql_prepare->bind_param('si', $this->keyword, $this->id);
            $mysql_prepare->execute();    
        }

        if(count($this->response_binds) > 0) { // make some connections between this keyword and a response(s)
            $sql = 'INSERT INTO keyword_x_responses (keyword_id, response_id) VALUES '.rtrim(str_repeat('(?,?), ', count($this->response_binds)), ", ").' ON DUPLICATE KEY UPDATE keyword_id=keyword_id';
            $mysql_prepare = $this->mysql_connection->prepare($sql);
            $connections = array();
            foreach($this->response_binds as $response_id) {
                $connections[] = $this->id;
                $connections[] = $response_id;
            }
            
            $mysql_prepare->bind_param(str_repeat('i', count($connections)), ...$connections);
            $mysql_prepare->execute();
        }
        
        if(count($this->response_binds_remove) > 0) { // Remove some connections between this keywoord and a response(s)
            $sql = 'DELETE FROM keyword_x_responses WHERE '.rtrim(str_repeat('(keyword_id=? AND response_id=?) OR ', count($this->response_binds_remove)), " OR ").'';
            $mysql_prepare = $this->mysql_connection->prepare($sql);
            $connections = array();
            foreach($this->response_binds_remove as $response_id) {
                $connections[] = $this->id;
                $connections[] = $response_id;
            }
            
            $mysql_prepare->bind_param(str_repeat('i', count($connections)), ...$connections);
            $mysql_prepare->execute();
        }

        return true;
    }

    /**
     * Deletes a keyword from the database.
     *
     * @return bool Returns true on succes, false if no ID is given.
     *              Make sure the ID is set using functions get() or get_keyword_by_name()
     * 
     */
    public function delete() : bool {
        if($this->id === null) return false; // no ID set, return false
        $mysql_prepare = $this->mysql_connection->prepare('DELETE FROM keywords WHERE id=?');
        $mysql_prepare->bind_param('i', $this->id);
        $mysql_prepare->execute();
        return true;
    }



}

?>