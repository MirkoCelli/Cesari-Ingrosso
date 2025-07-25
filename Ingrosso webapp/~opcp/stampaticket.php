<?php
// 29/07/2024 - Stampa Ticket in formato A5
try {
include("dbconfig.php");


$cartellapdf = "D:/wamp/www/fondvilla/html/resine/PDF/"; // deve essere personalizzata al wamp corrente - 29/07/2024
/*
 * 31/01/2021 - prove di Robert Gasperoni per verificare inserimento SVG, BarCode 1D e BarCode 2D con QR-CODE
 * dovranno essere poi usati per il progetto SCM Fonderia COLATA
 * http://192.168.1.227:8079/fondvilla/colata/tcpdf/esempioStampa.php
 */
$modalita = "I"; // I = Interattiva, D= Download  (per farlo vedere in una finestra del browser è necessario che sia I )

$includiA4 = false; // 12/08/2024 - deve stampare in A4 invece che A5

$adesso = DateTime::createFromFormat("Y-m-d H:i:s",date("Y-m-d H:i:s")); // 10/02/2021 la data odierna completa di orario
$datario2 = $adesso->format("Ymd_His"); // data e ora correnti in formato ISO 8601
//$datario = date("d/m/Y H:i:s",$adesso); // data e ora in formato italiano
$datario = $adesso->format("d/m/Y H:i:s");

// lettura dell'unico record con id = idcolata

// Eseguire la query sul database
$idordine = null;
$ticket = null;
$annocomp = null;

if (isset($_GET["idordine"])) {
    $idordine = $_GET["idordine"];
}

if (isset($_GET["ticket"]))
{
    $ticket = $_GET["ticket"];
}

if (isset($_GET["annocomp"])) {
    $annocomp = $_GET["annocomp"];
}

// eseguo la ricerca dei dati per la stampa A5

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

// dati per trovare l'ordine associato al ticket
if ($idordine == null){
 // cerca il idordine in base a numero di ticket/annocomp
 $sql = "SELECT o.id AS id, o.ticket as ticket, o.preparatore as preparatore, r.NomeBreve as nomeresponsabile, c.Denominazione as nomecliente, o.dataordine as dataordine ";
 $sql .= ", (p.perc_b % 123456)/1000 as perc_b, (p.perc_n % 123456)/1000 as perc_n "; // 2024-09-05
 $sql .= "FROM ";
 $sql .= "cp_ordinecliente o LEFT OUTER JOIN cp_responsabile r ON (r.id = o.preparatore) LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
 $sql .= "LEFT OUTER JOIN cp_rapportoconsegna p ON (p.cliente = c.id) "; // 2024-09-05
 $sql .= "WHERE o.ticket = " . $ticket . " AND YEAR(o.dataordine) = " . $annocomp;

 // eseguo il comando di query
 $result = mysqli_query($db, $sql);
 if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
 }
 if ($row = mysqli_fetch_array($result)) {
    $idordine = $row["id"];
    $nomecliente = $row["nomecliente"];
    $nomeprep = $row["nomeresponsabile"];
    $idticket = $row["ticket"];
    $dataordine = $row["dataordine"];
    // 2024-09-05 - ci serve per calcolare i totali
    $perc_b = $row["perc_b"];
    $perc_n = $row["perc_n"];
    // fine 2024-09-05
    $ticket = $idticket;
    $annocomp = date('Y', strtotime($dataordine));
 }
 mysqli_free_result($result);
 // 2024-09-05 - viene richiesto di stampare anche i totali totale_b e totale_n
 if (isset($idordine)){
    $totali = CalcolaTotaliRapportati($idordine, $perc_b, $perc_n);
 }
} else {
    // cerca il cliente dato idordine
    $sql = "SELECT o.id AS id, o.ticket as ticket, o.preparatore as preparatore, r.NomeBreve as nomeresponsabile, c.Denominazione as nomecliente, o.dataordine as dataordine ";
    $sql .= ", (p.perc_b % 123456)/1000 as perc_b, (p.perc_n % 123456)/1000 as perc_n "; // 2024-09-05
    $sql .= "FROM ";
    $sql .= "cp_ordinecliente o LEFT OUTER JOIN cp_responsabile r ON (r.id = o.preparatore) LEFT OUTER JOIN cp_cliente c ON (c.id = o.cliente) ";
    $sql .= "LEFT OUTER JOIN cp_rapportoconsegna p ON (p.cliente = c.id) "; // 2024-09-05
    $sql .= "WHERE o.id = " . $idordine;

    // eseguo il comando di query
    $result = mysqli_query($db, $sql);
    if (!$result) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
        echo ("Error description: " . mysqli_error($db));
        exit; // fine dello script php
    }
    if ($row = mysqli_fetch_array($result)) {
        $idordine = $row["id"];
        $nomecliente = $row["nomecliente"];
        $nomeprep = $row["nomeresponsabile"];
        $idticket = $row["ticket"];
        $dataordine = $row["dataordine"];
        // 2024-09-05 - ci serve per calcolare i totali
        $perc_b = $row["perc_b"];
        $perc_n = $row["perc_n"];
        // fine 2024-09-05
        $ticket = $idticket;
        $annocomp = date('Y', strtotime($dataordine));
    }
    mysqli_free_result($result);
    // 2024-09-05 - viene richiesto di stampare anche i totali totale_b e totale_n
    $totali = CalcolaTotaliRapportati($idordine, $perc_b, $perc_n);
}
// fine ricerca ordine per ticket
// 05/08/2024 - formattare dataordine in dd/mm/yyyy
$dt = new DateTime($dataordine);
$dataimb = $dt->format("d/m/Y");
// conteggio del numero di prodotti relativi all'ordine con quantità positiva
$sql = "SELECT COUNT(*) as conteggio ";
$sql .= "FROM ";
$sql .= "cp_dettaglioordine d LEFT OUTER JOIN cp_prodotto p ON (p.id = d.prodotto) ";
$sql .= "WHERE d.ordinecliente = " . $idordine . " AND d.quantita > 0 ";

$result = mysqli_query($db, $sql);
if (!$result) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error :: ' . "Query MySQL Error (10-2): " . mysqli_error($db), true, 500);
    echo ("Error description: " . mysqli_error($db));
    exit; // fine dello script php
}
if ($row = mysqli_fetch_array($result)) {
    $conta = $row["conteggio"];
}
mysqli_free_result($result);

// selezione dei dati corrispondenti all'ordine del cliente associato al ticket

/* QUI PARTE LA STAMPA A5 dell'ordine per il cliente (individuato per idordine oppure per annocomp e ticket */

require_once('tcpdf/tcpdf.php');

// require_once('tcpdf_barcodes_2d.php');

// le impostazioni per il formato della pagina da generare
/*
define ('PDF_PAGE_ORIENTATION', 'L');
define ('PDF_UNIT', 'mm');
define ('PDF_PAGE_FORMAT', 'A5');
*/
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
$pdf->SetTitle('Ordine cliente Cesari Pasticceria');
$pdf->SetSubject('Cesari Pasticceria');
$pdf->SetKeywords('Pasticceria,Cesari,San Giuliano,Rimini');

// remove default header/footer (quindi non mostra ne il contenuto ne la barra di separazione)
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default header data (header di Asuni che si può togliere)

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 158', PDF_HEADER_STRING);

// set header and footer fonts

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

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
/*
// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}
*/
// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);

$pagina = 1;
// add a page
$pdf->AddPage();

// *********************************************** *
// FOOTER                                          *
// *********************************************** *

// QUI SCRIVIAMO L'ORARIO ATTUALE
/*
$pdf->SetFont('helvetica', '', 8); // grassetto, 12
$pdf->Text(85, 190, 'Data:' . $dataimb); // 200
*/
// 05/08/2024
$pdf->SetFont('helvetica', '', 7); // grassetto, 12
$pdf->Text(120, 190, $annocomp . " / " . $ticket); // 200
// fine 05/08/2024
$pdf->SetFont('helvetica', '', 8); // grassetto, 12
$pdf->Text(135, 190, 'Pag.' . $pagina); // 200


/* ************************************************************** */
/*  PRIMA PAGINA A5 SU A4                                         */
/* ************************************************************** */

// TESTI DI INTESTAZIONE

$pdf->SetFont('helvetica', 'B', 11); // grassetto, 13
$pdf->MultiCell(120, 0, 'CESARI PASTICCERIA', 0, 'C', 0, 1, 10, 4, false, 0, false, true, 10, 'T', true);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ---------------------- //

// $pdf->Text(10, 19, 'Ordine del Cliente');
$pdf->SetFont('helvetica', '', 8); // grassetto, 12
$pdf->MultiCell(120, 0, 'Ordine del Cliente del ' . $dataimb , 0, 'C', 0, 1, 10, 6, false, 0, false, true, 10, 'T', true);
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
/*
$pdf->MultiCell($dimqta, 0, 'Q.tà', 0, 'C', 0, 1, $xpos, $ypos-1, false, 0, false, true, 16, 'T', true);
$pdf->MultiCell($dimnome, 0, 'Prodotto', 0, 'C', 0, 1, $x2pos, $ypos-1, false, 0, false, true, 16, 'T', true);
*/
$pdf->Text($xpos, $ypos + 4, 'Q.tà');
$pdf->Text($x2pos, $ypos + 4, 'Prodotto');
$pdf->Rect($xpos, $ypos+4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
$pdf->Rect($x2pos, $ypos+4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto

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

    // scriviamo Quantità e Nome Prodotto
    $pdf->SetFont('helvetica', '', 10); // grassetto, 12
    /*
    $pdf->MultiCell($dimqta, 0, $qtaprod, 0, 'R', 0, 1, $xpos, $ypos-1, false, 0, false, true, 16, 'T', true);
    $pdf->MultiCell($dimnome, 0, $nomeprod, 0, 'L', 0, 1, $x2pos, $ypos-1, false, 0, false, true, 16, 'T', true);
    */
    $xpos1 = $xpos + 10 - $pdf->GetStringWidth($qtaprod);
    $xpos2 = $x2pos + 100 - $pdf->GetStringWidth($nomeprod);

    $pdf->Text($xpos1, $ypos + 4, $qtaprod);
    $pdf->Text($x2pos, $ypos + 4, $nomeprod);

    // disegniamo i rettangoli
    $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
    $pdf->Rect($x2pos, $ypos + 4 , $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
    $ypos += $passo;
    // se si supera il limite inferiore necessario per la griglia di riepilogo allora crea una nuova pagina con intestazione
    if ($ypos > $limitegriglia){

        // *********************************************** *
        // FOOTER                                          *
        // *********************************************** *

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Text(10, 190, 'segue >'); // 200
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pagina += 1;
        // QUI SCRIVIAMO L'ORARIO ATTUALE
        /*
        $pdf->SetFont('helvetica', '', 8); // grassetto, 12
        $pdf->Text(85, 190, 'Orario:' . $datario); // 200
        */
        // 05/08/2024
        $pdf->SetFont('helvetica', '', 7); // grassetto, 12
        $pdf->Text(120, 190, $annocomp . " / " . $ticket); // 200
        // fine 05/08/2024
        $pdf->SetFont('helvetica', '', 8); // grassetto, 12
        $pdf->Text(135, 190, 'Pag.' . $pagina); // 200

        $pdf->Ln(); // lasciare un pò di spazio fra le righe
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

        $pdf->Text($xpos, $ypos + 4, 'Q.tà');
        $pdf->Text($x2pos, $ypos + 4, 'Prodotto');
        /*
        $pdf->MultiCell($dimqta, 0, 'Q.tà', 0, 'C', 0, 1, $xpos, $ypos - 1, false, 0, false, true, 16, 'T', true);
        $pdf->MultiCell($dimnome, 0, 'Prodotto', 0, 'C', 0, 1, $x2pos, $ypos - 1, false, 0, false, true, 16, 'T', true);
        */
        $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
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

// $style2 = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
// $pdf->Ln(); // lasciare un pò di spazio fra le righe

$ypos = $limitegriglia + 6;
$xpos = 10;
$x2pos = 30;
$dimqta = 20;
$dimnome = 50;
$passo = 6;

// intestazione della tabella
// $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
// set background color
$pdf->SetFillColor(255, 255, 255);
// set color for text
$pdf->SetTextColor(0, 0, 0);
/*
$pdf->MultiCell($dimqta, 0, 'Q.tà', 0, 'R', 0, 1, $xpos, $ypos - 1, true, 0, false, true, 16, 'T', true);
$pdf->MultiCell($dimnome, 0, 'Gruppo Prodotti', 0, 'C', 0, 1, $x2pos, $ypos - 1, true, 0, false, true, 16, 'T', true);
$pdf->SetFont('helvetica', '', 8); // grassetto, 12
*/
$pdf->SetFont('helvetica', 'B', 8); // grassetto, 12
$pdf->Text($xpos, $ypos - 2, 'QUANTITÀ');
$pdf->Text($x2pos, $ypos - 2, 'GRUPPO PRODOTTI');
$pdf->SetFont('helvetica', '', 8); // grassetto, 12

// 2024-09-05 - devo stampare anche l'importo Totale N vicino al riquadro di riepilogo prodotti
if ($perc_b < 100){ // solo se esiste rapporto 50:50 o diversi da 100:0
        $pdf->SetFont('helvetica', 'B', 8); // grassetto, 12
        $miotesto = "TOTALE N  " . number_format($totali["totale_n"], 2, ",");
        $pdf->Text($x2pos + $dimnome + 5, $ypos - 2, $miotesto); // TOTALE N
}

$pdf->Rect($xpos, $ypos-2 , $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
$pdf->Rect($x2pos, $ypos-2 , $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
// $pdf->SetFont('helvetica', '', 11); // grassetto, 12
// $ypos += $passo;
while ($row = mysqli_fetch_array($result)) {

    $nomegruppo = $row["nomegruppo"];
    $qtaprod = $row["quantita"];
    $qtaprod = str_replace(".000", "", $qtaprod);
    // scriviamo Quantità e Nome Prodotto
    $pdf->SetFont('helvetica', '', 8); // grassetto, 12
    $xpos1 = $xpos + 10 - $pdf->GetStringWidth($qtaprod);
    $xpos2 = $x2pos + 100 - $pdf->GetStringWidth($nomeprod);
    $pdf->Text($xpos1, $ypos + 4, $qtaprod);
    $pdf->Text($x2pos, $ypos + 4, $nomegruppo);
    /*
    $pdf->MultiCell($dimqta, 0, $qtaprod, 0, 'R', 0, 1, $xpos, $ypos+4, true, 0, false, true, 16, 'T', true);
    $pdf->MultiCell($dimnome, 0, $nomegruppo, 0, 'L', 0, 1, $x2pos, $ypos+4, true, 0, false, true, 16, 'T', true);
    */
    // disegniamo i rettangoli
    $pdf->Rect($xpos, $ypos + 4, $dimqta, $passo, 'D', array('all' => $style2)); // Q.tà
    $pdf->Rect($x2pos, $ypos + 4, $dimnome, $passo, 'D', array('all' => $style2)); // Prodotto
    $ypos += $passo;
}

mysqli_free_result($result);
mysqli_close($db);

$pdf->lastPage();

/**** FINE STAMPA DOPPIA A5 SU A4 ***********/


//Close and output PDF document
// $pdf->Output('esempioQRCode.pdf', 'D'); // download diretto
$pdf->Output('OrdineCesariPasticceria_'. $idordine.'.pdf', $modalita); // interattivo

// $pdf->Output($cartellapdf . "EtichettaStaffa_" . $idcolata . "_" . $datario2 . ".pdf","F"); // salva il file nella cartella prevista per i PDF
} catch (Exception $e) {
    echo 'StampaTciket.php Error Message: ' . $e->getMessage();
}

function CompattaNumero($valore){
    // qui dobbiamo togliere gli zero dopo la virgola decimale che non sono significativi (cioè, se è presente la virgola,
	// allora dal fondo si tolgono gli zeri finchè non ci sono più zeri o arriviamo alla virgola (in questo caso togliamo anche la virgola)
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

// 2024-09-05 - Calcola per l'ordine corrente dato da $id il totale_b e totale_n secondo le operazioni date in riepilogoordine.php

function CalcolaTotaliRapportati($id, $perc_b, $perc_n)
{
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
            $qtab[$key] += ceil($quantitagruppi[$key] * ($perc_b / 100));
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += floor($quantitagruppi[$key] * ($perc_n / 100));
            $totn[$key] += $qtan[$key] * $prezzogruppi[$key];
        }
        if ($unmisgruppi[$key] == "KG") {
            $qtab[$key] += ($quantitagruppi[$key] * ($perc_b / 100));
            $totb[$key] += $qtab[$key] * $prezzogruppi[$key];
            $qtan[$key] += ($quantitagruppi[$key] * ($perc_n / 100));
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

//============================================================+
// END OF FILE
//============================================================+
