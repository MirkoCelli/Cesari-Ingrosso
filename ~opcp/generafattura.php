<?php
  // (c) 2024-07-12 - Robert Gasperoni
  // script per la generazione della fattura per il cliente nel periodo di riferimento
  // con numero fattura e data fattura fornite dall'operatore
  // oppure in alternativa si potrebbe usare la tabella cp_numerazioni per ottenere la prossima fattura (ma occorre essere sicuri che possiamo essere noi a fornire il numero di fattura)

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

$numfatt = $_REQUEST["numfatt"];
$datafattura = $_REQUEST["datafatt"];
$idcliente = $_REQUEST["idcliente"];
$inizio = $_REQUEST["inizio"];
$fine = $_REQUEST["fine"];

// 2024-08-10 - devo indicare se è un rivenditore (in quanto al rivenditore vanno fatturate tutte le quantità dei suoi clienti)
// ma dato che la bolla di consegna viene intestata al cliente RIVENDITORE corrispondente è da trattare come gli altri clienti
$flgriv = $_REQUEST["flgriv"];

// le considero verificati dal client

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

header("Content-type: application/json");

$erroreDB = "{\"status\" : \"KO\" , \"error\" : \"Non è stato possibile collegarsi al db per la fattura per il cliente $idcliente \" , \"numfatt\" : \"" . $numfatt . "\", \"datafattura\" : \"" . $datafattura . "\", \"idfattura\" : \"\"}";

mysqli_select_db($db, $database) or die($erroreDB);

// ANNOTAZIONE: i prezzi che otteniamo dalle bolle di consegna sono i prezzi senza IVA (confermato in data 12/07/2024 da Nicolò Celli)
// e viene applicata la percentuale IVA del 10 %

if ($flgriv == 1){
    // riepilogo dei clienti del rivenditore
    $sql = "INSERT INTO cp_fattura (cliente, datafattura, numerofattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura, descrizione) \n";
    $sql .= "SELECT cl.id AS cliente, DATE('". $datafattura . "') AS datafattura, " . $numfatt . " AS numerofattura, \n";
    $sql .= "SUM(db.totale) AS totaleimponibile, cl.perc_iva AS prec_IVA, ROUND(SUM(db.totale) * (cl.perc_iva / 100),2) AS impostaIVA, \n";
    $sql .= "ROUND(SUM(db.totale) * (1+ cl.perc_IVA / 100),2) AS totalefattura, \n";
    $sql .= "CONCAT('Ordinativi per ',cl.Denominazione,' nel periodo dal ',DATE_FORMAT(DATE('2024-08-09'),'%d/%m/%Y'),' al ',DATE_FORMAT(DATE('2024-08-09'),'%d/%m/%Y')) AS descrizione \n ";
    $sql .= "FROM cp_intermediario i, cp_cliente cl, cp_dettagliobolla db, cp_ordinecliente c, cp_cliente cg, cp_bollaconsegna b \n";
    $sql .= "WHERE  i.codcliente = " . $idcliente . " AND \n";
    $sql .= "i.id = cg.intermediario AND \n";
    $sql .= "cl.id = i.codcliente AND \n";
    $sql .= "db.bolla = b.id AND  b.cliente = i.codcliente AND ";
    $sql .= "b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') AND \n";
    $sql .= "c.dataordine = b.dataconsegna AND \n";
    $sql .= "c.cliente = cg.id AND c.stato = 9 ";

    /*
    $sql .= "SELECT " . $idcliente . " AS cliente, DATE('" . $datafattura . "') AS datafattura, " . $numfatt . " AS numerofattura, \n";
    $sql .= "SUM(db.totale) AS totaleimponibile, c.perc_iva AS perc_IVA, ROUND(SUM(db.totale) * (c.perc_iva / 100),2) AS impostaIVA, ";
    $sql .= "ROUND(SUM(db.totale) * (1 + c.perc_IVA / 100),2) AS totalefattura,";
    $sql .= "CONCAT('Ordinativi per ',c.Denominazione,' nel periodo dal ',DATE_FORMAT(DATE('" . $inizio . "'),'%d/%m/%Y'),' al ',DATE_FORMAT(DATE('" . $fine . "'),'%d/%m/%Y')) AS descrizione \n";
    $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (bc.id = db.bolla) JOIN cp_cliente c ON (bc.cliente = c.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo) \n";
    $sql .= "WHERE c.id = " . $idcliente . " AND bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    */

    // eseguo la query e mi faccio dare id del record in bollaconsegna
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $idfattura = mysqli_insert_id($db);

    $sql = "INSERT INTO cp_dettagliofattura (fattura,gruppo,prodotto,quantita,prezzounitario,totale,descrizione,bolla,dettagliobolla)\n";

    $sql = "SELECT " . $idfattura ." AS fattura, \n";
    $sql .= "db.gruppo AS gruppo, NULL AS prodotto, \n";
    $sql .= "SUM(db.quantita) AS quantita, \n";
    $sql .= "db.prezzounitario AS prezzo, \n";
    $sql .= "SUM(db.totale) AS totale,\n";
    $sql .= "CONCAT(g.NomeGruppo,' Prz. ', db.prezzounitario) AS descrizione,\n";
    $sql .= "NULL AS bolla, NULL AS dettagliobolla \n";
    $sql .= "FROM cp_intermediario i, cp_cliente cl, cp_dettagliobolla db LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo),\n";
    $sql .= "cp_ordinecliente c, cp_cliente cg, cp_bollaconsegna b \n";
    $sql .= "WHERE i.codcliente = " . $idcliente . " AND \n";
    $sql .= "i.id = cg.intermediario AND cl.id = i.codcliente AND db.bolla = b.id AND b.cliente = i.codcliente AND \n";
    $sql .= "b.dataconsegna BETWEEN DATE('".$inizio."') AND DATE('".$fine."') AND \n";
    $sql .= "c.dataordine = b.dataconsegna AND c.cliente = cg.id AND c.stato = 9 \n";
    $sql .= "GROUP BY gruppo, prezzo \n";
    $sql .= "ORDER BY gruppo, prezzo ";

/*
    $sql .= "SELECT " . $idfattura . " AS fattura, db.gruppo AS gruppo, NULL AS prodotto, SUM(db.quantita) AS quantita, db.prezzounitario AS prezzounitario, SUM(db.totale) AS totale, ";
    $sql .= "CONCAT(g.NomeGruppo,' Prz. ', db.prezzounitario) AS descrizione, NULL AS bolla, NULL AS dettagliobolla ";
    $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (bc.id = db.bolla) JOIN cp_cliente c ON (bc.cliente = c.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo) ";
    $sql .= "WHERE c.id = " . $idcliente . " AND ";
    $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') \n";
    $sql .= "ORDER BY bc.dataconsegna, bc.numbolla, g.NomeGruppo, db.prezzounitario ";
*/

    $result1 = mysqli_query($db, $sql);
    if (!$result1) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }

    // mysqli_free_result($result1);

} else {

   // cliente proprio o dell'agente
   $sql = "INSERT INTO cp_fattura (cliente, datafattura, numerofattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura, descrizione) \n";
   $sql .= "SELECT " . $idcliente . " AS cliente, DATE('" . $datafattura . "') AS datafattura, " . $numfatt . " AS numerofattura, \n";
   $sql .= "SUM(db.totale) AS totaleimponibile, c.perc_iva AS perc_IVA, ROUND(SUM(db.totale) * (c.perc_iva / 100),2) AS impostaIVA, ";
   $sql .= "ROUND(SUM(db.totale) * (1 + c.perc_IVA / 100),2) AS totalefattura,";
   $sql .= "CONCAT('Ordinativi per ',c.Denominazione,' nel periodo dal ',DATE_FORMAT(DATE('" . $inizio . "'),'%d/%m/%Y'),' al ',DATE_FORMAT(DATE('" . $fine . "'),'%d/%m/%Y')) AS descrizione \n";
   $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (bc.id = db.bolla) JOIN cp_cliente c ON (bc.cliente = c.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo) \n";
   $sql .= "WHERE c.id = " . $idcliente . " AND bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";

   // eseguo la query e mi faccio dare id del record in bollaconsegna
   $result = mysqli_query($db, $sql);
   if (!$result) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
      echo ("Error description: " . mysqli_error($db));
      exit; // fine dello script php
   }
   $idfattura = mysqli_insert_id($db);

   $sql = "INSERT INTO cp_dettagliofattura (fattura,gruppo,prodotto,quantita,prezzounitario,totale,descrizione,bolla,dettagliobolla)\n";
   $sql .= "SELECT " . $idfattura . " AS fattura, db.gruppo AS gruppo, NULL AS prodotto, SUM(db.quantita) AS quantita, db.prezzounitario AS prezzounitario, SUM(db.totale) AS totale, ";
   $sql .= "CONCAT(g.NomeGruppo,' Prz. ', db.prezzounitario) AS descrizione, NULL AS bolla, NULL AS dettagliobolla ";
   $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (bc.id = db.bolla) JOIN cp_cliente c ON (bc.cliente = c.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo) ";
   $sql .= "WHERE c.id = " . $idcliente . " AND ";
   $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') \n";
   $sql .= "GROUP BY g.id, db.prezzounitario "; // 03/10/2024 devono essere raggruppati per indice e prezzo
   $sql .= "ORDER BY g.id, db.prezzounitario ";

   $result1 = mysqli_query($db, $sql);
   if (!$result1) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
      echo ("Error description: " . mysqli_error($db));
      exit; // fine dello script php
   }

   // mysqli_free_result($result1);
} // singolo cliente

// 2024-08-11 - Differenziare in base al tipo di cliente la query di aggiornamento dello stato da BOLLETTATO = 9 a FATTURATO = 5
// ora devo registrare l'ordine in stato = 5 FATTURATO (significa che la fattura per il periodo indicato è stata generata)
if ($flgriv == 1){
  // è il rivenditore: devo determinare il suo codice intermediario e trovare tutti i suoi clienti che hanno ordini nel periodo e che sono in stato BOLLETTATO
  $qrystr = "UPDATE cp_intermediario i, cp_cliente cl, cp_ordinecliente c, cp_bollaconsegna b SET c.stato = 5 ";
  $qrystr .= "WHERE c.id = b.ordine AND \n";
  $qrystr .= "i.codcliente = " . $idcliente . " AND ";
  $qrystr .= "i.id = cl.intermediario AND ";
  $qrystr .= "b.cliente = cl.id AND ";
  $qrystr .= "b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') AND ";
  $qrystr .= "c.stato = 9 \n";
} else {
  // è un cliente proprio o di un agente, ho il collegamento della bolla consegna con il relativo ordine  da cambiare di stato da BOLLETTATO = 9 a FATTURATO = 5
  $qrystr = "UPDATE cp_ordinecliente c, cp_bollaconsegna b SET c.stato = 5 WHERE c.id = b.ordine AND \n";
  $qrystr .= "b.cliente = " . $idcliente . " AND b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') AND c.stato = 9 \n";
}

$result2 = mysqli_query($db, $qrystr);
if (!$result2) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// ho completato le operazioni di inserimento fattura con dettagli

// {"status":"OK|KO", "error" : "testo errore" , "numfatt" : "xx", "datafattura": "xxxx-xx-xx", "idfattura" : "xxx"}
echo "{\"status\" : \"OK\" , \"error\" : \"\" , \"numfatt\" : \"" . $numfatt . "\", \"datafattura\" : \"" . $datafattura . "\", \"idfattura\" : \"" . $idfattura . "\"}";
?>