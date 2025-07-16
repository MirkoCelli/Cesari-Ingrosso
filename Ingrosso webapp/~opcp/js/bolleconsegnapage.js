var lastsel;
var selrow;
var editingRowId;
var testo;

var lastselDettOrd;
var selrowDettOrd;

var lastselDettBolla;
var selrowDettBolla;

// BOLLECONSEGNE - 2024-06-06

// Elenco dei valori ammessi per Zone
var elencoListino = ":;"; // indica un valore null di default se è NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "bolleconsegnapage.php?q=5",
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
    url: "bolleconsegnapage.php?q=6",
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

// chiamo la funzione che disegna le griglie
var oggi = $("#giorno").val();
RicostruisciMaschera(oggi);

function RicostruisciMaschera(adesso) {
    // con la data odierna in adesso vincolo le query di ricerca alla giornata indicata in adesso
    jQuery("#navgridBolle").jqGrid({
        url: 'bolleconsegnapage.php?q=10',
        datatype: "xml",
        colNames: ['ID', 'CLIENTE', 'NOME CLIENTE', 'DATA ORDINE', 'STATO','NOME STATO', 'ORDINE', 'INTERMEDIARIO', 'NOME INTERMEDIARIO', 'TIPO INTERM.',
            'BOLLA', 'DATA BOLLA', 'NUM.BOLLA', 'TOT. BOLLA', 'FATTURATA', 'RAPPORTO', 'TOTALE ORDINE', 'TOTALE B', 'TOTALE N',
            '% B','% N'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'cliente', index: 'cliente', width: 50, align: "left", hidden: true, editable: false, editoptions: { size: 15 } },
            { name: 'nomecliente', index: 'nomecliente', width: 250, editable: false, editoptions: { size: 250 } },
            { name: 'dataordine', index: 'dataordine', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'statoordine', index: 'statoordine', width:40, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomestato', index: 'nomestato', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'ordine', index: 'ordine', width: 80, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'intermediario', index: 'intermediario', hidden: true, width: 80, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomeintemediario', index: 'nomeintermediario', width: 150, editable: false, editoptions: { size: 30 } },
            { name: 'tipointermediazione', index: 'tipointermediazione', width: 50, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'bolla', index: 'bolla', width: 50, hidden: true, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'databolla', index: 'databolla', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'numerobolla', index: 'numerobolla', width: 100, align: "right", editable: false, editoptions: { size: 15 } },
            { name: 'totalebolla', index: 'totalebolla', width: 100, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'fatturata', index: 'fatturata', width: 50, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'rapporto', index: 'rapporto', width: 80, align: "right", editable: false, editoptions: { size: 15 } },
            { name: 'totaleordine', index: 'totaleordine', width: 120, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'totaleb', index: 'totaleb', width: 100, hidden: false, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'totalen', index: 'totalen', width: 100, hidden: false, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'percentuale_b', index: 'percentuale_b', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'percentuale_n', index: 'percentuale_n', width: 100, align: "left", editable: false, editoptions: { size: 15 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: '#pagernavBolle',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionata,
        loadComplete: function () {
            var sdati = $("#navgridBolle").jqGrid('getDataIDs');
            var idord = sdati[0];
            setTimeout(function () {
                $("#navgridBolle").jqGrid('setSelection', idord);
                jQuery("#navgridDettOrd").jqGrid("clearGridData");
                jQuery("#navgridDettBolla").jqGrid("clearGridData");
                setTimeout(function () {
                    $('#navgridDettOrd').trigger('reloadGrid');
                    $('#navgridDettBolla').trigger('reloadGrid');
                }, 500);
            }, 500);
        },
        caption: "Bolle di Consegna Cesari Pasticceria - " + adesso,
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsBolle").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "bolleconsegnapage.php",
        height: 330,
        width: 1800,
        shrinkToFit: false,
        autowidth: false
    });

    jQuery("#navgridBolle").jqGrid('navGrid', '#pagernavBolle',
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
            reloadAfterSubmit: true, mtype: "POST", url: "bolleconsegnapage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    // Bottone per esportare in Excel (CSV)

    jQuery("#navgridBolle").jqGrid('navButtonAdd', '#pagernavBolle', {
        caption: "CSV",
        onClickButton: function () {
            jQuery("#navgridBolle").jqGrid('excelExport', { "url": "bolleconsegnapage.php?q=50" });
        }
    });

    // Bottone per Generare la bolletta di consegna, richiedere il numero di bolla e assegnare come databolla la dataordine 

    jQuery("#navgridBolle").jqGrid('navButtonAdd', '#pagernavBolle', {
        caption: "Bolla",
        onClickButton: function () {
            // qui deve richiedere i dati della riga selezionata
            // richiede all'utente il numero di bolletta da assegnare a questo ordine
            // registra in bollaconsegna i dati riepilogativi
            // registra in dettagliobolla i dati di dettaglio per i gruppi di prodotti e solo la sezione da registrare in bolletta
            var rowData = jQuery("#navgridBolle").jqGrid("getRowData", selrow); // sono dei fields del record rowData: hanno lo stesso nome dei campi descritti in jqGrid
            // alert(rowData.id + ' ' + rowData.cliente + ' ' + rowData.nomecliente + ' ' + rowData.dataordine);
            var idordine = rowData.id;
            var databolla = rowData.dataordine;
            var stato = rowData.statoordine; // deve essere = 8 per potere generare la bolletta di consegna
            var intermediario = rowData.intermediario;
            var tipointermediazione = rowData.tipointermediazione;
            var totordine = rowData.totaleordine;
            // 2024-08-15 - se ha percentuale B = 0% allora non va emessa la bolla di consegna corrispondente (tutto in Nero)
            var perc_B = rowData.percentuale_b; // 2024-08-15 - se la percentuale B è 0 allora nonv a emessa bolla di consegna (è tutto in nero)
            if (perc_B == 0) {
                alert("Per questo cliente non e' prevista bolla!");
                return;
            }
            var numerobolla = "";
            // solo se è in stato Consegnato si può generare la sua bolletta di consegna
            if (stato != 4) {
                alert("Questo ordine non ha ancora completato la sua preparazione e consegna oppure è già stata bollettato. Non posso fare la bolla!");
                return;
            }
            // se è presente un intermediario allora le regole di bollettazione variano da quelle dei clienti, c'è un pulsante apposito
            if (intermediario != "") {
                if (tipointermediazione == 1) {
                    alert("I clienti degli intermediari Rivenditori sono da gestire con apposito pulsante per gli intermediari. Non posso fare la bolla!");
                    return;
                }
            }
            // 2024-08-10 - Se il totale ordine è NULL allora segnala che non ci sono elementi per l'ordine indicato
            // alert(totordine);
            if (totordine == null) {
                alert("Il totale dell'ordine non e' presente. Non posso fare la bolla!");
                return;
            }
            // fine 2024-08-10
            DeterminoNumeroBollaCliente(numerobolla, databolla, idordine);            
        }
    });

    // 2024-08-09 - Bolla specifica per il Rivenditore (si può vedere solo scegliendo il cliente Rivenditore)

    // Bottone per Generare la bolletta di consegna, richiedere il numero di bolla e assegnare come databolla la dataordine 

    jQuery("#navgridBolle").jqGrid('navButtonAdd', '#pagernavBolle', {
        caption: "Bolla Riv.",
        onClickButton: function () {
            // qui deve richiedere i dati della riga selezionata
            // richiede all'utente il numero di bolletta da assegnare a questo ordine
            // registra in bollaconsegna i dati riepilogativi
            // registra in dettagliobolla i dati di dettaglio per i gruppi di prodotti e solo la sezione da registrare in bolletta
            var rowData = jQuery("#navgridBolle").jqGrid("getRowData", selrow); // sono dei fields del record rowData: hanno lo stesso nome dei campi descritti in jqGrid
            // alert(rowData.id + ' ' + rowData.cliente + ' ' + rowData.nomecliente + ' ' + rowData.dataordine);
            var idordine = rowData.id;
            var databolla = rowData.dataordine;
            var stato = rowData.statoordine; // deve essere = 8 per potere generare la bolletta di consegna
            var intermediario = rowData.intermediario;
            var tipointermediazione = rowData.tipointermediazione;            
            var numerobolla = "";
            // solo se è in stato Consegnato si può generare la sua bolletta di consegna
            if (stato != 4) {
                alert("Questo ordine non ha ancora completato la sua preparazione e consegna oppure e' gia' stata bollettato. Non posso fare la bolla!");
                return;
            }
            // se è presente un intermediario allora le regole di bollettazione variano da quelle dei clienti, c'è un pulsante apposito
            if (intermediario != "") {
                if (tipointermediazione != 1) {
                    alert("Solo per i clienti degli intermediari Rivenditori. Non posso fare la bolla!");
                    return;
                }
            } else {
                alert("Solo per i clienti degli intermediari Rivenditori. Non posso fare la bolla!");
                return;
            }
            // il rivenditore è indicato in $intermediario, devo trovare il suo codice cliente corrispondente
            alert("DeterminoNumeroBollaRivenditore");
            DeterminoNumeroBollaRivenditore(numerobolla, databolla, idordine, intermediario);            
        }
    });

    //
    jQuery("#navgridBolle").jqGrid('inlineNav', '#pagernavBolle',
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
                    url: "bolleconsegnapage.php?q=21",
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
                mtype: "POST", keys: true, url: "bolleconsegnapage.php?q=22",
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
    jQuery("#navgridBolle").jqGrid("filterToolbar", {
        searchOperators: true,
        stringResult: true,
        searchOnEnter: false,
        defaultSearch: "eq"
    });
    */

    /* ************************************************ */
    /* Dettaglio Ordine per la Bollettazione            */
    /* ************************************************ */
    // alert("DettOrd");
    jQuery("#navgridDettOrd").jqGrid({
        url: 'dettordbollepage.php?q=10',
        datatype: "xml",
        colNames: ['ID', 'ORDINE', 'SEQUENZA', 'PRODOTTO', 'NOME PRODOTTO', 'GRUPPO', 'NOME GRUPPO', 'QUANTITA', 'U.M.', 'PREZZO','TOTALE'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'ordine', index: 'ordine', width: 100, align: "left", hidden: true, editable: false, editoptions: { size: 15 } },
            { name: 'sequenza', index: 'sequenza', width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'prodotto', index: 'prodotto', hidden: true, width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 250, editable: false, editoptions: { size: 250 } },
            { name: 'gruppo', index: 'gruppo', hidden: true, width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 100, editable: false, editoptions: { size: 30 } },
            { name: 'quantita', index: 'quantita', width: 60, align: "right", editable: false, editoptions: { size: 15 } },
            { name: 'unitamisura', index: 'unitamisura', width: 50, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'prezzo', index: 'prezzo', width: 80, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'totale', index: 'totale', width: 80, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: '#pagernavDettOrd',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataDettOrd,
        caption: "Dettaglio Ordine per Bolla di Consegna Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettOrd").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettordbollepage.php",
        height: 200,
        width: 750,
        shrinkToFit: false,
        autowidth: false
    });

    jQuery("#navgridDettOrd").jqGrid('navGrid', '#pagernavDettOrd',
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettordbollepage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    // Bottone per esportare in Excel (CSV)
    /*
    jQuery("#navgridDettOrd").jqGrid('navButtonAdd', '#pagernavDettOrd', {
        caption: "CSV",
        onClickButton: function () {
            jQuery("#navgridDettOrd").jqGrid('excelExport', { "url": "clientipage.php?q=50" });
        }
    });
    */

    jQuery("#navgridDettOrd").jqGrid('inlineNav', '#pagernavDettOrd',
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
                    url: "dettordbollepage.php?q=21",
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
                mtype: "POST", keys: true, url: "dettordbollepage.php?q=22",
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

    /* ************************************************ */
    /* Dettaglio Bolla per la Bollettazione            */
    /* ************************************************ */
    // alert("DettBolla");
    jQuery("#navgridDettBolla").jqGrid({
        url: 'dettbollepage.php?q=10',
        datatype: "xml",
        colNames: ['ID', 'BOLLA', 'SEQUENZA', 'GRUPPO', 'NOME GRUPPO', 'PRODOTTO', 'NOME PRODOTTO', 'QUANTITA', 'PREZZO',
                   'TOTALE','FATTURA'],
        colModel: [
            { name: 'id', index: 'id', width: 55, align: "right", hidden: true, sorttype: "integer", editable: false, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editoptions: { readonly: true, size: 10 } }, // è obbligatorio che si chiami "id"
            { name: 'bolla', index: 'bolla', hidden: true, width: 100, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'sequenza', index: 'sequenza', width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'gruppo', index: 'gruppo', hidden: true, width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomegruppo', index: 'nomegruppo', width: 120, editable: false, editoptions: { size: 250 } },
            { name: 'prodotto', index: 'prodotto', hidden: true, width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'nomeprodotto', index: 'nomeprodotto', width: 130, editable: false, editoptions: { size: 30 } },
            { name: 'quantita', index: 'quantita', width: 60, align: "left", editable: false, editoptions: { size: 15 } },
            { name: 'prezzo', index: 'prezzo', width: 80, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'totale', index: 'totale', width: 80, align: "right", formatter: 'currency', formatoptions: { prefix: '', suffix: '', thousandsSeparator: '' }, editable: false, editoptions: { size: 15 } },
            { name: 'fattura', index: 'fattura', width: 60, align: "left", editable: false, editoptions: { size: 15 } }],
        rowNum: 25,
        rowList: [10, 20, 25, 50, 100, 1000],
        pager: '#pagernavDettBolla',
        sortname: 'id',
        viewrecords: true,
        sortorder: "asc",
        onSelectRow: RigaSelezionataDettBolla,
        caption: "Dettaglio Bolla di Consegna Cesari Pasticceria",
        loadError: function (xhr, st, err) {
            if (xhr.status != 200) { jQuery("#fieldsDettBolla").html("Errore del Server= Type: " + st + "; Response: " + xhr.status + " " + xhr.statusText); }
        },
        editurl: "dettbollepage.php",
        height: 200,
        width: 650,
        shrinkToFit: false,
        autowidth: false
    });

    jQuery("#navgridDettBolla").jqGrid('navGrid', '#pagernavDettBolla',
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
            reloadAfterSubmit: true, mtype: "POST", url: "dettbollepage.php?q=23",
            afterComplete: GestoreAfterDel  // FUNZIONA
        }, // del options
        {} // search options : multipleSearch:true, multipleGroup:true
    );

    // Bottone per esportare in Excel (CSV)
    /*
    jQuery("#navgridDettBolla").jqGrid('navButtonAdd', '#pagernavDettBolla', {
        caption: "CSV",
        onClickButton: function () {
            jQuery("#navgridDettOrd").jqGrid('excelExport', { "url": "clientipage.php?q=50" });
        }
    });
    */

    jQuery("#navgridDettBolla").jqGrid('inlineNav', '#pagernavDettBolla',
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
                    url: "dettbollepage.php?q=21",
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
                mtype: "POST", keys: true, url: "dettbollepage.php?q=22",
                successfunc: function () {
                    var $self = $(this);
                    setTimeout(function () {
                        $self.trigger("reloadGrid");
                    }, 50)
                },
                aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
            }
        });

} // fine ricostruiscimaschera()

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
      jQuery('#navgridBolle').jqGrid('restoreRow',lastsel);
    }
    // devo aggiornare le sottogriglie dettaglio ordine e dettaglio bolla
    jQuery("#navgridDettOrd").jqGrid('setGridParam', { url: "dettordbollepage.php?q=10&idordine=" + id, page: 1 });
    jQuery("#fieldsDettOrd").html("");
    //
    jQuery("#navgridDettBolla").jqGrid('setGridParam', { url: "dettbollepage.php?q=10&idordine=" + id, page: 1 });
    jQuery("#fieldsDettBolla").html("");
    //
    jQuery("#navgridDettOrd").trigger('reloadGrid');
    jQuery("#navgridDettBolla").trigger('reloadGrid');
    // inoltro ricalcolo il riepilogo 
    setTimeout(function () { $('#riepilogobolla').load('riepilogoordine.php?idordine=' + id);},300);
    //
    lastsel=id;
  }
}

function RigaSelezionataDettOrd(id) {
    selrowDettOrd = id;
    if (id && id !== lastselDettOrd) {
        if (typeof lastselDettOrd != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettOrd').jqGrid('restoreRow', lastselDettOrd);
        }
        lastselDettOrd = id;
    }
}

function RigaSelezionataDettBolla(id) {
    selrowDettBolla = id;
    if (id && id !== lastselDettBolla) {
        if (typeof lastselDettBolla != 'undefined') {  // solo se è definita si fa restore
            jQuery('#navgridDettBolla').jqGrid('restoreRow', lastselDettBolla);
        }
        lastselDettBolla = id;
    }
}

/* FUNZIONI JAVASCRIPT PER BOLLECONSEGNA */

function modificaGriglie(adesso) {
    jQuery("#navgridBolle").jqGrid('setGridParam', { url: "bolleconsegnapage.php?q=10&giorno=" + adesso, page: 1 });
    jQuery("#fieldsBolle").html("");
    //
    jQuery("#navgridDettOrd").jqGrid('setGridParam', { url: "dettordbollepage.php?q=10&idordine=" + lastselDettOrd, page: 1 });
    jQuery("#fieldsDettOrd").html("");
    //
    jQuery("#navgridDettBolla").jqGrid('setGridParam', { url: "dettbollepage.php?q=10&idordine=" + lastselDettOrd, page: 1 });
    jQuery("#fieldsDettBolla").html("");
    // aggiorno anche il contenuto della sezione Riepilogo Ordinativo 
    //
    jQuery("#navgridBolle").jqGrid('setCaption', "Bolle di Consegna Cesari Pasticceria - " + adesso).trigger('reloadGrid');
    jQuery("#navgridDettOrd").trigger('reloadGrid');
    jQuery("#navgridDettBolla").trigger('reloadGrid');
}

function CambiaData() {
    var giornata = $("#giorno").val();
    // ricarica i dati di tutte le sezioni per la nuova giornata
    // alert(giornata);
    modificaGriglie(giornata);
    return false;
}

// 2024-08-14 - Separo su una function le operazioni di registrazione bolle di consegna per poter gestire l'autonumerazione 
function DeterminoNumeroBollaCliente(numerobolla, databolla, idordine) {
    // richiedo il numero di bolletta da associare a questo ordine
    var t = numerobolla;
    var d = new Date(databolla);
    var anno = d.getFullYear();
    var urlNumBolla = "prossimabolla.php?anno=" + anno;
    // alert(urlNumBolla);
    jQuery.ajax({
        url: urlNumBolla, success: function (result) {
            // alert(JSON.stringify(result));
            var numerobolla = "";
            try {
                var jsobj = result;
                numerobolla = jsobj.numero;
            } catch (error) {
                numerobolla = "";
            }
            var numbolla = prompt("Indicare il numero di bolla di consegna per questo ordine", numerobolla);
            if (!jQuery.isNumeric(numbolla)) {
                alert(numbolla + " non corrisponde ad un numero valido! Riprovare.");
                return;
            }
            var numerobolla = parseInt(numbolla);
            if (numerobolla == NaN) {
                alert(numbolla + " non corrisponde ad un numero intero valido! Riprovare.");
                return;
            }
            RichiamaGeneraBollaCliente(numbolla, databolla, idordine);
        }
    });    
}

function RichiamaGeneraBollaCliente(numerobolla1, databolla1, idordine1) {
    // ora chiamo uno script PHP a cui demando il compito di associare una bolla a questo ordine e poi richiedo il refresh della griglia
    urlBolla = "generabollaconsegna.php?numbolla=" + numerobolla1 + "&databolla=" + databolla1 + "&idordine=" + idordine1;
    // alert(urlBolla);
    $.ajax({
        type: "GET",
        url: urlBolla,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert("Successo");
            // alert("Data = " + data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "error" : "testo errore"}
            // Se status è KO vuol dire che non ha assegnato la bolla di consegna
            // Se status è OK allora la bolla di consegna è registrata
            // da fare visualizzare all'operatore
            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Problemi a generare la bolla di consegna per questo ordine: " + data["error"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK") {
                alert("Bolla di Consegna registrata per questo ordine "); //  + data["msg"]
                // riesco da fare un refresh della griglia Bolle
                jQuery("#navgridBolle").trigger('reloadGrid');
            };
        },
        error: function (error) {
            // alert("Errore");
            alert("Errore: " + error.status + ' Testo: ' + error.statusText + ' - ' + error.responseText);
        },
        async: false
    });
}

function DeterminoNumeroBollaRivenditore(numerobolla1, databolla1, idordine1, intermediario1) {
    // richiedo il numero di bolletta da associare a questo ordine
    alert(idordine1 + " " + intermediario1);
    var t = numerobolla1;
    var d = new Date(databolla1);
    var anno = d.getFullYear();
    var urlNumBolla = "prossimabolla.php?anno=" + anno;
    alert(urlNumBolla);
    jQuery.ajax({
        url: urlNumBolla, success: function (result) {
            var numerobolla = "";
            try {
                var jsobj = result;
                numerobolla = jsobj.numero;
            } catch (error) {
                numerobolla = "";
            }
            var numbolla = prompt("Indicare il numero di bolla di consegna per questo ordine", numerobolla);
            if (!jQuery.isNumeric(numbolla)) {
                alert(numbolla + " non corrisponde ad un numero valido! Riprovare.");
                return;
            }
            var numerobolla = parseInt(numbolla);
            if (numerobolla == NaN) {
                alert(numbolla + " non corrisponde ad un numero intero valido! Riprovare.");
                return;
            }
            
            RichiamaGeneraBollaRivenditore(numbolla, databolla1, idordine1,intermediario1);
        }
    });
}

function RichiamaGeneraBollaRivenditore(numerobolla1,databolla1,idordine1,intermediario1) {
    // ora chiamo uno script PHP a cui demando il compito di associare una bolla a questo ordine e poi richiedo il refresh della griglia
    urlBolla = "generabollarivenditore.php?numbolla=" + numerobolla1 + "&databolla=" + databolla1 + "&idordine=" + idordine1 + "&intermediario=" + intermediario1;
    // alert(urlBolla);
    $.ajax({
        type: "GET",
        url: urlBolla,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert("Successo");
            // alert("Data = " + data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "error" : "testo errore"}
            // Se status è KO vuol dire che non ha assegnato la bolla di consegna
            // Se status è OK allora la bolla di consegna è registrata
            // da fare visualizzare all'operatore
            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Problemi a generare la bolla di consegna per questo ordine: " + data["error"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK") {
                alert("Bolla di Consegna registrata per questo ordine "); //  + data["msg"]
                // riesco da fare un refresh della griglia Bolle
                jQuery("#navgridBolle").trigger('reloadGrid');
            };
        },
        error: function (error) {
            // alert("Errore");
            alert("Errore: " + error.status + ' Testo: ' + error.statusText + ' - ' + error.responseText);
        },
        async: false
    });

}

function OttieniProssimaBollaPerAnno(anno) {

}

function GenerareBolleGiornaliere() {
  // richiama uno script che genera tutte le bollette di consegna per la giornata indicata in $("#giorno")
    var databolla1 = $("#giorno").val();
    urlBolle = "generabollegiornaliero.php?giorno=" + databolla1;
    // alert(urlBolle);
    $.ajax({
        type: "GET",
        url: urlBolle,
        dataType: "json",
        data: {},
        success: function (data) {
            // abbiamo riceevuto la risposta in JSON con il numero di ticket pe l'ordine selezionato
            // alert("Successo");
            // alert("Data = " + data);
            // rispJson = JSON.stringify(data);
            // alert(rispJson);
            // la risposta è nel formato: {"status":"OK|KO", "error" : "testo errore"}
            // Se status è KO vuol dire che non ha assegnato la bolla di consegna
            // Se status è OK allora la bolla di consegna è registrata
            // da fare visualizzare all'operatore
            if (data["status"] == "KO") {
                // ci sono stati problemi
                alert("Problemi a generare le bolle di consegna per questo giorno: " + data["error"]); // + " (" + data["msg"] + ")");
            };
            if (data["status"] == "OK") {
                alert("Bolle di Consegna registrate per questo giorno "); //  + data["msg"]
                // riesco da fare un refresh della griglia Bolle
                jQuery("#navgridBolle").trigger('reloadGrid');
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