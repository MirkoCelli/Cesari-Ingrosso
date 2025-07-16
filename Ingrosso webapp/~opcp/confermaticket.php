<?php
// (c) 2024 - Robert Gasperoni - Procedura per la conferma del ticket da attribuire ad un ordine cliente e al responsabile richiedente
include("dbconfig.php");

// PRODUZIONE PRODOTTI - 2024-06-27
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$annocomp = $_REQUEST["annocomp"];
$idresp = $_REQUEST["responsabile"];
$idordine = $_REQUEST["ordine"];

$sqlMulti = "SET @statooper = -1;\n";
$sqlMulti .= "SET @biglietto = -100;\n";
$sqlMulti .= "SET @ilmessaggio = 'ee';\n";
$sqlMulti .= "CALL AssegnaTicket($annocomp,$idresp,$idordine,@statooper,@biglietto,@ilmessaggio);\n";
$sqlMulti .= "SELECT @statooper, @biglietto, @ilmessaggio;\n";

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno())
{
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
   exit; // fine dello script php
}

if (!mysqli_select_db($db,$database)){
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to open database in MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

//  eseguo qui la query multipla

$statooper = null;
$ticket = null;
$msq = null;

mysqli_multi_query($db, $sqlMulti);
do {
    if ($result = mysqli_store_result($db)){
        if ($row = mysqli_fetch_row($result)){
            $statooper = (int)$row[0];
            $ticket = (int)$row[1];
            $msg = $row[2];
        }
    }
} while (mysqli_next_result($db));

// se ci sono stati problemi lo statooper sarà a zero o negativo o null
if ($statooper == null || $statooper <= 0){
    // ci sono stati dei problemi segnalare la presenza di un errore nella risposta
    header("Content-type: application/json");
    echo "{\"status\" : \"KO\" , \"statooper\" : \"" . $statooper . "\" , \"ticket\" : \"" . $ticket . "\" , \"msg\" : \"" . $msg . "\" , \"error\" : \"Non è stato possibile ricevere un ticket per l'ordine $idordine \"}";
} else {
    header("Content-type: application/json");
    echo "{\"status\" : \"OK\" , \"statooper\" : \"" . $statooper . "\" , \"ticket\" : \"" . $ticket . "\" , \"msg\" : \"" . $msg . "\" , \"error\" : \"\"}";
}

?>