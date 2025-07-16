<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce gli elementi modificati di una data giornata in ordine da parte del cliente

include "../include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/intermediario/"; // . $chperc;
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
// deve esistere sempre l'id cliente

$idcln = $_REQUEST["id"];

$giorno = date("Y-m-d"); // oggi

if (isset($_GET["giorno"])) {
    $giorno = $_GET["giorno"];
}

if (isset($_POST["giorno"])) {
    $giorno = $_POST["giorno"];
}

// devo caricarmi la tabella dei prodotti selezionabili con tutte le voci associate: codiceprodotto, descrizione prodotto, unità misura, prezzo unitario associato al cliente
// e devo tenere conto che in base all'unità di misura si gestiscono le quantità (Pz sono interi, Kg e cm sono decimali a 3 cifre decimali)


?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/aggiungidati.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/aggiungidati.js"></script>
    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript"> var percorso = "<?= $serverpath ?>"; var giorno = "<?= $giorno?>"; </script>
</head>
  <body>
     <div class="Contenuti">
      <table name="opzioni" id="opzioni" class="aggiungidati">
          <tr>
              <td>
                 Codice Prodotto
              </td>
              <td>
                  <select name="codiceprodotto" id="codiceprodotto" onchange="prodottoSelezionato($('#codiceprodotto').val())">
                      <option value="" selected></option>
                      <option value="AB001">CROISSANT ALLA MELA</option>
                      <option value="AB002">CROISSANT ALLA PERA</option>
                      <option value="AB003">CROISSANT VUOTO</option>
                      <option value="AB004">CROISSANT CON CREMA</option>
                  </select>
              </td>
              <td>
                  <button name="ritorna" id="ritorna" class="bottone" onclick="ritornaPaginaPrec(giorno);">Back</button>

              </td>
          </tr>
         </table>
         <table name="datinuovi" id="datinuovi" class="aggiungidati">
             <tr>
                 <td class="griglia codprod">
                     <label class="titoli">Codice Prodotto</label>
                 </td>
                 <td class="griglia unmis">
                     <label class="titoli">U.M.</label>
                 </td>
                 <td class="griglia quant">
                     <label class="titoli">Quantit&agrave;</label>
                 </td>
                 <td class="griglia desc">
                     <label class="titoli">Descrizione Prodotto</label>
                 </td>
                 <td class="griglia prezzo">
                     <label class="titoli">Prezzo Unitario</label>
                 </td>
                 <td class="griglia tot">
                     <label class="titoli">Totale</label>
                 </td>
                 <td class="griglia pulsante"></td>
             </tr>

             <tr>
                 <td class="griglia codprod">
                     <label id="codprod" name="codprod" class="dettagli">...</label>
                 </td>
                 <td class="griglia unmis">
                     <label id="unmis" name="unmis" class="dettagli">...</label>

                 </td>
                 <td class="griglia quant">
                     <input type="number" name="quantita" id="quantita" value="0" onchange="cambioQuantita();" />
                 </td>

                 <td class="griglia desc">
                     <label id="descrprod" name="descrprod" class="dettagli">...</label>

                 </td>

                 <td class="griglia prezzo">
                     <label id="prezzoprod" name="prezzoprod" class="dettagli">...</label>

                 </td>

                 <td class="griglia tot">
                     <label id="totprod" name="totprod" class="dettagli">...</label>

                 </td>

                 <td class="griglia pulsante">
                     <button id="add" name="add" value="Aggiungi" class="bottone" onclick="AggiungereOrdine()">Aggiungi</button>
                 </td>

             </tr>
             <tr>
                 <td colspan="7" class="griglia">
                     Campi per aggiungere dati extra all'ordine corrente del <?= $giorno ?>
                 </td>
             </tr>
         </table>
  
    </div>
  </body>
</html>