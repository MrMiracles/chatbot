## Config file

Create a config file in the root directory called 'config.php' with the following content:
    
    <?php

    define('MYSQL_SERVER', '...'); // Adres of the SQL server (e.g. localhost)
    define('MYSQL_USER', '...'); // Username to the SQL server
    define('MYSQL_PASS', '...'); // Password to the SQL server
    define('MYSQL_DB', '...');  // Which database to select on the SQL server
    define('MYSQL_CHARSET', 'utf8'); // Database charset, do not change this unless necesarry (default 'utf8')

    define('MIN_KEYWORD_LENGTH', 3); // Minimum lengt of an keyword to be saved to the database
    define('MIN_RESPONSE_LENGTH', 5); // Minimum lengt of an keyword to be saved to the database

    define('CMS_PASS', '...'); // simpel password for the simpel cms thingy, use password_hash('...', PASSWORD_DEFAULT) to encrypt password

    ?>

Make sure to put the correct information on the dots (...)

---

## Database
See the [database.sql](database.sql) file for creating the database structure.

---

## Data class

Opbouw van het object:

    data {

        get_responses() : array : bool
        
        get_keywords_from_sentence(string) : array|null
        filter_by_keyword(string, [bool]) : bool
        search(string)

    }

### voorbeeld

    $data = new data(); // initialiseer object

    data->get_keywords_from_sentence('Zoek in deze zin naar keywoorden die al in de database staan'); // geeft een array met keyword objecten terug van de keywoorden die in de tekst gevonden zijn. 

    $data->filter_by_keyword("D&I", true); // filter op het keywoord 'D&I', als het laatste argument 'true' is dan zijn alle antwoorden die terugkomen verbonden met dat keywoord
    $data->filter_by_keyword("banaan", false); // filter op het keywoord 'banaan', als het laatste argument 'false' is dan worden alle antwoorden die verbonden zijn met dat keywoord teruggegeven. 'false' is de standaard optie als je niks invult
    $data->search('zoekwoord'); // zoekt binnen alle gefilterde antwoorden naar 'zoekwoord'. Gebruik % als wildcard (zie functie commentaar voor verdere uitleg)


    $array_antwoorden = $data->get_responses(); // geeft een array terug met de gevonden antwoorden, null indien geen responses gevonden

---

## Keyword class

opbouw van het object:

    keyword {
 
      set_keyword(string) : bool

      bind_keyword_to_response(int) : bool
      remove_bind_to_response(int) : bool
 
      get() : array
      get_id() : int
      get_keyword(): string
 
      get_keyword_by_id(int): bool
      get_keyword_by_name(string) : bool
     
      save() : bool
      delete() : bool
 
    }

### Voorbeeld

    $keyword = new keyword(); // initialiseer object

    $keyword->get_keyword_by_id(8); // haal keywoord uit database aan de hand van een id
    $keyword->get_keyword_by_name('banaan'); // haal keywoord uit database aan de hand van een naam
    $keyword->set_keyword('appel'); // zet de naam van het keywoord

    $keyword->bind_keyword_to_response(9); // Verbind dit keywoord met response met id 9
    $keyword->remove_bind_to_response(5); // verbreekt de verbinding tussen dit keywoord en response met id 5
    
    $keyword->get(); // geeft een array terug met het id en de naam ([id=>8, naam=>'appel'])
    $keyword->get_id(); // geeft het id van het keywoord terug (8)
    $keyword->get_keyword(); // geeft het keywoord zelf terug als string (appel)

    $keyword->save(); // slaat het keywoord op in de database
    $keyword->delete(); // verwijderd het keywoord uit de database

---

## Response class

opbouw van het object:


    response {

       set_response(string) : bool

       bind_response_to_keyword(int) : bool
       remove_bind_to_keyword(int) : bool
       
       get() : array
       get_id() : int
       get_response() : string
       get_keywords : array|null
  
       get_response_by_id(int) : bool
      
       save() : bool
       delete() : bool

    }

### voorbeeld
  
    $response = new response(); // initialiseer object

    $response->get_response_by_id(3);  // haal response uit database aan de hand van een id
    $response->set_response('Blabla mooi antwoord.'); // zet de response

    $response->bind_response_to_keyword(9); // Verbind deze response met keywoord met id 9
    $response->remove_bind_to_keyword(5); // verbreekt de verbinding tussen deze response en keywoord met id 5

    $response->get();  // geeft een array terug met het id en de response ([id=>3, response=>'Blabla mooi antwoord.'])
    $response->get_id();  // geeft het id van de response terug (3)
    $response->get_response();  // geeft de response zelf terug als string (Blabla mooi antwoord.)
    $response->get_keywords();  // geeft de keywoorden terug die horen bij deze repsonse of null als er geen keywoorden gevonden zijn


    $response->save();  // slaat de response op in de database
    $response->delete();  // verwijderd de response uit de database
    
---

## Stats class

opbouw van het object:


    stats {

        start_new_session()
        set_session(int)
        get_session() : int

        keyword_hit(int) : bool
        response_hit(int) : bool

        response_like(int) : bool
        response_dislike(int) : bool

        get_liked_responses() : array|null
        get_disliked_responses() : array|null
        get_keywords_used_by_response(int) : array|null

        save() : bool

    }
  
### voorbeeld
    
    $stats = new stats(); // initialiseer object

    $stats->start_new_session(); // start een nieuwe sessie, alleen gebruiken als er ook echt een nieuw gesprek wordt gestart. Deze functie retuneert het nieuwe sessie ID (bijvoorbeeld: 55)
    $stats->set_session(55); // zet het sessie ID op 55, gebruik dit als het gesprek verder gaat.
    $stats->get_session(); // retuneert het huidige sessie ID (Dat wil je weten voor wanneer het gesprek verder gaat, bijboorbeeld: 55)

    $stats->keyword_hit(2); // slaat in de statistieken op dat het keywoord met ID 2 is gebruikt. Je kunt meerdere keywoorden in de statistieken opslaan door deze functie vaker achter elkaar te gebruiken.
    $stats->response_hit(15); // slaat in de statistieken op dat het antwoord met ID 15 is gebruikt. Je kunt meerdere antwoorden in de statistieken opslaan door deze functie vaker achter elkaar te gebruiken.

    $stats->response_like(15); // slaat in de statistieken op dat antwoord met ID 15 door de gebruiker geliked werd. Je kunt meerdere antwoorden in de statistieken liken door deze functie vaker achter elkaar te gebruiken.
    $stats->response_dislike(15); // slaat in de statistieken op dat antwoord met ID 15 door de gebruiker gedisliked werd. Je kunt meerdere antwoorden in de statistieken disliken door deze functie vaker achter elkaar te gebruiken.

    $stats->get_liked_responses(); // geeft alle gelikede antwoorden uit deze sessie terug (als een array met response objecten).
    $stats->get_disliked_responses(); // geeft alle gedislikede antwoorden uit deze sessie terug (als een array met response objecten).
    $stats->get_keywords_used_by_response(15); // geeft alle keywords terug die gebruikt zijn om antwoord met ID 15 te geven. 

    $stats->save(); // gebruik deze functie op alle hits, likes en dislikes op te slaan in de database.

---

## Icons
- [Eye icons created by Kiranshastry - Flaticon](https://www.flaticon.com/free-icons/eye)
- [Search icons created by Kiranshastry - Flaticon](https://www.flaticon.com/free-icons/search)
- [Delete icons created by Arkinasi - Flaticon](https://www.flaticon.com/free-icons/delete)
- [Clear icons created by riajulislam - Flaticon](https://www.flaticon.com/free-icons/clear)
- [Document icons created by Freepik - Flaticon](https://www.flaticon.com/free-icons/document)