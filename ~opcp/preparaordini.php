<?php
// PREPARA ORDINI - 2024.08.07
/* Si preparano con ticket tutti gli ordini della giornata indicata : default la giornata odierna */
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
$errori = "";
$sql = "SELECT o.id AS id, c.NomeBreve AS nomecliente, o.ticket AS ticket, o.stato AS stato, c.intermediario AS intermediario, i.denominazione AS nomeintermediario, o.preparatore AS preparatore ";
$sql .= "FROM ";
$sql .= "cp_ordinecliente o, cp_cliente c  ";
$sql .= "LEFT OUTER JOIN cp_intermediario i ON (i.id = c.intermediario) ";
$sql .= "WHERE c.id = o.cliente AND o.stato = 7 AND o.dataordine = DATE('" . $giorno . "') "; // 7 = DA_PRODURRE, 8 = PREPARATO, 4 = CONSEGNATO
$sql .= "ORDER BY c.sequenza ";

// leggo ogni singolo record e gli assegno un ticket e lo metto nello stato 8 = PREPARATO
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
                if (!$result) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                    echo ("Error description: " . mysqli_error($db));
                    exit; // fine dello script php
                }
while ($row = mysqli_fetch_array($result)) {
    $idordine = $row["id"];
    $nomecliente = $row["nomecliente"];
    $ticket = $row["ticket"];
    $statoordine = $row["stato"];
    $intermediario = $row["intermediario"];
    $nomeintermediario = $row["nomeintermediario"];
    $preparatore = $row["preparatore"];

    $annocomp = date('Y',strtotime($giorno));
    $idresp = 1; // default responsabile Automatico ROBBIE

    $sqlMulti = "SET @statooper = -1;\n";
    $sqlMulti .= "SET @biglietto = -100;\n";
    $sqlMulti .= "SET @ilmessaggio = 'ee';\n";
    $sqlMulti .= "CALL AssegnaTicket($annocomp,$idresp,$idordine,@statooper,@biglietto,@ilmessaggio);\n";
    $sqlMulti .= "SELECT @statooper, @biglietto, @ilmessaggio;\n";

    //  eseguo qui la query multipla
    $statooper = null;
    $ticket = null;
    $msq = null;

    mysqli_multi_query($db, $sqlMulti);
    do {
        if ($result2 = mysqli_store_result($db)) {
            if ($row = mysqli_fetch_row($result2)) {
                $statooper = (int) $row[0];
                $ticket = (int) $row[1];
                $msg = $row[2];
            }
        }
    } while (mysqli_next_result($db));

    // se ci sono stati problemi lo statooper sarà a zero o negativo o null
    if ($statooper == null || $statooper <= 0) {
        // ci sono stati dei problemi segnalare la presenza di un errore nella risposta
        /* abbiamo avuto un problema che cosa facciamo?????
        header("Content-type: application/json");
        echo "{\"status\" : \"KO\" , \"statooper\" : \"" . $statooper . "\" , \"ticket\" : \"" . $ticket . "\" , \"msg\" : \"" . $msg . "\" , \"error\" : \"Non è stato possibile ricevere un ticket per l'ordine $idordine \"}";
        */
        $errori .= "\n Non è stato possibile ricevere un ticket per l'ordine $idordine ";
    } else {
        /* è andato tutto bene non c'è nulla da fare se non passare al prossimo ordine
        header("Content-type: application/json");
        echo "{\"status\" : \"OK\" , \"statooper\" : \"" . $statooper . "\" , \"ticket\" : \"" . $ticket . "\" , \"msg\" : \"" . $msg . "\" , \"error\" : \"\"}";
        */
        // qui faccio l'aggiornamento dello stato da DA_PRODURRE a PREPARATO e assegno il ticket all'ordine
        $sqlUpd = "UPDATE cp_ordinecliente SET ticket = $ticket, stato = 8, preparatore = 1 WHERE id = $idordine";
        $result1 = mysqli_query($db, $sqlUpd);
        if (!$result1) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
    }
}

// chiudi database

mysqli_close($db);
// conferma che è andato tutto bene
if ($errori != ""){
    header("Content-type: application/json");
    echo "{\"status\": \"ERROR\", \"errore\" : \"$errori\"}";
} else {
    header("Content-type: application/json");
    echo "{\"status\": \"OK\"}";
}
?>