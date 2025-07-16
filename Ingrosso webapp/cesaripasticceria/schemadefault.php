<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi dello scehma settimanale degli ordinativi prefissati

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

/* // non serve nello schema di default
if (isset($_GET["giorno"])) {
    $giorno = $_GET["giorno"];
}

if (isset($_POST["giorno"])){
    $giorno = $_POST["giorno"];
}
*/
/*
$giornosettimana = NomeGiornoSettimana($giorno);
$nomemese = NomeMese($giorno);
$gg1 = date("d", strtotime($giorno));
$aa1 = date("Y", strtotime($giorno));
*/
/*
// 29/05/2024 - si possono salvare solo i dati se si è prima delle ore 11.00 del giorno precedente alla data $giorno

// $adesso = date("Y-m-d H:i:s"); // data e ora fino ai secondi
$now = new DateTime();
$adesso = $now->format('Y-m-d H:i:s');
$adessosecondi = strtotime($adesso);
$giornoora = $giorno . " 11:00:00";
$giornoprima = strtotime(date("Y-m-d H:i:s", strtotime($giornoora)) . " -1 days");

$attivoSalva = ($adessosecondi <= $giornoprima);
*/
/*
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
*/

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

function SiglaGiornoSettimana($gg)
{
    // ricava il giorno della settimana in formato ISO 1=Lu,..,6=Sa,7=Do
    $nd = $gg;
    $risp = "";
    switch ($nd) {
        case 1:
            $risp = "Lu";
            break;
        case 2:
            $risp = "Ma";
            break;
        case 3:
            $risp = "Me";
            break;
        case 4:
            $risp = "Gi";
            break;
        case 5:
            $risp = "Ve";
            break;
        case 6:
            $risp = "Sa";
            break;
        case 7:
            $risp = "Do";
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

// devo contare il numero di elementi

$sql = "SELECT COUNT(*) as conta,c.id as cliente, o.id as schematico ";
$sql .= "FROM ";
$sql .= "cp_cliente c, ";
$sql .= "cp_login l ";
$sql .= "LEFT OUTER JOIN cp_schemadefault o ON (o.cliente = l.codice AND o.datafine IS NULL) ";
$sql .= "LEFT OUTER JOIN cp_dettaglioschema d ON (d.schematico = o.id) ";
$sql .= "WHERE ";
$sql .= "c.id = l.codice AND l.tipo = 1 AND "; // solo clienti e non i responsabili hanno schemadefault associato
$sql .= "l.id = " . $indice; /* indice dell'account */

// eseguo il comando di query
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

$numelem = 0;
$idcliente = null;
if ($row = mysqli_fetch_array($result)){
    $numelem = $row["conta"];
    $idcliente = $row["cliente"];
    $schema = $row["schematico"];
}
mysqli_free_result($result);

if ($idcliente == null){
    // non autorizzato perchè non è un cliente
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Questo account non è un cliente", true, 500);
    echo ("Error description: Questo account non è un cliente");
    exit; // fine dello script php
}

// se il conteggio è zero allora lo schema non esiste pertanto va creato per il cliente
if ($schema == null){ // se non ha schema associato al cliente allora lo deve creare
    $schema = CreareSchemaDefaultPerCliente($idcliente);
}

// devo registrare il numero di prodotti presenti in cp_prodotto
$sql = "SELECT COUNT(*) as conta FROM cp_prodotto ";
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

$numelem = 0;
if ($row = mysqli_fetch_array($result)) {
    $numelem = $row["conta"];
}
mysqli_free_result($result);


/**
 *
 * funzione per la creazione dello schema di default degli ordinativi settimanali per il cliente
 */
function CreareSchemaDefaultPerCliente($cln){
    // presi ordinatamente tutti gli elementi da cp_prodotto con l'ordinamento di defautl
    // creiamo per ogni giorno della settimana (tranne il lunedì che sono chiusi)
    // lo schemadefault per il cliente
    // crea la scheda dello schema default
    global $db;

    $oggi = date('Y-m-d');

    $sql = "INSERT INTO cp_schemadefault (cliente, datainizio, datafine, listino, responsabile) VALUES ($cln, DATE('$oggi'),NULL,1,1)"; // listino dei clienti come valore di default, il responsabile è un responsabile automatico = 1
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (20): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $idschema = mysqli_insert_id($db);

    for ($giorno = 1; $giorno < 8; $giorno++) {
        if ($giorno == 1){ // il lunedì viene scartato per chè sono chiusi
            continue;
        }
        $sql = "INSERT INTO cp_dettaglioschema (schematico,giornosettimana,sequenza,prodotto,quantita,unitamisura) \n";
        $sql .= "SELECT $idschema as schematico, $giorno as giornosettimana, SOLO_NOT_NULL(p.sequenza,o.sequenza) as sequenza, p.id as prodotto, 0 as quantita, p.unitamisura as unitamisura \n";
        $sql .= "FROM cp_prodotto p \n";
        $sql .= "LEFT OUTER JOIN cp_ordinamentoprodotti o ON (o.cliente = $cln AND o.prodotto = p.id) ";
        $sql .= "ORDER BY p.sequenza";
        // esegue la query di inserimento
        $result = mysqli_query($db, $sql);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (22): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
    }

    return $idschema;
}

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/schemadefault.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/schemadefault.js"></script>
    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript">var percorso = "<?=$serverpath?>";</script>
</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <table class="intesta">
            <tr>
                <td align="center" class="intestac"></td>
            </tr>
        </table>
    </div>
    <div name="contenuto" class="Contenuti">
        <center>
            <span class="titolo"></span>
        </center>
        <!-- center -->
        <form action="<?=$serverpath?>conferma.php" method="POST" onsubmit="return CheckSubmitFunction(event)">
            <table>
                <tr>
                    <td colspan="7" class="salva">
                        <button id="salva" name="salva" class="clona" onclick="RegistrareDatiSchema(); location.reload();return false;">SALVA</button>
                        &nbsp;&nbsp;
                          Ricopia il giorno di
                        <select id="inizio" name="inizio">
                            <!--<option value="1">Luned&igrave;</option>-->
                            <option value="2">Marted&igrave;</option>
                            <option value="3">Mercoled&igrave;</option>
                            <option value="4">Gioved&igrave;</option>
                            <option value="5">Venerd&igrave;</option>
                            <option value="6">Sabato</option>
                            <option value="7">Domenica</option>
                        </select>
                        &nbsp; sul giorno di
                        <select id="fine" name="fine">
                            <!--<option value="1">Luned&igrave;</option>-->
                            <option value="2">Marted&igrave;</option>
                            <option value="3">Mercoled&igrave;</option>
                            <option value="4">Gioved&igrave;</option>
                            <option value="5">Venerd&igrave;</option>
                            <option value="6">Sabato</option>
                            <option value="7">Domenica</option>
                        </select>
                        &nbsp;&nbsp;
                        <button id="clona" name="clona" class="clona" onclick="clonareGiornoSettimana();RegistrareDatiSchema(); location.reload()">COPIA</button>
                    </td>
                </tr>
                <tr>
                    <td align="center" class="titoli">
                        <!--<b>Luned&igrave;</b>-->
                    </td>
                    <td align="center" class="titoli">
                        <b>Marted&igrave;</b>
                    </td>
                    <td align="center" class="titoli">
                        <b>Mercoled&igrave;</b>
                    </td>
                    <td align="center" class="titoli">
                        <b>Gioved&igrave;</b>
                    </td>
                    <td align="center" class="titoli">
                        <b>Venerd&igrave;</b>
                    </td>
                    <td align="center" class="titoli">
                        <b>Sabato</b>
                    </td>
                    <td align="center" class="titoli">
                        <b>Domenica</b>
                    </td>
                </tr>

                <?php
     // qui faccio la query per i prodotti del giorno per il cliente (identificativo del cliente lo prendo alle cookies)

     $unitamisura = ""; // Pz o Kg
     $qtaprodotto = 0;
     $codiceprodotto = ""; // il codice prodotto
     $descrprodotto = "";
     $prezzounit = 0.00;
     $totale = round($qtaprodotto * $prezzounit,2,PHP_ROUND_HALF_UP); // arrotondamento per eccesso in valore assoluto 1.5 -> 2, -1.5 -> -2

                ?>
                <tr>
                    <?php
for ($igiorno = 1; $igiorno < 8; $igiorno++) {
    // inizio tabelle interne dei singoli giorni della settimana
    $sigla = SiglaGiornoSettimana($igiorno);
                    ?>
                    <td valign="top">

                        <?php
    if ($igiorno != 1) {
        $sql = "SELECT d.id, s.cliente, d.schematico,d.giornosettimana, SOLO_NOT_NULL(d.sequenza,o.sequenza) AS sequenza, d.prodotto, d.quantita, d.unitamisura, p.descrizionebreve as nomeprodotto, ";
        $sql .= "IFNULL(dl.prezzounitario,dg.prezzounitario) as prezzo ";
        $sql .= "FROM cp_schemadefault s  JOIN cp_dettaglioschema d ON (s.id = d.schematico) LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
        $sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = s.cliente) ";
        $sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = c.listino) ";
        $sql .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = c.listino AND dl.prodotto = d.prodotto) ";
        $sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = c.listino AND dg.gruppo = p.gruppo) ";
        $sql .= "LEFT OUTER JOIN cp_ordinamentoprodotti o ON(o.cliente = s.cliente) ";
        $sql .= "WHERE s.cliente = $idcliente AND s.datafine IS NULL AND  d.giornosettimana = $igiorno AND s.id = $schema ";
        $sql .= "ORDER BY d.giornosettimana, d.sequenza";
        // eseguo il comando di query
        $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
                        ?>
                        <table>
                            <?php
                        $indpos = 0;
                        while ($row = mysqli_fetch_array($result)) {
                            $indpos++;
                            $idrigaord = $row["id"];
                            $cliente = $row["cliente"];
                            $schema = $row["schematico"];
                            $giornosett = $row["giornosettimana"];
                            $sequenza = $row["sequenza"];
                            $unitamisura = $row["unitamisura"]; // Pz o Kg
                            $qtaprodotto = RiduciNumero($row["quantita"]);
                            $prodotto = $row["prodotto"];
                            $descrprodotto = $row["nomeprodotto"];
                            $prezzounit = $row["prezzo"];
                            $posizione = $sigla . "_" . $indpos;
                            $g_indpos = $sigla . "_" . $indpos;
                            if ($prezzounit == null){ // 01/08/2024 se non è valorizzato il prezzo viene messa la scritta NULL
                                        $prezzounit = "NULL";
                            }
                            ?>
                            <tr>
                                <td class="prod">
                                    <?= $descrprodotto ?>
                                </td>

                                <td class="quant">
                                    <div id="inqta_<?= $posizione ?>" name="inqta_<?= $posizione ?>" style="display: block;">
                                        <!--<button id="togli_<?= $posizione ?>" name="togli_<?= $posizione ?>" onclick="DecrementaA('<?= $posizione ?>'); return false;" class="bottoni"> - </button>-->
                                        <input id="quantita_<?= $posizione ?>" name="quantita_<?= $posizione ?>" type="number" value="<?= $qtaprodotto ?>" onblur="SalvaQtaProdottoA('<?= $posizione ?>')" onfocus="Entrato('<?= $posizione ?>')" />
                                    </div>
                                </td>
                                <td>
                                    <?= $unitamisura ?>
                                    <input id="um_<?= $posizione ?>" name="um_<?= $posizione ?>" type="hidden" value="<?= $unitamisura ?>" />
                                    <input id="qt_<?= $posizione ?>" name="qt_<?= $posizione ?>" type="hidden" value="<?= $qtaprodotto ?>" />
                                    <input id="oqt_<?= $posizione ?>" name="oqt_<?= $posizione ?>" type="hidden" value="<?= $qtaprodotto ?>" />
                                    <input id="pz_<?= $posizione ?>" name="pz_<?= $posizione ?>" type="hidden" value="<?= $prezzounit ?>" />
                                    <input id="dp_<?= $posizione ?>" name="dp_<?= $posizione ?>" type="hidden" value="<?= $descrprodotto ?>" />
                                    <input id="ab_<?= $posizione ?>" name="ab_<?= $posizione ?>" type="hidden" value="0" />
                                    <input id="id_<?= $posizione ?>" name="id_<?= $posizione ?>" type="hidden" value="<?= $idrigaord ?>" />
                                    <input id="pd_<?= $posizione ?>" name="pd_<?= $posizione ?>" type="hidden" value="<?= $prodotto ?>" />
                                    <input id="sq_<?= $posizione ?>" name="sq_<?= $posizione ?>" type="hidden" value="<?= $sequenza ?>" />
                                    <input id="rg_<?= $g_indpos ?>" name="rg_<?= $g_indpos ?>" type="hidden" class="indice" value="<?= $posizione ?>" />
                                </td>
                            </tr>
                            <?php
                        } // fine del ciclo while sui records dell'ordine
                            ?>
                        </table>
                        <?php
   // 2024-08-18 - qui aggiungiamo un atabella riepilogativa per gruppi prodotti dell'ordinativo previsato per il giorno

                        $sql1 = "SELECT p.gruppo, g.NomeGruppo AS nomegruppo, SUM(d.quantita) AS quantita, d.unitamisura, IFNULL(dl.prezzounitario,dg.prezzounitario) as prezzo ";
                        $sql1 .= "FROM cp_schemadefault s  JOIN cp_dettaglioschema d ON (s.id = d.schematico) LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
                        $sql1 .= "LEFT OUTER JOIN cp_cliente c ON (c.id = s.cliente) ";
                        $sql1 .= "LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = c.listino) ";
                        $sql1 .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = p.gruppo) ";
                        $sql1 .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = c.listino AND dl.prodotto = d.prodotto) ";
                        $sql1 .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = c.listino AND dg.gruppo = p.gruppo) ";
                        $sql1 .= "LEFT OUTER JOIN cp_ordinamentoprodotti o ON(o.cliente = s.cliente) ";
                        $sql1 .= "WHERE s.cliente = $idcliente AND s.datafine IS NULL AND  d.giornosettimana = $igiorno AND s.id = $schema ";
                        $sql1 .= "GROUP BY p.gruppo HAVING quantita > 0 ";
                        $result1 = mysqli_query($db, $sql1) or die("Couldn t execute query." . mysqli_error($db));
                        if (!$result1) {
                           header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                           echo ("Error description: " . mysqli_error($db));
                           exit; // fine dello script php
                        }
                        ?>
                        <br />
                        <table class="dettagli1">
                            <tr>
                                <td class="dettagli1">Gruppo Prodotti</td>
                                <td class="dettagli1">Quantit&agrave;</td>
                                <td class="dettagli1">U.M.</td>
                            </tr>
                            <?php

                        $indpos = 0;
                        while ($row1 = mysqli_fetch_array($result1)) {
                                    $nomegruppo = $row1["nomegruppo"];
                                    $quantgruppo = $row1["quantita"];
                                    $umgruppo = $row1["unitamisura"];
                            ?>
                            <tr>
                                <td class="dettagli1">
                                    <?= $nomegruppo ?>
                                </td>
                                <td class="dettagli1">
                                    <?= str_replace(",000","",number_format($quantgruppo,3,',')) ?>
                                </td>
                                <td class="dettagli1">
                                    <?= $umgruppo ?>
                                </td>
                            </tr>
                            <?php
                        }
                        mysqli_free_result($result1);
                            ?>
                        </table>
                    </td>
                    <?php
    } // fine tabelle interne dei singoli giorni della settimana
} // fine ciclo dei giorno
                    ?>
                </tr>
            </table>
            <input id="schema" name="schema" type="hidden" value="<?= $schema ?>" />
            <input id="numelem" name="numelem" type="hidden" value="<?= $numelem ?>" />
        </form>
        <!--</center>-->
        <br />
        <br />
        <br />
        <br />

        <br />
        <br />
        <br />
        <br />
        <button id="salva" name="salva" class="clona" onclick="RegistrareDatiSchema(); location.reload();return false;">SALVA</button>
        &nbsp;&nbsp;
    </div>

    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>mainpage.php" class="home" onclick="if (confirm('Avete salvato le modifiche? OK per Sì, altrimenti premere Annulla') == true) { return true; } else { return false; }">HOME</a>
    </div>
</body>

</html>
<?php
// chiusura del database
mysqli_free_result($result);
mysqli_close($db);
?>