var lastsel;
var selrow;
var editingRowId;
var testo;

jQuery("#navgridag").jqGrid({
   	url:'agentepage.php?q=10',
	datatype: "xml",
   	colNames:['ID','NOME'],
   	colModel:[
   		{name:'id',index:'id', width:55, align:"right",hidden:true,editable:false,searchoptions: {sopt: ['eq', 'ne', 'lt', 'le', 'gt', 'ge']},editoptions:{readonly:true,size:10}}, // è obbligatorio che si chiami "id"
   		{name:'nome',index:'nome', width:180, align:"left",editable:true,editoptions:{size:50}}],
   	rowNum:10,
   	rowList:[10,20,50,100,1000],
   	pager: '#pagernavag',
   	sortname: 'id',
    viewrecords: true,
    sortorder: "asc",
    onSelectRow: RigaSelezionata,
    caption:"Agente COFAS",
	loadError : function(xhr,st,err) {
    	if (xhr.status != 200) {jQuery("#fieldsag").html("Errore del Server= Type: "+st+"; Response: "+ xhr.status + " "+xhr.statusText);}
    },
    editurl:"agentepage.php",
	height:280,
	width: 740,
	shrinkToFit: false,
	autowidth: false
});

jQuery("#navgridag").jqGrid('navGrid','#pagernavag',
{ edit:false,
  add:false,
  del:true,
  search:true,
  deltitle:"Cancellazione Record"
  },
{height:280,reloadAfterSubmit:false}, // edit options
{height:280,reloadAfterSubmit:false}, // add options
{reloadAfterSubmit:true,mtype:"POST",url:"agentepage.php?q=23",
  afterComplete: GestoreAfterDel  // FUNZIONA
}, // del options
{} // search options : multipleSearch:true, multipleGroup:true
);

// Bottone per esportare in Excel (CSV)

jQuery("#navgridag").jqGrid('navButtonAdd','#pagernavag',{
       caption:"CSV", 
       onClickButton : function () { 
           jQuery("#navgridag").jqGrid('excelExport',{"url":"agentepage.php?q=50"});
       } 
});


jQuery("#navgridag").jqGrid('inlineNav','#pagernavag',
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
            url: "agentepage.php?q=21",
            keys: true,
            successfunc: function () {
               var $self = $(this);
               setTimeout(function () {
                     $self.trigger("reloadGrid");
                     }, 50)},			
            aftersavefunc: GestoreAfterAdd // NON FUNZIONA ADESSO
        }
    },
    editParams: { mtype: "POST", keys: true, url: "agentepage.php?q=22",
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
jQuery("#navgridZona").jqGrid("filterToolbar", {
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
	  jQuery('#navgridag').jqGrid('restoreRow',lastsel);
	}
	lastsel=id;
  }
}