<?php
// (c) 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina Ingrosso che rimanda alla sottocartella cesaripasticceria

// Per gli operatori della Pasticceria si usa un'altra pagina di accesso e gestione servizi
include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

redirect('/cesaripasticceria/index.php');

?>