  //Chatbot project auteur:Kamal Mashhour maart 2023

  // Wanneer op de verzendknop wordt geklikt
  var teller = 0;
	var input = document.getElementById("userInput");
	input.addEventListener("keypress", function(event) {
	  if (event.key === "Enter") {
		  event.preventDefault();
		  document.getElementById("send").click();
	  }
	});
    document.querySelector("#send").addEventListener("click", async () => {

         // Nieuwe aanvraagobject aanmaken. Bericht ophalen
        //let xhr = new XMLHttpRequest();
        var userMessage = document.querySelector("#userInput").value;
    
    
        // Maak html code aan om bericht vast te houden. 
        teller= teller + 1;
        let userHtml = '<div class="userSection">'+'<div id="T'+teller+'" class="messages user-message">'+userMessage+'</div>'+
        '<div class="seperator"></div>'+'</div>';

    
        // Voer het bericht in op de pagina.
        document.querySelector('#body').innerHTML+= userHtml;
        var maakid = String("T" + teller);
        let element = document.getElementById(maakid);
        element.scrollIntoView({ behavior: "smooth", block: "end", inline: "nearest" });
        //alert(element); //ivm debug element id

        // fetch api om userMessage als keyword naar query.php sturen en response terug krijgen.
        fetch("php/query.php",{
			    "method": "POST",
			    "headers": {
			    "Content-Type": "application/json; charset=utf-8"
			    },
			    "body": JSON.stringify(userMessage)
        }).then(function(response){
			      return response.text(); //return response.json(); // als array
            // Wanneer het antwoord wordt geretourneerd,
		    }).then(function(data){
			      //console.log(data); //ivm debug antwoorden
            //Het antwoord als HTML invoegen op de pagina.
            let botHtml = '<div class="botSection">'+'<div id="U'+teller+'" class="messages bot-reply">'+(data)+'</div>'+
            '<div class="seperator"></div>'+'</div>';

            document.querySelector('#body').innerHTML+= botHtml;
            var maakid = String("T" + teller);
            let element = document.getElementById(maakid);
            element.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
		    })
    
    });

