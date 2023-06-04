<?php
include_once('../../config.php');
include_once('../../stats.php');
session_start();

/**
 * 
 * Controleer of gebruiker is ingelogd
 * 
 */

if($_SESSION['login'] != true) { // gebruiker is niet ingelogd, retourneer error.
    $return = array(
        'login' => false,
        'succes' => false,
        'msg' => 'Gebruiker is niet ingelogd.');

    echo json_encode($return); // return JSON data
    exit; // stop script
}

// get the input json
$input = file_get_contents('php://input');
$jsonInput = (!empty($input)) ? json_decode($input) : new stdClass(); // decode JSON or create an empty object
if($jsonInput === null) trigger_error('Wrong JSON formatting, couldnt decode.', E_USER_ERROR); // error in the JSON formatting, die.

/**
 * 
 * get different kind of stats
 * 
 */

if(!isset($jsonInput->type)) {   // no stats type given
    
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen type statistieken gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

// create MSQL connection
$mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
if($mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $mysql_connection->connect_error, E_USER_ERROR); 
if($mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$mysql_connection->error, E_USER_ERROR);
if($mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$mysql_connection->error, E_USER_ERROR);


switch($jsonInput->type) {
    case 'keyword_hits':
        $keywords = get_keyword_hits($mysql_connection, ((isset($jsonInput->limit) ? intval($jsonInput->limit) : 1000)), ((isset($jsonInput->dateStart) ? $jsonInput->dateStart : null)), ((isset($jsonInput->dateEnd) ? $jsonInput->dateEnd : null)));
        if($keywords != null) {
            $return = array(
                'login' => true,
                'succes' => true,
                'keywords' => $keywords);
        } else {
            $return = array(
                'login' => true,
                'succes' => false,
                'msg' => 'Geen keyworden gevonden.');
        }
    break;
    case 'response_hits':
        $responses = get_response_hits($mysql_connection, ((isset($jsonInput->limit) ? intval($jsonInput->limit) : 1000)), ((isset($jsonInput->dateStart) ? $jsonInput->dateStart : null)), ((isset($jsonInput->dateEnd) ? $jsonInput->dateEnd : null)));
        if($responses != null) {
            $return = array(
                'login' => true,
                'succes' => true,
                'responses' => $responses);
        } else {
            $return = array(
                'login' => true,
                'succes' => false,
                'msg' => 'Geen antwoorden gevonden.');
        }
    break;
    case 'get_keywords_by_response':
        if(isset($jsonInput->id)) {
            $keywords = get_keywords_used_by_response($mysql_connection, $jsonInput->id);
            $keywordsLike = get_keywords_used_by_liked_response($mysql_connection, $jsonInput->id);
            $keywordsDislike = get_keywords_used_by_disliked_response($mysql_connection, $jsonInput->id);
            if($keywords != null) {
                $return = array(
                    'login' => true,
                    'succes' => true,
                    'keywords' => $keywords,
                    'likedKeywords' => $keywordsLike,
                    'dislikedKeywords' => $keywordsDislike);
            } else {
                $return = array(
                    'login' => true,
                    'succes' => false,
                    'msg' => 'Geen keywoorden gevonden.');
            }
        } else {
            $return = array(
                'login' => true,
                'succes' => false,
                'msg' => 'Geen response ID gegeven');
        }
    break;
    default:
        $return = array(
            'login' => true,
            'succes' => false,
            'msg' => 'Onjuist type statistieken opgevraagd.');
    }

echo json_encode($return); // return JSON data




/**
 * 
 * functies voor opvragen van statistieken
 * 
 * 
 * 
 */



function get_keyword_hits(&$mysql_connection, $limit=1000, $date_start=null, $date_end=null) : ?array {

    if($date_start !== null) {
        // selectie op datum (datum als yyyy-mm-dd)
        $sql = 'SELECT type_id as keyword_id, COUNT(type_id) AS count, keywords.keyword as keyword FROM stats LEFT JOIN keywords ON keywords.id = stats.type_id WHERE TYPE = \'keyword\' AND (timestamp > TIMESTAMP(?) and timestamp < '.(($date_end !== null) ? 'TIMESTAMP(?)' : 'NOW()').') GROUP BY type_id ORDER BY count DESC LIMIT ?';
        
        if($date_end !== null) {
            $parameters = [$date_start, $date_end, $limit];
        } else {
            $parameters = [$date_start, $limit];
        }
    } else {
        $sql = 'SELECT type_id as keyword_id, COUNT(type_id) AS count, keywords.keyword as keyword FROM stats LEFT JOIN keywords ON keywords.id = stats.type_id WHERE TYPE = \'keyword\' GROUP BY type_id ORDER BY count DESC LIMIT ?';
        $parameters = [$limit];
    }


    $mysql_prepare = $mysql_connection->prepare($sql);
    if(count($parameters) == 1) {
        $mysql_prepare->bind_param('i', ...$parameters);
    } else {
        $mysql_prepare->bind_param(str_repeat('s', count($parameters)-1).'i', ...$parameters);
    }

    $mysql_prepare->execute();
    $mysql_result = $mysql_prepare->get_result();
    $keywords = array();
    if($mysql_result->num_rows == 0) return null; // return null if no keywords are found
    
    while ($row = $mysql_result->fetch_assoc()) {
        $keywords[] = array('id'=>$row['keyword_id'], 'count'=>$row['count'], 'keyword'=>(($row['keyword']===null) ? 'Verwijderd keywoord' : $row['keyword']));
    }
    return $keywords;
}


function get_response_hits(&$mysql_connection, $limit=1000, $date_start=null, $date_end=null) : ?array {

    if($date_start !== null) {
        // selectie op datum (datum als yyyy-mm-dd)
        $sql = 'SELECT type_id AS response_id, COUNT(type_id) AS count, responses.response AS response FROM stats LEFT JOIN responses ON responses.id = stats.type_id WHERE type = \'response\' AND (timestamp > TIMESTAMP(?) and timestamp < '.(($date_end !== null) ? 'TIMESTAMP(?)' : 'NOW()').') GROUP BY type_id ORDER BY count DESC LIMIT ?';
        
        if($date_end !== null) {
            $parameters = [$date_start, $date_end, $limit];
        } else {
            $parameters = [$date_start, $limit];
        }
    } else {
        $sql = 'SELECT type_id AS response_id, COUNT(type_id) AS count, responses.response AS response FROM stats LEFT JOIN responses ON responses.id = stats.type_id WHERE type = \'response\' GROUP BY type_id ORDER BY count DESC LIMIT ?';
        $parameters = [$limit];
    }

    $mysql_prepare = $mysql_connection->prepare($sql);
    if(count($parameters) == 1) {
        $mysql_prepare->bind_param('i', ...$parameters);
    } else {
        $mysql_prepare->bind_param(str_repeat('s', count($parameters)-1).'i', ...$parameters);
    }
    
    $mysql_prepare->execute();
    $mysql_result = $mysql_prepare->get_result();
    $responses = array();
    if($mysql_result->num_rows == 0) return null; // return null if no responses are found
    
    while ($row = $mysql_result->fetch_assoc()) {
        $responses[] = array('id'=>$row['response_id'], 'count'=>$row['count'], 'response'=>(($row['response']===null) ? 'Verwijderd antwoord' : $row['response']));
    }
    return $responses;
}

function get_keywords_used_by_response(&$mysql_connection, $id) : ?array {
    $sql = 'SELECT
                type_id AS keyword_id, 
                count(type_id) AS count
            FROM
                stats as s
            RIGHT JOIN (
                SELECT
                    group_id, session_id
                FROM
                    stats
                WHERE TYPE = \'response\' AND type_id=?) AS x ON x.group_id = s.group_id AND x.session_id = s.session_id
            WHERE TYPE
                = \'keyword\'
            GROUP BY type_id
            ORDER BY count DESC';
    $mysql_prepare = $mysql_connection->prepare($sql);
    $mysql_prepare->bind_param('i', $id);
    $mysql_prepare->execute();
    $mysql_result = $mysql_prepare->get_result();
    $keywords = array();
    if($mysql_result->num_rows == 0) return null; // return null if no keywords are found
    
    while ($row = $mysql_result->fetch_assoc()) {
        $keyword = new keyword();
        $keyword->get_keyword_by_id($row['keyword_id']);
        $keywords[] = array('id'=>$row['keyword_id'], 'count'=>$row['count'], 'keyword'=>$keyword->get_keyword());
    }
    return $keywords;
}


function get_keywords_used_by_liked_response(&$mysql_connection, $id) : ?array {
    $sql = 'SELECT
                type_id AS keyword_id, 
                count(type_id) AS count
            FROM
                stats as s
            RIGHT JOIN (
                SELECT
                    group_id, session_id
                FROM
                    stats
                WHERE TYPE = \'response\' AND score=1 AND type_id=?) AS x ON x.group_id = s.group_id AND x.session_id = s.session_id
            WHERE TYPE
                = \'keyword\'
            GROUP BY type_id
            ORDER BY count DESC';
    $mysql_prepare = $mysql_connection->prepare($sql);
    $mysql_prepare->bind_param('i', $id);
    $mysql_prepare->execute();
    $mysql_result = $mysql_prepare->get_result();
    $keywords = array();
    if($mysql_result->num_rows == 0) return null; // return null if no keywords are found
    
    while ($row = $mysql_result->fetch_assoc()) {
        $keyword = new keyword();
        $keyword->get_keyword_by_id($row['keyword_id']);
        $keywords[] = array('id'=>$row['keyword_id'], 'count'=>$row['count'], 'keyword'=>$keyword->get_keyword());
    }
    return $keywords;
}

function get_keywords_used_by_disliked_response(&$mysql_connection, $id) : ?array {
    $sql = 'SELECT
                type_id AS keyword_id, 
                count(type_id) AS count
            FROM
                stats as s
            RIGHT JOIN (
                SELECT
                    group_id, session_id
                FROM
                    stats
                WHERE TYPE = \'response\' AND score=-1 AND type_id=?) AS x ON x.group_id = s.group_id AND x.session_id = s.session_id
            WHERE TYPE
                = \'keyword\'
            GROUP BY type_id
            ORDER BY count DESC';
    $mysql_prepare = $mysql_connection->prepare($sql);
    $mysql_prepare->bind_param('i', $id);
    $mysql_prepare->execute();
    $mysql_result = $mysql_prepare->get_result();
    $keywords = array();
    if($mysql_result->num_rows == 0) return null; // return null if no keywords are found
    
    while ($row = $mysql_result->fetch_assoc()) {
        $keyword = new keyword();
        $keyword->get_keyword_by_id($row['keyword_id']);
        $keywords[] = array('id'=>$row['keyword_id'], 'count'=>$row['count'], 'keyword'=>$keyword->get_keyword());
    }
    return $keywords;
}



?>