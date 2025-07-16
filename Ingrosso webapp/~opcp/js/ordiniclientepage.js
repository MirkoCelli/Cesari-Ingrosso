var lastsel;
var selrow;
var editingRowId;
var testo;

var lastselOrdini = -1;
var selrowOrdini;

var lastselDettOrd = -1;
var selrowDettOrd;

// funzioni di supporto

function stringToDate(_date, _format, _delimiter) {
    // alert(_date);
    var formatLowerCase = _format.toLowerCase();
    var formatItems = formatLowerCase.split(_delimiter);
    var dateItems = _date.split(_delimiter);
    var monthIndex = formatItems.indexOf("mm");
    var dayIndex = formatItems.indexOf("dd");
    var yearIndex = formatItems.indexOf("yyyy");
    var year = dateItems[yearIndex];
    var month = parseInt(dateItems[monthIndex]);
    month -= 1;
    var aaaa = dateItems[yearIndex].padStart(4,"0");
    var mm = dateItems[monthIndex].padStart(2,"0");
    var gg = dateItems[dayIndex].padStart(2,"0");
    var data_ISO = aaaa + "-" + mm + "-" + gg;
    var formatedDate = new Date(data_ISO);    
    return formatedDate;
}
/* esempi di uso della funzione sopra esposta
stringToDate("17/9/2014", "dd/MM/yyyy", "/");
stringToDate("9/17/2014", "mm/dd/yyyy", "/");
stringToDate("9-17-2014", "mm-dd-yyyy", "-");
*/

// ORDINICLIENTE - 2024-06-06

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

// alert("Clienti Ordini");

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
    loadComplete: function () {
        var sdati = $("#navgridClienti").jqGrid('getDataIDs');
        var idcln = sdati[0];
        setTimeout(function () {
            $("#navgridClienti").jqGrid('setSelection', idcln);
            jQuery("#navgridOrdini").jqGrid("clearGridData");
            jQuery("#navgridDettOrd").jqGrid("clearGridData");
            setTimeout(function () {                
                $('#navgridOrdini').trigger('reloadGrid');
                $('#navgridDettOrd').trigger('reloadGrid');
            }, 500);
        }, 500);
    },
    caption:"Clienti Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsClienti").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"clientipage.php",
    height:230,
	width: 1800,
    shrinkToFit: false,
    autowidth: false
});

jQuery("#navgridClienti").jqGrid('navGrid','#pagernavClienti',
{ edit:false,
  add:false,
  del:false, // disabilitato
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
/*
jQuery("#navgridClienti").jqGrid('navButtonAdd','#pagernavClienti',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridClienti").jqGrid('excelExport',{"url":"clientipage.php?q=50"});
       } 
});

*/

// bottone per il congelamento degli ordini dei clienti (non possono essere modificati dal cliente)

jQuery("#navgridClienti").jqGrid('navButtonAdd','#pagernavClienti',{
       caption:"Congela",
       onClickButton : function () {
           // jQuery("#navgridClienti").jqGrid('excelExport',{"url":"clientipage.php?q=50"});
           // alert("Congela");
           var giorno = new Date(); // mi da la data corrente
           // alert(giorno);
           // alert(giorno.toISOString());
           var strgiorno1 = giorno.toISOString().substr(0, 10);
           var gg = strgiorno1.substr(8, 2);
           var mm = strgiorno1.substr(5, 2);
           var yyyy = strgiorno1.substr(0, 4);
           /*
           var nm = giorno.getMonth();
           nm += 1;
           var mm = nm.toString(); // il numero di mese va da 0=Gennaio a 11=Dicembre, per questo occorre aggiungere +1
           var yyyy = giorno.getFullYear().toString();
           */
           // alert(gg + "-" + mm + "-" + yyyy);
           // prendo solo le ultime cifre
           gg = gg.padStart(2, "0");
           mm = mm.padStart(2, "0");
           yyyy = yyyy.padStart(4, "0");
           // alert("DD " + gg + "-" + mm + "-" + yyyy);
           var strgiorno = gg + "/" + mm + "/" + yyyy;
           // alert("str = " + strgiorno);
           var risposta1 = prompt("Indicare la data degli ordini da fissare per i clienti", strgiorno);
           // alert("rsp = " + risposta1);
           // controlla che sia una data valida in formato dd/mm/YYYY
           try {
               var nuovogiorno = stringToDate(risposta1, "dd/mm/yyyy", "/");
               // alert(nuovogiorno);
               // ora eseguiamo la query di congelamento degli ordini di tuti i clienti per la data nuovogiorno
               // alert("data scelta " + nuovogiorno.toISOString().split('T')[0]);

               $.ajax({
                   url: "ordiniclientepage.php?q=45&giorno=" + nuovogiorno.toISOString().split('T')[0],
                   data: "",
                   type: 'GET',
                   success: function (resp) {
                       alert(resp.status + " " + resp.giorno + " " + resp.error);
                   },
                   error: function (e) {
                       alert('Error: ' + e);
                   }
               });
           }
           catch (err) {
               alert("Formato della data " + risposta + " è sbagliato!");
           }

       }
});


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



/* QUESTO BLOCCO SERVE PER DEFINIRE L'ELEMENTO SLAVE DAL PRIMO BLOCCO GRIGLIA ORDINICLIENTE
 * 
 */
// alert("Ordini");

var elencoStato = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "ordineclientepage.php?q=6",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoStato += $(xml).find('rows').text();
    },
    async: false
});

// opzioni per le check box

elencoCheck = ":NULL;true:1;false:0";

jQuery("#navgridOrdini").jqGrid({
    url: 'ordineclientepage.php?q=10',
    datatype: "xml",
    colNames: ['IDORDINE','CLIENTE','SCHEMA','DATA ORDINE','STATO','STATO ORDINE', 'AUT.SUP.SPESA','COD.AUTOZZ.'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
        { name: 'cliente', index: 'cliente', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'schemadefault', index: 'schemadefault', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'dataordine', index: 'dataordine', width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'stato', index: 'stato', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoStato } },
        { name: 'descrizionestato', index: 'descrizionestato', width: 140, hidden: false, editable: false, editoptions: { size: 100 } },
        { name: 'autorizzatosuperamentospesa', index: 'autorizzatosuperamentospesa', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoCheck } },
        { name: 'codiceautorizzazione', index: 'codiceautorizzazione', width: 50, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoCheck } }        
    ],
    rowNum: 20,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavOrdini',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataOrdini,
    /*
    serializeRowData: function (postData) {
        postData.idordine = idordine_key;
        return postData;
    },
    */
    loadComplete: function () {
        var sdati = $("#navgridOrdini").jqGrid('getDataIDs');
        var idord = sdati[0];
        setTimeout(function () {
            $("#navgridOrdini").jqGrid('setSelection', idord);            
            $('#navgridDettOrd').trigger('reloadGrid');
        }, 1000);
    },    
    caption: "Ordine Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsOrdini").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "ordineclientepage.php",
    height: 550,
    width: 470, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

jQuery("#navgridOrdini").jqGrid('navGrid', '#pagernavOrdini',
    {
        edit: false,
        add: false,
        del: false,
        search: false,
        deltitle: "Cancellazione Record"
    },
    { height: 280, reloadAfterSubmit: false }, // edit options
    { height: 280, reloadAfterSubmit: false }, // add options
    {
        reloadAfterSubmit: true, mtype: "POST", url: "ordineclientepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

        jQuery("#navgridOrdini").jqGrid('inlineNav', '#pagernavOrdini',
            {
                // {},
                add: false,
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
                        url: "ordineclientepage.php?q=21&idcliente="+selrow,
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
                    mtype: "POST", keys: true, url: "ordineclientepage.php?q=22&idcliente="+selrow,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50)
                    },
                    aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
                }
            });

var miaGriglia = jQuery("#navgridOrdini");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

/* BLOCCO DEI DETTAGLI ORDINE
 */
// alert("Dettagli Ordini");

var elencoResp = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettordclientepage.php?q=5",
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
    url: "dettordclientepage.php?q=6",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoProdotti += $(xml).find('rows').text();
    },
    async: false
});


jQuery("#navgridDettOrd").jqGrid({
    url: 'dettordclientepage.php?q=10',
    datatype: "xml",
    colNames: ['IDDETTORD','ORDINE','DETTAGLIOORDINE','PRODOTTO','NOME PRODOTTO','GRUPPO','NOME GRUPPO','QUANTITA','UNITA MISURA','STATO','RIFERIMENTO PREC.','RESPONSABILE','NOME RESPONSABILE'
    ],
    colModel: [
        { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'ordine', index: 'ordine', width: 60, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'dettaglioordine', index: 'dettaglioordine', width: 60, align: "left", editable: true, editoptions: { size: 15 } },
        { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
        { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'gruppo', index: 'gruppo', width: 60, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'nomegruppo', index: 'nomegruppo', width: 120, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'quantita', index: 'quantita', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'unitamisura', index: 'unitamisura', width: 60, hidden: false, editable: false, editoptions: { size: 100 } },
        { name: 'stato', index: 'stato', width: 60, hidden: false, editable: false, editoptions: { size: 100 } },
        { name: 'riferimentoprec', index: 'riferimentoprec', width: 60, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'responsabile', index: 'responsabile', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoResp } },
        { name: 'nomeresponsabile', index: 'nomeresponsabile', width: 120, hidden: false, editable: false, editoptions: { size: 100 } }
    ],
    rowNum: 20,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavDettOrd',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataDettOrd,
    /*
    serializeRowData: function(postData) {
            postData.idordine = idordine_key;
            return postData;
    },
    */
    caption: "Dettaglio Ordine Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettOrd").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettordclientepage.php",
    height: 550,
    width: 1300, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

jQuery("#navgridDettOrd").jqGrid('navGrid', '#pagernavDettOrd',
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
        reloadAfterSubmit: true, mtype: "POST", url: "dettordclientepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

jQuery("#navgridDettOrd").jqGrid('inlineNav', '#pagernavDettOrd',
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
                url: "dettordclientepage.php?q=21&idordine=" + idordine_key,
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
            mtype: "POST", keys: true, url: "dettordclientepage.php?q=22&idordine=" + idordine_key,
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50)
            },
            aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
        }
    });

var miaGriglia = jQuery("#navgridDettOrd");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

// alert("Completato");

/* Funzione di supporto per indicare l'ordine di riferimento corrente alle varie sottogriglie */
function SetIdCliente(nomegriglia, nomepager, idcliente) {

    jQuery(nomegriglia).jqGrid({
        url: 'ordineclientepage.php?q=10&idcliente=' + idcliente,
        datatype: "xml",
        colNames: ['IDORDINE', 'CLIENTE', 'SCHEMA', 'DATA ORDINE', 'STATO', 'STATO ORDINE', 'AUT.SUP.SPESA', 'COD.AUTOZZ.'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
            { name: 'cliente', index: 'cliente', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
            { name: 'schemadefault', index: 'schemadefault', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
            { name: 'dataordine', index: 'dataordine', width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
            { name: 'stato', index: 'stato', width: 80, hidden: true, editable: false, edittype: "select", editoptions: { value: elencoStato } },
            { name: 'descrizionestato', index: 'descrizionestato', width: 160, hidden: false, editable: false, editoptions: { size: 100 } },
            { name: "autorizzatosuperamentospesa", width: 120, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" } },
            { name: "codiceautorizzazione", width: 120, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" } }

        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavOrdini',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionata,
        loadComplete: function () {
            var sdati = $("#navgridClienti").jqGrid('getDataIDs');
            var idcln = sdati[0];
            setTimeout(function () {
                $("#navgridClienti").jqGrid('setSelection', idcln);
                jQuery("#navgridOrdini").jqGrid("clearGridData");
                jQuery("#navgridDettOrd").jqGrid("clearGridData");
                setTimeout(function () {
                    $('#navgridOrdini').trigger('reloadGrid');
                    $('#navgridDettOrd').trigger('reloadGrid');
                }, 500);
            }, 500);
        },
        serializeRowData: SistemareDatiOrdini,
        caption: "Ordine Cliente Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsOrdini").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "ordineclientepage.php",
        height: 550,
        width: 470, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false
    });

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavOrdini',
        {
            edit: false,
            add: false,
            del: false,
            search: false,
            deltitle: "Cancellazione Record"
        },
        { height: 280, reloadAfterSubmit: false }, // edit options
        { height: 280, reloadAfterSubmit: false }, // add options
        {
            reloadAfterSubmit: true, mtype: "POST", url: "ordineclientepage.php?q=23&idcliente=" + idcliente,
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavOrdini',
        {
            // {},
            add: false,
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
                    url: "ordiniclientepage.php?q=21",
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
                mtype: "POST", keys: true, url: "ordineclientepage.php?q=22&idcliente="+ idcliente,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery("#navgridOrdini");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");

}

function SetIdOrdine(nomegriglia, nomepager, idordine) {
    // alert("Partenza Ordine "+idordine);
    jQuery(nomegriglia).jqGrid({
        url: 'dettordclientepage.php?q=10&idordine=' + idordine,
        datatype: "xml",
        colNames: ['IDDETTORD', 'ORDINE', 'DETTAGLIOORDINE', 'PRODOTTO', 'NOME PRODOTTO', 'GRUPPO', 'NOME GRUPPO', 'QUANTITA', 'UNITA MISURA', 'STATO', 'RIFERIMENTO PREC.', 'RESPONSABILE', 'NOME RESPONSABILE'
        ],
        colModel: [
            { name: 'id', index: 'id', width: 35, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'ordine', index: 'ordine', width: 60, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'dettaglioordine', index: 'dettaglioordine', width: 60, align: "left", editable: true, editoptions: { size: 15 } },
            { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'gruppo', index: 'gruppo', width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 120, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'quantita', index: 'quantita', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'unitamisura', index: 'unitamisura', width: 60, hidden: false, editable: false, editoptions: { size: 100 } },
            { name: 'stato', index: 'stato', width: 60, hidden: false, editable: false, editoptions: { size: 100 } },
            { name: 'riferimentoprec', index: 'riferimentoprec', width: 60, hidden: true, editable: false, editoptions: { size: 100 } },
            { name: 'responsabile', index: 'responsabile', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoResp } },
            { name: 'nomeresponsabile', index: 'nomeresponsabile', width: 120, hidden: false, editable: false, editoptions: { size: 100 } }
        ],
        rowNum: 20,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavDettOrd',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataOrdini,
        
        serializeRowData: function (postData) {
            postData.idordine = idordine_key;
            return postData;
        },
        
        loadComplete: function () {
            var sdati = $("#navgridOrdini").jqGrid('getDataIDs');
            var idord = sdati[0];
            setTimeout(function () {
                $("#navgridOrdini").jqGrid('setSelection', idord);
                $('#navgridDettOrd').trigger('reloadGrid');
            }, 1000);
        },
        caption: "Dettaglio Ordine Cliente Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettOrd").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettordclientepage.php",
        height: 550,
        width: 1300, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false
    });

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavDettOrd',
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettordclientepage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavDettOrd',
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
                    url: "dettordclientepage.php?q=21&idordine=" + idordine,
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
                mtype: "POST", keys: true, url: "dettordclientepage.php?q=22&idordine="+idordine,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery("#navgridDettOrd");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");
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
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridClienti').jqGrid('restoreRow',lastsel);
    }
    // qui dobbiamo sistemare la tabella degli ordini associati a questo cliente (ripulire i dettagli da dettagli ordine)
    if (id != null) {
        // alert("Cliente "+id);
        jQuery("#navgridOrdini").jqGrid('setGridParam', { url: "ordineclientepage.php?q=10&idcliente=" + id, page: 1 });
        SetIdCliente("#navgridOrdini", '#pagernavOrdini', id);
        jQuery("#fieldsOrdini").html("");
        jQuery("#navgridOrdini").jqGrid('setCaption', "Ordini Cliente - " + id).trigger('reloadGrid');
        // azzero la griglia dei dettagli??? metto id=0
        
        jQuery("#navgridDettOrd").jqGrid('setGridParam', { url: "dettordclientepage.php?q=10&idordine="+idordine_key, page: 1 });
        SetIdOrdine("#navgridDettOrd", '#pagernavDettOrd', idordine_key);
        jQuery("#fieldsDettOrd").html("");
        jQuery("#navgridDettOrd").jqGrid('setCaption', "Dettaglio Ordine - " + id).trigger('reloadGrid');
        
    }
    lastsel=id;
   }
   // alert(selrow);
}

function RigaSelezionataOrdini(id) {
    // alert("Ordine = " + id);
    selrowOrdini = id;
    idordine_key = id;
    if (id && id !== lastselOrdini) {

        if (typeof lastselOrdini != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridOrdini').jqGrid('restoreRow', lastselOrdini);
        }
        // qui dobbiamo sistemare la tabella dei dettagli dell'ordine associato a questo cliente (ripulire i dettagli da dettagli ordine)        
        
        if (id != null) {

            // alert("visto dett. Ordine per "+id);
            // la carico con le righe valide
            // alert(id);
            jQuery("#navgridDettOrd").jqGrid('setGridParam', { url: "dettordclientepage.php?q=10&idordine=" + id, page: 1 });
            SetIdOrdine("#navgridDettOrd", '#pagernavDettOrd', id);
            jQuery("#fieldsDettOrd").html("");
            jQuery("#navgridDettOrd").jqGrid('setCaption', "Dettaglio Ordine - " + id).trigger('reloadGrid');
        }
        
        lastselOrdini = id;
    }
}

function RigaSelezionataDettOrd(id) {
    // alert("Dettaglio Ordine = " + id);
    selrowDettOrd = id;
    if (id && id !== lastselDettOrd) {

        if (typeof lastselDettOrd != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettOrd').jqGrid('restoreRow', lastselDettOrd);
        }

        lastselDettOrd = id;
    }
}

function SistemareDatiOrdini(postData) {
    // controllo ache i campi dell'ordine siano valorizzati correttamente
    // data la data di inserimento dell'ordine attuale calcola la data consegna se ci sono i gg produzione
    // oppure calcola i gg produzione se c'è la data consegna come differenza con la data di inserimento
    return postData;
}
