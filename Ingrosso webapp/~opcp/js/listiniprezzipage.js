var lastsel;
var selrow;
var editingRowId;
var testo;
var lastselListino;
var selrowListino;
var idlistino_key;
var listinokey = null;

var lastselGruppo;
var selrowGruppo;

// LISTINI PREZZI - 2024-06-06

// Elenco dei valori ammessi per Zone
var elencoProdotti = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettlistprezzipage.php?q=5",
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
    url: "dettlistprezzipage.php?q=6",
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

// SEZIONE LISTINI 

jQuery("#navgridListini").jqGrid({
    url:'listiniprezzipage.php?q=10',
    datatype: "xml",
    colNames:['ID','TIPO CLIENTE', 'DATA INIZIO','DATA FINE','PROVVIGIONE %','ANNOTAZIONI'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
        {name:'tipo',index:'tipo', width:100, align:"left",editable:true,editoptions:{size:15}},
        { name: 'datainizio', index: 'datainizio', width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'datafine', index: 'datafine', width: 100, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'provvigione', index: 'provvigione', width: 55, align: "right", formatter: "number", hidden: false, sorttype: "number", editable: true, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { size: 10 } },
        {name:'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
    rowNum: 10,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavListini',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    loadComplete: function () {
        var sdati = $("#navgridListini").jqGrid('getDataIDs');
        var idlst = sdati[0];
        setTimeout(function () {
            $("#navgridListini").jqGrid('setSelection', idlst);
            jQuery("#navgridDettList").jqGrid("clearGridData");
            setTimeout(function () {
                $('#navgridDettList').trigger('reloadGrid');
            }, 500);
        }, 500);
    },
    caption:"Listini Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsListini").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"listiniprezzipage.php",
    height:90,
	width: 1800,
    shrinkToFit: false,
    autowidth: false
});

jQuery("#navgridListini").jqGrid('navGrid','#pagernavListini',
{ edit:false,
  add:false,
  del:true,
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"listiniprezzipage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridListini").jqGrid('navButtonAdd','#pagernavListini',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridListini").jqGrid('excelExport',{"url":"listiniprezzipage.php?q=50"});
       } 
});

// Bottone per completare i listini con i prodotti nuovi

jQuery("#navgridListini").jqGrid('navButtonAdd', '#pagernavListini', {
    caption: "Completa",
    onClickButton: function () {
        var listino = jQuery("#navgridListini").jqGrid('getGridParam', 'selrow');
        // alert(listino);
        $.ajax({
            url: "listiniprezzipage.php?q=45&id=" + listino,
            data: {},
            type: 'GET',
            success: function (resp) {
                // risposta {"id":"xxx", "error": "eeeee"} in formato JSON
                // alert(resp.id + " -- "+resp.error);
                if (resp.error != "") {
                    alert("Errore: " + resp.error);
                } else {
                    alert("Successo: inseriti " + resp.id + " prodotti nuovi");
                }                
            },
            error: function (e) {
                alert('Error: ' + e);
            }
        });
    }
});

jQuery("#navgridListini").jqGrid('inlineNav','#pagernavListini',
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
            url: "listiniprezzipage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "listiniprezzipage.php?q=22",
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

// **************************************************************** //
// SEZIONE DETTAGLI DEL LISTINO                                     //
// **************************************************************** //

jQuery("#navgridDettList").jqGrid({
    url: 'dettlistprezzipage.php?q=10',
    datatype: "xml",
    colNames: ['ID', 'LISTINO', 'PRODOTTO', 'NOME PRODOTTO','GRUPPO', 'UNITA MISURA', 'PREZZO UNIT.', 'ANNOTAZIONI'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'listino', index: 'listino', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
        { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
        { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'unitamisura', index: 'unitamisura', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoUnMis } },
        { name: 'prezzounitario', index: 'prezzounitario', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
    rowNum: 25,
    rowList: [10, 20, 25, 50, 100, 1000],
    pager: '#pagernavDettList',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataListino,
    loadComplete: function () {
        var sdati = $("#navgridListini").jqGrid('getDataIDs');
        var idlst = sdati[0];
    },
    serializeRowData: function (postData) {
        // alert(idlistinokey + " PPP");
        postData.idlistino = idlistinokey;
        return postData;
    },
    caption: "Dettagli Listino Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettList").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettlistprezzipage.php",
    height: 270,
    width: 1800,
    shrinkToFit: false,
    autowidth: false
});

// alert("Pager Dettagli Listino");

jQuery("#navgridDettList").jqGrid('navGrid', '#pagernavDettList',
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
        reloadAfterSubmit: true, mtype: "POST", url: "dettlistprezzipage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

jQuery("#navgridDettList").jqGrid('inlineNav', '#pagernavDettList',
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
                url: "dettlistprezzipage.php?q=21",
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
            mtype: "POST", keys: true, url: "dettlistprezzipage.php?q=22",
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50)
            },
            aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
        }
    });

// Bottone per esportare in Excel (CSV)

jQuery("#navgridDettList").jqGrid('navButtonAdd', '#pagernavDettList', {
    caption: "CSV",
    onClickButton: function () {
        jQuery("#navgridDettList").jqGrid('excelExport', { "url": "dettlistprezzipage.php?q=50" });
    }
});
// alert("Fine Dett.Listino");

// toolbar per la ricerca (filtri in testa alle colonne)
/* // non è molto estetico
jQuery("#navgridClienti").jqGrid("filterToolbar", {
    searchOperators: true,
    stringResult: true,
    searchOnEnter: false,
    defaultSearch: "eq"
});
*/

// 2024-07-28 - SEZIONE LISTINO PREZZI PER GRUPPI PRODOTTI

// **************************************************************** //
// SEZIONE DETTAGLI DEL LISTINO                                     //
// **************************************************************** //

// alert('Sezione Gruppi');

var elencoGruppi = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "dettlistinogruppipage.php?q=5",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file zona, ora lo dobbiamo assegnare a elencoZone
        elencoGruppi += $(xml).find('rows').text();
    },
    async: false
});

jQuery("#navgridDettGrp").jqGrid({
    url: 'dettlistinogruppipage.php?q=10',
    datatype: "xml",
    colNames: ['ID', 'LISTINO', 'GRUPPO', 'NOME GRUPPO', 'UNITA MISURA', 'PREZZO UNIT.', 'ANNOTAZIONI'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'listino', index: 'listino', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
        { name: 'gruppo', index: 'gruppo', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoGruppi } },
        { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'unitamisura', index: 'unitamisura', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoUnMis } },
        { name: 'prezzounitario', index: 'prezzounitario', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
        { name: 'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
    rowNum: 25,
    rowList: [10, 20, 25, 50, 100, 1000],
    pager: '#pagernavDettGrp',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataGruppo,
    loadComplete: function () {
        /*
        var sdati = $("#navgridListini").jqGrid('getDataIDs');
        var idlst = sdati[0];
        */
    },
    serializeRowData: function (postData) {
        // alert(idlistinokey + " PPP");
        postData.idlistino = idlistinokey;
        return postData;
    },
    caption: "Dettagli Listino Gruppi Prodotti Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettGrp").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettlistinogruppipage.php",
    height: 180,
    width: 1800,
    shrinkToFit: false,
    autowidth: false
});

// alert("Pager Dettagli Listino");

jQuery("#navgridDettGrp").jqGrid('navGrid', '#pagernavDettGrp',
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
        reloadAfterSubmit: true, mtype: "POST", url: "dettlistinogruppipage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridDettGrp").jqGrid('navButtonAdd', '#pagernavDettGrp', {
    caption: "CSV",
    onClickButton: function () {
        jQuery("#navgridDettGrp").jqGrid('excelExport', { "url": "dettlistinogruppipage.php?q=50" });
    }
});

jQuery("#navgridDettGrp").jqGrid('inlineNav', '#pagernavDettGrp',
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
                url: "dettlistinogruppipage.php?q=21",
                keys: true,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50);
                },
                aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
            }
        },
        editParams: {
            mtype: "POST", keys: true, url: "dettlistinogruppipage.php?q=22",
            successfunc: function () {
                var $self = $(this);
                setTimeout(function () {
                    $self.trigger("reloadGrid");
                }, 50);
            },
            aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
        }
    });

// alert('Completato');

// fine Sezione DETTAGLI LISTINO GRUPPI PRODOTTI

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
    idlistinokey = id; // importantissimo avere questa valorizzazione
    // alert(idlistinokey);
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se è definita si fa restore
      jQuery('#navgridListini').jqGrid('restoreRow',lastsel);
       }
       // qui dobbiamo sistemare la tabella dei dettagli dell'ordine associato a questo cliente (ripulire i dettagli da dettagli ordine)        
       if (id != null) {

           // alert("visto dett. listino per "+id);
           // la carico con le righe valide

           jQuery("#navgridDettList").jqGrid('setGridParam', { url: "dettlistprezzipage.php?q=10&idlistino=" + id, page: 1 });
           // SetIdListino("#navgridDettList", '#pagernavDettList', id);
           jQuery("#fieldsDettList").html("");
           jQuery("#navgridDettList").jqGrid('setCaption', "Dettaglio Listino - " + id).trigger('reloadGrid');
           // alert("Gruppo");
           jQuery("#navgridDettGrp").jqGrid('setGridParam', { url: "dettlistinogruppipage.php?q=10&idlistino=" + id, page: 1 });
           // SetIdGruppo("#navgridDettGrp", '#pagernavDettGrp', id);
           jQuery("#fieldsDettGrp").html("");
           jQuery("#navgridDettGrp").jqGrid('setCaption', "Dettaglio Listino Gruppi Prodotti- " + id).trigger('reloadGrid');
           // alert("completo");
       }
    lastsel=id;
  }
}

function RigaSelezionataListino(id) {
    // alert("Listino = " + id);
    selrowListino = id;
    // idlistino_key = id;
    if (id && id !== lastselListino) {

        if (typeof lastselListino != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettList').jqGrid('restoreRow', lastselListino);
        }

        lastselListino = id;
    }
}

function RigaSelezionataGruppo(id) {
    // alert("Listino = " + id);
    selrowGruppo = id;
    // idlistino_key = id;
    if (id && id !== lastselGruppo) {

        if (typeof lastselGruppo != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettGrp').jqGrid('restoreRow', lastselGruppo);
        }

        lastselGruppo = id;
    }
}

function SetIdListino(nomegriglia, nomepager, idlistino) {
    // alert("Partenza Listino");
    // alert("SetIdListino");
    jQuery(nomegriglia).jqGrid({
        url: 'dettlistprezzipage.php?q=10&idlistino='+idlistino,
        datatype: "xml",
        colNames: ['ID', 'LISTINO', 'PRODOTTO', 'NOME PRODOTTO' , 'GRUPPO', 'UNITA MISURA', 'PREZZO UNIT.', 'ANNOTAZIONI'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'listino', index: 'listino', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
            { name: 'prodotto', index: 'prodotto', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoProdotti } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'unitamisura', index: 'unitamisura', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoUnMis } },
            { name: 'prezzounitario', index: 'prezzounitario', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: nomepager,
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataListino,
        caption: "Dettagli Listino Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettList").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettlistprezzipage.php",
        height: 100,
        width: 1800,
        shrinkToFit: false,
        autowidth: false
    });
    // jQuery(nomegriglia).jqGrid('navGrid', '#pagernavDettList',
    // alert(nomepager);
    jQuery(nomegriglia).jqGrid('navGrid', nomepager,
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettlistprezzipage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', nomepager,
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
                    url: "dettlistprezzipage.php?q=21&idlistino="+idlistino,
                    keys: true,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50);
                    },
                    aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                }
            },
            editParams: {
                mtype: "POST", keys: true, url: "dettlistprezzipage.php?q=22&idlistino="+idlistino,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50);
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });
    // alert("completato");
    var miaGriglia = jQuery(nomegrid);
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");
}

function SetIdGruppo(nomegriglia, nomepager, idlistino) {
    // alert("SetIdGruppo");
    jQuery(nomegriglia).jqGrid({
        url: 'dettlistinogruppipage.php?q=10',
        datatype: "xml",
        colNames: ['ID', 'LISTINO', 'GRUPPO', 'NOME GRUPPO', 'UNITA MISURA', 'PREZZO UNIT.', 'ANNOTAZIONI'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'listino', index: 'listino', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"        
            { name: 'gruppo', index: 'gruppo', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoGruppi } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 180, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'unitamisura', index: 'unitamisura', width: 80, hidden: false, editable: true, edittype: "select", editoptions: { value: elencoUnMis } },
            { name: 'prezzounitario', index: 'prezzounitario', width: 100, hidden: false, editable: true, editoptions: { size: 100 } },
            { name: 'annotazioni', index: 'annotazioni', width: 680, editable: true, editoptions: { size: 1000 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: nomepager,
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataGruppo,
        loadComplete: function () {
            /*
            var sdati = $("#navgridListini").jqGrid('getDataIDs');
            var idlst = sdati[0];
            */
        },
        serializeRowData: function (postData) {
            // alert(idlistinokey + " PPP");
            postData.idlistino = idlistinokey;
            return postData;
        },
        caption: "Dettagli Listino Gruppi Prodotti Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettGrp").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettlistinogruppipage.php",
        height: 180,
        width: 1800,
        shrinkToFit: false,
        autowidth: false
    });
    // jQuery(nomegriglia).jqGrid('navGrid', '#pagernavDettList',
    // alert(nomepager);

    jQuery(nomegriglia).jqGrid('navGrid', nomepager,
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettlistinogruppipage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    jQuery(nomegriglia).jqGrid('inlineNav', nomepager,
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
                    url: "dettlistinogruppipage.php?q=21&idlistino=" + idlistinokey,
                    keys: true,
                    successfunc: function () {
                        var $self = $(this);
                        setTimeout(function () {
                            $self.trigger("reloadGrid");
                        }, 50);
                    },
                    aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
                }
            },
            editParams: {
                mtype: "POST", keys: true, url: "dettlistinogruppipage.php?q=22&idlistino=" + idlistinokey,
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50);
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    var miaGriglia = jQuery(nomegrid);
    $("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
    $("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");
}
