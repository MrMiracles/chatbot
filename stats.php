<?php
/**
 * stats {
 * 
 *  start_new_session()
 *  set_session(int)
 *  get_session() : int
 * 
 *  keyword_hit(int) : bool
 *  response_hit(int) : bool
 * 
 *  response_like(int) : bool
 *  response_dislike(int) : bool
 * 
 *  get_liked_responses() : array|null
 *  get_disliked_responses() : array|null
 *  get_keywords_used_by_response() : array|null
 * 
 *  save() : bool
 * 
 * }
 * 
**/

include_once('config.php');
include_once('keyword.php');
include_once('response.php');

class stats {

    private object $mysql_connection;
    private ?int $session_id;
    private int $last_group_id;
    private array $keywords_hit;
    private array $responses_hit;
    private array $responses_like;
    private array $responses_dislike;


    public function __construct() {
        $this->mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
		if ($this->mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $this->mysql_connection->connect_error, E_USER_ERROR); 
		if($this->mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$this->mysql_connection->error, E_USER_ERROR);
        if($this->mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$this->mysql_connection->error, E_USER_ERROR);
        $this->session_id = null;
    }

    /**
     * Starts a new session, only use this when starting a new conversation.
     * Make sure you set the session id using set_session() when the conversation continues. 
     *
     * @return int session id.
     * 
     */
    public function start_new_session() : int {
        $mysql_prepare = $this->mysql_connection->prepare('SELECT MAX(session_id)+1 FROM stats');
        $mysql_prepare->execute();
        $mysql_prepare->store_result();
        $mysql_prepare->bind_result($this->session_id);
        $mysql_prepare->fetch();

        if($this->session_id == null) { // there are no stats yet, this will only happen once!
            $this->session_id = 1;
        }
        $this->last_group_id = 0; // also set the conversation group id to zero (no group yet)
        return $this->session_id;
    }

    /**
     * Sets the current session, use this to continue a conversation.
     * Can be set to any session id recieved from either get_session() or start_new_session()
     *
     * @param int $session_id
     * 
     * @return bool true on succes, false if session is not found.
     * 
     */
    public function set_session(int $session_id) : bool {
        $mysql_prepare = $this->mysql_connection->prepare('SELECT MAX(group_id) FROM stats WHERE session_id = ?');
        $mysql_prepare->bind_param('i', $session_id);
        $mysql_prepare->execute();
        $mysql_prepare->store_result();

        $last_group_id = null;
        $mysql_prepare->bind_result($last_group_id);
        $mysql_prepare->fetch();
        if($last_group_id == null) { // session not found
            return false;
        }
        $this->last_group_id = $last_group_id;
        $this->session_id = $session_id;
        return true;
    }

    /**
     * Returns the current session id or null if not set (user start_new_session() to get a new session id or use set_session() to continue an previous session)
     *
     * @return int session id or null
     * 
     */
    public function get_session() : int {
        return $this->session_id;
    }
      
    /**
     * Set a hit for a keyword (keyword is used +1 time).
     * Make sure to use save() to acutally save the hit to the database.
     *
     * @param int $keyword_id
     * 
     * @return bool returns always true, no checks made.
     * 
     */
    public function keyword_hit(int $keyword_id) : bool {
        $this->keywords_hit[] = $keyword_id;
        return true;
    }

    /**
     * Set a hit for a response (response is used +1 time).
     * Make sure to use save() to acutally save the hit to the database.
     *
     * @param int $response_id
     * 
     * @return bool returns always true, no checks made.
     * 
     */
    public function response_hit(int $response_id) : bool {
        $this->responses_hit[] = $response_id;
        return true;
    }

    /**
     * Set a like for a response (response is liked by the user)
     * Make sure to use save() to acutally save the like to the database.
     * if the same response is found multiple times within the same conversation only the last one receives a like.
     *
     * @param int $response_id
     * 
     * @return bool returns always true, no checks made.
     * 
     */
    public function response_like(int $response_id) : bool {
        $this->responses_like[] = $response_id;
        return true;
    }

    /**
     * Set a dislike for a response (response is not liked by the user)
     * Make sure to use save() to acutally save the dislike to the database.
     * if the same response is found multiple times within the same conversation only the last one receives a dislike.
     *
     * @param int $response_id
     * 
     * @return bool returns always true, no checks made.
     * 
     */
    public function response_dislike(int $response_id) : bool {
        $this->responses_dislike[] = $response_id;
        return true;
    }

    /**
     * Returns array of 'response' objects with all responses which were liked during set session
     *
     * @return array|null array(response, ...) or null if no responses were found
     * 
     */
    public function get_liked_responses() : ?array {
        if($this->session_id === null) trigger_error('Session ID not set, first start a new session en set the session ID using set_session()', E_USER_ERROR);
        $mysql_prepare = $this->mysql_connection->prepare('SELECT type_id FROM stats WHERE type=\'response\' AND score>0 AND session_id=?');
        $mysql_prepare->bind_param('i', $this->session_id);
        $mysql_prepare->execute();
        $mysql_result = $mysql_prepare->get_result();
        $responses = array();
        if($mysql_result->num_rows == 0) return null; // return null if no liked responses are found
		
		while ($row = $mysql_result->fetch_assoc()) {
            $response = new response();
            if($response->get_response_by_id($row['type_id'])) {
                $responses[] = $response;
            }
		}
        return $responses;
    }

    /**
     * Returns array of 'response' objects with all responses which were disliked during set session
     *
     * @return array|null array(response, ...) or null if no responses were found
     * 
     */
    public function get_disliked_responses() : ?array {
        if($this->session_id === null) trigger_error('Session ID not set, first start a new session en set the session ID using set_session()', E_USER_ERROR);
        $mysql_prepare = $this->mysql_connection->prepare('SELECT type_id FROM stats WHERE type=\'response\' AND score<0 AND session_id=?');
        $mysql_prepare->bind_param('i', $this->session_id);
        $mysql_prepare->execute();
        $mysql_result = $mysql_prepare->get_result();
        $responses = array();
        if($mysql_result->num_rows == 0) return null; // return null if no liked responses are found
		
		while ($row = $mysql_result->fetch_assoc()) {
            $response = new response();
            if($response->get_response_by_id($row['type_id'])) {
                $responses[] = $response;
            }
		}
        return $responses;
    }

    /**
     * Returns an array of 'keyword' objects with all keywords which were hit right before the liked response.
     * (so the response was given because of those keywords)
     *
     * @param int $response_id
     * 
     * @return array|null return array(keyword, ...) or null of no keywords or no liked response was found
     * 
     */
    public function get_keywords_used_by_response(int $response_id) : ?array {
        if($this->session_id === null) trigger_error('Session ID not set, first start a new session en set the session ID using set_session()', E_USER_ERROR);
        $mysql_prepare = $this->mysql_connection->prepare('SELECT type_id, group_id FROM `stats` WHERE type=\'keyword\' AND session_id=? AND group_id IN (SELECT group_id FROM stats WHERE type=\'response\' AND session_id=? AND type_id=?)');
        $mysql_prepare->bind_param('iii', $this->session_id, $this->session_id, $response_id);
        $mysql_prepare->execute();
        $mysql_result = $mysql_prepare->get_result();
        $keywords = array();
        if($mysql_result->num_rows == 0) return null; // return null if no keywords or responses are found
		
		while ($row = $mysql_result->fetch_assoc()) {
            $keyword = new keyword();
            if($keyword->get_keyword_by_id($row['type_id'])) {
                $keywords[] = $keyword;
            }
		}
        return $keywords;
    }

    /**
     * Saves all the hits, likes and dislikes to the database.
     *
     * @return bool
     * 
     */
    public function save() : bool {
        if($this->session_id === null) trigger_error('Session ID not set, first start a new session en set the session ID using set_session()', E_USER_ERROR);
        $group_id = $this->last_group_id+1; // set new group id
        
        if(!empty($this->keywords_hit)) { // saved keywords hits
            $keywords = array();
            foreach($this->keywords_hit as $keyword_id) {
                $keywords[] = $this->session_id;
                $keywords[] = $group_id;
                $keywords[] = 'keyword';
                $keywords[] = $keyword_id;
            }
            $mysql_prepare = $this->mysql_connection->prepare('INSERT INTO stats (session_id, group_id, type, type_id) VALUES '.rtrim(str_repeat('(?,?,?,?), ', (count($keywords)/4)), ", "));
            $mysql_prepare->bind_param(str_repeat('iisi', (count($keywords)/4)), ...$keywords);
            $mysql_prepare->execute();
        }

        if(!empty($this->responses_hit)) { // save responses hits
            $responses = array();
            
            foreach($this->responses_hit as $response_id) {
                $responses[] = $this->session_id;
                $responses[] = $group_id;
                $responses[] = 'response';
                $responses[] = $response_id;
            }

            $mysql_prepare = $this->mysql_connection->prepare('INSERT INTO stats (session_id, group_id, type, type_id) VALUES '.rtrim(str_repeat('(?,?,?,?), ', (count($responses)/4)), ", "));
            $mysql_prepare->bind_param(str_repeat('iisi', (count($responses)/4)), ...$responses);
            $mysql_prepare->execute();
        }

        if(!empty($this->responses_like)) { // save responses likes  
            $mysql_prepare = $this->mysql_connection->prepare('UPDATE stats SET score=1 WHERE session_id=? AND type_id=? ORDER BY id DESC LIMIT 1');         
            foreach($this->responses_like as $response_id) {
                $mysql_prepare->bind_param('ii', $this->session_id, $response_id);
                $mysql_prepare->execute();
            }
        }

        if(!empty($this->responses_dislike)) { // save responses dislikes
            $mysql_prepare = $this->mysql_connection->prepare('UPDATE stats SET score=-1 WHERE session_id=? AND type_id=? ORDER BY id DESC LIMIT 1');
            foreach($this->responses_dislike as $response_id) {
                $mysql_prepare->bind_param('ii', $this->session_id, $response_id);
                $mysql_prepare->execute();
            }
        }

        return true;
    }

}

?>