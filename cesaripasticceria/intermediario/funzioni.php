<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert

// funzionalità di uso generale richiamabile dalle altre script con require __DIR__ . "/funzioni.php"
function redirect($url)
{
    header('Location: ' . $url);
    die();
}

// controllo se HTTPS o HTTP

function isSecure()
{
    return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

function ProtocolloHTTP()
{
    if (isSecure()) {
        return "https://";
    } else {
        return "http://";
    }
}

function PortHTTP($miaport)
{
    // se è https allora non deve essere 443
    // se è http allora non deve essere 80
    // per essere inserito nell'url esplicitamente
    if (isSecure()) {
            return "";        
    } else {
        if ($miaport != 80) {
            return ":" . $miaport;
        } else {
            return "";
        }
    }
}
// controlla se il token è valido e che non sia scaduto,

function abilitatoUtente($nome, $gettone, $scadenzagettone)
{
    // qui dovremo accedere al db per verificare le credenziali dell'utente e del token ricevuto dalle cookies e la sua scadenza
    // 2024-07-09 - leggere dal db lo stato per l'account
    // devo verificare che nome, gettone e scadenzagettone siano validi (scadenzatoken è 1 giorno dopo la data token)

    global $db;

    if (ApriDatabase()) {
        // devo aprire il database prima di eseguire
        $flgRisp = false;
        $sql = "SELECT * FROM cp_login WHERE account = '" . $nome . "' AND datafine IS NULL AND tipo = 2 "; // solo per gli intermediari
        $result = mysqli_query($db, $sql);
        if ($result) {
            if ($row1 = mysqli_fetch_assoc($result)) {
                $miotoken = $row1["token"];
                $miadatatoken = $row1["datatoken"];
                $miascadenza = gmdate("Y-m-d H:i:s", $scadenzagettone);
                // controlla che il token corrisponda
                if ( ($miotoken == $gettone) &&
                     (date('Y-m-d H:i:s', strtotime($miadatatoken . ' +1 day')) <= date('Y-m-d H:i:s', strtotime($miascadenza . ' +1 day'))) ) {
                    $flgRisp = true;
                }
            }
        }
        mysqli_free_result($result);
        ChiudiDatabase();
    }
    return $flgRisp;

    /*
    if ($nome == "robert") {
        return true;
    } else {
        return false;
    }
    */

}

function controllatoken1()
{
    // verifica se è presente la cookies altrimenti deve inviare l'utente alla pagina di login
    $cookie_name = "token";
    if (!isset($_COOKIE[$cookie_name])) {
        // echo "Cookie named '" . $cookie_name . "' is not set!";
        redirect("/cesaripasticceria/login.php");
    } else {
        // echo "Cookie '" . $cookie_name . "' is set!<br>";
        // echo "Value is: " . $_COOKIE[$cookie_name];
        // verificare se il token è scaduto
        $token = $_COOKIE[$cookie_name];
        $scadenza = $_COOKIE["expireToken"];
        $user = $_COOKIE["user"];
        // controllare che lo user sia abilitato
        if (!abilitatoUtente($user, $token, $scadenza)) {
            redirect("/cesaripasticceria/login.php");
        }
        if ($scadenza < time()) {
            // deve annullare il token precedente e generarne uno nuovo
            redirect("/cesaripasticceria/login.php");
        }
    }
}
function controllatoken(){
    // verifica se è presente la cookies altrimenti deve inviare l'utente alla pagina di login
    $cookie_name = "token";
    if (!isset($_COOKIE[$cookie_name])) {
        // echo "Cookie named '" . $cookie_name . "' is not set!";
        redirect("login.php");
    } else {
        // echo "Cookie '" . $cookie_name . "' is set!<br>";
        // echo "Value is: " . $_COOKIE[$cookie_name];
        // verificare se il token è scaduto
        $token = $_COOKIE[$cookie_name];
        $scadenza = $_COOKIE["expireToken"];
        $user = $_COOKIE["user"];
        // controllare che lo user sia abilitato
        if (!abilitatoUtente($user, $token, $scadenza)) {
            redirect("login.php");
        }
        if ($scadenza < time()) {
            // deve annullare il token precedente e generarne uno nuovo
            redirect("login.php");
        }
    }
}

function leggitoken(){
    global $db;
    $cookie_name = "token";
    $token = "";
    $user = "";
    $scadenza = null;
    $indice = null;
    //
    if (!isset($_COOKIE[$cookie_name])) {
        // redirect("login.php"); // se non ha la cookie allora lo manda sempre al Login
    } else {
        // echo "Cookie '" . $cookie_name . "' is set!<br>";
        // echo "Value is: " . $_COOKIE[$cookie_name];
        // verificare se il token è scaduto
        if (isset($_COOKIE[$cookie_name])) {
            $token = $_COOKIE[$cookie_name];
        }
        if (isset($_COOKIE["expireToken"])) {
            $scadenza = $_COOKIE["expireToken"];
        }
        if (isset($_COOKIE["user"])) {
            $user = $_COOKIE["user"];
        }
        if (isset($_COOKIE["indice"])) {
            $indice = $_COOKIE["indice"];
        }

        //
        // controllare che lo user sia abilitato
        if (!abilitatoUtente($user, $token, $scadenza)) {
            $valori = ["", "", -1];
        }
        if ($scadenza < time()) {
            // deve annullare il token precedente e generarne uno nuovo
        }
    }

    $valori = [$token, $user, $scadenza, $indice];
    return $valori;
}

function annullatoken(){
    $cookie_name = "token";
    $token = "";
    $user = "";
    $scadenza = null;
    //
    if (isset($_COOKIE[$cookie_name])) {
        // verificare se il token è scaduto
        $token = $_COOKIE[$cookie_name];
        $scadenza = $_COOKIE["expireToken"];
        $user = $_COOKIE["user"];
        setcookie("expireToken", -1);
    }
    $valori = [$token, $user, $scadenza];
    return $valori;
}

function encrypt_decrypt($action, $string)
{
    $output = false;
    $encrypt_method = "AES-256-CTR";
    $secret_key = 'R03erT%g45P3rOnI?54n-m4R1N0+U54m1'; // non devono essere visibili via url (32 bytes) - usare caratteri ASCII 7 bit per evitare problemi di calcolo
    $secret_iv = '0a1B2c3D4e5F6g7H'; // non devono essere visibili via url (16 bytes) - usare caratteri ASCII 7 bit per evitare problemi di calcolo
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

function CodificaTesto($testo){
    $risp = encrypt_decrypt("encrypt",$testo);
    if ($risp === false){
        return $testo;
    } else {
        return $risp;
    }
}

function DecodificaTesto($testo){
    $risp = encrypt_decrypt("decrypt", $testo);
    if ($risp === false) {
        return $testo;
    } else {
        return $risp;
    }
}

function sistemareSegreti(){
    // tutte le variabili in parametri.inc devono essere criptate per evitare hacking possibili tramite parametri.inc visto via url

    global $dbname;
    global $dbhost;
    global $dbuser;
    global $dbpwd;

    $pwdsgr = $dbpwd;
    $dbpwd = DecodificaTesto($pwdsgr);

    /*
    // solo per la configurazione iniziale
    $pwd = CodificaTesto($dbpwd);
    $pwd2 = DecodificaTesto($pwd);
    if ($dbpwd == $pwd2){
        echo "Password OK";
    } else {
        echo "Password KO";
    }
    */
}

function quotestr($testo){
    return str_replace("'", "''", $testo);
}

function ApriDatabase(){
    // apre il database con le credenziali
    global $dbname;
    global $dbhost;
    global $dbuser;
    global $dbpwd;
    global $db;

    $db = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
    if ($db === false) {
        return false;
    } else {
        return true;
    }
}

function ChiudiDatabase(){
    // chiude il database
    global $db;
    mysqli_close($db);
}

function VerificaToken($token,$indice,$adesso){
    // controlla che il token e indice siano gli ultimi registrati per l'utente altrimenti va in errore e fa rieseguire il login
    global $db;
    $flg = false;
    if (ApriDatabase()){
        $sql = "SELECT token, datatoken FROM cp_login WHERE id = " . $indice . " AND token = '" . $token . "' ";
        $query = mysqli_query($db, $sql);
        if ($row = mysqli_fetch_array($query)) {
            // il token deve corrispondere e la data del token deve essere quella di oggi (è qui che possiamo variare la durata del token)
            $flg = ($row["token"] == $token) && (date('Y-m-d',strtotime($row["datatoken"])) == date('Y-m-d',strtotime($adesso)));
        } else {
            // non ha token valido o indice utente è errato
            $flg = false;
        }
        ChiudiDatabase();
    } else {
        $flg = false;
    }
    return $flg;
}

?>