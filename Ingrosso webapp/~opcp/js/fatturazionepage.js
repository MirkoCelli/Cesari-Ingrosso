var lastsel;
var selrow;
var editingRowId;
var testo;

// FATTURAZIONE - 2024-06-06

// Elenco dei valori ammessi per Zone
var elencoListino = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "fatturazionepage.php?q=5",
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
    url: "fatturazionepage.php?q=6",
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


jQuery("#navgridFatture").jqGrid({
    url:'fatturazionepage.php?q=10',
    datatype: "xml",
    colNames:['ID','CODICE CLIENTE', 'DENOMINAZIONE','NOME BREVE','LISTINO','TIPO LISTINO','INTERMEDIARIO','NOME INTERMEDIARIO','TIPO INTERM.','TIPO INTERMEDIAZIONE','ANNOTAZIONI','CLN.SP.'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
        {name:'codicecliente',index:'codicecliente', width:100, align:"left",editable:true,editoptions:{size:15}},
        {name:'denominazione',index:'denominazione', width: 250, editable: true, editoptions: { size: 250 } },
        {name:'nomebreve',index:'nomebreve', width:150,editable:true,editoptions:{size:30}},        	
        {name:'listino', index: 'listino', width: 50, editable: true, edittype: "select", editoptions: { value: elencoListino } },
        {name:'tipolistino', index: 'tipolistino', width: 100, editable: false, editoptions: { size: 100 } },
        {name:'intermediario', index: 'intermediario', width: 50, editable: true, edittype: "select", editoptions: { value: elencoIntermediario } },
        { name: 'nomeintermediario', index: 'nomeintermediario', width: 250, editable: false, editoptions: { size: 100 } },
        { name: 'tipointermediario', index: 'tipointermediario', width: 50, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'nometipointermediario', index: 'nometipointermediario', width: 100, editable: false, editoptions: { size: 100 } },
        { name: 'annotazioni', index: 'annotazioni', width: 380, editable: true, editoptions: { size: 1000 } },
        { name: 'clientespeciale', index: 'clientespeciale', width: 50, editable: false, editoptions: { size: 100 } }],
    rowNum: 25,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavFatture',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    caption:"Fatturazione Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsFatture").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"fatturazionepage.php",
    height:330,
	width: 1600,
    shrinkToFit: false,
    autowidth: false
});


jQuery("#navgridFatture").jqGrid('navGrid','#pagernavFatture',
{ edit:false,
  add:false,
  del:false,
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"fatturazionepage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridFatture").jqGrid('navButtonAdd','#pagernavFatture',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridFatture").jqGrid('excelExport', { "url": "fatturazionepage.php?q=50" });
           // var inizio = $("#inizioperiodo").val();
           // var fine = $("#fineperiodo").val();
           // var urlRiepMens = "riepilogomensileclientela.php?inizio=" + inizio + "&fine=" + fine;
           // // alert(urlRiepMens);
           // jQuery("#navgridFatture").jqGrid('excelExport', { "url": urlRiepMens });
       } 
});

// Bottone per generare la fattura per il periodo per il cliente selezionato

jQuery("#navgridFatture").jqGrid('navButtonAdd', '#pagernavFatture', {
    caption: "Fattura",
    onClickButton: function () {
        // richiedere la data della fattura
        var oggi = new Date();
        var datafattura = oggi.toLocaleDateString();
        // var datafattura = prompt("Fornire la data della fattura", oggi.toLocaleDateString());
        var dateParts = datafattura.split("/");
        // month is 0-based, that's why we need dataParts[1] - 1
        var dtfatt = new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
        const offset = dtfatt.getTimezoneOffset();
        var yourDate = new Date(dtfatt.getTime() - (offset * 60 * 1000));
        var dtfattura = yourDate.toISOString().split('T')[0];
        GenerareFatturaPerClientePerPeriodo(lastsel, $("#inizioperiodo").val(), $("#fineperiodo").val(), datafattura);
        // alert("Generata la fattura");
    }
});

jQuery("#navgridFatture").jqGrid('inlineNav','#pagernavFatture',
{
// {},
   add: false,
   edit: false,   
   save:false,
   cancel:false,
   saveicon:"ui-icon-disk", 
   savetitle: "Salva i dati correnti",
   cancelicon:"ui-icon-cancel",
   canceltitle: "Annulla modifiche ai dati"
   ,addParams: {
        addRowParams: {
            mtype: "POST",
            url: "fatturazionepage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "fatturazionepage.php?q=22",
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
jQuery("#navgridFatture").jqGrid("filterToolbar", {
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

function GestioneDatiClienteFatturazione(idcliente) {
    // 2024-07-12 - Procedura che chiama uno script php del server per avere i dati da visualizzare su:
    // elenco dettagliato giornaliero dei prodotti consegnati, elenco riassunto per gruppo delle quantità consegnate
    // elenco delle fatture nel periodo verso il cliente
    var urlDati = 'datibollettecliente.php?id=' + idcliente + '&inizio=' + $("#inizioperiodo").val() + '&fine=' + $("#fineperiodo").val();
    // alert(urlDati);
    setTimeout(function () { $('#dettagliFatturazione').load(urlDati); }, 300);
}

function RigaSelezionata(id) {
   // alert(id);
   selrow = id;
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridFatture').jqGrid('restoreRow',lastsel);
    }
       lastsel = id;
              
    }
    // qui eseguiamo la chiamata per ottenere i dati relativi al cliente per la fatturazione
    GestioneDatiClienteFatturazione(id);
}

// generazione della fattura per il cliente nel periodo

function GenerareFatturaPerClientePerPeriodo(idcliente, inizio, fine, data_fattura) {
    // alert(idcliente);
    // alert(inizio);
    // alert(fine);
    // alert(data_fattura);

    // 2024-08-10 - devo tenere conto del fatto che i clienti del rivenditore non si fatturano direttamente si fattura il cliente RIVENDITORE corrispondente
    // si fatturano singolarmente i clienti propri e i clienti degli agenti. Il cliente AGENTE non si fattura mai direttamente.
    // In fattura va solo il ripielogo totale per gruppi di tutto il venduto del periodo. Per il cliente RIVENDITORE si fa la somma dei riepiloghi dei singoli clienti associati al rivenditore
    // e questo forma la fattura per il rivenditore.
    // alert("Selrow = " + selrow);
    var rowData = jQuery("#navgridFatture").jqGrid("getRowData", selrow); // sono dei fields del record rowData: hanno lo stesso nome dei campi descritti in jqGrid
    // alert(rowData.id + ' ' + rowData.listino + ' ' + rowData.intermediario + ' ' + rowData.tipointermediario + ' ' + rowData.clientespeciale);
    var idcliente = rowData.id;
    var listino = rowData.listino;
    var intermediario = rowData.intermediario;
    var tipointermediario = rowData.tipointermediario;
    var codcliente = rowData.clientespeciale;
    // alert("TipoInterm. = " + tipointermediario);
    // alert("codcliente = " + codcliente);
    if (codcliente == "") { codcliente = null;}
    var flgRivenditore = false;
    // test per stabilire se va fatturato o meno questo cliente
    if (listino == 2 && codcliente !== null) {
        alert("il cliente e' un AGENTE, non si fattura, si fatturano i suoi clienti!");
        return false;
    }
    if (listino == 3 && codcliente !== null) {
        alert("il cliente e' un RIVENDITORE, si fattura a lui i suoi clienti!");
        flgRivenditore = true;
    }
    /*
    if (codcliente == null && intermediario !== null && listino == 2) {
        // è il cliente di un AGENTE allora si fattura direttamente il cliente
    }
    */
    if (codcliente == null && intermediario !== null && listino == 3) {
        // è il cliente di un RIVENDITORE, non si fattura mai il cliente ma solo il rivenditore
        alert("il cliente e' gestito da un RIVENDITORE, non si fattura, viene fatturato il RIVENDITORE!");
        return false;
    }
    // fine 2024-08-10

    // Devo richiedere il numero di fattura all'operatore? Altrimenti devo farmi dare l'ultimo numero fattura pe l'ano di competenza da cp_numerazioni
    var datafattura = prompt("Fornire la data della fattura", data_fattura);
    var dateParts = datafattura.split("/");
    // month is 0-based, that's why we need dataParts[1] - 1
    var dtfatt = new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
    const offset = dtfatt.getTimezoneOffset();
    var yourDate = new Date(dtfatt.getTime() - (offset * 60 * 1000));

    datafattura = yourDate.toISOString().split('T')[0];
    var numfatt = prompt("Numero di Fattura", "");
    var numerofattura = Number(numfatt);
    // alert(numerofattura);
    if (!jQuery.isNumeric(numerofattura)) {
        alert("Il numero di fattura fornito non e' valido");
        return false;
    } else {
        // deve essere intero maggiore di 0
        if (!Number.isInteger(numerofattura)) {
            alert("Il numero di fattura fornito non e' valido, deve essere intero positivo");
            return false;
        }
        if (numerofattura <= 0) {
            alert("Il numero di fattura fornito non e' valido, deve essere intero e positivo");
            return false;
        }
    }
    

    // esegue uno script che genera la fattura e mi ritorna i riferimenti della fattura (risposta in formato JSON)
    // ora chiamo uno script PHP a cui demando il compito di associare una bolla a questo ordine e poi richiedo il refresh della griglia
    urlFattura = "generafattura.php?numfatt=" + numerofattura + "&datafatt=" + datafattura + "&idcliente=" + idcliente + "&inizio=" + inizio + "&fine=" + fine;
    if (flgRivenditore) {
        urlFattura += "&flgriv=1";
    } else {
        urlFattura += "&flgriv=0";
    }

    // alert(urlFattura);
    $.ajax({
        type: "GET",
        url: urlFattura,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert("Successo");
            // alert("Data = " + data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "error" : "testo errore" , "numfatt" : "xx", "datafattura": "xxxx-xx-xx", "idfattura" : "xxx"}
            // Se status è KO vuol dire che non ha assegnato la bolla di consegna
            // Se status è OK allora la bolla di consegna è registrata
            // da fare visualizzare all'operatore

            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Problemi a generare la fattura per questo cliente: " + data["error"]); // + " (" + data["msg"] + ")");
            };

            if (data["status"] == "OK") {
                alert("Fattura " + data.numfatt + " del " + data.datafattura + " registrata per questo cliente "); //  + data["msg"]
                // riesco da fare un refresh della griglia Bolle
                // jQuery("#navgridBolle").trigger('reloadGrid');
            };
        },
        error: function (error) {
            // alert("Errore");
            alert("Errore: " + error.status + ' Testo: ' + error.statusText + ' - ' + error.responseText);
        },
        async: false
    });

    //
    return true;
}

function RivalutaPeriodo() {
    alert("Rivaluta Periodo");
    return false;
}
// mi serve avere il JQuery

function CambiaPeriodo() {
    // anno di competenza
    // alert("CambiaPeriodo");
    // determina le due date inizio mese e fine mese dell'opzione in mese
    var oggi = new Date();
    // alert(oggi.toISOString().split('T')[0]);
    var anno = oggi.getFullYear();
    var mese = $("#mese").val(); // tolgo un 1 al numero di mese (numerazione mesi da 0 fino a 11)
    // alert(mese);
    var primoDelMese = new Date();
    primoDelMese.setFullYear(anno);
    primoDelMese.setDate(1);
    primoDelMese.setMonth(mese - 1);
    // alert("DATA ISO " + primoDelMese.toISOString().split('T')[0]);
    var ultimoDelMese = new Date(primoDelMese.toISOString().split('T')[0]);
    ultimoDelMese.setMonth(mese);
    ultimoDelMese.setDate(0);
    // alert(ultimoDelMese.toISOString().split('T')[0]);

    var primostr = primoDelMese.toISOString().split('T')[0];
    // alert("set first "+primostr);
    $("#inizioperiodo").val(primostr);
    var ultimostr = ultimoDelMese.toISOString().split('T')[0];
    // alert("set last "+ultimostr);
    $("#fineperiodo").val(ultimostr);
    // alert("set done");
    return false;
}

// 2024-08-07 - Mostra il Giornaliero
function MostraGiornaliero() {
    // nel giorno indicato in giornoperiodo si elencano i totali di ogni singolo cliente (ordinati per Sequenza)
    // con la sommatoria giornaliera generale di tutti i clienti per quel giorno
    var giorno = $("#giornoperiodo").val();
    // alert("MostraGiornaliero('" + giorno + "')");
    var urlDati = 'giornalieroclientela.php?giornoperiodo=' + $("#giornoperiodo").val();
    // alert(urlDati);
    setTimeout(function () { $('#dettagliFatturazione').load(urlDati); }, 300);
    return false;
}

// 2024-08-07 - Riepilogo Generale di tutte le operazioni del periodo
function RiepilogoGeneralePeriodo() {
    // nei giorni del periodo fa la somma giornaliera di tutti i movimenti e alla fine la sommatoria generale
    // alert("RiepilogoGenerale");
    var inizio = $("#inizioperiodo").val();
    var fine = $("#fineperiodo").val();
    // alert("RiepilogoGenerale('" + inizio + "','" + fine + "')");
    var urlDati = 'riepilogogenerale.php?inizio=' + $("#inizioperiodo").val() + "&fine=" + $("#fineperiodo").val();
    // alert(urlDati);
    setTimeout(function () { $('#dettagliFatturazione').load(urlDati); }, 300);
    return false;
}

// 2024-08-15 - Generazione del file CSV con i dati del ripeilogo mensile di tutta la clientela
function GeneraCSVRiepilogoMensileClientela() {
    var inizio = $("#inizioperiodo").val();
    var fine = $("#fineperiodo").val();
    var urlRiepMens = "riepilogomensileclientela.php?inizio=" + inizio + "&fine=" + fine;
    // alert(urlRiepMens);
    jQuery("#navgridFatture").jqGrid('excelExport', { "url": urlRiepMens });
    return false;
}

// 2024-08-15 - Genera tutte le fatture del periodo indicato fra inizioperiodo e fineperiodo
function GeneraFattureMensileClientela() {
    var inizio = $("#inizioperiodo").val();
    var fine = $("#fineperiodo").val();
    var oggi = new Date();
    const yyyy = oggi.getFullYear();
    let mm = oggi.getMonth() + 1; // Months start at 0!
    let dd = oggi.getDate();
    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;
    var datafattura1 = dd + '/' + mm + '/' + yyyy;
    // var datafattura1 = oggi.toLocaleDateString();
    var datafattura2 = prompt("Fornire la data della fattura", datafattura1);
    // alert(datafattura2);
    var dateParts = datafattura2.split("/");
    // month is 0-based, that's why we need dataParts[1] - 1    
    var dtfatt = dateParts[2] + "-" + dateParts[1] + "-" + dateParts[0];
    // alert(dtfatt);
    var urlFattMens = "generafatturemensile.php?datafattura=" + dtfatt + "&inizio=" + inizio + "&fine=" + fine;
    // alert(urlFattMens);
    $.ajax({
        type: "GET",
        url: urlFattMens,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert("Successo");
            // alert("Data = " + data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "error" : "testo errore" , "numfatt" : "xx", "datafattura": "xxxx-xx-xx", "idfattura" : "xxx"}
            // Se status è KO vuol dire che non ha assegnato la bolla di consegna
            // Se status è OK allora la bolla di consegna è registrata
            // da fare visualizzare all'operatore

            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Problemi a generare le fatture per questo periodo: " + data["error"]); // + " (" + data["msg"] + ")");
            };

            if (data["status"] == "OK") {
                alert("Fatture generate per questo periodo "); //  + data["msg"]
                // riesco da fare un refresh della griglia Bolle
                // jQuery("#navgridBolle").trigger('reloadGrid');
            };
        },
        error: function (error) {
            // alert("Errore");
            alert("Errore: " + error.status + ' Testo: ' + error.statusText + ' - ' + error.responseText);
        },
        async: true
    });
    return false;
}

var adesso = new Date();
$("#mese").val(adesso.getMonth() + 1);
CambiaPeriodo();