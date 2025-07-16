<?php
// � 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina principale del servizio per i clienti all'ingrosso di Cesari Pasticceria
// Per gli operatori della Pasticceria si usa un'altra pagina di accesso e gestione servizi
// Sezione dedicata agli Intermediari - Qui vengono elencati tutti i clienti associati all'intermediario che può scegliere singolarmente (ultima voce è l'Esci)

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
           <?php
  // qui elenchiamo tutti i clienti associati all'intermediario in ordine alfabetico (o di sequenza?)
  if (ApriDatabase()){
      $sql = "SELECT c.*, i.Denominazione as nomeintermediario FROM cp_cliente c, cp_intermediario i, cp_login l WHERE c.intermediario = i.id AND i.id = l.codice AND l.tipo = 2 AND  l.id = " . $indice; 
      $result = mysqli_query($db,$sql);
      if (!$result){
          // probleimi con la query
          echo "<tr><td>Problemi con la connessione al Database, riprovare pi&ugrave tardi</td></tr>";
      } else {
          while ($row = mysqli_fetch_assoc($result)){
              $idcln = $row["id"];
              $codcln = $row["CodiceCliente"];
              $nomecln = $row["Denominazione"];
              $nombebrv = $row["NomeBreve"];
              $sequenza = $row["sequenza"];
              $nomeintermediario = $row["nomeintermediario"];
?>
           <tr>
              <td align="center" class="settimanale">
                  <a href="<?= $serverpath ?>clientela.php?id=<?=$idcln?>" class="clientela"><?=$codcln?> - <?=$nomecln?></a>
              </td>
           </tr>
<?php
          }
      }
  }
?>
         <tr><td align="center">Clientela di <b><?=$nomeintermediario?></b></td></tr>
           <tr>
             <td align="center" class="mensile">
                <!--
                <a href="<?= $serverpath ?>resetpwd.php" class="" onclick="return confermaCambioPassword();">Reset Password</a>
                -->
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
                    <a href="<?= $serverpath ?>uscito.php" class="">Esci</a>
                </td>
            </tr>
        </table>
    </center>
</div>
</body>
</html>