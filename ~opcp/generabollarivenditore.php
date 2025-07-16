<?php
// (c) 2024-08-09 - Robert Gasperoni - Genera la Bolla giornaliera per il RIvenditore indicato se non è già presente

include("dbconfig.php");

// RIEPILOGO ORDINI PER LE BOLLE DI CONSEGNA
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
   //  exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$numbolla = $_REQUEST["numbolla"];
$databolla = $_REQUEST["databolla"];
$dataconsegna = $databolla;
$idordine = $_REQUEST["idordine"]; // indica l'ordine selezionato di un cliente del RIvenditore
$intermediario = $_REQUEST["intermediario"]; // indica il rivenditore intermediario

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

header("Content-type: application/json");
$erroreDB = "{\"status\" : \"KO\" , \"error\" : \"Non è stato possibile collegarsi al db per l'ordine $idordine e rivenditore $intermediario \"}";

mysqli_select_db($db, $database) or die($erroreDB);

// qui prima controllo che non ci sia già la bolla di consegna per il rivenditore
$sql = "SELECT COUNT(*) AS conta FROM cp_intermediario i, cp_bollaconsegna b WHERE i.id = " . $intermediario . " AND b.dataconsegna = DATE('" . $dataconsegna . "') AND b.cliente = i.codcliente ";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
$conta = 0;
if ($row = mysqli_fetch_array($result)) {
    $conta = $row["conta"];
}
mysqli_free_result($result);

if ($conta > 0){
    header("Content-type: application/json");
    echo "{\"status\" : \"KO\" , \"error\" : \"Esiste già una bolla di consegna per questo rivenditore $intermediario in data $dataconsegna \"}";
    exit;
}

$sql = "SELECT codcliente FROM cp_intermediario i WHERE i.id = " . $intermediario;

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
$codcliente = 0;
if ($row = mysqli_fetch_array($result)) {
    $codcliente = $row["codcliente"];
}
mysqli_free_result($result);

// devo creare un record bollaconsegna verso il cliente Rivenditore

// query per il totale della bolla del rivenditore

$qrystr = "SELECT ";
$qrystr .= "((r.perc_b % 123456) / 1000) as perc_b, ((r.perc_n % 123456) / 1000) as perc_n, ";
/* -- 2024-08-17 modificata query per gestire anche i prodotti venduti a peso KG
// $qrystr .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM( d.quantita) ) AS quantita_in_bolla, ";
// $qrystr .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM(d.quantita)) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS totale_in_bolla, ";
// $qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) AS qta_n, ";
// $qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS tot_n, ";
// $qrystr .= "SUM( d.quantita ) AS b, ";
// $qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM( d.quantita) ) AS n, ";
// $qrystr .= "SUM( d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS t ";
*/

// 2024-08-17 - includo anche i KG nei conteggi
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita),(((r.perc_b % 123456) / 1000) / 100) * d.quantita) ) AS quantita_in_bolla, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario)) ) AS totale_in_bolla, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita),(((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS qta_n, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),ROUND((((r.perc_n % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2)) ) AS tot_n, \n";
$qrystr .= "SUM( d.quantita ) AS b, \n";
$qrystr .= "SUM( IF (dl.unitamisura = 'PZ', FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita ), (((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS n, \n";
$qrystr .= "SUM( ROUND(d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2) ) AS t \n";
//
$qrystr .= "FROM cp_ordinecliente o, ";
$qrystr .= "cp_cliente c, ";
$qrystr .= "cp_dettaglioordine d, ";
$qrystr .= "cp_rapportoconsegna r, ";
$qrystr .= "cp_listinoprezzi l, ";
$qrystr .= "cp_dettagliolistino dl, ";
$qrystr .= "cp_dettagliolistinogruppi dg, ";
$qrystr .= "cp_gruppoprodotti g, ";
$qrystr .= "cp_prodotto p ";
$qrystr .= "WHERE o.dataordine = DATE('" . $dataconsegna . "') AND ";
$qrystr .= "o.id = d.ordinecliente AND ";
$qrystr .= "c.intermediario = " . $intermediario . " AND ";
$qrystr .= "o.cliente = c.id AND ";
$qrystr .= "r.cliente = c.id AND ";
$qrystr .= "c.listino = l.id  AND ";
$qrystr .= "dl.listino = c.listino AND ";
$qrystr .= "dl.prodotto = d.prodotto AND ";
$qrystr .= "dg.listino = c.listino AND ";
$qrystr .= "dg.gruppo = d.gruppo AND ";
$qrystr .= "d.gruppo = g.id AND ";
$qrystr .= "p.id = d.prodotto AND ";
$qrystr .= "d.stato = 0 ";

$result1 = mysqli_query($db, $qrystr);
if (!$result1) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
$perc_b = null;
$perc_n = null;
$qta_bolla = null;
$qta_n = null;
$tot_n = null;
$totalebolla = 0;
$b = null;
$n = null;
$t = null;
if ($row = mysqli_fetch_array($result1)) {
    $perc_b = $row["perc_b"];
    $perc_n = $row["perc_n"];
    $qta_bolla = $row["quantita_in_bolla"];
    $totalebolla = $row["totale_in_bolla"];
    $qta_n = $row["qta_n"];
    $tot_n = $row["tot_n"];
    $b = $row["b"];
    $n = $row["n"];
    $t = $row["t"];
}
mysqli_free_result($result1);

// ho gli elementi base per generare la bolla di consegna per il rivenditore

$sql = "INSERT INTO cp_bollaconsegna (dataconsegna, numbolla,totalebolla,cliente,ordine,rapporto,fatturato) \n";
$sql .= "VALUES (DATE('$dataconsegna'),$numbolla,$totalebolla,$codcliente,NULL,$perc_b,0) ";

// eseguo la query e mi faccio dare id del record in bollaconsegna
$result2 = mysqli_query($db, $sql);
if (!$result2) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
$idbolla = mysqli_insert_id($db);

// 27/07/2024 - La suddivisione in B e N si fa sul totale quantità per ogni gruppo prodotto/prezzo unitario

$qrystr = "SELECT d.gruppo AS gruppo, g.NomeGruppo as nomegruppo, IFNULL(dl.prezzounitario, dg.prezzounitario) as prezzo, ";
/* // 2024-08-17 includiamo anche i prodotti venduti a KG
$qrystr .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM( d.quantita) ) AS quantita_in_bolla, ";
$qrystr .= "CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM(d.quantita)) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS totale_in_bolla, ";
$qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) AS qta_n, ";
$qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS tot_n, ";
$qrystr .= "SUM( d.quantita ) AS b, ";
$qrystr .= "FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM( d.quantita) ) AS n, ";
$qrystr .= "SUM( d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario) AS t ";
*/
// 2024-08-17 - includo anche i prodotti venduti a KG
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita),(((r.perc_b % 123456) / 1000) / 100) * d.quantita) ) AS quantita_in_bolla, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario)) ) AS totale_in_bolla, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita),(((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS qta_n, \n";
$qrystr .= "SUM( IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),ROUND((((r.perc_n % 123456) / 1000) / 100) * d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2)) ) AS tot_n, \n";
$qrystr .= "SUM( d.quantita ) AS b, \n";
$qrystr .= "SUM( IF (dl.unitamisura = 'PZ', FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita), (((r.perc_n % 123456) / 1000) / 100) * d.quantita) ) AS n, \n";
$qrystr .= "SUM( ROUND(d.quantita * IFNULL(dl.prezzounitario,dg.prezzounitario),2) ) AS t \n";
//
$qrystr .= "FROM cp_ordinecliente o, ";
$qrystr .= "cp_cliente c, ";
$qrystr .= "cp_dettaglioordine d, ";
$qrystr .= "cp_rapportoconsegna r, ";
$qrystr .= "cp_listinoprezzi l, ";
$qrystr .= "cp_dettagliolistino dl, ";
$qrystr .= "cp_dettagliolistinogruppi dg, ";
$qrystr .= "cp_gruppoprodotti g, ";
$qrystr .= "cp_prodotto p ";
$qrystr .= "WHERE o.dataordine = DATE('" . $dataconsegna . "') AND ";
$qrystr .= "o.id = d.ordinecliente AND ";
$qrystr .= "c.intermediario = " . $intermediario . " AND ";
$qrystr .= "o.cliente = c.id AND ";
$qrystr .= "r.cliente = c.id AND ";
$qrystr .= "c.listino = l.id  AND ";
$qrystr .= "dl.listino = c.listino AND ";
$qrystr .= "dl.prodotto = d.prodotto AND ";
$qrystr .= "dg.listino = c.listino AND ";
$qrystr .= "dg.gruppo = d.gruppo AND ";
$qrystr .= "d.gruppo = g.id AND ";
$qrystr .= "p.id = d.prodotto AND ";
$qrystr .= "d.stato = 0 ";
$qrystr .= "GROUP BY d.gruppo, prezzo ";
$qrystr .= "ORDER BY d.gruppo, prezzo ";

$result4 = mysqli_query($db, $qrystr);
if (!$result4) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// $row = mysqli_fetch_array($result,MYSQL_ASSOC);
$riga = 0;
while ($row = mysqli_fetch_array($result4)) {
    $riga++;
    $gruppo = $row["gruppo"];
    $nomegruppo = $row["nomegruppo"];
    $prezzo = $row["prezzo"];
    $qtabolla = $row["quantita_in_bolla"];
    $totalebolla = $row["totale_in_bolla"];
    $qta_n = $row["qta_n"];
    $tot_n = $row["tot_n"];
    $b = $row["b"] * 1000 + rand(3, 1000) * 123456; // tre cifre decimali
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
    $result5 = mysqli_query($db, $qrystr);
    if (!$result5) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $iddettbolla = mysqli_insert_id($db);
}
;
mysqli_free_result($result4);

// ora devo registrare l'ordine in stato = 9 BOLLETTATO (significa che la bolla di consegna è stata generata) a tutti gli ordini coinvolti dalla query precedente
$qrystr = "UPDATE cp_ordinecliente o, cp_cliente c SET o.stato = 9 WHERE o.cliente = c.id AND c.intermediario = $intermediario AND o.dataordine = DATE('$dataconsegna') ";
$result6 = mysqli_query($db, $qrystr);
if (!$result6) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// ho completato le operazioni di inserimento bolla
echo "{\"status\" : \"OK\" , \"error\" : \"\"}";
?>