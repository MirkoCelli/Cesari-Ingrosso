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

$idcliente = null;
if (isset($_REQUEST["idcliente"])) {
    $idcliente = $_REQUEST["idcliente"];
    $wh .= " AND cliente = $idcliente ";
}

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
        $SQL .= "b.intermediario as intermediario, i.Denominazione as nomeintermediario ";
        $SQL .=	"FROM cp_cliente b LEFT OUTER JOIN cp_listinoprezzi l ON (l.id = b.listino) LEFT OUTER JOIN cp_intermediario i ON (i.id = b.intermediario) ";
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

    case 45: // congelamento degli ordini dei clienti per la giornata indicata

        // NOTA BENE: con il comando MySQL "ALTER TABLE cp_dettaglioordine AUTO_INCREMENT = 1" si possono resettare gli autoincrementi delle tabelle mysql
        $giorno = $_REQUEST["giorno"]; // il formato deve essere ISO
        // passo 1) per tutti i clienti che hanno uno schemadefault aperto si possono generare gli ordini del giorno indicato e si mette lo stato aperto = 0
        $sql = "SELECT c.id AS c1_id, s.id AS s1_id, o.id as o1_id ";
        $sql .= "FROM cp_cliente c ";
        $sql .= "JOIN cp_schemadefault s ON (c.id = s.cliente AND s.datainizio <= DATE('" . $giorno . "') AND  s.datafine IS NULL) ";
        $sql .= "LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = c.id AND o.schematico = s.id AND o.dataordine = DATE('" . $giorno ."')) ";
        // 2024-10-14 - escludere i clienti disabilitati (cioè abilitato = 0)
        $sql .= "WHERE c.abilitato = 1 ";

        $result = mysqli_query($db, $sql);
        $errore = mysqli_error($db);
        if (!$result)
        {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            header("Content-type: application/json; charset=utf-8");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
            exit;
        }

        // passo 2) ogni singolo cliente attivo si deve completare il suo ordine con tutti i records
        while ($row = mysqli_fetch_array($result)) {

            $idcliente = $row["c1_id"]; // cliente
            $idschema = $row["s1_id"]; // suo schema
            $idordine = $row["o1_id"]; // suo eventuale ordine già presente alla data

            if (!isset($idordine) || ($idordine == null)){
             // se per il giorno non ci sono record ordinecliente allora vanno inseriti tutti gli ordini data dalla query $sql1 altrimenti dato l'idordine si aggiungono solo i records che non sono presenti negli ordini dato in $sql2

             // qui inserisco il record in ordinecliente per questo cliente,schematico,dataordine,stato = 2,autorizzatosuperospesa = 0, codiceautorizzazione = 0
             $sqlins = "INSERT INTO cp_ordinecliente (cliente, schematico, dataordine, stato, autorizzatosuperamentospesa, codiceautorizzazione) VALUES (";
             $sqlins .= $idcliente . ",";
             $sqlins .= $idschema . ",";
             $sqlins .= "DATE('" . $giorno . "'),";
             $sqlins .= "2,"; // stato
             $sqlins .= "0, 0)"; // autorizzatosuperospesa, codiceautorizzazione
             // esegue la insert e si fa dare l'idordine corrispondente
             $resultins = mysqli_query($db, $sqlins);
             $idordine = mysqli_insert_id($db);
             $errore = mysqli_error($db);
             if ($resultins){
                 //
             } else {
                // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
                header("Content-type: application/json");
                echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: ".$errore."\"}";
                exit;
             }
             //
             $sql1 = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato) \n";

             $sql1 .= "SELECT " . $idordine . " AS ordinecliente, b.s_sequenza AS dettaglioordine, b.s_prodotto AS prodotto, p.gruppo AS gruppo, ";
             $sql1 .= "b.s_quantita AS quantita, b.s_unitamisura AS unitamisura, 0 AS stato \n";
             $sql1 .= "FROM ";
              //
             $sql1 .= "(SELECT t2.*, t1.* FROM (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, d.sequenza AS s_sequenza, ";
             $sql1 .= "d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
             $sql1 .= "ISODAYOFWEEK('" . $giorno . "') AS s_giornosettimana2 FROM cp_schemadefault s, cp_dettaglioschema d ";
             $sql1 .= "WHERE  s.cliente = " . $idcliente . " AND s.id = d.schematico AND s.datainizio <= DATE('" . $giorno . "') AND ";
             $sql1 .= "(s.datafine IS NULL OR DATE('" . $giorno . "') <= s.datafine) AND d.giornosettimana = ISODAYOFWEEK('" . $giorno . "') AND ";
             $sql1 .= "d.quantita > 0 ORDER BY d.sequenza) t1 ";
             $sql1 .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
             $sql1 .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
             $sql1 .= "ISODAYOFWEEK('" . $giorno . "') AS o_giornosettimana2 FROM cp_ordinecliente o, cp_dettaglioordine dt ";
             $sql1 .= "WHERE o.cliente = " . $idcliente . " AND o.id = dt.ordinecliente AND o.dataordine = DATE('" . $giorno . "') AND dt.quantita > 0 ";
             $sql1 .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) HAVING t2.o_dataordine IS NULL ) b, \n";
              //
             $sql1 .= "cp_prodotto p, cp_listinoprezzi l,  cp_dettagliolistino dl,  cp_cliente c WHERE ";
             $sql1 .= "c.id = " . $idcliente . " AND ";
             $sql1 .= "c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND  l.id = dl.listino AND  dl.prodotto = p.id";
             // nella riga dettgalio ordine devo inserire i seguenti dati:
             // (ordinecliente, dettaglioordine = sequenza, prodotto, gruppo, quantita, unitamisura,stato = 0)
             $result1 = mysqli_query($db, $sql1);
             $errore = mysqli_error($db);
             if ($result1) {
                 // $idordine = mysqli_insert_id($db);
                 // inserimento dei dettaglio ordine completato
             } else {
                 // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
                 header("Content-type: application/json");
                 echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
                 exit;
             }
            }
            else
            {
             // se l'ordine per il giorno e il cliente è già presente allora si inseriscono solo i dati che provengono da schemadefault e sezione ordinecliente è null

             $sql2 = "INSERT INTO cp_dettaglioordine (ordinecliente, dettaglioordine, prodotto, gruppo, quantita, unitamisura, stato) \n";

             $sql2 .= "SELECT " . $idordine . " AS ordinecliente, b.s_sequenza AS dettaglioordine, b.s_prodotto AS prodotto, p.gruppo AS gruppo, ";
             $sql2 .= "b.s_quantita AS quantita, b.s_unitamisura AS unitamisura, 0 AS stato \n";
             $sql2 .= "FROM ";
               //
             $sql2 .= "(SELECT t2.*, t1.* FROM (SELECT d.id AS s_id, s.cliente AS s_cliente, d.schematico AS s_schema, d.giornosettimana AS s_giornosettimana, d.sequenza AS s_sequenza, ";
             $sql2 .= "d.prodotto AS s_prodotto, d.quantita AS s_quantita, d.unitamisura AS s_unitamisura, ";
             $sql2 .= "ISODAYOFWEEK('" . $giorno . "') AS s_giornosettimana2 FROM cp_schemadefault s, cp_dettaglioschema d ";
             $sql2 .= "WHERE  s.cliente = " . $idcliente . " AND s.id = d.schematico AND s.datainizio <= DATE('" . $giorno . "') AND ";
             $sql2 .= "(s.datafine IS NULL OR DATE('" . $giorno . "') <= s.datafine) AND d.giornosettimana = ISODAYOFWEEK('" . $giorno . "') AND ";
             $sql2 .= "d.quantita > 0 ORDER BY d.sequenza) t1 ";
             $sql2 .= "LEFT OUTER JOIN (SELECT dt.id AS o_id, o.dataordine AS o_dataordine, dt.ordinecliente AS o_ordinecliente, dt.dettaglioordine AS o_dettagliordine, ";
             $sql2 .= "dt.prodotto AS o_prodotto, dt.gruppo AS o_gruppo, dt.quantita AS o_quantita, dt.unitamisura AS o_unitamisura, dt.stato AS o_stato, ";
             $sql2 .= "ISODAYOFWEEK('" . $giorno . "') AS o_giornosettimana2 FROM cp_ordinecliente o, cp_dettaglioordine dt ";
             $sql2 .= "WHERE o.cliente = " . $idcliente . " AND o.id = dt.ordinecliente AND o.dataordine = DATE('" . $giorno . "') AND dt.quantita > 0 ";
             $sql2 .= "ORDER BY dt.dettaglioordine) t2 ON (t1.s_prodotto = t2.o_prodotto) HAVING t2.o_dataordine IS NULL ) b, \n";
               //
             $sql2 .= "cp_prodotto p, cp_listinoprezzi l,  cp_dettagliolistino dl,  cp_cliente c WHERE ";
             $sql2 .= "c.id = " . $idcliente . " AND ";
             $sql2 .= "c.id = b.s_cliente AND c.listino = l.id AND SOLO_NOT_NULL(b.s_prodotto,b.o_prodotto) = p.id AND  l.id = dl.listino AND  dl.prodotto = p.id";
             // nella riga dettgalio ordine devo inserire i seguenti dati:
             // (ordinecliente, dettaglioordine = sequenza, prodotto, gruppo, quantita, unitamisura,stato = 0)
             $result2 = mysqli_query($db, $sql2);
             $errore = mysqli_error($db);
             if ($result2) {
                 // $idordine = mysqli_insert_id($db);
                 // inserimento dei dettaglio ordine completato
             } else {
                 // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
                 header("Content-type: application/json");
                 echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
                 exit;
             }
            }
        }

        mysqli_free_result($result);

        // passo 2.5) - se il cliente è disabilitato ed ha già inserito una variazione all'ordine del giorno allora va messo lo stato dell'ordine a chiuso = 1

        $sql = "SELECT c.id AS c1_id, s.id AS s1_id, o.id as o1_id ";
        $sql .= "FROM cp_cliente c ";
        $sql .= "JOIN cp_schemadefault s ON (c.id = s.cliente AND s.datainizio <= DATE('" . $giorno . "') AND  s.datafine IS NULL) ";
        $sql .= "LEFT OUTER JOIN cp_ordinecliente o ON (o.cliente = c.id AND o.schematico = s.id AND o.dataordine = DATE('" . $giorno ."')) ";
        // 2024-10-14 - i clienti disabilitati (cioè abilitato = 0) non possono avere ordini aperti
        $sql .= "WHERE c.abilitato = 0 ";

        $result = mysqli_query($db, $sql);
        $errore = mysqli_error($db);
        if (!$result)
        {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            $errore .= "- Query sui clienti dianilitati in errore";
            header("Content-type: application/json; charset=utf-8");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
            exit;
        }

        // per ogni singolo cliente disattivo si deve chiudere il suo ordine perchè non deve essere caricato
        while ($row = mysqli_fetch_array($result)) {
            $sql3 = "UPDATE cp_ordinecliente SET stato = 1 WHERE stato = 2 AND dataordine = DATE('" . $giorno . "') AND id = " . $row['o1_id'] ;
            $result3 = mysqli_query($db, $sql3);
            $errore = mysqli_error($db);
            if (!$result3) {
                header("Content-type: application/json; charset=utf-8");
                $errore .= " - Impossibile fare la chiusura degli ordini non abilitati alla data " . $giorno;
                echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile inserire il nuovo ordine: " . $errore . "\"}";
                exit;
            }
        }
        mysqli_free_result($result);

        // passo 3) solo ordini che sono in stato aperto = 2 nella giornata si possono chiudere con stato = 7 = da produrre

        $sql3 = "UPDATE cp_ordinecliente SET stato = 7 WHERE stato = 2 AND dataordine = DATE('" . $giorno . "') ";
        $result3 = mysqli_query($db, $sql3);
        $errore = mysqli_error($db);
        if ($result3) {
            // $idordine = mysqli_insert_id($db);
            // inserimento dei dettaglio ordine completato
        } else {
            // ATTENZIONE c'è stato un problema nell'insert dell'ordine fermare tutto !!!!
            header("Content-type: application/json");
            echo "{\"status\" : \"KO\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"Non è stato possibile aggiornare lo stato degli ordini del " . $giorno ." a ^Chiuso^: " . $errore . "\"}";
            exit;
        }
        // risposta in formato JSON
        header("Content-type: application/json; charset=utf-8");
        echo "{\"status\" : \"OK\" , \"giorno\" : \" " . $giorno . "\" , \"error\" : \"\"}";
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

?>
