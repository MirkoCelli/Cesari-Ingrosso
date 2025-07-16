<?php
// CONSEGNA ORDINI - 2024.08.07
/* Segna come consegnati tutti gli ordini del giorno in stato preparato, giorno di deafult è oggi */

include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}

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
// eseguo in un unico comando l'aggiornamento da stato 8 = PREPARATO in stato 4 = CONSEGNATO

$errori = "";

$sql = "UPDATE cp_ordinecliente SET stato = 4 ";
$sql .= "WHERE stato = 8 AND dataordine = DATE('" . $giorno . "') "; // 7 = DA_PRODURRE, 8 = PREPARATO, 4 = CONSEGNATO

$result = mysqli_query($db, $sql);

if (!$result) {
    $errori = "Errore in Consgena Ordini: " . mysqli_error($db);
}

// chiude database
mysqli_close($db);

if ($errori != "") {
    header("Content-type: application/json");
    echo "{\"status\": \"ERROR\", \"errore\" : \"$errori\"}";
} else {
    header("Content-type: application/json");
    echo "{\"status\": \"OK\"}";
}
?>