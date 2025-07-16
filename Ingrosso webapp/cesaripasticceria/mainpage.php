<?php
// � 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina principale del servizio per i clienti all'ingrosso di Cesari Pasticceria
// Per gli operatori della Pasticceria si usa un'altra pagina di accesso e gestione servizi

// session_start();
include "include/parametri.inc";
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

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . PortHTTP($_SERVER["SERVER_PORT"]) . "/cesaripasticceria/"; // . $chperc;
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

// 2024-10-14 - devo stabilire lo stato del cliente

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

$sql = "SELECT c.* FROM cp_cliente c, cp_login l WHERE l.id = " . $indice . " AND l.codice = c.id AND l.tipo = 1 "; // tipo = cliente

$result = mysqli_query($db, $sql);

$idcliente = 0;
$abilitato = 0;

if ($row = mysqli_fetch_assoc($result)) {
    $abilitato = $row["abilitato"];
    $idcliente = $row["id"];
} else {
    // ci sono dei problemi con il contratto
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error() . " - Credenziali non valide come utenza", true, 500);
    echo "Credenziali non valide come utenza. <a href='" . $serverpath . "/login.php'>Rientra</a>";
    exit; // fine dello script php
}

mysqli_free_result($result);

mysqli_close($db);

?>
<html>
<head>
    <link rel="stylesheet" href="<?=$serverpath?>css/mainpage.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/mainpage.js"></script>
    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript">        var percorso = "<?= $serverpath ?>";</script>
</head>
<body>
    <!--Main Page-->
<div name="intestazione" class="Intestazioni">
    <span id="date" class="cdate"></span> <span id="time" class="ctime"></span><font size="16" color="#e8f6fd">.</font><br />
    <span id="nota">
        <font class="nota" color="red">TERMINE ore 12:00<br/>invio ordine giornaliero<br/></font>
    </span>
    
</div>
<div name="contenuto" class="Contenuti">
   <center>
       <table>
           <tr>
               <td align="center" class="annuale">
                   <a href="<?= $serverpath ?>schemadefault.php" class="" onclick="mostraLoader(); return true;">LISTA ORDINI SETTIMANALE</a>


               </td>
           </tr>
           <tr>
               <td align="center" class="settimanale">
                   <a href="<?= $serverpath ?>settimanale.php" class="" onclick="mostraLoader(); return true;">MODIFICA ORDINE SINGOLO</a>



               </td>
           </tr>
           <!--
           <tr>
               <td align="center" class="giornaliero">
                   <a href="<?= $serverpath ?>giornaliero.php" class="">Giornaliero</a>
               </td>
           </tr>
           -->
           <tr>
               <td align="center" class="settimanale">
                   <a href="<?= $serverpath ?>settimanalestorico.php" class="" onclick="mostraLoader(); return true;">STORICO ORDINI</a>


               </td>
           </tr>
<?php
  // 2024-10-14 - se è abilitato il pulsante permette di Disabilitarlo oppure se non è abilitato permette di Abilitarlo
  if ($abilitato == 0){
?>
               <tr>
                   <td class="cursore">
                       <!-- Rounded switch -->
                       <table class="tab_cursore">
                           <tr>
                               <td class="cursore">
                                   <label class="switch" onclick="cambioStatoCliente(<?= $idcliente ?> , 1)">
                                   <input type="checkbox" />
                                   <span class="slider round"></span>
                               </label>

                           </td>

                           <td class="testo_cursore">
                               <span>ATTIVA ORDINI SETTIMANALI</span>
                           </td>

                       </tr>
                   </table>

               </td>
           </tr>

               <tr>
                   <td align="center" class="settimanale2 rosso">
                       <!--<a href="javascript: void(0)" onclick="cambioStatoCliente(<?= $idcliente ?> , 1)" class="">-->
                       <label class="rosso">utente non attivo</label>
                           <!--</a>-->
                   </td>
           </tr>
<?php           
  } else {
?>
                   <tr>
                       <td class="cursore">
                           <!-- Rounded switch -->
                           <table class="tab_cursore">
                               <tr>
                                   <td class="cursore">
                                       <label class="switch" onclick="cambioStatoCliente(<?= $idcliente ?> , 0)">
                                       <input type="checkbox" checked />

                                       <span class="slider round"></span>
                                   </label>

                           </td>

                           <td class="testo_cursore">
                               <span>ATTIVA ORDINI SETTIMANALI</span>
                           </td>

                       </tr>
                   </table>

               </td>
           </tr>

           <tr>
               <td align="center" class="settimanale2 verde">
                   <!-- <a href="javascript: void(0)" onclick="cambioStatoCliente(<?=$idcliente?> , 0)" class="">-->
                   <label class="verde">utente attivo</label>
                   <!--</a>-->



               </td>
           </tr>
<?php           
  }
?>
           <tr>
               
           </tr>
       </table>
   </center>
</div>
<div name="menubasso" class="MenuComandi">
    <center>
        <table>
            <tr>
                <td align="center" class="esci">
                    <a href="<?= $serverpath ?>uscito.php" class="">ESCI</a>
                </td>

                <!--
                <td align="center" class="altreopzioni">
                    <a href="<?= $serverpath ?>altreopzioni.php" class="">Reset Password</a>
                </td>
                -->
            </tr>
        </table>
    </center>
</div>
<div class="page_loader">
</div>
</body>
</html>