<?php
// (c) 2024-07-12 - Robert Gasperoni - impaginazione dei dati relativi al cliente nel periodo indicato

// ora ci servono gli elementi per generare la query
include("dbconfig.php");

// PRODUZIONE PRODOTTI - 2024-06-27
// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

$idcliente = null;
$inizio = null;
$fine = null;

if (isset($_REQUEST["id"])) {
    $idcliente = $_REQUEST["id"];
}
if (isset($_REQUEST["inizio"])) {
    $inizio = $_REQUEST["inizio"];
}
if (isset($_REQUEST["fine"])) {
    $fine = $_REQUEST["fine"];
}

if (!isset($idcliente) || !isset($inizio) || !isset($fine)){
    // mancano dei dati, indichiamo che sono mancanti
    header("Content-type: text/html");
    echo "<html><body>Informazioni insufficienti per la ricerca dei dati sulle Bolle di Consegna del cliente</body></html>";
    exit;
}

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$oggi = date("Y-m-d");
$giorno = $oggi;

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
$sql .= "SELECT b.Denominazione, ";
$sql .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario, ";
$sql .= "i.tipoIntermediazione as tipointermediario, t.tipo as nometipoint, ";
// 2024-08-15
$sql .= "b.perc_iva as percIVA, ";
// fine 2024-08-15
$sql .= "IFNULL(q.tipoIntermediazione,NULL) AS clientespeciale ";
$sql .= "FROM cp_cliente b ";
$sql .= "LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
$sql .= "LEFT OUTER JOIN cp_tipointermediario t ON (t.id = i.tipoIntermediazione) ";
$sql .= "LEFT OUTER JOIN cp_intermediario q ON (q.codcliente = b.id) ";
$sql .= "WHERE b.id = " . $idcliente ;

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

$nomecliente = "";
$intermediario = null;
$nomeintermediario = "";
$tipointerm = null;
$nometipoint = "";
$clientespeciale = null;
$perciva = null;

if ($row = mysqli_fetch_array($result)) {
    $nomecliente = $row["Denominazione"];
    $intermediario = $row["intermediario"];
    $nomeintermediario = $row["nomeintermediario"];
    $tipointerm = $row["tipointermediario"];
    $nometipoint = $row["nometipoint"];
    $clientespeciale = $row["clientespeciale"];
    $perciva = $row["percIVA"]; // 2024-08-15
}

mysqli_free_result($result);

// 2024-08-09 - La clientela si distingue per cliente pasticceria, cliente agente, cliente rivenditore, agente , rivenditore
// se $clientespeciale == 1 allora è Rivenditore, se $clientespeciale == 2 allora è Agente, $clientespeciale == null è un cliente
// se $intermediario != null allora in base alla tipointermediazione si stabilisce se è legato a Agente o Rivenditore

// in base alla tipologia di cleinte selezionato determino quale funzione chiamare
if ($clientespeciale == null){
    RiepiloghiClientelaStandard();
} 
elseif ($clientespeciale == 1) {
    RiepiloghiClientelaRivenditore();
}
else {
    RiepiloghiClientelaAgente();
}

// ************ Riepilogo per il cliente standard ******************

function RiepiloghiClientelaStandard()
{
    global $db;
    global $idcliente;
    global $nomecliente;
    global $inizio;
    global $fine;
    global $numerafatt;
    global $perciva; // 2024-08-15

    ?>
    <html>
     <head>
         <script type="text/javascript">
  function MostraDettagli(giorno, cliente) {
      // 2024-08-16 - Mostra in una finestra a parte i dettagli del cliente per il giorno indicato
      // alert(cliente + " " + giorno);
      // alert("MostraGiornaliero('" + giorno + "')");
      var urlDati = 'totaligruppiprodotto.php?dataordine=' + giorno + "&idcliente=" + cliente + "&azione=bolletta";
      // alert(urlDati);
      setTimeout(function () { $('#mostradett').load(urlDati); }, 300);
      return false;
  }
         </script>
     </head>
     <body>
      <table>
       <tr valign="top">
        <td>
         <table border="1">
          <tr><td colspan="10" align="center"><b>Riepilogo Ordini mensile con totali giornaliero e scalare di <br/><?= $nomecliente ?></b></td></tr>
          <tr>
           <td>Data Ordine</td>
           <td>Totale Da Fatturare</td>
           <td>Totale IVATO</td>
           <td>Totale non da Fatturare</td>
           <td>Totale Complessivo</td>
           <td>Totale Scalare</td>
           <td>Dettagli</td>
          </tr>
             <?php
    /* // dettaglio delle bollette mensil del cliente - non è questo che vogliono vedere
    $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, db.quantita AS quantita, db.totale AS totale, ";
    $sql .= "bc.dataconsegna AS dataconsegna, bc.numbolla AS numbolla \n";
    // 29/07/2024
    $sql .= ", ((db.b % 123456) / 1000) AS qta_b, ((db.n % 123456) / 1000) AS qta_n, ";
    $sql .= "db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000) AS tot_b,";
    $sql .= "db.prezzounitario * ((db.n % 123456) / 1000) AS tot_n, ((db.t % 123456) / 100) AS totale_cliente ";
    // fine 29/07/2024 - ci vuole anche il totale del Nero e la relativa quantità
    $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
    $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
    $sql .= "bc.dataconsegna BETWEEN DATE('". $inizio . "') AND DATE('" . $fine . "') ";
    $sql .= "ORDER BY bc.dataconsegna, g.NomeGruppo, db.prezzounitario ";
    */

    /* Query per il riepilogo mensile dei totali e dello scalare di un cliente */
    $sql = "SELECT bc.dataconsegna AS dataconsegna, SUM(((db.b % 123456) / 1000)) AS qta_b, ";
    $sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n,";
    $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
    // 2024-08-15 - Aggiungere il totale IVATO
    $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * (1+" . $perciva . " / 100.0) AS totivato, ";
    // fine 2024-08-15
    $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, ";
    $sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
    // 2024-08-15 - Aggiungere il totale IVATO
    $sql .= ", ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * (1.0 + " . $perciva . " / 100.0),2) + SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS totcomp ";
    // fine 2024-08-15
    //
    $sql .= "FROM cp_dettagliobolla db ";
    $sql .= "JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
    $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
    $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
    $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    $sql .= "GROUP BY bc.dataconsegna ORDER BY bc.dataconsegna ";

    $datarif = null;
    $numbollarif = null;

    $tot_b = 0.00;
    $tot_n = 0.00;
    $tot_cln = 0.00;
    $tot_ivato = 0.00; // 2024-08-15
    $tot_comp = 0.00; // 2024-08-15

    $totfatt = 0.00;
    $totfattiva = 0.00; // 2024-08-15
    $totnero = 0.00;
    $totcliente = 0.00;
    $totcompl = 0.00; // 2024-08-15

    $totscalare = 0.00;

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    while ($row = mysqli_fetch_array($result)) {
        $tot_b = $row["tot_b"];
        $tot_n = $row["tot_n"];
        $tot_cln = $row["totale_cliente"];
        $tot_ivato = $row["totivato"];
        $tot_comp = $row["totcomp"];

        $totfatt += $tot_b;
        $totnero += $tot_n;
        $totfattiva += $tot_ivato; // 2024-08-15

        $totcliente += $tot_cln;

        $totcompl += $tot_comp; // 2024-08-15

        // $totscalare += $tot_cln;
        $totscalare += $tot_comp; // 2024-08-15

        $datacons = $row["dataconsegna"];

             ?>
               <tr>
                <td><b><?= date("d/m/Y", strtotime($datacons)) ?></b></td>
                <td align="right">&euro;<?= number_format((float) $tot_b, 2, '.', '') ?></td>
                <td align="right">&euro;<?= number_format((float) $tot_ivato, 2, '.', '') ?></td>
                <td align="right">&euro;<?= number_format((float) $tot_n, 2, '.', '') ?></td>
                <td align="right">&euro;<?= number_format((float) $tot_comp, 2, '.', '') ?></td>
                <td align="right">&euro;<?= number_format((float) $totscalare, 2, '.', '') ?></td>
                <td>
                  <button name="dettagli" id="dettagli" onclick="MostraDettagli('<?=$datacons?>',<?=$idcliente?>); return false;">Dett.</button>
                </td>
               </tr>
        <?php
    }
    mysqli_free_result($result);
    ?>
           <tr>
            <td align="right"><b>TOTALE</b></td>
            <td align="right"><b>&euro;<?= number_format((float) $totfatt, 2, '.', '') ?></b></td>
            <td align="right"><b>&euro;<?= number_format((float) $totfattiva, 2, '.', '') ?></b></td>
            <td align="right"><b>&euro;<?= number_format((float) $totnero, 2, '.', '') ?></b></td>
            <td align="right"><b>&euro;<?= number_format((float) $totcompl, 2, '.', '') ?></b></td>
            <td align="right"></td>
           </tr>
          </table>
         </td>
         <td width="50px"></td>
         <td>
          <table border="1">
           <tr><td colspan="9" align="center"><b>Riepilogo quantitativi nel periodo per il cliente <br/><?= $nomecliente ?></b></td></tr>
           <tr>
            <td>Nome Gruppo</td>
            <td>Prezzo</td>
            <td>Quantit&agrave;</td>
            <td>Totale</td>
            <td>Totale Ivato</td>
            <!--  -->
            <td>Q.t&agrave; N</td>
            <td>Tot. N</td>
            <td>Q.t&agrave; Tot.</td>
            <td>Totale Cliente</td>
           </tr>
              <?php
    // qui facciamo il riepilogo generale
    $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, SUM(db.quantita) AS quantita, SUM(db.totale) AS totale \n";
    // 2024-07-29
    $sql .= ", SUM(((db.b % 123456) / 1000)) AS qta_b, SUM(((db.n % 123456) / 1000)) AS qta_n, ";
    $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
    // 2024-08-15
    $sql .= "ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * ( 1 + " . $perciva . "/ 100.0), 2)  AS tot_ivato,";
    // fine 2024-08-15
    $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, ";
    $sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
    // 2024-08-15
    $sql .= ", ROUND(SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) * ( 1 + " . $perciva . "/ 100.0), 2) + SUM(db.prezzounitario * ((db.n % 123456) / 1000))  AS tot_compl ";
    // fine 2024-08-15
    // fine 2024-07-29
    $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
    $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
    $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    // 03/10/2024- richiesta di Berti Giacomo Ceari Pasticceria
    $sql .= "GROUP BY g.id, db.prezzounitario \n";
    $sql .= "ORDER BY g.id, db.prezzounitario ";
    //
    // $sql .= "GROUP BY g.NomeGruppo, db.prezzounitario \n";
    //$sql .= "ORDER BY g.NomeGruppo, db.prezzounitario ";

    $totfatt = 0.00;
    $totnero = 0.00;
    $totcliente = 0.00;
    // 2024-08-15
    $totivato = 0.00;
    $totcompl = 0.00;
    $tot_cln = 0.00;
    $totfattiva = 0.00;

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    while ($row = mysqli_fetch_array($result)) {
        $nomegruppo = $row["nomegruppo"];
        $prezzo = $row["prezzo"];
        $quantita = $row["quantita"];
        $totale = $row["totale"];
        // 2024-08-15
        $totivato = $row["tot_ivato"];
        $tot_cln = $row["tot_compl"];
        // fine 2024-08-15

        // 2024-07-29 quantitativi dettagliati
        $qta_n = $row["qta_n"];
        $tot_b = $row["tot_b"];
        $tot_n = $row["tot_n"];
        $totcln = $row["totale_cliente"];
        // fine 2024-07-29
        $totfatt += $totale;
        $totnero += $tot_n;
        $totcliente += $tot_cln;
        $totfattiva += $totivato;
              ?>
                   <tr>
                    <td><?= $nomegruppo ?></td>
                    <td align="right">&euro;<?= number_format((float) $prezzo, 2, '.', '') ?></td>
                    <td align="right"><?= number_format((float) $quantita, 0, '.', '') ?></td>
                    <td align="right">&euro;<?= number_format((float) $totale, 2, '.', '') ?></td>
                    <td align="right">&euro;<?= number_format((float) $totivato, 2, '.', '') ?></td>
                    <!-- totali di dettaglio -->
                    <td align="right"><?= number_format((float) $qta_n, 0, '.', '') ?></td>
                    <td align="right">&euro;<?= number_format((float) $tot_n, 2, '.', '') ?></td>
                    <td align="right"><?= number_format((float) ($quantita + $qta_n), 0, '.', '') ?></td>
                    <td align="right">&euro;<?= number_format((float) $tot_cln, 2, '.', '') ?></td>
                   </tr>
            <?php
    }
    ?>
               <tr>
                <td><b>TOTALE</b></td>
                <td align="right"></td>
                <td align="right"></td>
                <td align="right"><b>&euro;<?= number_format((float) $totfatt, 2, '.', '') ?></b></td>
                <td align="right"><b>&euro;<?= number_format((float) $totfattiva, 2, '.', '') ?></b></td>
                <!-- totali aggiuntivi-->
                <td></td>
                <td align="right"><b>&euro;<?= number_format((float) $totnero, 2, '.', '') ?></b></td>
                <td align="right"><b>TOT.CLN</b></td>
                <td align="right"><b>&euro;<?= number_format((float) $totcliente, 2, '.', '') ?></b></td>
               </tr>
    <?php
    mysqli_free_result($result);
    ?>
          </table>
          <!-- 2024-08-18 - tabella che mostra il riepilogo giornaliero selezionato con MostraDettagli, viene chiamato uno script esterno per i dettagli -->
          <br/>
          <div name="mostradett" id="mostradett">Qui ci va la tabella dei dettagli del giorno selezionato</div>
         </td>
         <td width="50px"></td>
         <td>
          <table border="1">
           <tr><td colspan="7" align="center"><b>Elenco Fatture nel periodo per il cliente <br/><?= $nomecliente ?></b></td></tr>
           <tr>
            <td>Data Fattura</td>
            <td>N.fatt.</td>
            <td>Numerazione</td>
            <td>Imponibile</td>
            <td>Percentuale &percnt;</td>
            <td>Imposta IVA</td>
            <td>Totale Fatt.</td>
           </tr>
    <?php
    // devo stabilire se ha delle fatture emesse nel periodo e indico quante fatture sono:
    $sql = "SELECT datafattura, numerofattura, numerazionefattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura \n";
    $sql .= "FROM cp_fattura f ";
    $sql .= "WHERE f.cliente = " . $idcliente . " AND ";
    $sql .= "f.datafattura BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    $sql .= "ORDER BY f.datafattura, f.numerofattura ";

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    while ($row = mysqli_fetch_array($result)) {
        $datafatt = $row["datafattura"];
        $numfatt = $row["numerofattura"];
        $numerazfatt = $row["numerazionefattura"];
        $imponibile = $row["totaleimponibile"];
        $perciva = $row["perc_IVA"];
        $imposta = $row["impostaIVA"];
        $totale = $row["totalefattura"];
        ?>
              <tr>
               <td><?= date("d/m/Y", strtotime($datafatt)) ?></td>
               <td align="right"><?= $numfatt ?></td>
               <td><?= $numerafatt ?></td>
               <td align="right">&euro;<?= number_format((float) $imponibile, 2, '.', '') ?></td>
               <td align="right"><?= number_format((float) $perciva, 1, '.', '') ?> &percnt;</td>
               <td align="right">&euro;<?= number_format((float) $imposta, 2, '.', '') ?></td>
               <td align="right">&euro;<?= number_format((float) $totale, 2, '.', '') ?></td>
              </tr>
                <?php
    }
    ?>
          </table>
         </td>
        </tr>
    <?php
    // chiude il db
    mysqli_close($db);
    ?>
         </table>
     </body>
    </html>
    <?php
} // fine function RiepiloghiClientelaStandard();

// Riepilogo per l'Agente

function RiepiloghiClientelaAgente()
{
    global $db;
    global $idcliente;
    global $nomecliente;
    global $inizio;
    global $fine;
    global $numerafatt;

    ?>
    <html>
    <body>
        <table>
            <tr valign="top">
                <td>
                    <table border="1">
                        <tr>
                            <td colspan="10" align="center">
                                <b>
                                    Riepilogo Ordini mensile con totali giornaliero e scalare di <br />
                                    <?= $nomecliente ?> come AGENTE
                                </b>
                            </td>
                        </tr>
                        <tr>
                            <td>Data Ordine</td>
                            <td>Totale Da Fatturare</td>
                            <td>Totale non da Fatturare</td>
                            <td>Totale Complessivo</td>
                            <td>Totale Scalare</td>
                        </tr>
                        <?php
                        /* // dettaglio delle bollette mensil del cliente - non è questo che vogliono vedere
                        $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, db.quantita AS quantita, db.totale AS totale, ";
                        $sql .= "bc.dataconsegna AS dataconsegna, bc.numbolla AS numbolla \n";
                        // 29/07/2024
                        $sql .= ", ((db.b % 123456) / 1000) AS qta_b, ((db.n % 123456) / 1000) AS qta_n, ";
                        $sql .= "db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000) AS tot_b,";
                        $sql .= "db.prezzounitario * ((db.n % 123456) / 1000) AS tot_n, ((db.t % 123456) / 100) AS totale_cliente ";
                        // fine 29/07/2024 - ci vuole anche il totale del Nero e la relativa quantità
                        $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('". $inizio . "') AND DATE('" . $fine . "') ";
                        $sql .= "ORDER BY bc.dataconsegna, g.NomeGruppo, db.prezzounitario ";
                        */

                        /* Query per il riepilogo mensile dei totali e dello scalare di un cliente */
                        $sql = "SELECT bc.dataconsegna AS dataconsegna, SUM(((db.b % 123456) / 1000)) AS qta_b, ";
                        $sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n,";
                        $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
                        $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, ";
                        $sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
                        $sql .= "FROM cp_dettagliobolla db ";
                        $sql .= "JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
                        $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
                        $sql .= "GROUP BY bc.dataconsegna ORDER BY bc.dataconsegna ";

                        $datarif = null;
                        $numbollarif = null;

                        $tot_b = 0.00;
                        $tot_n = 0.00;
                        $tot_cln = 0.00;

                        $totfatt = 0.00;
                        $totnero = 0.00;
                        $totcliente = 0.00;

                        $totscalare = 0.00;

                        // eseguo il comando di query
                        $result = mysqli_query($db, $sql);
                        if (!$result) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                            echo ("Error description: " . mysqli_error($db));
                            exit; // fine dello script php
                        }
                        while ($row = mysqli_fetch_array($result)) {
                            $tot_b = $row["tot_b"];
                            $tot_n = $row["tot_n"];
                            $tot_cln = $row["totale_cliente"];

                            $totfatt += $tot_b;
                            $totnero += $tot_n;
                            $totcliente += $tot_cln;

                            $totscalare += $tot_cln;

                            $datacons = $row["dataconsegna"];

                            ?>
                            <tr>
                                <td>
                                    <b>
                                        <?= date("d/m/Y", strtotime($datacons)) ?>
                                    </b>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_b, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_n, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_cln, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totscalare, 2, '.', '') ?>
                                </td>
                            </tr>
                            <?php
                        }
                        mysqli_free_result($result);
                        ?>
                        <tr>
                            <td align="right">
                                <b>TOTALE</b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totfatt, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totnero, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totcliente, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right"></td>
                        </tr>
                    </table>
                </td>
                <td width="50px"></td>
                <td>
                    <table border="1">
                        <tr>
                            <td colspan="8" align="center">
                                <b>
                                    Riepilogo quantitativi nel periodo per il cliente <br />
                                    <?= $nomecliente ?>
                                </b>
                            </td>
                        </tr>
                        <tr>
                            <td>Nome Gruppo</td>
                            <td>Prezzo</td>
                            <td>Quantit&agrave;</td>
                            <td>Totale</td>
                            <!--  -->
                            <td>Q.t&agrave; N</td>
                            <td>Tot. N</td>
                            <td>Q.t&agrave; Tot.</td>
                            <td>Totale Cliente</td>
                        </tr>
                        <?php
                        // qui facciamo il riepilogo generale
                        $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, SUM(db.quantita) AS quantita, SUM(db.totale) AS totale \n";
                        // 2024-07-29
                        $sql .= ", SUM(((db.b % 123456) / 1000)) AS qta_b, SUM(((db.n % 123456) / 1000)) AS qta_n, ";
                        $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
                        $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, SUM(((db.t % 123456) / 100)) AS totale_cliente ";
                        // fine 2024-07-29
                        $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
                        // 03/10/2024 - richiesta Giacomo Berti Cesari Pasticceria
                        $sql .= "GROUP BY g.id, db.prezzounitario \n";
                        $sql .= "ORDER BY g.id, db.prezzounitario ";
                        //
                        // $sql .= "GROUP BY g.NomeGruppo, db.prezzounitario \n";
                        // $sql .= "ORDER BY g.NomeGruppo, db.prezzounitario ";

                        $totfatt = 0.00;
                        $totnero = 0.00;
                        $totcliente = 0.00;

                        // eseguo il comando di query
                        $result = mysqli_query($db, $sql);
                        if (!$result) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                            echo ("Error description: " . mysqli_error($db));
                            exit; // fine dello script php
                        }
                        while ($row = mysqli_fetch_array($result)) {
                            $nomegruppo = $row["nomegruppo"];
                            $prezzo = $row["prezzo"];
                            $quantita = $row["quantita"];
                            $totale = $row["totale"];
                            // 2024-07-29 quantitativi dettagliati
                            $qta_n = $row["qta_n"];
                            $tot_b = $row["tot_b"];
                            $tot_n = $row["tot_n"];
                            $totcln = $row["totale_cliente"];
                            // fine 2024-07-29
                            $totfatt += $totale;
                            $totnero += $tot_n;
                            $totcliente += $totcln;
                            ?>
                            <tr>
                                <td>
                                    <?= $nomegruppo ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $prezzo, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    <?= number_format((float) $quantita, 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totale, 2, '.', '') ?>
                                </td>
                                <!-- totali di dettaglio -->
                                <td align="right">
                                    <?= number_format((float) $qta_n, 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_n, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    <?= number_format((float) ($quantita + $qta_n), 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totcln, 2, '.', '') ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td>
                                <b>TOTALE</b>
                            </td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totfatt, 2, '.', '') ?>
                                </b>
                            </td>
                            <!-- totali aggiuntivi-->
                            <td></td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totnero, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>TOT.CLN</b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totcliente, 2, '.', '') ?>
                            </b>
                        </td>
                    </tr>
                    <?php
    mysqli_free_result($result);
                    ?>
                </table>
            </td>
            <td width="50px"></td>
            <td>
                <table border="1">
                    <tr>
                        <td colspan="7" align="center">
                            <b>
                                Elenco Fatture nel periodo per il cliente <br />
                                <?= $nomecliente ?>
                            </b>
                        </td>
                    </tr>
                    <tr>
                        <td>Data Fattura</td>
                        <td>N.fatt.</td>
                        <td>Numerazione</td>
                        <td>Imponibile</td>
                        <td>Percentuale &percnt;</td>
                        <td>Imposta IVA</td>
                        <td>Totale Fatt.</td>
                    </tr>
                    <?php
    // devo stabilire se ha delle fatture emesse nel periodo e indico quante fatture sono:
    $sql = "SELECT datafattura, numerofattura, numerazionefattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura \n";
    $sql .= "FROM cp_fattura f ";
    $sql .= "WHERE f.cliente = " . $idcliente . " AND ";
    $sql .= "f.datafattura BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    $sql .= "ORDER BY f.datafattura, f.numerofattura ";

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    while ($row = mysqli_fetch_array($result)) {
        $datafatt = $row["datafattura"];
        $numfatt = $row["numerofattura"];
        $numerazfatt = $row["numerazionefattura"];
        $imponibile = $row["totaleimponibile"];
        $perciva = $row["perc_IVA"];
        $imposta = $row["impostaIVA"];
        $totale = $row["totalefattura"];
                    ?>
                    <tr>
                        <td>
                            <?= date("d/m/Y", strtotime($datafatt)) ?>
                        </td>
                        <td align="right">
                            <?= $numfatt ?>
                        </td>
                        <td>
                            <?= $numerafatt ?>
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $imponibile, 2, '.', '') ?>
                        </td>
                        <td align="right">
                            <?= number_format((float) $perciva, 1, '.', '') ?> &percnt;
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $imposta, 2, '.', '') ?>
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $totale, 2, '.', '') ?>
                        </td>
                    </tr>
                    <?php
    }
                    ?>
                </table>
            </td>
        </tr>
        <?php
    // chiude il db
    mysqli_close($db);
        ?>
    </table>
</body>
</html>
<?php
} // fine function RiepiloghiClientelaAgente();

// Riepilogo per Rivenditore

function RiepiloghiClientelaRivenditore()
{
    global $db;
    global $idcliente;
    global $nomecliente;
    global $inizio;
    global $fine;
    global $numerafatt;

    ?>
    <html>
    <body>
        <table>
            <tr valign="top">
                <td>
                    <table border="1">
                        <tr>
                            <td colspan="10" align="center">
                                <b>
                                    Riepilogo Ordini mensile con totali giornaliero e scalare di <br />
                                    <?= $nomecliente ?>  come RIVENDITORE
                                </b>
                            </td>
                        </tr>
                        <tr>
                            <td>Data Ordine</td>
                            <td>Totale Da Fatturare</td>
                            <td>Totale non da Fatturare</td>
                            <td>Totale Complessivo</td>
                            <td>Totale Scalare</td>
                        </tr>
                        <?php
                        /* // dettaglio delle bollette mensil del cliente - non è questo che vogliono vedere
                        $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, db.quantita AS quantita, db.totale AS totale, ";
                        $sql .= "bc.dataconsegna AS dataconsegna, bc.numbolla AS numbolla \n";
                        // 29/07/2024
                        $sql .= ", ((db.b % 123456) / 1000) AS qta_b, ((db.n % 123456) / 1000) AS qta_n, ";
                        $sql .= "db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000) AS tot_b,";
                        $sql .= "db.prezzounitario * ((db.n % 123456) / 1000) AS tot_n, ((db.t % 123456) / 100) AS totale_cliente ";
                        // fine 29/07/2024 - ci vuole anche il totale del Nero e la relativa quantità
                        $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('". $inizio . "') AND DATE('" . $fine . "') ";
                        $sql .= "ORDER BY bc.dataconsegna, g.NomeGruppo, db.prezzounitario ";
                        */

                        /* Query per il riepilogo mensile dei totali e dello scalare di un cliente */
                        $sql = "SELECT bc.dataconsegna AS dataconsegna, SUM(((db.b % 123456) / 1000)) AS qta_b, ";
                        $sql .= "SUM(((db.n % 123456) / 1000)) AS qta_n,";
                        $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
                        $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, ";
                        $sql .= "SUM(((db.t % 123456) / 100)) AS totale_cliente ";
                        $sql .= "FROM cp_dettagliobolla db ";
                        $sql .= "JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) ";
                        $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
                        $sql .= "GROUP BY bc.dataconsegna ORDER BY bc.dataconsegna ";

                        $datarif = null;
                        $numbollarif = null;

                        $tot_b = 0.00;
                        $tot_n = 0.00;
                        $tot_cln = 0.00;

                        $totfatt = 0.00;
                        $totnero = 0.00;
                        $totcliente = 0.00;

                        $totscalare = 0.00;

                        // eseguo il comando di query
                        $result = mysqli_query($db, $sql);
                        if (!$result) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                            echo ("Error description: " . mysqli_error($db));
                            exit; // fine dello script php
                        }
                        while ($row = mysqli_fetch_array($result)) {
                            $tot_b = $row["tot_b"];
                            $tot_n = $row["tot_n"];
                            $tot_cln = $row["totale_cliente"];

                            $totfatt += $tot_b;
                            $totnero += $tot_n;
                            $totcliente += $tot_cln;

                            $totscalare += $tot_cln;

                            $datacons = $row["dataconsegna"];

                            ?>
                            <tr>
                                <td>
                                    <b>
                                        <?= date("d/m/Y", strtotime($datacons)) ?>
                                    </b>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_b, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_n, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_cln, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totscalare, 2, '.', '') ?>
                                </td>
                            </tr>
                            <?php
                        }
                        mysqli_free_result($result);
                        ?>
                        <tr>
                            <td align="right">
                                <b>TOTALE</b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totfatt, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totnero, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totcliente, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right"></td>
                        </tr>
                    </table>
                </td>
                <td width="50px"></td>
                <td>
                    <table border="1">
                        <tr>
                            <td colspan="8" align="center">
                                <b>
                                    Riepilogo quantitativi nel periodo per il cliente <br />
                                    <?= $nomecliente ?>
                                </b>
                            </td>
                        </tr>
                        <tr>
                            <td>Nome Gruppo</td>
                            <td>Prezzo</td>
                            <td>Quantit&agrave;</td>
                            <td>Totale</td>
                            <!--  -->
                            <td>Q.t&agrave; N</td>
                            <td>Tot. N</td>
                            <td>Q.t&agrave; Tot.</td>
                            <td>Totale Cliente</td>
                        </tr>
                        <?php
                        // qui facciamo il riepilogo generale
                        $sql = "SELECT g.NomeGruppo AS nomegruppo, db.prezzounitario AS prezzo, SUM(db.quantita) AS quantita, SUM(db.totale) AS totale \n";
                        // 2024-07-29
                        $sql .= ", SUM(((db.b % 123456) / 1000)) AS qta_b, SUM(((db.n % 123456) / 1000)) AS qta_n, ";
                        $sql .= "SUM(db.prezzounitario * ((db.b % 123456) / 1000) - db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_b,";
                        $sql .= "SUM(db.prezzounitario * ((db.n % 123456) / 1000)) AS tot_n, SUM(((db.t % 123456) / 100)) AS totale_cliente ";
                        // fine 2024-07-29
                        $sql .= "FROM cp_dettagliobolla db JOIN cp_bollaconsegna bc ON (db.bolla = bc.id) LEFT OUTER JOIN cp_gruppoprodotti g ON (db.gruppo = g.id) ";
                        $sql .= "WHERE bc.cliente = " . $idcliente . " AND ";
                        $sql .= "bc.dataconsegna BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
                        // 03/10/2024 - richiesta Giacomo Berti Cesari Pasticceria
                        $sql .= "GROUP BY g.id, db.prezzounitario \n";
                        $sql .= "ORDER BY g.id, db.prezzounitario ";
                        // $sql .= "GROUP BY g.NomeGruppo, db.prezzounitario \n";
                        // $sql .= "ORDER BY g.NomeGruppo, db.prezzounitario ";

                        $totfatt = 0.00;
                        $totnero = 0.00;
                        $totcliente = 0.00;

                        // eseguo il comando di query
                        $result = mysqli_query($db, $sql);
                        if (!$result) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                            echo ("Error description: " . mysqli_error($db));
                            exit; // fine dello script php
                        }
                        while ($row = mysqli_fetch_array($result)) {
                            $nomegruppo = $row["nomegruppo"];
                            $prezzo = $row["prezzo"];
                            $quantita = $row["quantita"];
                            $totale = $row["totale"];
                            // 2024-07-29 quantitativi dettagliati
                            $qta_n = $row["qta_n"];
                            $tot_b = $row["tot_b"];
                            $tot_n = $row["tot_n"];
                            $totcln = $row["totale_cliente"];
                            // fine 2024-07-29
                            $totfatt += $totale;
                            $totnero += $tot_n;
                            $totcliente += $totcln;
                            ?>
                            <tr>
                                <td>
                                    <?= $nomegruppo ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $prezzo, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    <?= number_format((float) $quantita, 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totale, 2, '.', '') ?>
                                </td>
                                <!-- totali di dettaglio -->
                                <td align="right">
                                    <?= number_format((float) $qta_n, 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $tot_n, 2, '.', '') ?>
                                </td>
                                <td align="right">
                                    <?= number_format((float) ($quantita + $qta_n), 0, '.', '') ?>
                                </td>
                                <td align="right">
                                    &euro;<?= number_format((float) $totcln, 2, '.', '') ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td>
                                <b>TOTALE</b>
                            </td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totfatt, 2, '.', '') ?>
                                </b>
                            </td>
                            <!-- totali aggiuntivi-->
                            <td></td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totnero, 2, '.', '') ?>
                                </b>
                            </td>
                            <td align="right">
                                <b>TOT.CLN</b>
                            </td>
                            <td align="right">
                                <b>
                                    &euro;<?= number_format((float) $totcliente, 2, '.', '') ?>
                            </b>
                        </td>
                    </tr>
                    <?php
    mysqli_free_result($result);
                    ?>
                </table>
            </td>
            <td width="50px"></td>
            <td>
                <table border="1">
                    <tr>
                        <td colspan="7" align="center">
                            <b>
                                Elenco Fatture nel periodo per il cliente <br />
                                <?= $nomecliente ?>
                            </b>
                        </td>
                    </tr>
                    <tr>
                        <td>Data Fattura</td>
                        <td>N.fatt.</td>
                        <td>Numerazione</td>
                        <td>Imponibile</td>
                        <td>Percentuale &percnt;</td>
                        <td>Imposta IVA</td>
                        <td>Totale Fatt.</td>
                    </tr>
                    <?php
    // devo stabilire se ha delle fatture emesse nel periodo e indico quante fatture sono:
    $sql = "SELECT datafattura, numerofattura, numerazionefattura, totaleimponibile, perc_IVA, impostaIVA, totalefattura \n";
    $sql .= "FROM cp_fattura f ";
    $sql .= "WHERE f.cliente = " . $idcliente . " AND ";
    $sql .= "f.datafattura BETWEEN DATE('" . $inizio . "') AND DATE('" . $fine . "') ";
    $sql .= "ORDER BY f.datafattura, f.numerofattura ";

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    while ($row = mysqli_fetch_array($result)) {
        $datafatt = $row["datafattura"];
        $numfatt = $row["numerofattura"];
        $numerazfatt = $row["numerazionefattura"];
        $imponibile = $row["totaleimponibile"];
        $perciva = $row["perc_IVA"];
        $imposta = $row["impostaIVA"];
        $totale = $row["totalefattura"];
                    ?>
                    <tr>
                        <td>
                            <?= date("d/m/Y", strtotime($datafatt)) ?>
                        </td>
                        <td align="right">
                            <?= $numfatt ?>
                        </td>
                        <td>
                            <?= $numerafatt ?>
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $imponibile, 2, '.', '') ?>
                        </td>
                        <td align="right">
                            <?= number_format((float) $perciva, 1, '.', '') ?> &percnt;
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $imposta, 2, '.', '') ?>
                        </td>
                        <td align="right">
                            &euro;<?= number_format((float) $totale, 2, '.', '') ?>
                        </td>
                    </tr>
                    <?php
    }
                    ?>
                </table>
            </td>
        </tr>
        <?php
    // chiude il db
    mysqli_close($db);
        ?>
    </table>
</body>
</html>
<?php
} // fine function RiepiloghiClientelaRivenditore();


?>