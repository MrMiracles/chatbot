<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>query</title>
</head>
<body>
<?php
        // Debug opties
        // ini_set('display_errors', 1);
        // error_reporting(E_ALL);

session_start();

include_once('../data/config.php');
include_once('../data/stats.php');

$stats = new stats(); // initialiseer object

// start new stats session of ga verder met bestaande sessie
// sla sessie op als het een nieuwe is.
if(isset($_SESSION['statsSession'])) {
    $stats->set_session($_SESSION['statsSession']);
} else {
    // Er is nog geen stats sessie gestart, dan valt er ook niets te liken/disliken.
    // exit het script zonder iets te doen.
    exit;
}

//if(isset($_POST['like'])) {
    if($_POST['action'] == 'call_like') {
    if(isset($_SESSION['gegevenAntwoorden']) == true) {
        foreach($_SESSION['gegevenAntwoorden'] as $id) {
            $stats->response_like($id);
        }   
        $stats->save();
    }
}

//if(isset($_POST['dislike'])) {
    if($_POST['action'] == 'call_dislike') {
    if(isset($_SESSION['gegevenAntwoorden']) == true) {
        foreach($_SESSION['gegevenAntwoorden'] as $id) {
            $stats->response_dislike($id);
        }   
        $stats->save();
    }
}
    

?>  
</body>
</html>
