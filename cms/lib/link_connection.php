<?php
include_once('../../config.php');
include_once('../../keyword.php');
include_once('../../response.php');
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
        'msg' => 'Geen response id of keywoord gegeven');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

$response = new response();
if(!$response->get_response_by_id($jsonInput->respid)) { // response niet gevonden
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord niet gevonden, kan geen connectie leggen.');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

$error = false;
$success = false;
if(is_array($jsonInput->keyword)) { // multiple keywords given

    foreach($jsonInput->keyword as $newKeyword) {
        
        $keyword = new keyword();
        if(!$keyword->get_keyword_by_name($newKeyword)) {
            // keywoord niet gevonden, voeg toe.
            if(!$keyword->set_keyword($newKeyword)) { // keywoord toevoegen mislukt
                $error = true;
                continue;
            } else {
                $keyword->save();
                $success = true;
            }
        } else {
            $success = true;
        }
        
        $response->bind_response_to_keyword($keyword->get_id()); // verbinding maken met keyword

    }
} else { // single keyword given
    

    $keyword = new keyword();
    if(!$keyword->get_keyword_by_name($jsonInput->keyword)) {
        // keywoord niet gevonden, voeg toe.
        if(!$keyword->set_keyword($jsonInput->keyword)) { // keywoord toevoegen mislukt
            $return = array(
                'login' => true,
                'succes' => false,
                'msg' => 'Keywoord to kort (minimaal '.MIN_KEYWORD_LENGTH.' tekens) of te lang (meer dan 50 tekens).');
            echo json_encode($return); // return JSON data
            exit; // stop script
        } else {
            $keyword->save();
        }
    }

    $response->bind_response_to_keyword($keyword->get_id()); // verbinding maken met keyword
    $success = true;
}


if($success != true) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Link tussen antwoord en keywoorden gemaakt!');
    echo json_encode($return); // return JSON data
    exit; // stop script
}

if($response->save()) {
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => ($error) ? 'Keywoord to kort (minimaal '.MIN_KEYWORD_LENGTH.' tekens) of te lang (meer dan 50 tekens). Andere keywoorden toegevoegd!' : 'Link tussen antwoord en keywoord gemaakt!');
    
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Ergens ging er iets fout! Probeer het nog een keertje.');
}

echo json_encode($return); // return JSON data



?>