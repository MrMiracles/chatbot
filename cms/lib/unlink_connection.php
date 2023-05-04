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
 * Remove connection between response and keyword
 * 
 */



if(!isset($jsonInput->respid) || !isset($jsonInput->keyid)) {   // No keyword and/or response id given
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen response of keywoord id gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}


$keyword = new keyword();
if(!$keyword->get_keyword_by_id(intval($jsonInput->keyid))) { // keywoord niet gevonden
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Keywoord niet gevonden, kan de verbinden niet verbreken.');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

if(!$keyword->remove_bind_to_response(intval($jsonInput->respid))) { // antwoord niet gevonden
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord niet gevonden, kan de verbinden niet verbreken.');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

if($keyword->save()) {
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Link tussen antwoord en keywoord verbroken!');
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Ergens ging er iets fout! Probeer het nog een keertje.');
}

echo json_encode($return); // return JSON data

?>