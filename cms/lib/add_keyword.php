<?php
include_once('../../config.php');
include_once('../../keyword.php');
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
 * Add keyword to the database
 * 
 */

if(!isset($jsonInput->keyword)) {   // No keyword given
    
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen keywoord gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

// create MSQL connection
$mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
if($mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $mysql_connection->connect_error, E_USER_ERROR); 
if($mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$mysql_connection->error, E_USER_ERROR);
if($mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$mysql_connection->error, E_USER_ERROR);

// add keyword
$keyword = new keyword();
if(!$keyword->set_keyword($jsonInput->keyword)) { // adding failed
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Keywoord to kort (minimaal '.MIN_KEYWORD_LENGTH.' tekens) of te lang (meer dan 50 tekens).');
    echo json_encode($return); // return JSON data
    exit; // stop script
}
if($keyword->save()) {
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Keywoord toegevoegd!');
    
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Er ging iets fout tijdens het toevoegen, probeer het opnieuw.');
}

echo json_encode($return); // return JSON data
?>