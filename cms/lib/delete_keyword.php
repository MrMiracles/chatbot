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
 * Delete keyword from the database
 * 
 */

if(!isset($jsonInput->id)) {   // No keyword id given
    
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen keywoord id gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

$keyword = new keyword();
if(!$keyword->get_keyword_by_id(intval($jsonInput->id))) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Keyword niet gevonden.');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

if($keyword->delete()) {
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Keywoord "'.$keyword->get_keyword().'" verwijderd.');
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Er ging iets fout tijdens het verwijderen, probeer het opnieuw.');
}

echo json_encode($return); // return JSON data
?>