<?php
// (c) 2024-08-14 - Robert Gasperoni - Genera Bolla Cliente

include("dbconfig.php");

// RIEPILOGO ORDINI PER LE BOLLE DI CONSEGNA
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$numbolla = $_REQUEST["numbolla"];
$databolla = $_REQUEST["databolla"];
$idordine = $_REQUEST["idordine"];

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

header("Content-type: application/json");
$erroreDB = "{\"status\" : \"KO\" , \"error\" : \"Non è stato possibile collegarsi al db per l'ordine $idordine \"}";

mysqli_select_db($db, $database) or die($erroreDB);

$sql = "INSERT INTO cp_bollaconsegna (dataconsegna, numbolla,totalebolla,cliente,ordine,rapporto,fatturato) \n";
$sql .= "SELECT o.dataordine AS dataconsegna, " . $numbolla . " AS numbolla, ";
/* $sql .= "SUM( CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario) ) AS totalebolla, ";*/ // 2024-05-17 devo considerare anche i KG
$sql .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario)) ) AS totalebolla, \n";
$sql .= "c.id AS cliente, o.id AS ordine, ";
$sql .= "(((r.perc_b % 123456) / 1000)) AS rapporto, ";
$sql .= " 0 AS fatturato \n";
$sql .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) ";
$sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) \n";
$sql .= "WHERE o.id = " . $idordine;

// eseguo la query e mi faccio dare id del record in bollaconsegna
$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
$idbolla = mysqli_insert_id($db);
/* // 27/07/2024 - non deve separare per singola riga dell'ordine in B e N, ma il totale del gruppo prodotto va separato in B e N
$sql = "SELECT d.gruppo AS gruppo, g.NomeGruppo as nomegruppo, dl.prezzounitario as prezzo, SUM( CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) ) AS quantita_in_bolla, ";
$sql .= "SUM( CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * dl.prezzounitario ) AS totale_in_bolla,";
$sql .= "SUM( FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS qta_n, ";
$sql .= "SUM( FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) * dl.prezzounitario ) AS tot_n ";
// 2024-07-11 aggiungo criptate anche le informazioni sulle quantità complessiva ordine e il totale complessivo ordine e la qta_n
$sql .= ", SUM( d.quantita ) AS b,";
$sql .= "SUM( FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS n, ";
$sql .= "SUM( d.quantita * dl.prezzounitario ) AS t ";
// fine 2024-07-11
$sql .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) ";
$sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) ";
$sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
$sql .= "WHERE o.id = " . $idordine . " ";
$sql .= "GROUP BY d.gruppo, dl.prezzounitario ";
$sql .= "ORDER BY d.gruppo, dl.prezzounitario ";
*/

// 27/07/2024 - La suddivisione in B e N si fa sul totale quantità per ogni gruppo prodotto/prezzo unitario
/* // 17/05/2024 è stata sostituita da una nuova query per gestire i KG
$sql = "SELECT d.gruppo AS gruppo, g.NomeGruppo as nomegruppo, IFNULL(dl.prezzounitario, dg.prezzounitario) as prezzo, ";
$sql .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM( d.quantita) ) AS quantita_in_bolla, ";
$sql .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM(d.quantita)) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS totale_in_bolla, ";
$sql .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) AS qta_n, ";
$sql .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS tot_n, ";
$sql .= "SUM( d.quantita ) AS b, ";
$sql .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM( d.quantita) ) AS n, ";
$sql .= "SUM( d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS t ";
$sql .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) ";
$sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) ";
$sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
$sql .= "WHERE o.id = " . $idordine . " ";
$sql .= "GROUP BY d.gruppo, prezzo ";
$sql .= "ORDER BY d.gruppo, prezzo ";
*/

// 2024-08-17 - ho una nuova query con i calcoli necessari per gestire i KG (avrò dei problemi con gli arrotondamenti dei prezzi se i pesi sono inferiori agli etti hg)

$sql = "SELECT d.gruppo AS gruppo, g.NomeGruppo as nomegruppo, IFNULL(dl.prezzounitario, dg.prezzounitario) as prezzo, d.unitamisura AS um, dl.unitamisura AS uml, dg.unitamisura AS umg, \n";
$sql .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita),(((r.perc_b % 123456) / 1000) / 100) * d.quantita) ) AS quantita_in_bolla, \n";
$sql .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario)) ) AS totale_in_bolla, \n";
$sql .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita),(((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS qta_n, \n";
$sql .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),ROUND((((r.perc_n % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2)) ) AS tot_n, \n";
$sql .= "SUM( d.quantita ) AS b, \n";
$sql .= "SUM( IF (dl.unitamisura = 'PZ', FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita ), (((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS n, \n";
$sql .= "SUM(ROUND(d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2) ) AS t \n";
$sql .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) \n";
$sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) \n";
$sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) \n";
$sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) \n";
$sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) \n";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) \n";
$sql .= "WHERE o.id = " . $idordine . " ";
$sql .= "GROUP BY d.gruppo, prezzo \n";
$sql .= "ORDER BY d.gruppo, prezzo ";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// $row = mysqli_fetch_array($result,MYSQL_ASSOC);
$riga = 0;
while ($row = mysqli_fetch_array($result)) {
    $riga++;
    $gruppo = $row["gruppo"];
    $nomegruppo = $row["nomegruppo"];
    $prezzo = $row["prezzo"];
    $qtabolla = $row["quantita_in_bolla"];
    $totalebolla = $row["totale_in_bolla"];
    $qta_n = $row["qta_n"];
    $tot_n = $row["tot_n"];
    $b = $row["b"] * 1000 + rand(3,1000) * 123456; // tre cifre decimali
    $n = $row["n"] * 1000 + rand(3, 1000) * 123456; // tre cifre decimali
    $t = $row["t"] * 100 + rand(3, 1000) * 123456; // due cifre decimali
    $qrystr = "INSERT INTO cp_dettagliobolla (bolla,dettagliordine,gruppo,prodotto,quantita,prezzounitario,totale,b,n,t) VALUES (";
    $qrystr .= $idbolla . ",";
    $qrystr .= $riga . ",";
    $qrystr .= $gruppo . ",";
    $qrystr .= "NULL,"; // prodotto non si deve indicare sono raggruppati tutti assieme
    $qrystr .= $qtabolla . ",";
    $qrystr .= $prezzo . ",";
    $qrystr .= $totalebolla . ",";
    $qrystr .= $b . ",";
    $qrystr .= $n . ",";
    $qrystr .= $t . ")";
    $result1 = mysqli_query($db, $qrystr);
    if (!$result1) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $iddettbolla = mysqli_insert_id($db);
};
mysqli_free_result($result);

// ora devo registrare l'ordine in stato = 9 BOLLETTATO (significa che la bolla di consegna è stata generata)
$qrystr = "UPDATE cp_ordinecliente SET stato = 9 WHERE id = " . $idordine;
$result2 = mysqli_query($db, $qrystr);
if (!$result2) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// ho completato le operazioni di inserimento bolla
echo "{\"status\" : \"OK\" , \"error\" : \"\"}";
?>