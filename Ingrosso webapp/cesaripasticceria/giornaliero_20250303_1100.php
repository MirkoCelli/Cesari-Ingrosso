<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi di una data giornata in ordine da parte del cliente

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/"; // . $chperc;
if ($elementi[0] != "") {
    $serverpath .= $elementi[0] . "/";
}

$identita = leggitoken(); // tre valori (token,user,scadenza)

$token = $identita[0];
$utente = $identita[1];
$scade = $identita[2];
$indice = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token, $indice, $adesso)) {
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}

$giorno = date("Y-m-d"); // oggi

if (isset($_GET["giorno"])) {
    $giorno = $_GET["giorno"];
}

if (isset($_POST["giorno"])){
    $giorno = $_POST["giorno"];
}

$giornosettimana = NomeGiornoSettimana($giorno);
$nomemese = NomeMese($giorno);
$gg1 = date("d", strtotime($giorno));
$aa1 = date("Y", strtotime($giorno));


// 29/05/2024 - si possono salvare solo i dati se si è prima delle ore 11.00 del giorno precedente alla data $giorno

// $adesso = date("Y-m-d H:i:s"); // data e ora fino ai secondi
$mioUTC = gmdate("Y-m-d H:i:s"); // mi ritorna la data UTC sempre
$now = new DateTime($mioUTC,new DateTimeZone('UTC'));
$now->setTimezone(new DateTimeZone("Europe/Rome")); // mi faccio dare l'orario del fuso orario di Roma (quello di Rimini/San Marino)
$adesso = $now->format('Y-m-d H:i:s'); // dovrei avere l'orario attuale a Rimini/San Marino
$adessosecondi = strtotime($adesso);
// $giornoora = $giorno . " 11:00:00";
$giornoora = $giorno . " " . $orariolimite; // valido dal 13/09/2024
$giornoprima = strtotime(date("Y-m-d H:i:s", strtotime($giornoora)) . " -1 days");
// 27/07/2024 - viene richiesto che per il martedì l'ordine sia inserito prima di domenica
$ggsett = date("w", strtotime($giorno)); // numero giorno settimana da domenica a sabato: 0=Domenica,1=Lunedì,...,6=Sabato
if ($ggsett == 2){ // se è martedì l'orario di fine caricamento ordini del cliente passa da lunedì alla domenica
    $giornoprima = strtotime(date("Y-m-d H:i:s", strtotime($giornoora)) . " -2 days");
}
$strlimiteorario = date("d/m/Y H:i",$giornoprima);
// quest'orario deve essere visualizzato all'utente
// fine 27/07/2024

$attivoSalva = ($adessosecondi <= $giornoprima);

function NomeMese($gg){
    $nm = date("m", strtotime($gg));
    $risp = "";
    switch($nm)
    {
        case 1:
            $risp = "Gennaio";
            break;
        case 2:
            $risp = "Febbraio";
            break;
        case 3:
            $risp = "Marzo";
            break;
        case 4:
            $risp = "Aprile";
            break;
        case 5:
            $risp = "Maggio";
            break;
        case 6:
            $risp = "Giugno";
            break;
        case 7:
            $risp = "Luglio";
            break;
        case 8:
            $risp = "Agosto";
            break;
        case 9:
            $risp = "Settembre";
            break;
        case 10:
            $risp = "Ottobre";
            break;
        case 11:
            $risp = "Novembre";
            break;
        case 12:
            $risp = "Dicembre";
            break;
    }
    return $risp;
}

function NomeGiornoSettimana($gg){
   // ricava il giorno della settimana in formato inglese 0=Sun,1=Mon,..,6=Sat
    $nd = date("w", strtotime($gg));
    $risp = "";
    switch($nd)
    {
        case 0:
           $risp = "Domenica";
           break;
        case 1:
            $risp = "Luned&igrave;";
            break;
        case 2:
            $risp = "Marted&igrave;";
            break;
        case 3:
            $risp = "Mercoled&igrave;";
            break;
        case 4:
            $risp = "Gioved&igrave;";
            break;
        case 5:
            $risp = "Venerd&igrave;";
            break;
        case 6:
            $risp = "Sabato";
            break;
    }
    return $risp;
}

function RiduciNumero($valore)
{
    return str_replace(".000", "", $valore);
}

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

// 2024-09-07 - devo limitare la navigazione a ritroso alle date $datacontratto e $datapmp

$prevMonth = date('Y-m-d', strtotime('-1 month', strtotime(substr($adesso, 0, 10))));
$datapmp = substr($prevMonth, 0, 8) . "01"; // primo del mese precedente a quello corrente
// la data contratto la otteniamo da schemadefault per il cliente - 2024-10-14 verifico se è abilitato
$sql = "SELECT s.datainizio, c.abilitato FROM cp_schemadefault s, cp_login l LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice AND l.tipo = 1) WHERE s.cliente = c.id AND s.datafine IS NULL ";
$result = mysqli_query($db, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $datacontratto = $row["datainizio"];
    $abilitato = $row["abilitato"]; // 2024-10-14
} else {
    // ci sono dei problemi con il contratto
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}
mysqli_free_result($result);

// se il giorno è antecedente a $datacontratto oppure a $datapmp allora va alla data odierna
if ($giorno < $datacontratto || $giorno < $datapmp){
    $giorno = date("Y-m-d"); // oggi
    $giornosettimana = NomeGiornoSettimana($giorno);
    $nomemese = NomeMese($giorno);
    $gg1 = date("d", strtotime($giorno));
    $aa1 = date("Y", strtotime($giorno));
}
// fine 2024-09-07

// devo contare il numero di elementi (è sempre pari al numero di prodotto)
/*
$sql = "SELECT COUNT(*) as conta ";
$sql .= "FROM ";
$sql .= "cp_login l ";
$sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice) ";
$sql .= "LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = l.codice) ";
$sql .= "LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id AND d.stato = 0) ";
$sql .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = p.gruppo) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = c.listino AND d.prodotto = dl.prodotto) ";
$sql .= "WHERE o.dataordine = DATE('" . $giorno . "') AND "; / * data del giorno da visualizzare * /
$sql .= "l.id = " . $indice; / * indice dell'account * /
*/

$sql = "SELECT COUNT(*) as conta ";
$sql .= "FROM ";
$sql .= "cp_prodotto p ";

// eseguo il comando di query
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

$numelem = 0;
if ($row = mysqli_fetch_array($result)){
    $numelem = $row["conta"];
}
mysqli_free_result($result);
$onumelem = $numelem; // è sempre fisso non si possono fare variazioni

// 2024-10-14 - leggo tutti gli elementi in cp_ferie e fra queste determino se le date sono nei giorni di ferie
$sqlferie = "SELECT * FROM cp_ferie ORDER BY DataInizio ";
$resultferie = mysqli_query($db, $sqlferie);
$ferie = mysqli_fetch_all($resultferie, MYSQLI_ASSOC);
mysqli_free_result($resultferie);

// ora definisco una funzione che mi dice se la data è in periodo di chiusra per ferie oppure no
function DataInChiusuraFerie($datagiorno)
{
    // devo stabilire se la data è nell'intervallo dei valori indicati in $ferie
    global $ferie;
    $stato = false;
    foreach ($ferie as $periodoferie) {
        if ($periodoferie["DataInizio"] <= $datagiorno && ($datagiorno <= $periodoferie["DataFine"] || $periodoferie["DataFine"] == null)) {
            $stato = true;
        }
    }
    return $stato;
}

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/giornaliero.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/giornaliero.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript">var percorso = "<?=$serverpath?>";</script>
</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <center>
            <table class="intesta">
                <tr>
                    <td align="center" class="intestac">
                        <form method="post" name="chgdate" id="chgdate" action="giornaliero.php">
                            <span id="giornaliero">
                                <input name="giorno" type="date" value="<?= $giorno ?>" required class="data" onchange="return CambiaData();" />
                                <input type="submit" name="invia" value="CAMBIA DATA" class="bottone" />
                            </span>
                        </form>
                    </td>
                    <td>
                        <font color="#ff0f0f">
                            Premere sempre il pulsante <b>SALVA</b>
                            dop le modifiche apportate.
                        </font>
                    </td>
                </tr>
            </table>
        </center>
    </div>
    <div name="contenuto" class="Contenuti">
        <!--
        <iframe allowtransparency="false" name="aggiungiDati" id="aggiungiDati" src="<?= $serverpath ?>aggiungidati.php?giorno=<?=$giorno?>" class="framenascosto"></iframe>
        -->
        <center>
            <span class="titolo">
                Giorno <?= $giornosettimana ?> <?=$gg1?> <?=$nomemese?> <?=$aa1?> -  <?= date('d/m/Y', strtotime($giorno)) ?><br />
                <font size="2">
                    L'ordine si pu&ograve; variare solo prima del <?= $strlimiteorario?>
                </font>
            </span>
        </center>
        <center>
            <form action="<?=$serverpath?>confermadati.php" method="POST" onsubmit="return CheckSubmitFunction(event)">
                <input id="onumelem" name="onumelem" type="hidden" value="<?=$numelem?>" />
                <input id="numelem" name="numelem" type="hidden" value="<?= $numelem ?>" />
                <input id="giorno" name="giorno" type="hidden" value="<?=$giorno?>" />
                <table>
                    <tr>
                        <?php
if ($attivoSalva && $abilitato && !DataInChiusuraFerie($giorno)) { // 2024-10-14 può salvare solo se è abilitato
                        ?>
                        <td colspan="2" class="salva">
                            <input id="salva" name="salva" type="submit" value="SALVA" onclick="return abilitaSubmit()" />
                        </td>
                        <td colspan="2" class="salva2">
                        </td>
                        <?php
} else {
                        ?>
                        <td colspan="2" class="salva">
                            <input id="salva" name="salva" type="submit" value="SALVA" disabled />
                        </td>
                        <td colspan="2" class="salva2">
                        </td>
                        <?php
}
                        ?>
                    </tr>
                    <tr>
                        <td colspan="30" aling="left"><b>Descrizione Prodotto</b></td>
                        <td aling="center"><b>Quantit&agrave;</b></td>
                        <td aling="center"><b>U.M.</b></td>
                    </tr>
                    <?php
     // qui faccio la query per i prodotti del giorno per il cliente (identificativo del cliente lo prendo alle cookies)

     $unitamisura = ""; // Pz o Kg
     $qtaprodotto = 0;
     $codiceprodotto = ""; // il codice prodotto
     $descrprodotto = "";
     $prezzounit = 0.00;
     $totale = round($qtaprodotto * $prezzounit,2,PHP_ROUND_HALF_UP); // arrotondamento per eccesso in valore assoluto 1.5 -> 2, -1.5 -> -2
     /*
     $unitamisura2 = "Pz"; // Pz o Kg
     $qtaprodotto2 = 1;
     $codiceprodotto2 = "AB0002"; // il codice prodotto
     $descrprodotto2 = "CROISSANT ALLA PERA";
     $prezzounit2 = 0.80;
     $totale2 = round($qtaprodotto2 * $prezzounit2, 2, PHP_ROUND_HALF_UP); // arrotondamento per eccesso in valore assoluto 1.5 -> 2, -1.5 -> -2
     */
     // query per ottenere i dati degli ordini per questo giorno
     /* //- 03/07/2024 - ora le righe degli ordini sono virtuali finchè non vengono alterate, si prendono dallo schema default del cliente
     $sql = "SELECT d.id as id, o.id as ordinecliente, o.dataordine, d.dettaglioordine, d.unitamisura as um, d.quantita as qta, p.codiceprodotto as codiceprodotto, ";
     $sql .= "p.descrizionebreve as prod, g.NomeGruppo as grp, dl.prezzounitario as prezzo, d.prodotto as prodotto, d.gruppo as gruppo FROM ";
     $sql .= "cp_login l ";
     $sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice) ";
     $sql .= "LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = l.codice) ";
     $sql .= "LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id AND d.stato = 0) ";
     // stato: 0 = Valido 1 = Annullato 2 = Sostituito (cliente ha variato le quantità) 3 = Modificato (il responsabile ha modificato le quantità)
     $sql .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
     $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = p.gruppo) ";
     $sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = c.listino AND d.prodotto = dl.prodotto) ";
     $sql .= "WHERE o.dataordine = DATE('". $giorno . "') AND "; / * data del giorno da visualizzare * /
     $sql .= "l.id = " . $indice; / * indice dell'account * /
     $sql .= " ORDER BY d.dettaglioordine";
     */

     // devo determinare il codice del cliente dal login
     $sql = "SELECT c.id as codice, c.NomeBreve as nomecliente ";
     $sql .= "FROM cp_login l ";
     $sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice) ";
     $sql .= "WHERE l.id = " . $indice;

     $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
     if (!$result) {
       header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
       echo ("Error description: " . mysqli_error($db));
       exit; // fine dello script php
     }

     while ($row = mysqli_fetch_array($result)) {
       $idcliente = $row["codice"];
       $nomecliente = $row["nomecliente"];
     }

     mysqli_free_result($result);
     // 2024-10-14 - Se non è abilitato oppure è un giorno di chiusura per ferie non mostra i dettagli dell'ordine
     if ($abilitato == 0 || DataInChiusuraFerie($giorno)) {
       $sql = "SELECT * FROM cp_ordinecliente WHERE false ";
       $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
       if (!$result) {
         header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
         echo ("Error description: " . mysqli_error($db));
         exit; // fine dello script php
       } 
     } else {
     // query che coinvolge la suddivisione in tre select distinte da unire assieme: solo da schema, da schema e ordine, solo da ordine
     $sql = "SELECT b.o_id  AS id, b.s_id AS riga, b.o_ordinecliente AS ordinecliente, SOLO_NOT_NULL(b.s_sequenza,b.o_dettaglioordine) AS sequenza, ";
     $sql .="SOLO_STR_NOT_NULL(b.s_unitamisura, b.o_unitamisura) AS unitamisura, SOLO_NOT_NULL(b.s_prodotto, b.o_prodotto) AS prodotto, SOLO_NOT_NULL(b.s_quantita,b.o_quantita) AS quantita, ";
     $sql .= "p.codiceprodotto AS codiceprodotto, p.gruppo AS gruppo, p.descrizionebreve AS nomeprodotto, p.descrizionecompleta AS nomeestesoprodotto, p.sequenza AS sequenzaprodotto, l.tipo AS tipolistino, ";
     // $sql .= "dl.prezzounitario as prezzo, ";
     $sql .= "SOLO_NOT_NULL(gl.prezzounitario*100,dl.prezzounitario*100)/100 AS prezzo, "; // 2024-07-28 il prezzo dipende da listiniprodotti e da listinogruppi (lo devo fare diventare intero e poi lo ridiviso per 100 per aver eil prezzo in euro
     $sql .= "c.CodiceCliente AS codcliente, c.Denominazione AS nomecliente, p.codiceprodotto, p.gruppo, p.descrizionebreve, p.sequenza, l.tipo, dl.prezzounitario, c.CodiceCliente, c.Denominazione \n";
     $sql .= "FROM \n";
     $sql .= "(SELECT t2.*, t1.* ";
     $sql .= "FROM ";
     $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettaglioordine, ";
     $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
     $sql .= " ISODAYOFWEEK('" . $giorno . "') AS o_giornosettimana2 ";
     $sql .= "FROM  cp_ordinecliente o,  cp_dettaglioordine dt ";
     $sql .= "WHERE o.cliente = " . $idcliente . " ";
     $sql .= "AND o.id = dt.ordinecliente ";
     $sql .= "AND o.dataordine = DATE('" . $giorno . "') ";
     $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
     $sql .= "ORDER BY dt.dettaglioordine) t2, ";
     $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
     $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
     $sql .= "ISODAYOFWEEK('" . $giorno . "') AS s_giornosettimana2 ";
     $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
     $sql .= "WHERE s.cliente = " . $idcliente . " ";
     $sql .= "AND s.id = d.schematico ";
     $sql .= "AND s.datainizio <= DATE('".$giorno."') ";
     $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno . "') <= s.datafine) ";
     $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno . "') ";
     $sql .= "AND d.quantita >= 0 ";
     $sql .= "ORDER BY d.sequenza) t1 ";
     $sql .= "WHERE t1.s_prodotto = t2.o_prodotto \n";

     $sql .= "UNION\n";

     $sql .= "SELECT t2.*, t1.* ";
     $sql .= "FROM ";
     $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
     $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
     $sql .= "ISODAYOFWEEK('" . $giorno . "') AS s_giornosettimana2 ";
     $sql .= "FROM ";
     $sql .= "cp_schemadefault s, cp_dettaglioschema d ";
     $sql .= "WHERE  s.cliente = " . $idcliente . " ";
     $sql .= "AND s.id = d.schematico ";
     $sql .= "AND s.datainizio <= DATE('" . $giorno . "') ";
     $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno . "') <= s.datafine) ";
     $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno . "') ";
     $sql .= "AND d.quantita >= 0 ";
     $sql .= "ORDER BY d.sequenza) t1 ";
     $sql .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
     $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
     $sql .= "ISODAYOFWEEK('" . $giorno . "') AS o_giornosettimana2 ";
     $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
     $sql .= "WHERE ";
     $sql .= "o.cliente = " . $idcliente . " ";
     $sql .= "AND o.id = dt.ordinecliente ";
     $sql .= "AND o.dataordine = DATE('" . $giorno . "') ";
     $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
     $sql .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) ";
     $sql .= "HAVING t2.o_dataordine IS NULL \n";

     $sql .= "UNION \n";

     $sql .= "SELECT t2.*, t1.* ";
     $sql .= "FROM ";
     $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
     $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
     $sql .= "ISODAYOFWEEK('". $giorno . "') AS o_giornosettimana2 ";
     $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
     $sql .= "WHERE ";
     $sql .= "o.cliente = " . $idcliente . " ";
     $sql .= "AND o.id = dt.ordinecliente ";
     $sql .= "AND o.dataordine = DATE('". $giorno . "') ";
     $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
     $sql .= "ORDER BY dt.dettaglioordine) t2 ";
     $sql .= "LEFT OUTER JOIN (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
     $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
     $sql .= "ISODAYOFWEEK('2024-06-23') AS s_giornosettimana2 ";
     $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
     $sql .= "WHERE ";
     $sql .= "s.cliente = ". $idcliente . " ";
     $sql .= "AND s.id = d.schematico ";
     $sql .= "AND s.datainizio <= DATE('" . $giorno . "') ";
     $sql .= "AND (s.datafine IS NULL OR DATE('". $giorno . "') <= s.datafine) ";
     $sql .= "AND d.giornosettimana = `ISODAYOFWEEK`('" . $giorno . "') ";
     $sql .= "AND d.quantita >= 0 ";
     $sql .= "ORDER BY d.sequenza) t1 ON (t1.s_prodotto = t2.o_prodotto) ";
     $sql .= "HAVING t1.s_schema IS NULL) b, ";
     $sql .= "cp_prodotto p, cp_listinoprezzi l, cp_dettagliolistino dl, cp_dettagliolistinogruppi gl, cp_cliente c \n";
     $sql .= "WHERE c.id = b.s_cliente AND c.listino = l.id AND \n";
     // 2024-09-06
     // $sql .= "SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND \n"; // 2024-09-06 sostituita con una formula equivalente senza chiamata di funzione
     $sql .= "((b.s_prodotto IS NULL AND b.o_prodotto = p.id) OR (b.s_prodotto IS NOT NULL AND b.s_prodotto = p.id) ) AND \n"; // 2024-09-06 questa condizione è più rapida di quella con la SOLO_NOT_NULL
     // fine 2024-09-06
     $sql .= "l.id = dl.listino AND dl.prodotto = p.id \n";
     $sql .= " AND p.gruppo = gl.gruppo AND gl.listino = c.listino "; // 2024-07-28 dipende anche dal prezzo del gruppo oltre al prezzo del prodotto (potrebbe essere null)
     $sql .= "ORDER BY sequenza";

     // eseguo il comando di query
     $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
     if (!$result) {
       header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
       echo ("Error description: " . mysqli_error($db));
       exit; // fine dello script php
     }
    }
                    $indpos = 0;
                    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $indpos++;
                        $idrigaord = $row["id"];
                        $idrigaschema = $row["riga"];
                        $ordine = $row["ordinecliente"]; // devo fornire anche il numero dell'ordine alla conferma dati
                        $posizione = $row["sequenza"];
                        $unitamisura = $row["unitamisura"]; // Pz o Kg
                        $qtaprodotto = RiduciNumero($row["quantita"]);
                        $prodotto = $row["prodotto"];
                        $gruppo = $row["gruppo"];
                        $codiceprodotto = $row["codiceprodotto"]; // il codice prodotto
                        $descrprodotto = $row["nomeprodotto"];
                        $nomeestesoprodotto = $row["nomeestesoprodotto"]; // 2024-10-16
                        $prezzounit = $row["prezzo"];
                        $prezzoalt = $row["prezzo2"];
                        if (isset($codiceprodotto)){
                           $totale = round($qtaprodotto * $prezzounit, 2, PHP_ROUND_HALF_UP); // arrotondamento per eccesso in valore assoluto 1.5 -> 2, -1.5 -> -2
                        } else {
                           $totale = 0.00;
                        }

                    ?>
                    <tr>
                        <td colspan="30">
                            <?= $nomeestesoprodotto?>
                        </td>

                        <td class="quant">
                            <div id="inqta_<?=$posizione?>" name="inqta_<?=$posizione?>">
                                <?php
                            if ($attivoSalva){
                                ?>
                                <input id="quantita_<?=$posizione?>" name="quantita_<?=$posizione?>" type="number" value="<?= $qtaprodotto ?>" onblur="SalvaQtaProdottoA(<?= $posizione ?>)" onfocus="Entrato('<?= $posizione ?>')" />
                                <?php
                            } else {
                                ?>
                                <input id="quantita_<?=$posizione?>" name="quantita_<?=$posizione?>" type="number" value="<?= $qtaprodotto ?>" disabled/ />
                                <?php
                            }
                                ?>
                            </div>
                            <td>
                                <?= $unitamisura ?>
                                <input id="um_<?= $posizione ?>" name="um_<?= $posizione ?>" type="hidden" value="<?= $unitamisura ?>" />
                                <input id="qt_<?= $posizione ?>" name="qt_<?= $posizione ?>" type="hidden" value="<?= $qtaprodotto ?>" />
                                <input id="oqt_<?= $posizione ?>" name="oqt_<?= $posizione ?>" type="hidden" value="<?= $qtaprodotto ?>" />
                                <input id="cp_<?= $posizione ?>" name="cp_<?= $posizione ?>" type="hidden" value="<?= $codiceprodotto ?>" />
                                <input id="pz_<?= $posizione ?>" name="pz_<?= $posizione ?>" type="hidden" value="<?= $prezzounit ?>" />
                                <input id="dp_<?= $posizione ?>" name="dp_<?= $posizione ?>" type="hidden" value="<?= $descrprodotto ?>" />
                                <input id="ab_<?= $posizione ?>" name="ab_<?= $posizione ?>" type="hidden" value="0" />
                                <input id="id_<?= $posizione ?>" name="id_<?= $posizione ?>" type="hidden" value="<?= $idrigaord ?>" />
                                <input id="ids_<?= $posizione ?>" name="ids_<?= $posizione ?>" type="hidden" value="<?= $idrigaschema ?>" />
                                <input id="pd_<?= $posizione ?>" name="pd_<?= $posizione ?>" type="hidden" value="<?= $prodotto ?>" />
                                <input id="gr_<?= $posizione ?>" name="gr_<?= $posizione ?>" type="hidden" value="<?= $gruppo ?>" />
                                <input id="rg_<?= $indpos ?>" name="rg_<?= $indpos ?>" type="hidden" value="<?= $posizione ?>" />
                            </td>
                    </tr>
                    <?php
                    } // fine del ciclo while sui records dell'ordine
                    ?>
                </table>
                <input id="ordine" name="ordine" type="hidden" value="<?= $ordine ?>" />
                <input id="idcliente" name="idcliente" type="hidden" value="<?= $idcliente ?>" />
            </form>
        </center>
        <br />
        <br />
        <br />
        <br />

        <br />
        <br />
        <br />
        <br />

    </div>
    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>mainpage.php" onclick="if (confirm('Avete salvato le modifiche?') == true) { return true; } else { return false; }" class="bottonehome">HOME</a>
    </div>
</body>

</html>
<?php
// chiusura del database
mysqli_free_result($result);
mysqli_close($db);
?>