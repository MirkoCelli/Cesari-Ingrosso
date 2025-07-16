<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi dello schema di default degli ordini per il cliente

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/intermediario/";
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

// occorre tenere presente che mi ritorna tutti i parametri POST ricevuti da confermadati.php e quindi c'è anche ordine
// e tutte le indicazioni di quali valori sono stati variati e quali no.
// se ci sono degli elementi da aggiungere gli si assegna un progressivo superiore al massimo dettaglioordine registrato (deve essere univoco per ogni nuovo elemento)
// ATTENZIONE: questo indice potrebbe fare sballare l'ordine di preparazione dei vassoi destinati al cliente

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
    <link rel="stylesheet" href="<?= $serverpath ?>css/registraschema.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/registraschema.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


 </head>
 <body>
     <?php
 // leggiamo in un array di array tipo records i dati ottenutoi di $_POST
 // $oldnumelem = (int) ($_POST["onumelem"]); // numero di righe precedenti
 $numelem = (int) ($_POST["numelem"]); // numero di righe
 // $giornata = $_POST["giorno"]; // giorno di riferimento
 $salva = $_POST["salva"]; // bottone salva
 // $ordinecliente = $_POST["ordine"]; // numero dell'ordine in esame
 $giornate = [];
 for ($igiorno = 1; $igiorno < 8; $igiorno++)
 {
    $dati = [];
    $sigla = SiglaGiornoSettimana($igiorno);
    if ($igiorno != 1) {
       for ($i = 1; $i <= $numelem; $i++) {
         $indpos = $_POST["rg_" . $sigla . "_" . $i];
         $indposestesa = $sigla . "_" . $i;
         $registrazione = null;
         $registrazione["idrigaord"] = $_POST["id_" . $indposestesa];
         $registrazione["unmis"] = $_POST["um_" . $indposestesa];
         $registrazione["quantita"] = $_POST["qt_" . $indposestesa];
         $registrazione["oldquantita"] = $_POST["oqt_" . $indposestesa];
         $registrazione["prezzo"] = $_POST["pz_" . $indposestesa];
         $registrazione["descrizioneprodotto"] = $_POST["dp_" . $indposestesa];
         $registrazione["prodotto"] = $_POST["pd_" . $indposestesa];
         $registrazione["abilitato"] = $_POST["ab_" . $indposestesa];
         $registrazione["posizione"] = $indposestesa;
         $registrazione["sequenza"] = $_POST["sq_" . $indposestesa];
         $dati[$i] = $registrazione;
       }
       $giornate[$igiorno] = $dati;
    }
 }
     // solo chi è abilitato dovrà essere modificato come record nelle tabelle dell'ordine
 // cioè: il vecchio record idrigaord viene segnato come Sostituito se abilitato = 1, altrimenti è un nuovo record se abilitato = 2
 //       si crea un nuovo record con gli elementi del record precedente ma con quantità aggiornata ovvero un nuovo record con i dati letti
 // si ricalcola il totale

 // connect to the database
 $db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
 if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
 }

 mysqli_select_db($db, $dbname) or die("Error conecting to db.");
 $errore = "";
 $benvenuto = "Aggiornato schema dell'ordine del " . date("d/m/Y",strtotime($giorno));
 for ($igiorno = 1; $igiorno < 8; $igiorno++)
 {
     if ($igiorno != 1){ // il lunedì è escluso
      $dati = $giornate[$igiorno];
      for ($i = 1; $i <= $numelem; $i++)
      {
       if ($dati[$i]["abilitato"] > 0)
       {
         $idrigaord = null;

         if ($dati[$i]["abilitato"] == 1){
             // vengono aggiornate solo le quantità del prodotto per la giornata corrente
            $unmis = $dati[$i]["unmis"];
            $dettaglioordine = $dati[$i]["posizione"];
            $quantita = $dati[$i]["quantita"];            
            $prodotto = $dati[$i]["prodotto"];            
            $idrigaord = $dati[$i]["idrigaord"];
            $sequenza = $dati[$i]["sequenza"];
            $sql = "UPDATE cp_dettaglioschema SET ";
            $sql .= "quantita = " . $quantita . " ";
            $sql .="WHERE id = " . $idrigaord;
            mysqli_query($db, $sql);
         }
       }
      }
     }
 }
// 27/06/2024 - Invio di una email di conferma con elenco dell'ordine della giornata modificato al cliente
     ?>
     <div name="intestazione" class="Intestazioni">
         <center>
         </center>
     </div>

     <div name="contenuto" class="Contenuti">
         <center>
             <table>
                 <tr>
                     <td align="center">
                         <label class="titoli">
                             <?= $benvenuto ?>
                         </label>
                         <label class="titoli">
                             <?= $errore ?>
                         </label>

                     </td>

                 </tr>
                 <tr>
                     <td align="center">
                         <form action="schemadefault.php" method="post">
                             <input type="hidden" id="id" name="id" value="?id=<?= $idcln ?>" />
                             <input class="clona" type="submit" name="submit" id="submit" value="Ritorna" />
                         </form>
                     </td>
                 </tr>
             </table>
         </center>
     </div>
     <div name="menubasso" class="MenuComandi">
         <a href="<?= $serverpath ?>schemadefault.php?id=<?=$idcln?>">Back</a>
     </div>
 </body>
</html>
