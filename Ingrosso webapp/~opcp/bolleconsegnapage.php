<?php
include("dbconfig.php");

// BOLLE CONSEGNA
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
    if( $fld=='id' || $fld =='codicecliente' || $fld=='denominazione' || $fld=='nomebreve' || $fld=='annotazioni' || $fld=='listino' || $fld=='intermediario' || $fld == 'nomestato') {
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

        $giorno = date("Y-m-d");
        if (isset($_REQUEST["giorno"])) {
            $giorno = $_REQUEST["giorno"];
        }

        $sqlconta = "SELECT COUNT(*) FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) WHERE o.dataordine = DATE('" . $giorno ."') AND o.stato <> 1 ";

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

        // Query per ottenere gli ordini della giornata indicata in $giorno con le relative bolle di consegna e i totali corrispondenti


        $SQL = "SELECT o.id as id, c.id AS cliente, c.Denominazione AS nomecliente, o.dataordine AS dataordine, o.stato AS statoordine, so.descrizionestato AS nomestato, o.id AS ordine,";
        $SQL .= "i.id AS intermediario, i.Denominazione AS nomeintermediario, i.tipoIntermediazione AS tipointermediazione,  b.id AS bolla,";
        $SQL .= "b.dataconsegna AS databolla, b.numbolla AS numerobolla, b.totalebolla AS totalebolla, b.fatturato AS fatturata, b.rapporto AS rapporto,";
        $SQL .= "SUM(IFNULL(d.quantita,0) * IFNULL(dl.prezzounitario,dg.prezzounitario)) AS totaleordine, ";
        // 2024-08-17 - sostituisce le formule per includere anche i prodotti venduti a peso kg
        /*
        $SQL .= "SUM(IFNULL(CEIL((((r.perc_b % 123456) / 1000) / 100) * d.quantita),0) * IFNULL(dl.prezzounitario,dg.prezzounitario)) AS totaleb, ";
        $SQL .= "SUM(IFNULL(FLOOR((((r.perc_n % 123456) / 1000) / 100) * d.quantita),0) * IFNULL(dl.prezzounitario,dg.prezzounitario)) AS totalen, ";
        */
        /*
        $SQL .= "SUM(IFNULL(IF(dl.unitamisura = 'PZ',CEIL((((r.perc_b % 123456) / 1000) / 100) * (d.quantita)) * IFNULL(dl.prezzounitario,dg.prezzounitario),(((r.perc_b % 123456) / 1000) / 100) * (d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario)),0)) AS totaleb, \n";
        $SQL .= "SUM(IFNULL(IF(dl.unitamisura = 'PZ',FLOOR((((r.perc_n % 123456) / 1000) / 100) * (d.quantita) ) * IFNULL(dl.prezzounitario,dg.prezzounitario),ROUND((((r.perc_n % 123456) / 1000) / 100) * (d.quantita) * IFNULL(dl.prezzounitario,dg.prezzounitario),2)),0)) AS totalen, \n";
        */
        // temporaneo per mandare un valore null - 04/09/2024
        $SQL .= "NULL AS totaleb, \n";
        $SQL .= "NULL AS totalen, \n";

        //
        $SQL .= "((r.perc_b % 123456) / 1000) AS percentuale_b, ((r.perc_n % 123456) / 1000) AS percentuale_n ";
        $SQL .= "FROM cp_cliente c JOIN cp_rapportoconsegna r ON (r.cliente = c.id) JOIN cp_ordinecliente o ON (o.cliente = c.id) ";
        $SQL .= "LEFT OUTER JOIN cp_dettaglioordine d ON (d.ordinecliente = o.id) ";
        $SQL .= "LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = c.listino) ";
        $SQL .= "LEFT OUTER JOIN cp_dettagliolistino dl ON (dl.listino = l.id AND dl.prodotto = d.prodotto) ";
        $SQL .= "LEFT OUTER JOIN cp_dettagliolistinogruppi dg ON (dg.listino = l.id AND dg.gruppo = d.gruppo) ";
        $SQL .= "LEFT OUTER JOIN cp_bollaconsegna b ON (b.ordine = o.id AND b.cliente = o.cliente) ";
        $SQL .= "LEFT OUTER JOIN cp_intermediario i ON (c.intermediario = i.id) ";
        $SQL .= "LEFT OUTER JOIN cp_statoordine so ON (so.id = o.stato) ";
        $SQL .= "WHERE o.dataordine = DATE('" . $giorno ."') AND o.stato <> 1 " . $wh; // 2024-10-14 escludere dli ordini chiusi
        $SQL .= "GROUP BY o.id ";
        // 2024-08-09 - devo unirci anche i dati per il rivenditore o agente che abbiamo in codcliente in intermediario

        $SQL .= /*"ORDER BY o.sequenza " . $sidx . " " . $sord .*/ " LIMIT " . $start . " , " . $limit;

        /*
        $SQL = "SELECT b.id as id, b.CodiceCliente as codicecliente, b.Denominazione as denominazione, ";
        $SQL .= "b.NomeBreve as nomebreve, b.Annotazioni as annotazioni, b.listino as listino, l.tipo as tipolistino, ";
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario ";
        $SQL .=	"FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
        $SQL .= "WHERE 1 = 1 ".$wh." ORDER BY ".$sidx." ". $sord." LIMIT ".$start." , ".$limit;
        */

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
            // qui devo calcolare i singoli totale_b e totale_n
            $totali = CalcolaTotaliRapportati($row["id"],$row["percentuale_b"],$row["percentuale_n"]);
            //
            $s .= "<row id='" . xml_entities($row['id']) . "'>";
            $s .= "<cell>" . xml_entities($row['id']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['cliente']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomecliente']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['dataordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['statoordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomestato']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['ordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['intermediario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['nomeintermediario']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['tipointermediazione']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['bolla']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['databolla']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['numerobolla']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['totalebolla']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['fatturata']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['rapporto']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['totaleordine']) . "</cell>";
            $s .= "<cell>" . xml_entities($totali['totale_b']) . "</cell>";
            $s .= "<cell>" . xml_entities($totali['totale_n']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['percentuale_b']) . "</cell>";
            $s .= "<cell>" . xml_entities($row['percentuale_n']) . "</cell>";
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
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        $qrystr = "INSERT INTO cp_cliente (CodiceCliente, Denominazione, NomeBreve, Annotazioni, listino, intermediario) ";
        $qrystr .= "VALUES ";
        $qrystr .= "($codicecliente, $denominazione, $nomebreve, $annotazioni, $listino, $intermediario)";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (21): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        $id = mysqli_insert_id($db);
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
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        $qrystr = "UPDATE cp_cliente SET CodiceCliente = $codicecliente, Denominazione = $denominazione, ";
        $qrystr .= "NomeBreve = $nomebreve, Annotazioni = $annotazioni, listino = $listino, intermediario = $intermediario ";
        $qrystr .= "WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
        if (!$result){
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (22): " . mysqli_error($db), true, 500);
            echo("Error description: " . mysqli_error($db));
            exit; // fine dello script php
        }
        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        // file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 23:	// DELETE
        // delete current row and response with old id
        $msg = "";
        foreach ( $_REQUEST as $k => $v ){
           $msg .= "$k = $v ; ";
        }
        $id = $_REQUEST['id'];
        $qrystr = "DELETE FROM cp_cliente WHERE id = $id";
        $msg .= "\n$qrystr";
        $result = mysqli_query($db,$qrystr);
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
        $nomeintermediario = quoteStr(($_REQUEST['nomeintermediariov']));

        echo "{\"id\" : \"$id\"}";
        // scrivo su un file di appoggio il contenuto della $_REQUEST
        //--> file_put_contents("c:\\temp\\datiletti.txt",$msg);
        break;
    case 50:  // CSV
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

// 2024-09-05 - Calcola per l'ordine corrente dato da $id il totale_b e totale_n secondo le operazioni date in riepilogoordine.php

function CalcolaTotaliRapportati($id,$perc_b,$perc_n){
    global $db;

    $totale_b = 0.00;
    $totale_n = 0.00;
    //
    $quantitagruppi = [];
    $prezzogruppi = [];
    $unmisgruppi = [];

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
    $sql .= "WHERE d.ordinecliente = " . $id . " AND d.stato = 0 ";
    $sql .= "ORDER BY g.NomeGruppo ";

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
        $unmis = $row["unitamisura"];
        // ora verifico se esiste il gruppo in $quantitagruppo e $unmisgruppi
        if (!isset($quantitagruppi[$gruppo])) {
            $quantitagruppi[$gruppo] = 0;
            $prezzogruppi[$gruppo] = $prezzo;
            $unmisgruppi[$gruppo] = $unmis;
            $qtab[$gruppo] = 0;
            $qtan[$gruppo] = 0;
            $totb[$gruppo] = 0.00;
            $totn[$gruppo] = 0.00;
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

    foreach ($quantitagruppi as $key => $value) {
        if ($unmisgruppi[$key] == "PZ") {
            $qtab[$key] += ceil($quantitagruppi[$key] * ($perc_b/100));
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += floor($quantitagruppi[$key] * ($perc_n/100));
            $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
        }
        if ($unmisgruppi[$key] == "KG") {
            $qtab[$key] += ($quantitagruppi[$key] * ($perc_b/100));
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += ($quantitagruppi[$key] * ($perc_n/100));
            $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
        }
        /*
        $qtab[$key] = ceil($quantitagruppi[$key] * ($perc_b/100));
        $totb[$key] = $qtab[$key] * $prezzogruppi[$key];
        $qtan[$key] = floor($quantitagruppi[$key] * ($perc_n/100));
        $totn[$key] = $qtan[$key] * $prezzogruppi[$key];
        */
        // sommo queste quantità agli accumulatori previsti
        $qtabolla_1 += $qtab[$key];
        $totalebolla_1 += $totb[$key];
        $qta_n_1 += $qtan[$key];
        $tot_n_1 += $totn[$key];
    }
    // determino i totali
    $totale_b = $totalebolla_1;
    $totale_n = $tot_n_1;

    mysqli_free_result($result);
    //
    return ["totale_b" => $totale_b, "totale_n" => $totale_n];
}
?>
