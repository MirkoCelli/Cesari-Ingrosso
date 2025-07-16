<?php
/*
 * 31/01/2021 - prove di Robert Gasperoni per verificare inserimento SVG, BarCode 1D e BarCode 2D con QR-CODE
 * dovranno essere poi usati per il progetto SCM Fonderia COLATA
 * http://192.168.1.227:8079/fondvilla/colata/tcpdf/esempioStampa.php
 */
$nomecliente = "*KURTZ EISENGUSS GMBH & CO.KG";
$denominazione = "MAGNETTRAGER DIS.RG594515";
$disegno = "RG594515";
$codice = "017321GS"; // codice identificativo elemento
$pesounit = "150 Kg";
$pesocompl = "450 Kg";

$speciale = "SPECIALE"; // è un flag da aggiungere per indicare lo stato (char 10 di testo libero)

$numerolotto = "L.123456ABC";
$qtalotto = "4";
$numpzlotto = "1";

$tipostaffasup = "800 x 600 x 350";
$tipostaffainf = "800 x 600 x 250";

$tempcolata = "1150 °C";
$tipoghisa = "GG25V";
$numerograppe = "4"; // 0 / 4 / 8
$contrappeso = "4"; // 0 / 1 / 2 / 3 ...

$note = "Questa nota serve per dare una indicazione più precisa del da farsi da parte dell'operatore incaricato. Può essere su più righe se serve";

// Include the main TCPDF library (search for installation path).
// require_once('tcpdf_include.php');

require_once('tcpdf.php');

// require_once('tcpdf_barcodes_2d.php');

// le impostazioni per il formato della pagina da generare
/*
define ('PDF_PAGE_ORIENTATION', 'L');
define ('PDF_UNIT', 'mm');
define ('PDF_PAGE_FORMAT', 'A5');
*/
// create new PDF document
$pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

// set document information

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Robert Gasperoni');
$pdf->SetTitle('Stampe per SCM Fonderia Colata');
$pdf->SetSubject('TCPDF Dimostrativo');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

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

// add a page
$pdf->AddPage();

// NOTE: Uncomment the following line to rasterize SVG image using the ImageMagick library.
//$pdf->setRasterizeVectorImages(true);

//--> $pdf->ImageSVG($file='examples/images/testsvg.svg', $x=15, $y=30, $w=50, $h=15, $link='http://www.tcpdf.org', $align='', $palign='', $border=1, $fitonpage=false);

//--> $pdf->ImageSVG($file='examples/images/tux.svg', $x=70, $y=30, $w='', $h=20, $link='', $align='', $palign='', $border=0, $fitonpage=false);

/* testo dei copyrights usati che si può escludere
$pdf->SetFont('helvetica', '', 8);
$pdf->SetY(195);
$txt = '© The copyright holder of the above Tux image is Larry Ewing, allows anyone to use it for any purpose, provided that the copyright holder is properly attributed. Redistribution, derivative work, commercial use, and all other use is permitted.';
$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);
*/

/* DISEGNO DEI RIQUADRI DELLA MASCHERA */
// da 0.2 a 0.5 per avere le linee più marcate
$style2 = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
// $pdf->Line(5, 10, 5, 30, $style2);

//$pdf->Line(40, 17, 205, 17, $style2); // sottolineatura titolo

// $pdf->Rect(145, 10, 40, 20, 'D', array('all' => $style2));

$pdf->Rect(10, 17, 190, 8, 'D', array('all' => $style2)); // cliente
$pdf->Rect(10, 25, 190, 8, 'D', array('all' => $style2)); // denominazione
//$pdf->Rect(40, 33, 160, 8, 'D', array('all' => $style2)); // disegno - da escludere

$pdf->Rect(10, 33, 190, 18, 'D', array('all' => $style2)); // riquadro Peso unitario + Lotto + Disegno
$pdf->Rect(10, 33, 40, 18, 'D', array('all' => $style2)); // riquadro solo Lotto da 68 a 10
$pdf->Rect(50, 33, 70, 18, 'D', array('all' => $style2)); // riquadro solo Peso unitario

$pdf->Rect(10, 51, 65, 8, 'D', array('all' => $style2)); // staffa sup.
$pdf->Rect(10, 59, 65, 8, 'D', array('all' => $style2)); // staffa inf.
$pdf->Rect(75, 51, 45, 16, 'D', array('all' => $style2)); // temperatura colata
$pdf->Rect(120, 51, 80, 16, 'D', array('all' => $style2)); // tipo ghisa

$pdf->Rect(10, 83, 80, 51, 'D', array('all' => $style2)); // riquadro Disegno + Codice Articolo
$pdf->Rect(10, 83, 80, 21, 'D', array('all' => $style2)); // riquadro Disegno solamente
$pdf->Rect(90, 83, 40, 51, 'D', array('all' => $style2)); // Num. Grappe
$pdf->Rect(130, 83, 40, 51, 'D', array('all' => $style2)); // Contrappeso
$pdf->Rect(170, 83, 30, 51, 'D', array('all' => $style2)); // GS Stella per ghisa specifica

$pdf->Rect(10, 67, 190, 16, 'D', array('all' => $style2)); // note
// ---------------------------------------------------------

// Logo della SCM Fonderie

$pdf->Image('logo_medium_new.jpg', 10, 8, 52, 8, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);

// public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array()) {

// impaginazione delle parti testuali - 01/02/2021 - SCM Fonderia - Sezione Colata
// ------------------------------------------------------------------ //

$pdf->SetFont('helvetica', 'B', 13); // grassetto, 13
$pdf->Text(90, 10, 'SCHEDA DI IDENTIFICAZIONE STAFFA');
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ---------------------- //

$pdf->Text(10, 19, 'Cliente:');
$pdf->SetFont('helvetica', 'B', 18); // grassetto, 12 -- richiesto che venga evidenziato
$pdf->Text(28, 17, $nomecliente); // 115
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ---------------------- //

$pdf->Text(10, 27, 'Denominazione:');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(45, 27, $denominazione);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ---------------------- //
/* // richiesta di eliminarlo in data 02/02/2021
$pdf->Text(40, 36, 'Disegno:');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(75, 36, $disegno);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11
*/
// ---------------------- //
/* richiesta di eliminarlo in data 02/02/2021
$pdf->Text(10, 48, 'Codice AS400:');
$pdf->SetFont('helvetica', '', 11); // normale, 11
$pdf->Text(40, 48, $codice);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11
*/
/* da non stampare
$pdf->Text(80, 44, 'Peso complessivo:');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(120, 44, $pesocompl);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11
*/

$pdf->Text(12, 36, 'N.Pz Lotto:'); // 70
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(42, 36, $numpzlotto); // 100
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

$pdf->Text(12, 44, 'Q.tà Lotto:'); // 70  
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(42, 44, $qtalotto); // 100
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11

$pdf->Text(52, 40, 'Peso:'); // 120
$pdf->SetFont('helvetica', 'B', 36); // grassetto, 12 -- deve essere evidenziato
$pdf->Text(65, 33, $pesounit); // 150
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// testo libero per la scritta SPECIALE
$pdf->SetFont('helvetica', 'B', 36); // grassetto, 12 -- deve essere evidenziato
$pdf->Text(122, 33, $speciale); // 150
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ---------------------- //
/* non va in stampa
$pdf->Text(20, 52, 'Num. Lotto:');
$pdf->SetFont('helvetica', '', 11); // normale, 11
$pdf->Text(50, 52, $numerolotto);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11
*/



// *********************** //

$pdf->Text(10, 52, 'Staffa sup.:');
$pdf->SetFont('helvetica', '', 11); // normale, 11
$pdf->Text(40, 52, $tipostaffasup);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11

$pdf->Text(75, 52, 'Temperatura:');
$pdf->SetFont('helvetica', 'B', 30); // normale, 11
$pdf->Text(78, 55, $tempcolata);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11

$pdf->Text(125, 52, 'Ghisa:');
$pdf->SetFont('helvetica', 'B', 36); // grassetto, 12 -- da evidenziare
$pdf->Text(142, 52, $tipoghisa); // 60
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// ----------------------- //
$pdf->Text(10, 60, 'Staffa inf:');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(40, 60, $tipostaffainf);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11

$pdf->Text(80, 60, '');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(120, 60, '');
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 11

$pdf->Text(150, 60, '');
$pdf->SetFont('helvetica', '', 11); // normale, 12
$pdf->Text(180, 60, '');
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

// *********************** //

$pdf->Ln(); // lasciare un pò di spazio fra le righe
// set cell padding
$pdf->setCellPaddings(1, 1, 1, 1);
// set cell margins
$pdf->setCellMargins(1, 1, 1, 1);
// set color for background
$pdf->SetFillColor(255, 255, 255);

// Fit text on cell by reducing font size
$pdf->MultiCell(0, 0, "Note:\n".$note, 0, 'L', 0, 1, 10, 66, false, 0, false, true, 16, 'T', true);


$pdf->Text(10, 84, 'Disegno:');
$pdf->SetFont('helvetica', 'B', 29); // grassetto, 16 -- da evidenziare
$pdf->Text(11, 89, $disegno);
$pdf->SetFont('helvetica', 'B', 11); // grassetto, 12

$pdf->Text(10, 103, 'Codice:');

// QRCODE,Q : QR-CODE Better error correction
// set style for barcode
/*
// provo a scrivere il QR-CODE nella pagina - viene generata una immagine SVG che va includa nel documento PDF
$style = array(
	'border' => 0,  // 2 mette il bordo, 0 non mette il bordo
	'vpadding' => 'auto',
	'hpadding' => 'auto',
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255)
	'module_width' => 1, // width of a single module in points
	'module_height' => 1 // height of a single module in points
);
$pdf->write2DBarcode($codice, 'QRCODE,Q', 120, 70, 50, 50, $style, 'N');
$pdf->Text(120, 65, 'QRCODE Q per '.$codice);

*/

// CODE_39 ANSI per il BarCode 1D

// define barcode style
$style = array(
	'position' => '',
	'align' => 'C',
	'stretch' => false,
	'fitwidth' => true,
	'cellfitalign' => '',
	'border' => false, // true mette il bordo, false non mette il bordo
	'hpadding' => 'auto',
	'vpadding' => 'auto',
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255),
	'text' => false, // true = scrive il testo, false= non scrive il testo
	'font' => 'helvetica',
	'fontsize' => 10, // 8
	'stretchtext' => 1 // 4
);

// PRINT VARIOUS 1D BARCODES

// CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
$pdf->Cell(0, 0, '', 0, 1);
$pdf->write1DBarcode($codice, 'C39', '', '', '', 15, 0.4, $style, 'N'); // 20, 0.4,...
$pdf->Ln();
// il testo lo scrivo io nella posizione che desidero 
$pdf->SetFont('helvetica', 'B', 24); // grassetto, 12
$pdf->Text(30, 121, $codice);
/*
$pdf->SetFont('helvetica', 'B', 26); // grassetto, 16
$pdf->Text(90, 88, $codice);
$pdf->SetFont('helvetica', 'B', 12); // grassetto, 12
*/

// -- disegni per Numero Grappe e Contropeso -- //
$pdf->SetFont('helvetica', 'B', 12); // grassetto, 12
$pdf->Text(95, 84, 'num.grappe');
switch ($numerograppe) {
	case 0:	   
       $pdf->SetFont('helvetica', '', 11); // normale, 12
       $pdf->Text(95, 114, '');
       $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	   break;
	case 4:
       // $pdf->SetFont('helvetica', '', 11); // normale, 12
       // $pdf->Text(95, 114, $numerograppe);
       $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	   // $pdf->ImageSVG($file='grappe4.svg', $x=95, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	   $pdf->Image('grappe4.jpg', 95, 93, 30, 20, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
	   break;
	case 8:
       // $pdf->SetFont('helvetica', '', 11); // normale, 12
       // $pdf->Text(95, 114, $numerograppe);
       $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	   // $pdf->ImageSVG($file='grappe8.svg', $x=95, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	   $pdf->Image('grappe8.jpg', 95, 93, 30, 20, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
	   break;
	default:
	   $pdf->SetFont('helvetica', '', 11); // normale, 12
       $pdf->Text(95, 114, $numerograppe);
       $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	   // $pdf->ImageSVG($file='grappe.svg', $x=95, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	   // $pdf->Image('grappe4.jpg', 95, 93, 30, 20, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
       // no break	
}
$pdf->SetFont('helvetica', 'B', 12); // grassetto, 12
$pdf->Text(135, 84, 'contrappeso');
switch ($contrappeso){
	case 0:	  
      $pdf->SetFont('helvetica', '', 26); // normale, 12
      $pdf->Text(160, 90, '');
      $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	  break;
	case 1:
      // $pdf->SetFont('helvetica', '', 11); // normale, 12
      // $pdf->Text(135, 114, $contrappeso);
      $pdf->SetFont('helvetica', 'B', 26); // grassetto, 12
	  // $pdf->ImageSVG($file='contrapeso.svg', $x=135, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	  $pdf->Image('contrapeso.jpg', 135, 93, 30, 30, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
	  $pdf->Text(160, 90, $contrappeso);
      $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	  break;
	case 2:
      // $pdf->SetFont('helvetica', '', 11); // normale, 12
      // $pdf->Text(135, 114, $contrappeso);
      $pdf->SetFont('helvetica', 'B', 26); // grassetto, 12
	  // $pdf->ImageSVG($file='contrapeso.svg', $x=135, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	  $pdf->Image('contrapeso.jpg', 135, 93, 30, 30, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
	  $pdf->Text(160, 90, $contrappeso);
      $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	  break;
	default:	  
      $pdf->SetFont('helvetica', 'B', 26); // grassetto, 12
	  // $pdf->ImageSVG($file='contrapeso.svg', $x=135, $y=93, $w=30, $h=30, $link='', $align='', $palign='', $border=0, $fitonpage=false);
	  $pdf->Image('contrapeso.jpg', 135, 93, 30, 30, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
	  $pdf->Text(160, 90, $contrappeso);
      $pdf->SetFont('helvetica', 'B', 11); // grassetto, 12
	  // no break
}

// devo disegnare una stella se il codice $codice contiene GS

if (substr($codice,6,2) == "GS"){
   // disegno di una stella in alto a sinistra sotto al logo SCM Fonderie
   // $pdf->Text(176, 84, 'STELLA');
   // $pdf->ImageSVG($file='stella.svg', $x=175, $y=93, $w=20, $h=20, $link='', $align='', $palign='', $border=0, $fitonpage=false);  
   $pdf->Image('stella.jpg', 175, 93, 20, 20, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);   
}

// ---------------------------------------------------------------------- //


//Close and output PDF document
$pdf->Output('esempioQRCode.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+
