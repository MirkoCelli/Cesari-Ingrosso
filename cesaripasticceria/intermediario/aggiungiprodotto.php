<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi di una data giornata in ordine da parte del cliente

include "../include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

//$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/intermediario/";

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
// deve esistere sempre l'id cliente

$idcln = $_REQUEST["id"];

$giorno = date("Y-m-d"); // oggi

if (isset($_GET["giorno"])) {
    $giorno = $_GET["giorno"];
}

if (isset($_POST["giorno"])) {
    $giorno = $_POST["giorno"];
}

$ordine = null;
if (isset($_GET["ordine"])) {
    $ordine = $_GET["ordine"];
}
if (isset($_POST["ordine"])) {
    $ordine = $_POST["ordine"];
}

$azione = null;
if (isset($_GET["Azione"])) {
    $azione = $_GET["Azione"];
}
if (isset($_POST["Azione"])) {
    $azione = $_POST["Azione"];
}

$prodotto = null;
if (isset($_POST["prodotto"])) {
    $prodotto = $_POST["prodotto"];
}

$quantita = 0;
if (isset($_POST["quantita"])) {
    $quantita = $_POST["quantita"];
}

$errore = "";

// elenco in una tabella i prodotti per gruppo (disabilito la selezione sui prodotti che ci sono già nell'ordine del cliente)

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

$titolo = "";
if ($azione == "Salva") {
    // qui eseguiamo la query di inserimento nuova voce nell'ordine indicato, mettendo come dettaglioordine il max(dettaglioordine) + 1
    $sqlprd = "SELECT * FROM cp_prodotto WHERE id = " . $prodotto; // è un unico prodotto
    $result = mysqli_query($db, $sqlprd) or die("Couldn t execute query." . mysqli_error($db));
    if (!$result) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
      echo ("Error description: " . mysqli_error($db));
      exit; // fine dello script php
    }
    if ($row = mysqli_fetch_array($result)) {
        $codiceprodotto = $row["codiceprodotto"];
        $gruppoprod = $row["gruppo"];
        $nomeprodotto = $row["descrizionebreve"];
        $unmis = $row["unitamisura"];
        $titolo = "Aggiunto prodotto: " . $nomeprodotto . " -- " . $codiceprodotto;
    }
    mysqli_free_result($result);
    // devo farmi dare il max(dettaglioordine) dell'ordine in oggetto
    $sqlprd = "SELECT MAX(dettaglioordine) as maxdetord FROM cp_dettaglioordine WHERE ordinecliente = " . $ordine; // è un unico prodotto
    $result = mysqli_query($db, $sqlprd) or die("Couldn t execute query." . mysqli_error($db));
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    if ($row = mysqli_fetch_array($result)) {
        $maxdetord = $row["maxdetord"] + 1;
    } else {
        $maxdetord = 1; // non si cono dettagli quindi è un primo ordine
    }
    mysqli_free_result($result);
    //
    $errore = "";
    $sqlins  = "INSERT INTO cp_dettaglioordine ( ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato ) VALUES (";
    $sqlins .= $ordine . ",";
    $sqlins .= $maxdetord . ",";
    $sqlins .= $prodotto . ",";
    $sqlins .= $gruppoprod . ",";
    $sqlins .= $quantita . ",";
    $sqlins .= "'" . $unmis . "',";
    $sqlins .= "0"; // stato attivo
    $sqlins .= ")";
    if (mysqli_query($db, $sqlins) === false) {
        $errore = "Problemi ad aggiornare l'ordine";
        $benvenuto = "";
    } else {
        $ultimoid = mysqli_insert_id($db); // id attribuito a questo nuovo record
    }
}


$sql  = "SELECT g.NomeGruppo AS nomegruppo, g.id AS gruppo, p.id as prodotto, p.codiceprodotto AS codiceprodotto, p.descrizionebreve as nomeprodotto, p.gruppo AS gruppoprodotto, p.descrizionecompleta AS descrizione, p.unitamisura AS unmis ";
$sql .= "FROM cp_prodotto p, cp_gruppoprodotti g ";
$sql .= "WHERE g.id = p.gruppo AND p.id NOT IN (SELECT prodotto FROM cp_dettaglioordine d WHERE d.ordinecliente = " . $ordine . " ) ";
$sql .= "ORDER BY g.id, p.descrizionebreve, p.codiceprodotto ";

$gruppo = 0;

$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
   echo ("Error description: " . mysqli_error($db));
   exit; // fine dello script php
}
?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/aggiungiprodotto.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/aggiungiprodotto.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <center>
            <table border="0">
                <tr>
                    <td align="center" class="intestac">
                        <center>
                            <span class="titolo">
                                Giorno <?= date('d/m/Y', strtotime($giorno)) ?>
                            </span>
                        </center>
                    </td>
                </tr>
            </table>
        </center>
    </div>
    <div name="contenuto" class="Contenuti">
        <center>
            <h3><?=$titolo?></h3>
            <h3><?=$errore?></h3>
            <form action="<?= $serverpath ?>aggiungiprodotto.php" method="POST">
                <input type="hidden" name="ordine" value="<?=$ordine?>" />
                <input type="hidden" name="giorno" value="<?=$giorno?>" />
                
                <label class="qta">Quantit&agrave;:</label> <input type="text" name="quantita" id="quantita" value="1" /><br/><br /><br />

                <table>
<?php
$indpos = 0;
while ($row = mysqli_fetch_array($result)) {
    $indpos++;
    $idgruppo = $row["gruppo"];
    $nomegruppo = $row["nomegruppo"];
    $prodotto = $row["prodotto"];
    $codiceprodotto = $row["codiceprodotto"];
    $gruppoprodotto = $row["gruppoprodotto"];
    $nomeprodotto = $row["nomeprodotto"];
    $descrizione = $row["descrizione"];
    $unmis = $row["unmis"];
    // ora prepariamo la riga corrispondente
    if ($gruppo !== $idgruppo){
        $gruppo = $idgruppo;
?>
                     <tr><td colspan="2" align="center"><b><?=$nomegruppo?></b></td></tr>
<?php        
    }
?>
                     <tr><td><input type="radio" name="prodotto" id="<?=$codiceprodotto?>" value="<?=$prodotto?>" /> <label for="<?= $codiceprodotto ?>"><?=$nomeprodotto?></label></td></tr>
<?php
}
?>
               </table>
                <br />
                <br />
                <br />
                <br />
                <input type="submit" name="Azione" value="Salva" />
            </form>
            <form action="<?= $serverpath ?>giornaliero.php" method="POST">
                <input type="hidden" name="ordine" value="<?= $ordine ?>" />
                <input type="hidden" name="giorno" value="<?= $giorno ?>" />
                <input type="submit" name="submit" value="Ritorna" />
            </form>
                <br />
                <br />
                <br />
                <br />

</center>
        <br />
        <br />
        <br />
        <br />
        <br />
<?php
mysqli_free_result($result);
mysqli_close($db);
?>
    </div>
    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>mainpage.php">Back</a>
    </div>
</body>
</html>
