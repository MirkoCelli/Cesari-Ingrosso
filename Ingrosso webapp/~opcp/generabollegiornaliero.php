<?php
// (c) 2024-08-15 - Robert Gasperoni - Genera tutte le Bolle per il giorno indicato di tutta la Clientela

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

$giorno = $_REQUEST["giorno"];

$ggoggi = DateTime::createFromFormat("Y-m-d", $giorno);
$anno = $ggoggi->format("Y");

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

// devo determinare il numero di bolla da cui si inizia l'asssegnazione delle bollette di consegna
$numbolla = null;

$sql = "SELECT IFNULL(MAX(numbolla)+1,1) AS numero FROM cp_bollaconsegna WHERE YEAR(dataconsegna) = " . $anno;

$result = mysqli_query($db, $sql);
while ($row = mysqli_fetch_array($result)) {
    $numbolla = $row["numero"];
}
mysqli_free_result($result);

if ($numbolla == null){
   $numbolla = 1;
}

// *********************************************************************** //

// Sezione dedicata a Clienti Singoli della Pasticceria o Clienti di un Agente (vengono fatturati direttamente da CP) gli si fa la bollettina a loro

$qrycln = "SELECT o.id as id, c.id AS cliente, c.Denominazione AS nomecliente, o.dataordine AS dataordine, o.stato AS statoordine, so.descrizionestato AS nomestato, o.id AS ordine, \n";
$qrycln .= "i.id AS intermediario, i.Denominazione AS nomeintermediario, i.tipoIntermediazione AS tipointermediazione, \n";
$qrycln .= "((r.perc_b % 123456) / 1000) AS percentuale_b, ((r.perc_n % 123456) / 1000) AS percentuale_n, b.totalebolla as totlaebolla \n";
$qrycln .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) \n";
$qrycln .= "LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) \n";
$qrycln .= "LEFT OUTER JOIN cp_bollaconsegna b ON (b.ordine = o.id AND b.cliente = o.cliente) \n";
$qrycln .= "LEFT OUTER JOIN cp_intermediario i ON (c.intermediario = i.id) \n";
$qrycln .= "LEFT OUTER JOIN cp_statoordine so ON (so.id = o.stato) \n";
$qrycln .= "WHERE o.dataordine = DATE('" . $giorno . "') \n";
$qrycln .= "AND o.stato = 4 AND (i.tipoIntermediazione <> 1 OR i.tipoIntermediazione IS NULL) \n"; // escludo ordini già fatti o non consegnati (solo consegnati) e che non siano clienti di un rivenditore
$qrycln .= "GROUP BY o.id ";


// faccio una query per avere l'elenco di tutti i clienti che hanno un ordine nella data odierna $giorno e che abbiamo perc_B > 0 per potergli emettere una bolletta

$resultcln = mysqli_query($db, $qrycln);
while ($rowcln = mysqli_fetch_array($resultcln)) {

    // devo individuare l'ordine corrispondente a questo cliente

    $idordine = $rowcln["id"];
    $perc_b = $rowcln["percentuale_b"];
    $tot_bolla_b = $rowcln["totaleb"];
    $perc_b_ord = $perc_b / 100;
    $perc_n_ord = (100 - $perc_b) / 100;
    // se non deve essere emessa bolletta per questo cliente allora va al prossimo cliente

    if ($perc_b == 0)
    {
        // a questo ordine comunque devo cambiare di stato da 4 a 9 anche se non emetto bolletta
        $qrystr = "UPDATE cp_ordinecliente SET stato = 9 WHERE id = " . $idordine;
        $result2 = mysqli_query($db, $qrystr);
        if (!$result2) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        continue;
    }

    // 2024-08-16 - anche se il totale bolla B è zero va chiusa senza emettere bolla
    if ($tot_bolla_b == 0 && $tot_bolla_b !== null)
    {
        // a questo ordine comunque devo cambiare di stato da 4 a 9 anche se non emetto bolletta
        $qrystr = "UPDATE cp_ordinecliente SET stato = 9 WHERE id = " . $idordine;
        $result2 = mysqli_query($db, $qrystr);
        if (!$result2) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        continue;
    }

    // inserisco una bolletta per questo ordine e questo cliente

    $sql = "INSERT INTO cp_bollaconsegna (dataconsegna, numbolla,totalebolla,cliente,ordine,rapporto,fatturato) \n";
    $sql .= "SELECT o.dataordine AS dataconsegna, " . $numbolla . " AS numbolla, ";
    $sql .= "NULL AS totaleb, \n";
    $sql .= "c.id AS cliente, o.id AS ordine, ";
    $sql .= "(((r.perc_b % 123456) / 1000)) AS rapporto, ";
    $sql .= " 0 AS fatturato \n";
    $sql .= "FROM cp_ordinecliente o JOIN cp_cliente c ON (o.cliente = c.id) JOIN cp_rapportoconsegna r ON (r.cliente = c.id) ";
    $sql .= "WHERE o.id = " . $idordine;

    // eseguo la query e mi faccio dare id del record in bollaconsegna
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $idbolla = mysqli_insert_id($db);



    /* ***************************************************** */
    // si usano le regole impiegate in riepilogoordine.php per ottenere i totali delle quantità e dei prezzi per gruppi prodotti

    $quantitagruppi = [];
    $prezzogruppi = [];
    $unmisgruppi = [];
    $nomegruppi = [];
    $qtab = [];
    $qtan = [];
    $totb = [];
    $totn = [];

    $sql = "SELECT d.dettaglioordine, d.prodotto, d.gruppo, d.quantita, dg.prezzounitario, dg.unitamisura, g.NomeGruppo ";
    $sql .= "FROM cp_ordinecliente o ";
    $sql .= "LEFT OUTER JOIN cp_dettaglioordine d  ON (d.ordinecliente = o.id) ";
    $sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
    $sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
    $sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
    $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
    $sql .= "WHERE d.ordinecliente = " . $idordine . " AND d.stato = 0 ";
    // $sql .= "ORDER BY g.NomeGruppo ";
    $sql .= "ORDER BY g.id "; // 03/10/2024 - modificato su richiesta Giacomo Berti Ceari Pasticceria
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }

    while ($row = mysqli_fetch_array($result)) {
        $gruppo = $row["gruppo"];
        $nomegruppo = $row['NomeGruppo'];
        $prezzo = $row["prezzounitario"];
        $qtapezzo = $row["quantita"];
        $prod = $row["prodotto"];
        $unmis = $row["unitamisura"];
        $dettord = $row["dettagliordine"];
        // ora verifico se esiste il gruppo in $quantitagruppo e $unmisgruppi
        if (!isset($quantitagruppi[$gruppo])) {
            $quantitagruppi[$gruppo] = 0;
            $prezzogruppi[$gruppo] = $prezzo;
            $unmisgruppi[$gruppo] = $unmis;
            $qtab[$gruppo] = 0;
            $qtan[$gruppo] = 0;
            $totb[$gruppo] = 0.00;
            $totn[$gruppo] = 0.00;
            $nomegruppi[$gruppo] = $nomegruppo;
        }
        // aggiungo la quantità a quantitagruppi
        $quantitagruppi[$gruppo] += $qtapezzo;
    }

    // ora in $quantitagruppi dovrei avere le quantità totali per ogni gruppo
// ora devo determinare il loro qta_b e qta_n in base alle percentuali di rapporto consegna

    $qtabolla_1 = 0;
    $totalebolla_1 = 0.00;
    $qta_n_1 = 0;
    $tot_n_1 = 0.00;

    foreach ($quantitagruppi as $key => $value) {
        if ($unmisgruppi[$key] == "PZ"){
            $qtab[$key] += ceil($quantitagruppi[$key] * $perc_b_ord);
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += floor($quantitagruppi[$key] * $perc_n_ord);
            $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
        }
        if ($unmisgruppi[$key] == "KG"){
            $qtab[$key] += ($quantitagruppi[$key] * $perc_b_ord);
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += ($quantitagruppi[$key] * $perc_n_ord);
            $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
        }
        $gruppo = $key;
        // calcolo gli elementi di inserire come dettagliobolla

        $riga++;

        $nomegruppo = $nomegruppi[$key];
        $prezzo = $prezzogruppi[$key];

        $qtabolla = $qtab[$key];
        $totalebolla = $totb[$key];
        $qta_n = $qtan[$key];
        $tot_n = $totn[$key];
        $b = ($qtabolla+$qta_n) * 1000 + rand(3, 1000) * 123456; // tre cifre decimali è il totale dell'ordine reale
        $n = $qta_n * 1000 + rand(3, 1000) * 123456; // tre cifre decimali
        $t = ($totalebolla + $tot_n) * 100 + rand(3, 1000) * 123456; // due cifre decimali non includo l'IVA al 10% per il in bolla - 2024-09-04

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

        // sommo queste quantità agli accumulatori previsti
        $qtabolla_1 += $qtab[$key];
        $totalebolla_1 += $totb[$key];
        $qta_n_1 += $qtan[$key];
        $tot_n_1 += $totn[$key];
    }

    mysqli_free_result($result);

    // devo aggiornare il totale in bolla perchè non può restare NULL

    $sql = "UPDATE cp_bollaconsegna SET totalebolla = " . $totalebolla_1 . " WHERE id = " . $idbolla;
    $result1 = mysqli_query($db, $sql);
    if (!$result1) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
    }

    /* ***************************************************** */

    // ora devo registrare l'ordine in stato = 9 BOLLETTATO (significa che la bolla di consegna è stata generata)
    $qrystr = "UPDATE cp_ordinecliente SET stato = 9 WHERE id = " . $idordine;
    $result2 = mysqli_query($db, $qrystr);
    if (!$result2) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }

    $numbolla++; // numero della prossima bolla di consegna
}
mysqli_free_result($resultcln);

/* *********************  FINE BLOCCO CLIENTI DIRETTI O DI AGENTE ******************* */
/* NON FACCIAMO I RIVENDITORI PERCHE' CI CREA PROBLEMI CON IL RAPPORTO nella bollaconsegna */
/* inoltre è stato trabilito che il rivenditore non passa per questa procedura */

// ho completato le operazioni di inserimento bolla
echo "{\"status\" : \"OK\" , \"error\" : \"\"}";
?>