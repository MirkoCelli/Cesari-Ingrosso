var lastsel;
var selrow;
var editingRowId;
var testo;

// PERIODO DI FERIE - 2024-12-30

/*
 * per modificare le caratteristiche grafiche della griglia vedere in themes/ui.jqgrid.css e cambiare i parametri per
 * 
 / * Grid * /
.ui - jqgrid { position: relative; font - size: 11px; }  <- qui metter ela dimensione della font 
.ui - jqgrid.ui - jqgrid - view { position: relative; left: 0px; top: 0px; padding: .0em; }
 */

jQuery("#navgridFerie").jqGrid({
    url:'feriepage.php?q=10',
    datatype: "xml",
    colNames:['ID','DATA INIZIO','DATA FINE','ANNOTAZIONI'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
        { name: 'datainizio', index: 'datainizio', width: 80, formatter: 'date', formatoptions: { srcformat: "Y-m-d", newformat: "d/m/Y" }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'datafine', index: 'datafine', width: 80, formatter: 'date', formatoptions: { srcformat: "Y-m-d", newformat: "d/m/Y" }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'annotazioni', index: 'annotazioni', width: 480, editable: true, editoptions: { size: 1000 } }],
    rowNum: 25,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavFerie',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    caption:"Periodo di Ferie Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsFerie").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"feriepage.php",
    height:230,
	width: 1780,
    shrinkToFit: false,
    autowidth: false
});

jQuery("#navgridFerie").jqGrid('navGrid','#pagernavFerie',
{ edit:false,
  add:false,
  del:true,
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"feriepage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridFerie").jqGrid('navButtonAdd','#pagernavFerie',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridFerie").jqGrid('excelExport',{"url":"feriepage.php?q=50"});
       } 
});

// Bottone per invio email di avviso delle ferie

jQuery("#navgridFerie").jqGrid('navButtonAdd', '#pagernavFerie', {
    caption: "EMail",
    onClickButton: function () {
        // alert("Invio delle email di avviso periodo di ferie");
        // chiama un AJAX per inviare il periodo delle ferie selezionato a tutti i clienti
        // in selrow ho la riga selezionata
        // jQuery('#navgridFerie').jqGrid('restoreRow', lastsel);
        var inizio = jQuery('#navgridFerie').jqGrid('getCell', selrow, 'datainizio');
        var fine = jQuery('#navgridFerie').jqGrid('getCell', selrow, 'datafine');
        urlMail = "inviaemail.php?inizio=" + inizio + "&fine=" + fine;
        // alert(urlMail);
        jQuery.ajax({
            url: urlMail, success: function (result) {
                // alert(JSON.stringify(result));
                var parsed_data = JSON.parse(result);
                alert("Comunicazione inviata via email: clienti=" + parsed_data.numclienti + ", numerrati=" + parsed_data.numerrati + ", errori="+parsed_data.errori);
            }
        });
    }
});

jQuery("#navgridFerie").jqGrid('inlineNav','#pagernavFerie',
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
            url: "feriepage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "feriepage.php?q=22",
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
jQuery("#navgridFerie").jqGrid("filterToolbar", {
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
      jQuery('#navgridFerie').jqGrid('restoreRow',lastsel);
    }
    lastsel=id;
  }
}
