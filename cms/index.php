<?php

include('../config.php');
include('../data.php');

session_start();
$info = '';


if(!isset($_SESSION['login'])) { // nog geen login, toon login pagina en stop het script.

    if(isset($_GET['a']) && $_GET['a'] == 'login') {
        if($_POST['password'] == CMS_PASS) { // login succes
            $_SESSION['login'] = true;
        } else { // login fail
            echo "Helaas pindakaas, verkeerde wachtwoord!";

            echo '<form action="index.php?a=login" method="post">
            <input name="password" type="password" placeholder="Voer wachtwoord in">
            <input type="submit" value="login!">
            </form>';

            exit;
        }
    } else {

        echo '<form action="index.php?a=login" method="post">
        <input name="password" type="password" placeholder="Voer wachtwoord in">
        <input type="submit" value="login!">
        </form>';

        exit;
    }

} 

if(isset($_SESSION['login'])) { // gebruiker ingelogd, tijd voor actie!

    if(isset($_GET['a'])) $info = do_action($_GET['a']); // moet er iets gebeuren?

    // create MSQL connection
    $mysql_connection = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASS);
    if($mysql_connection->connect_errno !== 0) trigger_error('MySQL Connection failed:'. $mysql_connection->connect_error, E_USER_ERROR); 
    if($mysql_connection->set_charset(MYSQL_CHARSET) === false) trigger_error('MySQL set charset failed: '.$mysql_connection->error, E_USER_ERROR);
    if($mysql_connection->select_db(MYSQL_DB) === false) trigger_error('MySQL select database failed '.$mysql_connection->error, E_USER_ERROR);
}



function do_action(string $a) {

    switch($a) {
        case 'addkeyword':

            if(!isset($_POST['keyword'])) return "Geen keywoord gegeven.";

            $keyword = new keyword();
            $posted_keyword = htmlspecialchars($_POST['keyword']);
            if(!$keyword->set_keyword($posted_keyword)) return "Keywoord to kort (minimaal ".MIN_KEYWORD_LENGTH." tekens) of te lang (meer dan 50 tekens).";
            if($keyword->save()) {
                return "Keywoord Toegevoegd: ".$posted_keyword;
            } else {
                return "Keywoord misschien te klein (minimaal ".MIN_KEYWORD_LENGTH." tekens), het keywoord bestaat al of er is een andere fout: ".$posted_keyword;
            }
            break;
        
        case 'deleteKeyword':

            if(!isset($_GET['id'])) return "Geen id gegeven.";

            $keyword = new keyword();
            if(!$keyword->get_keyword_by_id(intval($_GET['id']))) return "Keywoord met id ".htmlspecialchars($_GET['id'])." niet gevonden.";
            if($keyword->delete()) {
                return "Keywoord ".$keyword->get_keyword()." verwijderd.";
            } else {
                return "Iets ging er fout tijdens het verwijderen, jammer dan!";
            }

            break;

        case 'addResponse':

            if(!isset($_POST['response'])) return "Geen antwoord gegeven.";

            $response = new response();
            $posted_response = htmlspecialchars($_POST['response']);
            $posted_response_keywords = htmlspecialchars($_POST['keywords']);
            $response->set_response($posted_response);
            $bind_keywords = explode(',', $posted_response_keywords);
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
                return "Antwoord Toegevoegd: <br>".nl2br($posted_response);
            } else {
                return "Antwoord waarschijnlijk te klein (minimaal ".MIN_RESPONSE_LENGTH." tekens), of er is een andere fout: ".$posted_response;
            }
            break;
        
        case 'deleteResponse':

            if(!isset($_GET['id'])) return "Geen id gegeven.";

            $response = new response();
            if(!$response->get_response_by_id(intval($_GET['id']))) return "Antwoord met id ".htmlspecialchars($_GET['id'])." niet gevonden.";
            if($response->delete()) {
                return "Keywoord ".$response->get_response()." verwijderd.";
            } else {
                return "Iets ging er fout tijdens het verwijderen, jammer dan!";
            }
            break;

        case 'linkKeyword':

            if(!isset($_GET['respid'])) return "Geen antwoord id gegeven.";
            if(!isset($_POST['keyword'])) return "Geen keywoord gegeven.";
            $posted_keyword = htmlspecialchars($_POST['keyword']);

            $keyword = new keyword();
            if(!$keyword->get_keyword_by_name($posted_keyword)) {
                // keywoord niet gevonden, voeg toe.
                if(!$keyword->set_keyword($posted_keyword)) return "Keywoord to kort (minimaal ".MIN_KEYWORD_LENGTH." tekens) of te lang (meer dan 50 tekens).";
            }
            if(!$keyword->bind_keyword_to_response(intval($_GET['respid']))) return "Antwoord niet gevonden, kan geen connectie leggen";
            if($keyword->save()) {
                return "Link tussen antwoord en keywoord is gemaakt!";
            } else {
                return "Ergens ging er iets fout! Probeer het nog een keertje ofzo.";
            }

            break;

        case 'unlinkKeyword':

            if(!isset($_GET['respid'])) return "Geen id gegeven.";
            if(!isset($_GET['keyid'])) return "Geen keywoord id gegeven.";

            $keyword = new keyword();
            if(!$keyword->get_keyword_by_id($_GET['keyid'])) return "Keywoord niet gevonden, kan de verbinden niet verbreken.";
            if(!$keyword->remove_bind_to_response(intval($_GET['respid']))) return "Antwoord niet gevonden, kan de verbinden niet verbreken.";
            if($keyword->save()) {
                return "Link tussen antwoord en keywoord verbroken!";
            } else {
                return "Ergens ging er iets fout! Probeer het nog een keertje ofzo.";
            }

            break;

        case 'login': // wordt boven al afgehandeld
            break;

        default:
            return 'Geen juiste actie gevonden.. sorry!: '.$a;

    }

}






?>

<!DOCTYPE html>
<html  lang="nl-nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data CMS - Chatbot Fontys</title>
    <link rel="stylesheet" href="style.css">

    <script>
        function hideDiv(divid) {
            document.getElementById(divid).style.display = 'none';
        }
    </script>
</head>

<body>

        <?php
            if(!isset($_GET['a']) && empty($info)) {
                echo '
                <div id="firstandlastwarning">
                    <h1>Let op!</h1>
                    <p>Door op het prullenbakje <img src="delete.png" width="16" style="background-color: #efefef; padding: 5px; border-radius:100%; position: relative; top:0.5em" /> te klikken verwijder je <b>zonder waarschuwing</b> een keywoord of antwoord en de verbindingen tussenbeide.<br/><br/>
                    Door op het het kruisje <img src="unlink.png" width="16" style="background-color: #efefef; padding: 5px; border-radius:100%; position: relative; top:0.5em" /> te klikken verwijder je <b>zonder waarschuwing</b> de verbinding tussen een keywoord en antwoord.<br/><br/>
                    <button  onclick="hideDiv(\'firstandlastwarning\')">Gelezen & sluiten</button>
                    </p>
                </div>
                ';
            }
        ?>
    
    <div id="infobox" class="info" onclick="hideDiv('infobox')">
        <?php if(!empty($info)) echo "<p>".$info."</p>"; ?>
    </div>

    <div class="gridContainer">

        <div class="containerResponses">

            <h1>Antwoorden</h1>

            <div class="addResponse">
                <form action="index.php?a=addResponse" method="post">
                    <label>Voeg antwoord toe:</label>
                    <textarea name="response" placeholder="Typ hier een antwoord."></textarea>
                    <textarea name="keywords" placeholder="Typ hier keywords die verbonden moeten worden met het antwoord. Scheid de keywoorden met komma's." place></textarea>
                    <input type="submit" value="Toevoegen">
                </form>
            </div>

            <div class="responseList">
                <ul>
                    <?php
                    // create list with keywords
                    $mysql_prepare = $mysql_connection->prepare('SELECT keyword FROM keywords ORDER BY keyword ASC');
                    $mysql_prepare->execute();
                    $mysql_result = $mysql_prepare->get_result();
                    echo "<datalist id=\"keywords\">";
                    while ($row = $mysql_result->fetch_assoc()) {
                        echo "<option>".$row['keyword']."</option>";
                    }
                    echo "</datalist>";


                    $mysql_prepare = $mysql_connection->prepare('SELECT r.id, r.response, k.keyword, x.keyword_id FROM responses AS r LEFT JOIN keyword_x_responses AS x ON x.response_id = r.id LEFT JOIN keywords AS k ON x.keyword_id = k.id ORDER BY r.id DESC');
                    $mysql_prepare->execute();
                    $mysql_result = $mysql_prepare->get_result();
                    if($mysql_result->num_rows == 0) echo "Geen antwoorden gevonden.";
                    
                    $last_id = 0;
                    while ($row = $mysql_result->fetch_assoc()) {
                        if($last_id != $row['id']) { // nieuw response
                            if($last_id != 0) {
                                echo "</ul>"; // sluit laatste item af
                                echo '<form action="index.php?a=linkKeyword&respid='.$last_id.'" method="post">
                                <input name="keyword" type="text" autocomplete="off" list="keywords" placeholder="Typ keywoord.">
                                <input type="submit" value="Verbinden!"> <i class="tip">(als het keywoord niet bestaat wordt deze toegevoegd)</i>
                                </form>';
                                echo "</div></li>"; // sluit laatste item af
                            }
                            $last_id = $row['id'];
                            echo "<li class=\"response\">";
                            echo "<div><b class=response_text>".$row['response']."</b> <a class=\"delete\" href=\"index.php?a=deleteResponse&id=".$row['id']."\"> <img src=\"delete.png\" width=\"16\" style=\"vertical-align: -10%\" /></a></div>";
                            echo "<div><ul>";
                            if($row['keyword'] != null) echo "<li>".$row['keyword']." <a href=\"index.php?a=unlinkKeyword&respid=".$row['id']."&keyid=".$row['keyword_id']."\"> <img src=\"unlink.png\" width=\"16\" style=\"vertical-align: -10%\" /></a></li>";
                        } else { // zelfde antwoord
                            echo "<li>".$row['keyword']." <a href=\"index.php?a=unlinkKeyword&respid=".$row['id']."&keyid=".$row['keyword_id']."\"> <img src=\"unlink.png\" width=\"16\" style=\"vertical-align: -10%\" /></a></li>";
                        }
                    }
                    echo "</ul>"; // sluit laatste item af
                    echo '<form action="index.php?a=linkKeyword&respid='.$last_id.'" method="post">
                                <input name="keyword" type="text" autocomplete="off" list="keywords" placeholder="Typ keywoord.">
                                <input type="submit" value="Verbinden!"> <i class="tip">(als het keywoord niet bestaat wordt deze toegevoegd)</i>
                                </form>';
                    echo "</div></li>"; // sluit laatste item af
                    ?>
                </ul>
            </div>
        </div>

        <div class="containerKeywords">

            <h1>Keywords</h1>

            <div class="addKeyword">
                <form action="index.php?a=addkeyword" method="post">
                    <label>Voeg keywoord toe:</label>
                    <input name="keyword" type="text">
                    <input type="submit" value="Toevoegen">
                </form>
            </div>

            <div class="keywordList">
                <ul>
                    <?php
                    $mysql_prepare = $mysql_connection->prepare('SELECT id, keyword FROM keywords ORDER BY id DESC');
                    $mysql_prepare->execute();
                    $mysql_result = $mysql_prepare->get_result();
                    if($mysql_result->num_rows == 0) echo "Geen keywoorden gevonden.";
                    
                    while ($row = $mysql_result->fetch_assoc()) {
                        echo "<li>".$row['keyword']." <a href=\"index.php?a=deleteKeyword&id=".$row['id']."\"><img src=\"delete.png\" width=\"16\" style=\"vertical-align: -10%\" /></a></li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        
    </div>

</body>


</html>
