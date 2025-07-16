<?php
  // 2024-08-17 - Robert Gassperoni
  // dati idcliente e data ordine elencare per ogni ordine associato al cliente per la data ordine
  // il raggruppamento dei gruppi prodotti con le corrispondenti quantità, attenzione solo righe con stato = 0 vanno coneggiati


// ora ci servono gli elementi per generare la query
include("dbconfig.php");

// PRODUZIONE PRODOTTI - 2024-06-27
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
}
// fine verifica cookies - 06/06/2024

$idcliente = null;
$dataordine = null;
$azione = null;

if (isset($_REQUEST["idcliente"])) {
    $idcliente = $_REQUEST["idcliente"];
}
if (isset($_REQUEST["dataordine"])) {
    $dataordine = $_REQUEST["dataordine"];
}
if (isset($_REQUEST["azione"])){
    $azione = $_REQUEST["azione"];
}

if (!isset($idcliente) || !isset($dataordine)) {
    // mancano dei dati, indichiamo che sono mancanti
    header("Content-type: text/html");
    echo "<html><body>Informazioni insufficienti per la ricerca dei dati del cliente per i relativi ordini alla data ordine</body></html>";
    exit;
}

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$oggi = date("Y-m-d");
$giorno = $oggi;

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

if ($azione == "ordine"){
    // solo i quantitativi dell'ordinato dal clietne alla data
    /*
    $sql = "SELECT o.id AS idordine, o.dataordine AS dataordine, d.gruppo AS gruppo,  g.NomeGruppo AS nomegruppo, SUM(d.quantita) AS quantita,  d.unitamisura AS um \n";
    $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine d LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) \n";
    $sql .= "WHERE o.cliente = " . $idcliente . " AND \n";
    $sql .= "o.dataordine = DATE('" . $dataordine . "') AND \n";
    $sql .= "d.ordinecliente = o.id AND \n";
    $sql .= "d.stato = 0 \n";
    $sql .= "GROUP BY idordine, dataordine, nomegruppo \n";
    $sql .= "ORDER BY idordine, dataordine, nomegruppo";
    */

    $sql = "SELECT o.id as idordine, o.dataordine as dataordine, d.gruppo AS gruppo, g.NomeGruppo as nomegruppo, dl.unitamisura as unitamisura, IFNULL(dl.prezzounitario,dg.prezzounitario) as prezzo, ";
    // 2024-08-17 - includere i prodotti venduti a peso kg
    $sql .= "IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM( d.quantita) ),(((r.perc_b % 123456) / 1000) / 100) * SUM( d.quantita)) AS quantita_in_bolla, \n";
    $sql .= "IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * SUM(d.quantita)) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * SUM(d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario)) AS totale_in_bolla, \n";
    $sql .= "IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ),(((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita)) AS qta_n, \n";
    $sql .= "IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) ) * IFNULL(dl.prezzounitario,dg.prezzounitario),ROUND((((r.perc_n % 123456) / 1000) / 100) * SUM(d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),2)) AS tot_n, \n";
    $sql .= "SUM( d.quantita ) AS b, \n";
    $sql .= "IF (dl.unitamisura = 'PZ', FLOOR((((r.perc_n % 123456) / 1000) / 100) * SUM( d.quantita) ), (((r.perc_n % 123456) / 1000) / 100) * SUM( d.quantita)) AS n, \n";
    $sql .= "ROUND(SUM( d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),2) AS t \n";
    //
    $sql .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) ";
    $sql .= "JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id AND d.stato = 0) ";
    $sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
    $sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) ";
    $sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
    $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
   //  $sql .= "WHERE o.id = " . $idordine . " ";

    $sql .= "WHERE o.cliente = " . $idcliente . " AND \n";
    $sql .= "o.dataordine = DATE('" . $dataordine . "') AND \n";
    $sql .= "d.ordinecliente = o.id AND \n";
    $sql .= "d.stato = 0 \n";
    $sql .= "GROUP BY d.gruppo, prezzo ";
    $sql .= "ORDER BY d.gruppo, prezzo ";


    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $tabella = "<table border=\"0\">\n<tr><td colspan=\"1\">Riepilogo del giorno $dataordine dei gruppi prodotto del cliente</td></tr>\n";
    $tabella .= "<tr><td>\n";

    $oldordine = null;
    while ($row = mysqli_fetch_array($result)) {
        $idordine = $row["idordine"];
        $dtord = $row["dataordine"];
        $gruppo = $row["gruppo"];
        $nomegruppo = $row["nomegruppo"];
        $unitamisura = $row["unitamisura"];
        $prezzo = $row["prezzo"];
        $qtabolla = $row["quantita_in_bolla"];
        $totbolla = $row["totale_in_bolla"];
        $qtan = $row["qta_n"];
        $totn = $row["tot_n"];
        $b = $row["b"];
        $n = $row["n"];
        $t = $row["t"];
        //
        if ($oldordine !== $idordine) {
            // chiusura della sottotabella precedente e apertura della nuova
            if ($oldgruppo !== null) {
                // chiude la sottotabella precedente
                $tabella .= "</table></td></tr>\n";
                $tabella .= "<tr><td>\n";
            }
            // apre una nuova tabella
            $oldordine = $idordine;
            $tabella .= "<table border=\"1\">\n";
            $tabella .= "<tr><td colspan=\"7\">Ordine $idordine </td></tr>\n";
            $tabella .= "<tr><td>Nome gruppo</td><td>Prezzo</td><td>U.M.</td><td>Quantit&agrave; B.</td><td>Tot. B.</td><td>Q.ta N</td><td>Tot. N</td></tr>\n";
        }
        // trascrive la riga della tabella
        $riga = "<tr><td> $nomegruppo </td>";
        $riga .= "<td>&euro;" . number_format((float) $prezzo, 2, '.', '') . "</td>";
        $riga .= "<td> $unitamisura</td>";
        $riga .= "<td> $qtabolla</td>";
        $riga .= "<td>&euro;" . number_format((float) $totbolla, 2, '.', '') . "</td>";
        $riga .= "<td> $qtan</td>";
        $riga .= "<td>&euro;" . number_format((float) $totn, 2, '.', '') . "</td>";
        $riga .= "<tr>\n";
        $tabella .= $riga;
    }
    // chiusura dell'ultima tabella
    if ($oldordine !== null) {
        // chiude la tabella
        $tabella .= "</table>\n";
    }
    $tabella .= "</td></tr></table>\n";

    mysqli_free_result($result);
}

if ($azione == "bolletta"){
    // riepilogo per gruppi prodotto del bollettato in data ordine dal cliente
    // per i clienti singoli c'è sempre un ordine associato e quindi possiamo ricavare
    /*
    $sql = "SELECT o.id AS idbolla, o.dataconsegna AS dataconsegna, d.gruppo AS gruppo,  g.NomeGruppo AS nomegruppo, SUM(d.quantita) AS quantita, \n";
    $sql .= "IFNULL(dl.prezzounitario,dg.prezzounitario) as prezzounitario, d.totale as totale, (d.b % 123456)/1000 as b, (d.n % 123456)/1000 as n, (d.t % 123456) /100 as t \n";
    $sql .= ", IFNULL(dl.prezzounitario,dg.prezzounitario) * ((d.n % 123456)/1000) as tot_n ";
    $sql .= "FROM cp_bollaconsegna o, cp_dettagliobolla d LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo), \n";
    $sql .= "cp_cliente c LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = c.listino) LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id) LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id) \n";
    $sql .= "WHERE o.cliente = " . $idcliente . " AND \n";
    $sql .= "c.id = o.cliente AND \n";
    $sql .= "o.dataconsegna = DATE('" . $dataordine . "') AND \n";
    $sql .= "d.bolla = o.id \n";
    $sql .= "GROUP BY idbolla, dataconsegna, nomegruppo \n";
    $sql .= "ORDER BY idbolla, dataconsegna, nomegruppo";
    */
    // 2024-08-19 - correzione alla query
    $sql = "SELECT o.id AS idbolla, o.dataconsegna AS dataconsegna, d.gruppo AS gruppo,  g.NomeGruppo AS nomegruppo, SUM(d.quantita) AS quantita,\n";
    $sql .= "IFNULL(dl.prezzounitario,dg.prezzounitario) as prezzounitario, d.totale as totale, (d.b % 123456)/1000 as b, (d.n % 123456)/1000 as n, \n";
    $sql .= "(d.t % 123456) /100 as t, IFNULL(dl.prezzounitario,dg.prezzounitario) * ((d.n % 123456)/1000) as tot_n \n";
    $sql .= "FROM cp_bollaconsegna o JOIN cp_dettagliobolla d ON (d.bolla = o.id) JOIN cp_cliente c ON (o.cliente = c.id) \n";
    $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = c.listino) \n";
    $sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) \n";
    $sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) \n";
    $sql .= "WHERE o.cliente = " . $idcliente . " AND \n";
    $sql .= "o.dataconsegna = DATE('" . $dataordine . "') \n";
    // 03/10/2024 - Richiesta di Berti Giacomo Cesari Pasticceria
    $sql .= "GROUP BY idbolla, dataconsegna, gruppo \n";
    $sql .= "ORDER BY idbolla, dataconsegna, gruppo ";   
    //$sql .= "GROUP BY idbolla, dataconsegna, nomegruppo \n";
    // $sql .= "ORDER BY idbolla, dataconsegna, nomegruppo ";


    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $dtelem = explode("-", $dataordine);
    $dataordine1 = $dtelem[2] . "/" . $dtelem[1] . "/" . $dtelem[0];
    $tabella = "<table border=\"0\">\n<tr><td colspan=\"1\">Riepilogo da bolletta del giorno " . $dataordine1 . " dei gruppi prodotto del cliente</td></tr>\n";
    $tabella .= "<tr><td>\n";

    $oldbolla = null;
    while ($row = mysqli_fetch_array($result)) {
        $idbolla = $row["idbolla"];
        $dtbolla = $row["dataconsegna"];
        $gruppo = $row["gruppo"];
        $nomegruppo = $row["nomegruppo"];
        $quantita = $row["quantita"];
        $totale = $row["totale"];
        $b = $row["b"];
        $n = $row["n"];
        $t = $row["t"];
        $tot_n = $row["tot_n"];
        $prezzo = $row["prezzounitario"];
        //
        if ($oldbolla !== $idbolla) {
            // chiusura della sottotabella precedente e apertura della nuova
            if ($oldbolla !== null) {
                // chiude la sottotabella precedente
                $tabella .= "</table></td></tr>\n";
                $tabella .= "<tr><td>\n";
            }
            // apre una nuova tabella
            $oldbolla = $idbolla;
            $tabella .= "<table border=\"1\">\n";
            $tabella .= "<tr><td colspan=\"6\">Bolletta Consegna $idbolla </td></tr>\n";
            $tabella .= "<tr><td>Nome gruppo</td><td>Prezzo</td><td>Quantit&agrave; Bolla</td><td>Totale Bolla</td><td>Q.t&agrave N</td><td>Totale N.</td></tr>\n";
        }
        // trascrive la riga della tabella
        $riga = "<tr><td>$nomegruppo</td>";
        $riga .= "<td align=\"right\">&euro;" . number_format((float) $prezzo, 2, '.', '') . "</td>";
        $riga .= "<td align=\"right\">" . str_replace(".000","",$quantita) . "</td>";
        $riga .= "<td align=\"right\">&euro;" . number_format((float) $totale, 2, '.', '') . "</td>";
        $riga .= "<td align=\"right\">" . str_replace(".0000","",$n) . "</td>";
        $riga .= "<td align=\"right\">&euro;" . number_format((float) $tot_n, 2, '.', '') . "</td>";
        $tabella .= $riga;
    }
    // chiusura dell'ultima tabella
    if ($oldbolla !== null) {
        // chiude la tabella
        $tabella .= "</table>\n";
    }
    $tabella .= "</td></tr></table>\n";
    mysqli_free_result($result);
}

mysqli_close($db);
echo $tabella;
?>