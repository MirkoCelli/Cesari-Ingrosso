<!--
<html>
 <body>
  <H1>Produzione Prodotti</h1>
  <div id="ClientiDIV">
   <table align="left">

	<tr><td>
	    <table id="navgridClienti"></table>
		<div id="pagernavClienti"></div>
		<script src="js/produzionepage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsClienti"></div>			
	</td></tr>
   </table>
  </div>
 </body>
</html>
-->
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


if ($_REQUEST["giorno"]){
    $giorno = $_REQUEST["giorno"];
}
$dataodierna = date_create($giorno);
$adesso = date_format($dataodierna, "d/m/Y");

$passo = 1;
if ($_REQUEST["passo"]){
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

// devo contare il numero di elementi
$numeroRighe = 15;
$numeroColonne = 0;

$sql = "SELECT p.descrizionebreve as nomeprodotto, SUM(IFNULL(d.quantita,0)) as totaleprodotto ";
$sql .= "FROM ";
$sql .= "cp_ordinecliente o ";
$sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) ";
$sql .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
$sql .= "WHERE o.dataordine = DATE('" . $giorno . "') "; /* data del giorno da visualizzare */
$sql .= "GROUP BY p.descrizionebreve "; /* indice dell'account */
$sql .= "ORDER BY p.sequenza ";

// eseguo il comando di query
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/produzione.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/mainpage.js"></script>

   <!-- <script src="<?= $serverpath ?>tabs.js"></script>-->
</head>
<body>
    <!--Main Page-->
<div name="intestazione" class="Intestazioni">
  <center>
    <table class="intesta">
       <tr>
           <td align="center" class="intestac">
<?php
  /*
   if (isset($passo)){
?>
              <form method="post" action="produzione.php">
<?php       
   } else {
?>
              <form method="post" action="produzione.php" target="_blank">
<?php               
   }
   */
?>
                  <span id="giornaliero">
                      <input id="giorno" name="giorno" type="date" value="<?=$giorno ?>" required class="data" />
                      <!--<input type="submit" name="invia" value="Verifica" class="bottone" />-->
                      <button type="button" name="invia" value="Verifica" class="bottone" onclick="return CambiaPaginaProduzione();">Cambia data</button>
                      <input type="hidden" id="passo" name="passo" value="1" />
                  </span>
<?php
/*
              </form>
*/
?>
           </td>
       </tr>
    </table>
  </center>
</div>
<div name="contenuto" class="Contenuti">
<center>
  <table class="tabellacontenuto">
      <tr><td colspan="2" align="center">Produzione del <?= $adesso ?></td></tr>
  <tr  style="vertical-align: top">
<?php
// ora dobbiamo fare la query, verificare il numero di prodotti, calcolare il numero di colonne in base al numero di righe per colonna
// poi leggere in sequenza i vari prodotti con le corrispondenti quantit�
     $iprod = -1;
     $oldcolonna = -1;
     while ($row = mysqli_fetch_array($result)){
         $iprod++;
         $iriga = $iprod % $numeroRighe; // resto modulo il numero di righe
         $phpvers = phpversion();
         if (substr($phpvers,0,1) >= "7"){
            $icolonna = intdiv($iprod, $numeroRighe); // divisione intera e non frazionaria    
         } else {
            $icolonna = round($iprod / $numeroRighe); // divisione intera e non frazionaria                
         }         
         if ($oldcolonna != $icolonna){
             // predisporre per una nuova colonna e l'avvio di una nuova tabella
             if ($oldcolonna != -1){ // devo chiudere la tabelkla delle righe e la colonna precedente 
?>
        </table>
      </td>
<?php
             }
        $oldcolonna = $icolonna;
?>
      <td class="tabellacontenuto">
        <table style="vertical-align: top">
          <tr><td align="right">Quantit&agrave;</td><td align="left">Nome Prodotto</td></tr>
<?php
         }
     // ora devo scrivere in una TR e TD quantit� /TD TD nomeprodotto /TD /TR per il prodotto corrente
        $qta = $row["totaleprodotto"];
        $prod = $row["nomeprodotto"];
?>
          <tr><td align="right"><?=number_format($qta,0,',','.')?></td><td align="left"><?=$prod?></td></tr>
<?php
         
     }
     if ($iprod == -1){
?>
            <tr><td>Non ci sono prodotti</td><td>in questa data <?=date_format($dataodierna,"d/m/Y")?></td></tr>
<?php            
     }
?>
        </table>
      </td>
  </tr>
  </table>
</center>
</div>
</iframe>
</body>
</html>
