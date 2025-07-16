<?php
include("dbconfig.php");

// DETTAGLI DEL LISTINO PREZZI PER I GRUPPI PRODOTTO - 2024-07-28

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

$examp = $_REQUEST["q"]; //query number

if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page']; // get the requested page
} else {
    $page = 1;
}
if (isset($_REQUEST['rows'])) {
    $limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
} else {
    $limit = 10;
}
if (isset($_REQUEST['sidx'])) {
    $sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
} else {
    $sidx = NULL;
}
if (isset($_REQUEST['sord'])) {
    $sord = $_REQUEST['sord']; // get the direction
} else {
    $sord = NULL;
}

if (!$sidx)
    $sidx = 1;

// search options
// IMPORTANT NOTE!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// this type of constructing is not recommendet
// it is only for demonstration
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
$wh = "";
$idlistinokey = $_REQUEST["idlistino"];

$searchOn = Strip($_REQUEST['_search']);
if ($searchOn == 'true') {
    $fld = Strip($_REQUEST['searchField']);
    if ($fld == 'id' || $fld == 'listino' || $fld == 'gruppo' || $fld == 'nomegruppo' || $fld == 'unitamisura' || $fld == 'prezzounitario' || $fld == 'annotazioni') {
        $fldata = Strip($_REQUEST['searchString']);
        $foper = Strip($_REQUEST['searchOper']);
        if ($fld == 'id') {
            $fld = 'id';
        } // qui mettiamo il nome in tabella se la ricerca non funziona con il nome alias
        // peswonalizzazione dei nomi delle colonne originali
        if ($fld == 'listino') {
            $fld = 'b.listino';
        }
        if ($fld == 'gruppo') {
            $fld = 'b.gruppo';
        }
        if ($fld == 'nomegruppo') {
            $fld = 'p.nomegruppo';
        }
        if ($fld == 'unitamisura') {
            $fld = 'b.unitamisura';
        }
        if ($fld == 'prezzounitario') {
            $fld = 'b.prezzounitario';
        }
        if ($fld == 'annotazioni') {
            $fld = 'b.Annotazioni';
        }
        // costruct where
        if (($foper == "in") || ($foper == "ni")) {
            if ($foper == "in") {
                $wh .= " AND ( INSTR('" . $fldata . "'," . $fld . ") > 0 ) ";
            } else {
                $wh .= " AND NOT(INSTR('" . $fldata . "'," . $fld . ") > 0) ";
            }
        } else {
            $wh .= " AND " . $fld;
        }
        switch ($foper) {
            case "bw":
                $fldata .= "%";
                $wh .= " LIKE '" . $fldata . "'";
                break;
            case "eq":
                if (is_numeric($fldata)) {
                    $wh .= " = " . $fldata;
                } else {
                    $wh .= " = '" . $fldata . "'";
                }
                break;
            case "ne":
                if (is_numeric($fldata)) {
                    $wh .= " <> " . $fldata;
                } else {
                    $wh .= " <> '" . $fldata . "'";
                }
                break;
            case "lt":
                if (is_numeric($fldata)) {
                    $wh .= " < " . $fldata;
                } else {
                    $wh .= " < '" . $fldata . "'";
                }
                break;
            case "le":
                if (is_numeric($fldata)) {
                    $wh .= " <= " . $fldata;
                } else {
                    $wh .= " <= '" . $fldata . "'";
                }
                break;
            case "gt":
                if (is_numeric($fldata)) {
                    $wh .= " > " . $fldata;
                } else {
                    $wh .= " > '" . $fldata . "'";
                }
                break;
            case "ge":
                if (is_numeric($fldata)) {
                    $wh .= " >= " . $fldata;
                } else {
                    $wh .= " >= '" . $fldata . "'";
                }
                break;
            case "ew":
                $wh .= " LIKE '%" . $fldata . "'";
                break;
            case "en":
                $wh .= " NOT LIKE '%" . $fldata . "'";
                break;
            case "bn":
                $fldata .= "%";
                $wh .= " NOT LIKE '" . $fldata . "'";
                break;
            case "cn":
                $wh .= " LIKE '%" . $fldata . "%'";
                break;
            case "nc":
                $wh .= " NOT LIKE '%" . $fldata . "%'";
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
            default:
                $wh = "";
        }

    }
}


// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");

switch ($examp) {

    case 5: // per ottenere i dati del cp_prodotto (per ottenere i dati delle combobox quando serve)
        $query = "SELECT g.id as id, g.NomeGruppo as gruppo ";
        $query .= "FROM cp_gruppoprodotti g ORDER BY g.NomeGruppo ";

        $risultato = mysqli_query($db, $query);
        $gruppo = "";
        $valoriGruppi = "";
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriGruppi !== "") {
                $valoriGruppi .= ";";
            };
            $valoriGruppi .= $row['id'] . ":" . $row['gruppo'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriGruppi;
        $s .= "</rows>";
        echo $s;
        break;

    case 6: // per ottenere i dati del le unità di misura sono voci fisse PZ:Pezzo;KG:Kilo;ML:Metro
        /*
        $result = mysqli_query($db, "SELECT id, CodiceIntermediario, Denominazione FROM cp_intermediario b ORDER BY Denominazione ");
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (6): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        $valoriInterm = "";
        // while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
        while ($row = mysqli_fetch_array($result)) {
            if ($valoriInterm !== '') {
                $valoriInterm .= ";";
            }
            $valoriInterm .= $row['id'] . ":" . $row['Denominazione'] . "-" . $row["CodiceIntermediario"];
        }
        */
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= "PZ:Pezzo;KG:Kilo;ML:metro";
        $s .= "</rows>";
        echo $s;
        break;

    case 10: // SELECT
        if (isset($idlistinokey)) {
            $wh .= " AND listino = $idlistinokey ";
        }
        $result = mysqli_query($db, "SELECT COUNT(*) AS count FROM cp_dettagliolistinogruppi b WHERE 1 = 1 " . $wh);

        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-1): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        // $row = mysqli_fetch_array($result,MYSQL_ASSOC);
        $row = mysqli_fetch_array($result);
        $count = $row['count'];

        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages)
            $page = $total_pages;
        $start = $limit * $page - $limit; // do not put $limit*($page - 1)
        if ($start < 0)
            $start = 0;
        $SQL = "SELECT b.id as id, b.listino as listino, b.gruppo as gruppo, ";
        $SQL .= "g.NomeGruppo as nomegruppo, b.unitamisura as unitamisura, b.prezzounitario as prezzounitario, b.annotazioni as annotazioni ";
        $SQL .= "FROM cp_dettagliolistinogruppi b LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = b.gruppo) ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }

        if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
            header("Content-type: application/xhtml+xml;charset=utf-8");
        } else {
            header("Content-type: text/xml;charset=utf-8");
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= "<page>" . xml_entities($page) . "</page>";
        $s .= "<total>" . xml_entities($total_pages) . "</total>";
        $s .= "<records>" . xml_entities($count) . "</records>";
        // be sure to put text data in CDATA
        // while($row = mysqli_fetch_array($result,MYSQL_ASSOC)) {
        while ($row = mysqli_fetch_array($result)) {
            $s .= "<row id='" . xml_entities($row['id']) . "'>";
            $s .= "<cell>" . xml_entities($row['id']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['listino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['gruppo']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomegruppo']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['unitamisura']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['prezzounitario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['annotazioni']) . "</cell>";
            $s .= "</row>";
        }
        $s .= "</rows>";
        echo $s;
        break;
    case 21: // INSERT
        // insert new row and response with new id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $idriga = numberOrNull($_REQUEST['idlistino']);
        $gruppo = numberOrNull(strtoupper($_REQUEST['gruppo']));
        $prezzo = numberOrNull(str_replace(",",".",$_REQUEST["prezzounitario"]));
        $unmis = quoteStr(($_REQUEST['unitamisura']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));

        $qrystr = "INSERT INTO cp_dettagliolistinogruppi (listino, gruppo, unitamisura, prezzounitario,annotazioni) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($idriga, $gruppo, $unmis, $prezzo, $annotazioni)";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db, $qrystr);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $id = mysqli_insert_id($db);
        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        // file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 22: // UPDATE
        // update current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];

        $idriga = numberOrNull($_REQUEST['idlistino']);
        $gruppo = numberOrNull(strtoupper($_REQUEST['gruppo']));
        $prezzo = numberOrNull(str_replace(",", ".", $_REQUEST["prezzounitario"]));
        $unmis = quoteStr(($_REQUEST['unitamisura']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));

        $qrystr = "UPDATE cp_dettagliolistinogruppi SET gruppo = $gruppo, unitamisura = $unmis, ";
        $qrystr .= "prezzounitario = $prezzo, annotazioni = $annotazioni ";
        $qrystr .= "WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db, $qrystr);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (22): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        // file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 23: // DELETE
        // delete current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $qrystr = "DELETE FROM cp_dettagliolistinogruppi WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db, $qrystr);
        $errore = mysqli_error($db);
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
    case 24: // CURRENT ROW - PAGE TEST
        // current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $idriga = numberOrNull($_REQUEST['idlistino']);
        $gruppo = numberOrNull(strtoupper($_REQUEST['gruppo']));
        $prezzo = numberOrNull(str_replace(",", ".", $_REQUEST["prezzounitario"]));
        $unmis = quoteStr(($_REQUEST['unitamisura']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));

        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        //--> file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 50: // CSV
        $result = mysqli_query($db, "SELECT COUNT(*) AS count FROM cp_dettagliolistinogruppi b WHERE 1 = 1 " . $wh);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        // $row = mysqli_fetch_array($result,MYSQL_ASSOC);
        $row = mysqli_fetch_array($result);
        $count = $row['count'];

        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
        if ($page > $total_pages)
            $page = $total_pages;
        $start = $limit * $page - $limit; // do not put $limit*($page - 1)
        if ($start < 0)
            $start = 0;
        $SQL = "SELECT b.id as id, b.listino as listino, b.gruppo as gruppo, ";
        $SQL .= "g.NomeGruppo as nomegruppo, b.unitamisura as unitamisura, b.prezzounitario as prezzounitario, b.annotazioni as annotazioni ";
        $SQL .= "FROM cp_dettagliolistinogruppi b LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = b.gruppo) ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord ; // . " LIMIT " . $start . " , " . $limit;

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-2): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
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
        while ($row = mysqli_fetch_array($result /*,MYSQL_ASSOC*/)) {
            $s = "";
            foreach ($row as $elem) {
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

function xml_entities($string)
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function Strip($value)
{
    /*
    if (get_magic_quotes_gpc() != 0) {
        if (is_array($value))
            if (array_is_associative($value)) {
                foreach ($value as $k => $v)
                    $tmp_val[$k] = stripslashes($v);
                $value = $tmp_val;
            } else
                for ($j = 0; $j < sizeof($value); $j++)
                    $value[$j] = stripslashes($value[$j]);
        else
            $value = stripslashes($value);
    }
    */
    return $value;
}

function array_is_associative($array)
{
    if (is_array($array) && !empty($array)) {
        for ($iterator = count($array) - 1; $iterator; $iterator--) {
            if (!array_key_exists($iterator, $array)) {
                return true;
            }
        }
        return !array_key_exists(0, $array);
    }
    return false;
}

function quoteStr($testo)
{
    if ($testo !== '') {
        return "'" . str_replace("'", "''", $testo) . "'"; // evitare che gli apici restino singoli, potrebbero causare hacking del MySQL
    } else {
        return "NULL";
    }
}

function numberOrNull($testo)
{
    if (is_numeric($testo)) {
        return $testo;
    } else {
        return "NULL";
    }
}

function DateFormatted($date, $format)
{
    // ritorna NULL se non è una data valida
    // in base al formato costruisce la corrispondente data e verifica se è valida
    // $d = DateTime::createFromFormat($format, $date);
    if (strtotime($date) == -1) {
        return "NULL";
    } else {
        if ($format == "dd/mm/yyyy") {
            $gg = substr($date, 0, 2);
            $mm = substr($date, 3, 2);
            $aa = substr($date, 6, 4);
            if (checkdate($mm, $gg, $aa)) {
                return "Date('$aa-$mm-$gg')";
            } else
                return "NULL"; // formato data errato
        }
        if ($format == "mm/dd/yyyy") {
            $mm = substr($date, 0, 2);
            $gg = substr($date, 3, 2);
            $aa = substr($date, 6, 4);
            if (checkdate($mm, $gg, $aa)) {
                return "Date('$aa-$mm-$gg')";
            } else
                return "NULL"; // formato data errato
        }
        if ($format == "yyyy-mm-dd") {
            $aa = substr($date, 0, 4);
            $mm = substr($date, 5, 2);
            $gg = substr($date, 8, 2);
            if (checkdate($mm, $gg, $aa)) {
                return "Date('$aa-$mm-$gg')";
            } else
                return "NULL"; // formato data errato
        }
        return "NULL"; // altrimenti null di default
    }
}

?>
