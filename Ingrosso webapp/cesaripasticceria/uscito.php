<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il prospetto annuale dell'ordinativo del cliente

session_commit();

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();
// funzionalità di uno locale

annullatoken();

?>
<html>
<head>
    <style>
    </style>
    <link rel="stylesheet" href="css/uscito.css" />
    <script src="js/cookies.js"></script>
</head>
 <body>
     Arrivederci a presto. <a href="index.php">Rientra nel portale</a>
 </body>
</html>