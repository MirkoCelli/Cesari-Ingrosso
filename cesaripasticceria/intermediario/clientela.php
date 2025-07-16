<?php
// � 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina principale del servizio per i clienti all'ingrosso di Cesari Pasticceria
// Per gli operatori della Pasticceria si usa un'altra pagina di accesso e gestione servizi

session_start();
include "../include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();
$cartellaradice = $_SERVER["DOCUMENT_ROOT"]; // da questo posso ottenere i percorsi relativi dei files php?
// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$funzione = "";
$dominio = "";
$nomemese = "";
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
$indice = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token,$indice,$adesso)){
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}

// deve esistere sempre l'id cliente

$idcln = $_REQUEST["id"];

// devo cercare il nome del cliente
// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

// devo contare il numero di elementi (è sempre pari al numero di prodotto)

// devo determinare il codice del cliente dal idcln
$sql = "SELECT c.id as codice, c.NomeBreve as nomecliente ";
$sql .= "FROM cp_cliente c ";
$sql .= "WHERE c.id = " . $idcln;

$result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

while ($row = mysqli_fetch_array($result)) {
    $idcliente = $row["codice"];
    $nomecliente = $row["nomecliente"];
}

mysqli_free_result($result);

?>
<html>
<head>
    <link rel="stylesheet" href="<?=$serverpath?>css/mainpage.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/mainpage.js"></script>

</head>
<body>
    <!--Main Page-->
<div name="intestazione" class="Intestazioni"><span id="datetime"></span></div>
<div name="contenuto" class="Contenuti">
   <center>
       <table>
           <tr><td align="center"><?=$nomecliente?></td></tr>
           <tr>
               <td align="center" class="altreopzioni">
                   <a href="<?= $serverpath ?>schemadefault.php?id=<?= $idcln ?>" class="">Ordine base ripetitivo</a>
               </td>
           </tr>
           <tr>
               <td align="center" class="settimanale">
                   <a href="<?= $serverpath ?>settimanale.php?id=<?= $idcln ?>" class="">Ordine "usa e getta"</a>
               </td>
           </tr>
           <tr>
               <td align="center" class="altreopzioni">
                   <a href="<?= $serverpath ?>settimanalestorico.php?id=<?= $idcln ?>" class="">Storico Ordini</a>
               </td>
           </tr>
       </table>
   </center>
</div>
<div name="menubasso" class="MenuComandi">
    <center>
        <table>
            <tr>
                <td align="center" class="esci">
                    <a href="<?= $serverpath ?>mainpage.php" class="">Back</a>
                </td>
            </tr>
        </table>
    </center>
</div>
</body>
</html>