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
if(!isset($jsonInput->response) || !isset($jsonInput->keywords)) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen response en/of keywords gegeven.');
    echo json_encode($return); // return JSON data
    exit;
}

// voeg response en keywords toe
$response = new response();
$response->set_response($jsonInput->response);
$bind_keywords = explode(',', $jsonInput->keywords);
if(count($bind_keywords) > 0) {
    foreach($bind_keywords as $bind_keyword) {
        $keyword = new keyword();
        if(!$keyword->get_keyword_by_name(trim($bind_keyword))) {
            // keywoord niet gevonden, voeg toe.
            $keyword->set_keyword(trim($bind_keyword));
        }
        if($keyword->save()) $response->bind_response_to_keyword($keyword->get_id());
    }
}
if($response->save()) {
    // return responses as JSON
    $return = array(
        'login' => true,
        'succes' => true,
        'msg' => 'Antwoord toegevoegd!');
} else {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Antwoord waarschijnlijk te klein (minimaal '.MIN_RESPONSE_LENGTH.' tekens), of er is een andere fout. Probeer het opnieuw.');
}

echo json_encode($return); // return JSON data
?>