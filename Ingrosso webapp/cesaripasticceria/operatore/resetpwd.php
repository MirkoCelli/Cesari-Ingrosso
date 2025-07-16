<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce la richeista di reset della password, viene assegnata una password a caso e viene inoltrata alla email del cliente
// la nuova password temporanea, l'utente finchè non avrà cambiato la password non potrà accedere: può mettere la password che vuole
// anche la stessa e anche banale se lo desidera (eventualmente in futuro rafforzeremo la sicurezza con dei criteri più stringenti)

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

$serverpath = "http://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
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

// $sql = "UPDATE cp_login SET password = PASSWORD('" . $nuovapwd . "'), primavolta = 1 WHERE id = $indice "; // sostituire PASSWORD con PASSWORD2
$sql = "UPDATE cp_login SET password = PASSWORD2('" . $nuovapwd . "'), primavolta = 1 WHERE id = $indice "; // richiesto in MySQL 8.0
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
$sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice) ";
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

$testomail = "Gentile cliente $nomecliente con account utente $utente ,hai richiesto il reset della password: " . $nuovapwd . " è la tua nuova password. Devi cambiarla subito al prossimo collegamento.";

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/restepwd.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/resetpwd.js"></script>
    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript"> var percorso = "<?= $serverpath ?>"; var giorno = "<?= $giorno?>"; </script>
</head>
  <body>
     <div class="Contenuti">
<?= $testomail ?>  
    </div>
  </body>
</html>