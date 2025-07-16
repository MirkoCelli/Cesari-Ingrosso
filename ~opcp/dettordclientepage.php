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
$idordinekey = $_REQUEST["idordine"];

$searchOn = Strip($_REQUEST['_search']);
if ($searchOn == 'true') {
    $fld = Strip($_REQUEST['searchField']);
    if (
        $fld == 'id' || $fld == 'ordinecliente' || $fld == 'dettaglioordine' || $fld == 'prodotto' || $fld == 'nomeprodotto' || $fld == 'gruppo' || $fld == 'nomegruppo'
        || $fld == 'quantita' || $fld == 'unitamisura' || $fld == 'stato' || $fld == 'descrizionestato' || $fld == 'responsabile' || $fld == 'nomeresponsabile') {
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

if (isset($idordinekey))
{
    $wh .= " AND ordinecliente = " . $idordinekey; // imposto la condizione di limite al solo ordine selezionato
}

// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword)
    or die("Connection Error: " . mysqli_error($db));

mysqli_select_db($db, $database) or die("Error conecting to db.");

switch ($examp) {

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
        $sql = "SELECT COUNT(*) AS count FROM cp_dettaglioordine b WHERE 1 = 1 AND stato = 0 " . $wh;
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
            $sidx = "b.dettaglioordine";
        }
        $SQL  = "SELECT b.id as id, b.ordinecliente as ordine, b.dettaglioordine as dettaglioordine, b.prodotto as prodotto, ";
        $SQL .= "p.descrizionebreve as nomeprodotto, b.gruppo as gruppo, g.NomeGruppo as nomegruppo, ";
        $SQL .= "b.quantita AS quantita, b.unitamisura as unitamisura, b.stato as stato, b.riferimentoprec as riferimentoprec, b.responsabile as responsabile, r.NomeBreve as nomeresponsabile ";
        $SQL .= "FROM cp_dettaglioordine b ";
        $SQL .= "LEFT OUTER JOIN cp_prodotto p ON (p.id = b.prodotto) ";
        $SQL .= "LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = b.gruppo) ";
        $SQL .= "LEFT OUTER JOIN cp_responsabile r ON (r.id = b.responsabile) ";
        $SQL .= "WHERE 1 = 1 AND stato = 0 " . $wh . " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
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
            $s .= "<cell>" . xml_entities($row['ordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['dettaglioordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['prodotto']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeprodotto']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['gruppo']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomegruppo']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['quantita']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['unitamisura']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['stato']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['riferimentoprec']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['responsabile']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeresponsabile']) . "</cell>";
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
        $idordine = NumberOrNull(strtoupper($_REQUEST['idordine']));
        $ordine = NumberOrNull($_REQUEST['ordine']); // 2024-08-08 (questo numero viene indicato dall'operatore e quindi sostituisce idordine

        $prodotto = NumberOrNull(strtoupper($_REQUEST['prodotto']));
        $gruppo = NumberOrNull(strtoupper($_REQUEST['gruppo']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));
        $responsabile = NumberOrNull(strtoupper($_REQUEST['responsabile']));

        $dettaglioordine = 0; // questo deve essere ricavato con un aquery dall'ordine corrente come il MAX + 1 di dettagliordine
        $SQL = "SELECT IFNULL(MAX(d.dettaglioordine),0) + 1 as dettaglioordine FROM cp_dettaglioordine d WHERE d.ordinecliente = $ordine ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $dettaglioordine = $row['dettaglioordine'];
        } else {
            $dettaglioordine = 1;
        }
        mysqli_free_result($result);
        // controllo che non ci sia già il prodotto indicato nell'ordine
        $contapresenza = 0; // questo deve essere ricavato con un aquery dall'ordine corrente come il MAX + 1 di dettagliordine
        $SQL = "SELECT COUNT(*) as conta FROM cp_dettaglioordine d WHERE d.ordinecliente = $ordine AND d.prodotto = $prodotto AND stato = 0 ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $contapresenze = $row['conta'];
        } else {
            $contapresenze = 0;
        }
        if ($contapresenze > 0){
            // die("Il Prodotto $prodotto già presente in questo ordine!!! ");
            echo "{\"id\" : \"0\", \"errore\" : \"Il Prodotto $prodotto già presente in questo ordine!!! \"}";
            die("Già Presente");
        }
        mysqli_free_result($result);
        // ottengo informazioni sul prodotto se esiste altrimenti deve segnalare un errore

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
        $qrystr = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato, responsabile) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($ordine, $dettaglioordine, $prodotto, $gruppoprod, $quantita, $unmis, 0, $responsabile)";

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
        $idordine = NumberOrNull(strtoupper($_REQUEST['idordine']));
        $prodotto = NumberOrNull(strtoupper($_REQUEST['prodotto']));
        $gruppo = NumberOrNull(strtoupper($_REQUEST['gruppo']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));
        $responsabile = NumberOrNull(strtoupper($_REQUEST['responsabile']));
        $ordine = NumberOrNull($_REQUEST['ordine']); // 2024-08-08 (questo numero viene indicato dall'operatore e quindi sostituisce idordine

        // ATTENZIONE: la procedura di aggiornamento si esegue in due fasi:
        // prima fase: si chiude la posizione precedente dopo essersi accertati che il prodotto è sempre lo stesso altrimenti si ferma, mette lo stato = 3 per indicare modificato da Responsabile pasticceria
        // seconda fase: accertato che il prodotto non è alterato, allora inserisce una nuova riga con i valori modificati e mette stato = 0 e riferimentoprec = $id e responsabile = $responsabile

        // Prima fase:

        $SQL = "SELECT d.quantita, d.dettaglioordine, d.ordinecliente, d.prodotto, d.gruppo, d.unitamisura FROM cp_dettaglioordine d WHERE d.id = $idriga ";

        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $oldqta = $row['quantita'];
            $olddettord = $row["dettaglioordine"];
            $oldordine = $row["ordinecliente"];
            $oldprod = $row["prodotto"];
            $oldgruppo = $row["gruppo"];
            $oldunmis = quoteSTr($row["unitamisura"]);
        } else {
            echo "{\"id\" : \"0\", \"errore\" : \"Riga $idriga non esiste!!!\"}";
            die("Dettaglio Ordine assente");
        }
        mysqli_free_result($result);

        if ($oldprod !== $prodotto)
        {
            echo "{\"id\" : \"0\", \"errore\" : \"Prodotto $oldprod non si può variare!!!\"}";
            die("Prodotto variato in modifica");
        }
        // devo mettere alla riga attuale lo stato a 3

        $qrystr = "UPDATE cp_dettaglioordine SET stato = 3 ";
        $qrystr .= "WHERE id = $idriga AND ordinecliente = $oldordine ";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);

        // Seconda fase: inserire un nuovo record con i dati fissi dell'ordine precedente e quelli variati nella nuova condizione
        // ora possiamo effettuare le operazioni di insert
        $qrystr = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato, riferimentoprec, responsabile) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($oldordine, $olddettord, $oldprod, $oldgruppo, $quantita, $oldunmis, 0,$idriga, $responsabile)";
        $msg .= "\n$qrystr";
        mysqli_query($db, $qrystr);
        $id = mysqli_insert_id($db);
        echo "{\"id\" : \"$id\"}";
        break;
    case 23: // DELETE
        // delete current row and response with old id
        $msg = "";
        foreach ($_REQUEST as $k => $v) {
            $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $qrystr = "DELETE FROM cp_dettaglioordine WHERE id = $id";
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
        $idordine = NumberOrNull(strtoupper($_REQUEST['idordine']));
        $ordine = NumberOrNull($_REQUEST['ordine']); // 2024-08-08 (questo numero viene indicato dall'operatore e quindi sostituisce idordine
        $tipologiaporta = NumberOrNull(strtoupper($_REQUEST['tipologiaporta']));
        $larghezza = NumberOrNull(strtoupper($_REQUEST['larghezza']));
        $altezza = NumberOrNull(strtoupper($_REQUEST['altezza']));
        $spessore = quoteStr(strtoupper($_REQUEST['spessoreporta']));
        $quantita = NumberOrNull(strtoupper($_REQUEST['quantita']));
        $sensoapertura = quoteStr(strtoupper($_REQUEST['sensoapertura']));
        $sistemaapertura = numberOrNull(strtoupper($_REQUEST['sistemaapertura']));
        $descrizione = quoteStr(strtoupper($_REQUEST['descrizione']));
        $rifporta = quoteStr($_REQUEST['rifporta']);
        $disegno = quoteStr(strtoupper($_REQUEST['disegno']));
        $tipomisura = NumberOrNull(strtoupper($_REQUEST['tipomisura']));
        $larghezzamisura = NumberOrNull(strtoupper($_REQUEST['larghezzamisura']));
        $altezzamisura = NumberOrNull(strtoupper($_REQUEST['altezzamisura']));
        $larghezzagrande = NumberOrNull(strtoupper($_REQUEST['larghezzagrande']));
        $larghezzapiccola = NumberOrNull(strtoupper($_REQUEST['larghezzapiccola']));
        $codicelistino = quoteStr(strtoupper($_REQUEST['codicelistino']));
        // novità 03/04/2018
        $imm_ferramenta = quoteStr(strtoupper($_REQUEST['immferramenta']));
        $imm_telaio = quoteStr(strtoupper($_REQUEST['immtelaio']));
        $stipite = quoteStr(strtoupper($_REQUEST['stipite']));
        // novità 27/01/2016 il massello perimetrale è determinato dalla tabella spessorematerialemp e non da scelta operatore
        $SQL = "SELECT m.MasselloPerimetrale FROM spessorematerialemp m, ordine o WHERE m.SpessorePannello = $spessore AND m.Materiale = o.TipoMateriale AND m.MDF = o.SceltaMDF AND o.IdOrdine = $idordine ";
        $result = mysqli_query($db, $SQL) or die("Couldn t execute query." . mysqli_error($db));
        if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $masselloperimetrale = $row['MasselloPerimetrale'];
        } else {
            $masselloperimetrale = null;
        }
        mysqli_free_result($result);
        echo "{\"id\" : \"$id\"}";
        break;
}
mysqli_close($db);

// Questa funzione normalizza i chars per essere inseriti nel XML ed è più completo per UTF-8

function numeroProssimo($db1, $tab, $annualita)
{
    mysqli_autocommit($db1, FALSE);

    /* Insert some values */
    try {
        $msg .= "AutoCommit(false)\r\n";
        $qrystr = "SELECT IFNULL(UltimoNumero,-1) as UltimoNumero FROM numerazioni WHERE Anno =" . $annualita . " AND Tabella = '" . $tab . "' ";
        $msg .= $qrystr . "\r\n";
        $result = mysqli_query($db1, $qrystr);
        $row = mysqli_fetch_array($result, MYSQL_ASSOC);
        $msg .= "ROW = $row\r\n";
        $count = $row['UltimoNumero'];
        $msg .= "Conteggio=$count\r\n";

        if ($count == -1) {
            $qrystr = "INSERT INTO numerazioni (AnnoCompetenza,UltimoNumero,Tabella) VALUES (" . $annualita . ",1,'" . $tab . "')";
            mysqli_query($db1, $qrystr);
            $errore = mysqli_error($db);
            $msg .= "Errore db (1)=$error\r\n";
            // echo "{\"id\" : \"$id\", \"error\":\"$errore\"}";
            $msg .= "$qrystr\r\n";
            if ($error != "") {
                throw new Exception($error);
            }
            $count = 1;

        } else {
            $count += 1;
            $qrystr = "UPDATE numerazioni SET UltimoNumero = " . $count . " WHERE Anno = " . $annualita . " AND Tabella = '" . $tab . "'";
            $msg .= "$qrystr\r\n";
            mysqli_query($db1, $qrystr);
            $errore = mysqli_error($db);
            $msg .= "Errore db (2)=$error\r\n";
            // echo "{\"id\" : \"$id\", \"error\":\"$errore\"}";
            if ($error != "") {
                throw new Exception($error);
            }
        }

        /* commit transaction */
        mysqli_commit($db1);
        $msg .= "COMMIT\r\n";

    } catch (Exception $e) {
        /* rollback */
        mysqli_rollback($db1);
        $msg .= "ROLLBACK\r\n";
        $count = -1; // segnala un errore
    }
    mysqli_autocommit($db1, TRUE);
    // echo "Messaggio: $msg\r\n";
    return $count;
}

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

function CercaPrezzoBase($codlistino, $idordine, $oggi, $db1)
{
    $prezzo = 0.0;
    // devo cercare una voce nel listino prezzi che sia valido alla data odierna e che sia corrispondente al codice listino fornito
    // si differenzia per essenza perciò devo leggere dall'ordine questa informazione (differenziare anche per tipoPorta: finite, grezze,...)
    $SQL = "SELECT * FROM ordine WHERE idordine = $idordine";
    $msg1 = "\n\rCalcolaPrezzoBase con idordine = $idordine e cod.listino = $codlistino e data odierna = $oggi ";
    file_put_contents("c:\\temp\\calcoliprezzobase.txt", $msg1);
    $result = mysqli_query($db1, $SQL);
    $essenza = -1;
    $tipolavorazione = -1;
    while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
        $essenza = $row['ColoreImpiallacciatura'];
        $tipolavorazione = $row['TipoLavorazione'];
        $msg1 .= "\n\rEssenza = $essenza e TipoLavorazione = $tipolavorazione ";
        file_put_contents("c:\\temp\\calcoliprezzobase.txt", $msg1);
    }
    mysqli_free_result($result);
    switch ($tipolavorazione) {
        case 5: // porte finite
            $SQL = "SELECT * FROM listinoportefinite WHERE CodiceListino = '$codlistino' AND CodiceEssenza = $essenza AND DataInizio <= Date('$oggi') AND (DataFine IS NULL OR DataFine >= Date('$oggi'))";
            $msg1 .= "\n\rSQL = $SQL ";
            file_put_contents("c:\\temp\\calcoliprezzobase.txt", $msg1);
            $result = mysqli_query($db1, $SQL);
            while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
                $prezzo = $row['PrezzoUnitario'];
                $msg1 .= "\n\rPrezzo Unitario = $prezzo ";
                file_put_contents("c:\\temp\\calcoliprezzobase.txt", $msg1);
            }
            mysqli_free_result($result);
            break;
        case 2: // porte grezze
            break;
        case 3: // porte grezze assemblate
            break;
        case 4: // porte grezze preassemblate
            break;
    }
    return $prezzo;
}

function CalcolaVariazionePrezzo($prezzobase, $codlistino, $idord, $idrigapannello, $db1, $sistemaapertura1)
{
    $msg1 = "\n\rCalcolaVariazionePrezzo0 con idordine = $idord e cod.listino = $codlistino e prezzo base = $prezzobase ";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $prezzovariato = 0.0;
    // alcune variazioni dipendono da elementi dell'ordine
    $SQL1 = "SELECT * FROM ordine WHERE idordine = $idord";
    $result1 = mysqli_query($db1, $SQL1);
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $essenza = -1;
    $tipolavorazione = -1;
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $essenza = $row1['ColoreImpiallacciatura'];
        $tipolavorazione = $row1['TipoLavorazione'];
        $ferramenta = $row1['Ferramenta'];
        $coloreferramenta = $row1['ColoreFerramenta'];
        $coprifilo = $row1['CodiceCopriFili'];
        $fermavetro = $row1['FermaVetro']; // non è il codice è solo una descrizione
        $guarnizioni = $row1['Guarnizioni'];

    }
    mysqli_free_result($result1);
    $msg1 .= "\r\nCalcolaVariazionePrezzo_1.0";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    // ottiene da ordinerigapannello e ordinerigatelaio
    $SQL1 = "SELECT * FROM rigaordinepannelli WHERE IdRigaPan = $idrigapannello AND IdOrdine = $idord";
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $result1 = mysqli_query($db1, $SQL1);
    $spessoreporta = "";
    $altezza = 0.0;
    $larghezza = 0.0;
    $sistemaapertura = 0;
    if ($sistemaapertura1 == "NULL") {
        $sistemaapertura = 0;
    } else {
        $sistemaapertura = $sistemaapertura1;
    }
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $spessoreporta = $row1['SpessorePorta']; // da riga pannello
        $altezza = $row1['Altezza'];
        $larghezza = $row1['Larghezza'];
        $tipologiaporta = $row1['TipologiaPorta'];

    }
    mysqli_free_result($result1);
    $msg1 .= "\r\nCalcolaVariazionePrezzo_1.1";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $SQL1 = "SELECT * FROM rigaordinetelaio WHERE PannelloCorrispondente = $idrigapannello AND IdOrdine = $idord ";
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $result1 = mysqli_query($db1, $SQL1);
    $spessoremuro = 0.0;
    $tipotelaio = -1;
    $stipite = 0;
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $spessoremuro = $row1['SpessoreMuro']; // da riga telaio
        $tipotelaio = $row1['TipoTelaio']; // da riga telaio
        $stipite = $row1['Stipite']; // da riga telaio novità del 05/02/2017
    }
    mysqli_free_result($result1);
    $msg1 .= "\r\nCalcolaVariazionePrezzo_1.2";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $eb_ferramenta = -1;
    $eb_coloreferramenta = -1;
    $eb_coprifilo = -1;
    $eb_fermavetro = -1;
    $eb_guarnizione = -1;
    $eb_spessoreporta = -1;
    $eb_tipotelaio = -1;
    $eb_spessoremuro = 0.0;
    $eb_tipologiaporta = -1;
    $SQL1 = "SELECT * FROM elementibasedefault WHERE CodiceListino = '$codlistino'";
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $result1 = mysqli_query($db1, $SQL1);
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $eb_ferramenta = $row1['Ferramenta'];
        $eb_coloreferramenta = $row1['ColoreFerramenta'];
        $eb_coprifilo = $row1['Coprifilo'];
        $eb_fermavetro = $row1['Fermavetro'];
        $eb_guarnizione = $row1['Guarnizioni'];
        $eb_spessoreporta = $row1['SpessorePorta'];
        $eb_tipotelaio = $row1['TipologiaTelaio'];
        $eb_spessoremuro = $row1['SpessoreMuro'];
        $eb_tipologiaporta = $row1['TipologiaPorta'];
    }
    mysqli_free_result($result1);
    $msg1 .= "\r\nCalcolaVariazionePrezzo2 con $tipolavorazione";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $oggi = date("Y-m-d");
    switch ($tipolavorazione) {
        case 5: // porte finite
            $SQL1 = "SELECT * FROM listinoportefinite WHERE CodiceListino = '$codlistino' AND CodiceEssenza = $essenza AND DataInizio <= Date('$oggi') AND (DataFine IS NULL OR DataFine >= Date('$oggi'))";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $prezzo = $row1['PrezzoUnitario'];
            }
            mysqli_free_result($result1);
            // devo determinare la percentuale di aumento o il prezzo fisso di variazione del prezzo base
            $percvar = 0.0;
            $prezzovar = 0.0;
            // query per estrarre la percentuale e il prezzo fisso in base a altezza e larghezza
            $SQL1 = "SELECT * FROM condizionivariazioniprezzobase WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "MinL <= $larghezza AND $larghezza <= MaxL AND ";
            $SQL1 .= "MinH <= $altezza AND $altezza <= MaxH ";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = $row1['Percentuale'];
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
            // qui abbiamo determinato la variazione del prezzo base per larghezza x altezza
            $prezzovariato += round($prezzobase * ($percvar / 100), 2) + round($prezzovar, 2);
            // ora si devono valutare le altre variazioni
            $msg1 .= "Calcolo per $larghezza x $altezza con $percvar % x $prezzobase + $prezzovar = $prezzovariato a applicare a $prezzobase \r\n";
            $msg1 .= "Per il colore = $essenza \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            // deve valutare le variazioni del prezzo in base agli elementi variati rispetto ai calcoli base
            $importovariazioni = ValutazioneVariazioniElementi(
                $db1,
                $oggi,
                $prezzobase,
                $eb_ferramenta,
                $eb_coloreferramenta,
                $eb_coprifilo,
                $eb_fermavetro,
                $eb_guarnizione,
                $eb_spessoreporta,
                $eb_tipotelaio,
                $eb_spessoremuro,
                $eb_tipologiaporta,
                $essenza,
                $tipolavorazione,
                $ferramenta,
                $coloreferramenta,
                $coprifilo,
                $fermavetro,
                $guarnizioni,
                $spessoreporta,
                $altezza,
                $larghezza,
                $spessoremuro,
                $tipotelaio,
                $tipologiaporta,
                $stipite,
                $sistemaapertura
            );
            $prezzovariato += round($importovariazioni, 2);
            $importoextra = ValutazionePrezzoExtra($db1, $oggi, $prezzobase, $codlistino, $idord, $idrigapannello);
            $prezzovariato += round($importoextra, 2);
            //
            break;
        case 2: // porte grezze
            break;
        case 3: // porte grezze assemblate
            break;
        case 4: // porte grezze preassemblate
            break;
    }
    // alcune variazioni dipendono da elementi del pannello
    // alcune variazioni dipendono da elementi del telaio associato al pannello corrente
    return $prezzovariato;
}

function ValutazionePrezzoExtra($db1, $oggi, $prezzobase, $codlistino, $idord, $idrigapannello)
{
    $totale = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    $quantita = 0.0;
    $prezzo = 0.0;
    // considero i calcoli in confordineextra come quelli validi
    try {
        $SQL1 = "SELECT e.Quantita as quantita, e.PrezzoUnitario as prezzo, l.VariazionePercentuale as VariazionePercentuale, l.PrezzoFisso as PrezzoFisso ";
        $SQL1 .= "FROM rigaordineextra e left outer join listinoelementiextra l ON (l.CodiceExtra = e.CodiceExtra AND l.DataInizio <= Date('$oggi') AND l.DataFine IS NULL) WHERE 1 = 1 AND ";
        $SQL1 .= "RigaOrdine = $idrigapannello ";
        $msg1 = "\r\nQuery = $SQL1 \r\n";
        file_put_contents("c:\\temp\\calcoliprezzoextra.txt", $msg1);
        $result1 = mysqli_query($db1, $SQL1);
        while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
            $percvar = round($row1['VariazionePercentuale']);
            $prezzovar = $row1['PrezzoFisso'];
            $quantita = $row1['quantita'];
            $prezzo = $row1['prezzo'];
            $totale += round($prezzo * $quantita, 2); // non considero al momento altre modalità di calcolo
            // $prezzovariato += round($prezzobase * ($percvar / 100),2) + round($prezzovar,2);
        }
        mysqli_free_result($result1);
    } catch (Exception $e) {
        $percvar = 0.0;
        $prezzovar = 0.0;
        $quantita = 0.0;
        $prezzo = 0.0;
        $totale = 0.0;
    }

    return $totale;
}

function CalcolaElencoVariazionePrezzo($prezzobase, $codlistino, $idord, $idrigapannello, $db1)
{
    $elenco = "";
    // alcune variazioni dipendono da elementi dell'ordine
    // alcune variazioni dipendono da elementi del pannello
    // alcune variazioni dipendono da elementi del telaio associato al pannello corrente
    $msg1 = "\n\rCalcolaElencoVariazionePrezzo0 con idordine = $idord e cod.listino = $codlistino e prezzo base = $prezzobase ";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $prezzovariato = 0.0;
    // alcune variazioni dipendono da elementi dell'ordine
    $SQL1 = "SELECT * FROM ordine WHERE idordine = $idord";
    $result1 = mysqli_query($db1, $SQL1);
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $essenza = -1;
    $tipolavorazione = -1;
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $essenza = $row1['ColoreImpiallacciatura'];
        $tipolavorazione = $row1['TipoLavorazione'];
        $ferramenta = $row1['Ferramenta'];
        $coloreferramenta = $row1['ColoreFerramenta'];
        $coprifilo = $row1['CodiceCopriFili'];
        $fermavetro = $row1['FermaVetro']; // non è il codice è solo una descrizione
        $guarnizioni = $row1['Guarnizioni'];
    }
    mysqli_free_result($result1);
    $msg1 .= "\r\nCalcolaElencoVariazionePrezzo_1.0";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    // ottiene da ordinerigapannello e ordinerigatelaio
    $SQL1 = "SELECT * FROM rigaordinepannelli WHERE IdRigaPan = $idrigapannello AND IdOrdine = $idord";
    $msg1 .= "\r\nQuery = $SQL1 \r\n";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $result1 = mysqli_query($db1, $SQL1);
    $spessoreporta = "";
    $altezza = 0.0;
    $larghezza = 0.0;
    while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
        $spessoreporta = $row1['SpessorePorta']; // da riga pannello
        $altezza = $row1['Altezza'];
        $larghezza = $row1['Larghezza'];
        $tipologiaporta = $row1['tipologiaporta'];
    }
    mysqli_free_result($result1);
    //     //
    $msg1 .= "\r\nCalcolaElencoVariazionePrezzo_1.1";
    file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
    $oggi = date("Y-m-d");
    switch ($tipolavorazione) {
        case 5: // porte finite
            $SQL1 = "SELECT * FROM listinoportefinite WHERE CodiceListino = '$codlistino' AND CodiceEssenza = $essenza AND DataInizio <= Date('$oggi') AND (DataFine IS NULL OR DataFine >= Date('$oggi'))";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $prezzo = $row1['PrezzoUnitario'];
            }
            mysqli_free_result($result1);
            // devo determinare la percentuale di aumento o il prezzo fisso di variazione del prezzo base
            $percvar = 0.0;
            $prezzovar = 0.0;
            // query per estrarre la percentuale e il prezzo fisso in base a altezza e larghezza
            $SQL1 = "SELECT * FROM condizionivariazioniprezzobase WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "MinL <= $larghezza AND $larghezza <= MaxL AND ";
            $SQL1 .= "MinH <= $altezza AND $altezza <= MaxH ";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['Percentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
            // qui abbiamo determinato la variazione del prezzo base per larghezza x altezza
            $prezzovariato += round($prezzobase * ($percvar / 100), 2) + round($prezzovar, 2);
            $elenco .= "$percvar %"; // indica la percentuale applicata di variazione al prezzo base
            // ora si devono valutare le altre variazioni
            $msg1 .= "Calcolo per $larghezza x $altezza con $percvar % x $prezzobase + $prezzovar = $prezzovariato a applicare a $prezzobase \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            //
            break;
        case 2: // porte grezze
            break;
        case 3: // porte grezze assemblate
            break;
        case 4: // porte grezze preassemblate
            break;
    }
    // alcune variazioni dipendono da elementi del pannello
    // alcune variazioni dipendono da elementi del telaio associato al pannello corrente
    return $elenco;
}

function ValutazioneVariazioniElementi(
    $db1,
    $oggi,
    $prezzobase,
    $eb_ferramenta,
    $eb_coloreferramenta,
    $eb_coprifilo,
    $eb_fermavetro,
    $eb_guarnizione,
    $eb_spessoreporta,
    $eb_tipotelaio,
    $eb_spessoremuro,
    $eb_tipologiaporta,
    $essenza,
    $tipolavorazione,
    $ferramenta,
    $coloreferramenta,
    $coprifilo,
    $fermavetro,
    $guarnizioni,
    $spessoreporta,
    $altezza,
    $larghezza,
    $spessoremuro,
    $tipotelaio,
    $tipologiaporta,
    $stipite,
    $sistemaapertura
) {
    $imp = 0.0; // importo delle variazioni
    // calcolo della variazione dello spessore muro

    $imp += ValutazioneVariazioneSpessoreMuro($db1, $oggi, $prezzobase, $spessoremuro, $essenza); // Calcola prezzo per la variazione di spessore muro
    $imp += ValutazioneVariazioneSpessorePorta($db1, $oggi, $prezzobase, $eb_spessoreporta, $spessoreporta); // Calcola prezzo per la variazione di spessore porta
    $imp += ValutazioneVariazioneTipologiaPorta($db1, $oggi, $prezzobase, $eb_tipologiaporta, $tipologiaporta); // Calcola prezzo per la variazione di tipologia di porta
    $imp += ValutazioneVariazioneFerramenta($db1, $oggi, $prezzobase, $eb_ferramenta, $ferramenta); // Calcola prezzo per la variazione di ferramenta
    $imp += ValutazioneVariazioneColoreFerramenta($db1, $oggi, $prezzobase, $eb_coloreferramenta, $coloreferramenta); // Calcola prezzo per la variazione di colore ferramenta
    $imp += ValutazioneVariazioneCoprifilo($db1, $oggi, $prezzobase, $eb_coprifilo, $coprifilo); // calcola prezzo per la variazione di coprifilo
    $imp += ValutazioneVariazioneFermaVetro($db1, $oggi, $prezzobase, $eb_fermavetro, $fermavetro); // calcola prezzo per la variazione di fermavetro
    $imp += ValutazioneVariazioneStipiti($db1, $oggi, $prezzobase, $stipite); // calcola prezzo per variazione di stipite - 05/02/2017
    $imp += ValutazioneSistemaApertura($db1, $oggi, $prezzobase, $sistemaapertura); // calcola prezzo per sistema d'apertura - 14/03/2017
    return $imp;
}

function ValutazioneSistemaApertura($db1, $oggi, $prezzo, $sistemaapertura)
{
    $impsm = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    $msg1 = "";
    try {
        $SQL1 = "SELECT * FROM variazionilistinosistemiapertura WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
        $SQL1 .= "Idsa = $sistemaapertura ";
        $msg1 = "\r\nQuery = $SQL1 \r\n";
        file_put_contents("c:\\temp\\calcolisistemaapertura.txt", $msg1);
        $result1 = mysqli_query($db1, $SQL1);
        while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
            $percvar = round($row1['VariazionePercentuale']);
            $prezzovar = $row1['PrezzoFisso'];
        }
        mysqli_free_result($result1);
    } catch (Exception $e) {
        $percvar = 0.0;
        $prezzovar = 0.0;
    }
    // ora aggiungo al risultato la variazione calcolata
    $impsm += $prezzo * ($percvar / 100) + $prezzovar;
    $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzo == $impsm";
    file_put_contents("c:\\temp\\calcolisistemaapertura.txt", $msg1);
    return $impsm;
}


function ValutazioneVariazioneSpessoreMuro($db1, $oggi, $prezzo, $spessmuro, $colore)
{
    $impsm = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    $dimblococo = 0.0;
    $varprezzo = 0.0;
    $spessmin = 0.0;
    $spessmax = 0.0;
    try {
        $SQL1 = "SELECT * FROM variazionilistinospessoremuro WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
        $SQL1 .= "SpessMin <= $spessmuro AND $spessmuro <= SpessMax AND ";
        $SQL1 .= "Essenza = $colore ";
        $msg1 = "\r\nQuery = $SQL1 \r\n";
        file_put_contents("c:\\temp\\calcolivariazioniSpessMuro.txt", $msg1);
        $result1 = mysqli_query($db1, $SQL1);
        while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
            $percvar = round($row1['VariazionePercentuale']);
            $prezzovar = $row1['PrezzoFisso'];
            $dimblocco = $row1['DimBlocco'];
            $varprezzo = $row1['VarBloccoPrezzo'];
            $spessmin = $row1['SpessMin'];
            $spessmax = $row1['SpessMax'];
        }
        mysqli_free_result($result1);
    } catch (Exception $e) {
        $percvar = 0.0;
        $prezzovar = 0.0;
        $dimblococo = 0.0;
        $varprezzo = 0.0;
        $spessmin = 0.0;
        $spessmax = 0.0;
    }
    // ora aggiungo al risultato la variazione calcolata
    $impsm += $prezzo * ($percvar / 100) + $prezzovar;
    $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzo == $impsm";
    file_put_contents("c:\\temp\\calcolivariazioniSpessMuro.txt", $msg1);
    if ($dimblocco > 0) {
        if ($spessmuro - $spessmin > 0) {
            // la valutazione extra è da calcolare
            $intervalli = ceil(($spessmuro - $spessmin) / $dimblocco); // ne prende il valore superiore dell'arrotondamento per eccesso
            $impsm += round($intervalli * $varprezzo, 2);
        }
    }
    return $impsm;
}

function ValutazioneVariazioneSpessorePorta($db1, $oggi, $prezzobase, $eb_spessoreporta, $spessoreporta)
{
    // Calcola prezzo per la variazione di spessore porta
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    if ($eb_spessoreporta !== $spessoreporta) {
        // cerca il prezzo da applicare per questa variazione
        try {
            $SQL1 = "SELECT * FROM variazionilistinospessoreporta WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceSpessore = '$spessoreporta' ";
            $msg1 = "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioniSpessPorta.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniSpessPorta.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneTipologiaPorta($db1, $oggi, $prezzobase, $eb_tipologiaporta, $tipologiaporta)
{
    // Calcola prezzo per la variazione di tipologia di porta
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    $msg1 = "\r\nValori $eb_tipologiaporta = $tipologiaporta \r\n";
    file_put_contents("c:\\temp\\calcolivariazioniTipoPorta.txt", $msg1);
    if ($eb_tipologiaporta !== $tipologiaporta) {
        // cerca il prezzo da applicare per questa variazione
        try {
            $SQL1 = "SELECT * FROM variazionilistinotipologiaporta WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceTipologiaPorta = $tipologiaporta ";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioniTipoPorta.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniTipoPorta.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneFerramenta($db1, $oggi, $prezzobase, $eb_ferramenta, $ferramenta)
{
    // Calcola prezzo per la variazione di ferramenta
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    if ($eb_ferramenta !== $ferramenta) {
        // cerca il prezzo da applicare per questa variazione
        try {
            $SQL1 = "SELECT * FROM variazionilistinoferramenta WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceFerramenta = $ferramenta ";
            $msg1 = "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioniFerramenta.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniFerramenta.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneColoreFerramenta($db1, $oggi, $prezzobase, $eb_coloreferramenta, $coloreferramenta)
{
    // Calcola prezzo per la variazione di colore ferramenta
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    if ($eb_coloreferramenta !== $coloreferramenta) {
        // cerca il prezzo da applicare per questa variazione
        try {
            $SQL1 = "SELECT * FROM variazionilistinocoloreferramenta WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceColoreFerramenta = $coloreferramenta ";
            $msg1 = "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioni.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniColFerr.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneCoprifilo($db1, $oggi, $prezzobase, $eb_coprifilo, $coprifilo)
{
    // calcola prezzo per la variazione di coprifilo
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    $msg1 = "\r\nCoprifili $eb_coprifilo = $coprifilo con $prezzobase in data $oggi \r\n";
    file_put_contents("c:\\temp\\calcolivariazioniCoprifilo.txt", $msg1);
    if ($eb_coprifilo !== $coprifilo) {
        // cerca il prezzo da applicare per questa variazione
        try {
            $SQL1 = "SELECT * FROM variazionilistinocoprifilo WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceCoprifilo = '$coprifilo' ";
            $msg1 .= "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioniCoprifilo.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniCoprifilo.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneFermaVetro($db1, $oggi, $prezzobase, $eb_fermavetro, $fermavetro)
{
    // calcola prezzo per la variazione di fermavetro
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    if ($eb_fermavetro !== $fermavetro) {
        // cerca il prezzo da applicare per questa variazione
        $percvar = 0.0;
        $prezzovar = 0.0;
        try {
            $SQL1 = "SELECT * FROM variazionilistinofermavetro WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
            $SQL1 .= "CodiceFermaVetro = '$fermavetro' ";
            $msg1 = "\r\nQuery = $SQL1 \r\n";
            file_put_contents("c:\\temp\\calcolivariazioniFermaVetro.txt", $msg1);
            $result1 = mysqli_query($db1, $SQL1);
            while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
                $percvar = round($row1['VariazionePercentuale']);
                $prezzovar = $row1['PrezzoFisso'];
            }
            mysqli_free_result($result1);
        } catch (Exception $e) {
            $percvar = 0.0;
            $prezzovar = 0.0;
        }
        $imp += $prezzobase * ($percvar / 100) + $prezzovar;
        $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
        file_put_contents("c:\\temp\\calcolivariazioniFermaVetro.txt", $msg1);
    }
    return $imp;
}

function ValutazioneVariazioneStipiti($db1, $oggi, $prezzobase, $stipite)
{
    // calcola prezzo per variazione di stipite rispetto a modelli standard
    $imp = 0.0;
    $percvar = 0.0;
    $prezzovar = 0.0;
    // cerca il prezzo da applicare per questa variazione
    $percvar = 0.0;
    $prezzovar = 0.0;
    $msg1 = "Calcolo variazione prezzo per stipite\r\n";
    try {
        $SQL1 = "SELECT * FROM variazionilistinostipite WHERE DataInizio <= Date('$oggi') AND DataFine IS NULL AND ";
        $SQL1 .= "CodiceStipite = $stipite ";
        $msg1 = "\r\nQuery = $SQL1 \r\n";
        file_put_contents("c:\\temp\\calcolivariazioniStipiti.txt", $msg1);
        $result1 = mysqli_query($db1, $SQL1);
        while ($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)) {
            $percvar = round($row1['VariazionePercentuale']);
            $prezzovar = $row1['PrezzoFisso'];
        }
        mysqli_free_result($result1);
    } catch (Exception $e) {
        $percvar = 0.0;
        $prezzovar = 0.0;
    }
    $imp += $prezzobase * ($percvar / 100) + $prezzovar;
    $msg1 .= "\r\nPrezzo calcolato da $percvar % e $prezzovar su $prezzobase == $imp";
    file_put_contents("c:\\temp\\calcolivariazioniStipiti.txt", $msg1);
    return $imp;
}

?>