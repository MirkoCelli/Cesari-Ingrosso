<?php
/*  PAGINA PER LE SEZIONI TABELLE - 27/06/2024 - Robert Gasperoni */

error_reporting(E_ERROR | E_PARSE);
$user = $_REQUEST['user']; // account con cui si è collegato l'utente
include("dbconfig.php");
// connect to the database
$db = mysqli_connect($dbhost, $dbuser, $dbpassword)
      or die("Connection Error: " . mysqli_error($db));
mysqli_select_db($db,$database) or die("Error conecting to db.");
/*
  $result = mysqli_query($db,"SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = \"NomeTabella\" AND a.IdLogin = l.IdLogin AND l.user = \"" . $user + "\" ");
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){}
*/

echo "<html>\r\n";
echo "<body>\r\n";
// echo "<H1>Tabelle</h1>\r\n";
echo "  <div>\r\n";
echo "    <ul id=\"tabelletabs\">\r\n";
echo "      <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('gruppiprodotto.php','#pagina2');\">Gruppi Prodotto</A></LI>\r\n";
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('prodotti.php?user=$user','#pagina2');\">Prodotti</A></LI>\r\n";
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('intermediari.php?user=$user','#pagina2');\">Intermediari</A></LI>\r\n";
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('responsabili.php?user=$user','#pagina2');\">Responsabili</A></LI>\r\n";
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('credenziali.php?user=$user','#pagina2');\">Credenziali</A></LI>\r\n";
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('statoordine.php','#pagina2');\">Tab. Stato Ordine</A></LI>\r\n";
// 2024-10-11 - aggiunta la tabella per il periodo delle ferie
echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('ferie.php','#pagina2');\">Tab. Periodo Ferie</A></LI>\r\n";
// echo "	    <LI><A class=\"tabelle\" href=\"javascript:loadPageToDiv('autorizzasuperospesa.php','#pagina2');\">Autorizzazioni Supero Spesa</A></LI>\r\n"; // 05/08/2024 non vogliono più limit di spesa
echo "	 </ul>\r\n";
echo "  </div>\r\n";

/* PRIMA RIGA DEL MENU' DI SELEZIONE */
/* come si riesce a fornire gli elementi in base ai diritti di accesso: da COFAS

  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'ZONA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "      <li><a href=\"javascript:loadPageToDiv('zona.php','#tabella');\">Zona</a></li>\r\n";
  }
  $result = mysqli_query($db,"SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'PROVINCIA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ");
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('provincia.php','#tabella');\">Provincia</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'COLORI' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "      <li><a href=\"javascript:loadPageToDiv('colori.php','#tabella');\">Impiallacciatura</a></li>\r\n"; // Colori-Essenza
  }

*/

// sezione delle tabelle
echo "  <div class=\"tabelleContent2\" id=\"tabella2\">\r\n";
// echo "     Selezionare la tabella che si vuole gestire.\r\n";
echo "  </div>\r\n";
?>
<div id="pagina2" class="sectionContent2">
    <h1>Informazioni Generali</h1>
    Questa pagina fornisce informazioni sull'applicazione Operatori per Cesari Pasticceria
</div>
<?php
echo " </body>\r\n";
echo " </html>\r\n";
mysqli_close($db);
?>