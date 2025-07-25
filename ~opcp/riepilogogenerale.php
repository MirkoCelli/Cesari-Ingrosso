<?php
  // 2024-08-07 - Robert Gasperoni
  // RIEPILOGO GENERALE DEL PERIODO
// ora ci servono gli elementi per generare la query
include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

$inizio = null;
$fine = null;

if (isset($_REQUEST["inizio"])) {
    $inizio = $_REQUEST["inizio"];
}
if (isset($_REQUEST["fine"])) {
    $fine = $_REQUEST["fine"];
}

if (!isset($inizio) || !isset($fine)) {
    // mancano dei dati, indichiamo che sono mancanti
    header("Content-type: text/html");
    echo "<html><body>Informazioni insufficienti per la ricerca dei dati per il Riepilogo Generale del periodo</body></html>";
    exit;
}

$giorno = date("d/m/Y"); // default la giornata odierna (formato italiano da convertire in ISO

$giornatainiziale = new DateTime($inizio);
$ggi = $giornatainiziale->format("Y-m-d");
$partenza = $giornatainiziale->format("d/m/Y");

$giornatafinale = new DateTime($fine);
$ggf = $giornatafinale->format("Y-m-d");
$arrivo = $giornatafinale->format("d/m/Y");

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
/*
$sql = "SELECT c.id AS id, c.Denominazione AS nomecliente, c.sequenza, ";
$sql .= "bc.dataconsegna AS dataconsegna,";
$sql .= "SUM(((db.b % 123456) / 1000)) AS qta_b,";
$sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n,";
$sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
$sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n,";
$sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
$sql .= "FROM cp_cliente c, cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
$sql .= "WHERE bc.cliente = c.id AND ";
$sql .= "bc.dataconsegna = DATE('" . $gg . "') ";
$sql .= "GROUP BY c.id , bc.dataconsegna ";
$sql .= "ORDER BY c.sequenza ";
*/

$sql = "SELECT bc.dataconsegna AS dataconsegna, ";
$sql .= "SUM(((db.b % 123456) / 1000)) AS qta_b, ";
$sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n, ";
$sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b, ";
$sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, ";
$sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
// 2024-08-15
$sql .= ", ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * (1+c.perc_iva / 100),2) AS tot_ivato, ";
$sql .= "ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * (1+c.perc_iva / 100),2) + SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_compl ";
// fine 2024-08-15
$sql .= "FROM cp_cliente c,";
$sql .= "cp_dettagliobolla db ";
$sql .= "JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
$sql .= "WHERE bc.cliente = c.id AND ";
$sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine ."') ";
$sql .= "GROUP BY bc.dataconsegna ";
$sql .= "ORDER BY bc.dataconsegna";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
?>
<html>
<body>
    <table border="1" width="50%">
        <tr>
            <td colspan="7" align="center">
                <b>
                    Riepilogo Generale dal <?= $partenza ?> al <?= $arrivo ?>
                </b>
            </td>
        </tr>
        <tr>
            <td align="right" width="15%"><b>Data</b></td>
            <td align="right" width="10%"><b>Q.t&agrave; B</b></td>
            <td align="right" width="10%"><b>Q.t&agrave; N</b></td>
            <td align="right" width="15%"><b>Totale B</b></td>
            <td align="right" width="15%"><b>Totale IVATO</b></td>
            <td align="right" width="15%"><b>Totale N</b></td>
            <td align="right" width="15%"><b>Totale Compl.</b></td>
        </tr>

        <?php
        $sommaqta_b = 0;
        $sommaqta_n = 0;
        $sommaqta_cln = 0;
        $sommatot_b = 0.00;
        $sommatot_n = 0.00;
        $sommatot_cln = 0.00;
        // 2024-08-15
        $sommatot_ivato = 0.00;
        $sommatot_compl = 0.00;

        while ($row = mysqli_fetch_array($result)) {
            $datacons = $row["dataconsegna"];
            $giornatacons = new DateTime($datacons);
            $ggc = $giornatacons->format("Y-m-d");
            $dataconsegnato = $giornatacons->format("d/m/Y");
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
            $sommatot_compl += $tot_compl;
        ?>
            <tr>
                <td align="right">
                    <?= $dataconsegnato ?>
                </td>
                <td align="right">
                    <?= number_format((float) $qta_b, 0, '.', '') ?>
                </td>
                <td align="right">
                    <?= number_format((float) $qta_n, 0, '.', '') ?>
                </td>
                <td align="right">
                    &euro;<?= number_format((float) $tot_b, 2, '.', '') ?>
                </td>
                <td align="right">
                    &euro;<?= number_format((float) $tot_ivato, 2, '.', '') ?>
                </td>
                <td align="right">
                    &euro;<?= number_format((float) $tot_n, 2, '.', '') ?>
                </td>
                <td align="right">
                    &euro;<?= number_format((float) $tot_compl, 2, '.', '') ?>
                </td>                
            </tr>
        <?php
        }

        mysqli_free_result($result);

        // riporto i totali della clientela
?>
        <tr>
            <td align="right">
                <b>TOTALI</b>
            </td>
            <td align="right">
                <?= number_format((float) $sommaqta_b, 0, '.', '') ?>
            </td>
            <td align="right">
                <?= number_format((float) $sommaqta_n, 0, '.', '') ?>
            </td>
            <td align="right">
                <font size="2"><b>&euro;<?= number_format((float) $sommatot_b, 2, '.', '') ?></b></font>
            </td>
            <td align="right">
                <font size="2"><b>&euro;<?= number_format((float) $sommatot_ivato, 2, '.', '') ?></b></font>
            </td>
            <td align="right">
                <font size="2"><b>&euro;<?= number_format((float) $sommatot_n, 2, '.', '') ?></b></font>
            </td>

            <td align="right">
                <font size="3"><b>&euro;<?= number_format((float) $sommatot_compl, 2, '.', '') ?></b></font>
            </td>

        </tr>
        <tr>
            <td></td>
            <td align="right">
                <?= $sommaqta_cln ?>
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>

        <?php
        // chiusura della pagina
        ?>
    </table>
</body>
</html>