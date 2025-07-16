<?php
// (c) 2024-08-15 - Robert Gasperoni
// script per la generazione delle fatture per tutti i cliente nel periodo di riferimento mensile
// numerazione automatica delle fatture per l'anno di competenza della fattura

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

// mi devo fare dare la data della fattura e le date del periodo da fatturare (solitamente è il mese ma si potrebbe fornire anche settimanale o altri periodi)
$datafattura = $_REQUEST["datafattura"];
$ggoggi = DateTime::createFromFormat("Y-m-d", $datafattura);
$anno = $ggoggi->format("Y");

$inizio = $_REQUEST["inizio"];
$fine = $_REQUEST["fine"];

/*
$idcliente = $_REQUEST["idcliente"];
$numfatt = $_REQUEST["numfatt"];

$flgriv = $_REQUEST["flgriv"];

*/
// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

header("Content-type: application/json; charset=UTF-8");
$erroreDB = "{\"status\" : \"KO\" , \"error\" : \"Non è stato possibile collegarsi al db \"}";

mysqli_select_db($db, $database) or die($erroreDB);

// devo determinare il numero di bolla da cui si inizia l'asssegnazione delle bollette di consegna
$numfatt = null;

$sql = "SELECT IFNULL(MAX(numerofattura)+1,1) AS numero FROM cp_fattura WHERE YEAR(datafattura) = " . $anno;

$result = mysqli_query($db, $sql);
while ($row = mysqli_fetch_array($result)) {
    $numfatt = $row["numero"];
}
mysqli_free_result($result);

if ($numfatt == null) {
    $numfatt = 1;
}

// elenco tutti i clienti che hanno una bolletta presente nel periodo $inizio - $fine


$qrycln = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, \n";
$qrycln .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino,\n";
$qrycln .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario , i.tipoIntermediazione as tipointermediario, t.tipo as nometipoint,\n";
$qrycln .= "IFNULL(q.tipoIntermediazione,NULL) AS clientespeciale \n";
$qrycln .= "FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) \n";
$qrycln .= "LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) \n";
$qrycln .= "LEFT OUTER JOIN cp_tipointermediario t ON (t.id = i.tipoIntermediazione) \n";
$qrycln .= "LEFT OUTER JOIN cp_intermediario q ON (q.codcliente = b.id) \n";
$qrycln .= "WHERE (SELECT COUNT(*) FROM cp_bollaconsegna bc WHERE bc.cliente = b.id AND bc.dataconsegna BETWEEN DATE('$inizio') AND DATE('$fine')) > 0";



$resultcln = mysqli_query($db, $qrycln);
while ($rowcln = mysqli_fetch_array($resultcln)) {

    // devo individuare l'ordine corrispondente a questo cliente

    $idcliente = $rowcln["id"];
    $flgriv = ($rowcln["clientespeciale"] == 1); // mi deve indicare se è un Rivenditore e quando clientespeciale è 1 è un Rivenditore
    // se non deve essere emessa bolletta per questo cliente allora va al prossimo cliente

    if ($flgriv == 1) {
        // riepilogo dei clienti del rivenditore
        $sql = "INSERT INTO cp_fattura (cliente, datafattura, numerofattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura, descrizione) \n";
        $sql .= "SELECT cl.id AS cliente, DATE('" . $datafattura . "') AS datafattura, " . $numfatt . " AS numerofattura, \n";
        $sql .= "SUM(db.totale) AS totaleimponibile, cl.perc_iva AS prec_IVA, ROUND(SUM(db.totale) * (cl.perc_iva / 100),2) AS impostaIVA, \n";
        $sql .= "ROUND(SUM(db.totale) * (1+ cl.perc_IVA / 100),2) AS totalefattura, \n";
        $sql .= "CONCAT('Ordinativi per ',cl.Denominazione,' nel periodo dal ',DATE_FORMAT(DATE('2024-08-09'),'%d/%m/%Y'),' al ',DATE_FORMAT(DATE('2024-08-09'),'%d/%m/%Y')) AS descrizione \n ";
        $sql .= "FROM cp_intermediario i, cp_cliente cl, cp_dettagliobolla db, cp_bollaconsegna b \n";
        $sql .= "WHERE  i.codcliente = " . $idcliente . " AND \n";
        $sql .= "cl.id = i.codcliente AND \n";
        $sql .= "db.bolla = b.id AND  b.cliente = i.codcliente AND ";
        $sql .= "b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "')\n";

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
        $sql .= "SELECT " . $idfattura . " AS fattura, \n";
        $sql .= "db.gruppo AS gruppo, NULL AS prodotto, \n";
        $sql .= "SUM(db.quantita) AS quantita, \n";
        $sql .= "db.prezzounitario AS prezzo, \n";
        $sql .= "SUM(db.totale) AS totale,\n";
        $sql .= "CONCAT(g.NomeGruppo,' Prz. ', db.prezzounitario) AS descrizione,\n";
        $sql .= "NULL AS bolla, NULL AS dettagliobolla \n";
        $sql .= "FROM cp_intermediario i, cp_cliente cl, cp_dettagliobolla db LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = db.gruppo),\n";
        $sql .= "cp_bollaconsegna b \n";
        $sql .= "WHERE i.codcliente = " . $idcliente . " AND \n";
        $sql .= "cl.id = i.codcliente AND db.bolla = b.id AND b.cliente = i.codcliente AND \n";
        $sql .= "b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') \n";
        $sql .= "GROUP BY gruppo, prezzo \n";
        $sql .= "ORDER BY g.NomeGruppo, prezzo ";

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
        // 03/10/2024 - dove fare anche il group by g.Id e non ordinare per nomegruppo ma per g.Id
        $sql .= "GROUP BY g.Id, db.prezzounitario ";
        $sql .= "ORDER BY g.Id, db.prezzounitario ";
        // $sql .= "ORDER BY g.NomeGruppo, db.prezzounitario ";

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
    if ($flgriv == 1) {
        // è il rivenditore: devo determinare il suo codice intermediario e trovare tutti i suoi clienti che hanno ordini nel periodo e che sono in stato BOLLETTATO
        $qrystr = "UPDATE cp_intermediario i, cp_cliente cl, cp_ordinecliente c, cp_bollaconsegna b SET c.stato = 5 ";
        $qrystr .= "WHERE \n";
        $qrystr .= "i.codcliente = " . $idcliente . " AND ";
        $qrystr .= "i.id = cl.intermediario AND ";
        $qrystr .= "b.cliente = " . $idcliente . " AND ";
        $qrystr .= "b.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') AND ";
        $qrystr .= "c.stato = 9 AND \n";
        $qrystr .= "c.cliente = cl.id AND \n";
        $qrystr .= "c.dataordine BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
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

    // prossimo numero di fattura
    $numfatt++;
}

mysqli_free_result($resultcln);

// ho completato le operazioni di inserimento fattura con dettagli

// {"status":"OK|KO", "error" : "testo errore" , "numfatt" : "xx", "datafattura": "xxxx-xx-xx", "idfattura" : "xxx"}

echo "{\"status\" : \"OK\" , \"error\" : \"\"}";
?>