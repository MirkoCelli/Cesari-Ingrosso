<?php

// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il Login e fornisce le cookies per l'accesso ai servizi
// Attenzione: prima si verifica se l'account è di un intermediario, il quale ha la possibilità di gestire più clienti e quindi ci sarà una pagina
// di intermediazione dove vedrà l'elenco dei clienti da scegliere e poi li gestirà singolarmente
// se non è un intermediario è un cliente singolo e quindi va direttamente alla sua pagina di gestione
// Il pulsante Esci per gli intermediari fa tornare al menù dei clienti (e l'esci di questo menù fa uscire dall'applicazione)
// per i singoli clienti l'esci fa uscire direttamente dall'applicazione

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

$cartellaradice = $_SERVER["DOCUMENT_ROOT"]; // da questo posso ottenere i percorsi relativi dei files php?
$oggi = date('Y-m-d'); // data odierna
$adesso = date('Y-m-d H:i:s'); // orario corrente

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

// funzionalità di uso locale

 function GenToken($nome,$cred,$datascadenza){
     $token = rand(100000,999999);
     // registra per l'utente $nome questo token (modulo xxx) dove xxx è un segreto fra cliente e server
     $segreto = 592837; // questo è il segreto fra le due parti
     $tokenbase = $token % $segreto;
     $tokentrasmesso = $tokenbase + rand(5,200) * $segreto;
     // registrare nel record delle credenziali i valori $tokenbase, $segreto, $tokentrasmesso
     return $tokentrasmesso; // questo numero è il token che viene trasmesso
 }

 function FissaScadenza(){
    return time() + (86400 * 1); // concede 24 ore solamente di durata // 300; // solo 5 minuti di validità //
}

 $conta = 0;
 $errore = "";

// se riceve i campi di Login e Password allora deve procedere all'autenticazione altrimenti segnala l'errore. Sono ammessi al massimo tre tentativi,
// poi non viene più ammesso l'accesso al servizio a questa account senza il ripristino da parte degli operatori della Pasticceria Cesari
 if (isset($_POST["uname"])){
    //
    $conta = $_POST["conta"];
    if ($conta > 100){ // 2024-08-24 - da 3 a 10 tentativi, - 08/03/2025  da 10 a 100 , lo vorrebbero senza limite di tentativi, ma preferisco avere il limite per evitare attacchi hacker
      // questo utente ha superato il limite di tentativi, account bloccato (oppure ritardo di qualche minuto il prossimo tentativo?)
      redirect("espulso.php");
    } else {
      // verifichiamo sul DB che le credenziali siano valide e generiamo il token da mettere nelle cookies per questo utente
        $uname = $_POST["uname"];
        $password = $_POST["psw"];
        // 06/08/2024 - attenzione non può avere apici, ne virgolette ne doppio trattino (semplicemente li sostituisco con empty string
        $password = str_replace("'", "", $password);
        $password = str_replace("\"", "", $password);
        $password = str_replace("--", "", $password);
        // fine 06/08/2024
        if (ApriDatabase()){
            // $sql = "SELECT COUNT(*) AS conta, id AS indice, tipo as tipo, codice as codice, primavolta AS pv  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = PASSWORD('" . $password . "') AND "; // sostituire PASSWORD con PASSWORD2
            // $sql = "SELECT COUNT(*) AS conta, id AS indice, tipo as tipo, codice as codice, primavolta AS pv  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = PASSWORD2('" . $password . "') AND "; // richiesto in MySQL 8.0
            // 06/08/2024 - le password ora sono in chiaro e non si può fare il reset da parte dell'utente
            $sql = "SELECT COUNT(*) AS conta, id AS indice, tipo as tipo, codice as codice, primavolta AS pv  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = '" . $password . "' AND "; // richiesto in MySQL 8.0
            $sql .= " datainizio <= DATE('" . $oggi . "') AND (datafine IS NULL OR datafine >= DATE('" . $oggi . "') ) ";
            $query = mysqli_query($db, $sql);
            // while ($row = mysqli_fetch_array($query)) {
            if ($row = mysqli_fetch_array($query)) {
                if ($row["conta"] >= 1) {
                    // non può essere un operatore interno, segnalare subito l'errore
                    if ($row["tipo"] != 3) {

                        $scad = FissaScadenza();
                        $miotoken = GenToken($uname, $password, $scad);
                        setcookie("token", $miotoken, $scad, "/");
                        setcookie("expireToken", $scad, $scad, "/");
                        setcookie("user", $uname, $scad, "/");
                        // registro il token appena generato per le successive verifiche
                        $indice = $row["indice"];
                        // 17/06/2024 - devo sapere che tipo di utente corrisponde uname
                        $tipousr = $row["tipo"];
                        $codiceusr = $row["codice"];
                        setcookie("tipo", $tipousr);
                        setcookie("codice", $codiceusr);
                        //
                        setcookie("indice", $indice);

                        //
                        $sql = "UPDATE cp_login SET token = '" . quotestr($miotoken) . "', datatoken = '" . $adesso . "' WHERE id = " . $indice;
                        $query = mysqli_query($db, $sql);
                        if ($query === false) {
                            // c'è stato un errore
                            $errore = "Non sono riuscito a registrare il token";
                        }
                        // va alla pagina iniziale
                        // 02/07/2024 se è l aprima volta che entra allora deve cambiare le password e rifare il login
                        /* -- 06/08/2024 - non si può fare più la richiesta di reset e quindi primavolta viene ignorato
                        $primavolta = $row["pv"];
                        if ($primavolta == 1) {
                            redirect("cambiopwd.php");
                        }
                        */
                        // 16/07/2024 - in base alla tipologia dell'utente va a mainpage.php se è tipo = 1 per cliente,
                        // va a pagina mainintpage.php se è tipo = 2 per intermediario
                        // va a pagina mainpastpage.php se è tipo = 3 per operatore di pasticceria
                        switch ($tipousr) {
                            case 1: // cliente
                                redirect("mainpage.php");
                                break;
                            case 2: // intermediario
                                redirect("intermediario/mainpage.php");
                                break;
                            case 3: // operatore
                                // redirect("operatore/mainpage.php");
                                redirect("index.php"); // non può usare questa procedura un operatore, si richiede di nuovo il login
                                break;
                            default:
                                redirect("mainpage.php");
                        }
                    } else {
                        $errore = "Utente non abilitato per questo servizio!";
                    }
                } else {
                    $errore = "Username e/o Password errati";
                }
            } else {
                $errore = "Username e/o Password errati oppure utente disabilitato";
            }
            ChiudiDatabase();
        } else {
            $errore = "Servizio Autenticazione non attivo, riprovare più tardi o contattare assistenza tecnica.";
        }
    }
 }
?>
<html>
<head>
    <style>
    </style>
    <link rel="stylesheet" href="<?=$serverpath?>css/login.css" />
    <script src="<?=$serverpath?>js/cookies.js"></script>
</head>
<body onload="return CheckCookies(<?=$token?>);">
    <p>
        <center>
            <table>
                <tr>
                    <td align="center">
                        <img src="images/logocp.png" class="logocp" />
                    </td>
                </tr>
                <tr><td>
        <form action="<?=$serverpath?>login.php" method="post">
            <!--
    <div class="imgcontainer">
        <img src="img_avatar2.png" alt="Avatar" class="avatar" />
    </div>
    -->
            <div class="container">
                <input type="hidden" name="conta" value="<?=($conta+1)?>" />
                <table border="0">
                    <tr>
                        <td>
                            <label for="uname" class="titoli">
                                username
                            </label>
                        </td>
                        <td>
                            <input type="text" placeholder="" name="uname" class="edituser" value="<?=$utente?>" required />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="psw" class="titoli">
                                password
                            </label>
                        </td>
                        <td>
                            <input type="password" placeholder="" name="psw" class="editpws" required />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center">
                            <button type="submit" class="bottoni">ENTRA</button>
                        </td>
                        <!--
                    <td>
                        <button type="reset" class="cancelbtn">Pulisci</button>
                    </td>
                    -->
                    </tr>
                    <tr>
                        <td colspan="2">
                            <label for="errore" class="errori">
                                <?=$errore?>
                            </label>
                        </td>
                    </tr>
                    <!--
            <tr>
                <td>
                    <label>
                        <input type="checkbox" checked="checked" name="remember" />
                        Ricordami
                    </label>
                </td>
            </tr>
            -->
                </table>
            </div>
            <!--
    <div class="container" style="background-color:#f1f1f1">        
    </div>
    -->
        </form>
      </td></tr>
</table>
        <?php

        ?>
</body>


</html>