
	// Istruzioni per formare una sezione di una voce di menù:
	// nella CSS va aggiunta una sezione per le ul#xxxtabs e la div.xxxContent
	// vanno aggiunte le variabili tabLinksNN e xxxDivs
	// nella funzione init() vanno aggiunte le sezioni per gestire la DIV e le voci del menù con link relativi univoci (vedi menù Generale)
	// nella DIV della voce di menù vanno inseriti <ul> della xxxtabs e le DIV di xxxContent
	
    var tabLinks = new Array();
    var contentDivs = new Array();

    var tabLinks2 = new Array();
    var sectionDivs = new Array();
	
    var tabLinks3 = new Array();
    var clienteDivs = new Array();
	
    function init() {
	  
	  // alert('INIT');
	  
      // Grab the tab links and content divs from the page
      var tabListItems = document.getElementById('tabs').childNodes;
      for ( var i = 0; i < tabListItems.length; i++ ) {
        if ( tabListItems[i].nodeName == "LI" ) {
          var tabLink = getFirstChildWithTagName( tabListItems[i], 'A' );
          var id = getHash( tabLink.getAttribute('href') );
          tabLinks[id] = tabLink;
          contentDivs[id] = document.getElementById( id );
        }
      }

      // Assign onclick events to the tab links, and
      // highlight the first tab
      var i = 0;

      for ( var id in tabLinks ) {
        tabLinks[id].onclick = showTab;
        tabLinks[id].onfocus = function() { this.blur() };
        if ( i == 0 ) tabLinks[id].className = 'selected';
        i++;
      }

      // Hide all content divs except the first
      var i = 0;

      for ( var id in contentDivs ) {
        if ( i != 0 ) contentDivs[id].className = 'tabContent hide';
        i++;
      }
	  
	  // modifiche per gestire i sottomenù di Generale
	  
     var tabListItems2 = document.getElementById('subtabs').childNodes;
      for ( var i = 0; i < tabListItems2.length; i++ ) {
        if ( tabListItems2[i].nodeName == "LI" ) {
          var tabLink2 = getFirstChildWithTagName( tabListItems2[i], 'A' );
          var id = getHash( tabLink2.getAttribute('href') );
          tabLinks2[id] = tabLink2;
          sectionDivs[id] = document.getElementById( id );
        }
      }

      // Assign onclick events to the tab links, and
      // highlight the first tab
      var i = 0;

      for ( var id in tabLinks2 ) {
        tabLinks2[id].onclick = showTab2;
        tabLinks2[id].onfocus = function() { this.blur() };
        if ( i == 0 ) tabLinks2[id].className = 'selected';
        i++;
      }

      // Hide all content divs except the first
      var i = 0;

      for ( var id in sectionDivs ) {
        if ( i != 0 ) sectionDivs[id].className = 'sectionContent hide';
        i++;
      }	  
	  // fine menù generale
	  
	  // modifiche per gestire i sottomenù di Cliente
	  
     var tabListItems3 = document.getElementById('clientetabs').childNodes;
      for ( var i = 0; i < tabListItems3.length; i++ ) {
        if ( tabListItems3[i].nodeName == "LI" ) {
          var tabLink3 = getFirstChildWithTagName( tabListItems3[i], 'A' );
          var id = getHash( tabLink3.getAttribute('href') );
          tabLinks3[id] = tabLink3;
          clienteDivs[id] = document.getElementById( id );
        }
      }

      // Assign onclick events to the tab links, and
      // highlight the first tab
      var i = 0;

      for ( var id in tabLinks3 ) {
        tabLinks3[id].onclick = showTab3;
        tabLinks3[id].onfocus = function() { this.blur() };
        if ( i == 0 ) tabLinks3[id].className = 'selected';
        i++;
      }

      // Hide all content divs except the first
      var i = 0;

      for ( var id in clienteDivs ) {
        if ( i != 0 ) clienteDivs[id].className = 'clienteContent hide';
        i++;
      }	  
      // fine menù Cliente	  
    }

    function showTab() {
      var selectedId = getHash( this.getAttribute('href') );

      // Highlight the selected tab, and dim all others.
      // Also show the selected content div, and hide all others.
      for ( var id in contentDivs ) {
        if ( id == selectedId ) {
          tabLinks[id].className = 'selected';
          contentDivs[id].className = 'tabContent';
        } else {
          tabLinks[id].className = '';
          contentDivs[id].className = 'tabContent hide';
        }
      }

      // Stop the browser following the link
      return false;
    }

    function showTab2() {
      var selectedId = getHash( this.getAttribute('href') );

      // Highlight the selected tab, and dim all others.
      // Also show the selected content div, and hide all others.
      for ( var id in sectionDivs ) {
        if ( id == selectedId ) {
          tabLinks2[id].className = 'selected';
          sectionDivs[id].className = 'sectionContent';
        } else {
          tabLinks2[id].className = '';
          sectionDivs[id].className = 'sectionContent hide';
        }
      }

      // Stop the browser following the link
      return false;
    }	
	
    function showTab3() {
      var selectedId = getHash( this.getAttribute('href') );

      // Highlight the selected tab, and dim all others.
      // Also show the selected content div, and hide all others.
      for ( var id in clienteDivs ) {
        if ( id == selectedId ) {
          tabLinks3[id].className = 'selected';
          clienteDivs[id].className = 'clienteContent';
        } else {
          tabLinks3[id].className = '';
          clienteDivs[id].className = 'clienteContent hide';
        }
      }

      // Stop the browser following the link
      return false;
    }
	
    function getFirstChildWithTagName( element, tagName ) {
      for ( var i = 0; i < element.childNodes.length; i++ ) {
        if ( element.childNodes[i].nodeName == tagName ) return element.childNodes[i];
      }
    }

    function getHash( url ) {
      var hashPos = url.lastIndexOf ( '#' );
      return url.substring( hashPos + 1 );
    }

// 2024-07-09 - versione personalizzata
	function loadPageToDiv2(urlToLoad,nomediv,indice) {
	  //alert($.fn.jquery);
	  //alert(urlToLoad);
      //alert(nomediv);
      //alert(indice);
      //if (indice != 3 && indice != 8 && indice != 9 && indice != 12 && indice != 99) { // evito di fare l'evidenzia sulle pagine dove non c'è
            for (var i = 1; i <= 14; i++) {
                $("#" + i).removeClass("evidenzia");
                if (i == indice) {
                    $("#" + i).addClass("evidenzia");
                }
            }
      //  }
        //alert("Load");
        try {
            /*
            if ($(nomediv) != null) {
                alert("Presente " + nomediv);
            }*/
            $(nomediv).load(urlToLoad, function (responseText, textStatus, req) {
                // alert("Risposta");
                if (textStatus == "error") {
                    alert("Errore: " + responseText);
                } else {
                    // alert('Load was performed.');
                }
            });
        } catch (err) {
            alert("Catch " + err.message);
        }
      
	}
// fine versione personalizzata

function loadPageToDiv(urlToLoad, nomediv) {
    //alert($.fn.jquery);
    //alert(urlToLoad);
    //alert(nomediv);

    $(nomediv).load(urlToLoad, function () {
        //alert('Load was performed.');
    })
}

	function impostaAnnoCompetenzaGenerale()
	{
	    alert("Impostato anno competenza a " + $('#annocompgen').val());
	}
	
	function LeggiDatiGenerale()
	{
	    // deve leggere i dati da un file ricevuto via JSON dal server
	    // alert("Lettura dei dati ");
		datipost = '{"richiesta":""}';
		PostJSONWebService('parametri.php',datipost, MostraDati);
	}
	
	function MostraDati(data)
	{
	  // funzione di callback per mostrare i dati in maschera
	   	$('#ncddtitalia').val(data.ncddtitalia);
		$('#ncddtrsm').val(data.ncddtrsm);
		$('#ncfatture').val(data.ncfatture);
		$('#ncriba').val(data.ncriba);
		$('#ncdistintariba').val(data.ncdistintariba);
	}
	
	function SalvaDatiGenerale()
	{
	    // invia i dati via JSON al server per la registrazione
	    // alert("Salvataggio dei dati");		
		/*
		elementi = '{"ncddtitalia" : "' + $('#ncddtitalia').val() + '",';
		elementi = elementi + '"ncddtrsm" : "' + $('#ncddtrsm').val() + '",';
		elementi = elementi +  '"ncfatture" : "' +  $('#ncfatture').val() + '",';
		elementi = elementi +  '"ncriba" : "' + $('#ncriba').val() + '",';
		elementi = elementi +  '"ncdistintariba" : "' + $('#ncdistintariba').val() + '"}';
		*/
		var oggetto = { ncddtitalia : $('#ncddtitalia').val() ,
		          ncddtrsm : $('#ncddtrsm').val() ,
				  ncfatture : $('#ncfatture').val(),
				  ncriba : $('#ncriba').val(),
				  ncdistintariba : $('#ncdistintariba').val()};
	    // alert("Scrivo: " + JSON.stringify(oggetto));
		PostJSONWebService('registraGenerale.php', oggetto, function(data){ alert("Salvato i parametri");  }); // alert("Fatture: " + data.ncfatture + ",DDT Italia:" + data.ncddtitalia);
	    // alert("Richiesta inviata");
	}
	
	function PostJSONWebService(urlWS,dati,callback)
	{
	   // http://hayageek.com/jquery-ajax-json-parsejson-post-getjson/
	   // alert("Sito:" + urlWS);
	   risposta = null;
	   
	   if (dati != null)
	   {
	     // alert("Dati non null");
	     $.ajax({
           type: 'POST',
           url: urlWS,
           data: JSON.stringify(dati) , // '{"name":"jonas"}', // or JSON.stringify ({name: 'jonas'}),
           success: callback, // function(data) { risposta = data;},
		   error: function (xhr, ajaxOptions, thrownError) {
               alert(xhr.status);
               alert(thrownError);
		     },
           contentType: "application/json",
           dataType: 'json'
         });
	   }
	   else
	   {
	     // alert("Dati NULL");
	     $.ajax({
           type: 'POST',
           url: urlWS,
		   data: null,
           success: callback, // function(data) { risposta = data; },
		   error: function (xhr, ajaxOptions, thrownError) {
               alert(xhr.status);
               alert(thrownError);
		     },		   
           contentType: "application/json",
           dataType: 'json'
         });	   
	   }
	}