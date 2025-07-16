<?php
  // (c) 2024 - Robert Gasperoni - 2024-07-10 Gestione della bollettazione della giornata odierna
  $giorno = date("Y-m-d");
  $flgGiorno = false;

  if (isset($_REQUEST["giorno"])){
    $giorno = $_REQUEST["giorno"];
    $flgGiorno = true;
  }
?>
<html>
 <head>
 <?php
     if ($flgGiorno) {
 ?>
     <link rel="stylesheet" href="css/imballaggio.css" />
     <link rel="stylesheet" type="text/css" media="screen" href="themes/redmond/jquery-ui-1.8.2.custom.css" />
     <link rel="stylesheet" type="text/css" media="screen" href="themes/ui.jqgrid.css" />
     <link rel="stylesheet" type="text/css" media="screen" href="themes/ui.multiselect.css" />
     <link rel="stylesheet" type="text/css" media="screen" href="css/navgriddemo.css" />
     <script src="js/jquery.min.js" type="text/javascript"></script>
     <script src="js/jquery-ui-1.8.2.custom.min.js" type="text/javascript"></script>
     <script src="js/jquery.layout.js" type="text/javascript"></script>
     <script src="js/lang/grid.locale-it.js" type="text/javascript"></script>
     <script type="text/javascript">
        $.jgrid.no_legacy_api = true;
        $.jgrid.useJSON = true;
     </script>
     <script src="js/jquery.jqGrid.min.js" type="text/javascript"></script>
     <script src="js/jquery.tablednd.js" type="text/javascript"></script>
     <script src="js/jquery.contextmenu.js" type="text/javascript"></script>
     <script src="js/ui.multiselect.js" type="text/javascript"></script>
     <link href="tabs2.css" rel="stylesheet" type="text/css" />
     <script src="tabs.js"></script>
 <?php
  }
 ?>
 </head>
 <body>
  
  <div id="ClientiDIV">
   <table align="left">
	<tr>
	  <td align="left" colspan="4">
        <font size="5">
            <b>Bolle Consegna</b>
        </font>
        <label name="dataordine">Data Ord.:</label> <input type="date" id="giorno" name="giorno" value="<?=$giorno?>" /> <button name="cambiadata" id="cambiadata" value="CambiaData" onclick="return CambiaData();">Cambia Data</button>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <button name="generabolle" id="generabolle" value="GeneraBolle" onclick="return GenerareBolleGiornaliere();">Genera Bolle Giornaliere</button>

	  </td>
	</tr>
	<tr><td colspan="3">
	    <table id="navgridBolle"></table>
		<div id="pagernavBolle"></div>
		<!-- definisce il navigatore e la griglia -->	
		<script src="js/bolleconsegnapage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsBolle"></div>			
	</td></tr>
    <!-- Elenco Dettagli Ordini del giorno  -->
    <tr>
        <td valign="top">
           <div id="riepilogobolla">Qui ci va il riepilogo della bolla selezionata</div>
        </td>
		
        <td>
            <table id="navgridDettBolla"></table>
            <div id="pagernavDettBolla"></div>
            <br />
            <br />
            <div id="fieldsDettBolla"></div>
        </td>
		
        <td>
            <table id="navgridDettOrd"></table>
            <div id="pagernavDettOrd"></div>
            <br />
            <br />
            <div id="fieldsDettOrd"></div>
        </td>

	</tr>
   </table>
  </div>
 </body>
</html>