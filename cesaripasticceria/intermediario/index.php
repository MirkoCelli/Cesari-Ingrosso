<?php

// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// questa pagina controlla che l'utente abbia un token e che questo sia valido, altrimenti manda alla pagina di login, se va bene allora manda alla pagina
// di gestione operazioni (entrambe con delle redirect alle corrispondenti pagine Php)

include "include/parametri.inc";

require __DIR__ . "/funzioni.php";

sistemareSegreti();

// funzionalità di uso locale


// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$cartellaradice = $_SERVER["DOCUMENT_ROOT"]; // da questo posso ottenere i percorsi relativi dei files php?
$percorso = $_SERVER['REQUEST_URI'];
$chiamante = $_SERVER["SCRIPT_NAME"];
// il path relativo lo ottengo da questo link: /path/script.php
$chpath = explode('/', $chiamante);
$chserver = $chpath[0];
$chperc = "";
$i = 1;
while (strpos($chpath[$i],'.php') === false){
    $chperc .=  $chpath[$i] . "/" ;
    $i++;
}
$chscript = $chpath[$i];

//
$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$funzione = "";
$dominio = "";
$nomemese = "";
$pathbase = $elementi[1];

$token = null;
$user = null;
$scadenza = null;
$indice = null;

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/"; // . $chperc;
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/intermediario/";
/*
if ($elementi[0] != "") {
    $serverpath .= $elementi[0] . "/";
}
*/
?>
<html>
<body>
    <?php
// verifica se è presente la cookies altrimenti deve inviare l'utente alla pagina di login
    controllatoken();
    redirect("mainpage.php");
/*
$cookie_name = "token";
if (!isset($_COOKIE[$cookie_name])) {
    // echo "Cookie named '" . $cookie_name . "' is not set!";
    redirect("login.php");
} else {
    // echo "Cookie '" . $cookie_name . "' is set!<br>";
    // echo "Value is: " . $_COOKIE[$cookie_name];
    // verificare se il token è scaduto
    $scadenza = $_COOKIE["expireToken"];
    $user = $_COOKIE["user"];
    // controllare che lo user sia abilitato
    if (! abilitatoUtente($user,$token,$scadenza)){
       redirect("login.php");
    }
    if ($scadenza < time()){
      // deve annullare il token precedente e generarne uno nuovo
      redirect("login.php");
    }
    redirect("mainpage.php");
}
*/
    ?>
</body>
</html>
