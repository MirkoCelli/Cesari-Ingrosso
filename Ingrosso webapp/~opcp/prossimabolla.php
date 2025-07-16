<?php
  // (c) 2024-08-14 - Trova il numero per la prossima bolla per l'anno indicato come da data bolla
include("dbconfig.php");

// BOLLE CONSEGNA
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

// mi deve dare l'anno della data bolla altrimenti prende la data odierna
$anno = date("Y");

if (isset($_REQUEST["anno"])){
    $anno = $_REQUEST["anno"];
}

$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");

$sql = "SELECT IFNULL(MAX(numbolla)+1,1) AS numero FROM cp_bollaconsegna WHERE YEAR(dataconsegna) = " . $anno;
$result = mysqli_query($db, $sql);
while ($row = mysqli_fetch_array($result)) {
    $valore = $row["numero"];
}

$risp = "{\"numero\" : $valore }";

mysqli_free_result($result);
mysqli_close($db);

// ritorno un JSON come risposta
header("Content-type: application/json");
echo $risp;

?>