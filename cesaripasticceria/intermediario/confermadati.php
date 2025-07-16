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

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/intermediario/";
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/intermediario/";
if ($elementi[0] != "") {
    $serverpath .= $elementi[0] . "/";
}

$identita = leggitoken(); // tre valori (token,user,scadenza)

$token = $identita[0];
$utente = $identita[1];
$scade = $identita[2];
$indice1 = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token, $indice1, $adesso)) {
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
if (isset($_POST["ordine"])) {
    $ordine = $_POST["ordine"];
    if ($ordine == ""){
        $ordine = null;
    }
}

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/confermadati.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/confermadati.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <center>
            <table>
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
            <form action="<?= $serverpath ?>registradati.php" method="POST">
<?php
  // tutti idati del POST tranne alcuni vanno riportati in campo input di tipo hidden
  foreach ($_POST as $campo => $valore){
?>
                <input type="hidden" id="<?=$campo?>" name="<?=$campo?>" value="<?=$valore?>" />
                <?php
  }
  // ora creo la tabella con le informazioni da visualizzare
$oldnumelem = (int)($_POST["onumelem"]);   // numero di righe precedenti
$numelem    = (int)($_POST["numelem"]);    // numero di righe
$giornata   = $_POST["giorno"];     // giorno di riferimento
$salva      = $_POST["salva"];      // bottone salva

$idcliente = $_POST["idcliente"];
$ordine = null;
if (isset($_POST["ordine"])){
    $ordine = $_POST["ordine"];
}

// 17/06/2024 - devo determinare il massimo di spesa concesso al cliente sia in generale che il supero del limite per l'ordine spewcifico

// $sql = "SELECT limiteconcesso FROM cp_autorizzazionisuperospesa WHERE ordine = " . $ordine . " AND responsabile IS NOT NULL AND dataautorizzazione IS NOT NULL ";

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error connecting to db.");
// aperto il database
/*
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

if ($row = mysqli_fetch_array($result)) {
    $limiteconcesso = $row["limiteconcesso"];
} else {

*/
    // il limite è quello del cliente
    // mysqli_free_result($result);
    // limite di spesa generale del cliente
    //    $sql = "SELECT c.limitespesa as limiteconcesso FROM cp_cliente c, cp_ordinecliente o WHERE o.cliente = c.id AND o.id = " . $ordine ;
    // lo schema di default riporta il limite di spesa per quel tipo di ordine
    // $sql = "SELECT s.limitespesa as limiteconcesso FROM cp_schemadefault s, cp_ordinecliente o WHERE o.cliente = s.cliente AND o.schematico = s.id AND o.id = " . $ordine;
    $sql = "SELECT limitespesa FROM cp_cliente WHERE id = " . $idcliente;
    $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    if ($row = mysqli_fetch_array($result)) {
        $limiteconcesso = $row["limitespesa"];
        if ($limiteconcesso == null){
           $limiteconcesso = 999999999.99; // cifra enorme
        }
    } else {
        $limiteconcesso = 999999999.99; // cifra enorme
    }
// } // else per limitespesa legato all'ordine

mysqli_free_result($result);

// Funzioni di supporto

function siglaGiornoSettimana($igiorno){
    $miadata = new DateTime($igiorno);
    $gg = date_format($miadata,"N"); // formato ISO del giorno della settimana
    $risp = "";
    switch($gg){
        case 1: // Lunedì
            $risp = "Lu";
            break;
        case 2: // Martedì
            $risp = "Ma";
            break;
        case 3: // Mercoledì
            $risp = "Me";
            break;
        case 4: // Giovedì
            $risp = "Gi";
            break;
        case 5: // Venerdì
            $risp = "Ve";
            break;
        case 6: // Sabato
            $risp = "Sa";
            break;
        case 7: // Domenica
            $risp = "Do";
            break;
    }
    return $risp;
}

// ora disegniamo la tabella riepilogativa dei prodotti con il totale per ogni prodotto e il totale ordine
$totaleordine = 0.00;
$totaleriga = 0.00;

                ?>
                <table class="riepilogo">
                    <tr>
                        <td>Quantit&agrave;</td>
                        <td>U.M.</td>
                        <!--<td>Codice Prodotto</td>-->
                        <td>Descrizione del prodotto</td>
                        <td align="right">Prezzo unit.</td>
                        <td align="right">Totale</td>
                    </tr>
                    <?php
// attenzione stiamo trattando un giorno della settimana che ci farà da indice
$sigla = siglaGiornoSettimana($giorno);

for ($indice = 1; $indice <= $numelem; $indice++){
    $indiceesteso = $sigla . "_" . $indice;
    $idrigaord = $_POST["id_" . $indice];
    $idrigaschema = $_POST["ids_" . $indice];
    $unitamisura = $_POST["um_" . $indice];
    $quantita = (float)($_POST["qt_" . $indice]);
    $codiceprod = $_POST["cp_" . $indice];
    $descprod = $_POST["dp_" . $indice];
    $prezzoprod = (float)($_POST["pz_" . $indice]);
    $abilitato = (int)($_POST["ab_" . $indice]);
    $prodotto = (int) ($_POST["pd_" . $indice]);
    $gruppo = (int) ($_POST["gr_" . $indice]);
    $totaleriga = number_format((float) round($quantita * $prezzoprod,2), 2, ',', '.');
    $totaleordine += round($quantita * $prezzoprod, 2);
    if ($quantita > 0){
    // &euro; per il simbolo dell'euro
                    ?>
                    <tr>
                        <td align="right">
                            <?=$quantita?>
                        </td>
                        <td><?= $unitamisura ?></td>
                        <!--
                        <td>
                            <?=$codiceprod?>
                        </td>
                        -->
                        <td>
                            <?=$descprod?>
                        </td>
                        <td align="right">
                            &euro; <?= number_format($prezzoprod,2,',','.') ?>
                        </td>

                        <td align="right">
                            &euro; <?= $totaleriga ?>
                        </td>
                    </tr>

                    <?php
    }
}

                    ?>
                    <tr>
<?php
                       // verifico il superamento del limite concesso
                        
                        if ($totaleordine > $limiteconcesso){
?>
                        <td colspan="4"><b>ATTENZIONE</b>: Supera il limite di spesa pari a &euro; <?= number_format($limiteconcesso, 2, ',', '.')?></td>
<?php

                        } else {
?>
                        <td colspan="3"></td>
                           <!-- <td colspan="4"><font size="3"> non supera il limite di spesa pari a &euro; <?= number_format($limiteconcesso, 2, ',', '.') ?></font></td>-->
<?php                        
                        }

?>
                        <td align="right">TOTALE ORDINE</td>
                        <td align="right"><b>&euro; <?=number_format($totaleordine,2,',', '.')?></b></td>
                    </tr>
                </table>
<br/><br /><br/>
                
<?php
if ($totaleordine > $limiteconcesso) {
    ?>
                    <input type="submit" name="submit" id="submit" class="ordina" value="Ordina" disabled/> <button name="refresh" id="refresh" class="refresh" onclick="location.reload(); return false;">Ricarica</button>

    <?php
} else {
    ?>
                    <input type="submit" name="submit" id="submit" class="ordina" value="Ordina" />
    <?php
}
?>


             </form>
        </center>
        <br/><br /><br /><br /><br />
    </div>
    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>mainpage.php">Back</a>
    </div>
</body>
</html>
