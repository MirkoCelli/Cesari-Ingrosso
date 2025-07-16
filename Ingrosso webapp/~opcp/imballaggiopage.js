var lastsel;
var selrow;
var editingRowId;
var testo;

// BOLLECONSEGNE - 2024-06-06

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
    caption:"Clienti Cesari Pasticceria",
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