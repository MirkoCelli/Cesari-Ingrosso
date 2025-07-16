var lastsel;
var selrow;
var editingRowId;
var testo;
var lastselFattura;
var selrowFattura;
var idfattura_key;
var fatturakey = null;
// FATTURE PAGE - 2024-06-06

// Elenco dei valori ammessi per Zone
var elencoProdotti = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettfatturepage.php?q=5",
    dataType: "xml",
    success: function(xml) {
       // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
       elencoProdotti += $(xml).find('rows').text();
    },
    async:   false
});

// Elenco dei valori ammessi per Provincie
var elencoUnMis = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettfatturepage.php?q=6",
    dataType: "xml",
    success: function(xml) {
       // abbiamo in xml il nostro file provincia, ora lo dobbiamo assegnare a elencoProvincie
       elencoUnMis += $(xml).find('rows').text();
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

// SEZIONE FATTURE 

jQuery("#navgridFatture").jqGrid({
    url:'fatturepage.php?q=10',
    datatype: "xml",
    colNames:['ID','CLIENTE', 'NOME CLIENTE','DATA FATTURA','NUM.FATT.','NUMER.FATT.','TOT.IMPONIBILE','RIFERIMENTI','% IVA','IMPOSTA IVA','TOTALE FATT.','ANNOTAZIONE'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
        { name: 'cliente', index: 'cliente', hidden: true, width: 100, align: "left", editable: true, editoptions: { size: 15 } },
        { name: 'nomecliente', index: 'nomecliente', width: 200, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'datafattura', index: 'datafattura', width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'numerofattura', index: 'numerofattura', width: 100, align: "right", editable: true, editoptions: { size: 15 } },
        { name: 'numerazionefattura', index: 'numerazionefattura', width: 150, align: "left", editable: true, editoptions: { size: 15 } },
        { name: 'totaleimponibile', index: 'totaleimponibile', width: 150, align: "right", formatter: "number",  editable: true, editoptions: { size: 15 } },
        { name: 'riferimenti', index: 'riferimenti', width: 100, align: "left", editable: true, editoptions: { size: 15 } },
        { name: 'perc_iva', index: 'perc_iva', width: 55, align: "right", formatter: "number", hidden: false, sorttype: "number", editable: true, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { size: 10 } },
        { name: 'impostaiva', index: 'impostaiva', width: 150, align: "right", formatter: "number", hidden: false, sorttype: "number", editable: true, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { size: 10 } },
        { name: 'totalefattura', index: 'totalefattura', width: 150, align: "right", formatter: "number", hidden: false, sorttype: "number", editable: true, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { size: 10 } },
        {name:'descrizione', index: 'descrizione', width: 380, editable: true, editoptions: { size: 1000 } }],
    rowNum: 10,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavFatture',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    loadComplete: function () {
        var sdati = $("#navgridFatture").jqGrid('getDataIDs');
        var idlst = sdati[0];
        setTimeout(function () {
            $("#navgridFatture").jqGrid('setSelection', idlst);
            jQuery("#navgridDettFatt").jqGrid("clearGridData");
            setTimeout(function () {
                $('#navgridDettFatt').trigger('reloadGrid');
            }, 500);
        }, 500);
    },
    caption:"Fatture Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsFatture").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"fatturepage.php",
    height:100,
	width: 1800,
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
{reloadAfterSubmit:true,mtype:"POST",url:"fatturepage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridFatture").jqGrid('navButtonAdd','#pagernavFatture',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridFatture").jqGrid('excelExport',{"url":"fatturepage.php?q=50"});
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
            url: "fatturepage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "fatturepage.php?q=22",
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

// alert("Dettagli listino");

// SEZIONE DETTAGLI DELLA FATTURA

jQuery("#navgridDettFatt").jqGrid({
    url: 'dettfatturepage.php?q=10',
    datatype: "xml",
    colNames: ['ID', 'FATTURA', 'GRUPPO', 'NOME GRUPPO', 'PRODOTTO', 'NOME PRODOTTO', 'QUANTITA', 'PREZZO UNIT.','TOTALE', 'ANNOTAZIONI','BOLLA','DETTAGLIO BOLLA'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'fattura', index: 'fattura', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
        { name: 'gruppo', index: 'gruppo', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
        { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
        { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'quantita', index: 'quantita', width: 100, formatter: "number", align : "right", hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'prezzounitario', index: 'prezzounitario', width: 150, formatter: "number", align: "right", hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'totale', index: 'totale', width: 150, hidden: false, formatter: "number", align: "right", editable: true, editoptions: { size: 100 } },
        { name: 'annotazioni', index: 'annotazioni', width: 480, editable: true, editoptions: { size: 1000 } },
        { name: 'bolla', index: 'bolla', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'dettagliobolla', index: 'dettagliobolla', width: 100, hidden: false, editable: true, editoptions: { size: 100 } }],
    rowNum: 25,
    rowList: [10, 20, 25, 50, 100, 1000],
    pager: '#pagernavDettFatt',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataListino,
    loadComplete: function () {
        var sdati = $("#navgridFatture").jqGrid('getDataIDs');
        var idlst = sdati[0];
    },
    serializeRowData: function (postData) {
        // alert(idfatturakey + " PPP");
        postData.idfattura = idfatturakey;
        return postData;
    },
    caption: "Dettagli Listino Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettFatt").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettfatturepage.php",
    height: 400,
    width: 1800,
    shrinkToFit: false,
    autowidth: false
});

// alert("Pager Dettagli Listino");

jQuery("#navgridDettFatt").jqGrid('navGrid', '#pagernavDettFatt',
    {
        edit: false,
        add: false,
        del: false,
        search: true,
        deltitle: "Cancellazione Record"
    },
    { height: 280, reloadAfterSubmit: false }, // edit options
    { height: 280, reloadAfterSubmit: false }, // add options
    {
        reloadAfterSubmit: true, mtype: "POST", url: "dettfatturepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridDettFatt").jqGrid('navButtonAdd', '#pagernavDettFatt', {
    caption: "CSV",
    onClickButton: function () {
        jQuery("#navgridDettFatt").jqGrid('excelExport', { "url": "dettfatturepage.php?q=50" });
    }
});

jQuery("#navgridDettFatt").jqGrid('inlineNav', '#pagernavDettFatt',
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
                url: "dettfatturepage.php?q=21&idfattura="+idfatturakey,
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
            mtype: "POST", keys: true, url: "dettfatturepage.php?q=22&idfattura="+idfatturakey,
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50)
            },
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
    idfatturakey = id; // importantissimo avere questa valorizzazione
    // alert(idfatturakey);
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridFatture').jqGrid('restoreRow',lastsel);
       }
       // qui dobbiamo sistemare la tabella dei dettagli dell'ordine associato a questo cliente (ripulire i dettagli da dettagli ordine)        
       if (id != null) {

           // alert("visto dett. Ordine per "+id);
           // la carico con le righe valide

           jQuery("#navgridDettFatt").jqGrid('setGridParam', { url: "dettfatturepage.php?q=10&idfattura=" + id, page: 1 });
           SetIdListino("#navgridDettFatt", '#pagernavDettFatt', id);
           jQuery("#fieldsDettFatt").html("");
           jQuery("#navgridDettFatt").jqGrid('setCaption', "Dettaglio Listino - " + id).trigger('reloadGrid');
       }
    lastsel=id;
  }
}

function RigaSelezionataListino(id) {
    // alert("Listino = " + id);
    selrowListino = id;
    // idfattura_key = id;
    if (id && id !== lastselListino) {

        if (typeof lastselListino != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettFatt').jqGrid('restoreRow', lastselListino);
        }

        lastselListino = id;
    }
}

function SetIdListino(nomegriglia, nomepager, idfattura) {
    // alert("Partenza Listino");
    jQuery(nomegriglia).jqGrid({
        url: 'dettfatturepage.php?q=10',
        datatype: "xml",
        colNames: ['ID', 'FATTURA', 'GRUPPO', 'NOME GRUPPO', 'PRODOTTO', 'NOME PRODOTTO', 'QUANTITA', 'PREZZO UNIT.', 'TOTALE', 'ANNOTAZIONI', 'BOLLA', 'DETTAGLIO BOLLA'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'fattura', index: 'fattura', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
            { name: 'gruppo', index: 'gruppo', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'quantita', index: 'quantita', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'prezzounitario', index: 'prezzounitario', width: 150, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'totale', index: 'totale', width: 150, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'annotazioni', index: 'annotazioni', width: 480, editable: true, editoptions: { size: 1000 } },
            { name: 'bolla', index: 'bolla', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'dettagliobolla', index: 'dettagliobolla', width: 100, hidden: false, editable: true, editoptions: { size: 100 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: '#pagernavDettFatt',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataListino,
        loadComplete: function () {
            var sdati = $("#navgridFatture").jqGrid('getDataIDs');
            var idlst = sdati[0];
        },
        serializeRowData: function (postData) {
            // alert(idfatturakey + " PPP");
            postData.idfattura = idfatturakey;
            return postData;
        },
        caption: "Dettagli Listino Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettFatt").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettfatturepage.php",
        height: 400,
        width: 1800,
        shrinkToFit: false,
        autowidth: false
    });

    jQuery(nomegriglia).jqGrid('navGrid', '#pagernavDettFatt',
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettfatturepage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', '#pagernavDettFatt',
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
                    url: "dettfatturepage.php?q=21&idfattura="+idfattura,
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
                mtype: "POST", keys: true, url: "dettfatturepage.php?q=22&idfattura="+idfattura,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery("#navgridDettFatt");
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");
}
