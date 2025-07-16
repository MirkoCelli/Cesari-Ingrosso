<?php
/*  PAGINA PER GESTIRE I DATI DELL'ORDINE DEL CLIENTE - è diverso da ordinicliente.php che è il suo MASTER - 21/06/2024 - Robert Gasperoni */

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
$id = $_REQUEST["id"];
$idclientekey = $_REQUEST["idcliente"];
if ($idclientekey == "undefined"){
    $idclientekey = null;
}

$searchOn = Strip($_REQUEST['_search']);
if ($searchOn == 'true') {
    $fld = Strip($_REQUEST['searchField']);
    if (
        $fld == 'id' || $fld == 'cliente' || $fld == 'datainizio' || $fld == 'datafine' || $fld == 'listino' || $fld == 'nomelistino' || $fld == 'responsabile'
        || $fld == 'nomeresponsabile' || $fld == 'limitespesa' ) {
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

if (isset($idclientekey))
{
    $wh .= " AND cliente = " . $idclientekey; // imposto la condizione di limite al solo ordine selezionato
}

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword)
    or die("Connection Error: " . mysqli_error($db));

mysqli_select_db($db, $database) or die("Error conecting to db.");

switch ($examp) {

    case 5: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella schemadefault e listinoprezzi ottengo gli elementi per il codice pagamento (esempio di query)

        $query  = "SELECT l.id as id, l.tipo as nomelistino ";
        $query .= "FROM cp_listinoprezzi l ORDER BY l.id ";

        $risultato = mysqli_query($db, $query);
        $valoriLP = "";
        // $dati = mysqli_fetch_all($risultato);
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriLP !== '') {
                $valoriLP .= ";";
            }
            $valoriLP .= $row['id'] . ":" . $row['nomelistino'];
        }
        /*
        for ($i = 0; $i < count($dati); $i++){
            if ($valoriLP !== '') {
                $valoriLP .= ";";
            }
            $valoriLP .= $dati[$i][0] . ":" . $dati[$i][1];
        }
        */
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriLP;
        $s .= "</rows>";
        echo $s;
        break;

    case 6: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella cp_statoordine

        $query = "SELECT r.id as id, r.NomeBreve AS nomeresponsabile ";
        $query .= "FROM cp_responsabile r ORDER BY r.NomeBreve ";

        $risultato = mysqli_query($db, $query);
        $valoriStato = "";
        while ($row = mysqli_fetch_array($risultato, MYSQLI_ASSOC)) { // importantissimi: MYSQLI_ASSOC è il solo valore valido per la query non usare mai MYSQL_ASSOC che funziona solo sulle vecchie versioni di PHP < 7.3
            if ($valoriStato !== '') {
                $valoriStato .= ";";
            }
            $valoriStato .= $row['id'] . ":" . $row['nomeresponsabile'];
        }
        $et = ">";
        $s = "<?xml version='1.0' encoding='utf-8'?$et\n";
        $s .= "<rows>";
        $s .= $valoriStato;
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
        $sql = "SELECT COUNT(*) AS count FROM cp_schemadefault b WHERE 1 = 1 " . $wh;
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
        $SQL = "SELECT b.id as id, b.cliente as cliente, b.datainizio as datainizio, ";
        $SQL .= "b.datafine AS datafine, b.listino as listino, l.tipo as nomelistino, b.responsabile as responsabile, r.NomeBreve as nomeresponsabile, b.limitespesa as limitespesa ";
        $SQL .= "FROM cp_schemadefault b ";
        $SQL .= "LEFT OUTER JOIN cp_listinoprezzi l ON (b.listino = l.id) ";
        $SQL .= "LEFT OUTER JOIN cp_responsabile r ON (b.responsabile = r.id) ";
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
            $s .= "<cell>" . xml_entities($row['cliente']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['datainizio']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['datafine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['listino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomelistino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['responsabile']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeresponsabile']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['limitespesa']) . "</cell>";
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

    case 21: // INSERT (non è possibile farlo per evitare doppioni o gestioni errate delle date) - 24/06/2024
        // insert new row and response with new id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $idcliente = NumberOrNull(strtoupper($_REQUEST['idcliente']));
        $cliente = NumberOrNull(strtoupper($_REQUEST['cliente']));
        $datainizio = DateFormatted(strtoupper($_REQUEST['datainizio']),"dd/mm/yyyy");
        $datafine = DateFormatted(strtoupper($_REQUEST['datafine']), "dd/mm/yyyy");
        $listino = NumberOrNull(strtoupper($_REQUEST['listino']));
        $responsabile = NumberOrNull(strtoupper($_REQUEST['responsabile']));
        $limitespesa = NumberOrNull(strtoupper($_REQUEST['limitespesa']));
        // ora possiamo effettuare le operazioni di insert
        $qrystr = "INSERT INTO cp_schemadefault (cliente, datainizio, datafine, listino, responsabile, limitespesa) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($cliente, $datainizio, $datafine, $listino, $responsabile, $limitespesa)";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);
        $id = mysqli_insert_id($db);
        // 05/08/2024 - qui eseguiamo la chiamata alle funzionalità espresse in schemaclientepage.php?q=47
        $schema = $id;
        $errore = GenerareDettagliDefaultPerSchema($cliente, $schema, $dtinizio, $dtfine);
        if ($errore != "") {
            header("Content-type: text/json");
            echo "{\"stato\" : \"ERRORE\", \"errore\" : \"$errore\"}";
        } else {
            header("Content-type: text/json");
            echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
        }
        // fine 05/08/2024
        // echo "{\"id\" : \"$id\"}";
        break;

    case 22: // UPDATE (si possono modificare lo Stato, i flags di Autorizzazionesuperamentospesa e codiceautorizzazione) - 24/06/2024
        // update current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];

        $idcliente = NumberOrNull(strtoupper($_REQUEST['idcliente']));
        $cliente = NumberOrNull(strtoupper($_REQUEST['cliente']));
        $datainizio = DateFormatted(strtoupper($_REQUEST['datainizio']), "dd/mm/yyyy");
        $datafine = DateFormatted(strtoupper($_REQUEST['datafine']), "dd/mm/yyyy");
        $listino = NumberOrNull(strtoupper($_REQUEST['listino']));
        $responsabile = NumberOrNull(strtoupper($_REQUEST['responsabile']));
        $limitespesa = NumberOrNull(strtoupper($_REQUEST['limitespesa']));

        $qrystr = "UPDATE cp_schemadefault s SET s.datainizio = $datainizio, s.datafine = $datafine, s.listino = $listino, ";
        $qrystr .= "s.responsabile = $responsabile, s.limitespesa = $limitespesa ";
        $qrystr .= "WHERE s.id = $id "; // AND c.cliente = $idcliente ";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);
        echo "{\"id\" : \"$id\"}";
        break;

    case 23: // DELETE (non ammesso perchè crea confusione)
        // delete current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $qrystr = "DELETE FROM cp_schemadefault WHERE id = $id";
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
        $id = $_REQUEST['id'];

        $idcliente = NumberOrNull(strtoupper($_REQUEST['idcliente']));
        $cliente = NumberOrNull(strtoupper($_REQUEST['cliente']));
        $datainizio = DateFormatted(strtoupper($_REQUEST['datainizio']), "dd/mm/yyyy");
        $datafine = DateFormatted(strtoupper($_REQUEST['datafine']), "dd/mm/yyyy");
        $listino = NumberOrNull(strtoupper($_REQUEST['listino']));
        $responsabile = NumberOrNull(strtoupper($_REQUEST['responsabile']));
        $limitespesa = NumberOrNull(strtoupper($_REQUEST['limitespesa']));

        echo "{\"id\" : \"$id\"}";
        break;
    case 40: // controlla se ci sono ordini nel periodo indicato per il cliente
        $cliente = $_REQUEST["cliente"];
        // $schema = $_REQUENST["schema"];
        $dtinizio = $_REQUEST["datainizio"];
        $dtfine = $_REQUEST["datafine"];
        // controllo se ci sono ordini presenti
        $SQL = "SELECT COUNT(*) as conteggio ";
        $SQL .= "FROM cp_ordinecliente b ";
        $SQL .= "WHERE cliente = $cliente AND dataordine BETWEEN DATE('$dtinizio') AND DATE('$dtfine') ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        /*
        if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
            header("Content-type: application/xhtml+xml;charset=utf-8");
        } else {
            header("Content-type: text/xml;charset=utf-8");
        }
        */
        //
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $conta = $row["conteggio"];
            if ($conta > 0){
                header("Content-type: text/json");
                echo "{\"stato\" : \"ERRORE\", \"errore\" : \"Ci sono $conta ordini presenti nel periodo $dtinizio _ $dtfine indicato dallo schema\"}";
            } else {
                header("Content-type: text/json");
                echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
            }
        }
        break;
    case 41: // controlla se ci sono ordini modificati o in stato diverso da 2 = APERTO)
        $cliente = $_REQUEST["cliente"];
        // $schema = $_REQUENST["schema"];
        $dtinizio = $_REQUEST["datainizio"];
        $dtfine = $_REQUEST["datafine"];
        // controllo se ci sono ordini presenti
        $SQL = "SELECT COUNT(*) as conteggio ";
        $SQL .= "FROM cp_ordinecliente b LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = b.id) ";
        $SQL .= "WHERE cliente = $cliente AND dataordine BETWEEN DATE('$dtinizio') AND DATE('$dtfine') AND ";
        $SQL .= "( NOT(d.stato =  0) OR NOT(b.stato = 2) ) ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));

        //
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $conta = $row["conteggio"];
            if ($conta > 0) {
                header("Content-type: text/json");
                echo "{\"stato\" : \"ERRORE\", \"errore\" : \"Ci sono $conta ordini o dettagli ordine variati nel periodo $dtinizio _ $dtfine indicato dallo schema\"}";
            } else {
                header("Content-type: text/json");
                echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
            }
        }
        break;
    case 45: // generare gli ordini per lo schema selezionato con tutti i dettagli
        // attenzione: di deve verificare che per il cliente nel periodo indicato nello schema con ci siano già degli ordini con i relativi dettagli
        // (se c'è l'ordine significa che è stato già generato)
        // leggo i parametri forniti e poi richiamo una procedura in Php per eseguire le singole query di inserimento degli ordini
        $cliente = $_REQUEST["cliente"];
        $schema = $_REQUEST["schema"];
        $dtinizio = $_REQUEST["datainizio"];
        $dtfine = $_REQUEST["datafine"];
        $errore = GenerareGliOrdiniPerSchema($cliente, $schema, $dtinizio, $dtfine);
        if ($errore != "") {
            header("Content-type: text/json");
            echo "{\"stato\" : \"ERRORE\", \"errore\" : \"$errore\"}";
        } else {
            header("Content-type: text/json");
            echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
        }
        break;
    case 46: // cancellazione degli ordini per lo schema selezionato solo se sono in stato aperto altrimenti non si possono cancellare
        // se per caso una schema viene variato per un periodo futuro e si vogliono sostituire gli ordini vecchi con quelli nuovi
        // sarà necessario rimuovere i vecchi dettagli e sostituirli con quelli nuovi purchè non siano stati alterati dal cliente
        // che cosa dobbiamo fare in questo caso? Farsi spiegare da Nicolò Celli
        $cliente = $_REQUEST["cliente"];
        $schema = $_REQUEST["schema"];
        $dtinizio = $_REQUEST["datainizio"];
        $dtfine = $_REQUEST["datafine"];
        $errore = CancellareGliOrdiniPerSchema($cliente, $schema, $dtinizio, $dtfine);
        if ($errore != "") {
            header("Content-type: text/json");
            echo "{\"stato\" : \"ERRORE\", \"errore\" : \"$errore\"}";
        } else {
            header("Content-type: text/json");
            echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
        }
        break;
    case 47: // genera uno schema per tutti i giorni lavorativi con tutti i prodotti a qauntità zero, dato il record dello schema corrente
        // accertarsi che non ci siano dettagli per questo schema altrimenti non si può procedere
        $cliente = $_REQUEST["cliente"];
        $schema = $_REQUEST["schema"];
        $dtinizio = $_REQUEST["datainizio"];
        $dtfine = $_REQUEST["datafine"];
        $errore = GenerareDettagliDefaultPerSchema($cliente, $schema, $dtinizio, $dtfine);
        if ($errore != "") {
            header("Content-type: text/json");
            echo "{\"stato\" : \"ERRORE\", \"errore\" : \"$errore\"}";
        } else {
            header("Content-type: text/json");
            echo "{\"stato\" : \"OK\", \"errore\" : \"\"}";
        }
        break;
}
mysqli_close($db);

// Questa funzione normalizza i chars per essere inseriti nel XML ed è più completo per UTF-8

function Strip($value)
{  // get_magic_quotes_gpc è deprecata e non ha nessun effetto basta fare solo $value = stripslasshes($value);
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

function xml_entities($string)
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
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
    if (strtotime($date) == -1 || $date == "") {
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

// funzioni operative per il problema di generazione degli ordini
function GenerareGliOrdiniPerSchema($cliente, $schema, $dtinizio, $dtfine){
    global $db;

    $localerror = "";
    $elencoDate = displayDates($dtinizio, $dtfine);
    for ($i = 0; $i < count($elencoDate); $i++){
        $datacorrente = $elencoDate[$i];
        $giornosettimana = date("N", strtotime($datacorrente));
        // ora eseguiamo la query che genera l'ordine per il cliente dallo schema per il giorno della settimana associata a $datacorrente

        $sql = "INSERT INTO cp_ordinecliente (cliente,schematico,dataordine,stato,autorizzatosuperamentospesa,codiceautorizzazione) \n";
        $sql .= "VALUES \n";
        $sql .= "($cliente, $schema, DATE('$datacorrente'), 2, 0, 0)";
        mysqli_query($db, $sql);
        $mioerror = mysqli_error($db);
        if ($mioerror != ""){
            $localerror .= $mioerror . " per $datacorrente \n";
        }

        $idordine = mysqli_insert_id($db); // questo è id ordine da usare nei dettagli

        // inserisco i dettagli dell'ordine alla data corrente

        $sql = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura,stato) \n";
        $sql .= "SELECT $idordine as ordinecliente, d.sequenza as dettaglioordine, d.prodotto, p.gruppo, d.quantita, d.unitamisura, 0 as stato \n";
        $sql .= "FROM cp_dettaglioschema d LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) \n";
        $sql .= "WHERE d.schematico = $schema AND d.giornosettimana = $giornosettimana \n";
        $sql .= "ORDER BY d.sequenza, d.prodotto ";

        mysqli_query($db, $sql);
        $mioerror = mysqli_error($db);
        if ($mioerror != "") {
            $localerror .= $mioerror . " per $datacorrente \n";
        }
    }
    return $localerror;
}

function CancellareGliOrdiniPerSchema($cliente, $schema, $dtinizio, $dtfine)
{
    global $db;

    $localerror = "";
    $elencoDate = displayDates($dtinizio, $dtfine);
    for ($i = 0; $i < count($elencoDate); $i++) {
        $datacorrente = $elencoDate[$i];
        // $giornosettimana = date("N", strtotime($datacorrente));
        // ora eseguiamo la query che mi fornisce il record dell'ordine alla data corrente per il cliente

        $SQL = "SELECT b.id as id ";
        $SQL .= "FROM cp_ordinecliente b ";
        $SQL .= "WHERE cliente = $cliente AND dataordine = Date('$datacorrente') ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));

        //
        $idordine = null;
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $idordine = $row["id"];
            // cancello i dettagli associati a questo ordine
            $sql = "DELETE FROM cp_dettaglioordine WHERE ordinecliente = $idordine \n";
            mysqli_query($db, $sql);
            $mioerror = mysqli_error($db);
            if ($mioerror != "") {
                $localerror .= $mioerror . " per $datacorrente e idordine = $idordine \n";
            }

            // inserisco i dettagli dell'ordine alla data corrente

            $sql = "DELETE FROM cp_ordinecliente WHERE id = $idordine \n";

            mysqli_query($db, $sql);
            $mioerror = mysqli_error($db);
            if ($mioerror != "") {
                $localerror .= $mioerror . " per $datacorrente e idordine = $idordine\n";
            }
        }
    }
    return $localerror;
}

function GenerareDettagliDefaultPerSchema($cliente, $schema, $dtinizio, $dtfine){
    global $db;

    $localerror = "";

    $SQL = "SELECT COUNT(*) as conta ";
    $SQL .= "FROM cp_dettaglioschema b ";
    $SQL .= "WHERE schematico =  " . $schema ;

    $result = mysqli_query($db, $SQL);
    if (!$result){
       $localerror = "Problemi GenerareDettagliPerSWchema: Couldn t execute query." . mysqli_error($db);
       return $localerror;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row["conta"] > 0)
    {
        $localerror = "Problemi GenerareDettagliPerSWchema: sono già presenti dei dettagli per lo schema di default indicato";
        return $localerror;
    }

    for ($i = 1; $i < 8; $i++) {
        if ($i != 1){
            // si esclude il lunedì in automatico
            $sql = "INSERT INTO cp_dettaglioschema (schematico,giornosettimana,sequenza,prodotto,quantita,unitamisura) \n";
            $sql .= "SELECT " . $schema . " AS schematico, " . $i . " AS giornosettimana, p.sequenza AS sequenza, p.id as prodotto, ";
            $sql .= "0 AS quantita, p.unitamisura AS unitamisura FROM cp_prodotto p ORDER BY p.sequenza";
            if (!mysqli_query($db, $sql)){
                $localerror = "Problemi GenerareDettagliPerSWchema: Couldn t execute query." . mysqli_error($db);
                return $localerror;
            }
        }
    }
    return $localerror;
}

// ritorna un array con tutte le date comprese fra le due date indicate nel formato espresso
function displayDates($date1, $date2, $format = 'Y-m-d')
{
    $dates = array();
    $current = strtotime($date1);
    $date2 = strtotime($date2);
    $stepVal = '+1 day';
    while ($current <= $date2) {
        $dates[] = date($format, $current);
        $current = strtotime($stepVal, $current);
    }
    return $dates;
}
?>