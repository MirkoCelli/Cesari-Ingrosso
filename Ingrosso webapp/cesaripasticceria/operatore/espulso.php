<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina di segnalazione espulsione dal servizio

session_abort();

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// voglio cancellare tutte le cookies generate da questo sito nel browser del client

// le devo fare spirare tutte
setcookie("token", "", -1);
setcookie("expireToken", "", -1);
setcookie("user", "", -1);
setcookie("indice", "", -1);

?>
<html>
 <head>
   <link rel="stylesheet" href="css/login.css" />
 </head>
 <body>
    <p>Siamo spiacenti troppi tentativi di accesso al servizio oppure non avete diritto di accesso al servizio Ordinativi.</p>
 </body>
</html>