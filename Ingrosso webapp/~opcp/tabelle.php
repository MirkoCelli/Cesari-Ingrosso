<?php
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
  echo "<H1>Tabelle</h1>\r\n";
  echo "  <div>\r\n";
  echo "    <ul id=\"tabelletabs\">\r\n";
  /* PRIMA RIGA DEL MENU' DI SELEZIONE */
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
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MDF' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "      <li><a href=\"javascript:loadPageToDiv('mdf.php','#tabella');\">MDF</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'SUPPORTOMDF' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "      <li><a href=\"javascript:loadPageToDiv('supportomdf.php','#tabella');\">Coll.MDF - Mat.</a></li>\r\n"; // Supporto MDF
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MATERIALE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "      <li><a href=\"javascript:loadPageToDiv('materiale.php','#tabella');\">Materiale</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MAZZETTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('mazzetta.php','#tabella');\">Mazzetta</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MASSELLOPERIMETRALE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('masselloperimetrale.php','#tabella');\">Massello Perimetrale</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'COLOREMASSELLOPERIMETRALE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('colorimasselloperimetrale.php','#tabella');\">Colore Massello Perimetrale</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOMASSELLO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipomassello.php','#tabella');\">Massello</a></li>\r\n"; // Tipo Massello
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'COPRIFILI' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('coprifili.php','#tabella');\">Coprifili</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'COLORICOPRIFILI' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('coloricoprifili.php','#tabella');\">Colori Coprifili</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOBUGNA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipobugna.php','#tabella');\">Tipo Bugna</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOCONTROBUGNA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipocontrobugna.php','#tabella');\">Tipo Contro Bugna</a></li>\r\n";
  }
  // 05/02/2017 - stipite
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'STIPITE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('stipite.php','#tabella');\">Stipite</a></li>\r\n";
  }

  echo "	 </ul>\r\n";
  echo "  </div>\r\n";

  /* SECONDA RIGA DEL MENU' DI SELEZIONE */

  echo "  <div>\r\n";
  echo "	 <ul id=\"tabelle2tabs\">\r\n";
$qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MATERIALEFAMIGLIA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('materialefamiglia.php','#tabella');\">Materiale Famiglia</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'FAMIGLIA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('famiglia.php','#tabella');\">Codice Porta</a></li>\r\n"; // Famiglia
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOLOGIALAVORAZIONE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipologialavorazione.php','#tabella');\">Tipologia Lavorazione</a></li>	  \r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOLOGIAPORTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipologiaporta.php','#tabella');\">Tipologia Porta</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOPORTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipoporta.php','#tabella');\">Spessore Porta</a></li>\r\n"; // Tipo Porta
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'FAMIGLIATELAIO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('famigliatelaio.php','#tabella');\">Famiglia Telaio</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOFERMAVETRO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipofermavetro.php','#tabella');\">Tipo Ferma Vetro</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'GUARNIZIONI' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('guarnizioni.php','#tabella');\">Guarnizioni</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'FERRAMENTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('ferramenta.php','#tabella');\">Ferramenta</a></li>\r\n";
  }
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'COLOREFERRAMENTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('coloreferramenta.php','#tabella');\">Colori Ferramenta</a></li>\r\n";
  }
  // test per tempiproduzione
  echo "	  <li><a href=\"javascript:loadPageToDiv('tempiproduzione.php','#tabella');\">Tempi Produzione</a></li>\r\n";
  //
  echo "    </ul>\r\n";
  echo "  </div>\r\n";

  /* TERZA RIGA DEL MENU' DI SELEZIONE */

  echo "  <div>\r\n";
  echo "	 <ul id=\"tabelle2tabs\">\r\n";
  // spessore MATERIALE massello perimetrale
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'SPESSOREMATERIALEMP' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('spessorematerialemp.php','#tabella');\">Spessore Materiale M.P.</a></li>\r\n";
  }
  // spessore colore massello perimetrale
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'SPESSORECOLOREMP' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('spessorecoloremp.php','#tabella');\">Spessore Colore M.P.</a></li>\r\n";
  }
  // tipo battuta
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOBATTUTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipobattuta.php','#tabella');\">Tipo Battuta</a></li>\r\n";
  }
  // battuta-porta
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'BATTUTAPORTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('battutaporta.php','#tabella');\">Battuta Porta</a></li>\r\n";
  }
  // tipo telaio
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOTELAIO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipotelaio.php','#tabella');\">Tipo Telaio</a></li>\r\n";
  }
  // disegno porta
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'DISEGNOPORTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('disegnoporta.php','#tabella');\">Disegno Porta</a></li>\r\n";
  }
  // tipo misura
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'TIPOMISURA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('tipomisura.php','#tabella');\">Tipo Misura</a></li>\r\n";
  }
  // misure battuta telaio
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MISUREBATTUTATELAIO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('misurebattutatelaio.php','#tabella');\">Misure Battuta Telaio</a></li>\r\n";
  }
  // misure massellino
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MISUREMASSELLINO' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('misuremassellino.php','#tabella');\">Misure Massellino</a></li>\r\n";
  }
  // misure tipologia porta
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MISURETIPOLOGIAPORTA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
    echo "	  <li><a href=\"javascript:loadPageToDiv('misuretipologiaporta.php','#tabella');\">Misure Tipologia Porta</a></li>\r\n";
  }
  // fine del menù delle TABs
  echo "    </ul>\r\n";
  echo "  </div>\r\n";
  //
  /* QUARTA RIGA DEL MENU' DI SELEZIONE */

  echo "  <div>\r\n";
  echo "	 <ul id=\"tabelle2tabs\">\r\n";
  // modello pantografatura
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'PANTOGRAFATURA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('modellopantografatura.php','#tabella');\">Pantografatura</a></li>\r\n";
  }
  // Cerniere
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'CERNIERE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('cerniere.php','#tabella');\">Cerniere</a></li>\r\n";
  }
  // Serrature
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'SERRATURE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('serrature.php','#tabella');\">Serrature</a></li>\r\n";
  }
  // 08/04/2021 - tabelle nuove: MOdalitaConsegna (CLiente) ; ModalitaConsegnaOrdine, ResponsabileCOFAS
  // Modalita Consegna (Cliente)
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MODCONSEGNA' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('modalitaconsegna.php','#tabella');\">Modalita Consegna (cliente)</a></li>\r\n";
  }
  // MOdalita Consegna Ordine
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'MODCONSORD' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('modalitaconsegnaordine.php','#tabella');\">Modalita Consegna Ordine</a></li>\r\n";
  }
  // Responsabile COFAS Ordine
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'RESPCOFAS' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db,$qrystr);
  $row = mysqli_fetch_array($result,MYSQL_ASSOC);
  $count = $row['count'];
  if($count > 0){
      echo "	  <li><a href=\"javascript:loadPageToDiv('responsabilecofas.php','#tabella');\">Responsabile Cofas Ordine</a></li>\r\n";
  }
  // novità 06/02/2024
  // Agente COFAS Ordine
  $qrystr = "SELECT Stato AS count FROM accessrights a, login l WHERE a.NomeDiritto = 'AGENTE' AND a.IdLogin = l.IdLogin AND l.user = '" . $user . "' ";
  $result = mysqli_query($db, $qrystr);
  $row = mysqli_fetch_array($result, MYSQL_ASSOC);
  $count = $row['count'];
  if ($count > 0) {
      echo "	  <li><a href=\"javascript:loadPageToDiv('agente.php','#tabella');\">Agente COFAS</a></li>\r\n";
  }
  // fine del menù delle TABs
  echo "    </ul>\r\n";
  echo "  </div>\r\n";
  // sezione delle tabelle
  echo "  <div class=\"tabelleContent\" id=\"tabella\" width=\"800\" height=\"800\">\r\n";
  echo "     Selezionare la tabella che si vuole gestire.\r\n";
  echo "  </div>\r\n";
  echo " </body>\r\n";
  echo " </html>\r\n";
  mysqli_close($db);

?>