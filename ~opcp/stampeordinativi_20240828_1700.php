<?php
// 2024-08-20 - Robert Gasperoni - Stampe Ordinativi - unica stampa con tutte le bollette ordinativi del giorno indicato

include("dbconfig.php");



$modalita = "I"; // I = Interattiva, D= Download  (per farlo vedere in una finestra del browser è necessario che sia I )

$includiA4 = false; // 12/08/2024 - deve stampare in A4 invece che A5

$adesso = DateTime::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s")); // 10/02/2021 la data odierna completa di orario
$datario2 = $adesso->format("Ymd_His"); // data e ora correnti in formato ISO 8601
//$datario = date("d/m/Y H:i:s",$adesso); // data e ora in formato italiano
$datario = $adesso->format("d/m/Y H:i:s");

$oggi = date("Y-m-d");
$giorno = $oggi;
$flgGiorno = false;

if ($_REQUEST["giorno"]) {
    $giorno = $_REQUEST["giorno"];
    $flgGiorno = true;
}
$dataodierna = date_create($giorno);
$adesso = date_format($dataodierna, "d/m/Y");

$db = mysqli_connect($dbhost, $dbuser, $dbpassword); // or die("Connection Error: " . mysqli_error($db));
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}

mysqli_select_db($db, $database) or die("Error conecting to db.");
if (mysqli_connect_errno()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Failed to connect to MySQL: " . mysqli_connect_error(), true, 500);
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit; // fine dello script php
}
// aperto il database


// preparo il PDF
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/tcpdf_include.php'); // mi server ??? 2024-08-20

// require_once('tcpdf_barcodes_2d.php');
// le impostazioni per il formato della pagina da generare
// create new PDF document
// $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
// $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false); // 03/09/2021 doppio A5 su A4
if ($includiA4) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
} else { // A5 landscape
    $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
}
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Robert Gasperoni');
$pdf->SetTitle('Ordini dei clienti Cesari Pasticceria');
$pdf->SetSubject('Cesari Pasticceria');
$pdf->SetKeywords('Pasticceria,Cesari,San Giuliano,Rimini');
// remove default header/footer (quindi non mostra ne il contenuto ne la barra di separazione)
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
// set default header data (header di Asuni che si pu� togliere)
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 158', PDF_HEADER_STRING);
// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
// set auto page breaks
$pdf->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM); // TRUE, ...
// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
// font di base
$pdf->SetFont('helvetica', '', 10);

// echo "Passo 2";

$sql = "SELECT o.id AS idordine, c.sequenza as sequenza, C.id AS idcliente, c.Denominazione AS nomecliente, o.ticket AS numeroticket, YEAR(o.dataordine) AS annocomp ";
$sql .= "FROM cp_cliente c JOIN cp_ordinecliente o ON (o.cliente = c.id) WHERE ";
$sql .= "o.dataordine = DATE('" . $giorno . "') ORDER BY c.sequenza";

$result1 = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
if (!$result1) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}

// determino l'ordine di indice minimo e indice massimo
// i dati che fornisco al client sono: {"giorno" : "$giorno", "min" : "$min", "max" : "$max", "elenco" : [{sequenza, idordine, idcliente, annocomp, ticket}]}
$seq = 0;
$min = 300000;
$max = 0;
$elenco = "";
$risposta = "";

while ($row1 = mysqli_fetch_array($result1)) {
    $seq++;
    $sequenza = $row1["sequenza"];
    $idordine = $row1["idordine"];
    if ($idordine < $min) {
        $min = $idordine;
    }
    if ($idordine > $max) {
        $max = $idordine;
    }
    $idcliente = $row1["idcliente"];
    $nomecliente = $row1["nomecliente"];
    $ticket = $row1["numeroticket"];
    $annocomp = $row1["annocomp"];
    if ($elenco != "") {
        $elenco .= ",";
    }
    $elenco .= "{\"sequenza\" : \"$sequenza\", \"idordine\" : \"$idordine\", \"idcliente\" : \"$idcliente\", \"nomecliente\" : \"$nomecliente\", \"annocomp\": \"$annocomp\", \"ticket\" : \"$ticket\"}";

// qui aggiungiamo la stampa del ticket corrispondente a questo elemento ordine

    $pagina = 1;
    // add a page
    $pdf->AddPage();

    // *********************************************** *
// FOOTER                                          *
// *********************************************** *

    // QUI SCRIVIAMO L'ORARIO ATTUALE
    $pdf->SetFont('helvetica', '', 8); // grassetto, 12
    $pdf->Text(85, 190, 'Orario:' . $datario); // 200
// 05/08/2024
    $pdf->SetFont('helvetica', '', 7); // grassetto, 12
    $pdf->Text(120, 190, $annocomp . " / " . $ticket); // 200
// fine 05/08/2024
    $pdf->SetFont('helvetica', '', 8); // grassetto, 12
    $pdf->Text(135, 190, 'Pag.' . $pagina); // 200



    // * ************************************************************** * /
    // *  PRIMA PAGINA A5                                               * /
    // / * ************************************************************** * /

    // TESTI DI INTESTAZIONE

    $pdf->SetFont('helvetica', 'B', 11); // grassetto, 13
    $pdf->MultiCell(120, 0, 'CESARI PASTICCERIA', 0, 'C', 0, 1, 10, 4, false, 0, false, true, 10, 'T', true);
    $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

    // ---------------------- //

    // $pdf->Text(10, 19, 'Ordine del Cliente');
    $pdf->SetFont('helvetica', '', 8); // grassetto, 12
    $pdf->MultiCell(120, 0, 'Ordine del Cliente del ' . $dataimb, 0, 'C', 0, 1, 10, 6, false, 0, false, true, 10, 'T', true);
    $pdf->SetFont('helvetica', 'B', 18); // grassetto, 12 -- richiesto che venga evidenziato
    $pdf->MultiCell(120, 0, $nomecliente, 0, 'C', 0, 1, 10, 12, false, 0, false, true, 16, 'T', true);
    $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

    // ---------------------- //

    // QUI DISEGNIAMO LA TABELLA DEI PRODOTTI ORDINATI UNICA COLONNA
    $style2 = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));

    $ypos = 20;
    $xpos = 10;
    $x2pos = 30;
    $dimqta = 20;
    $dimnome = 100;
    $passo = 6;
    $limitegriglia = 150; //160 -  170; - 2024-08-13 ho dovuto alzare il limite griglia di 1 cm

    // set background color
    $pdf->SetFillColor(255, 255, 255);
    // set color for text
    $pdf->SetTextColor(0, 0, 0);

    // intestazione della tabella
    $pdf->SetFont('helvetica', 'B', 10); // grassetto, 12
    $miotesto = "Q.tà";
    $pdf->Text($xpos, $ypos + 4, $miotesto); // Q.tà
    $pdf->Text($x2pos, $ypos + 4, 'Prodotto');
    $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
    $pdf->Rect($x2pos, $ypos + 4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto

    // qui elenchiamo tutti gli operatori preposti all'imballaggio
    $sql = "SELECT d.dettaglioordine as sequenza, p.descrizionebreve as nomeprodotto, d.quantita as quantita ";
    $sql .= "FROM ";
    $sql .= "cp_dettaglioordine d LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
    $sql .= "WHERE d.ordinecliente = " . $idordine . " AND d.quantita > 0 ";
    $sql .= "ORDER BY d.dettaglioordine ";

    // eseguo il comando di query
    $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    $ypos += $passo;
    $pdf->SetFont('helvetica', '', 10); // grassetto, 12
    while ($row = mysqli_fetch_array($result)) {
        $seq = $row["sequenza"];
        $nomeprod = $row["nomeprodotto"];
        $qtaprod = $row["quantita"];
        $qtaprod = str_replace(".000", "", $qtaprod);

        // scriviamo Quantit� e Nome Prodotto
        $pdf->SetFont('helvetica', '', 10); // grassetto, 12
        $xpos1 = $xpos + 10 - $pdf->GetStringWidth($qtaprod);
        $xpos2 = $x2pos + 100 - $pdf->GetStringWidth($nomeprod);

        $pdf->Text($xpos1, $ypos + 4, $qtaprod);
        $pdf->Text($x2pos, $ypos + 4, $nomeprod);

        // disegniamo i rettangoli
        $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.t�
        $pdf->Rect($x2pos, $ypos + 4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
        $ypos += $passo;
        // se si supera il limite inferiore necessario per la griglia di riepilogo allora crea una nuova pagina con intestazione
        if ($ypos > $limitegriglia) {

            // *********************************************** *
            // FOOTER                                          *
            // *********************************************** *

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(10, 190, 'segue >'); // 200
            $pdf->SetFont('helvetica', '', 10);
            $pdf->AddPage();
            $pagina += 1;
            // QUI SCRIVIAMO L'ORARIO ATTUALE
            $pdf->SetFont('helvetica', '', 8); // grassetto, 12
            $pdf->Text(85, 190, 'Orario:' . $datario); // 200
            // 05/08/2024
            $pdf->SetFont('helvetica', '', 7); // grassetto, 12
            $pdf->Text(120, 190, $annocomp . " / " . $ticket); // 200
            // fine 05/08/2024
            $pdf->SetFont('helvetica', '', 8); // grassetto, 12
            $pdf->Text(135, 190, 'Pag.' . $pagina); // 200

            $pdf->Ln(); // lasciare un p� di spazio fra le righe
            // set cell padding
            $pdf->setCellPaddings(1, 1, 1, 1);
            // set cell margins
            $pdf->setCellMargins(1, 1, 1, 1);
            // set color for background
            $pdf->SetFillColor(255, 255, 255);
            // intestazione
            $pdf->SetFont('helvetica', 'B', 11); // grassetto, 13
            $pdf->MultiCell(120, 0, 'CESARI PASTICCERIA', 0, 'C', 0, 1, 10, 4, false, 0, false, true, 10, 'T', true);
            $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
            // $pdf->Text(10, 19, 'Ordine del Cliente');
            $pdf->SetFont('helvetica', '', 8); // grassetto, 12
            $pdf->MultiCell(120, 0, 'Ordine del Cliente', 0, 'C', 0, 1, 10, 6, false, 0, false, true, 10, 'T', true);
            $pdf->SetFont('helvetica', 'B', 18); // grassetto, 12 -- richiesto che venga evidenziato
            $pdf->MultiCell(120, 0, $nomecliente, 0, 'C', 0, 1, 10, 12, false, 0, false, true, 16, 'T', true);
            $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

            $ypos = 20;
            $xpos = 10;
            $x2pos = 30;
            $dimqta = 20;
            $dimnome = 100;
            $passo = 6;
            // intestazione della tabella
            $pdf->SetFont('helvetica', 'B', 10); // grassetto, 12
            $miotesto = "Q.t�";
            $pdf->Text($xpos, $ypos + 4, $miotesto); // Q.t�
            $pdf->Text($x2pos, $ypos + 4, 'Prodotto');
            $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.t�
            $pdf->Rect($x2pos, $ypos + 4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
            $ypos += $passo;
            $pdf->SetFont('helvetica', '', 10); // grassetto, 12
        }
    }
    mysqli_free_result($result);

    // Ora disegniamo la griglia del riepilogo quantitativi per gruppo prodotti

    $sql = "SELECT g.NomeGruppo as nomegruppo, SUM(d.quantita) as quantita ";
    $sql .= "FROM ";
    $sql .= "cp_dettaglioordine d LEFT OUTER JOIN cp_gruppoprodotti g ON (g.id = d.gruppo) ";
    $sql .= "WHERE d.ordinecliente = " . $idordine . " ";
    $sql .= "GROUP BY g.NomeGruppo ";
    $sql .= "ORDER BY g.NomeGruppo ";
    // eseguo il comando di query
    $result = mysqli_query($db, $sql) or die("Couldn t execute query." . mysqli_error($db));
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }

    $ypos = $limitegriglia + 6;
    $xpos = 10;
    $x2pos = 30;
    $dimqta = 20;
    $dimnome = 50;
    $passo = 6;

    // intestazione della tabella
    $pdf->SetFillColor(255, 255, 255);
    // set color for text
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont('helvetica', 'B', 8); // grassetto, 12
    $miotesto = "QUANTITÀ";
    $pdf->Text($xpos, $ypos - 2, $miotesto); // QUANTITÀ
    $pdf->Text($x2pos, $ypos - 2, 'GRUPPO PRODOTTI');
    $pdf->SetFont('helvetica', '', 8); // grassetto, 12

    $pdf->Rect($xpos, $ypos - 2, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
    $pdf->Rect($x2pos, $ypos - 2, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto

    while ($row = mysqli_fetch_array($result)) {

        $nomegruppo = $row["nomegruppo"];
        $qtaprod = $row["quantita"];
        $qtaprod = str_replace(".000", "", $qtaprod);
        // scriviamo Quantit� e Nome Prodotto
        $pdf->SetFont('helvetica', '', 8); // grassetto, 12
        $xpos1 = $xpos + 10 - $pdf->GetStringWidth($qtaprod);
        $xpos2 = $x2pos + 100 - $pdf->GetStringWidth($nomeprod);
        $pdf->Text($xpos1, $ypos + 4, $qtaprod);
        $pdf->Text($x2pos, $ypos + 4, $nomegruppo);
        // disegniamo i rettangoli
        $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.t�
        $pdf->Rect($x2pos, $ypos + 4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
        $ypos += $passo;
    }

    mysqli_free_result($result);


    // le pagine per ogni cliente devono sempre essere pari
    // se al momento � dispari allora si aggiunge una pagina in pi�
    if ($pagina % 2 != 0){
        $pdf->AddPage();
    }

    // fine stampa pagina dell'ordine

}

mysqli_free_result($result1);
mysqli_close($db);

// indichiamo che � l'ultima pagina
if ($pagina > 0){
    $pdf->lastPage();
}

// ora ritorniamo il file PDF
try{
   $pdf->Output('OrdiniCesariPasticceria_'. $giorno.'.pdf', $modalita); // interattivo

// $pdf->Output($cartellapdf . "EtichettaStaffa_" . $idcolata . "_" . $datario2 . ".pdf","F"); // salva il file nella cartella prevista per i PDF
} catch (Exception $e) {
    echo 'StampeOrdinativi.php Error Message: ' . $e->getMessage();
}

function CompattaNumero($valore){
    // qui dobbiamo togliere gli zero dopo la virgola decimale che non sono significativi (cio�, se � presente la virgola,
	// allora dal fondo si tolgono gli zeri finch� non ci sono pi� zeri o arriviamo alla virgola (in questo caso togliamo anche la virgola)
	if (strpos($valore,",")!= false)
    {
		$suffisso = substr($valore,strlen($valore)-3,3);
		$valore = substr($valore,0,strlen($valore)-3);
        $flg = true;
		while ($flg){
            $cifra = substr($valore,strlen($valore)-1,1);
			if ($cifra == "0") {
                $valore = substr($valore,0,strlen($valore)-1); // toglie l'ultima cifra
            }
			else
            { $flg = false; }
			if ($cifra == ","){
				$valore = substr($valore,0,strlen($valore)-1); // toglie la virgola
				$flg = false;
            }
        }
		$valore .= $suffisso;
    }
    return $valore;
}


?>