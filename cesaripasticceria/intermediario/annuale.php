<?php
// � 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il prospetto annuale dell'ordinativo del cliente

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();
// funzionalit� di uno locale

function NomeDelMese($nummese){
    $risp = "";
    switch($nummese){
        case 1:
            $risp = "GENNAIO";
            break;
        case 2:
            $risp = "FEBBRAIO";
            break;
        case 3:
            $risp = "MARZO";
            break;
        case 4:
            $risp = "APRILE";
            break;
        case 5:
            $risp = "MAGGIO";
            break;
        case 6:
            $risp = "GIUGNO";
            break;
        case 7:
            $risp = "LUGLIO";
            break;
        case 8:
            $risp = "AGOSTO";
            break;
        case 9:
            $risp = "SETTEMBRE";
            break;
        case 10:
            $risp = "OTTOBRE";
            break;
        case 11:
            $risp = "NOVEMBRE";
            break;
        case 12:
            $risp = "DICEMBRE";
            break;
    }
    return $risp;
}

// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/',$percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$funzione = "";
$dominio = "";
$nomemese = "";
$pathbase = $elementi[1];

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
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

if (!VerificaToken($token, $indice, $adesso)) {
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}


$anno = date('Y', strtotime(date("Y-m-d")));
/*
if (count($elementi) > 2){
    $dominio = $elementi[2];
    $funzione = $elementi[3];
    $mese = $elementi[4];
    $nomemese = strtoupper($mese);
    $anno = $elementi[5];
}
*/

if (isset($_POST["annocorrente"])){
    $anno = $_POST["annocorrente"];
}

// per trasformare i link da GET a POST si può vedere una idea in https://stackoverflow.com/questions/8398726/using-the-post-method-with-html-anchor-tags
// si usa la Form ma il link fa partire un Javascript che avvia il submit della form

?>
<html>
<head>
    <link rel="stylesheet" href="css/annuale.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="js/annuale.js"></script>
    <script src="js/jquery-3.7.1.min.js"></script>

</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <form method="post" action="<?= $serverpath ?>annuale.php">
            <span id="mese">
                <?=$nomemese?>
            </span>
            <span id="anno">
                <input name="annocorrente" type="number" min="2024" max="2099" value="<?=$anno?>" required class="spin" />
                <input type="submit" name="invia" value="Cambia Anno" class="bottone"/>

            </span>
        </form>
</div>
    <div name="contenuto" class="Contenuti">
        <center>
            <table>
                <tr>
                    <td align="center" class="gennaio">
                        <a href="<?= $serverpath ?>mensile.php?mese=1&anno=<?= $anno ?>" class="">GENNAIO</a>


                    </td>

                    <td align="center" class="febbraio">
                        <a href="<?= $serverpath ?>mensile.php?mese=2&anno=<?= $anno ?>" class="">FEBBRAIO</a>





                    </td>

                    <td align="center" class="marzo">
                        <a href="<?= $serverpath ?>mensile.php?mese=3&anno=<?= $anno ?>" class="">MARZO</a>




                    </td>

                    <td align="center" class="aprile">
                        <a href="<?= $serverpath ?>mensile.php?mese=4&anno=<?= $anno ?>" class="">APRILE</a>




                    </td>
                </tr>
                <tr>
                    <td align="center" class="maggio">
                        <a href="<?= $serverpath ?>mensile.php?mese=5&anno=<?= $anno ?>" class="">MAGGIO</a>




                    </td>

                    <td align="center" class="giugno">
                        <a href="<?= $serverpath ?>mensile.php?mese=6&anno=<?= $anno ?>" class="">GIUGNO</a>




                    </td>

                    <td align="center" class="luglio">
                        <a href="<?= $serverpath ?>mensile.php?mese=7&anno=<?= $anno ?>" class="">LUGLIO</a>




                    </td>

                    <td align="center" class="agosto">
                        <a href="<?= $serverpath ?>mensile.php?mese=8&anno=<?= $anno ?>" class="">AGOSTO</a>




                    </td>
                </tr>
                <tr>
                    <td align="center" class="settembre">
                        <a href="<?= $serverpath ?>mensile.php?mese=9&anno=<?= $anno ?>" class="">SETTEMBRE</a>




                    </td>

                    <td align="center" class="ottobre">
                        <a href="<?= $serverpath ?>mensile.php?mese=10&anno=<?= $anno ?>" class="">OTTOBRE</a>




                    </td>

                    <td align="center" class="novembre">
                        <a href="<?= $serverpath ?>mensile.php?mese=11&anno=<?= $anno ?>" class="">NOVEMBRE</a>




                    </td>

                    <td align="center" class="dicembre">
                        <a href="<?= $serverpath ?>mensile.php?mese=12&anno=<?= $anno ?>" class="">DICEMBRE</a>




                    </td>
                </tr>
            </table>

        </center>
    </div>
    <div name="menubasso" class="MenuComandi"><a href="<?= $serverpath ?>mainpage.php">Back</a></div>
</body>
</html>
