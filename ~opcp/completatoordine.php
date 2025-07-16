<?php
// COMPLETATO ORDINE - 2024.07.08
/* Si conferma il completamento dell'ordine indicato purchè fosse in stato = 7 e il responsabile preparatore è quello segnalato assieme all'ordine
   mette come stato dell'ordine il valore 8 == PREPARATO */
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

$sql = "SELECT COUNT(*) as conteggio ";
$sql .= "FROM ";
$sql .= "cp_ordinecliente o ";
$sql .= "WHERE o.id = " . $idordine . " AND o.preparatore = " . $responsabile . " AND o.stato = 7 ";

// eseguo il comando di query
$result = mysqli_query($db, $sql);
if (!$result) {
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
   echo ("Error description: " . mysqli_error($db));
   exit; // fine dello script php
}
if ($row = mysqli_fetch_array($result)) {
    $conta = $row["conteggio"];
    mysqli_free_result($result);
    if ($conta == 0) {
        // non si può confermare il completato
        header("Content-type: application/json");
        echo "{\"status\" : \"KO\" , \"error\" : \"Non si può segnalare come completato l'ordine $idordine per problemi con stato ordine o preparatore non corrispondente\"}";
    } else {
        // esegue la query di aggiornamento dello stato per l'ordine
        $sql = "UPDATE cp_ordinecliente SET stato = 8 WHERE id = " . $idordine;
        $result1 = mysqli_query($db, $sql);
        if (!$result1) {
            //           header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
//           echo ("Error description: " . mysqli_error($db));
            header("Content-type: application/json");
            echo "{\"status\" : \"KO\" , \"error\" : \"Errore aggiornamento stato ordine: " . mysqli_error($db) . "\"}";
            exit; // fine dello script php
        }
        // si può confermare il completato
        header("Content-type: application/json");
        echo "{\"status\" : \"OK\" , \"error\" : \"\"}";
    }
} else {
    mysqli_free_result($result);
}
mysqli_close($db);

?>