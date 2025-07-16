var lastsel;
var selrow;
var editingRowId;
var testo;

var lastselOrdini = -1;
var selrowOrdini;

var lastselDetOrd = -1;
var selrowDetOrd;

alert("OrdiniClientiPage.js");

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

alert("Clienti Ordini");

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



/* QUESTO BLOCCO SERVE PER DEFINIRE L'ELEMENTO SLAVE DAL PRIMO BLOCCO GRIGLIA ORDINICLIENTE */


alert("Ordini");


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

jQuery("#navgridOrdini").jqGrid({
    url: 'ordineclientepage.php?q=10',
    datatype: "xml",
    colNames: ['IDORDINE'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
        { name: 'dataordine', index: 'dataordine', width: 80, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'stato', index: 'stato', width: 80, hidden: true, editable: false, edittype: "select", editoptions: { value: elencoStato } },
        { name: 'descrizionestato', index: 'descrizionestato', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: "autorizzatosuperamentospesa", width: 70, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" } },
        { name: "codiceautorizzazione", width: 70, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" }
    ],
    rowNum: 10,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavOrdini',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataOrdini,
    serializeRowData: SistemareDatiOrdini,
    caption: "Ordine Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsOrdini").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "ordineclientepage.php",
    height: 550,
    width: 870, //width: 1150,
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
                        url: "ordineclientepage.php?q=21",
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
                    mtype: "POST", keys: true, url: "ordineclientepage.php?q=22",
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


/* BLOCCO DEI DETTAGLI ORDINE */

alert("Dettagli Ordini");


jQuery("#navgridDettOrd").jqGrid({
    url: 'dettordclientepage.php?q=10',
    datatype: "xml",
    colNames: ['IDORDINE'
    ],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
    ],
    rowNum: 10,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavDettOrd',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataDetOrd,
    serializeRowData: SistemareDatiOrdDet,
    caption: "Dettaglio Ordine Cliente Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDettOrd").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "dettordclientepage.php",
    height: 550,
    width: 900, //width: 1150,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

jQuery("#navgridDettOrd").jqGrid('navGrid', '#pagernavDettOrd',
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
        reloadAfterSubmit: true, mtype: "POST", url: "dettordclientepage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

jQuery("#navgridDettOrd").jqGrid('inlineNav', '#pagernavDettOrd',
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
                url: "dettordclientepage.php?q=21",
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
            mtype: "POST", keys: true, url: "dettordclientepage.php?q=22",
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

alert("Completato");


/* Funzione di supporto per indicare l'ordine di riferimento corrente alle varie sottogriglie */


function SetIdCliente(nomegriglia, nomepager, idcliente) {
    // alert("Partenza Ordine");
    jQuery(nomegriglia).jqGrid({
        url: 'ordineclientepage.php?q=10&idcliente=' + idcliente,
        datatype: "xml",
        colNames: ['IDORDINE'],
        colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: true, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } },
        { name: 'dataordine', index: 'dataordine', width: 80, align: "left", sorttype: "date", formatter: 'date', formatoptions: { srcformat: 'Y-m-d', newformat: 'd/m/Y' }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'stato', index: 'stato', width: 80, hidden: true, editable: false, edittype: "select", editoptions: { value: elencoStato } },
        { name: 'descrizionestato', index: 'descrizionestato', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: "autorizzatosuperamentospesa", width: 70, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" },
        { name: "codiceautorizzazione", width: 70, align: "center", formatter: "checkbox", formatoptions: { disabled: false }, edittype: "checkbox", editoptions: { value: "1:0", defaultValue: "0" }, stype: "select", searchoptions: { sopt: ["eq", "ne"], value: ":NULL;true:1;false:0" }
    ],
        rowNum: 10,
        rowList: [10, 20, 50, 100, 1000],
        pager: '#pagernavOrdini',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataOrdini,
        serializeRowData: SistemareDatiOrdini,
        caption: "Ordine Cliente Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsOrdini").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "ordineclientepage.php",
        height: 550,
        width: 870, //width: 1150,
        shrinkToFit: false,
        autowidth: false,
        hiddengrid: false        
    });
    // alert("Prima");
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
            reloadAfterSubmit: true, mtype: "POST", url: "ordineclientepage.php?q=23&idcliente=" + idcliente,
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );
            jQuery(nomegriglia).jqGrid('inlineNav', nomepager,
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
                            url: "ordineclientepage.php?q=21&idcliente=" + idcliente,
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
                        mtype: "POST", keys: true, url: "ordineclientepage.php?q=22&idcliente=" + idcliente,
                        oneditfunc: function (ids) { alert("Riga " + ids); },
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


/* QUESTO GESTISCE LA GRIGLIA DEI DETTAGLI DELL'ORDINE */

/* DETORD dettagli dell'ordine cliente */


jQuery("#navgridDetOrd").jqGrid({
    url: 'ordinipanpage.php?q=10',
    datatype: "xml",
    colNames: ['IDRIGAPAN', 'IDORDINE', 'QUANTITA', 'TIPOMISURA', 'LARGHEZZAMISURA', 'ALTEZZAMISURA', 'LARGHEZZA', 'ALTEZZA', 'SENSOAPERTURA', 'DESCRSENSOAPERTURA', 'SISTEMAAPERTURA', 'DESCRIZIONEAPERTURA', 'TIPOLOGIAPORTA', 'DESCRIZIONEPORTA', 'SPESSOREPORTA', 'DESCRPORTA', 'DESCRIZIONE',
        'RIF_PORTA',
        'DISEGNO', 'SIGLA', 'SCELTAMASPER', 'NOMEMASSELLOPERIMETRALE', 'LARGHEZZAGRANDE', 'LARGHEZZAPICCOLA', 'LISTINO',
        'PREZZOBASE', 'VARIAZIONE', 'ELENCOVAR', 'PREZZOUNITARIO', 'TOTALERIGA', 'IMM_FERRAMENTA', 'IMM_TELAIO', 'STIPITE', 'NOMESTIPITE'],
    colModel: [
        { name: 'id', index: 'id', width: 55, align: "right", hidden: false, editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
        { name: 'idordine', index: 'idordine', width: 80, align: "left", editable: false, editoptions: { size: 5 } },
        { name: 'quantita', index: 'quantita', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 5 }, editrules: { required: true, number: true } },
        { name: 'tipomisura', index: 'tipomisura', width: 80, editable: true, edittype: "select", editoptions: { value: elencoMis }, editrules: { required: true } },
        { name: 'larghezzamisura', index: 'larghezzamisura', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 5 }, editrules: { required: true, number: true } },
        { name: 'altezzamisura', index: 'altezzamisura', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 5 }, editrules: { required: true, number: true } },
        { name: 'larghezza', index: 'larghezza', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: false, editoptions: { size: 5 } },
        { name: 'altezza', index: 'altezza', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: false, editoptions: { size: 5 } },
        { name: 'sensoapertura', index: 'sensoapertura', width: 80, editable: true, edittype: "select", editoptions: { value: elencoSensoA } },
        { name: 'descrsensoapertura', index: 'descrsensoapertura', width: 100, editable: false, editoptions: { size: 20 } },
        { name: 'sistemaapertura', index: 'sistemaapertura', width: 80, hidden: true, editable: false, edittype: "select", editoptions: { value: elencoSA } },
        { name: 'descrizioneapertura', index: 'descrizioneapertura', width: 160, hidden: true, editable: false, editoptions: { size: 100 } },
        { name: 'tipologiaporta', index: 'tipologiaporta', width: 55, editable: true, edittype: "select", editoptions: { value: elencoTP } },
        { name: 'descrizioneporta', index: 'descrizioneporta', width: 100, editable: false, editoptions: { size: 50 } },
        { name: 'spessoreporta', index: 'spessoreporta', width: 55, editable: true, edittype: "select", editoptions: { defaultValue: 44, value: elencoPorta }, editrules: { required: true } },
        { name: 'descrporta', index: 'descrporta', width: 100, editable: false, editoptions: { size: 50 } },
        { name: 'descrizione', index: 'descrizione', width: 100, editable: true, editoptions: { size: 50 } },
        { name: 'rifporta', index: 'rifporta', width: 100, editable: true, editoptions: { size: 10 } },
        { name: 'disegno', index: 'disegno', width: 55, editable: true, edittype: "select", editoptions: { value: elencoDis }, editrules: { required: true } },
        { name: 'sigla', index: 'sigla', width: 55, editable: false, editoptions: { size: 10 } },
        { name: 'masselloperimetrale', index: 'masselloperimetrale', width: 55, editable: false, edittype: "select", editoptions: { value: elencoMP1 } },
        { name: 'nomemasselloperimetrale', index: 'nomemasselloperimetrale', width: 100, editable: false, editoptions: { size: 50 } },
        { name: 'larghezzagrande', index: 'larghezzagrande', searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, width: 80, editable: true, editoptions: { size: 5 }, editrules: { required: false, number: true } },
        { name: 'larghezzapiccola', index: 'larghezzapiccola', searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, width: 80, editable: true, editoptions: { size: 5 }, editrules: { required: false, number: true } },
        { name: 'codicelistino', index: 'codicelistino', width: 80, editable: true, edittype: "select", editoptions: { value: elencoList } },
        { name: 'prezzobase', index: 'prezzobase', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, formatter: "currency", align: "right", editable: false, editoptions: { size: 10 } },
        { name: 'variazione', index: 'variazione', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, formatter: "currency", align: "right", editable: false, editoptions: { size: 10 } },
        { name: 'elencovar', index: 'elencovar', width: 80, align: "left", editable: false, editoptions: { size: 10 } },
        { name: 'prezzounit', index: 'prezzounit', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, formatter: "currency", align: "right", editable: false, editoptions: { size: 10 } },
        { name: 'totaleriga', index: 'totaleriga', width: 80, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, formatter: "currency", align: "right", editable: false, editoptions: { size: 10 } },
        { name: 'immferramenta', index: 'immferramenta', width: 55, editable: true, edittype: "select", editoptions: { value: elencoDis_1 } },
        { name: 'immtelaio', index: 'immtelaio', width: 55, editable: true, edittype: "select", editoptions: { value: elencoDis_2 } },
        { name: 'stipite', index: 'stipite', width: 55, editable: true, edittype: "select", editoptions: { value: elencoLST } },
        { name: 'nomestipite', index: 'nomestipite', width: 100, editable: false, editoptions: { size: 50 } }
    ],
    rowNum: 10,
    rowList: [10, 20, 50, 100, 1000],
    pager: '#pagernavDetOrd',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionataPan,
    serializeRowData: function (postData) {
        postData.idordine = idordine_key;
        return postData;
    },
    caption: "Dettaglio Ordine Cesari Pasticceria",
    loadError: function (xhr, st, err) {
        if (xhr.status != 200) { jQuery("#fieldsDetOrd").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
    },
    editurl: "ordinipanpage.php",
    height: 280,
    width: 980, //width: 570,
    shrinkToFit: false,
    autowidth: false,
    hiddengrid: false
});

jQuery("#navgridDetOrd").jqGrid('navGrid', '#pagernavDetOrd',
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
        reloadAfterSubmit: true, mtype: "POST", url: "ordinipanpage.php?q=23",
        afterComplete: GestoreAfterDel  // FUNZIONA
    }, // del options
    {} // search options : multipleSearch:true, multipleGroup:true
);

jQuery("#navgridDetOrd").jqGrid('inlineNav', '#pagernavDetOrd',
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
         url: "ordinipanpage.php?q=21",
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
        mtype: "POST", keys: true, url: "ordinipanpage.php?q=22",
        successfunc: function () {
          var $self = $(this);
          setTimeout(function () {
             $self.trigger("reloadGrid");
          }, 50)
        },
        aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
       }
});


var miaGriglia = jQuery("#navgridDetOrd");
$("#add_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#edit_" + miaGriglia[0].id).removeClass("ui-state-disabled");
$("#del_" + miaGriglia[0].id).removeClass("ui-state-disabled");


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
    // qui dobbiamo sistemare la tabella degli ordini associati a questo cliente (ripulire i dettagli da dettagli ordine)
    if (id != null) {
        // alert("Ordine "+id);
        jQuery("#navgridOrdini").jqGrid('setGridParam', { url: "ordineclientepage.php?q=10&idcliente=" + id, page: 1 });
        SetIdCliente("#navgridOrdini", '#pagernavOrdini', id);
        jQuery("#fieldsOrdini").html("");
        flgPan = false;
        flgTel = false;
        jQuery("#navgridOrdini").jqGrid('setCaption', "Ordine Cliente- " + id).trigger('reloadGrid');
    }
    lastsel=id;
   }
   alert(selrow);
}

function RigaSelezionataOrdini(id) {
    selrowOrdini = id;
    if (id && id !== lastselOrdini) {

        if (typeof lastselOrdini != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridOrdini').jqGrid('restoreRow', lastselOrdini);
        }
        // qui dobbiamo sistemare la tabella dei dettagli dell'ordine associato a questo cliente (ripulire i dettagli da dettagli ordine)
        if (id != null) {
            // alert("Ordine "+id);
            jQuery("#navgridOrdDet").jqGrid('setGridParam', { url: "ordiniorddetpage.php?q=10&idordine=" + id, page: 1 });
            SetIdOrdine("#navgridOrdDet", '#pagernavOrdDet', id);
            jQuery("#fieldsOrdDet").html("");
            flgPan = false;
            flgTel = false;
            jQuery("#navgridOrdDet").jqGrid('setCaption', "Dettaglio Ordine - " + id).trigger('reloadGrid');
            // 13/04/2021
            jQuery("#navgridAcc").jqGrid('setGridParam', { url: "ordiniaccpage.php?q=10&idordine=" + id, page: 1 });
            SetIdOrdineAcc("#navgridAcc", "#pagernavAcc", id);
            jQuery("#navgridAcc").jqGrid('setCaption', "Ordini Accoppiati Ordine - " + id).trigger('reloadGrid');
        }

        lastselOrdini = id;
    }
}

function SistemareDatiOrdini(postData) {
    // controllo ache i campi dell'ordine siano valorizzati correttamente
    // data la data di inserimento dell'ordine attuale calcola la data consegna se ci sono i gg produzione
    // oppure calcola i gg produzione se c'è la data consegna come differenza con la data di inserimento
    // alert("ID="+idordine_key); 
    // alert("DataImm="+dataimm);  
    var datacons = postData.dataconsegna;
    var ggprod = postData.giorniproduzione;
    // se datacons è valorizzata si ricalcolano i ggproduzione

    if (postData.cava == "") { postData.cava = "AGB50"; };
    if ((postData.altezzamaniglia == "") || (postData.altezzamaniglia == "0.00")) { postData.altezzamaniglia = "100"; };
    return postData;
}

function RigaSelezionataDetOrd(id) {
    selrowDetOrd = id;
    if (id && id !== lastselDetOrd) {

        if (typeof lastselDetOrd != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDetOrd').jqGrid('restoreRow', lastselDetOrd);
        }
        // non ci sono elementi da sistemare su altre tabelle
        /*
        // qui dobbiamo sistemare la tabella degli ordini associati a questo cliente (ripulire i dettagli da dettagli ordine)
        if (id != null) {
            // alert("Ordine "+id);
            jQuery("#navgridOrdDet").jqGrid('setGridParam', { url: "ordiniorddetpage.php?q=10&idordine=" + id, page: 1 });
            SetIdOrdine("#navgridOrdDet", '#pagernavOrdDet', id);
            jQuery("#fieldsOrdDet").html("");
            flgPan = false;
            flgTel = false;
            jQuery("#navgridOrdDet").jqGrid('setCaption', "Dettaglio Ordine - " + id).trigger('reloadGrid');
            // 13/04/2021
            jQuery("#navgridAcc").jqGrid('setGridParam', { url: "ordiniaccpage.php?q=10&idordine=" + id, page: 1 });
            SetIdOrdineAcc("#navgridAcc", "#pagernavAcc", id);
            jQuery("#navgridAcc").jqGrid('setCaption', "Ordini Accoppiati Ordine - " + id).trigger('reloadGrid');
        }
        */

        lastselDetOrd = id;
    }
}

function SistemareDatiOrdDet(postData) {
    // controllo ache i campi dell'ordine siano valorizzati correttamente
    // data la data di inserimento dell'ordine attuale calcola la data consegna se ci sono i gg produzione
    // oppure calcola i gg produzione se c'è la data consegna come differenza con la data di inserimento
    // alert("ID="+idordine_key); 
    // alert("DataImm="+dataimm);  
    var datacons = postData.dataconsegna;
    var ggprod = postData.giorniproduzione;
    // se datacons è valorizzata si ricalcolano i ggproduzione

    if (postData.cava == "") { postData.cava = "AGB50"; };
    if ((postData.altezzamaniglia == "") || (postData.altezzamaniglia == "0.00")) { postData.altezzamaniglia = "100"; };
    return postData;
}


function RigaSelezionataOrdDet(id) {
    // alert("Riga selezionata "+id);
    selrowOrdDet = id;
    rigaId = jQuery("#navgridOrdini").jqGrid('getGridParam', 'selrow');
    var riga = jQuery("#navgridOrdini").jqGrid('getRowData', rigaId);
    dataimm = riga['datainserimento'];
    if (inizio) {
        fine = new Date();
        if (fine - inizio < 2000) { // la differenza indica i millisecondi trascorsi fra le due date (2000 ms = 2 secondi)
            if (lastselOrdDet == selrowOrdDet) {
                // doppio click
                // mostriamo i dati nel campo fieldsOrdDet
                var testotab = "<TABLE><TR><TD width='150'>Campo</TD><TD>Valore</TD></TR>";
                testotab += "<TR><TD>IdOrdine</TD><TD>" + id + "</TD></TR>";
                var rowId = jQuery("#navgridOrdini").jqGrid('getGridParam', 'selrow');
                var rowData = jQuery("#navgridOrdini").jqGrid('getRowData', rowId);
                var colData = rowData['annocompetenza'];
                testotab += "<TR><TD>Anno Competenza</TD><TD>" + colData + "</TD></TR>";
                colData = rowData['numeroordine'];
                testotab += "<TR><TD>Numero Ordine</TD><TD>" + colData + "</TD></TR>";
                colData = rowData['datainserimento'];
                testotab += "<TR><TD>Data di Inserimento</TD><TD>" + colData + "</TD></TR>";
                //
                var rowData1 = jQuery("#navgridOrdDet").jqGrid('getRowData', rowId);
                colData = rowData1['dataconsegna'];
                testotab += "<TR><TD>Data di Consegna</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['giorniproduzione'];
                testotab += "<TR><TD>GG di Produzione</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomecliente'];
                testotab += "<TR><TD>Cliente</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['riferimentocliente'];
                testotab += "<TR><TD>Riferimento del Cliente</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['lavorazione'];
                testotab += "<TR><TD>Tipo Lavorazione</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomemateriale'];
                testotab += "<TR><TD>Materiale</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomefamiglia'];
                testotab += "<TR><TD>Famiglia</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomecolore'];
                testotab += "<TR><TD>Colore Impiallacciatura</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomecoloremazzetta'];
                testotab += "<TR><TD>Colore Mazzetta</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['descrizionemazzetta'];
                testotab += "<TR><TD>Descrizione Mazzetta</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomemdf'];
                testotab += "<TR><TD>MDF</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomemasselloperimetrale'];
                testotab += "<TR><TD>Massello Perimetrale</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nomecliente'];
                testotab += "<TR><TD>Cliente</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['cava'];
                testotab += "<TR><TD>Cava</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['altezzamaniglia'];
                testotab += "<TR><TD>Altezza della Maniglia</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['bugna'];
                testotab += "<TR><TD>Bugna</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['controbugna'];
                testotab += "<TR><TD>Contro-Bugna</TD><TD>" + colData + "</TD></TR>";
                // nuovi campi
                colData = rowData1['coprifilo'];
                testotab += "<TR><TD>Coprifilo</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['descrcoprifilo'];
                testotab += "<TR><TD>Descrizione-Coprifilo</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['guarnizioni'];
                testotab += "<TR><TD>Guarnizioni</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['fermavetro'];
                testotab += "<TR><TD>Ferma-Vetro</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['nota'];
                testotab += "<TR><TD>Nota</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ferramenta'];
                testotab += "<TR><TD>Cod.Ferramenta</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['descrferramenta'];
                testotab += "<TR><TD>Descrizione Ferramenta</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['coloreferramenta'];
                testotab += "<TR><TD>Colore Ferramenta</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['descrcoloreferramenta'];
                testotab += "<TR><TD>Nome Colore Ferramenta</TD><TD>" + colData + "</TD></TR>";
                //
                colData = rowData1['numerobugne'];
                testotab += "<TR><TD>Numero di Bugne</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['hzoccolo'];
                testotab += "<TR><TD>Altezza Zoccolo</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ltestata'];
                testotab += "<TR><TD>Larghezza Testata</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['htraverso1'];
                testotab += "<TR><TD>Altezza Primo traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ltraverso1'];
                testotab += "<TR><TD>Larghezza Primo Traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['htraverso2'];
                testotab += "<TR><TD>Altezza Secondo traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ltraverso2'];
                testotab += "<TR><TD>Larghezza Secondo Traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['htraverso3'];
                testotab += "<TR><TD>Altezza Terzo traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ltraverso3'];
                testotab += "<TR><TD>Larghezza Terzo Traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['htraverso4'];
                testotab += "<TR><TD>Altezza Quarto traverso</TD><TD>" + colData + "</TD></TR>";
                colData = rowData1['ltraverso4'];
                testotab += "<TR><TD>Larghezza Quarto Traverso</TD><TD>" + colData + "</TD></TR>";
                //		
                testotab += "</TABLE>";
                jQuery('#fieldsOrdDet').html(testotab);
            }
        }
        inizio = undefined;
        fine = undefined;
    }
    else {
        inizio = new Date(); // registro il tempo attuale
    }
    // alert("RigaDetOrd:"+id);
    if (id && id !== lastselOrdDet) {
        if (typeof lastselOrdDet != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridOrdDet').jqGrid('restoreRow', lastselOrdDet);
            // jQuery('#navgridOrdDet').jqGrid('editRow',id,true,function(ids){alert("Fatto"+ids);});
            // jQuery('#navgridOrdDet').jqGrid('editRow',id,true,pickdatesOrdDet); // test per avere la tendina a calendario
        }
        lastselOrdDet = id;
    }
}