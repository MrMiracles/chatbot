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

    ?>

Make sure to put the correct information on the dots (...)

## Keyword class

opbouw van het object:

    keyword {
 
      set_keyword(<string>) : bool

      bind_keyword_to_response(<int>) : bool
      remove_bind_to_response(<int>) : bool
 
      get() : array
      get_id() : int
      get_keyword(): string
 
      get_keyword_by_id(<string>): bool
      get_keyword_by_name(<string>) : bool
     
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


## Response class

opbouw van het object:


    response {

       set_response(<string>) : bool

       bind_response_to_keyword(<int>) : bool
       remove_bind_to_keyword(<int>) : bool
       
       get() : array
       get_id() : int
       get_response() : string
  
       get_response_by_id(<int>) : bool
      
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


    $response->save();  // slaat de response op in de database
    $response->delete();  // verwijderd de response uit de database
    
