<?php
// 2024-08-07 - Robert Gasperoni
// RIEPILOGO GIORNALIERO CLIENTELA
// ora ci servono gli elementi per generare la query
include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024


$giorno = date("d/m/Y"); // default la giornata odierna (formato italiano da convertire in ISO

$inizio = null;
$fine = null;

function NomeMese($ind){
    $nome = "";
    switch($ind){
        case 1:
            $nome = "GENNAIO";
            break;
        case 2:
            $nome = "FEBBRAIO";
            break;
        case 3:
            $nome = "MARZO";
            break;
        case 4:
            $nome = "APRILE";
            break;
        case 5:
            $nome = "MAGGIO";
            break;
        case 6:
            $nome = "GIUGNO";
            break;
        case 7:
            $nome = "LUGLIO";
            break;
        case 8:
            $nome = "AGOSTO";
            break;
        case 9:
            $nome = "SETTEMBRE";
            break;
        case 10:
            $nome = "OTTOBRE";
            break;
        case 11:
            $nome = "NOVEMBRE";
            break;
        case 12:
            $nome = "DICEMBRE";
            break;
    }
    return $nome;
}

if (isset($_REQUEST["inizio"])) {
    $inizio = $_REQUEST["inizio"];
    $giornata = new DateTime($inizio);
    $dtinizio = $giornata->format("d/m/Y");
    // mi serviranno per il nome del mese e l'anno di riferimento
    $dd = $giornata->format("d");
    $mm = $giornata->format("m");
    $nm = NomeMese($mm);
    $yyyy = $giornata->format("Y");
}

if (isset($_REQUEST["fine"])) {
    $fine = $_REQUEST["fine"];
    $giornata = new DateTime($fine);
    $dtfine = $giornata->format("d/m/Y");
}

// determino l'anno e il mese (prendo la data inizio come riferimento

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$oggi = date("Y-m-d");
$giorno1 = $oggi;

$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}
// aperto il database
// devo farmi dare il nome del cliente
$sql = "SELECT c.id AS id, c.Denominazione AS nomecliente, c.sequenza, ";
$sql .= "SUM(((db.b % 123456) / 1000)) AS qta_b,";
$sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n,";
$sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
$sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n,";
$sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
// 2024-08-15
$sql .= ", ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * ( 1 + c.perc_iva / 100.0),2) AS tot_ivato, ";
$sql .= "ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * ( 1 + c.perc_iva / 100.0),2) + SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_compl ";
// fine 2024-08-15
$sql .= "FROM cp_cliente c, cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
$sql .= "WHERE bc.cliente = c.id AND ";
$sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') ";
$sql .= "AND DATE('" . $fine . "') ";
$sql .= "GROUP BY c.id ";
$sql .= "ORDER BY c.sequenza ";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// in $s mettiamo il testo del foglio CSV

$s = "\"Riepilogo Clientela $nm del periodo dal $dtinizio al $dtfine\";;;;;;\n";
$s .= '"Nominativo cliente";"Qt B";"Qt N";"Tot. B";"Tot.Ivato";"Tot. N";"Tot.Compl.";';
$s .= "\n";

//
        $sommaqta_b = 0;
        $sommaqta_n = 0;
        $sommaqta_cln = 0;
        $sommatot_b = 0.00;
        $sommatot_n = 0.00;
        $sommatot_cln = 0.00;
        // 2024-08-15
        $sommatot_ivato = 0.00;
        $sommatot_comp = 0.00;

        while ($row = mysqli_fetch_array($result)) {
            $idcln = $row["id"];
            $nomecliente = $row["nomecliente"];
            $seq = $row["sequenza"];
            $datacons = $row["dataconsegna"];
            //
            $qta_b = $row["qta_b"];
            $qta_n = $row["qta_n"];
            //
            $tot_b = $row["tot_b"];
            $tot_n = $row["tot_n"];
            //
            $tot_cln = $row["totale_cliente"];
            // 2024-08-15
            $tot_ivato = $row["tot_ivato"];
            $tot_compl = $row["tot_compl"];
            // fine 2024-08-15
            //
            $sommaqta_b += $qta_b;
            $sommaqta_n += $qta_n;
            $sommaqta_cln += $qta_b + $qta_n;
            //
            $sommatot_b += $tot_b;
            $sommatot_n += $tot_n;
            $sommatot_cln += $tot_cln;
            // 2024-08-15
            $sommatot_ivato += $tot_ivato;
            $sommatot_comp += $tot_compl;

  $s .= '"' . $nomecliente . '";';
  $s .= '"' . number_format((float) $qta_b, 0, ',', '') . '";';
  $s .= '"' . number_format((float) $qta_n, 0, ',', '') . '";';
  $s .= '"' . number_format((float) $tot_b, 2, ',', '') . '";';
  $s .= '"' . number_format((float) $tot_ivato, 2, ',', '') . '";';
  $s .= '"' . number_format((float) $tot_n, 2, ',', '') . '";';
  $s .= '"' . number_format((float) $tot_compl, 2, ',', '') . '";';
  $s .= "\n";

        }

        mysqli_free_result($result);

        // ora aggiungiamo la riga finale con i totali
   $s .= ";;;;;;;\n";

   $s .= '"TOTALI";';
   $s .= '"' . number_format((float)$sommaqta_b, 0, ',', '') . '";';
   $s .= '"' . number_format((float) $sommaqta_n, 0, ',', '') . '";';
   $s .= '"' . number_format((float) $sommatot_b, 2, ',', '') . '";';
   $s .= '"' . number_format((float) $sommatot_ivato, 2, ',', '') . '";';
   $s .= '"' . number_format((float) $sommatot_n, 2, ',', '') . '";';
   $s .= '"' . number_format((float) $sommatot_comp, 2, ',', '') . '";';
   $s .= "\n";

   $s .= ';"' . $sommaqta_cln . '";;;;;;';
   $s .= "\n";

// qui generiamo il file CSV i campi sono separati da ; e i valori racchiusi fra virgolette
// vedere: https://stackoverflow.com/questions/4348802/how-can-i-output-a-utf-8-csv-in-php-that-excel-will-read-properly

header("Content-type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=riepilogomensileclientela_" . $nm . "_" . $yyyy . ".csv");
header("Pragma: no-cache");
header("Expires: 0");
//
echo "\xEF\xBB\xBF"; // UTF-8 BOM
echo $s;

?>