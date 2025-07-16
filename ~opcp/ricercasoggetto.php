<html>
<body>
    <table border="1">
        <tr>
            <td align="right">Codice Soggetto</td>
            <td align="left">Nominativo Soggetto</td>
            <td align="left">Nome Breve</td>
            <td align="left">Tipo Soggetto</td>
        </tr>

<?php
// (c) 2024-07-30 - Robert Gasperoni - Form per avere l'elenco dei soggetti di tiposoggetto che rispettano il criterio di ricerca nominale (anche parziale)

include("dbconfig.php");

// RIEPILOGO ORDINI PER LE BOLLE DI CONSEGNA
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);
$tiposoggetto = $_REQUEST["tiposoggetto"];
$nomesoggetto = $_REQUEST["nomesoggetto"];

// attenzione: devo evitare SQL Injection, pertanto vanno esclusi ', ", e -- perchè cono indicazioni che ci potrebbe essere una SQL Injection, li sostituisco con %
$nomesoggetto = str_replace("--", "%", $nomesoggetto); // i due trattini sono un trucco per fare commentare sezioni di query per la SQL Injection
$nomesoggetto = str_replace("'", "%", $nomesoggetto); // gli apici creano problemi nelle query
$nomesoggetto = str_replace("\"", "%", $nomesoggetto); // le virgolette creano problemi con i nomi dei campi di tabella

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");

// in base al tipo soggetto fa la corrispondente query
$sql = "";
$tiposogg = "";
switch($tiposoggetto){
    case 1: // CLIENTE
            $sql .= "SELECT id as codice, Denominazione as nomesoggetto, NomeBreve as nomebreve FROM cp_cliente WHERE Denominazione LIKE '%" . $nomesoggetto . "%' ";
            $sql .= " OR NomeBreve LIKE '%" . $nomesoggetto . "%' ";
            $sql .= "ORDER BY id ";
            $tiposogg = "CLIENTE";
            break;
    case 2: // INTERMEDIARIO
            $sql .= "SELECT id as codice, Denominazione as nomesoggetto, NULL as nomebreve FROM cp_intermediario WHERE Denominazione LIKE '%" . $nomesoggetto . "%' ORDER BY id ";
            $tiposogg = "INTERMEDIARIO";
            break;
    case 3: // RESPONSABILE (Operatore di pasticceria)
            $sql .= "SELECT id as codice, NomeCompletoResponsabile as nomesoggetto, NomeBreve as nomebreve FROM cp_responsabile WHERE NomeCompletoResponsabile LIKE '%" . $nomesoggetto . "%' ";
            $sql .= " OR NomeBreve LIKE '%" . $nomesoggetto . "%' ";
            $sql .= "ORDER BY id ";
            $tiposogg = "RESPONSABILE";
            break;
}

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// $row = mysqli_fetch_array($result,MYSQL_ASSOC);
while ($row = mysqli_fetch_array($result)) {
    $codice = $row["codice"];
    $nometizio = $row['nomesoggetto'];
    $nomebreve = $row["nomebreve"];
        ?>
        <tr>
            <td align="right">
                <?=$codice?>
            </td>
            <td align="left">
                <?=$nometizio?>
            </td>
            <td align="left">
                <?=$nomebreve?>
            </td>
            <td align="left">
                <?= $tiposogg ?>
            </td>
        </tr>
        <?php
};
mysqli_free_result($result);
mysqli_close($db);
?>
    </table>
</body>
</html>