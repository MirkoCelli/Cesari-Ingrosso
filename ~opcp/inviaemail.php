<?php

// 2024-12-31 - Invia una comunicazione a tutti i clienti per il periodo di ferie

include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// mi vengono dati le date del periodo di ferie
$inizio = null;
$fine = null;

$dtinizio = null;
$dtfine = null;

if ($_REQUEST["inizio"]){
    $inizio = $_REQUEST["inizio"];
}
if ($_REQUEST["fine"]) {
    $fine = $_REQUEST["fine"];
}
// li fornisce già in formato italiano
if (strpos("/",$inizio)){
    // devo convertire la data da formato ISO in formato italiano
    if ($inizio != null) {
        $elem = explode('-', $inizio);
        $dtinizio = $elem[2] . "/" . $elem[1] . "/" . $elem[0];
    }

    if ($fine != null) {
        $elem = explode('-', $fine);
        $dtfine = $elem[2] . "/" . $elem[1] . "/" . $elem[0];
    }
} else {
    $dtinizio = $inizio;
    $dtfine = $fine;
}


// ora devo farmi dare le singole email di tutti i clienti che hanno una email associata

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno())
{
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
   exit; // fine dello script php
}

mysqli_select_db($db,$database) or die("Error conecting to db.");

$result = mysqli_query($db,"SELECT id, email, denominazione FROM cp_cliente b WHERE email IS NOT NULL");

if (!$result){
  header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (5): " . mysqli_error($db), true, 500);
  echo("Error description: " . mysqli_error($db));
  exit; // fine dello script php
}
$contaclienti = 0;
$contaerrori = 0;
$errori = "";

// while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
while ($row = mysqli_fetch_array($result)) {
    $idcliente = $row["id"];
    $emailcliente = $row["email"];
    $nomecliente = $row["denominazione"];

    $mail = new PHPMailer(true);

    try {
        /*
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // con questo leggiamo i messagi di comunicazione fra client e server
        // $mail->SMTPDebug = 0;
        / * solo per debug - 02/08/2024
             $mail->isSMTP();
             $mail->Host = 'out.alice.sm';
             $mail->SMTPAuth = true;
             $mail->Username = 'inthenet@omniway.sm';
             $mail->Password = 'cap30968';
             $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
             $mail->Port = 587;

             $mail->setFrom('inthenet@omniway.sm');
             $mail->addAddress($emailcliente);
             $mail->addAddress('gasperonirobert@gmail.com');
             $mail->addReplyTo('inthenet@omniway.sm');
        */
        /*
        $mail->addCC('gasperonirobert@yahoo.com');
        $mail->addBCC('gasperonirobert@yahoo.com');
        */
        $errore = "";
        $successo = "";
        $contaclienti++;
        if ($emailcliente != "") {
            /*
            // /* solo in debug
            $mail->isSMTP();
            $mail->Host = 'out.alice.sm';
            $mail->SMTPAuth = true;
            $mail->Username = 'inthenet@omniway.sm';
            $mail->Password = 'cap30968';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('inthenet@omniway.sm');
            */
            
            // solo in produzione
            $mail->isSMTP();
            $mail->Host = 'authsmtp.securemail.pro';
            $mail->SMTPAuth = true;
            $mail->Username = 'smtp@cesaripasticceria.it';
            $mail->Password = 'P@138-st55';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('info@cesaripasticceria.it');
            
            $mail->addAddress($emailcliente);
            // $mail->addAddress('gasperonirobert@gmail.com'); // da togliere in produzione
            $mail->addReplyTo('info@cesaripasticceria.it');

            // file allegati al messaggio
            // $mail->addAttachment('/percorso_del_file/nome_file');

            // corpo del messaggio
            $mail->charSet = "ISO-8859-1"; // indica la codifica in Latin 1
            $mail->setLanguage('it');
            $mail->isHTML(true);
            $mail->Subject = "Comunicazione Chiusura per Ferie dal " . $dtinizio . " al " . $dtfine;
            $testo = "<html><meta charset=\"ISO-8859-1\"><body>";
            $testo .= "Spett.le " . $nomecliente . "<br/>";
            $testo .= "  Vi informiamo che saremo chiusi per ferie dal " . $dtinizio . " fino al " . $dtfine . " incluso.";
            $testo .= "<br/><br/>Cordialmente, <br/>Cesari Pasticceria";
            $testo .= "</body></html>";
            $mail->Body = mb_convert_encoding($testo, "ISO-8859-1"); // questo funziona bene per le lettere accentate
            $mail->AltBody = mb_convert_encoding($testo, "ISO-8859-1"); // questo funziona bene per le lettere accentate
            // invio del messaggio

            if (!$mail->send()) {
                $errore = "Non riesco a spedire il messaggio: " . $mail->ErrorInfo;
                $errori .= $nomecliente . " ha dato problemi verso " . $emailcliente . " :: " . $errore . " || ";
                $contaerrori++;
            } else {
                $successo = 'Messaggio inviato';
            }
        }
    } catch (Exception $e) {
        $errore = "Non riesco a spedire il messaggio: {$mail->ErrorInfo}";
        $errori .= $nomecliente . " ha dato problemi verso " . $emailcliente . " :: " . $errore . " || ";
        $contaerrori++;
    }
}

mysqli_close($db);

// risponde con il numero di clienti a cui si è spedito il messagggio e quanti hanno dato errore e gli errori riscontrati
// in formato JSON
$data = '{"numclienti" : " ' . $contaclienti .'","numerrati" : "' . $contaerrori .'","errori" : " ' . $errori . '"}';
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
?>