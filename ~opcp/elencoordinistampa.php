<?php
use function CommonMark\Parse;
  // (c) 2024-08-06 Robert Gasperoni
  // elenco in JSON delle informazioni utili alla stampa dei ticket associati agli ordini di un giorno indicato nel GET o nel POST
include("dbconfig.php");

$oggi = date("Y-m-d");
$giorno = $oggi;
$flgGiorno = false;

if ($_REQUEST["giorno"]) {
    $giorno = $_REQUEST["giorno"];
    $flgGiorno = true;
}
$dataodierna = date_create($giorno);
$adesso = date_format($dataodierna, "d/m/Y");

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

$sql = "SELECT o.id AS idordine, c.sequenza as sequenza, C.id AS idcliente, c.Denominazione AS nomecliente, o.ticket AS numeroticket, YEAR(o.dataordine) AS annocomp ";
$sql .= "FROM cp_cliente c JOIN cp_ordinecliente o ON (o.cliente = c.id) WHERE ";
$sql .= "o.dataordine = DATE('" . $giorno . "') ORDER BY c.sequenza";

$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// determino l'ordine di indice minimo e indice massimo
// i dati che fornisco al client sono: {"giorno" : "$giorno", "min" : "$min", "max" : "$max", "elenco" : [{sequenza, idordine, idcliente, annocomp, ticket}]}
$seq = 0;
$min = 300000;
$max = 0;
$elenco = "";
$risposta = "";
while ($row = mysqli_fetch_array($result)) {
    $seq++;
    $sequenza = $row["sequenza"];
    $idordine = $row["idordine"];
    if ($idordine < $min){
        $min = $idordine;}
    if ($idordine > $max){
        $max = $idordine;}
    $idcliente = $row["idcliente"];
    $nomecliente = $row["nomecliente"];
    $ticket = $row["numeroticket"];
    $annocomp = $row["annocomp"];
    if ($elenco != "") {
        $elenco .= ",";
    }
    $elenco .= "{\"sequenza\" : \"$sequenza\", \"idordine\" : \"$idordine\", \"idcliente\" : \"$idcliente\", \"nomecliente\" : \"$nomecliente\", \"annocomp\": \"$annocomp\", \"ticket\" : \"$ticket\"}";
}
$risposta .= "{\"giorno\" : \"$giorno\", \"min\" : \"$min\", \"max\" : \"$max\", \"count\" : \"$seq\", \"elenco\" : [$elenco]}";
// 2024-08-13 - devo fare questa conversione a JSON Object locale pre poter avere il testo in utf8 da inviare al client
$jsonobj = Json_decode($risposta);

mysqli_free_result($result);
mysqli_close($db);
// invio la risposta JSON con i dati richiesti
header("Content-type: text/json; charset=utf8");
echo Json_encode($jsonobj);
?>

