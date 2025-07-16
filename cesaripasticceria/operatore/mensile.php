<?php
// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Pagina che gestisce il prospetto mensile dell'ordinativo del cliente

include "include/parametri.inc";
require __DIR__ . "/funzioni.php";
sistemareSegreti();
// funzionalità di uno locale

// la modalità REST viene esclusa si usano i GET

function build_calendar($month, $year, $dateArray, $server_path)
{

    // Create array containing abbreviations of days of week.
    // $daysOfWeek = array('S', 'M', 'T', 'W', 'T', 'F', 'S'); // versione inglese
    $daysOfWeek = array('Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'); // versione italiana (1,2,3,4,5,6,0)

    // What is the first day of the month in question?
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);

    // How many days does this month contain?
    $numberDays = date('t', $firstDayOfMonth);

    // Retrieve some information about the first day of the
    // month in question.
    $dateComponents = getdate($firstDayOfMonth);

    // What is the name of the month in question?
    // $monthName = $dateComponents['month'];

    $monthName = NomeDelMese($month);

    // What is the index value (0-6) of the first day of the
    // month in question.
    $dayOfWeek = (7+$dateComponents['wday']-1) % 7; // conversione da inglese a italiana

    $weekNumber = (int)date("W", $firstDayOfMonth);


    // echo $weekNumber;

    // Create the table tag opener and day headers

    $calendar = "<table class='calendar'>";
    $calendar .= "<caption class='nome_mese'>$monthName $year</caption>";
    $calendar .= "<tr><th class='header'>N.Week</th>";

    // Create the calendar headers

    foreach ($daysOfWeek as $day) {
        $calendar .= "<th class='header'>$day</th>";
    }

    // Create the rest of the calendar

    // Initiate the day counter, starting with the 1st.

    $currentDay = 1;

    $month1 = $month;
    while (strlen($month1)< 2) {
        $month1 = "0" . $month1;
    }
    $calendar .= "</tr><tr><td class='week'><a href='" . $server_path . "settimanale.php?weeknum=" . $weekNumber . "&anno=" . $year . "&giornata=$year-$month1-01' class=''>" . $weekNumber . "</a></td>";

    // The variable $dayOfWeek is used to
    // ensure that the calendar
    // display consists of exactly 7 columns.

    if ($dayOfWeek > 0) {
        $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>";
    }

    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    $year1 = $year;
    while ($currentDay <= $numberDays) {

        // Seventh column (Saturday) reached. Start a new row.

        if ($dayOfWeek == 7) {

            $dayOfWeek = 0;
            $weekNumber++;
            if ($weekNumber >= 53) {
                // la numerazione termina a 52 quindi diventa 1
                $weekNumber = 1;
                $year1++;
            }
            $currentDayRel1 = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
            $date1 = "$year-$month-$currentDayRel1";
            $calendar .= "</tr><tr><td class='week'><a href='" . $server_path . "settimanale.php?weeknum=" . $weekNumber . "&anno=" . $year1 . "&giornata=" . $date1 . "' class=''>" . $weekNumber."</a></td>";
        }

        $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);

        $date = "$year-$month-$currentDayRel";

        $calendar .= "<td class='day' rel='$date'><a href='" . $server_path . "giornaliero.php?giorno=" . $date ."' class=''>" . $currentDay . "</a></td>";

        // Increment counters

        $currentDay++;
        $dayOfWeek++;

    }



    // Complete the row of the last week in month, if necessary

    if ($dayOfWeek != 7) {

        $remainingDays = 7 - $dayOfWeek;
        $calendar .= "<td colspan='$remainingDays'>&nbsp;</td>";

    }

    $calendar .= "</tr>";

    $calendar .= "</table>";

    return $calendar;

}

function NomeDelMese($nummese)
{
    $risp = "";
    switch ($nummese) {
        case 1:
            $risp = "GENNAIO";
            break;
        case 2:
            $risp = "FEBBRAIO";
            break;
        case 3:
            $risp = "MARZO";
            break;
        case 4:
            $risp = "APRILE";
            break;
        case 5:
            $risp = "MAGGIO";
            break;
        case 6:
            $risp = "GIUGNO";
            break;
        case 7:
            $risp = "LUGLIO";
            break;
        case 8:
            $risp = "AGOSTO";
            break;
        case 9:
            $risp = "SETTEMBRE";
            break;
        case 10:
            $risp = "OTTOBRE";
            break;
        case 11:
            $risp = "NOVEMBRE";
            break;
        case 12:
            $risp = "DICEMBRE";
            break;
    }
    return $risp;
}

function IndiceMese($nome){
    $risp = 0;
    if ($nome == "GENNAIO") {
        $risp = 1;
    }
    if ($nome == "FEBBRAIO") {
        $risp = 2;
    }
    if ($nome == "MARZO") {
        $risp = 3;
    }
    if ($nome == "APRILE") {
        $risp = 4;
    }
    if ($nome == "MAGGIO") {
        $risp = 5;
    }
    if ($nome == "GIUGNO") {
        $risp = 6;
    }
    if ($nome == "LUGLIO") {
        $risp = 7;
    }
    if ($nome == "AGOSTO") {
        $risp = 8;
    }
    if ($nome == "SETTEMBRE") {
        $risp = 9;
    }
    if ($nome == "OTTOBRE") {
        $risp = 10;
    }
    if ($nome == "NOVEMBRE") {
        $risp = 11;
    }
    if ($nome == "DICEMBRE") {
        $risp = 12;
    }
    return $risp;
}

// qui voglio vedere se riesco a gestire un path tipo REST http://xxx/script.php/Domain/Function/Data (ha il problema che l'impaginazione non � quella di base, perch� il path dove cerca css e js � quello intero e non quello ridotto)
$percorso = $_SERVER['REQUEST_URI'];

$elementi = explode('/', $percorso); // separo le parti in base a / (0= niente, 1 = nome script, 2-xx il path REST

$mese = "";
$pathbase = $elementi[1];

$serverpath = "http://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . "/cesaripasticceria/";
if ($elementi[0] != "") {
    $serverpath .= $elementi[0] . "/";
}

$identita = leggitoken(); // tre valori (token,user,scadenza)

$token = $identita[0];
$utente = $identita[1];
$scade = $identita[2];
$indice = $identita[3];
$adesso = date("Y-m-d H:i:s");

if (!VerificaToken($token, $indice, $adesso)) {
    // deve rieffettuare il login se il token non corrisponde
    redirect($serverpath . "login.php");
}

$anno = date('Y', strtotime(date("Y-m-d")));
$nummese = (int)date('m', strtotime(date("Y-m-d")));
$nomemese = "";

/*
if (count($elementi) > 2) {
    $mese = strtoupper($elementi[2]);
    $nummese = IndiceMese($mese);
    $anno = $elementi[3];
    $nomemese = $mese;
}
*/

// se è chiamato da annuale si usa il GET

if (isset($_GET["mese"])) {
    // il mese è indicato numericamente
    $nummese = $_GET["mese"];
    $nomemese = NomeDelMese($nummese);
    $mese = $nomemese;
}

if (isset($_GET["anno"])) {
    $anno = $_GET["anno"];
}

// se è stato chiamato da POST allora leggo i dati

if (isset($_POST["mese"])){
  // il mese è indicato numericamente
    $nummese = $_POST["mese"];
    $nomemese = NomeDelMese($nummese);
    $mese = $nomemese;
}

if (isset($_POST["annocorrente"])){
    $anno = $_POST["annocorrente"];
}

// il mese lo rappresento con un calendario dei giorni distribuiti per settimane sulle righe e i giorni della settimana sulle colonne
// preso un esempio da https://css-tricks.com/snippets/php/build-a-calendar-table/

$dateComponents = getdate();

$month = $dateComponents['mon'];
$year = $dateComponents['year'];

// echo build_calendar($month, $year, $dateArray);

?>
<html>
<head>
    <link rel="stylesheet" href="<?=$serverpath?>css/mensile.css" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="<?= $serverpath ?>js/mensile.js"></script>

    <script src="<?= $serverpath ?>js/jquery-3.7.1.min.js"></script>


</head>
<body>
    <!--Main Page-->
    <div name="intestazione" class="Intestazioni">
        <form method="post" action="mensile.php">
            <span id="mese">
                <select name="mese" class="elencomesi">
<?php
        for ($i = 1; $i <= 12; $i++){
            if ($i == $nummese){
?>
                    <option value="<?=$i?>" selected><?=NomeDelMese($i)?></option>

<?php
            } else {
?>
                   <option value="<?= $i ?>"><?=NomeDelMese($i)?></option>

<?php                    
            }
        }
?>
                </select>
            </span>
            <span id="anno">
                <input name="annocorrente" type="number" min="2020" max="2099" value="<?= $anno ?>" required class="spin" />
                <input type="submit" name="invia" value="Cambia Mese/Anno" class="bottone"/>

            </span>
        </form>
    </div>
    <div name="contenuto" class="Contenuti">
        <center>
    <?php
   $dateArray = array();
   echo build_calendar($nummese, $anno, $dateArray, $serverpath);
    ?>
        </center>
    </div>
    <div name="menubasso" class="MenuComandi">
        <a href="<?=$serverpath?>mainpage.php">Back</a>
    </div>
</body>
</html>
