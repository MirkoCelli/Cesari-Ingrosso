<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce la richeista di reset della password, viene assegnata una password a caso e viene inoltrata alla email del cliente
// la nuova password temporanea, l'utente finchè non avrà cambiato la password non potrà accedere: può mettere la password che vuole
// anche la stessa e anche banale se lo desidera (eventualmente in futuro rafforzeremo la sicurezza con dei criteri più stringenti)


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

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
// $serverpath1 = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
$serverpath1 = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/";
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

if (isset($_POST["giorno"])) {
    $giorno = $_POST["giorno"];
}

// se siamo qui l'utente ha richiesto il reset della password

// il reset della password lo registriamo anche nella tabella dei log

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

$nuovapwd = "";

for ($i = 1; $i < 6; $i++){
    $cifra = rand(0, 9);
    $nuovapwd .= $cifra;
}

// $sql = "UPDATE cp_login SET password = PASSWORD('" . $nuovapwd . "'), primavolta = 1 WHERE id = $indice "; // sostituita PASSWORD con PASSWORD2
$sql = "UPDATE cp_login SET password = PASSWORD2('" . $nuovapwd . "'), primavolta = 1 WHERE id = $indice "; // richiesto da MySQL 8.0 è una nostra FUNCTION che usa SHA2 da 384
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// mandare la email al cliente
$sql = "SELECT c.* ";
$sql .= "FROM ";
$sql .= "cp_login l ";
$sql .= "LEFT OUTER JOIN cp_intermediario c ON (c.id = l.codice) ";
$sql .= "WHERE ";
$sql .= "l.id = " . $indice; /* indice dell'account */

// eseguo il comando di query
$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

if ($row = mysqli_fetch_array($result)) {
    $nomecliente = $row["Denominazione"];
    $email = $row["email"];
}
mysqli_free_result($result);

// ora ho gli elementi per inviare le nuove credenziali temporanee al cliente

$testomail = "Gentile Intermediario $nomecliente con account utente $utente , <br/>hai richiesto il reset della password: " . $nuovapwd . " è la tua nuova password.<br/> Devi cambiarla subito al prossimo collegamento.";
$emailcliente = $email;
$successo = "";
$errore = "";

$mail = new PHPMailer(true);

try {
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // con questo leggiamo i messagi di comunicazione fra client e server
    // $mail->SMTPDebug = 0;
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
    $mail->Subject = "Richiesta di Reset Password " . date("d/m/Y", strtotime($giorno));
    $testo = "<html><meta charset=\"ISO-8859-1\"><body>";
    $testo .= $testomail;
    $testo .= "<br/><br/>";
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

} catch (Exception $e) {
    $errore = "Non riesco a spedire il messaggio: {$mail->ErrorInfo}";
}

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/resetpwd.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/resetpwd.js"></script>
    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript"> var percorso = "<?= $serverpath ?>"; var giorno = "<?= $giorno?>"; </script>
</head>
  <body>
     <div class="Contenuti">
       Vi abbiamo inviato una nuova password alla vostra casella E-Mail, da usare al prossimo collegamento come password temporanea.<br/> Subito dopo, dovrete cambiarla per i collegamenti successivi.<br />
         <a href="<?= $serverpath1 ?>login.php">Rientra nel portale</a>
    </div>
  </body>
</html>