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
 * bind connection between response and keyword
 * 
 */




if(!isset($jsonInput->respid) || !isset($jsonInput->keyword)) {   // No keyword and/or response id given
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen response of keywoord id gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}


$posted_keyword = htmlspecialchars($jsonInput->keyword);

$keyword = new keyword();
if(!$keyword->get_keyword_by_name($posted_keyword)) {
    // keywoord niet gevonden, voeg toe.
    if(!$keyword->set_keyword($posted_keyword)) { // keywoord toevoegen mislukt
        $return = array(
            'login' => true,
            'succes' => false,
            'msg' => 'Keywoord to kort (minimaal '.MIN_KEYWORD_LENGTH.' tekens) of te lang (meer dan 50 tekens).');
        echo json_encode($return); // return JSON data
        exit; // stop script
    }
}

if(!$keyword->bind_keyword_to_response(intval($jsonInput->respid))) { // response niet gevonden
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord niet gevonden, kan geen connectie leggen.');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

if($keyword->save()) {
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Link tussen antwoord en keywoord gemaakt!');
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Ergens ging er iets fout! Probeer het nog een keertje.');
}

echo json_encode($return); // return JSON data



?>