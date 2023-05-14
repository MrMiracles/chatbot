<?php
include_once('../../config.php');
include_once('../../data.php');
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
 * Retourneer alle responses
 * 
 */

$data = new data();

// filter responses on given keywords
if(isset($jsonInput->keywords)){
    foreach($jsonInput->keywords as $keyword) {
        $data->filter_by_keyword($keyword);
    }
}


// recieve keywords from database
$responses = $data->get_responses();
if($responses === null) {
    $return = array(
        'login' => true,
        'succes' => false,
        'msg' => 'Geen responses gevonden.');
} else {
    // haal van elke respons de keywords op
    foreach($responses as &$response) {
        $tempResponse = new response();
        $tempResponse->get_response_by_id($response['id']);
        $keywords = $tempResponse->get_keywords();
        if($keywords !== false) $response['keywords'] = $keywords;
    }
    
    // return responses as JSON
    $return = array(
    'login' => true,
    'succes' => true,
    'responses' => $responses);
}



echo json_encode($return); // return JSON data
?>