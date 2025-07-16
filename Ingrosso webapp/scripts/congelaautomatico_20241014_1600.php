<?php // (c) 2024-08-08 - Congela situazione ordini per i clienti per il giorno successivo alla data odierna (se è Domenica va al mertedì, se è Lunedì non fa congelamento)

include("dbconfig.php");

// NOTA BENE: con il comando MySQL "ALTER TABLE cp_dettaglioordine AUTO_INCREMENT = 1" si possono resettare gli autoincrementi delle tabelle mysql
$giorno = date("Y-m-d"); // la data odierna del sistema

if (isset($_REQUEST["giorno"])) {
    $giorno = $_REQUEST["giorno"]; // il formato deve essere ISO
}

$mioUTC = gmdate("Y-m-d H:i:s"); // mi ritorna la data UTC sempre
$now = new DateTime($mioUTC, new DateTimeZone('UTC'));
$now->setTimezone(new DateTimeZone("Europe/Rome")); // mi faccio dare l'orario del fuso orario di Roma (quello di Rimini/San Marino)
$adesso = $now->format('Y-m-d H:i:s'); // dovrei avere l'orario attuale a Rimini/San Marino
$adessosecondi = strtotime($adesso);
// $giornoora = $giorno . " 11:00:00";
$giornoora = $giorno . " " . $orariolimite; // valido dal 13/09/2024

$giornodopo1 = strtotime(date("Y-m-d", strtotime($giorno)) . " +1 days");

$giornodopo = date("Y-m-d", $giornodopo1);
$giornoseguente = date("d/m/Y", $giornodopo1);

$ggsett = date("w", strtotime($giorno)); // numero giorno settimana da domenica a sabato: 0=Domenica,1=Lunedì,...,6=Sabato
if ($ggsett == 0) { // se è martedì l'orario di fine caricamento ordini del cliente passa da lunedì alla domenica
    $giornodopo1 = strtotime(date("Y-m-d", strtotime($giorno)) . " +2 days");
    $giornodopo = date("Y-m-d", $giornodopo1);
    $giornoseguente = date("d/m/Y", $giornodopo1);
}
if ($ggsett == 1){
    // è Lunedì non ci sono congelamenti ordini da fare, esco con segnalazione errore?
    header("Content-type: application/json");
    echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giornodopo . "\" , \"error\" : \"Non è stato possibile aggiornare lo stato degli ordini del " . $giornoseguente . " in quanto oggi è un lunedì : " . $errore . "\"}";
    exit;
}

// determino il prossimo giorno per cui congelare gli ordini dei clienti
// connect to the database

$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");
// aperto il database

// passo 1) per tutti i clienti che hanno uno schemadefault aperto si possono generare gli ordini del giorno indicato e si mette lo stato aperto = 0
$sql = "SELECT c.id AS c1_id, s.id AS s1_id, o.id as o1_id FROM cp_cliente c JOIN cp_schemadefault s ON (c.id = s.cliente AND s.datainizio <= DATE('" . $giornodopo . "') AND  s.datafine IS NULL) LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = c.id AND o.schematico = s.id AND o.dataordine = DATE('" . $giornodopo . "')) ";
$result = mysqli_query($db, $sql);
$errore = mysqli_error($db);
if (!$result) {
    // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
    header("Content-type: application/json; charset=utf-8");
    echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
    exit;
}

// passo 2) ogni singolo cliente attivo si deve completare il suo ordine con tutti i records
while ($row = mysqli_fetch_array($result)) {

    $idcliente = $row["c1_id"]; // cliente
    $idschema = $row["s1_id"]; // suo schema
    $idordine = $row["o1_id"]; // suo eventuale ordine già presente alla data

    if (!isset($idordine) || ($idordine == null)) {
        // se per il giorno non ci sono record ordinecliente allora vanno inseriti tutti gli ordini data dalla query $sql1 altrimenti dato l'idordine si aggiungono solo i records che non sono presenti negli ordini dato in $sql2

        // qui inserisco il record in ordinecliente per questo cliente,schematico,dataordine,stato = 2,autorizzatosuperospesa = 0, codiceautorizzazione = 0
        $sqlins = "INSERT INTO cp_ordinecliente (cliente, schematico, dataordine, stato, autorizzatosuperamentospesa, codiceautorizzazione) VALUES (";
        $sqlins .= $idcliente . ",";
        $sqlins .= $idschema . ",";
        $sqlins .= "DATE('" . $giornodopo . "'),";
        $sqlins .= "2,"; // stato
        $sqlins .= "0, 0)"; // autorizzatosuperospesa, codiceautorizzazione
        // esegue la insert e si fa dare l'idordine corrispondente
        $resultins = mysqli_query($db, $sqlins);
        $idordine = mysqli_insert_id($db);
        $errore = mysqli_error($db);
        if ($resultins) {
            //
        } else {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            header("Content-type: application/json");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giornodopo . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
            exit;
        }
        //
        $sql1 = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato) \n";

        $sql1 .= "SELECT " . $idordine . " AS ordinecliente, b.s_sequenza AS dettaglioordine, b.s_prodotto AS prodotto, p.gruppo AS gruppo, ";
        $sql1 .= "b.s_quantita AS quantita, b.s_unitamisura AS unitamisura, 0 AS stato \n";
        $sql1 .= "FROM ";

        $sql1 .= "(SELECT t2.*, t1.* FROM (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, d.sequenza AS s_sequenza, ";
        $sql1 .= "d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
        $sql1 .= "ISODAYOFWEEK('" . $giornodopo . "') AS s_giornosettimana2 FROM cp_schemadefault s, cp_dettaglioschema d ";
        $sql1 .= "WHERE  s.cliente = " . $idcliente . " AND s.id = d.schematico AND s.datainizio <= DATE('" . $giornodopo . "') AND ";
        $sql1 .= "(s.datafine IS NULL OR DATE('" . $giornodopo . "') <= s.datafine) AND d.giornosettimana = ISODAYOFWEEK('" . $giornodopo . "') AND ";
        $sql1 .= "d.quantita > 0 ORDER BY d.sequenza) t1 ";
        $sql1 .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
        $sql1 .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
        $sql1 .= "ISODAYOFWEEK('" . $giornodopo . "') AS o_giornosettimana2 FROM cp_ordinecliente o, cp_dettaglioordine dt ";
        $sql1 .= "WHERE o.cliente = " . $idcliente . " AND o.id = dt.ordinecliente AND o.dataordine = DATE('" . $giornodopo . "') AND dt.quantita > 0 ";
        $sql1 .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) HAVING t2.o_dataordine IS NULL ) b, \n";

        $sql1 .= "cp_prodotto p, cp_listinoprezzi l,  cp_dettagliolistino dl,  cp_cliente c WHERE ";
        $sql1 .= "c.id = " . $idcliente . " AND ";
        $sql1 .= "c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND  l.id = dl.listino AND  dl.prodotto = p.id";

        // nella riga dettgalio ordine devo inserire i seguenti dati:
        // (ordinecliente, dettaglioordine = sequenza, prodotto, gruppo, quantita, unitamisura,stato = 0)
        $result1 = mysqli_query($db, $sql1);
        $errore = mysqli_error($db);
        if ($result1) {
            // $idordine = mysqli_insert_id($db);
            // inserimento dei dettaglio ordine completato
        } else {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            header("Content-type: application/json");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
            exit;
        }
    } else {
        // se l'ordine per il giorno e il cliente è già presente allora si inseriscono solo i dati che provengono da schemadefault e sezione ordinecliente è null

        $sql2 = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato) \n";

        $sql2 .= "SELECT " . $idordine . " AS ordinecliente, b.s_sequenza AS dettaglioordine, b.s_prodotto AS prodotto, p.gruppo AS gruppo, ";
        $sql2 .= "b.s_quantita AS quantita, b.s_unitamisura AS unitamisura, 0 AS stato \n";
        $sql2 .= "FROM ";

        $sql2 .= "(SELECT t2.*, t1.* FROM (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, d.sequenza AS s_sequenza, ";
        $sql2 .= "d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
        $sql2 .= "ISODAYOFWEEK('" . $giornodopo . "') AS s_giornosettimana2 FROM cp_schemadefault s, cp_dettaglioschema d ";
        $sql2 .= "WHERE  s.cliente = " . $idcliente . " AND s.id = d.schematico AND s.datainizio <= DATE('" . $giornodopo . "') AND ";
        $sql2 .= "(s.datafine IS NULL OR DATE('" . $giornodopo . "') <= s.datafine) AND d.giornosettimana = ISODAYOFWEEK('" . $giornodopo . "') AND ";
        $sql2 .= "d.quantita > 0 ORDER BY d.sequenza) t1 ";
        $sql2 .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
        $sql2 .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
        $sql2 .= "ISODAYOFWEEK('" . $giornodopo . "') AS o_giornosettimana2 FROM cp_ordinecliente o, cp_dettaglioordine dt ";
        $sql2 .= "WHERE o.cliente = " . $idcliente . " AND o.id = dt.ordinecliente AND o.dataordine = DATE('" . $giornodopo . "') AND dt.quantita > 0 ";
        $sql2 .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) HAVING t2.o_dataordine IS NULL ) b, \n";

        $sql2 .= "cp_prodotto p, cp_listinoprezzi l,  cp_dettagliolistino dl,  cp_cliente c WHERE ";
        $sql2 .= "c.id = " . $idcliente . " AND ";
        $sql2 .= "c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND  l.id = dl.listino AND  dl.prodotto = p.id";

        // nella riga dettgalio ordine devo inserire i seguenti dati:
        // (ordinecliente, dettaglioordine = sequenza, prodotto, gruppo, quantita, unitamisura,stato = 0)
        $result2 = mysqli_query($db, $sql2);
        $errore = mysqli_error($db);
        if ($result2) {
            // $idordine = mysqli_insert_id($db);
            // inserimento dei dettaglio ordine completato
        } else {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            header("Content-type: application/json");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
            exit;
        }
    }
}

mysqli_free_result($result);
// passo 3) solo ordini che sono in stato aperto = 2 nella giornata si possono chiudere con stato = 7 = da produrre

$sql3 = "UPDATE cp_ordinecliente SET stato = 7 WHERE stato = 2 AND dataordine = DATE('" . $giornodopo . "') ";
$result3 = mysqli_query($db, $sql3);
$errore = mysqli_error($db);
if ($result3) {
    // $idordine = mysqli_insert_id($db);
    // inserimento dei dettaglio ordine completato
    header("Content-type: application/json");
    echo "{\"status\" : \"OK\" , \"giorno\" : \" " . $giornodopo . "\" , \"error\" : \"\"}";
    exit;
} else {
    // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
    header("Content-type: application/json");
    echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giornodopo . "\" , \"error\" : \"Non è stato possibile aggiornare lo stato degli ordini del " . $giornodopo . " a ^Chiuso^: " . $errore . "\"}";
    exit;
}
?>