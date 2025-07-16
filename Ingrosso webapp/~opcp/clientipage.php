<?php
include("dbconfig.php");

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

$examp = $_REQUEST["q"]; //query number

if (isset($_REQUEST['page']))
{
    $page = $_REQUEST['page']; // get the requested page
} else
{
    $page = 1;
}
if (isset($_REQUEST['rows']))
{
    $limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
} else
{
    $limit = 10;
}
if (isset($_REQUEST['sidx']))
{
   $sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
} else
{
   $sidx = NULL;
}
if (isset($_REQUEST['sord']))
{
   $sord = $_REQUEST['sord']; // get the direction
} else
{
   $sord = NULL;
}

if(!$sidx) $sidx =1;

// search options
// IMPORTANT NOTE!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// this type of constructing is not recommendet
// it is only for demonstration
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
$wh = "";

$searchOn = Strip($_REQUEST['_search']);
if($searchOn=='true') {
    $fld = Strip($_REQUEST['searchField']);
    if( $fld=='id' || $fld =='codicecliente' || $fld=='denominazione' || $fld=='nomebreve' || $fld=='annotazioni' || $fld=='listino' || $fld=='intermediario'
        || $fld == 'abilitato' || $fld == 'rifwhatsapp') {
        $fldata = Strip($_REQUEST['searchString']);
        $foper = Strip($_REQUEST['searchOper']);
        if ($fld == 'id') { $fld = 'id'; } // qui mettiamo il nome in tabella se la ricerca non funziona con il nome alias
        // peswonalizzazione dei nomi delle colonne originali
        if ($fld == 'codicecliente') {
            $fld = 'b.CodiceCliente';
        }
        if ($fld == 'denominazione') {
            $fld = 'b.Denominazione';
        }
        if ($fld == 'nomebreve') {
            $fld = 'b.NomeBreve';
        }
        if ($fld == 'annotazioni') {
            $fld = 'b.Annotazioni';
        }
        // costruct where
        if (($foper == "in") || ($foper == "ni")){
            if ($foper == "in")
            {
                $wh .= " AND ( INSTR('".$fldata . "',".$fld.") > 0 ) ";
            }
            else
            {
                $wh .= " AND NOT(INSTR('".$fldata . "',".$fld.") > 0) ";
            }
        }
        else
        {
            $wh .= " AND ".$fld;
        }
        switch ($foper) {
            case "bw":
                $fldata .= "%";
                $wh .= " LIKE '".$fldata."'";
                break;
            case "eq":
                if(is_numeric($fldata)) {
                    $wh .= " = ".$fldata;
                } else {
                    $wh .= " = '".$fldata."'";
                }
                break;
            case "ne":
                if(is_numeric($fldata)) {
                    $wh .= " <> ".$fldata;
                } else {
                    $wh .= " <> '".$fldata."'";
                }
                break;
            case "lt":
                if(is_numeric($fldata)) {
                    $wh .= " < ".$fldata;
                } else {
                    $wh .= " < '".$fldata."'";
                }
                break;
            case "le":
                if(is_numeric($fldata)) {
                    $wh .= " <= ".$fldata;
                } else {
                    $wh .= " <= '".$fldata."'";
                }
                break;
            case "gt":
                if(is_numeric($fldata)) {
                    $wh .= " > ".$fldata;
                } else {
                    $wh .= " > '".$fldata."'";
                }
                break;
            case "ge":
                if(is_numeric($fldata)) {
                    $wh .= " >= ".$fldata;
                } else {
                    $wh .= " >= '".$fldata."'";
                }
                break;
            case "ew":
                $wh .= " LIKE '%".$fldata."'";
                break;
            case "en":
                $wh .= " NOT LIKE '%".$fldata."'";
                break;
            case "bn":
                $fldata .= "%";
                $wh .= " NOT LIKE '".$fldata."'";
                break;
            case "cn":
                $wh .= " LIKE '%".$fldata."%'";
                break;
            case "nc":
                $wh .= " NOT LIKE '%".$fldata."%'";
                break;
            case "nu":
                $wh .= " IS NULL ";
                break;
            case "nn":
                $wh .= " IS NOT NULL ";
                break;
            case "in":
                break;
            case "ni":
                break;
            default :
                $wh = "";
        }

    }
}


// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno())
{
   header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
   exit; // fine dello script php
}

mysqli_select_db($db,$database) or die("Error conecting to db.");

switch ($examp) {

    case 5: // per ottenere i dati del cp_listino (per ottenere i dati delle combobox quando serve)
        $result = mysqli_query($db,"SELECT id, tipo FROM cp_listinoprezzi b ORDER BY id ");
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (5): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $valoriList = "";
        // while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
        while ($row = mysqli_fetch_array($result))
        {
            if ($valoriList !== '')
            {
               $valoriList .= ";" ;
            }
            $valoriList .= $row['id'] . ":" . $row['tipo'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriList;
        $s .= "</rows>";
        echo $s;
        break;

    case 6: // per ottenere i dati del cp_intermediario
        $result = mysqli_query($db,"SELECT id, CodiceIntermediario, Denominazione FROM cp_intermediario b ORDER BY Denominazione ");
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (6): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        $valoriInterm = "";
        // while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
        while ($row = mysqli_fetch_array($result))
        {
            if ($valoriInterm !== '')
            {
                $valoriInterm .= ";" ;
            }
            $valoriInterm .= $row['id'] . ":" . $row['Denominazione'] . "-" . $row["CodiceIntermediario"];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriInterm;
        $s .= "</rows>";
        echo $s;
        break;

    case 10:  // SELECT
        $result = mysqli_query($db,"SELECT COUNT(*) AS count FROM cp_cliente b WHERE 1 = 1 ".$wh);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        // $row = mysqli_fetch_array($result,MYSQL_ASSOC);
        $row = mysqli_fetch_array($result);
        $count = $row['count'];

        if( $count >0 ) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $SQL = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, ";
        $SQL .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino, ";
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario, b.email as email, b.rifwhatsapp as rifwhatsapp, b.sequenza as sequenza ";// 05/08/2024
        $SQL .= ", rc.perc_b as p_b, rc.perc_n as p_n "; // 2024-07-08 gestione del rapporto di consegna
        $SQL .= ", b.abilitato as abilitato "; // 2024-10-11 - Aggiunti Riferimento WhatsApp e Flag ABilitato 1/0 == Sì/No
        $SQL .=	"FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
        $SQL .= "LEFT OUTER JOIN cp_rapportoconsegna rc ON (rc.cliente = b.id) "; // 2024-07-08 gestione del rapporto di consegna
        $SQL .= "WHERE 1 = 1 ".$wh." ORDER BY ".$sidx." ". $sord." LIMIT ".$start." , ".$limit;
        $result = mysqli_query( $db, $SQL ) or die("Couldn t execute query.".mysqli_error($db));
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8"); } else {
        header("Content-type: text/xml;charset=utf-8");
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= "<page>".xml_entities($page)."</page>";
        $s .= "<total>".xml_entities($total_pages)."</total>";
        $s .= "<records>".xml_entities($count)."</records>";
        // be sure to put text data in CDATA
        // while($row = mysqli_fetch_array($result,MYSQL_ASSOC)) {
        while ($row = mysqli_fetch_array($result)){
            $s .= "<row id='" . xml_entities($row['id']) . "'>";
            $s .= "<cell>" . xml_entities($row['id']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['codicecliente']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['denominazione']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomebreve']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['listino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['tipolistino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['intermediario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeintermediario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['annotazioni']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['email']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['rifwhatsapp']) . "</cell>"; // 2024-10-11
            $s .= "<cell>" . xml_entities($row['sequenza']) . "</cell>";
            // 2024-07-08 - gestione del rapporto di consegna
            $s .= "<cell>" . xml_entities($row['p_b']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['p_n']) . "</cell>";
            //
            $s .= "<cell>" . xml_entities($row['abilitato']) . "</cell>"; // 2024-10-11
            // fine 2024-07-08
            $s .= "</row>";
        }
        $s .= "</rows>";
        echo $s;
        break;
    case 21:  // INSERT
        // insert new row and response with new id
        $msg = "";
        foreach ( $_REQUEST as $k => $v ){
           $msg .= "$k = $v ; ";
        }
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediario']));
        $email = quoteStr(($_REQUEST['email'])); // 05/08/2024
        $rifwhatsapp = quoteStr(($_REQUEST['rifwhatsapp'])); // 2024-10-11
        $abilitato = numberOrNull($_REQUEST['abilitato']); // 2024-10-11
        $sequenza = numberOrNull(($_REQUEST['sequenza'])); // 05/08/2024
        // 2024-07-08
        $perc_b = numberOrNull($_REQUEST['p_b']);
        $perc_n = numberOrNull($_REQUEST['p_n']);
        if ($perc_b == "NULL") {
            $perc_b = 100.0;
            $perc_n = 0.00;
        }
        if ($perc_n == "NULL") {
            $perc_n = 100.00 - $perc_b;
        }
        // se sono superiori a 100000 non vanno alterati altrimenti va calcolato il valore da registrare
        if (isset($perc_b) && ($perc_b <= 100) && ($perc_b >= 0)) {
            // è una cifra decimale non superiore a 100.000 (cento) quindi lo moltiplico per 1000 per ottenere una cifra intera
            // poi scelgo a caso un valore q fra 3 e 5000 da moltiplicare a 123456 e da aggiungere al valore di perc_b ricalcolato
            $perc_n = 100 - $perc_b; // viene calcolato in base a perc_b
            $perc_b = (int) ($perc_b * 1000);
            $q = rand(3, 5000);
            $perc_b = $perc_b + $q * 123456; // cerco di rientrare nei 32 bit di un integer
        };
        if (isset($perc_n) && ($perc_n <= 100)) {
            // è una cifra decimale non superiore a 100.000 (cento) quindi lo moltiplico per 1000 per ottenere una cifra intera
            // poi scelgo a caso un valore q fra 3 e 5000 da moltiplicare a 123456 e da aggiungere al valore di perc_b ricalcolato
            $perc_n = (int) ($perc_n * 1000);
            $q = rand(3, 5000);
            $perc_n = $perc_n + $q * 123456; // cerco di rientrare nei 32 bit di un integer
        }
        // fine 2024-07-08

        // 05/08/2024 - attenzione: se non ha numero di sequenza va messo l'ultimo disponibile + 1 (troncato all'intero) altrimenti se è incluso fra minimo e massimo e
        // esiste già allora vanno spostati di 1 verso l'alto tutti quelli da questo numero di sequenza in sù prima di inserire il record nuovo
        if ($sequenza == "NULL"){
            // determino il prossimo numero di sequenza
            $qrystr = "SELECT FLOOR(IFNULL(MAX(sequenza),0) + 1) AS nuovasequenza FROM cp_cliente ";
            $result = mysqli_query($db,$qrystr);
            if (!$result) {
              header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
              echo ("Error description: " . mysqli_error($db));
              exit; // fine dello script php
            }
            if ($row = mysqli_fetch_assoc($result)){
                $sequenza = $row["nuovasequenza"];
            }
            if ($sequenza == "NULL"){
                $sequenza = 1;
            }
            mysqli_free_result($result);
        } else {
            // verifico se esiste già: se esiste allora vanno spostati verso l'alto di +1 tutti i numero di sequenza da $sequenza in sù
            $qrystr = "SELECT COUNT(*) AS presenze FROM cp_cliente WHERE sequenza = " . $sequenza;
            $result = mysqli_query($db,$qrystr);
            if (!$result) {
              header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
              echo ("Error description: " . mysqli_error($db));
              exit; // fine dello script php
            }

            $conta = -1;
            if ($row = mysqli_fetch_assoc($result)){
                $conta = $row["presenze"];
            }
            mysqli_free_result($result);

            if ($conta > 0){
                // qui facciamo spostare tutte le sequenze da $sequenza in sù di +1
                $qrystr = "UPDATE cp_cliente SET sequenza = sequenza + 1 WHERE sequenza >= " . $sequenza;
                $result = mysqli_query($db,$qrystr);
                if (!$result) {
                  header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
                  echo ("Error description: " . mysqli_error($db));
                  exit; // fine dello script php
                }
            }

        }
        // fine 05/08/2024
        $qrystr = "INSERT INTO cp_cliente (CodiceCliente, Denominazione, NomeBreve, Annotazioni, listino, intermediario,email,sequenza,rifwhatsapp,abilitato) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($codicecliente, $denominazione, $nomebreve, $annotazioni, $listino, $intermediario,$email,$sequenza,$rifwhatsapp,$abilitato)";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $id = mysqli_insert_id($db);
        // 2024-07-08 - sezione per gestire il rapportoconsegna
        $idcliente = $id;
        $adesso = "DATE(" . quoteStr(date("Y-m-d")) . ")";
        $qrystr = "INSERT INTO cp_rapportoconsegna (cliente,datainizio,datafine,perc_b,perc_n,abilitato) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($idcliente, $adesso, NULL, $perc_b, $perc_n, 1)";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db, $qrystr);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $idrappcons = mysqli_insert_id($db);
        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        // file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 22:	// UPDATE
        // update current row and response with old id
        $msg = "";
        foreach ( $_REQUEST as $k => $v ){
           $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediario']));
        $email = quoteStr(($_REQUEST['email'])); // 05/08/2024
        $rifwhatsapp = quoteStr(($_REQUEST['rifwhatsapp'])); // 2024-10-11
        $abilitato = numberOrNull($_REQUEST['abilitato']); // 2024-10-11
        $sequenza = numberOrNull(($_REQUEST['sequenza'])); // 05/08/2024
        // 2024-07-08
        $perc_b = numberOrNull($_REQUEST['p_b']);
        $perc_n = numberOrNull($_REQUEST['p_n']);
        if ($perc_b == "NULL") {
            $perc_b = 100.0;
            $perc_n = 0.00;
        }
        if ($perc_n == "NULL"){
            $perc_n = 100.00 - $perc_b;
        }
        // se perc_b > 100000 allora il valore precedente non va variato
        $flgValore = ($perc_b > 100000 || !($perc_b <= 100 && $perc_b >= 0));
        // se sono superiori a 100000 non vanno alterati altrimenti va calcolato il valore da registrare
        if (isset($perc_b) && ($perc_b <= 100)) {
            // è una cifra decimale non superiore a 100.000 (cento) quindi lo moltiplico per 1000 per ottenere una cifra intera
            // poi scelgo a caso un valore q fra 3 e 5000 da moltiplicare a 123456 e da aggiungere al valore di perc_b ricalcolato
            $perc_n = 100 - $perc_b; // viene calcolato in base a perc_b
            $perc_b = (int) ($perc_b * 1000);
            $q = rand(3, 5000);
            $perc_b = $perc_b + $q * 123456; // cerco di rientrare nei 32 bit di un integer
        };
        if ((isset($perc_n)) && ($perc_n <= 100)) {
            // è una cifra decimale non superiore a 100.000 (cento) quindi lo moltiplico per 1000 per ottenere una cifra intera
            // poi scelgo a caso un valore q fra 3 e 5000 da moltiplicare a 123456 e da aggiungere al valore di perc_b ricalcolato
            $perc_n = (int) ($perc_n * 1000);
            $q = rand(3, 5000);
            $perc_n = $perc_n + $q * 123456; // cerco di rientrare nei 32 bit di un integer
        }
        // fine 2024-07-08
        // 05/08/2024
        if ($sequenza == "NULL") {
          // determino il prossimo numero di sequenza
          $qrystr = "SELECT FLOOR(IFNULL(MAX(sequenza),0) + 1) AS nuovasequenza FROM cp_cliente ";
          $result = mysqli_query($db, $qrystr);
          if (!$result) {
              header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
              echo ("Error description: " . mysqli_error($db));
              exit; // fine dello script php
          }
          if ($row = mysqli_fetch_assoc($result)) {
              $sequenza = $row["nuovasequenza"];
          }
          if ($sequenza == "NULL") {
              $sequenza = 1;
          }
          mysqli_free_result($result);
        } else {
          $qrystr = "SELECT COUNT(*) AS presenze FROM cp_cliente WHERE sequenza = " . $sequenza . " AND id != ". $id;
          $result = mysqli_query($db, $qrystr);
          if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
          }

          $conta = -1;
          if ($row = mysqli_fetch_assoc($result)) {
            $conta = $row["presenze"];
          }
          mysqli_free_result($result);

          if ($conta > 0) {
            // qui facciamo spostare tutte le sequenze da $sequenza in sù di +1
            $qrystr = "UPDATE cp_cliente SET sequenza = sequenza + 1 WHERE sequenza >= " . $sequenza;
            $result = mysqli_query($db, $qrystr);
            if (!$result) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
                echo ("Error description: " . mysqli_error($db));
                exit; // fine dello script php
            }
          }
        }
        // fine 05/08/2024
        $qrystr = "UPDATE cp_cliente SET CodiceCliente = $codicecliente, Denominazione = $denominazione, ";
        $qrystr .= "NomeBreve = $nomebreve, Annotazioni = $annotazioni, listino = $listino, intermediario = $intermediario, email = $email ";
        $qrystr .= ", sequenza = $sequenza ";
        $qrystr .= ", rifwhatsapp = $rifwhatsapp, abilitato = $abilitato "; // 2024-10-11
        $qrystr .= "WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (22): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        // 2024-07-08 - aggiornamento del record in rapportoconsegna
        if (!$flgValore){ // solo se il valore di perc_b è variato fra 0 e 100 allora si fa l'aggiornamento delle percentuali criptate
         $qrystr = "UPDATE cp_rapportoconsegna SET perc_b = $perc_b, perc_n = $perc_n ";
         $qrystr .= "WHERE cliente = $id";
         $msg .= "\n$qrystr";
         $result = mysqli_query($db, $qrystr);
         if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (22): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
         }
        }
        // fine 2024-07-08
        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        // file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 23:	// DELETE
        // delete current row and response with old id
        $errore = ""; // 2024-07-08
        $msg = "";
        foreach ( $_REQUEST as $k => $v ){
           $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        // 2024-07-08 devo cancellare prima il record in rapportoconsegna
        $qrystr = "DELETE FROM cp_rapportoconsegna WHERE cliente = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db, $qrystr);
        $errore .= "(1) " . mysqli_error($db);
        // fine 2024-07-08 e poi cancello il record in clienti
        $qrystr = "DELETE FROM cp_cliente WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
        $errore .= "(2) " . mysqli_error($db);
        /* viene già gestito
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error: " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        */
        echo "{\"id\" : \"$id\", \"error\":\"$errore\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        //--> file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;

    case 24:	// CURRENT ROW - PAGE TEST
        // current row and response with old id
        $msg = "";
        foreach ( $_REQUEST as $k => $v ){
           $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediario']));
        $email = quoteStr(($_REQUEST['email'])); // 05/08/2024
        $rifwhatsapp = quoteStr(($_REQUEST['rifwhatsapp'])); // 2024-10-11
        $abilitato = numberOrNull($_REQUEST['abilitato']); // 2024-10-11
        $sequenza = numberOrNull(($_REQUEST['sequenza'])); // 05/08/2024

        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        //--> file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;

    case 43: // 2024-07-08 - calcola le due percentuali perc_b e perc_n a partire dai valori in rapportoconsegna
        // ogni cifra viene calcolata come perc_b % 123456 = A e poi si divide per 1000 ed otteniamo la cifra della percentuale

        $id = $_REQUEST["id"]; // cliente corrispondente

        $SQL = "SELECT b.perc_b as perc_b, b.perc_n as perc_n "; // 2024-07-08 gestione del rapporto di consegna
        $SQL .= "FROM cp_rapportoconsegna b ";
        $SQL .= "WHERE cliente = $id ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $perc_b = null;
        $perc_n = null;
        if ($row = mysqli_fetch_assoc($result)) {
            $perc_b = $row["perc_b"];
            $perc_n = $row["perc_n"];
            if ($perc_b > 100.000) {
                $perc_b = $perc_b % 123456;
                $perc_b = $perc_b / 1000;
            }
            if ($perc_n > 100.000) {
                $perc_n = $perc_n % 123456;
                $perc_n = $perc_n / 1000;
            }
        }
        header("Content-type: application/json;");
        echo "{\"perc_b\" : \"$perc_b\" , \"perc_n\" : \"$perc_n\" , \"cliente\" : \"$id\"}";
        break;

    case 50: // CSV
        $result = mysqli_query($db,"SELECT COUNT(*) AS count FROM cp_cliente b WHERE 1 = 1 ".$wh);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        // $row = mysqli_fetch_array($result,MYSQL_ASSOC);
        $row = mysqli_fetch_array($result);
        $count = $row['count'];

        if( $count >0 ) {
            $total_pages = ceil($count/$limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages) $page=$total_pages;
        $start = $limit*$page - $limit; // do not put $limit*($page - 1)
        if ($start<0) $start = 0;
        $SQL = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, ";
        $SQL .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino, ";
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario ";
        $SQL .= ", b.rifwhatsapp as rifwhatsapp, b.abilitato as abilitato "; // 2024-10-11
        $SQL .= "FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord; //  . " LIMIT " . $start . " , " . $limit;

        $result = mysqli_query( $db, $SQL ) or die("Couldn t execute query.".mysqli_error($db));
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-2): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        // indicare che stiamo inviando un CSV che dovrà essere salvato o letto con Excel
        // header("Content-type: text/HTML");
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=clienti.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        //
        $s = "";
        $fields = mysqli_fetch_fields($result);
        foreach ($fields as $val) {
          $s .= '"' . $val->name . '"' . ";";
        }
        $s .= "\r\n";
        echo $s;
        $s = "";
        while($row = mysqli_fetch_array($result/*,MYSQL_ASSOC*/)) {
            $s = "";
            foreach($row as $elem)
            {
               $s .= $elem . ";";
            }
            $s .= "\r\n";
            echo $s;
        }
        mysqli_free_result($result);
        break;
}
mysqli_close($db);

// Questa funzione normalizza i chars per essere inseriti nel XML ed è più completo per UTF-8

function xml_entities($string) {
   return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function Strip($value)
{
    /*
    if(get_magic_quotes_gpc() != 0)
    {
        if(is_array($value))
            if ( array_is_associative($value) )
            {
                foreach( $value as $k=>$v)
                    $tmp_val[$k] = stripslashes($v);
                $value = $tmp_val;
            }
            else
                for($j = 0; $j < sizeof($value); $j++)
                    $value[$j] = stripslashes($value[$j]);
        else
            $value = stripslashes($value);
    }
    */
    return $value;
}

function array_is_associative ($array)
{
    if ( is_array($array) && ! empty($array) )
    {
        for ( $iterator = count($array) - 1; $iterator; $iterator-- )
        {
            if ( ! array_key_exists($iterator, $array) ) { return true; }
        }
        return ! array_key_exists(0, $array);
    }
    return false;
}

function quoteStr($testo)
{
    if ( $testo !== '')
    {
       return "'" . str_replace("'","''",$testo) . "'"; // evitare che gli apici restino singoli, potrebbero causare hacking del MySQL
    }
    else
    {
       return "NULL";
    }
}

function numberOrNull($testo)
{
    if ( is_numeric($testo) )
    {
       return $testo;
    }
    else
    {
        return "NULL";
    }
}

function DateFormatted($date,$format)
{
   // ritorna NULL se non è una data valida
   // in base al formato costruisce la corrispondente data e verifica se è valida
   // $d = DateTime::createFromFormat($format, $date);
   if (strtotime($date) == -1)
   {
       return "NULL";
   } else
   {
      if ($format == "dd/mm/yyyy")
      {
         $gg = substr($date,0,2);
         $mm = substr($date,3,2);
         $aa = substr($date,6,4);
         if (checkdate($mm,$gg,$aa))
         {
             return "Date('$aa-$mm-$gg')";
         }
         else
             return "NULL"; // formato data errato
      }
      if ($format == "mm/dd/yyyy")
      {
         $mm = substr($date,0,2);
         $gg = substr($date,3,2);
         $aa = substr($date,6,4);
         if (checkdate($mm,$gg,$aa))
         {
             return "Date('$aa-$mm-$gg')";
         }
         else
             return "NULL"; // formato data errato
      }
      if ($format == "yyyy-mm-dd")
      {
         $aa = substr($date,0,4);
         $mm = substr($date,5,2);
         $gg = substr($date,8,2);
         if (checkdate($mm,$gg,$aa))
         {
             return "Date('$aa-$mm-$gg')";
         }
         else
             return "NULL"; // formato data errato
      }
      return "NULL"; // altrimenti null di default
   }
}

?>
