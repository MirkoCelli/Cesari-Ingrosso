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

if ($_REQUEST["giorno"]) {
    $giorno = $_REQUEST["giorno"];
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

// i due valori passati per la ricerca sono: idordine e responsabile

$idordine = null;
$responsabile = null;

if (isset($_REQUEST["idordine"])){
    $idordine = $_REQUEST["idordine"];
}
if (isset($_REQUEST["responsabile"])) {
    $responsabile = $_REQUEST["responsabile"];
}

// informazioni inerenti al responsabile, cliente e ordine

$sql = "SELECT o.id AS id, o.ticket as ticket, o.preparatore as preparatore, r.NomeBreve as nomeresponsabile, c.Denominazione as nomecliente, o.dataordine as dataordine ";
$sql .= "FROM ";
$sql .= "cp_ordinecliente o LEFT OUTER JOIN cp_responsabile r ON (r.id = o.preparatore) LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
$sql .= "WHERE o.id = " . $idordine;

// eseguo il comando di query
$result = mysqli_query($db, $sql);
if (!$result) {
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
   echo ("Error description: " . mysqli_error($db));
   exit; // fine dello script php
}
if ($row = mysqli_fetch_array($result)) {
   $nomecliente = $row["nomecliente"];
   $nomeprep = $row["nomeresponsabile"];
   $idticket = $row["ticket"];
   $dataordine = $row["dataordine"];
}
mysqli_free_result($result);

?>
<html>
<head>
  <script src="js/jquery-3.7.1.min.js"></script>
  <script src="js/imballaggio.js" type="text/javascript"></script>
</head>
 <body>
  <H1>Ordine del cliente <?= $nomecliente?> per il <?= date("d/m/Y",strtotime($dataordine))?> - Preparatore <?= $nomeprep?></h1>
  <div id="ClientiDIV">
   <table align="left">

	<tr>
	   <td class="intestazioni" colspan="3">
		 <table>
			 <tr>
				 <td><label name="ticket"><?=$idticket?></label></td>
				 <td><label name="operatore"><?=$nomeprep?></label></td>
				 <td><label name="cliente"><?=$nomecliente?></label></td>                 
			 </tr>
		 </table>		   
	   </td>
	   <td class="rinfresco">
           <input type="hidden" name="dataordine" id="dataordine" value="<?=$dataordine?>" />
	   </td>
	</tr>
	<tr>
		<td class="operatori" valign="top">
            <table>
                <tr>
                    <td>Sequenza</td>
                    <td>Nome Prodotto</td>
                    <td>Quantit&agrave;</td>
                </tr>
<?php
  // qui elenchiamo tutti gli operatori preposti all'imballaggio
  $sql = "SELECT d.dettaglioordine as sequenza, p.descrizionebreve as nomeprodotto, d.quantita as quantita ";
  $sql .= "FROM ";
  $sql .= "cp_dettaglioordine d LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
$sql .= "WHERE d.ordinecliente = " . $idordine . " ";
  $sql .= "ORDER BY d.dettaglioordine ";

  // eseguo il comando di query
  $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
  if (!$result) {
     header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
     echo ("Error description: " . mysqli_error($db));
     exit; // fine dello script php
  }
  while ($row = mysqli_fetch_array($result)){
	  $seq = $row["sequenza"];
	  $nomeprod = $row["nomeprodotto"];
      $qtaprod = $row["quantita"];
?>
              <tr>
				<td align="right">
                    <?=$seq?>
                </td>
                <td align="left">
                    <?=$nomeprod?>
                </td>
                <td align="right">
                    <?=$qtaprod?>
                </td>
			  </tr>
<?php
  }
  mysqli_free_result($result);
                ?>
            </table>
        </td>
    </tr>
    <tr>
        <td class="clienti" valign="top" colspan="2">
                <?php
                // qui elenchiamo tutti gli ordini del giorno, mettendo il radio button a disabilitato se è già stato trattato
                $sql = "SELECT o.id AS id, o.ticket as ticket, o.preparatore as preparatore, r.NomeBreve as nomeresponsabile ";
                $sql .= "FROM ";
                $sql .= "cp_ordinecliente o LEFT OUTER JOIN cp_responsabile r ON (r.id = o.preparatore)  ";
                $sql .= "WHERE o.id = " . $idordine;

                // eseguo il comando di query
                $result = mysqli_query($db, $sql);
                if (!$result) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                    echo ("Error description: " . mysqli_error($db));
                    exit; // fine dello script php
                }
                if ($row = mysqli_fetch_array($result)) {
                    $ordine = $row["id"];
                    $nomepreparatore = $row["nomeresponsabile"];
                    $ticket = $row["ticket"];
                    $idpreparatore = $row["preparatore"];
                    if ($idpreparatore == $responsabile){
                        // abilitato a segnalare il completato
?>
            <button name="completato" onclick="return CompletatoOrdine(<?=$idordine?>,<?=$responsabile?>);">Completato</button>
<?php            
                    } else {
                        // non abilitato a segnalare il completato
?>
            <button name="completato" onclick="return false" disabled>Completato</button>
<?php            
                    }
                ?>
                    </td>
                </tr>
<?php
  }
  mysqli_free_result($result);
?>
   </table>
  </div>
 </body>
</html>

<?php

mysqli_close($db);

?>