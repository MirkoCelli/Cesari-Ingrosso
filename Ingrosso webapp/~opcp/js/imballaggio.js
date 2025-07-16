// (c) 2024-07-05 - Robert Gasperoni
// funzioni che permettono di registrare l'assegnamento agli operatori degli ordinativi da imballare

function ConfermaAssegnazioneTicket() {
    // richiama lo script per l'assegnazione del ticket
    // dalla risposta otteniamo il numero di ticket per fare visualizzare in una nuova scheda i dettagli dell'ordine
    // alert("Conferma Assegnazione Ticket");
    // mi servono annocomp, responsabile, ordine
    var giorno = $("#giorno").val();
    var adesso = new Date(giorno);
    var annocomp = adesso.getFullYear();
    // var resp = $("#operatori").val();
    var resp = $("input[name='operatori']:checked").val();
    // var ordine = $("#ordini").val();
    var ordine = $("input[name='ordini']:checked").val();
    var urlConfTick = "confermaticket.php?annocomp=" + annocomp + "&responsabile=" + resp + "&ordine=" + ordine;
    // alert("url = " + urlConfTick);
    var rispJson;
    $.ajax({
        type: "GET",
        url: urlConfTick,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert(data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "statooper" : "0|1", "ticket" : "xxx", "msg" : "testo msg" , "error" : "testo errore"}
            // Se status è KO vuol dire che non ha assegnato il ticket per problemi
            // Se status è OK allora in ticket c'è il numero di ticket attribuito all'ordine che ci servirà per avere i dettagli dell'ordine
            // da fare visualizzare all'operatore
            if (data["status"] == "KO" || data["statooper"] == 0) {
                // ci sono stati problemi
                alert("Non ho potuto assegnare il ticket a questo ordine: " + data["error"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK" && data["statooper"] == 1) {
                alert("Il ticket " + data["ticket"] + " l'abbiamo assegnato a questo ordine "); //  + data["msg"]
                // aprire una finestra con i dettagli dell'ordine per l'operatore responsabile
                var urlPaginaOrdine = "dettaglioordine.php?idordine=" + ordine + "&responsabile=" + resp;
                // alert(urlPaginaOrdine);
                var finestra = window.open(urlPaginaOrdine, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
                finestra.document.style.background = "#F5F5DC";
                // esegue il refresh della pagina
                // loadPageToDiv2("imballaggio.php?giorno=" + $("#giornata").val(), "#pagina", 8);
            };
        },
        error: function (error) {
            alert(error);
        },
        async: false
    });

    return false;
}

function ApriOrdineTicket() {
    // richiama lo script per l'assegnazione del ticket
    // dalla risposta otteniamo il numero di ticket per fare visualizzare in una nuova scheda i dettagli dell'ordine
    // alert("Conferma Assegnazione Ticket");
    // mi servono annocomp, responsabile, ordine
    // da fare solo se resp è valorizzato
    var giorno = $("#giorno").val();
    var adesso = new Date(giorno);
    var annocomp = adesso.getFullYear();
    var ticket = $("input[name='numeroticket']").val().trim();
    var resp = $("input[name='operatori']:checked").val();
    var urlApriTick = "mostraticket.php?annocomp=" + annocomp + "&ticket=" + ticket + "&responsabile=" + resp;
    // alert(resp);
    // alert("url = " + urlApriTick);
    if (!isNaN(ticket)&&(ticket!=="")) {
        if (resp !== undefined) {
            var finestra = window.open(urlApriTick, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
            finestra.document.style.background = "#F5F5DC";
        } else {
            alert("Non avete scelto l'operatore");
        }
    } else {
        alert("Indicare il numero di ticket");
    }
    
    return false;
}

function CambiareData() {
    // cambiare la data di riferimento
    /*
    // alert("Cambiare Data");   
    var urlPaginaImballaggio = "imballaggio.php?giorno=" + $("#giornata").val();
    // alert(urlPaginaImballaggio);
    var finestra = window.open(urlPaginaImballaggio, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
    finestra.document.style.background = "#F5F5DC";
    */
    // --- crea problemi con la navigazione del menù principale, faccio aprire una nuova finestra
    loadPageToDiv2('imballaggio.php?giorno=' + $("#giornata").val(), '#pagina', 8);    

    return false;
}

function CompletatoOrdine(ordine, responsabile) {
   // chiama lo script per segnalare il completamento dell'imballaggio definito da ordine e responsabile
    var urlCompletato = "completatoordine.php?idordine=" + ordine + "&responsabile=" + responsabile;
    // alert(urlCompletato);
    $.ajax({
        type: "GET",
        url: urlCompletato,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert(data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "statooper" : "0|1", "ticket" : "xxx", "msg" : "testo msg" , "error" : "testo errore"}
            // Se status è KO vuol dire che non ha assegnato il ticket per problemi
            // Se status è OK allora in ticket c'è il numero di ticket attribuito all'ordine che ci servirà per avere i dettagli dell'ordine
            // da fare visualizzare all'operatore
            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Non ho potuto registrare il completamento di questo ordine: " + data["error"]);
            };
            if (data["status"] == "OK") {
                alert("Completamento di questo ordine ");
            };
        },
        error: function (error) {
            alert(error);
        },
        async: false
    });
    window.open('', '_self').close();
    return false;
}

function PreparaTutto() {
    // 07/08/2024 - Si indicano tutti gli ordini da mettere in stato preparato se sono in stato DA_PRODURRE e assegnandogli un ticket per ciascuno
    // alert("PreparaTutto");
    var giorno = $("#giornata").val();
    var adesso = new Date(giorno);
    var annocomp = adesso.getFullYear();
    var giornobuono = adesso.toISOString().split('T')[0];
    var urlPrepara = "preparaordini.php?giorno=" + giornobuono;
    // alert("url = " + urlPrepara);
    var rispJson;
    $.ajax({
        type: "GET",
        url: urlPrepara,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            if (data["status"] == "ERROR") {
                // ci sono stati problemi
                alert(data["errore"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK") {
                alert("Abbiamo assegnato un ticket e lo stato preparato a tutti gli ordini del giorno "); //  + data["msg"]
            };
        },
        error: function (error) {
            alert(error);
        },
        async: false
    });
    // alert("Ordini del giorno Preparati");
    return false;
}

function ConsegnaTutto() {
    // 07/08/2024 - Si indicano tutti gli ordini da mettere in stato consegnato se sono in stato preparato
    // alert("ConsegnaTutto");
    var giorno = $("#giornata").val();
    var adesso = new Date(giorno);
    var annocomp = adesso.getFullYear();
    var giornobuono = adesso.toISOString().split('T')[0];
    var urlConsegna = "consegnaordini.php?giorno=" + giornobuono;
    // alert("url = " + urlConsegna);
    var rispJson;
    $.ajax({
        type: "GET",
        url: urlConsegna,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            if (data["status"] == "ERROR") {
                // ci sono stati problemi
                alert(data["errore"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK") {
                alert("Abbiamo consegnato tutti gli ordini del giorno "); //  + data["msg"]
            };
        },
        error: function (error) {
            alert(error);
        },
        async: false
    });
    // alert("Ordini del giorno Consegnati");
    return false;
}

function VisualizzaDettagliOrdine() {
    // apre una scheda dei dettagli ordine pe ril cliente selezionato 
    alert("Visualizza Dettaglio Ordine");
    return false;
}

function VisualizzaDettaglioOrdinePerTicket(numeroticket) {
    // apre una scheda dei dettagli ordine per il numero di ticket indicato e alla data del giorno
    alert("Visualizza Ordine del Ticket");
    return false;
}

function RefreshImballaggio() {
    // alert("Refresh Imballaggio");
    loadPageToDiv2('imballaggio.php?giorno='+$("#giornata").val(), '#pagina',8);
    return false;
}

// stampa dell'ordine per il cliente
function StampaOrdine(idord) {
    urlStampaTick = "stampaticket.php?idordine=" + idord;
    var finestra = window.open(urlStampaTick, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
    return false;
}

var lastsel;
var selrow;
var editingRowId;
var testo;

// IMBALLAGGIO - 2024-07-05

// Elenco dei valori ammessi per Zone
var elencoListino = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "clientipage.php?q=5",
    dataType: "xml",
    success: function(xml) {
       // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
       elencoListino += $(xml).find('rows').text();
    },
    async:   false
});

// Elenco dei valori ammessi per Provincie
var elencoIntermediario = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "clientipage.php?q=6",
    dataType: "xml",
    success: function(xml) {
       // abbiamo in xml il nostro file provincia, ora lo dobbiamo assegnare a elencoProvincie
       elencoIntermediario += $(xml).find('rows').text();
    },
    async:   false
});

/*
 * per modificare le caratteristiche grafiche della griglia vedere in themes/ui.jqgrid.css e cambiare i parametri per
 * 
 / * Grid * /
.ui - jqgrid { position: relative; font - size: 11px; }  <- qui metter ela dimensione della font 
.ui - jqgrid.ui - jqgrid - view { position: relative; left: 0px; top: 0px; padding: .0em; }
 */

jQuery("#navgridClienti").jqGrid({
    url:'clientipage.php?q=10',
    datatype: "xml",
    colNames:['ID','CODICE CLIENTE', 'DENOMINAZIONE','NOME BREVE','LISTINO','TIPO LISTINO','INTERMEDIARIO','NOME INTERMEDIARIO','ANNOTAZIONI'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
        {name:'codicecliente',index:'codicecliente', width:100, align:"left",editable:true,editoptions:{size:15}},
        {name:'denominazione',index:'denominazione', width: 250, editable: true, editoptions: { size: 250 } },
        {name:'nomebreve',index:'nomebreve', width:150,editable:true,editoptions:{size:30}},        	
        {name:'listino', index: 'listino', width: 50, editable: true, edittype: "select", editoptions: { value: elencoListino } },
        {name:'tipolistino', index: 'tipolistino', width: 100, editable: false, editoptions: { size: 100 } },
        {name:'intermediario', index: 'intermediario', width: 100, editable: true, edittype: "select", editoptions: { value: elencoIntermediario } },
        {name:'nomeintermediario', index: 'nomeintermediario', width: 250, editable: false, editoptions: { size: 100 } },
        {name:'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
    rowNum: 25,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavClienti',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    caption:"Imballaggio Vassoi Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsClienti").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"clientipage.php",
    height:630,
	width: 1800,
    shrinkToFit: false,
    autowidth: false
});

jQuery("#navgridClienti").jqGrid('navGrid','#pagernavClienti',
{ edit:false,
  add:false,
  del:true,
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"clientipage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridClienti").jqGrid('navButtonAdd','#pagernavClienti',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridClienti").jqGrid('excelExport',{"url":"clientipage.php?q=50"});
       } 
});

jQuery("#navgridClienti").jqGrid('inlineNav','#pagernavClienti',
{
// {},
   add: true,
   edit: true,   
   save:true,
   cancel:true,
   saveicon:"ui-icon-disk", 
   savetitle: "Salva i dati correnti",
   cancelicon:"ui-icon-cancel",
   canceltitle: "Annulla modifiche ai dati"
   ,addParams: {
        addRowParams: {
            mtype: "POST",
            url: "clientipage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "clientipage.php?q=22",
                  successfunc: function () {
                      var $self = $(this);
                      setTimeout(function () {
                           $self.trigger("reloadGrid");
                           }, 50)} ,
                  aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
                 }
 });

// toolbar per la ricerca (filtri in testa alle colonne)
/* // non è molto estetico
jQuery("#navgridClienti").jqGrid("filterToolbar", {
    searchOperators: true,
    stringResult: true,
    searchOnEnter: false,
    defaultSearch: "eq"
});
*/

/// FUNZIONI PER GESTIRE GLI EVENTI DEL NAVIGATORE

function GestoreAfterAdd(rowid, response)
{
    // alert("Inserita riga " + rowid + "==" + response.responseText);
    var newId = $.parseJSON(response.responseText)	
}

function GestoreAfterEdit(rowid, response)
{
    // alert("Modificata riga " + rowid + "==" + response.responseText);
    // var oldId = $.parseJSON(response.responseText)	
}

function GestoreAfterDel(response, postdata, formid){
    // alert("Cancellata riga " + postdata + "==" + response.responseText);
    // formid è undefined
    // alert(postdata); // numero di riga (id)
    // alert(response.responseText); // risposta dal server in JSON
    // var oldId = $.parseJSON(response.responseText)	
}


function RigaSelezionata(id){
   selrow = id;
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridClienti').jqGrid('restoreRow',lastsel);
    }
    lastsel=id;
  }
}