<?php
include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// i parametri ricevuti sono solo:

// idriga e quantita
$idriga = null;
$quantita = null;

if (isset($_GET["idriga"])){
    $idriga = $_GET["idriga"];
}

if (isset($_GET["qta"])) {
    $quantita = $_GET["qta"];
}

if (isset($idriga) && isset($quantita)){
 // connect to the database
 $db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
 if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
 }

 mysqli_select_db($db, $dbname) or die("Error conecting to db.");
 // aperto il database

 // esegue l'aggiornamento immediato della riga del dettaglioschema con le nuove quantità
 $sql = "UPDATE cp_dettaglioschema SET quantita = $quantita WHERE id = $idriga ";
 // eseguo il comando di query
 $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
 if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
 }
// mysqli_free_result($result);
 mysqli_close($db);
}
?>