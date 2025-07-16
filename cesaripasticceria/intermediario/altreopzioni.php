<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il Login e fornisce le cookies per l'accesso ai servizi

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
$indice = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token, $indice, $adesso)) {
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}

$idcln = $_REQUEST["id"];

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/altreopzioni.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/altreopzioni.js"></script>

</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <span id="datetime"></span>
    </div>
    <div name="contenuto" class="Contenuti">
        <center>
            <table>
                <tr>
                    <td align="center" class="annuale">
                        <a href="<?= $serverpath ?>schemadefault.php?id=<?=$idcln?>" class="">Schemi Ordinativi</a>
                    </td>
                </tr>                
            </table>
        </center>
    </div>
    <div name="menubasso" class="MenuComandi">
        <a href="<?= $serverpath ?>clientela.php?id=<?=$idcln?>">Back</a>
    </div>
</body>
</html>
