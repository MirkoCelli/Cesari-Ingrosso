<?php

// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che permette di cambiare la password, al momento può essere arbitraria, basta che non sia vuota
// ma predisponiamo per un controllo di ammissibilità futura

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

function ammissibilePwd($testo){
    // non può essere vuota ne essere più corta di 3 lettere, non può contenere * come primo carattere (regola fissata da noi),
    // non deve contenere ' o " perchè potrebbero creare problemi con le query
    $flg = true;
    // vuota o meno di 3 caratteri
    if (strlen($testo) < 3){
        $flg = false;
    }
    // la prima lettera non può essere *
    if (substr($testo,0,1) == "*"){
        $flg = false;
    }
    // non può contenere ' o "
    if (strpos($testo,"'") || strpos($testo,"\"")){
        $flg = false;
    }
    return $flg;
}

$errore = "";
$primapwd = "";
$secondapwd = "";
if (isset($_POST["errore"])){
    $errore = $_POST["errore"];
}
if (isset($_POST["primapwd"])){
    $primapwd = $_POST["primapwd"];
}
if (isset($_POST["secondapwd"])){
    $secondapwd = $_POST["secondapwd"];
}

if (isset($primapwd)) {
    // ha inserito le password allora devono essere uguali primapwd e secondapwd per poterla confermare negli archivi
    if ($primapwd == $secondapwd) {
        // sono identiche, verifico se sono ammissibili
        if (ammissibilePwd($primapwd)) {
            // può registrare la nuova password, togliere il primavolta e fare andare al login.php
            if (ApriDatabase()) {
               // $sql = "UPDATE cp_login SET password = PASSWORD('" . $primapwd . "'), primavolta = 0 WHERE id = $indice "; // sostituite PASSWORD con PASSWORD2
                $sql = "UPDATE cp_login SET password = PASSWORD2('" . $primapwd . "'), primavolta = 0 WHERE id = $indice "; // richiesta da MySQL 8.0
               $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
               if (!$result) {
                  header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                  $errore = "Problemi aggiornamento credenziali: Error description: " . mysqli_error($db);
               } else {
                  redirect("login.php");
               }
            }
        } else {
            $errore = "Password non ammissibile, fornire una password diversa: almeno 3 lettere, non può avere apice oppure virgolette, non può iniziare con asterisco!";
        }
    } else {
        $errore = "Le password indicate non sono uguali, riprovare!";
    }
}
?>
<html>
<head>
    <style>
    </style>
    <link rel="stylesheet" href="<?=$serverpath?>css/cambiopwd.css" />
    <script src="<?=$serverpath?>js/cambiopwd.js"></script>
    <script src="<?=$serverpath?>js/cookies.js"></script>
</head>
<body onload="return CheckCookies(<?=$token?>);">
    <form action="<?=$serverpath?>cambiopwd.php" method="post">
        <div class="container">            
            <table border="0">
                <tr>
                    <td>
                        <label for="primapwd" class="titoli">
                            <b>Fornire nuova password: </b>
                        </label>
                    </td>
                    <td>
                        <input type="password" placeholder="Fornire nuova password" name="primapwd" class="edituser" required />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="secondapsw" class="titoli">
                            <b>Confermare nuova pawword:</b>
                        </label>
                    </td>
                    <td>
                        <input type="password" placeholder="Confermare nuova password" name="secondapwd" class="editpws" required />
                    </td>
                </tr>
                <tr>
                    <td>
                        <button type="submit" class="bottoni">Registra</button>
                    </td>
                    <td>
                        <button type="reset" class="cancelbtn">Pulisci</button>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="errore" class="errori">
                            <?=$errore?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </form>
</body>
</html>