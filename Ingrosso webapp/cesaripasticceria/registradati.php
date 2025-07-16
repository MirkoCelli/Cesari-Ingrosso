<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi di una data giornata in ordine da parte del cliente

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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
$indice1 = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token, $indice1, $adesso)) {
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}

$giorno = date("Y-m-d"); // oggi

if (isset($_GET["giorno"])) {
    $giorno = $_GET["giorno"];
}

if (isset($_POST["giorno"])) {
    $giorno = $_POST["giorno"];
}

$ordine = null;
if (isset($_POST["ordine"]) && $_POST["ordine"] != "") {
    $ordine = $_POST["ordine"];
}

$idcliente = null;
if (isset($_POST["idcliente"]) && $_POST["idcliente"] != "") {
    $idcliente = $_POST["idcliente"];
}


// occorre tenere presente che mi ritorna tutti i parametri POST ricevuti da confermadati.php e quindi c'è anche ordine
// e tutte le indicazioni di quali valori sono stati variati e quali no.
// se ci sono degli elementi da aggiungere gli si assegna un progressivo superiore al massimo dettaglioordine registrato (deve essere univoco per ogni nuovo elemento)
// ATTENZIONE: questo indice potrebbe fare sballare l'ordine di preparazione dei vassoi destinati al cliente

?>
<html>
 <head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/registradati.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/registradati.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


 </head>
 <body>
     <?php
 // leggiamo in un array di array tipo records i dati ottenutoi di $_POST
 $oldnumelem = (int) ($_POST["onumelem"]); // numero di righe precedenti
 $numelem = (int) ($_POST["numelem"]); // numero di righe
 $giornata = $_POST["giorno"]; // giorno di riferimento
 $salva = $_POST["salva"]; // bottone salva
 $ordinecliente = $_POST["ordine"]; // numero dell'ordine in esame
 $dati = [];
 for ($i = 1; $i <= $numelem; $i++){
    $indpos = $_POST["rg_".$i];
    $registrazione = null;
    $registrazione["idrigaord"] = $_POST["id_".$indpos];
    $registrazione["idrigaschema"] = $_POST["ids_" . $indpos];
    $registrazione["unmis"] = $_POST["um_".$indpos];
    $registrazione["quantita"] = $_POST["qt_".$indpos];
    $registrazione["oldquantita"] = $_POST["oqt_".$indpos];
    $registrazione["codiceprodotto"] = $_POST["cp_".$indpos];
    $registrazione["prezzo"] = $_POST["pz_".$indpos];
    $registrazione["descrizioneprodotto"] = $_POST["dp_".$indpos];
    $registrazione["prodotto"] = $_POST["pd_".$indpos];
    $registrazione["gruppo"] = $_POST["gr_".$indpos];
    $registrazione["abilitato"] = $_POST["ab_".$indpos];
    $registrazione["posizione"] = $indpos;
    // sistemare i valori: se sono blank sostituirli con null
    if ($registrazione["idrigaord"] == "") { $registrazione["idrigaord"] = null; }
    if ($registrazione["idrigaschema"] == "") { $registrazione["idrigaschema"] = null; }
    //
    $dati[$i] = $registrazione;
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
 // devo stabilire se esiste il record in cp_ordinecliente per la giornata odierna
 $idordine = null;
 $nomecliente = "";
 $emailcliente = "";
 $sql = "SELECT o.id as id, c.Denominazione as nomecliente, c.email as email FROM cp_ordinecliente o JOIN cp_cliente c ON (c.id = o.cliente) WHERE o.cliente = " . $idcliente . " AND o.dataordine = DATE('" . $giorno . "') LIMIT 1 OFFSET 0 ";

 $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
 if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
 }

 if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $idordine = $row["id"];
    $nomecliente = $row["nomecliente"];
    $emailcliente = $row["email"];
 }
 mysqli_free_result($result);

 // 2024-07-28 - se non ha la email il cliente allora ritenta la lettura per avere la sua email
 $sql = "SELECT o.id as id, c.Denominazione as nomecliente, c.email as email FROM cp_ordinecliente o JOIN cp_cliente c ON (c.id = o.cliente) WHERE o.cliente = " . $idcliente . " AND o.dataordine = DATE('" . $giorno . "') LIMIT 1 OFFSET 0 ";
 $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
 if (!$result) {
     header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
     echo ("Error description: " . mysqli_error($db));
     exit; // fine dello script php
 }
 if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
     $emailcliente = $row["email"];
 }
 mysqli_free_result($result);

// fine 2024-07-28

     $errore = "";
 $benvenuto = "Aggiornato ordine del " . date("d/m/Y",strtotime($giorno));
 for ($i = 1; $i <= $numelem; $i++){
     if ($dati[$i]["abilitato"] > 0){
         // la quantità è stata modificata se abilitato = 1 altrimenti è una nuova registrazione

         $idrigaord = null;

         if ($dati[$i]["abilitato"] == 1){
             // segnare che il vecchio record della riga ordine è in stato sostituito (stato := 2 se era a stato = 0)
             /*
               0 = Valido
               1 = Annullato
               2 = Sostituito (cliente ha variato le quantità)
               3 = Modificato (il responsabile ha modificato le quantità)
             */
            // se non c'è record in ordinecliente allora abbiamo $dati[$i]["idrigaord"] != null , quindi il record va inserito per la prima volta
             // non devve fare l'update
            $idrigaord = $dati[$i]["idrigaord"];
            if ($idrigaord != null){
               $sql = "UPDATE cp_dettaglioordine SET stato = 2 WHERE id = " . $idrigaord;
               mysqli_query($db, $sql);
            }
         }
         // se $ordine == Null allora va inserito il primo record
         if ($idordine == null) {
           $sql = "INSERT INTO cp_ordinecliente (cliente,schematico,dataordine,stato,autorizzatosuperamentospesa,codiceautorizzazione) \n"; // stato = 2 --> Aperto
           $sql .= "SELECT c.id as cliente, s.id as schematico, DATE('" . $giorno . "') as dataordine, 2 as stato, 0 as autorizzatosuperamentospesa, 0 as codiceautorizzazione ";
           $sql .= "FROM cp_cliente c, cp_schemadefault s ";
           $sql .= "WHERE c.id = " . $idcliente . " AND s.cliente = c.id AND s.datainizio <= DATE('" . $giorno . "') ";
           $sql .= "AND (s.datafine IS NULL) LIMIT 1 OFFSET 0 ";
           // solo il primo viene considerato quello valido

           if (mysqli_query($db, $sql) === false) {
             $errore = "Problemi ad aggiornare l'ordine (1)";
             $benvenuto = "";
           } else {
             $ordinecliente = mysqli_insert_id($db); // id attribuito a questo nuovo record
             $idordine = $ordinecliente; // 2024-07-09 - manca da assegnarlo al idordine il nuovo record se non è presente
           }
         } else {
            $ordinecliente = $idordine;
         }
         // ora inserisco il nuovo record con le quantità correnti
         $unmis = $dati[$i]["unmis"];
         $dettaglioordine = $dati[$i]["posizione"];
         $quantita = $dati[$i]["quantita"];
         $codiceprodotto = $dati[$i]["codiceprodotto"];
         $prodotto = $dati[$i]["prodotto"];
         $gruppo = $dati[$i]["gruppo"];
         $sql = "INSERT INTO cp_dettaglioordine (ordinecliente,dettaglioordine,prodotto,gruppo,quantita,unitamisura,stato,riferimentoprec) VALUES ";
         $sql .= "(" . $ordinecliente . "," . $dettaglioordine . "," . $prodotto . "," . $gruppo . "," . $quantita . ",'" . $unmis . "',0,";
         if ($idrigaord !== null) {
            $sql .= $idrigaord . ")";
         } else {
            $sql .= "NULL)";
         }
         // ora eseguo l'insert
             if (mysqli_query($db, $sql) === false) {
                 $errore = "Problemi ad aggiornare l'ordine";
                 $benvenuto = "";
             } else {
                 $ultimoid = mysqli_insert_id($db); // id attribuito a questo nuovo record
             }
     }
}

// 16/07/2024 - Invio di una email di conferma con elenco dell'ordine della giornata modificato al cliente

// preparare il testo della tabella in HTML con i dati riepilogativi dell'ordine

$tabellaordine = GeneraTabellaOrdine($dati, $numelem);

$successo = "";
$errore = "";

$mail = new PHPMailer(true);

try{
         // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // con questo leggiamo i messagi di comunicazione fra client e server
         // $mail->SMTPDebug = 0;
         /* // solo per Debug - 2024-07-26
         $mail->isSMTP();
         $mail->Host = 'out.alice.sm';
         $mail->SMTPAuth = true;
         $mail->Username = 'inthenet@omniway.sm';
         $mail->Password = 'cap30968';
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
         $mail->Port = 587;
         $mail->setFrom('inthenet@omniway.sm');
         */
      if ($emailcliente != ""){


         $mail->isSMTP();
         $mail->Host = 'authsmtp.securemail.pro';
         $mail->SMTPAuth = true;
         $mail->Username = 'smtp@cesaripasticceria.it';
         $mail->Password = 'P@138-st55';
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
         $mail->Port = 465;
         $mail->setFrom('info@cesaripasticceria.it');

         $mail->addAddress($emailcliente);
         $mail->addAddress('gasperonirobert@gmail.com');
         $mail->addReplyTo('info@cesaripasticceria.it');
         /*
         $mail->addCC('gasperonirobert@yahoo.com');
         $mail->addBCC('gasperonirobert@yahoo.com');
         */

         // file allegati al messaggio
         // $mail->addAttachment('/percorso_del_file/nome_file');

         // corpo del messaggio
         $mail->charSet = "ISO-8859-1"; // indica la codifica in Latin 1
         $mail->setLanguage('it');
         $mail->isHTML(true);
         $mail->Subject = "Vostro Ordine per il giorno " . date("d/m/Y",strtotime($giornata));
         $testo = "<html><meta charset=\"ISO-8859-1\"><body>";
         $testo .= "Spett.le " . $nomecliente . "<br/>";
         $testo .= "  in allegato alla presente il riepilogo del vostro ordine per la giornata del " . date("d/m/Y", strtotime($giornata));
         $testo .= "<br/> con le eventuali variazioni da voi registrate in data " . date("d/m/Y H:i:s");
         $testo .= "<br/><br/>" . $tabellaordine;
         $testo .= "<br/><br/>Cordialmente, <br/>Pasticceria Cesari";
         $testo .= "</body></html>";
         $mail->Body = utf8_decode($testo); // questo funziona bene per le lettere accentate
         $mail->AltBody = utf8_decode($testo); // questo funziona bene per le lettere accentate
         // invio del messaggio
         if (!$mail->send()) {
             $errore = "Non riesco a spedire il messaggio: " . $mail->ErrorInfo;
         } else {
             $successo = 'Messaggio inviato';
         }
      }
     } catch(Exception $e)
{
         $errore = "Non riesco a spedire il messaggio: {$mail->ErrorInfo}";
}

/* FUNZIONE DI SUPPORTO */
function GeneraTabellaOrdine($datiordine, $numelementi){
  $tabella = "<table border='1'>\n";
  $tabella .= " <tr>\n";
  $tabella .= "  <td align='right'>Quantit&agrave;</td>\n";
  $tabella .= "  <td>U.M.</td>\n";
  $tabella .= "  <td>Codice Prodotto</td>\n";
  $tabella .= "  <td>Descrizione del Prodotto</td>\n";
  $tabella .= "  <td align='right'>Prezzo Unitario</td>\n";
  $tabella .= "  <td align='right'>Totale</td>\n";
  $tabella .= " </tr>\n";
  $totaleordine = 0.00;
  for ($i = 1; $i <= $numelementi; $i++) {
    $totaleordine += round($datiordine[$i]["quantita"] * $datiordine[$i]["prezzo"], 2);
    $tabella .= " <tr>\n";
    $tabella .= "  <td align='right'>" . $datiordine[$i]["quantita"] . "</td>\n";
    $tabella .= "  <td>" . $datiordine[$i]["unmis"] . "</td>\n";
    $tabella .= "  <td>" . $datiordine[$i]["codiceprodotto"] . "</td>\n";
    $tabella .= "  <td>" . $datiordine[$i]["descrizioneprodotto"] . "</td>\n";
    $tabella .= "  <td align='right'>&euro; " . number_format((float)$datiordine[$i]["prezzo"],2,',','.') . "</td>\n";
    $tabella .= "  <td align='right'>&euro; " . number_format((float)($datiordine[$i]["quantita"] * $datiordine[$i]["prezzo"]),2,',','.') . "</td>\n";
    $tabella .= " </tr>\n";
  }
  // riga del totale
  $tabella .= " <tr>\n";
  $tabella .= "  <td></td>\n";
  $tabella .= "  <td></td>\n";
  $tabella .= "  <td></td>\n";
  $tabella .= "  <td></td>\n";
  $tabella .= "  <td align='right'>TOTALE ORDINE</td>\n";
  $tabella .= "  <td align='right'>&euro; " . number_format((float)($totaleordine),2,',','.') . "</td>\n";
  $tabella .= " </tr>\n";
  $tabella .= "</table>\n";
  return $tabella;
}

     ?>
     <div name="intestazione" class="Intestazioni">
         <center>
         </center>
     </div>

     <div name="contenuto" class="Contenuti">
         <center>
             <table>
                 <tr>
                     <td>
                         <label>
                             <?= $benvenuto ?>
                         </label>
                         <label>
                             <?= $errore ?>
                         </label>
                     </td>

                 </tr>
                 <tr>
                     <td>
                         <form action="giornaliero.php" method="post">
                             <input type="hidden" name="giorno" id="giorno" value="<?= $giorno?>" />
                             <input type="submit" name="submit" id="submit" value="RITORNA" class="bottone" />
                         </form>
                     </td>

                 </tr>
             </table>
         </center>
     </div>
     <div name="menubasso" class="MenuComandi">
         <a href="<?= $serverpath ?>mainpage.php" class="home">HOME</a>
     </div>
 </body>
</html>
