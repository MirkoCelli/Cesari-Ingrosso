<html>
<body>
    <table border="1">
        <tr>
            <td align="left">Gruppo Prodotti</td>
            <td align="center">Prezzo</td>
            <td align="center">Quantit&agrave; Bolla</td>
            <td align="center">Totale Bolla</td>
            <td align="center">Q.t&agrave; N.</td>
            <td align="center">Tot. N.</td>
        </tr>

        <?php
// (c) 2024-07-10 - Robert Gasperoni - Form per il riepilogo di un ordine con totali in chiaro e in nero

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
$idordine = $_REQUEST["idordine"];

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");

/* **************  BLOCCO TOTALMENTE SOSTITUITO DA CALCOLI IN LOCALE ******************************** */

// determino gli elementi costanti per il riepilogo ordine

$sqlord = "SELECT o.id as idordine, c.id as cliente, (((r.perc_b % 123456) / 1000) / 100) as perc_b, (((r.perc_n % 123456) / 1000) / 100) as perc_n \n ";
$sqlord .= "FROM cp_ordinecliente o \n JOIN cp_cliente c ON (c.id = o.cliente) \n JOIN cp_rapportoconsegna r ON (r.cliente = o.cliente) \n ";
$sqlord .= "WHERE o.id = " . $idordine ;

$resultord = mysqli_query($db, $sqlord);

if (!$resultord) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

while ($roword = mysqli_fetch_array($resultord)) {
    $idordineord = $roword["idordine"];
    $clienteord = $roword['cliente'];
    $perc_b_ord = $roword["perc_b"];
    $perc_n_ord = $roword["perc_n"];
}
mysqli_free_result($resultord);

// ora cerco di ottenere tutti i dettagli dell'ordine e per ogni gruppo di prodotti ne calcolo la quantità (in base alla unita di misura faccio arrotondamenti oppure no)

// mi serve avere un array con chiave gruppo e valore le quantità e un array per unita di misura per ogni chiave gruppo
// $quantitagruppi[$key] = $quantitagruppi[$key] + $value;

$quantitagruppi = [];
$prezzogruppi = [];
$unmisgruppi = [];
$nomegruppi = [];
$qtab = [];
$qtan = [];
$totb = [];
$totn = [];

$sql = "SELECT d.dettaglioordine, d.prodotto, d.gruppo, d.quantita, dg.prezzounitario, dg.unitamisura, g.NomeGruppo ";
$sql .= "FROM cp_ordinecliente o ";
$sql .= "LEFT OUTER JOIN cp_dettaglioordine d  ON (d.ordinecliente = o.id) ";
$sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
$sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
$sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
$sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
$sql .= "WHERE d.ordinecliente = " . $idordine . " AND d.stato = 0 ";
$sql .= "ORDER BY g.id "; // 03/10/2024 - Richiesta Berti Giacomo Cesari Pasticceria
// $sql .= "ORDER BY g.NomeGruppo ";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

while ($row = mysqli_fetch_array($result)) {
    $gruppo = $row["gruppo"];
    $nomegruppo = $row['NomeGruppo'];
    $prezzo = $row["prezzounitario"];
    $qtapezzo = $row["quantita"];
    $prod = $row["prodotto"];
    $unmis = $row["unitamisura"];
    $dettord = $row["dettagliordine"];
    // ora verifico se esiste il gruppo in $quantitagruppo e $unmisgruppi
    if (!isset($quantitagruppi[$gruppo])){
        $quantitagruppi[$gruppo] = 0;
        $prezzogruppi[$gruppo] = $prezzo;
        $unmisgruppi[$gruppo] = $unmis;
        $qtab[$gruppo] = 0;
        $qtan[$gruppo] = 0;
        $totb[$gruppo] = 0.00;
        $totn[$gruppo] = 0.00;
        $nomegruppi[$gruppo] = $nomegruppo;
    }
    // aggiungo la quantità a quantitagruppi
    $quantitagruppi[$gruppo] += $qtapezzo;
}

// ora in $quantitagruppi dovrei avere le quantità totali per ogni gruppo
// ora devo determinare il loro qta_b e qta_n in base alle percentuali di rapporto consegna

$qtabolla_1 = 0;
$totalebolla_1 = 0.00;
$qta_n_1 = 0;
$tot_n_1 = 0.00;

foreach ($quantitagruppi as $key => $value)
{
            if ($unmisgruppi[$key] == "PZ") {
                $qtab[$key] += ceil($quantitagruppi[$key] * $perc_b_ord);
                $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
                $qtan[$key] += floor($quantitagruppi[$key] * $perc_n_ord);
                $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
            }
            if ($unmisgruppi[$key] == "KG") {
                $qtab[$key] += ($quantitagruppi[$key] * $perc_b_ord);
                $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
                $qtan[$key] += ($quantitagruppi[$key] * $perc_n_ord);
                $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
            }
            /*
            $qtab[$key] = ceil($quantitagruppi[$key] * $perc_b_ord);
            $totb[$key] = $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] = floor($quantitagruppi[$key] * $perc_n_ord);
            $totn[$key] = $qtan[$key] * $prezzogruppi[$key];
            */
        ?>
        <tr>
            <td align="left">
                <?=$nomegruppi[$key]?>
            </td>
            <td align="right">
                <?=number_format($prezzogruppi[$key],2,'.') ?>
            </td>
            <td align="right">
                <?=$qtab[$key]?>
            </td>
            <td align="right">
                <?=number_format($totb[$key],2,'.') ?>
            </td>
            <td align="right">
                <?=$qtan[$key]?>
            </td>
            <td align="right">
                <?=number_format($totn[$key],2,'.') ?>
            </td>
        </tr>
        <?php
        // sommo queste quantità agli accumulatori previsti
        $qtabolla_1 += $qtab[$key];
        $totalebolla_1 += $totb[$key];
        $qta_n_1 += $qtan[$key];
        $tot_n_1 += $totn[$key];
}

        ?>
        <tr>
            <td></td>
            <td align="right"><b>TOTALI</b></td>
            <td align="right"><b><?= $qtabolla_1 ?></b></td>
            <td align="right"><b><?= number_format( $totalebolla_1,2,'.') ?></b></td>
            <td align="right"><b><?= $qta_n_1 ?></b></td>
            <td align="right"><b><?= number_format($tot_n_1,2,'.')  ?></b></td>
        </tr>
        <?php

mysqli_free_result($result);

 // 2024-08-09 - aggiungiamo un controllo se è un cliente di un intermediario, e nel caso mostriamo il riepilogo giornaliero di tutti i clienti dell'intermediario
 //
 goto fuoriblocco;

// blocco per gestire i dati dell'intermediario

$sql = "SELECT o.dataordine AS dataordine, c.Denominazione as nomecliente, c.intermediario as intermediario, i.Denominazione as nomeintermediario, i.tipoIntermediazione as tipointermediario, ";
$sql .= "i.codcliente as codicecliente, c.listino as listino, l.provvigione as provvigione \n";
$sql .= "FROM cp_ordinecliente o ";
$sql .= "JOIN cp_cliente c ON (o.cliente = c.id) ";
$sql .= "JOIN cp_listinoprezzi l ON (l.id = c.listino) ";
$sql .= "LEFT OUTER JOIN cp_intermediario i ON (i.id = c.intermediario) ";
$sql .= "WHERE o.id = " . $idordine;
$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// $row = mysqli_fetch_array($result,MYSQL_ASSOC);
while ($row = mysqli_fetch_array($result)) {
    $dataordine = $row["dataordine"];
    $nomecliente = $row["nomecliente"];
    $intermediario = $row["intermediario"];
    $nomeintermediario = $row["nomeintermediario"];
    $tipointermediario = $row["tipointermediario"];
    $nometipoint = "";
    switch($tipointermediario){
        case 0:
                $nometipoint = "Cliente";
                break;
        case 1:
                $nometipoint = "Rivenditore";
                break;
        case 2:
                $nometipoint = "Agente";
                break;
    }
    $codicecliente = $row["codicecliente"]; // codice cliente dell'intermediario
    $listino = $row["listino"];
    $provvigione = $row["provvigione"]; // per il calcolo della provvigione dell'agente
    // determiniamo tutti gli ordini del giorno dei clienti associati all'intermediario
    if ($intermediario !== null){
        ?>
    <br/>
    <table border="1">
        <tr>
            <td colspan="6" align="center">
                <?=$nometipoint?><b>
                    <?=$nomeintermediario?>
                </b>
            </td>
        </tr>
        <tr>
            <td align="left">Gruppo Prodotti</td>
            <td align="center">Prezzo</td>
            <td align="center">Quantit&agrave; Bolla</td>
            <td align="center">Totale Bolla</td>
            <td align="center">Q.t&agrave; N.</td>
            <td align="center">Tot. N.</td>
        </tr>
        <?php

    // preparo gli accumulatori

    $cquantitagruppi = [];
    $cprezzogruppi = [];
    $cunmisgruppi = [];
    $cnomegruppi = [];
    $cqtab = [];
    $cqtan = [];
    $ctotb = [];
    $ctotn = [];

    $cqtabolla_1 = 0;
    $ctotalebolla_1 = 0.00;
    $cqta_n_1 = 0;
    $ctot_n_1 = 0.00;


    // mi devo fare indicare tutti gli ordini associati all'intermediario e di ognuno calcolo il totale dei gruppi prodotto

    $sqlcln = "SELECT o.id as idordine, c.id as cliente, (((r.perc_b % 123456) / 1000) / 100) as perc_b, (((r.perc_n % 123456) / 1000) / 100) as perc_n ";
    $sqlcln .= "FROM cp_ordinecliente o, ";
    $sqlcln .= "cp_cliente c, ";
    $sqlcln .= "cp_rapportoconsegna r ";
    $sqlcln .= "WHERE o.dataordine = DATE('" . $dataordine . "') AND ";
    $sqlcln .= "c.intermediario = " . $intermediario . " AND ";
    $sqlcln .= "o.cliente = c.id AND ";
    $sqlcln .= "r.cliente = c.id ";
    $sqlcln .=  "ORDER BY o.id ";

    $resultcln = mysqli_query($db, $sqlcln);
    if (!$resultcln) {
       header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
       echo ("Error description: " . mysqli_error($db));
       exit; // fine dello script php
    }

    while ($rowcln = mysqli_fetch_array($resultcln)) {
       $idordinecln = $rowcln["idordine"];
       $clientecln = $rowcln["cliente"];
       $perc_b_cln = $rowcln["perc_b"];
       $perc_n_cln = $rowcln["perc_n"];
       // ora faccio le stesse operazioni che ho fatto per il singol ordine ma non azzero gli accumulatori
// ********************************************************************************************************************************** //
       $sql = "SELECT d.dettaglioordine, d.prodotto, d.gruppo, d.quantita, dg.prezzounitario, dg.unitamisura, g.NomeGruppo ";
       $sql .= "FROM cp_ordinecliente o ";
       $sql .= "LEFT OUTER JOIN cp_dettaglioordine d  ON (d.ordinecliente = o.id) ";
       $sql .= "LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
       $sql .= "LEFT OUTER JOIN cp_listinoprezzi l ON (c.listino = l.id) ";
       $sql .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
       $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
       $sql .= "WHERE d.ordinecliente = " . $idordinecln . " AND d.stato = 0 ";
       $sql .= "ORDER BY g.NomeGruppo ";

       $result9 = mysqli_query($db, $sql);
       if (!$result9) {
         header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
         echo ("Error description: " . mysqli_error($db));
         exit; // fine dello script php
       }

       while ($row = mysqli_fetch_array($result9)) {
         $gruppo = $row["gruppo"];
         $nomegruppo = $row['NomeGruppo'];
         $prezzo = $row["prezzounitario"];
         $qtapezzo = $row["quantita"];
         $prod = $row["prodotto"];
         $unmis = $row["unitamisura"];
         $dettord = $row["dettagliordine"];
         // ora verifico se esiste il gruppo in $quantitagruppo e $unmisgruppi
         if (!isset($cquantitagruppi[$gruppo])) {
           $cquantitagruppi[$gruppo] = 0;
           $cprezzogruppi[$gruppo] = $prezzo;
           $cunmisgruppi[$gruppo] = $unmis;
           $cqtab[$gruppo] = 0;
           $cqtan[$gruppo] = 0;
           $ctotb[$gruppo] = 0.00;
           $ctotn[$gruppo] = 0.00;
           $cnomegruppi[$gruppo] = $nomegruppo;
         }
         // aggiungo la quantità a quantitagruppi
         $cquantitagruppi[$gruppo] += $qtapezzo;
       }
       mysqli_free_result($result9);
       foreach ($cquantitagruppi as $key => $value) {

        if ($cunmisgruppi[$key] == "PZ"){
            $cqtab[$key] += ceil($cquantitagruppi[$key] * $perc_b_ord);
            $ctotb[$key] += $cqtab[$key] * $cprezzogruppi[$key];
            $cqtan[$key] += floor($cquantitagruppi[$key] * $perc_n_ord);
            $ctotn[$key] += $cqtan[$key] * $cprezzogruppi[$key];
        }
        if ($cunmisgruppi[$key] == "KG"){
            $cqtab[$key] += ($cquantitagruppi[$key] * $perc_b_ord);
            $ctotb[$key] += $cqtab[$key] * $cprezzogruppi[$key];
            $cqtan[$key] += ($cquantitagruppi[$key] * $perc_n_ord);
            $ctotn[$key] += $cqtan[$key] * $cprezzogruppi[$key];
        }
        /*
         $cqtab[$key] = ceil($cquantitagruppi[$key] * $perc_b_ord);
         $ctotb[$key] = $cqtab[$key] * $cprezzogruppi[$key];
         $cqtan[$key] = floor($cquantitagruppi[$key] * $perc_n_ord);
         $ctotn[$key] = $cqtab[$key] * $cprezzogruppi[$key];
         */
         // sommo queste quantità agli accumulatori previsti
         $cqtabolla_1 += $qtab[$key];
         $ctotalebolla_1 += $totb[$key];
         $cqta_n_1 += $qtan[$key];
         $ctot_n_1 += $totn[$key];
       }
       // adesso mostro i dettagli gruppi per l'agente
       foreach($cquantitagruppi as $key => $value){
         // qui mostro i dettagli per gruppo
?>
<tr>
  <td align="left"><?= $cnomegruppi[$key] ?></td>
  <td align="right"><?= $cprezzogruppi[$key] ?></td>
  <td align="right"><?= $cqtab[$key] ?></td>
  <td align="right"><?= number_format($ctotb[$key], 2, '.') ?></td>
  <td align="right"><?= $cqtan[$key] ?></td>
  <td align="right"><?= number_format($ctotn[$key], 2, '.') ?></td>
</tr>
<?php
       }

       // ora in $quantitagruppi dovrei avere le quantità totali per ogni gruppo
// ora devo determinare il loro qta_b e qta_n in base alle percentuali di rapporto consegna

        ?>
         <tr>
            <td></td>
            <td align="right">
              <b>TOTALI</b>
            </td>
            <td align="right">
               <b><?= $cqtabolla_1 ?></b>
            </td>
            <td align="right">
               <b><?= number_format($ctotalebolla_1,2,'.')  ?></b>
            </td>
            <td align="right">
               <b><?= $cqta_n_1 ?></b>
            </td>
            <td align="right">
               <b><?= number_format($ctot_n_1,2,'.')  ?></b>
            </td>
        </tr>
<?php

        // mysqli_free_result($result);
// ********************************************************************************************************************************** //
       // fine delle operazioni di accumulo quantità
    }

    mysqli_free_result($resultcln);
?>

    </table>
<?php

 // 2024-08-09 - devo visualizzare se è un rivenditore il intermediario faccio vedere se alla dataordine c'è una bollaconsegna associata al codcliente dell'intermediario
  if ($tipointermediario == 1){
    $sql = "SELECT bc.*, c.Denominazione as nomecliente FROM cp_bollaconsegna bc LEFT OUTER JOIN cp_cliente c ON (c.id = bc.cliente), cp_intermediario i WHERE bc.cliente = i.codcliente AND bc.dataconsegna = DATE('$dataordine') AND i.id = $intermediario ";
    $result2 = mysqli_query($db, $sql);
    if (!$result2) {
       header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
       echo ("Error description: " . mysqli_error($db));
       exit; // fine dello script php
    }

            $dt1 = new DateTime($dataordine);
            $dataordine1 = $dt1->format("d/m/Y");
    // $row = mysqli_fetch_array($result,MYSQL_ASSOC);
?>
    <br/>
    <table border="1">
        <tr><td colspan="6" align="center">Bolla di Consegna del Rivenditore <?= $intermediario?> del <?= $dataordine1?></td></tr>
        <tr>
            <td>Data Bolla</td>
            <td>Num.Bolla</td>
            <td>Totale Bolla</td>
            <td>Nome Cliente</td>
            <td>Rapporto</td>
            <td>Fatturato</td>
        </tr>


        <?php
    while ($row1 = mysqli_fetch_array($result2)) {
       $idbolla = $row1["id"];
       $databolla = $row1["dataconsegna"];
       $dt = new DateTime($databolla);
       $databolla1 = $dt->format("d/m/Y");
       $numbolla = $row1["numbolla"];
       $totalebolla = $row1["totalebolla"];
       $cliente = $row1["cliente"];
       $ordine = $row1["ordine"];
       $rapporto = $row1["rapporto"];
       $fatturato = $row1["fatturato"];
       $nomecliente = $row1["nomecliente"];
       // ora disegniamo la tabella con queste informazioni
?>
        <tr>
            <td><?= $databolla1 ?></td>
            <td><?= $numbolla ?></td>
            <td><?= number_format($totalebolla,2,'.') ?></td>
            <td><?= $nomecliente ?></td>
            <td><?= $rapporto ?></td>
            <td><?= $fatturato ?></td>
        </tr>
        <?php
    }
    mysqli_free_result($result2);
  }
 } // fine intermediario
}

fuoriblocco:

        ?>
    </table>
</body>
</html>