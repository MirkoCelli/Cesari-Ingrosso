<?php
// PRODUZIONE - 2024.06.28
/* Qui carichiamo i dati di produzione previsti per la giornata indicata: oggi � la data di default */
include("dbconfig.php");

// PRODUZIONE PRODOTTI - 2024-06-27
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$oggi = date("Y-m-d");
$giorno = $oggi;
$flgGiorno = false;

if ($_REQUEST["giorno"]) {
    $giorno = $_REQUEST["giorno"];
    $flgGiorno = true;
}
$dataodierna = date_create($giorno);
$adesso = date_format($dataodierna, "d/m/Y");

$passo = null;
if ($_REQUEST["passo"]) {
    $passo = $_REQUEST["passo"];
}

$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}
// aperto il database

?>
<html>
<head>
   <link rel="stylesheet" href="css/imballaggio.css" />
<?php
  // if ($flgGiorno) {
  if (false){
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
    <script src="js/imballaggio.js" type="text/javascript"></script>
</head>
 <body>
  <H1>Imballaggio per il <?= $adesso ?></h1>
     <div id="ClientiDIV" class="ImballaggioDiv">
         <table align="left">
             <!--
	<tr><td>
	    <table id="navgridClienti"></table>
		<div id="pagernavClienti"></div>
		<! - - definisce il navigatore e la griglia - - >	
		<script src="js/imballaggiopage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsClienti"></div>
	</td></tr>
-->
             <tr>
                 <td colspan="2">
                     <label name="dataimb">Data di imballaggio </label>
                     <input type="date" name="giornata" id="giornata" value="<?=$giorno?>" />&nbsp;&nbsp;
                     <button type="button" name="cambiadata" value="cambia" onclick="return CambiareData();">Cambia Data</button>
                 </td>  
                 <td class="comandi">
                     <button type="button" name="prepara" onclick="return PreparaTutto();">Prepara Ordini</button>
                 </td>
                 <td>
                     <button type="button" name="consegna" onclick="return ConsegnaTutto();">Consegna Ordini</button>
                 </td>
                 <!--
                 <td class="comandi">
                     <button type="button" name="conferma" onclick="return ConfermaAssegnazioneTicket();">Conferma</button>

                 </td>
                 <td class="biglietto">
                     <label name="numticket">Ticket:</label>
                     <input type="text" name="numeroticket" id="numeroticket" />
                     <button type="button" name="apriticket" onclick="return ApriOrdineTicket();">Apri Ordine</button>

                 </td>
                 <td class="rinfresco">
                     <input type="hidden" name="giorno" id="giorno" value="<?= $giorno ?>" />
                     <button type="button" name="refresh" onclick="return RefreshImballaggio();">Refresh</button>

                 </td>
                 -->
             </tr>
             <!--
             <tr>
                 <td class="intestazioni" colspan="3">
                     <table>
                         <tr>
                             <td>
                                 <label name="ticket"></label>
                             </td>
                             <td>
                                 <label name="operatore"></label>
                             </td>
                             <td>
                                 <label name="cliente"></label>
                             </td>
                         </tr>
                     </table>
                 </td>

             </tr>
             -->
             <tr>
                 <td class="clienti" valign="top" colspan="2">
                     <table>
                         <?php
                // qui elenchiamo tutti gli ordini del giorno, mettendo il radio button a disabilitato se è già stato trattato
                $sql = "SELECT o.id AS id, c.NomeBreve AS nomecliente, o.ticket AS ticket, o.stato AS stato, c.intermediario AS intermediario, i.denominazione AS nomeintermediario, o.preparatore AS preparatore ";
                $sql .= "FROM ";
                $sql .= "cp_ordinecliente o, cp_cliente c  ";
                $sql .= "LEFT OUTER JOIN cp_intermediario i ON (i.id = c.intermediario) ";
                $sql .= "WHERE c.id = o.cliente AND (o.stato = 7 OR o.stato = 8) AND o.dataordine = DATE('" . $giorno . "') ";
                $sql .= "ORDER BY c.sequenza ";

                // eseguo il comando di query
                $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
                if (!$result) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                    echo ("Error description: " . mysqli_error($db));
                    exit; // fine dello script php
                }
                while ($row = mysqli_fetch_array($result)) {
                    $idordine = $row["id"];
                    $nomecliente = $row["nomecliente"];
                    $ticket = $row["ticket"];
                    $statoordine = $row["stato"];
                    $intermediario = $row["intermediario"];
                    $nomeintermediario = $row["nomeintermediario"];
                    $preparatore = $row["preparatore"];
                         ?>
                         <tr>
                             <td align="left" class="ordinativi2">
                                 <?php
                   if ($ticket != null){
                                 ?>
                                 <input type="radio" id="ord_<?= $idordine ?>" name="ordini" value="<?= $idordine ?>" disabled />
                             </td>
                             <td class="ordinativi2">
                                 <label for="op_<?= $idordine ?>">
                                     <?php
                   } else {
                                     ?>
                                     <input type="radio" id="ord_<?= $idordine ?>" name="ordini" value="<?= $idordine ?>" />
                             </td>

                             <td>
                                 <label for="op_<?= $idordine ?>">
                                     <?php
                   }
                   if ($intermediario != null) {
                                     ?>
                    [<?= $nomeintermediario?>]
                                     <?php
                   }
                                     ?>
                                     <?= $nomecliente ?>
                                     <?php
                   if ($ticket != null){
                                     ?>
                                    [ <?= $ticket ?> ]
                                     <?php
                   }
                                     ?>
                                 </label>
                             </td>

                         </tr>
                         <?php
  }
  mysqli_free_result($result);
                         ?>
                     </table>

                 </td>

             </tr>
         </table>
     </div>

 </body>
</html>

<?php

mysqli_close($db);

?>