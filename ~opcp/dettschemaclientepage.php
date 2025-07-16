<?php
/*  PAGINA PER GESTIRE I DATI DETTAGLI DELL'ORDINE DEL CLIENTE - è diverso da ordineclientepage.php che è il suo MASTER - 22/06/2024 - Robert Gasperoni */

include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}

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
$idschemakey = $_REQUEST["idschema"];
$idgiornokey = $_REQUEST["idgiorno"];

$searchOn = Strip($_REQUEST['_search']);
if ($searchOn == 'true') {
    $fld = Strip($_REQUEST['searchField']);
    if (
        $fld == 'id' || $fld == 'schema' || $fld == 'gs' || $fld == 'giornosettimana' || $fld == 'sequenza' || $fld == 'prodotto' || $fld == 'nomeprodotto'
        || $fld == 'quantita' || $fld == 'unitamisura' ) {
        $fldata = Strip($_REQUEST['searchString']);
        $foper = Strip($_REQUEST['searchOper']);
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

if (isset($idschemakey))
{
    $wh .= " AND schematico = " . $idschemakey;
}
if (isset($idgiornokey)) {
    $wh .= " AND giornosettimana = " . $idgiornokey;
}

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword)
    or die("Connection Error: " . mysqli_error($db));

mysqli_select_db($db, $database) or die("Error conecting to db.");

switch ($examp) {
    case 4: // i giorni della settimana
        $valoriGS = "1:Lunedì;2:Martedì;3:Mercoledì;4:Giovedì;5:Venerdì;6:Sabato;7=Domenica";

        $query = "SELECT s.id as id, s.giorno AS giorno ";
        $query .= "FROM cp_giornosettimana s ORDER BY s.id ";

        $risultato = mysqli_query($db, $query);
        $valoriGs = "";
        // $dati = mysqli_fetch_all($risultato);
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriGs !== '') {
                $valoriGs .= ";";
            }
            $valoriGs .= $row['id'] . ":" . $row['giorno'];
        }

        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriGs;
        $s .= "</rows>";
        echo $s;
        break;

    case 5: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella schemadefault e listinoprezzi ottengo gli elementi per il codice pagamento (esempio di query)

        $query  = "SELECT s.id as id, s.NomeBreve AS nomeresponsabile ";
        $query .= "FROM cp_responsabile s ORDER BY s.NomeBreve ";

        $risultato = mysqli_query($db, $query);
        $valoriResp = "";
        // $dati = mysqli_fetch_all($risultato);
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriResp !== '') {
                $valoriResp .= ";";
            }
            $valoriResp .= $row['id'] . ":" . $row['nomeresponsabile'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriResp;
        $s .= "</rows>";
        echo $s;
        break;

    case 6: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella cp_statoordine

        $query = "SELECT p.id as id, p.descrizionebreve AS descrizione, g.NomeGruppo as gruppo ";
        $query .= "FROM cp_prodotto p LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = p.gruppo)  ORDER BY gruppo, descrizione ";

        $risultato = mysqli_query($db, $query);
        $gruppo = "";
        $valoriProdotti = "";
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriProdotti !== '') {
                $valoriProdotti .= ";";
            }
            if ($row["gruppo"] != $gruppo){
                $gruppo = $row["gruppo"];
                $valoriProdotti .= ":-- " . $gruppo . " --;";

            }
            $valoriProdotti .= $row['id'] . ":" . $row['descrizione'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriProdotti;
        $s .= "</rows>";
        echo $s;
        break;

    case 7: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella tpagam ottengo gli elementi per il codice pagamento (esempio di query)
        $result = mysqli_query($db, "SELECT SiglaProv, NomeProvincia, Zona FROM provincia b ORDER BY SiglaProv ");
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (5): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $valoriProv = "";
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
            if ($valoriProv !== '') {
                $valoriProv .= ";";
            }
            $valoriProv .= $row['SiglaProv'] . ":" . $row['NomeProvincia'] . " - " . $row['Zona'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriProv;
        $s .= "</rows>";
        echo $s;
        break;

    case 10: // SELECT
        $msg = "";
        $sql = "SELECT COUNT(*) AS count FROM cp_dettaglioschema b WHERE 1 = 1 " . $wh;
        $result = mysqli_query($db, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
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
        // ordinare per dettaglioordine se non è indicato diversamente
        if ($sidx == "id"){
            $sidx = "b.giornosettimana, b.sequenza";
        }
        $SQL  = "SELECT b.id as id, b.schematico as schematico, b.giornosettimana as giornosettimana, g.giorno as nomegs, b.sequenza as sequenza, b.prodotto as prodotto, ";
        $SQL .= "p.descrizionebreve as nomeprodotto, ";
        $SQL .= "b.quantita AS quantita, b.unitamisura as unitamisura ";
        $SQL .= "FROM cp_dettaglioschema b ";
        $SQL .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = b.prodotto) ";
        $SQL .= "LEFT OUTER JOIN cp_giornosettimana g ON (g.id = b.giornosettimana) ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));

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
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            // qui vanno fatti i calcoli e non fuori dal loop
            $s .= "<row id='" . xml_entities($row['id']) . "'>";
            $s .= "<cell>" . xml_entities($row['id']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['schematico']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['giornosettimana']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomegs']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['sequenza']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['prodotto']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeprodotto']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['quantita']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['unitamisura']) . "</cell>";
            $s .= "</row>";
        }
        $s .= "</rows>";
        echo $s;
        $msg .= "$s\r\n";
        break;

    case 11: // SELECT per gironi settimana da cp_giornosettimana
        $msg = "";
        $sql = "SELECT COUNT(*) AS count FROM cp_giornosettimana b WHERE 1 = 1 " . $wh;
        $result = mysqli_query($db, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
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
        // ordinare per dettaglioordine se non è indicato diversamente
        $SQL = "SELECT b.id as id, b.giorno as giorno ";
        $SQL .= "FROM cp_giornosettimana b ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));

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
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            // qui vanno fatti i calcoli e non fuori dal loop
            $s .= "<row id='" . xml_entities($row['id']) . "'>";
            $s .= "<cell>" . xml_entities($row['id']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['giorno']) . "</cell>";
            $s .= "</row>";
        }
        $s .= "</rows>";
        echo $s;
        $msg .= "$s\r\n";
        break;

    case 21: // INSERT
        // insert new row and response with new id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $idschema = NumberOrNull(strtoupper($_REQUEST['idschema']));
        $idschema = NumberOrNull(strtoupper($_REQUEST['schema']));
        $giornosett = NumberOrNull(strtoupper($_REQUEST['giornosettimana']));
        $sequenza = NumberOrNull(strtoupper($_REQUEST['sequenza']));
        $prodotto = NumberOrNull(strtoupper($_REQUEST['prodotto']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));

        $nomeprod = "";
        $codprod = "";
        $gruppoprod = 0;
        $unmis = "";

        $SQL = "SELECT p.codiceprodotto as codiceprodotto, p.gruppo as gruppo, p.unitamisura as unmis, p.descrizionebreve as nomeprodotto FROM cp_prodotto p WHERE p.id = $prodotto ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $nomeprod = quoteStr($row['nomeprodotto']);
            $codprod = quoteStr($row['codiceprodotto']);
            $gruppoprod = $row['gruppo'];
            $unmis =quoteStr($row['unmis']);
        } else {
            echo "{\"id\" : \"0\", \"errore\" : \"Il Prodotto $prodotto non esiste!!!\"}";
            die("Prodotto assente");
        }
        mysqli_free_result($result);


        // ora possiamo effettuare le operazioni di insert
        $qrystr = "INSERT INTO cp_dettaglioschema (schematico, giornosettimana, sequenza, prodotto, quantita, unitamisura) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($idschema, $giornosett, $sequenza, $prodotto, $quantita, $unmis)";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);
        $id = mysqli_insert_id($db);
        echo "{\"id\" : \"$id\"}";
        break;

    case 22: // UPDATE
        // update current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $idriga = NumberOrNull(strtoupper($_REQUEST['id']));
        $idschema = NumberOrNull(strtoupper($_REQUEST['idschema']));
        $idschema = NumberOrNull(strtoupper($_REQUEST['schema']));
        $giornosett = NumberOrNull(strtoupper($_REQUEST['giornosettimana']));
        $sequenza = NumberOrNull(strtoupper($_REQUEST['sequenza']));
        $prodotto = NumberOrNull(strtoupper($_REQUEST['prodotto']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));

        $nomeprod = "";
        $codprod = "";
        $gruppoprod = 0;
        $unmis = "";

        $SQL = "SELECT p.codiceprodotto as codiceprodotto, p.gruppo as gruppo, p.unitamisura as unmis, p.descrizionebreve as nomeprodotto FROM cp_prodotto p WHERE p.id = $prodotto ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $nomeprod = quoteStr($row['nomeprodotto']);
            $codprod = quoteStr($row['codiceprodotto']);
            $gruppoprod = $row['gruppo'];
            $unmis = quoteStr($row['unmis']);
        } else {
            echo "{\"id\" : \"0\", \"errore\" : \"Il Prodotto $prodotto non esiste!!!\"}";
            die("Prodotto assente");
        }
        mysqli_free_result($result);

        $qrystr = "UPDATE cp_dettaglioschema SET giornosettimana = $giornosett, sequenza = $sequenza, ";
        $qrystr .= "prodotto = $prodotto, quantita = $quantita, unitamisura =$unmis ";
        $qrystr .= "WHERE id = $idriga ";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);

        echo "{\"id\" : \"$id\"}";
        break;
    case 23: // DELETE
        // delete current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $qrystr = "DELETE FROM cp_dettaglioschema WHERE id = $id";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);
        $errore = mysqli_error($db);
        echo "{\"id\" : \"$id\", \"error\":\"$errore\"}";
        break;

    case 24: // CURRENT ROW - PAGE TEST
        // current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $idriga = NumberOrNull(strtoupper($_REQUEST['id']));
        $idschema = NumberOrNull(strtoupper($_REQUEST['idschema']));
        $idschema = NumberOrNull(strtoupper($_REQUEST['schema']));
        $giornosett = NumberOrNull(strtoupper($_REQUEST['giornosettimana']));
        $sequenza = NumberOrNull(strtoupper($_REQUEST['sequenza']));
        $prodotto = NumberOrNull(strtoupper($_REQUEST['prodotto']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));
        echo "{\"id\" : \"$id\"}";
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