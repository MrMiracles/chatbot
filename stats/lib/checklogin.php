<?php
include_once('../../config.php');
session_start();


if(isset($_SESSION['login']) && $_SESSION['login'] == true) {  // Gebruiker is al ingelogd, hoera!
    $return = array(
        'succes' => true,
        'msg' => 'Gebruiker is al ingelogd!');

} else { // login fail
    $return = array(
        'succes' => false,
        'msg' => 'Gebruiker is nog niet ingelogd');

    
}

echo json_encode($return); // return JSON data

?>