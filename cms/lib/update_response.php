<?php
include_once('../../config.php');
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

// controleer of juiste gegevens zijn meegestuurd
if(!isset($jsonInput->id) || !isset($jsonInput->response)) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen response id of tekst gegeven.');
    echo json_encode($return); // return JSON data
    exit;
}

// voeg response en keywords toe
$response = new response();
if(!$response->get_response_by_id(intval($jsonInput->id))) { // response niet gevonden
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord niet gevonden.');
    echo json_encode($return); // return JSON data
    exit;
}

$response->set_response($jsonInput->response);

if($response->save()) {
    // return responses as JSON
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Antwoord opgeslagen!');
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord waarschijnlijk te klein (minimaal '.MIN_RESPONSE_LENGTH.' tekens), of er is een andere fout. Probeer het opnieuw.');
}

echo json_encode($return); // return JSON data
?>