<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php
        // Debug opties
        // ini_set('display_errors', 1);
        // error_reporting(E_ALL);
        
        session_start();

        // include data en stats class.
        include_once('../data/data.php');
        include_once('../data/stats.php');

         // controleer of en een POST request gedaan is. 
         // als er geen gedaan is gebruik dan de voorbeeld zin -> dit is alleen om te testen!
        if(isset($_POST)) {
           //k $message = $_POST['messageValue'];
           $data = file_get_contents("php://input");
           $message = json_decode($data);
        } else {
            $message = 'Ik ben opzoek naar een luk van D&I over communicatie';
        }
        
        // echo $message; // Tijdelijk: om bugs op te sporen
        
        // initialiseer data en stats object
        $data = new data();
        $stats = new stats();

        // ga opzoek naar keywoorden in de gegeven zin.
        // Deze functie geeft een array met keyword objecten terug van de keywoorden die in de tekst gevonden zijn. 
        $gevondenKeywoorden = $data->get_keywords_from_sentence($message); 
        
        // start new stats session of ga verder met bestaande sessie
        // sla sessie op als het een nieuwe is.
        if(isset($_SESSION['statsSession'])) {
            if($stats->set_session($_SESSION['statsSession']) === false) { // sessie niet gevonden, start nieuwe
                $stats->start_new_session();
                $_SESSION['statsSession'] = $stats->get_session();
                $stats->save(); // sla sessie op
            }
        } else {
            $stats->start_new_session();
            $_SESSION['statsSession'] = $stats->get_session();
            $stats->save(); // sla sessie op
        }
        

        // Filter de antwoorden op de gevonden keywoorden uit de 'messageValue'.
       // door de 'true' op het einde MOET het keywoord voorkomen in het antwoord.
       if($gevondenKeywoorden !== null) {
        foreach($gevondenKeywoorden as $keywoord) {
            $data->filter_by_keyword($keywoord->get_keyword(), false);
            $stats->keyword_hit($keywoord->get_id()); // voeg keyword hit toe aan statistieken
            
        }
       } else {
           // er zijn geen keywoorden gevonden, geef dat terug en stop script.
            echo '<p>Geen antwoorden gevonden helaas, stel een andere vraag..</p>';
            exit;
       }

        // vraag de gevonden antwoorden op
        // geeft een array terug met de gevonden antwoorden, null indien geen responses gevonden.
        $antwoorden = $data->get_responses();


        // controleer of er antwoorden zijn gevonden (null betekent dat er geen zijn gevonden)
        if($antwoorden === null) {
            echo '<p>Geen antwoorden gevonden helaas!</p>';
        } else {
            // presenteer de gevonden antwoorden als tekst.
            $arrAntwoorden = array();
            foreach($antwoorden as $antwoord) {
                echo '<p>'.$antwoord['response'].'</p>';
                $arrAntwoorden[] = $antwoord['id'];
                $stats->response_hit($antwoord['id']);  // voeg response hit toe aan statistieken
            }
        }
        
        $_SESSION['gegevenAntwoorden'] = $arrAntwoorden;
        
         $stats->save(); // sla stats op
    
    ?>  
</body>
</html>