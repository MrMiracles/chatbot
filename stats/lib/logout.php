<?php
session_start();

/**
 * 
 * loguit gebruiker
 * 
 */


session_destroy(); // sessie verwijderen
$return = array(
    'succes' => true,
    'msg' => 'Succesvol uitgelogd!');

echo json_encode($return); // return JSON data

?>