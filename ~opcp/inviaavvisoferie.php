<?php
  // 2024-10-15 - inviare un avviso di chiusura per ferie a tutti i clienti che hanno una email valida
  // il testo della email è prefissato in questo script, le date di riferimento sono indicate nell'url
include("dbconfig.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$datainizio = null;
$datafine = null;

if (isset($_REQUEST["datainizio"])){
    $datainizio = $_REQUEST["datainizio"];
}

if (isset($_REQUEST["datafine"])) {
    $datafine = $_REQUEST["datafine"];
}

if (isset($datainizio))
{
    $inizio = explode("-", $datainizio);
    $giornoinizio = $inizio[2] . "/" . $inizio[1] . "/" . $inizio[0];
}

if (isset($datafine)) {
    $fine = explode("-", $datafine);
    $giornofine = $fine[2] . "/" . $fine[1] . "/" . $fine[0];
}

// ora preparo il soggetto e il testo del messaggio

$oggettomsg = "AVVISO: Chiusura per Ferie della Cesari Pasticceria";

// echo $oggettomsg . "\n\n\n" . $corpomsg;
$nomecliente = "Robert Gasperoni";
$emailcliente = "robert@inthenet.sm";

$successo = "";
$errore = "";



// faccio una interrgoazione di tutti i clienti che hanno una email per spedirgli una comunicazione di chiusura per ferie
// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");

$sql = "SELECT * FROM cp_cliente WHERE email IS NOT NULL AND email <> '' "; // considero anche i clienti disabilitati
$result = mysqli_query( $db, $sql ) or die("Couldn t execute query.".mysqli_error($db));
if (!$result){
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
   echo("Error description: " . mysqli_error($db));
   exit; // fine dello script php
}
while ($row = mysqli_fetch_array($result)) {
    // qui devo calcolare i singoli totale_b e totale_n
    $nomecliente = $row["Denominazione"];
    $emailcliente = $row["email"];
    //
    $mail = new PHPMailer(true);
    try {
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // con questo leggiamo i messagi di comunicazione fra client e server
        // $mail->SMTPDebug = 0;
        /*
        // solo per Debug - 2024-07-26
        $mail->isSMTP();
        $mail->Host = 'out.alice.sm';
        $mail->SMTPAuth = true;
        $mail->Username = 'inthenet@omniway.sm';
        $mail->Password = 'cap30968';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('inthenet@omniway.sm');
        */

        if ($emailcliente != "") {

            
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
            $mail->Subject = $oggettomsg; //  "Vostro Ordine per il giorno " . date("d/m/Y", strtotime($giornata));

            $testo = "<html><meta charset=\"ISO-8859-1\"><body>";
            $testo .= "Spett.le " . $nomecliente . "<br/>";
            $testo .= "&nbsp;&nbsp; con la presente siamo ad informarVi che la Cesari Pasticceria rester&agrave;  chiusa per ferie dal " . $giornoinizio . " fino al " . $giornofine . ".";
            $testo .= "<br/>";
            $testo .= "Durante il periodo di chiusura non verranno effettuate consegne e ogni ordine che avete programmato in questo periodo verr&agrave; annullato.";
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
    } catch (Exception $e) {
        $errore .= "Non riesco a spedire il messaggio: {$mail->ErrorInfo}\n";
    }
}

mysqli_free_result($result);
mysqli_close($db);

echo '{"status":"ok","errore":"' . $errore . '"}';
?>