var lastsel;
var selrow;
var editingRowId;
var testo;

// AUTORIZZAZIONESUPEROSPESA - 2024-06-06

// Elenco dei valori ammessi per Responsabile
var elencoResp = ":;"; // indica un valore null di default se � NULLABLE in campo di destinazione
$.ajax({
    type: "GET",
    url: "autorizzasuperospesapage.php?q=7",
    dataType: "xml",
    success: function (xml) {
        // abbiamo in xml il nostro file provincia, ora lo dobbiamo assegnare a elencoProvincie
        elencoResp += $(xml).find('rows').text();
    },
    async: false
});

/*
 * per modificare le caratteristiche grafiche della griglia vedere in themes/ui.jqgrid.css e cambiare i parametri per
 * 
 / * Grid * /
.ui - jqgrid { position: relative; font - size: 11px; }  <- qui metter ela dimensione della font 
.ui - jqgrid.ui - jqgrid - view { position: relative; left: 0px; top: 0px; padding: .0em; }
 */

jQuery("#navgridClienti").jqGrid({
    url:'autorizzasuperospesapage.php?q=10',
    datatype: "xml",
    colNames:['ID', 'ORDINE', 'NOME CLIENTE','DATA ORDINE', 'CLIENTE', 'DATA RICHIESTA', 'RESPONSABILE','NOME RESP.','LIMITE CONCESSO','DATA AUTORIZZAZIONE','ANNOTAZIONI'],
    colModel:[
        {name:'id',index:'id', width:55, align:"right",hidden:true,sorttype:"integer",editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // � obbligatorio che si chiami "id"
        { name: 'ordine', index: 'ordine', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'nomecliente', index: 'nomecliente', width: 150, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'dataordine', index: 'dataordine', width: 120, formatter: 'date', formatoptions: { srcformat: "Y-m-d", newformat: "d/m/Y" }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: false, editoptions: { size: 10 } },
        { name: 'cliente', index: 'cliente', width: 100, align: "left", editable: false, editoptions: { size: 15 } },
        { name: 'datarichiesta', index: 'datarichiesta', width: 120, formatter: 'date', formatoptions: { srcformat: "Y-m-d", newformat: "d/m/Y" }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name: 'responsabile', index: 'responsabile', width: 50, editable: true, edittype: "select", editoptions: { value: elencoResp } },
        { name: 'nomeresponsabile', index: 'nomeresponsabile', width: 100, editable: false, editoptions: { size: 100 } },
        { name: 'limiteconcesso', index: 'limiteconcesso', width: 150, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, formatter: 'number', formatoptions: { onError: function (rowid, jqXHR, textStatus) { alert('Problema in Limite Concesso!' + textStatus); } }, editable: true, editoptions: { size: 10 } },
        { name: 'dataautorizzazione', index: 'dataautorizzazione', width: 180, formatter: 'date', formatoptions: { srcformat: "Y-m-d H:i:s", newformat: "d/m/Y H:i:s" }, searchoptions: { sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge'] }, editable: true, editoptions: { size: 10 } },
        { name:'annotazioni', index: 'annotazioni', width: 580, editable: true, editoptions: { size: 1000 } }],
    rowNum: 25,
    rowList:[10,20,25,50,100,1000],
    pager: '#pagernavClienti',
    sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    caption:"Autorizzazione Supero Spesa Cesari Pasticceria",
    loadError : function(xhr,st,err) {
        if (xhr.status != 200) {jQuery("#fieldsClienti").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"autorizzasuperospesapage.php",
    height:530,
	width: 1750,
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
    {
        reloadAfterSubmit: true, mtype: "POST", url:"autorizzasuperospesapage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridClienti").jqGrid('navButtonAdd','#pagernavClienti',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridClienti").jqGrid('excelExport', { "url":"autorizzasuperospesapage.php?q=50"});
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
           url: "autorizzasuperospesapage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: {
        mtype: "POST", keys: true, url: "autorizzasuperospesapage.php?q=22",
                  successfunc: function () {
                      var $self = $(this);
                      setTimeout(function () {
                           $self.trigger("reloadGrid");
                           }, 50)} ,
                  aftersavefunc: GestoreAfterEdit // NON FUNZIONA ADESSO
                 }
 });

// toolbar per la ricerca (filtri in testa alle colonne)
/* // non � molto estetico
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
    // formid � undefined
    // alert(postdata); // numero di riga (id)
    // alert(response.responseText); // risposta dal server in JSON
    // var oldId = $.parseJSON(response.responseText)	
}


function RigaSelezionata(id){
   selrow = id;
   if(id && id!==lastsel){
    if(typeof lastsel != 'undefined'){  // solo se � definita si fa restore
      jQuery('#navgridClienti').jqGrid('restoreRow',lastsel);
    }
    lastsel=id;
  }
}