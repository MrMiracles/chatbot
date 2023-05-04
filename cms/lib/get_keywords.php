<?php
include_once('../../config.php');
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
 * Retourneer alle keywords
 * 
 */

// create MSQL connection
$mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
if($mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $mysql_connection->connect_error, E_USER_ERROR); 
if($mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$mysql_connection->error, E_USER_ERROR);
if($mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$mysql_connection->error, E_USER_ERROR);

// check if user wants to search the keywords
if(empty($jsonInput->search)) { // no search words, user wants all the keywords  
    $mysql_prepare = $mysql_connection->prepare('SELECT id, keyword FROM keywords ORDER BY id DESC');
} else {
    $mysql_prepare = $mysql_connection->prepare('SELECT id, keyword FROM keywords WHERE keyword LIKE ? ORDER BY id DESC');
    $searchValue = '%'.htmlspecialchars($jsonInput->search, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8').'%';
    $mysql_prepare->bind_param('s', $searchValue);
}

// recieve keywords from database

$mysql_prepare->execute();
$mysql_result = $mysql_prepare->get_result();
if($mysql_result->num_rows == 0) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen keywoorden gevonden');
        
        echo json_encode($return); // return JSON data
}

// put keywords in an array
$keywords = array();
while ($row = $mysql_result->fetch_assoc()) {
    $keywords[] = array('id' =>$row['id'], 'keyword' => $row['keyword']);
}

// return keywords as JSON
$return = array(
'login' => true,
'succes' => true,
'keywords' => $keywords);

echo json_encode($return); // return JSON data
?>