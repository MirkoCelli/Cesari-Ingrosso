<!doctype html> 

<head>

<meta charset="utf-8"> 

<title>Cesari Pasticceria (c) 2024</title> 

<link rel="stylesheet" type="text/css" media="screen" href="themes/redmond/jquery-ui-1.8.2.custom.css" />
<link rel="stylesheet" type="text/css" media="screen" href="themes/ui.jqgrid.css" />
<link rel="stylesheet" type="text/css" media="screen" href="themes/ui.multiselect.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/navgriddemo.css" />
<!-- accertarsi che jQuery sia la versione 1.11.0 e jquery.jqgrid.min deve essere la 4.6.0 -->
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/jquery-ui-1.8.2.custom.min.js" type="text/javascript"></script>
<script src="js/jquery.layout.js" type="text/javascript"></script>
<script src="js/lang/grid.locale-it.js" type="text/javascript"></script>
<script type="text/javascript">
	$.jgrid.no_legacy_api = true;
	$.jgrid.useJSON = true;
</script>
<script src="js/jquery.jqGrid.min.js" type="text/javascript"></script>
<script src="js/jquery.tablednd.js" type="text/javascript"></script>
<script src="js/jquery.contextmenu.js" type="text/javascript"></script>
<script src="js/ui.multiselect.js" type="text/javascript"></script>

<link href="tabs2.css" rel="stylesheet" type="text/css">
<!-- script src="/jquery-ui-1.10.3/jquery-1.9.1.js"></script -->
<script src="tabs.js"></script>
<!-- <script src="tabs.js"></script> -->
</head>
<body onload="init()">
<div width="1600">
<form method="POST" action="index.php">	
<?php

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();

$indice = null;
$token = null;
$oggi = date("Y-m-d");
$adesso = date("Y-m-d H:i:s");

  // Pagina principale per la gestione della fatturazione per Adria Top Srl (c) 2014 - In The Net di Gasperoni Robert
  date_default_timezone_set('Europe/Rome'); // imposta la Zona per la fascia oraria
  
  // Attenzione: qui usiamo IP e credenziali dirette e non le leggiamo da dbconfig.php
  $db; // connessione al DB

// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST
$serverpath = ProtocolloHTTP() . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/";
if ($elementi[0] != "") {
    $serverpath .= $elementi[0] . "/";
}

if (!ApriDatabase()) {
   // problemi con il DB
    die("Non riesco ad accedere al Database");
    exit;
}

$identita = leggitoken(); // tre valori (token,user,scadenza)

$token = $identita[0];
$utente = $identita[1];
$scade = $identita[2];
$indice = $identita[3];
if ($scade > 0){
    $miotoken = $token;
}


// la verifica token è all'interno della sezione mainpage
  function IsAuthorized($utente,$parolachiave)
  {
    global $dbhost;
    global $dbname;
    global $dbuser;
    global $dbpwd;
    global $db;
	if (ApriDatabase()){
        if (mysqli_connect_errno()) {
            echo "Errore: " . mysqli_connect_error();
            exit;
        }
        /*
        $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        
        $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD2('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        */
        // 06/08/2024
        $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = '" . str_replace("'", "''", $parolachiave) . "' AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";

        $result = mysqli_query($db, $QryStr) or die("Errore: " . mysqli_error($db));
        $row = mysqli_fetch_row($result);
        $conta = $row[0];
        mysqli_free_result($result);

        $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' ";
        $result = mysqli_query($db, $QryStr) or die("<html><body>Errore: " . mysqli_error($db) . "</body></html>");
        $row = mysqli_fetch_row($result);
        $conta2 = $row[0];
        mysqli_free_result($result);

        if (($conta == 0) && ($conta2 == 0)) {
            return (false);
        }
        ;
        if (($conta == 0) && ($conta2 == 1)) {
            return (false);
        }
        ChiudiDatabase();
        return (true);

    } else {
        return false;
    }
    /*
	  $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname); // ("192.168.1.204","root","itn5f125","cesaripasticceria");
      if (mysqli_connect_errno($conn))
      {
          echo "Errore: " . mysqli_connect_error();
	      exit;
      }
	  $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'","`",$utente) . "' AND password = PASSWORD('" . str_replace("'","''",$parolachiave) . "') AND datafine IS NULL";	  
      $result = mysqli_query($conn,$QryStr) or die("Errore: " . mysqli_error($conn));  	  
	  $row = mysqli_fetch_row($result);
	  $conta = $row[0];
	  mysqli_free_result($result); 

	  $QryStr = "SELECT COUNT(*) AS conta FROM cp_login WHERE account = '" . str_replace("'","`",$utente) . "' ";	  
      $result = mysqli_query($conn,$QryStr) or die("<html><body>Errore: " . mysqli_error($conn) . "</body></html>" );  	  
	  $row = mysqli_fetch_row($result);
	  $conta2 = $row[0];
	  mysqli_free_result($result); 
	  
	  if (($conta == 0) && ($conta2 == 0))
	  {
	      return (false);
	  };
	  if (($conta == 0) && ($conta2 == 1))
	  {
	      return (false);
	  };    
      return(true);
      */
  }

  function IdUtente($utente,$parolachiave)
  {
    global $dbhost;
    global $dbname;
    global $dbuser;
    global $dbpwd;
    global $db;
      if (ApriDatabase()){
        if (mysqli_connect_errno()) {
            echo "Errore: " . mysqli_connect_error();
            exit;
        }
        /*
        $QryStr = "SELECT id FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        
        $QryStr = "SELECT id FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD2('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        */
        // 06/08/2024
        $QryStr = "SELECT id FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = '" . str_replace("'", "''", $parolachiave) . "' AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        $result = mysqli_query($db, $QryStr) or die("Errore: " . mysqli_error($db));
        $row = mysqli_fetch_row($result);
        $conta = $row[0];
        mysqli_free_result($result);
        return ($conta);
      } else {
        echo "Errore: " . mysqli_connect_error();
        exit;
      }
      /*
	  $conn = mysqli_connect("192.168.1.204","root","itn5f125","cesaripasticceria");	  
      if (mysqli_connect_errno($conn))
      {
          echo "Errore: " . mysqli_connect_error();
	      exit;
      }
	  $QryStr = "SELECT id FROM cp_login WHERE account = '" . str_replace("'","`",$utente) . "' AND password = PASSWORD('" . str_replace("'","''",$parolachiave) . "') AND datafine IS NULL";	  
      $result = mysqli_query($conn,$QryStr) or die("Errore: " . mysqli_error($conn));  	  
	  $row = mysqli_fetch_row($result);
	  $conta = $row[0];
	  mysqli_free_result($result); 
	  return ($conta);
      */
  }

  function TipoUtente($utente,$parolachiave)
  {
    global $dbhost;
    global $dbname;
    global $dbuser;
    global $dbpwd;
    global $db;
    if (ApriDatabase()){
        if (mysqli_connect_errno()) {
            echo "Errore: " . mysqli_connect_error();
            exit;
        }
        /*
        $QryStr = "SELECT id, tipo FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        
        $QryStr = "SELECT id, tipo FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = PASSWORD2('" . str_replace("'", "''", $parolachiave) . "') AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        */
        // 06/08/2024
        $QryStr = "SELECT id, tipo FROM cp_login WHERE account = '" . str_replace("'", "`", $utente) . "' AND password = '" . str_replace("'", "''", $parolachiave) . "' AND (datafine IS NULL OR ";
        $QryStr .= "datafine >= DATE('" . date("Y-m-d") . "') )";
        $result = mysqli_query($db, $QryStr) or die("Errore: " . mysqli_error($db));
        $row = mysqli_fetch_row($result);
        $conta = $row[1]; // ritorna il tipo utente
        mysqli_free_result($result);
        return ($conta);

    } else {
        if (mysqli_connect_errno()) {
            echo "Errore: " . mysqli_connect_error();
            exit;
        } else {
            echo "Errore: ApriDatabase fallito";
        }
    }
/*
      $conn = mysqli_connect("192.168.1.204","root","itn5f125","cesaripasticceria");
      if (mysqli_connect_errno($conn))
      {
          echo "Errore: " . mysqli_connect_error();
          exit;
      }
      $QryStr = "SELECT id, tipo FROM cp_login WHERE account = '" . str_replace("'","`",$utente) . "' AND password = PASSWORD('" . str_replace("'","''",$parolachiave) . "') AND datafine IS NULL";	  
      $result = mysqli_query($conn,$QryStr) or die("Errore: " . mysqli_error($conn));  	  
      $row = mysqli_fetch_row($result);
      $conta = $row[1]; // ritorna il tipo utente
      mysqli_free_result($result); 
      return ($conta);
      */
  }
  
  // Qui effettuiamo il Login e poi generiamo la pagina principale da dove si possono effettuare tutte le operazioni
  if (isset($_REQUEST["loginid"]))
  {
    // solo se � un utente autorizzato si pu� procedere altrimenti errore
// devo registrare il token sulla posizione di login dell'utente corrente
    
      $uname = $_REQUEST["username"];
      $password = $_REQUEST["password"];
	  if (!(IsAuthorized($uname,$password)))
	  {
         // Procedura di Login per l'acceso al programma di fatturazione
	     include("loginpage.txt");		 
	     echo "<p><b>Errore: Nome utente o Password non corretti, riprovare!</b></p>";
		 exit;	  
	  }

    if (!ApriDatabase()) {
        include("loginpage.txt");
        echo "<p><b>Errore: Nome utente o Password non corretti, riprovare!</b></p>";
        exit;
    }
        /*
        $sql = "SELECT COUNT(*) AS conta, id AS indice  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = PASSWORD('" . $password . "') AND ";
        $sql .= " datainizio <= DATE('" . $oggi . "') AND (datafine IS NULL OR datafine >= DATE('" . $oggi . "') ) ";
        
        $sql = "SELECT COUNT(*) AS conta, id AS indice  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = PASSWORD2('" . $password . "') AND ";
        $sql .= " datainizio <= DATE('" . $oggi . "') AND (datafine IS NULL OR datafine >= DATE('" . $oggi . "') ) ";
        */
        // 06/08/2024
        $sql = "SELECT COUNT(*) AS conta, id AS indice  FROM cp_login WHERE account = '" . quotestr($uname) . "' AND password = '" . str_replace("'","''", $password) . "' AND ";
        $sql .= " datainizio <= DATE('" . $oggi . "') AND (datafine IS NULL OR datafine >= DATE('" . $oggi . "') ) ";
        $query = mysqli_query($db, $sql);
        $errorisql = mysqli_error($db);
        // while ($row = mysqli_fetch_array($query)) {
        if ($row = mysqli_fetch_array($query)) {
            if ($row["conta"] >= 1) {
                $scad = FissaScadenza();
                $miotoken = GenToken($uname, $password, $scad);
                setcookie("token", $miotoken, $scad, "/");
                setcookie("expireToken", $scad, $scad, "/");
                setcookie("user", $uname, $scad, "/");
                // registro il token appena generato per le successive verifiche
                $indice = $row["indice"];
                setcookie("indice", $indice);
                $scade = $scad;
                //
                $sql = "UPDATE cp_login SET token = '" . quotestr($miotoken) . "', datatoken = '" . $adesso . "' WHERE id = " . $indice;
                $query = mysqli_query($db, $sql);
                if ($query === false) {
                    // c'è stato un errore
                    $errore = "Non sono riuscito a registrare il token";
                }
                // va alla pagina iniziale
            } else {
                $errore = "Utente e/o Credenziali sono errate";
            }
            // ChiudiDatabase();
        }
    } else {
    include("loginpage.txt");
    $scade = -1; // devo forzare il login
  }
    // va alla pagina iniziale
if ($scade < 0) {
    // include("loginpage.txt");
} else {
    if (isset($indice)) {
        if (!VerificaToken($miotoken, $indice, $adesso)) {
            // deve rieffettuare il login se il token non corrisponde
            include("loginpage.txt");
        } else {
            // Pagina principale della procedura di fatturazione per Adria Top
            echo "<input type='hidden' id='iduser' name='iduser' value='" . IdUtente($_REQUEST["username"], $_REQUEST["password"]) . "'>";
            echo "<input type='hidden' id='tipoutente' name='tipoutente' value='" . TipoUtente($_REQUEST["username"], $_REQUEST["password"]) . "'>";
            include("mainpage.txt");

            // ChiudiDatabase(); // in php non è necessario se si chiude la pagina php viene chiuso in automatico il canale
        }  
}
  
}
?>
</form>
</div>
</body>
</html>