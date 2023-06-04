<?php
include_once('../../config.php');
session_start();

// get the input json
$input = file_get_contents('php://input');
$jsonInput = (!empty($input)) ? json_decode($input) : new stdClass(); // decode JSON or create an empty object
if($jsonInput === null) trigger_error('Wrong JSON formatting, couldnt decode.', E_USER_ERROR); // error in the JSON formatting, die.

/**
 * 
 * Login gebruiker
 * 
 */

if(isset($jsonInput->password)) { // nieuwe login poging komt binnen

        if(password_verify($jsonInput->password,CMS_PASS)) { // login succes
            $_SESSION['login'] = true;  // log gebruiker in voor deze sessie
            $return = array(
                'login' => true,
                'succes' => true,
                'msg' => 'Succesvol ingelogd!');

        } else { // login fail
            $return = array(
                'login' => false,
                'succes' => false,
                'msg' => 'Helaas pindakaas, verkeerde wachtwoord!');

            
        }
    echo json_encode($return); // return JSON data
} 



?>