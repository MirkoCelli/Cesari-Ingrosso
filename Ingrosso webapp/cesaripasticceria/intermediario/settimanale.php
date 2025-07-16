<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il prospetto settimanale dell'ordinativo del cliente

include "../include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();
// funzionalità di uno locale
// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

// $serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/intermediario/";
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

// deve esistere sempre l'id cliente

$idcln = $_REQUEST["id"];

// richiesta altra data
if (isset($_POST["giorno"])){
    $giornata = $_POST["giorno"];
} else {
    $giornata = date("Y-m-d");
}
$weeknum = date("W", strtotime($giornata));
$yearnum = date("Y", strtotime($giornata));
$weekyear = 0;

// se proviene da numero di settimana per l'anno
if (isset($_GET["weeknum"])){
    $weeknum = $_GET["weeknum"];
    $yearnum = $_GET["anno"];
    if (isset($_GET["giornata"])){
        $giornata = $_GET["giornata"];
    } else {
        $giornata = date("Y-m-d"); // potrebbe dare problemi ??
    }
}

// in base al giorno scelto si fissa la settimana di riferimento
function getStartAndEndDate($week, $year, $today)
{
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $ret['year'] = $dto->format('Y');
    $ret['week_start'] = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $ret['week_end'] = $dto->format('Y-m-d');

    if (!($ret["week_start"] <= $today && $today <= $ret["week_end"])) {
        if ($week == 1){
            // prima settimana dell'anno successivo?
            $dto1 = new DateTime();
            $dto1->setISODate(++$year, $week);
            $ret['week_start'] = $dto1->format('Y-m-d');
            $dto1->modify('+6 days');
            $ret['week_end'] = $dto1->format('Y-m-d');
            $ret['year'] = $dto1->format('Y');
        } else {
            // ultima settimana dell'anno precedente
            $dto1 = new DateTime();
            $dto1->setISODate(--$year, $week);
            $ret['year'] = $dto1->format('Y');
            $ret['week_start'] = $dto1->format('Y-m-d');
            $dto1->modify('+6 days');
            $ret['week_end'] = $dto1->format('Y-m-d');
       }
    } else {
        if ($week == 1 && $ret["year"] != $year){
            $ret["year"] = $year;
        }
    }

    return $ret;
}

function RiduciNumero($valore){
    return str_replace(".000", "", $valore);
}

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpwd); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $dbname) or die("Error conecting to db.");
// aperto il database

$week_array = getStartAndEndDate($weeknum, $yearnum,$giornata);
$datainiziale = $week_array["week_start"];
$datafinale = $week_array["week_end"];
$weekyear = $week_array["year"];

$datainizio = date('Y-m-d', strtotime($datainiziale . ' -7 day'));
$datafine = date('Y-m-d', strtotime($datafinale . ' +1 day'));

$idcliente = $idcln;
// determino il nome del cliente
$sql = "SELECT c.Denominazione AS nomecliente FROM cp_cliente c ";
$sql .= "WHERE c.id = " . $idcln;

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
if ($row = mysqli_fetch_assoc($result)) {
    $nomecliente = $row["nomecliente"];
}

// 2024-09-07 - devo limitare la navigazione a ritroso alle date $datacontratto e $datapmp

$prevMonth = date('Y-m-d', strtotime('-1 month', strtotime(substr($adesso, 0, 10))));
$datapmp = substr($prevMonth, 0, 8) . "01"; // primo del mese precedente a quello corrente
// la data contratto la otteniamo da schemadefault per il cliente
$sql = "SELECT s.datainizio FROM cp_schemadefault s WHERE s.cliente = " . $idcliente . " AND s.datafine IS NULL ";
$result = mysqli_query($db, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $datacontratto = $row["datainizio"];
} else {
    // ci sono dei problemi con il contratto
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}
mysqli_free_result($result);
// fine 2024-09-07

?>
<html>
<head>
    <link rel="stylesheet" href="<?= $serverpath ?>css/settimanale.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/settimanale.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <center>
            <table>
                <tr>
                    <td align="left" class="intesta">
                        <form method="post" action="settimanale.php">
                            <input name="giorno" type="hidden" value="<?= $datainizio ?>" />
                            <input name="id" type="hidden" value="<?= $idcln ?>" />
<?php
   if ($datafinale >= $datacontratto && $datafinale >= $datapmp){
?>
                            <input type="submit" name="prec" value="< Prec." class="bottone" />
<?php
   }
?>
                        </form>
                    </td>

                    <td align="center" class="intestac">
                        <form method="post" action="settimanale.php" name="chgdate" id="chgdate">
                            <span id="settimana">
                                <input name="giorno" type="date" value="<?= $giornata ?>" required class="data" onchange="return CambiaData();"/>
                                <input name="id" type="hidden" value="<?= $idcln ?>" />
                                <input type="submit" name="invia" value="Cambia Data" class="bottone" />
                            </span>
                        </form>
                    </td>


                    <td align="right" class="intesta">
                        <form method="post" action="settimanale.php">
                            <input name="giorno" type="hidden" value="<?= $datafine ?>" />
                            <input name="id" type="hidden" value="<?= $idcln ?>" />
                            <input type="submit" name="succ" value="Succ. >" class="bottone" />
                        </form>
                    </td>

                    <td align="center" class="intesta2">
                        <a href="<?= $serverpath ?>clientela.php?id=<?=$idcln?>">Back</a>
                    </td>
                </tr>
            </table>
        </center>
    </div>
    <div name="contenuto" class="Contenuti">
        <center>
            <span class="titolo"> Cliente : <?=$nomecliente?> <br />
                Settimana n. <?=$weeknum?> / <?=$weekyear?> - Periodo <?=date('d/m/Y',strtotime($datainiziale))?> - <?= date('d/m/Y',strtotime($datafinale))?>
            </span>

        </center>
        <center>
            <table>
                <tr>
                    <td class="giorni">Luned&igrave;</td>

                    <td class="giorni">Marted&igrave;</td>

                    <td class="giorni">Mercoled&igrave;</td>

                    <td class="giorni">Gioved&igrave;</td>

                    <td class="giorni">Venerd&igrave;</td>

                    <td class="giorni">Sabato</td>

                    <td class="giorni">Domenica</td>

                </tr>
                <tr>
                    <?php
  // elenchiamo i giorni della settimana: da $datainiziale a $datafinale
     $begin = new DateTime($datainiziale);
     $end = new DateTime($datafine);
     $interval = DateInterval::createFromDateString('1 day');
     $period = new DatePeriod($begin, $interval, $end);
     foreach ($period as $dt) {
        $giorno = $dt->format("d/m/Y");
        $giorno1 = $dt->format("Y-m-d");
        $indgiorno++; // 2024-08-18 - il lunedì non deve essere selezionabile
        if ( $indgiorno > 1 ){
            // 2024-09-07 - solo se la data $giorno1 è non precedente a $datacontratto e $datapmp allor si mostra il link
            if ($giorno1 >= $datacontratto && $giorno1 >= $datapmp){
                    ?>
                    <td class="date">
                        <a href="<?=$serverpath?>giornaliero.php?giorno=<?=$giorno1?>&id=<?=$idcln?>" class=""><?= $giorno ?></a>
                    </td>
                    <?php       }  else { // fine 2024-09-07
                    ?>
                    <td class="date">
                        <?= $giorno ?>
                    </td>
<?php       }
        } else {
                    ?>
                    <td class="date">
                        <?= $giorno ?>
                    </td>
<?php            
        }
     }
                    ?>
                </tr>
                <tr>
                    <?php
  // qui cerco per il cliente associato a indice i dati per la settimana
      /*
      $sql = "SELECT c.id AS id FROM cp_login l LEFT OUTER JOIN cp_cliente c ON (c.id = l.codice AND l.tipo = 1) ";
      $sql .= "WHERE l.id = " . $indice;
      
      $result = mysqli_query($db, $sql);
      if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
      }
      if ($row = mysqli_fetch_assoc($result)){
                        $idcliente = $row["id"];
      }
      */


      foreach ($period as $dt) {
         $giorno = $dt->format("d/m/Y");
         $giorno1 = $dt->format("Y-m-d");
         // query per ottenere i dati degli ordini per questo giorno
         /* // vecchia query per ottenere i dati degli ordini giornalieri per il cliente - 2024-07-08
         $sql = "SELECT o.dataordine, d.dettaglioordine, d.unitamisura as um, d.quantita as qta, p.descrizionebreve as prod, g.NomeGruppo as grp FROM ";
         $sql .= "cp_login l ";
         $sql .= "LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = l.codice) ";
         $sql .= "LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) ";
         $sql .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
         $sql .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = p.gruppo) ";
         $sql .= "WHERE o.dataordine = DATE('".$giorno1 . "') AND "; / * data del giorno da visualizzare * /
         $sql .= "l.id = " . $indice; / * indice dell'account * /
         $sql .= " ORDER BY d.dettaglioordine";
         */
         // nuova query per ottenere i dettagli della settimana
                    
                        // query che coinvolge la suddivisione in tre select distinte da unire assieme: solo da schema, da schema e ordine, solo da ordine
                        $sql = "SELECT b.o_id  AS id, b.s_id AS riga, b.o_ordinecliente AS ordinecliente, SOLO_NOT_NULL(b.s_sequenza,b.o_dettaglioordine) AS sequenza, ";
                        $sql .= "SOLO_STR_NOT_NULL(b.s_unitamisura, b.o_unitamisura) AS unitamisura, SOLO_NOT_NULL(b.s_prodotto, b.o_prodotto) AS prodotto, SOLO_NOT_NULL(b.s_quantita,b.o_quantita) AS quantita, ";
                        $sql .= "p.codiceprodotto AS codiceprodotto, p.gruppo AS gruppo, p.descrizionebreve AS nomeprodotto, p.sequenza AS sequenzaprodotto, l.tipo AS tipolistino, dl.prezzounitario AS prezzo, ";
                        $sql .= "c.CodiceCliente AS codcliente, c.Denominazione AS nomecliente, p.codiceprodotto, p.gruppo, p.descrizionebreve, p.sequenza, l.tipo, dl.prezzounitario, c.CodiceCliente, c.Denominazione \n";
                        $sql .= "FROM \n";
                        $sql .= "(SELECT t2.*, t1.* ";
                        $sql .= "FROM ";
                        $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettaglioordine, ";
                        $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
                        $sql .= " ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
                        $sql .= "FROM  cp_ordinecliente o,  cp_dettaglioordine dt ";
                        $sql .= "WHERE o.cliente = " . $idcliente . " ";
                        $sql .= "AND o.id = dt.ordinecliente ";
                        $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
                        $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
                        $sql .= "ORDER BY dt.dettaglioordine) t2, ";
                        $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
                        $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
                        $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS s_giornosettimana2 ";
                        $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
                        $sql .= "WHERE s.cliente = " . $idcliente . " ";
                        $sql .= "AND s.id = d.schematico ";
                        $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
                        $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
                        $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno1 . "') ";
                        $sql .= "AND d.quantita >= 0 ";
                        $sql .= "ORDER BY d.sequenza) t1 ";
                        $sql .= "WHERE t1.s_prodotto = t2.o_prodotto \n";

                        $sql .= "UNION\n";

                        $sql .= "SELECT t2.*, t1.* ";
                        $sql .= "FROM ";
                        $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
                        $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
                        $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS s_giornosettimana2 ";
                        $sql .= "FROM ";
                        $sql .= "cp_schemadefault s, cp_dettaglioschema d ";
                        $sql .= "WHERE  s.cliente = " . $idcliente . " ";
                        $sql .= "AND s.id = d.schematico ";
                        $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
                        $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
                        $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno1 . "') ";
                        $sql .= "AND d.quantita >= 0 ";
                        $sql .= "ORDER BY d.sequenza) t1 ";
                        $sql .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
                        $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
                        $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
                        $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
                        $sql .= "WHERE ";
                        $sql .= "o.cliente = " . $idcliente . " ";
                        $sql .= "AND o.id = dt.ordinecliente ";
                        $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
                        $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
                        $sql .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) ";
                        $sql .= "HAVING t2.o_dataordine IS NULL \n";

                        $sql .= "UNION \n";

                        $sql .= "SELECT t2.*, t1.* ";
                        $sql .= "FROM ";
                        $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
                        $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
                        $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
                        $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
                        $sql .= "WHERE ";
                        $sql .= "o.cliente = " . $idcliente . " ";
                        $sql .= "AND o.id = dt.ordinecliente ";
                        $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
                        $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
                        $sql .= "ORDER BY dt.dettaglioordine) t2 ";
                        $sql .= "LEFT OUTER JOIN (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
                        $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
                        $sql .= "ISODAYOFWEEK('2024-06-23') AS s_giornosettimana2 ";
                        $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
                        $sql .= "WHERE ";
                        $sql .= "s.cliente = " . $idcliente . " ";
                        $sql .= "AND s.id = d.schematico ";
                        $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
                        $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
                        $sql .= "AND d.giornosettimana = `ISODAYOFWEEK`('" . $giorno1 . "') ";
                        $sql .= "AND d.quantita >= 0 ";
                        $sql .= "ORDER BY d.sequenza) t1 ON (t1.s_prodotto = t2.o_prodotto) ";
                        $sql .= "HAVING t1.s_schema IS NULL) b, ";
                        $sql .= "cp_prodotto p, cp_listinoprezzi l, cp_dettagliolistino dl, cp_cliente c \n";
                        $sql .= "WHERE c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND l.id = dl.listino AND dl.prodotto = p.id \n";
                        $sql .= "ORDER BY sequenza";

         // eseguo il comando di query
         $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
         if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
         }
                    ?>
  <td valign="top">
   <table class="dettagli">
       <tr>
          <td>U.M.</td>
          <td>Q.t&agrave;</td>
          <td>Prodotto</td>
          <td>Gruppo</td>
      </tr>
       <?php
         while ($row = mysqli_fetch_array($result)) {
             // ora leggo i dati e formatto una tabellina con U.M | Q.tà | Prodotto | Gruppo |
             if ($row["quantita"] > 0){ // 2024-08-18 solo le qunatità positive di prodotto vengono elencate
       ?>
      <tr>
          <td align="right"><?=$row["unitamisura"]?></td>
          <td align="right"><?=RiduciNumero($row["quantita"])?></td>
          <td align="left"><?=$row["nomeprodotto"]?></td>
          <td align="right"><?=$row["gruppo"]?></td>

      </tr>
       <?php
            }
         }
       ?>
       </table>
          <!-- 2024-08-15 - tabellina di riepilogo per gruppi prodotto dell'ordinato di questo giorno particolare per questo cliente -->
          <br />
          <table class="dettagli1">
              <tr>
                  <td class="dettagli1">Gruppo Prodotto</td>
                  <td class="dettagli1">Q.t&agrave;</td>
                  <td class="dettagli1">U.M.</td>
              </tr>
              <?php
              // prendo la query precedente e la riduco al giorno che ci interessa e ne faccio il raggruppamento per gruppo dei prodotti con le loro quantità e unita di misura
              $sql = "SELECT IFNULL(dl.prezzounitario,dg.prezzounitario) AS prezzoprodotto, p.gruppo AS gruppo, g.NomeGruppo AS nomegruppo, SOLO_STR_NOT_NULL(b.s_unitamisura, b.o_unitamisura) AS unitamisura, ";
              $sql .= "SUM(SOLO_NOT_NULL(b.s_quantita,b.o_quantita)) AS quantita ";
              $sql .= "FROM \n";
              $sql .= "(SELECT t2.*, t1.* ";
              $sql .= "FROM ";
              $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettaglioordine, ";
              $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
              $sql .= " ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
              $sql .= "FROM  cp_ordinecliente o,  cp_dettaglioordine dt ";
              $sql .= "WHERE o.cliente = " . $idcliente . " ";
              $sql .= "AND o.id = dt.ordinecliente ";
              $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
              $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
              $sql .= "ORDER BY dt.dettaglioordine) t2, ";
              $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
              $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
              $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS s_giornosettimana2 ";
              $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
              $sql .= "WHERE s.cliente = " . $idcliente . " ";
              $sql .= "AND s.id = d.schematico ";
              $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
              $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
              $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno1 . "') ";
              $sql .= "AND d.quantita >= 0 ";
              $sql .= "ORDER BY d.sequenza) t1 ";
              $sql .= "WHERE t1.s_prodotto = t2.o_prodotto \n";

              $sql .= "UNION\n";

              $sql .= "SELECT t2.*, t1.* ";
              $sql .= "FROM ";
              $sql .= "(SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
              $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
              $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS s_giornosettimana2 ";
              $sql .= "FROM ";
              $sql .= "cp_schemadefault s, cp_dettaglioschema d ";
              $sql .= "WHERE  s.cliente = " . $idcliente . " ";
              $sql .= "AND s.id = d.schematico ";
              $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
              $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
              $sql .= "AND d.giornosettimana = ISODAYOFWEEK('" . $giorno1 . "') ";
              $sql .= "AND d.quantita >= 0 ";
              $sql .= "ORDER BY d.sequenza) t1 ";
              $sql .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
              $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
              $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
              $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
              $sql .= "WHERE ";
              $sql .= "o.cliente = " . $idcliente . " ";
              $sql .= "AND o.id = dt.ordinecliente ";
              $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
              $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
              $sql .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) ";
              $sql .= "HAVING t2.o_dataordine IS NULL \n";

              $sql .= "UNION \n";

              $sql .= "SELECT t2.*, t1.* ";
              $sql .= "FROM ";
              $sql .= "(SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
              $sql .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
              $sql .= "ISODAYOFWEEK('" . $giorno1 . "') AS o_giornosettimana2 ";
              $sql .= "FROM cp_ordinecliente o, cp_dettaglioordine dt ";
              $sql .= "WHERE ";
              $sql .= "o.cliente = " . $idcliente . " ";
              $sql .= "AND o.id = dt.ordinecliente ";
              $sql .= "AND o.dataordine = DATE('" . $giorno1 . "') ";
              $sql .= "AND dt.quantita >= 0 AND dt.stato = 0 ";
              $sql .= "ORDER BY dt.dettaglioordine) t2 ";
              $sql .= "LEFT OUTER JOIN (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, ";
              $sql .= "d.sequenza AS s_sequenza, d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
              $sql .= "ISODAYOFWEEK('2024-06-23') AS s_giornosettimana2 ";
              $sql .= "FROM cp_schemadefault s, cp_dettaglioschema d ";
              $sql .= "WHERE ";
              $sql .= "s.cliente = " . $idcliente . " ";
              $sql .= "AND s.id = d.schematico ";
              $sql .= "AND s.datainizio <= DATE('" . $giorno1 . "') ";
              $sql .= "AND (s.datafine IS NULL OR DATE('" . $giorno1 . "') <= s.datafine) ";
              $sql .= "AND d.giornosettimana = `ISODAYOFWEEK`('" . $giorno1 . "') ";
              $sql .= "AND d.quantita >= 0 ";
              $sql .= "ORDER BY d.sequenza) t1 ON (t1.s_prodotto = t2.o_prodotto) ";
              $sql .= "HAVING t1.s_schema IS NULL) b, ";
              $sql .= "cp_prodotto p, cp_listinoprezzi l, cp_dettagliolistino dl,cp_dettagliolistinogruppi dg, cp_gruppoprodotti g, cp_cliente c \n";
              $sql .= "WHERE c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND l.id = dl.listino AND dl.prodotto = p.id AND dg.listino = l.id AND dg.gruppo = p.gruppo AND g.id = p.gruppo \n";
              $sql .= "GROUP BY gruppo \n HAVING quantita > 0 ";
              $result1 = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
              if (!$result1) {
                  header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
                  echo ("Error description: " . mysqli_error($db));
                  exit; // fine dello script php
              }
              while ($row1 = mysqli_fetch_array($result1)) {
                  // ora leggo i dati e formatto una tabellina con U.M | Q.tà | Prodotto | Gruppo |
                  ?>
                  <tr>
                      <td class="dettagli1">
                          <?= $row1["nomegruppo"] ?>
                      </td>
                      <td class="dettagli1">
                          <?= $row1["quantita"] ?>
                      </td>
                      <td class="dettagli1">
                          <?= $row1["unitamisura"] ?>
                      </td>
                  </tr>
                  <?php
              }
           mysqli_free_result($result1);
          ?>
      </table>

      <!-- fine modifiche 2024-08-18 -->

  </td>
<?php
      }
?>
                </tr>
            </table>
        </center>
    </div>
    <div name="menubasso" class="MenuComandi1">
     <!--   <a href="<?= $serverpath ?>mainpage.php">Back</a>  -->
    </div>
</body>
</html>

<?php
  // chiusura del database
  mysqli_close($db);
?>