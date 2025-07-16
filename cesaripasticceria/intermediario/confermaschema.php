<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi di una scehma ordini di default per il cliente

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
}

// funzionalità di supporto

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
/* Fine funzionalità di supporto */

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/confermaschema.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/confermaschema.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <center>            
        </center>
    </div>
    <div name="contenuto" class="Contenuti">
        
        <center>
            <form action="<?= $serverpath ?>registraschema.php?id=<?=$idcln?>" method="POST">
<?php
  // tutti idati del POST tranne alcuni vanno riportati in campo input di tipo hidden
  foreach ($_POST as $campo => $valore){
?>
                <input type="hidden" id="<?=$campo?>" name="<?=$campo?>" value="<?=$valore?>" />
<?php
  }

// ora creo la tabella con le informazioni da visualizzare

$numelem    = (int)($_POST["numelem"]);    // numero di righe
$salva      = $_POST["salva"];      // bottone salva

?>
                <table>
                    <tr>
                        <td>Luned&igrave;</td>
                        <td>Marted&igrave;</td>
                        <td>Mercoled&igrave;</td>
                        <td>Gioved&igrave;</td>
                        <td>Venerd&igrave;</td>
                        <td>Sabato</td>
                        <td>Domenica</td>
                    </tr>
                    <tr>
                        <?php
// ora leggiamo i dati per ogni singolo giorno della settimana e li elenchiamo nella colonna del giorno
for ($igiorno = 1; $igiorno < 8; $igiorno++){
    $sigla = SiglaGiornoSettimana($igiorno);

    if ($igiorno == 1){ // il lunedì è giorno di chiusura e quindi non si fanno consegne
       echo "<td></td>";
       continue;
    }
    // ora disegniamo la tabella riepilogativa dei prodotti con il totale per ogni prodotto e il totale ordine
    $totaleordine = 0.00;
    $totaleriga = 0.00;

                        ?>
                        <td>
                        <table class="riepilogo">
                            <tr>
                                <td>Q.t&agrave;</td>
                                <td>U.M.</td>
                                <td>Descrizione del prodotto</td>
                                <td align="right">Prezzo</td>
                                <td align="right">Totale</td>
                            </tr>
                            <?php
for ($indice = 1; $indice <= $numelem; $indice++){
    $indiceesteso = $sigla . "_" . $indice;
    $idrigaord = $_POST["id_" . $indiceesteso];
    $unitamisura = $_POST["um_" . $indiceesteso];
    $quantita = (float)($_POST["qt_" . $indiceesteso]);
    $descprod = $_POST["dp_" . $indiceesteso];
    $prezzoprod = (float)($_POST["pz_" . $indiceesteso]);
    $abilitato = (int)($_POST["ab_" . $indiceesteso]);
    $prodotto = (int) ($_POST["pd_" . $indiceesteso]);
    $totaleriga = number_format((float) round($quantita * $prezzoprod,2), 2, ',', '.');
    $totaleordine += round($quantita * $prezzoprod, 2);
    // &euro; per il simbolo dell'euro
                            ?>
                            <tr>
                                <td align="right">
                                    <?=$quantita?>
                                </td>
                                <td>
                                    <?= $unitamisura ?>
                                </td>
                                <td>
                                    <?=$descprod?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format($prezzoprod,2,',','.') ?>
                                </td>

                                <td align="right">
                                    &euro;<?= $totaleriga ?>
                                </td>
                            </tr>

                            <?php
} // fine ciclo prodotti
                            ?>
                            <tr>
                                <td colspan="2"></td>
                                <td align="right">TOTALE ORDINE</td>
                                <td></td>
                                <td align="right">
                                    <b>
                                        &euro;<?=number_format($totaleordine,2,',', '.')?>
                                    </b>
                                </td>
                            </tr>
                        </table>
                        </td>
                        <?php
} // fine ciclo giorni settimana
                        ?>
                    </tr>                    
                </table>
                <br />
                <br />
                <br />
                <input type="submit" name="submit" id="submit" class="ordina" value="Conferma" />
                <br />
                <br />
                <br />
             </form>
        </center>
        <br/><br /><br /><br /><br /><br />
<br />
<br />
        <br />
        <br />
        <br />

    </div>
    <!--
    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>mainpage.php">Back</a>
    </div>
            -->
</body>
</html>
