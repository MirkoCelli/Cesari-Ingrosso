<?php
include("dbconfig.php");

// sezione per la verifica delle cookies
if (!isset($_COOKIE["token"])) {
    die("Utente non abilitato ad usare questa risorsa");
    exit;
}
// fine verifica cookies - 06/06/2024

// introdotto per escludere il warning in output (da togliere appena si trova la soluzione

error_reporting(E_ERROR | E_PARSE);

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
    if( $fld=='id' || $fld =='codicecliente' || $fld=='denominazione' || $fld=='nomebreve' || $fld=='annotazioni' || $fld=='listino' || $fld=='intermediario') {
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

    case 5: // per ottenere i dati del tpagam (per ottenere i dati delle combobox quando serve)
        // dalla tabella schemadefault e listinoprezzi ottengo gli elementi per il codice pagamento (esempio di query)

        $query = "SELECT l.id as id, l.tipo as nomelistino ";
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
            $valoriStato .= $row['id'] . ":" . $row['descrizione'];
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
        $result = mysqli_query($db, "SELECT COUNT(*) AS count FROM cp_cliente b WHERE 1 = 1 AND b.sequenza >= 0 " . $wh);
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
        $SQL = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, ";
        $SQL .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino, ";
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario ";
        $SQL .= "FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
        $SQL .= "WHERE 1 = 1  AND b.sequenza >= 0 " . $wh . " ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . " , " . $limit;
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
            $s .= "<cell>" . xml_entities($row['codicecliente']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['denominazione']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomebreve']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['listino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['tipolistino']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['intermediario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeintermediario']) . "</cell>";
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
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        $qrystr = "INSERT INTO cp_cliente (CodiceCliente, Denominazione, NomeBreve, Annotazioni, listino, intermediario) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($codicecliente, $denominazione, $nomebreve, $annotazioni, $listino, $intermediario)";
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
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        $qrystr = "UPDATE cp_cliente SET CodiceCliente = $codicecliente, Denominazione = $denominazione, ";
        $qrystr .= "NomeBreve = $nomebreve, Annotazioni = $annotazioni, listino = $listino, intermediario = $intermediario ";
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
        $qrystr = "DELETE FROM cp_cliente WHERE id = $id";
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
        $codicecliente = quoteStr(strtoupper($_REQUEST['codicecliente']));
        $denominazione = quoteStr(($_REQUEST['denominazione']));
        $nomebreve = quoteStr(($_REQUEST['nomebreve']));
        $annotazioni = quoteStr(($_REQUEST['annotazioni']));
        $listino = numberOrNull($_REQUEST['listino']);
        $tipolistino = quoteStr(($_REQUEST['tipolistino']));
        $intermediario = numberOrNull($_REQUEST['intermediario']);
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        //--> file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 44: // clonazione del record fornito con tutti i suoi dettagli e poi ritorno id del nuovo record
        // registro id del record di riferimento
        $id_old = $_REQUEST["id"];
        $adesso = date('Y-m-d', time());
        $sql = "INSERT INTO cp_schemadefault (cliente,datainizio,datafine,listino,responsabile,limitespesa) \n";
        $sql .= "SELECT cliente, Date('" . $adesso . "'), NULL, listino, responsabile, limitespesa \n";
        $sql .= "FROM cp_schemadefault \n";
        $sql .= "WHERE id = " . $id_old;

        $result = mysqli_query($db, $sql);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $errore = mysqli_error($db);
        $id = mysqli_insert_id($db);

        if ($errore !== ""){
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . $errore, true, 500);
                echo ("Error description: " . mysqli_error($db));
                exit; // fine dello script php
        }

        $sql = "INSERT INTO cp_dettaglioschema (schematico, giornosettimana, sequenza, prodotto, quantita, unitamisura) \n";
        $sql .= "SELECT $id as schematico, giornosettimana, sequenza, prodotto, quantita, unitamisura \n";
        $sql .= "FROM cp_dettaglioschema \n";
        $sql .= "WHERE schematico = " . $id_old . " ORDER BY giornosettimana, sequenza ";

        $result = mysqli_query($db, $sql);
        if (!$result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . mysqli_error($db), true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $errore = mysqli_error($db);
        if ($errore !== "") {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (50-1): " . $errore, true, 500);
            echo ("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        // creo il nuovo record in schemadefault e mi faccio dare il suo id
        header("Content-type: text/json");
        echo "{\"id\" : \"$id\", \"error\":\"$errore\"}";
        break;
    case 50: // CSV
        $result = mysqli_query($db, "SELECT COUNT(*) AS count FROM cp_cliente b WHERE 1 = 1 " . $wh);
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
        $SQL = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, ";
        $SQL .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino, ";
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario ";
        $SQL .= "FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
        $SQL .= "WHERE 1 = 1 " . $wh . " ORDER BY " . $sidx . " " . $sord; //  . " LIMIT " . $start . " , " . $limit;

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
