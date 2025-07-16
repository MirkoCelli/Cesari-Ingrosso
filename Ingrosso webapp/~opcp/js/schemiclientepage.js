var lastsel;
var selrow;
var editingRowId;
var testo;

var lastselSchemi = -1;
var selrowSchemi;

var lastselGiorni = -1;
var selrowGiorni;

var lastselDettSchema = -1;
var selrowDettSchema;

var idcliente_key = null;
var idordine_key = null;
var idschema_key = null;
var idgiorno = null;

// SCHEMICLIENTE - 2024-06-06

// Elenco dei valori ammessi per Zone
var elencoListino = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "schemiclientepage.php?q=5",
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
    url: "schemiclientepage.php?q=6",
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

// alert("Clienti Schemi");

jQuery("#navgridClienti").jqGrid({
    url:'schemiclientepage.php?q=10',
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
    loadComplete: function () {
        var sdati = $("#navgridClienti").jqGrid('getDataIDs');
        var idcln = sdati[0];
        setTimeout(function () { $("#navgridClienti").jqGrid('setSelection', idcln); }, 500);        
    },
    caption:"Clienti Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsClienti").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"schemiclientepage.php",
    height:230,
	width: 1800,
    shrinkToFit: false,
    autowidth: false
});

// alert("Pager Clienti");

jQuery("#navgridClienti").jqGrid('navGrid','#pagernavClienti',
{ edit:false,
  add:false,
  del:false, // disabilitato
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"schemiclientepage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)
/*
jQuery("#navgridClienti").jqGrid('navButtonAdd','#pagernavClienti',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridClienti").jqGrid('excelExport',{"url":"clientipage.php?q=50"});
       } 
});

*/

// alert("Pager 2 Clienti");

jQuery("#navgridClienti").jqGrid('inlineNav','#pagernavClienti',
{
// {},
   add: false, // disabilitato / 
   edit: false,  // disabilitato /
   save:false,
   cancel:false,
   saveicon:"ui-icon-disk", 
   savetitle: "Salva i dati correnti",
   cancelicon:"ui-icon-cancel",
   canceltitle: "Annulla modifiche ai dati"
   ,addParams: {
        addRowParams: {
            mtype: "POST",
            url: "schemiclientepage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "schemiclientepage.php?q=22",
                  successfunc: function () {
                      var $self = $(this);
                      setTimeout(function () {
                           $self.trigger("reloadGrid");
                           }, 50)} ,
                  aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
                 }
 });

// toolbar per la ricerca (filtri in testa alle colonne)

/* ********************************************************
 *   SCHEMI DI DEFAULT PER OGNI CLIENTE
 * ********************************************************
*/

/* QUESTO BLOCCO SERVE PER DEFINIRE L'ELEMENTO SLAVE DAL PRIMO BLOCCO GRIGLIA SCHEMICLIENTE
 * 
 */
// alert("Schemi Default");

var elencoListino = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "schemaclientepage.php?q=5",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoListino += $(xml).find('rows').text();
    },
    async: false
});

var elencoResp = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "schemaclientepage.php?q=6",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoResp += $(xml).find('rows').text();
    },
    async: false
});

jQuery("#navgridSchemi").jqGrid({
    url: 'schemaclientepage.php?q=10&idcliente='+selrow, // è importante che sia filtrato per idcliente
    datatype: "xml",
    colNames: ['ID', 'CLIENTE', 'DATA INIZIO', 'DATA FINE', 'LISTINO', 'NOME LISTINO', 'RESPONSABILE', 'NOME RESPONSABILE', 'LIMITE SPESA'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
        { name: 'cliente', index: 'cliente', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'datainizio', index: 'datainizio', hidden: false, width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'datafine', index: 'datafine', hidden: false, width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'listino', index: 'listino', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoListino } },
        { name: 'nomelistino', index: 'nomelistino', width: 140, hidden: false, editable: false, editoptions: { size: 100 } },
        { name: 'responsabile', index: 'responsabile', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoResp } },
        { name: 'nomeresponsabile', index: 'nomeresponsabile', width: 140, hidden: false, editable: false, editoptions: { size: 100 } },
        { name: 'limitespesa', index: 'limitespesa', width: 100, formatter: "number", hidden: true, editable: true, editoptions: { size: 100 } }
    ],
    rowNum: 20,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavSchemi',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataSchemi,
    serializeRowData: function (postData) { postData.cliente = idcliente_key; return postData; },
    loadComplete: function () {
        /*
        var sdati = $("#navgridSchemi").jqGrid('getDataIDs');
        var idsch = sdati[0];
        if (selrowSchemi & (selrowSchemi != idsch)) {
            idsch = selrowSchemi;
        }
        setTimeout(function () {
                $("#navgridSchemi").jqGrid('setSelection', idsch);
                setTimeout(function () {
                    $("#navgridGiorni").trigger('reloadGrid');
                    $('#navgridDettSch').trigger('reloadGrid');
                }, 600);
        }, 600);   */
        return;
    },
    caption: "Schema Default per Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsSchemi").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "schemaclientepage.php",
    height: 250,
    width: 1270, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

// devo aggiungere un evento per capire se sto facendo Add, Update o Delete nella griglia SchemiCliente - 26/06/2024

// jQuery("#navgridSchemi").bind('jqGridAddEditBeforeInitData', function (e, form, oper) { alert("Azione è " + oper); });

jQuery("#navgridSchemi").jqGrid('navGrid', '#pagernavSchemi',
    {
        add: false,
        edit: false,        
        del: true,
        search: true,
        deltitle: "Cancellazione Record"
    },
    { height: 280, reloadAfterSubmit: false }, // edit options
    { height: 280, reloadAfterSubmit: false, beforeInitData: function (formID) { alert("Sono passato qui per Add"); } }, // add options
    {
        reloadAfterSubmit: true, mtype: "POST", url: "schemaclientepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

        jQuery("#navgridSchemi").jqGrid('inlineNav', '#pagernavSchemi',
            {
                // {},
                add: true,
                edit: true,
                save: true,
                cancel: true,
                saveicon: "ui-icon-disk",
                savetitle: "Salva i dati correnti",
                cancelicon: "ui-icon-cancel",
                canceltitle: "Annulla modifiche ai dati"
                , addParams: {
                    addRowParams: {
                        mtype: "POST",
                        url: "schemaclientepage.php?q=21&idcliente="+selrow,
                        keys: true,
                        successfunc: function () {
                            var $self = $(this);
                            setTimeout(function () {
                                $self.trigger("reloadGrid");
                            }, 50)
                        },
                        aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                    }
                },
                editParams: {
                    mtype: "POST", keys: true, url: "schemaclientepage.php?q=22&idcliente="+selrow,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50)
                    },
                    aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
                }
            });

var miaGriglia = jQuery("#navgridSchemi");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

///// ************************************************** /////
/////   COMANDI AGGIUNTIVI PER GESTIONE SCHEMA DEFAULT   /////
///// ************************************************** /////

/* Aggiungiamo un button per la clonazione del record degli schemi corrente con tutti i relativi dettagli identici tranne datainizio la data odierna e datafine a NULL */
/* 05/08/2024 tolto perchè non ha molto senso visto che dovrà essere unico il record degli schemi per ogni cliente
jQuery("#navgridSchemi").jqGrid('navButtonAdd', '#pagernavSchemi', {
    caption: "Clona",
    onClickButton: function () {
        // qui eseguo uno script del server che mi ritorna l'id del nuovo schema che poi alla fine selezionerò per ulteriori modifiche
        var giorniSett = ""; // non può avere il null
        jQuery.getJSON("schemiclientepage.php?q=44&id=" + idschema_key, null, function (data) {
            // alert(JSON.stringify(data));
            giorniSett += JSON.stringify(data);
            $.each(data, function (i, field) {
                // alert(i + " -- " + field);
                if (i == "id") {
                    // faccio il refresh della griglia e 
                    // alert("Refresh su"+field);                    
                    setTimeout(function () {
                        $("#navgridSchemi").trigger("realodGrid");
                        $("#navgridSchemi").jqGrid('setSelection', field);
                    }, 1400);                    
                }
            });
        })
    }
});
*/

/* Aggiungiamo un button per la generazione dei record in ordinecliente a partire dallo schema corrente per il periodo da datainizio a datafine (se è datafine = NULL si segnala che non si può fare senza una datafine) */

/* -- 31/07/2024 - escluso perchè non serve
jQuery("#navgridSchemi").jqGrid('navButtonAdd', '#pagernavSchemi', {
    caption: "Genera",
    onClickButton: function () {
        // qui eseguo uno script del server che mi ritorna l'id del nuovo schema che poi alla fine selezionerò per ulteriori modifiche
        // alert("Genera Ordini");
        var myGrid = $('#navgridSchemi');
        var selRowId = myGrid.jqGrid('getGridParam', 'selrow');
        var datafine = myGrid.jqGrid('getCell', selRowId, 'datafine');
        var datainizio = myGrid.jqGrid('getCell', selRowId, 'datainizio');
        var schema = myGrid.jqGrid('getCell', selRowId, 'id');
        var cliente = myGrid.jqGrid('getCell', selRowId, 'cliente');
        // devo convertire le date in formato ISO
        dtfine = datafine.split("/").reverse().join("-");
        dtinizio = datainizio.split("/").reverse().join("-");
        // alert(dtinizio + " " + dtfine);
        // alert("passo 1");
        / * 31/07/2024 - la data fine non è obbligatoria
        if (dtfine == null) {
            alert("La data fine deve essere valorizzata per procedere con la generazione degli ordini");
            return;
        }
        * /
        if (dtinizio > dtfine) {
            alert("La data fine non può essere antecedente alla data inizio, correggere");
            return;
        }
        // alert("Passo 2");
        // verifichiamo che non ci siano già ordini per il cliente nel periodo indicato
        urlCheckPeriodo = "schemaclientepage.php?q=40&cliente=" + cliente + "&datainizio=" + dtinizio + "&datafine="+dtfine;
        jQuery.getJSON(urlCheckPeriodo, null, function (data) {
            $.each(data, function (i, field) {
                // alert(i + " -- " + field);
                if (i == "stato") {
                    if (field == "OK") {
                        // possiamo generare gli ordini legati allo schema selezionato
                        // alert("Possiamo generare gli ordini dello schema " + schema + " per il cliente " + cliente);
                        generareOrdiniDaSchema(cliente, schema, dtinizio, dtfine, selRowId);
                    }
                }
                if (i == "errore") {
                    if (!(field == "")) {
                        alert("Errore: " + field + " per lo schema " + schema + " per il cliente " + cliente);
                    }
                }
            });
        })
    }
});
*/

/* Aggiungiamo un button per la cancellazione dei record in ordinecliente a partire dallo schema corrente per il periodo da datainizio a datafine (se è datafine = NULL si segnala che non si può fare senza una datafine) 
   ma solo che non ci sono dettagli modificati per gli ordini del periodo indicato, altrimenti non può continuare
*/

/* -- 31/07/2024 escluso perchè non serve
jQuery("#navgridSchemi").jqGrid('navButtonAdd', '#pagernavSchemi', {
    caption: "Cancella",
    onClickButton: function () {
        // qui eseguo uno script del server che mi ritorna l'id del nuovo schema che poi alla fine selezionerò per ulteriori modifiche
        alert("Cancella Ordini");
        var myGrid = $('#navgridSchemi');
        var selRowId = myGrid.jqGrid('getGridParam', 'selrow');
        var datafine = myGrid.jqGrid('getCell', selRowId, 'datafine');
        var datainizio = myGrid.jqGrid('getCell', selRowId, 'datainizio');
        var schema = myGrid.jqGrid('getCell', selRowId, 'id');
        var cliente = myGrid.jqGrid('getCell', selRowId, 'cliente');
        // devo convertire le date in formato ISO
        dtfine = datafine.split("/").reverse().join("-");
        dtinizio = datainizio.split("/").reverse().join("-");
        alert(dtinizio + " " + dtfine);
        // alert("passo 1");
        if (dtfine == null) {
            alert("La data fine deve essere valorizzata per procedere con la generazione degli ordini");
            return;
        }
        if (dtinizio > dtfine) {
            alert("La data fine non può essere antecedente alla data inizio, correggere");
            return;
        }
        // alert("Passo 2");
        // verifichiamo che non ci siano già ordini per il cliente nel periodo indicato
        urlCheckPeriodo = "schemaclientepage.php?q=41&cliente=" + cliente + "&datainizio=" + dtinizio + "&datafine=" + dtfine;
        jQuery.getJSON(urlCheckPeriodo, null, function (data) {
            $.each(data, function (i, field) {
                // alert(i + " -- " + field);
                if (i == "stato") {
                    if (field == "OK") {
                        // possiamo generare gli ordini legati allo schema selezionato
                        // alert("Possiamo generare gli ordini dello schema " + schema + " per il cliente " + cliente);
                        cancellareOrdiniDaSchema(cliente, schema, dtinizio, dtfine, selRowId);
                    }
                }
                if (i == "errore") {
                    if (!(field == "")) {
                        alert("Errore: " + field + " per lo schema " + schema + " per il cliente " + cliente);
                    }
                }
            });
        })
    }
});
*/

/* Aggiungiamo un button per la creazione di uno schema vuoto di default con tutti i prodotti dal martedì alla domenica con quantità a zero
 * Ci deve pensare l'utente o l'operatore a indicare le quantità desiderate dal cliente
*/
/* -- 05/08/2024 - non serve più il bottone perchè viene inserito in automatico alla creazione dello schema nuovo
jQuery("#navgridSchemi").jqGrid('navButtonAdd', '#pagernavSchemi', {
    caption: "Schema N.",
    onClickButton: function () {
        // qui eseguo uno script del server che mi ritorna l'id del nuovo schema che poi alla fine selezionerò per ulteriori modifiche
        // alert("Schema Nuovo");
        var myGrid = $('#navgridSchemi');
        var selRowId = myGrid.jqGrid('getGridParam', 'selrow');
        var datafine = myGrid.jqGrid('getCell', selRowId, 'datafine');
        var datainizio = myGrid.jqGrid('getCell', selRowId, 'datainizio');
        var schema = myGrid.jqGrid('getCell', selRowId, 'id');
        var cliente = myGrid.jqGrid('getCell', selRowId, 'cliente');
        // devo convertire le date in formato ISO
        dtfine = datafine.split("/").reverse().join("-");
        dtinizio = datainizio.split("/").reverse().join("-");
        // alert(dtinizio + " " + dtfine);
        // alert("passo 1");
        if (dtfine == null) {
            alert("La data fine deve essere valorizzata per procedere con la generazione degli ordini");
            return;
        }
        if (dtinizio > dtfine) {
            alert("La data fine non può essere antecedente alla data inizio, correggere");
            return;
        }
        // alert("Passo 2");
        // eseguew la generazione dello schema con tutti i prodotti con quantità iniziale a zero per tutti i giorni lavorativi dal martedì alla domenica

        urlCheckPeriodo = "schemaclientepage.php?q=47&cliente=" + cliente + "&schema=" + idschema_key + "&datainizio=" + dtinizio + "&datafine=" + dtfine;
        jQuery.getJSON(urlCheckPeriodo, null, function (data) {
            $.each(data, function (i, field) {
                // alert(i + " -- " + field);
                if (i == "stato") {
                    if (field == "OK") {
                        // possiamo generare gli ordini legati allo schema selezionato
                        // alert("Possiamo generare gli ordini dello schema " + schema + " per il cliente " + cliente);
                        // cancellareOrdiniDaSchema(cliente, schema, dtinizio, dtfine, selRowId);
                        alert("Abbiamo inserito lo schema ordinativi per lo schema " + schema + " per il cliente " + cliente);
                    }
                }
                if (i == "errore") {
                    if (!(field == "")) {
                        alert("Errore: " + field + " per lo schema " + schema + " per il cliente " + cliente);
                    }
                }
            });
        })
    }
});
*/

/* BLOCCO DI SELEZIONE DEI GIORNI DELLA SETTIMANA
 */
// alert("Giorni");

/* ***********************************************************
 * GIORNI DELLA SETTIMANA
 * ***********************************************************
*/

jQuery("#navgridGiorni").jqGrid({
    url: 'dettschemaclientepage.php?q=11',
    datatype: "xml",
    colNames: ['ID', 'GIORNO SETTIMANA'
    ],
    colModel: [
        { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
        { name: 'giorno', index: 'giorno', width: 180, align: "left", editable: false, editoptions: { size: 15 } }
    ],
    rowNum: 20,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavGiorni',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataGiorni,
    loadComplete: function () {
        /*
        var sdati = $("#navgridGiorni").jqGrid('getDataIDs');
        var idgg = sdati[0];
        setTimeout(function () {
            $("#navgridGiorni").jqGrid('setSelection', idgg);
            setTimeeout(function () {
                $('#navgridDettSchema').trigger('reloadGrid');
            },1600)
            
        }, 1600);*/
        return;
    },
    caption: "Giorni della Settimana",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsGiorni").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettschemaclientepage.php",
    height: 550,
    width: 250, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

//alert("Giorni 2");

jQuery("#navgridGiorni").jqGrid('navGrid', '#pagernavGiorni',
    {
        edit: false,
        add: false,
        del: true,
        search: false,
        deltitle: "Cancellazione Record"
    },
    { height: 280, reloadAfterSubmit: false }, // edit options
    { height: 280, reloadAfterSubmit: false }, // add options
    {
        reloadAfterSubmit: true, mtype: "POST", url: "dettschemaclientepage.php?q=123",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

//alert("Giorni 3");

jQuery("#navgridGiorni").jqGrid('inlineNav', '#pagernavGiorni',
    {
        // {},
        add: false,
        edit: false,
        save: false,
        cancel: false,
        saveicon: "ui-icon-disk",
        savetitle: "Salva i dati correnti",
        cancelicon: "ui-icon-cancel",
        canceltitle: "Annulla modifiche ai dati"
        , addParams: {
            addRowParams: {
                mtype: "POST",
                url: "dettschemaclientepage.php?q=121&idschema=" + selrowSchemi,
                keys: true,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
            }
        },
        editParams: {
            mtype: "POST", keys: true, url: "dettschemaclientepage.php?q=122&idschema=" + selrowSchemi,
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50)
            },
            aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
        }
    });

// alert("Giorni 4");

var miaGriglia = jQuery("#navgridGiorni");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");



/* BLOCCO DEI DETTAGLI SCHEMA DEFAULT
 */
// alert("Dettagli Schema");

/* **********************************************************************
 * DETTAGLI DEL GIORNO DELLA SETTIMANA PER LO SCHEMA DEFAULT DEL CLIENTE
 * **********************************************************************
*/

var giorniSett = ""; // non può avere il null
$.ajax({
    type: "GET",
    url: "dettschemaclientepage.php?q=4",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        giorniSett += $(xml).find('rows').text();
    },
    async: false
});

var elencoResp = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettschemaclientepage.php?q=5",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoResp += $(xml).find('rows').text();
    },
    async: false
});

// qui dobbiamo creare l'elenco dei prodotti completo suddiviso per gruppi
var elencoProdotti = ":;";
$.ajax({
    type: "GET",
    url: "dettschemaclientepage.php?q=6",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoProdotti += $(xml).find('rows').text();
    },
    async: false
});


jQuery("#navgridDettSchema").jqGrid({
    url: 'dettschemaclientepage.php?q=10',
    datatype: "xml",
    colNames: ['ID','SCHEMA','GS','GIORNO SETTIMANA','SEQUENZA','PRODOTTO','NOME PRODOTTO','QUANTITA','UNITA MISURA'
    ],
    colModel: [
        { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'schema', index: 'ordine', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'giornosettimana', index: 'giornosettimana', width: 80, hidden: true, editable: true, edittype: "select", editoptions: { value: giorniSett } },
        { name: 'nomegs', index: 'nomegs', width: 180, align: "left", hidden: true, editable: false, editoptions: { size: 15 } },
        { name: 'sequenza', index: 'sequenza', width: 60, align: "left", editable: true, editoptions: { size: 15 } },
        { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
        { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'quantita', index: 'quantita', formatter: "number", width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'unitamisura', index: 'unitamisura', width: 60, hidden: false, editable: false, editoptions: { size: 100 } }
    ],
    rowNum: 20,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavDettSchema',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataDettOrd,
    serializeRowData: function (postData) { postData.schema = idschema_key; postData.giornosettimana = idgiorno; return postData; },
    caption: "Dettaglio Schema Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettSchema").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettschemaclientepage.php",
    height: 550,
    width: 1300, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

jQuery("#navgridDettSchema").jqGrid('navGrid', '#pagernavDettSchema',
    {
        edit: false,
        add: false,
        del: true,
        search: true,
        deltitle: "Cancellazione Record"
    },
    { height: 280, reloadAfterSubmit: false }, // edit options
    { height: 280, reloadAfterSubmit: false }, // add options
    {
        reloadAfterSubmit: true, mtype: "POST", url: "dettschemaclientepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

jQuery("#navgridDettSchema").jqGrid('inlineNav', '#pagernavDettSchema',
    {
        // {},
        add: true,
        edit: true,
        save: true,
        cancel: true,
        saveicon: "ui-icon-disk",
        savetitle: "Salva i dati correnti",
        cancelicon: "ui-icon-cancel",
        canceltitle: "Annulla modifiche ai dati"
        , addParams: {
            addRowParams: {
                mtype: "POST",
                url: "dettschemaclientepage.php?q=21&idschema=" + idschema_key,
                keys: true,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
            }
        },
        editParams: {
            mtype: "POST", keys: true, url: "dettschemaclientepage.php?q=22&idschema=" + idschema_key,
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50)
            },
            aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
        }
    });

var miaGriglia = jQuery("#navgridDettSchema");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

// alert("Completato");

/* Funzione di supporto per indicare l'ordine di riferimento corrente alle varie sottogriglie */
function SetIdCliente(nomegriglia, nomepager, idcliente) {
    // alert("SetIdCliente " + nomegriglia + " cln." + idcliente);
    ScriviStatus("SetIdCliente " + idcliente);
    idcliente_key = idcliente;
    jQuery(nomegriglia).jqGrid({
        url: 'schemaclientepage.php?q=10&idcliente=' + idcliente_key,
        datatype: "xml",
        colNames: ['ID', 'CLIENTE', 'DATA INIZIO', 'DATA FINE', 'LISTINO', 'NOME LISTINO', 'RESPONSABILE', 'NOME RESPONSABILE', 'LIMITE SPESA'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
            { name: 'cliente', index: 'cliente', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
            { name: 'datainizio', index: 'datainizio', hidden: false, width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
            { name: 'datafine', index: 'datafine', hidden: false, width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
            { name: 'listino', index: 'listino', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoListino } },
            { name: 'nomelistino', index: 'nomelistino', width: 140, hidden: false, editable: false, editoptions: { size: 100 } },
            { name: 'responsabile', index: 'responsabile', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoResp } },
            { name: 'nomeresponsabile', index: 'nomeresponsabile', width: 140, hidden: false, editable: false, editoptions: { size: 100 } },
            { name: 'limitespesa', index: 'limitespesa', width: 100, formatter: "number", hidden: false, editable: true, editoptions: { size: 100 } }
        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavSchemi',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataSchemi,
        serializeRowData: function (postData) { postData.cliente = idcliente_key; return postData; },
        loadComplete: function () {
            /*
            var sdati = $("#navgridSchemi").jqGrid('getDataIDs');
            var idsch = sdati[0];
            if (selrowSchemi & (selrowSchemi != idsch)) {
                idsch = selrowSchemi;
            }
            setTimeout(function () {
                $("#navgridSchemi").jqGrid('setSelection', idsch);
                setTimeout(function () {
                    $("#navgridGiorni").trigger('reloadGrid');
                    $('#navgridDettSch').trigger('reloadGrid');
                }, 600);
            }, 600);*/
            return;
        },
        caption: "Schema Default per Cliente Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsSchemi").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "schemaclientepage.php",
        height: 250,
        width: 1070, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false
    });
    // jQuery(nomegriglia).trigger('reloadGrid');

    // ScriviStatus('SetIdClienti Schemi Grid ' + idcliente);
    // alert("finito 1");

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavSchemi',
        {
            add: false,
            edit: false,            
            del: true,
            search: true,
            deltitle: "Cancellazione Record"
        },
        { height: 280, reloadAfterSubmit: false }, // edit options
        { height: 280, reloadAfterSubmit: false }, // add options
        {
            reloadAfterSubmit: true, mtype: "POST", url: "schemaclientepage.php?q=23&idcliente=" + idcliente,
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );
    
    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavSchemi',
        {
            // {},
            add: true,
            edit: true,
            save: true,
            cancel: true,
            saveicon: "ui-icon-disk",
            savetitle: "Salva i dati correnti",
            cancelicon: "ui-icon-cancel",
            canceltitle: "Annulla modifiche ai dati"
            , addParams: {
                addRowParams: {
                    mtype: "POST",
                    url: "schemaclientepage.php?q=21&idcliente=" + selrow,
                    keys: true,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50)
                    },
                    aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                }
            },
            editParams: {
                mtype: "POST", keys: true, url: "schemaclientepage.php?q=22&idcliente=" + selrow,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });
    
    var miaGriglia = jQuery("#navgridSchemi");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

    // non vanno aggiunti i button extra

    // jQuery(nomegriglia).trigger("reloadGrid");
    // ScriviStatus("SetIdCliente fine " + idcliente);
}

function SetIdOrdine(nomegriglia, nomepager, idordine, idgiorno) {
    ScriviStatus("SetIdOrdine di " + idordine + " giorno " + idgiorno);
    jQuery(nomegriglia).jqGrid({
        url: 'dettschemaclientepage.php?q=10&idschema='+idordine,
        datatype: "xml",
        colNames: ['ID', 'SCHEMA', 'GS', 'GIORNO SETTIMANA', 'SEQUENZA', 'PRODOTTO', 'NOME PRODOTTO', 'QUANTITA', 'UNITA MISURA'
        ],
        colModel: [
            { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'schema', index: 'ordine', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
            { name: 'giornosettimana', index: 'giornosettimana', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: giorniSett } },
            { name: 'nomegs', index: 'nomegs', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'sequenza', index: 'sequenza', width: 60, align: "left", editable: true, editoptions: { size: 15 } },
            { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'quantita', index: 'quantita', formatter: "number", width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'unitamisura', index: 'unitamisura', width: 60, hidden: false, editable: false, editoptions: { size: 100 } }
        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavDettSchema',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataDettOrd,
        serializeRowData: function (postData) { postData.schema = idschema_key; postData.giornosettimana = idgiorno; return postData; },
        caption: "Dettaglio Schema Cliente Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettSchema").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettschemaclientepage.php",
        height: 550,
        width: 1300, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false
    });

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavDettSchema',
        {
            add: false,
            edit: false,            
            del: true,
            search: false,
            deltitle: "Cancellazione Record"
        },
        { height: 280, reloadAfterSubmit: false }, // edit options
        { height: 280, reloadAfterSubmit: false }, // add options
        {
            reloadAfterSubmit: true, mtype: "POST", url: "dettschemaclientepage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavDettSchema',
        {
            // {},
            add: true,
            edit: true,
            save: true,
            cancel: true,
            saveicon: "ui-icon-disk",
            savetitle: "Salva i dati correnti",
            cancelicon: "ui-icon-cancel",
            canceltitle: "Annulla modifiche ai dati"
            , addParams: {
                addRowParams: {
                    mtype: "POST",
                    url: "dettschemaclientepage.php?q=21&idschema=" + idordine + '&giorno=' + idgiorno,
                    keys: true,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50)
                    },
                    aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                }
            },
            editParams: {
                mtype: "POST", keys: true, url: "dettschemaclientepage.php?q=22&idschema="+idordine+'&giorno='+idgiorno,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery("#navgridDettSchema");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

    // jQuery(nomegriglia).trigger("reloadGrid");
}

function SetIdGiorno(nomegriglia, nomepager, idschema, idgiorno) {
    ScriviStatus("SetIdGiorno " + nomegriglia + " per schema " + idschema + " nel giorno " + idgiorno);
    jQuery(nomegriglia).jqGrid({
        url: 'dettschemaclientepage.php?q=11',
        datatype: "xml",
        colNames: ['ID', 'GIORNO SETTIMANA'
        ],
        colModel: [
            { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
            { name: 'giorno', index: 'giorno', width: 180, align: "left", editable: false, editoptions: { size: 15 } }
        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavGiorni',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataGiorni,        
        loadComplete: function () {
            /*
            var sdati = $("#navgridGiorni").jqGrid('getDataIDs');
            var idgg = sdati[0];
            setTimeout(function () {
                $("#navgridGiorni").jqGrid('setSelection', idgg);
                setTimeeout(function () {
                    $('#navgridDettSchema').trigger('reloadGrid');
                }, 1600)

            }, 1600);*/
            return;
        },
        caption: "Giorni della Settimana",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsGiorni").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettschemaclientepage.php",
        height: 550,
        width: 250, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false
    });

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavGiorni',
        {
            edit: false,
            add: false,
            del: true,
            search: false,
            deltitle: "Cancellazione Record"
        },
        { height: 280, reloadAfterSubmit: false }, // edit options
        { height: 280, reloadAfterSubmit: false }, // add options
        {
            reloadAfterSubmit: true, mtype: "POST", url: "dettschemaclientepage.php?q=123",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavGiorni',
        {
            // {},
            add: false,
            edit: false,
            save: false,
            cancel: false,
            saveicon: "ui-icon-disk",
            savetitle: "Salva i dati correnti",
            cancelicon: "ui-icon-cancel",
            canceltitle: "Annulla modifiche ai dati"
            , addParams: {
                addRowParams: {
                    mtype: "POST",
                    url: "dettschemaclientepage.php?q=121",
                    keys: true,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50)
                    },
                    aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                }
            },
            editParams: {
                mtype: "POST", keys: true, url: "dettschemaclientepage.php?q=122",
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery("#navgridDettSchema");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

    // jQuery(nomegriglia).trigger("reloadGrid");
}

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
    idcliente_key = id;    
    // alert("Idcliente = " + idcliente_key);
    ScriviStatus("RigaSelezionata = " + id);
   if(id /* && id !== lastsel */){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridClienti').jqGrid('restoreRow',lastsel);
    }
    // qui dobbiamo sistemare la tabella degli ordini associati a questo cliente (ripulire i dettagli da dettagli ordine)
       if (id != null) {
        ScriviStatus("1) Blocco RigaSelezionata " + id);
        jQuery("#navgridSchemi").jqGrid('setGridParam', { url: 'schemaclientepage.php?q=10&idcliente=' + id, page: 1 });
        SetIdCliente("#navgridSchemi", '#pagernavSchemi', id);
        ScriviStatus("1) Blocco RigaSelezionata SetIdCliente " + id);
        jQuery("#fieldsSchemi").html("");
        // jQuery("#navgridSchemi").trigger('clearGridData');
        jQuery("#navgridSchemi").jqGrid('setCaption', 'Schemi Default per Cliente - ' + id).trigger('reloadGrid');
        ScriviStatus("1) Blocco RigaSelezionata Schemi " + id);
        setTimeout(function () {
            // facciamo eseguire la ricerca dell'indice della prima riga e poi selezioniamo al prima riga
            var sdati = $("#navgridSchemi").jqGrid('getDataIDs');
            var idsch = sdati[0];
            if (selrowSchemi & (selrowSchemi != idsch)) {
                idsch = selrowSchemi;
            }
            $("#navgridSchemi").jqGrid('setSelection', idsch);
            setTimeout(function () {
                var sdati1 = $("#navgridSchemi").jqGrid('getDataIDs');
                var idsch1 = sdati1[0];
                idsch1 = selrowSchemi;
                $("#navgridSchemi").jqGrid('setSelection', idsch1);
            }, 800); // lo faccio due volte
        }, 600);
        SetIdGiorno("#navgridGiorni", '#pagernavGiorni',0, 1);
        ScriviStatus("1) Blocco RigaSelezionata SetIdGiorno " + 1);
        jQuery("#fieldsGiorni").html("");
        // jQuery("#navgridGiorni").trigger('clearGridData');
        jQuery("#navgridGiorni").jqGrid('setCaption', 'Giorni della Settimana - ' + 1).trigger('reloadGrid');        
           ScriviStatus("1) Blocco RigaSelezionata Giorni " + id);
        jQuery("#navgridDettSchema").jqGrid('setGridParam', { url: 'dettschemaclientepage.php?q=10&idschema='+idschema_key+'&idgiorno=1', page: 1 });
           SetIdOrdine("#navgridDettSchema", '#pagernavDettSchema', idschema_key, 1);
           ScriviStatus("1) Blocco RigaSelezionata SetIdOrdine " + 0);
        jQuery("#fieldsDettSchema").html("");
        // jQuery("#navgridDettSchema").trigger('clearGridData');
        jQuery("#navgridDettSchema").jqGrid('setCaption', 'Dettaglio Schema per Cliente - ' + id).trigger('reloadGrid');
           ScriviStatus("1) Blocco RigaSelezionata DettSchema " + id);
        // setTimeout(function () { jQuery("#navgridDettSchema").trigger('reloadGrid'); ScriviStatus("URL = "+ $("#navgridDettSchema").jqGrid("getGridParam","url")); }, 1500); // necessario il timer per il refresh della griglia
    }
    lastsel=id;
   }
   // alert(selrow);
}

function RigaSelezionataSchemi(id) {
    // alert("Schema = " + id + " per cln. " + idcliente_key);
    ScriviStatus("RigaSelezionataSchemi = " + id);
    selrowSchemi = id;
    idordine_key = id;
    idschema_key = id;
    testo = String(id);
    if (testo.includes('jq')) {
        // alert("Sto faccendo Add in Schemi:" + id);
        return;
    }
    // alert("idschema=" + idschema_key);
    if (id && id !== lastselSchemi) {
        // alert("Schema "+idschema_key);

        if (typeof lastselSchemi != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridSchemi').jqGrid('restoreRow', lastselSchemi);
        }
        // qui dobbiamo sistemare la tabella dei dettagli dell'ordine associato a questo cliente (ripulire i dettagli da dettagli ordine)        
        
        if (id != null) {
            ScriviStatus("Blocco RigaSelezionataSchemi " + id);
            
            jQuery("#navgridSchemi").jqGrid('setGridParam', { url: "schemaclientepage.php?q=10&idcliente=" + idcliente_key, page: 1 });
            SetIdCliente("#navgridSchemi", '#pagernavSchemi', idcliente_key);
            jQuery("#fieldsSchemi").html("");            

            // jQuery("#navgridSchemi").trigger("clearGridData");
            jQuery("#navgridSchemi").jqGrid('setCaption', "Schemi Default per Cliente - " + id).trigger('reloadGrid');
            ScriviStatus("2) fine Blocco RigaSelezionataSchemi : Schema " + id);
            // alert("visto dett. Ordine per "+id);
            setTimeout(function () {
                // facciamo eseguire la ricerca dell'indice della prima riga e poi selezioniamo al prima riga
                var sdati = $("#navgridGiorni").jqGrid('getDataIDs');
                var idgs = sdati[0];
                $("#navgridGiorni").jqGrid('setSelection', idgs);
                setTimeout(function () { $("#navgridGiorni").jqGrid('setSelection', idgs); }, 800); // lo faccio due volte
            }, 500);            
            // la carico con le righe valide
            ScriviStatus("2) Blocco RigaSelezionataSchemi (SetIdCliente)" + id);
            jQuery("#navgridGiorni").jqGrid('setGridParam', { url: "schemaclientepage.php?q=11&idgiorno=1", page: 1 });            
            SetIdGiorno("#navgridGiorni", '#pagernavGiorni', id, 1);
            jQuery("#fieldsGiorni").html("");
            // jQuery("#navgridGiorni").trigger("clearGridData");
            jQuery("#navgridGiorni").jqGrid('setCaption', "Giorni della Settimana - " + id).trigger('reloadGrid');
            ScriviStatus("2) Blocco RigaSelezionataSchemi (SetIdGiorno)" + id);
            jQuery("#navgridDettSchema").jqGrid('setGridParam', { url: "dettschemaclientepage.php?q=10&idschema="+idschema_key+"&idgiorno=1", page: 1 });
            SetIdOrdine("#navgridDettSchema", '#pagernavDettSchema', id, 1);
            jQuery("#fieldsDettSchema").html("");
            // jQuery("#navgridDettSchema").trigger("clearGridData");
            jQuery("#navgridDettSchema").jqGrid('setCaption', "Dettaglio Schema per Cliente - " + id).trigger('reloadGrid');
            // setTimeout(function () { jQuery("#navgridDettSchema").trigger('reloadGrid'); ScriviStatus("URL = " + $("#navgridDettSchema").jqGrid("getGridParam", "url")); }, 1500); // necessario il timer per il refresh della griglia
            ScriviStatus("2) Blocco RigaSelezionataSchemi (SetIdOrdine) " + id);
            // riselezionare la riga appena gestita
            setTimeout(function () {
                idsch1 = idschema_key;
                $("#navgridSchemi").jqGrid('setSelection', idsch1);
            }, 800); // lo faccio due volte
        }
        
        lastselSchemi = id;
    }
}

function RigaSelezionataGiorni(id) {
    // alert("Giorno Settimana = " + id + " schema = " + idschema_key);
    ScriviStatus("RigaSelezionataGiorni = " + id);
    selrowGiorni = id;
    idgiorno = id;
    if (id && id !== lastselGiorni) {

        if (typeof lastselGiorni != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridGiorni').jqGrid('restoreRow', lastselDettSchema);
        }
        if (id != null) {

            // alert("visto dett. Ordine per "+id);
            // scelgo un giorno diverso da  idgiorno
            // la carico con le righe valide            
            ScriviStatus("Blocco RigaSelezionataGiorni " + id);
            jQuery("#navgridDettSchema").jqGrid('setGridParam', { url: "dettschemaclientepage.php?q=10&idschema=" + idschema_key + '&idgiorno=' + idgiorno, page: 1 });
            SetIdOrdine("#navgridDettSchema", '#pagernavDettSchema', idschema_key, idgiorno);
            jQuery("#fieldsDettSchema").html("");
            ScriviStatus("3) Blocco RigaSelezionataGiorni : Giorno (setIdOrdine) schema " + idschema_key +" giorno " + id);
            // jQuery("#navgridDettSchema").trigger("clearGridData");
            jQuery("#navgridDettSchema").jqGrid('setCaption', "Dettaglio Schema per Cliente - " + id).trigger('reloadGrid');
            setTimeout(function () {
                // facciamo eseguire la ricerca dell'indice della prima riga e poi selezioniamo al prima riga
                var sdati = $("#navgridDettSchema").jqGrid('getDataIDs');
                var iddsch = sdati[0];
                $("#navgridDettSchema").jqGrid('setSelection', iddsch);
                setTimeout(function () { $("#navgridDettSchema").jqGrid('setSelection', iddsch); }, 800); // lo faccio due volte
            }, 600);
        }
        lastselGiorni = id;
    }
}

function RigaSelezionataDettOrd(id) {
    // alert("Dettaglio Ordine = " + id);
    ScriviStatus("RigaSelezionataDettOrd = " + id);
    selrowDettSchema = id;
    if (id && id !== lastselDettSchema) {
        ScriviStatus("Blocco RigaSelezionataDettOrd " + id);
        if (typeof lastselDettSchema != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettSchema').jqGrid('restoreRow', lastselDettSchema);
        }
        lastselDettSchema = id;
    }
}

function SistemareDatiOrdini(postData) {
    // controllo ache i campi dell'ordine siano valorizzati correttamente
    // data la data di inserimento dell'ordine attuale calcola la data consegna se ci sono i gg produzione
    // oppure calcola i gg produzione se c'è la data consegna come differenza con la data di inserimento
    return postData;
}

function ScriviStatus(testo) {
    // 05/08/2024 disabilitato
    // $("#status").val($("#status").val() + "-" + testo + "\n");
}

// nuove funzionalità per gestire operazioni multiple

function generareOrdiniDaSchema(cliente, schema, dtinizio, dtfine, selRowId) {
    // qui eseguiamo il comando di generazione degli ordini dallo schema
    // alert("generareOrdiniDaSchema");
    urlGeneraPeriodo = "schemaclientepage.php?q=45&cliente=" + cliente + "&schema=" + schema + "&datainizio=" + dtinizio + "&datafine=" + dtfine;
    jQuery.getJSON(urlGeneraPeriodo, null, function (data) {
        $.each(data, function (i, field) {
            // alert(i + " -- " + field);
            if (i == "stato") {
                if (field == "OK") {
                    // possiamo generare gli ordini legati allo schema selezionato
                    alert("Abbiamo generato gli ordini dello schema " + schema + " per il cliente " + cliente);
                }
            }
            if (i == "errore") {
                if (!(field == "")) {
                    alert("Errore: " + field + " per lo schema " + schema + " per il cliente " + cliente);
                }
            }
        });
    });
}

function cancellareOrdiniDaSchema(cliente, schema, dtinizio, dtfine, selRowId) {
    // qui eseguiamo il comando di generazione degli ordini dallo schema
    // alert("generareOrdiniDaSchema");
    urlGeneraPeriodo = "schemaclientepage.php?q=46&cliente=" + cliente + "&schema=" + schema + "&datainizio=" + dtinizio + "&datafine=" + dtfine;
    jQuery.getJSON(urlGeneraPeriodo, null, function (data) {
        $.each(data, function (i, field) {
            // alert(i + " -- " + field);
            if (i == "stato") {
                if (field == "OK") {
                    // possiamo generare gli ordini legati allo schema selezionato
                    alert("Abbiamo cancellato gli ordini dello schema " + schema + " per il cliente " + cliente);
                }
            }
            if (i == "errore") {
                if (!(field == "")) {
                    alert("Errore: " + field + " per lo schema " + schema + " per il cliente " + cliente);
                }
            }
        });
    });
}