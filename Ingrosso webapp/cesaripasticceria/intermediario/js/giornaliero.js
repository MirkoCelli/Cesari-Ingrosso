// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Script per gestire gli elementi in giornaliero.php

var abilitaForm = false;

function apriInserimento(indice) {
    var x = document.getElementById("qta_" + indice);
    x.style.display = "none";
    var y = document.getElementById("inqta_" + indice);
    y.style.display = "block";
}

function chiudiInserimento(indice) {
    var x = document.getElementById("qta_" + indice);
    x.style.display = "block";
    var y = document.getElementById("inqta_" + indice);
    y.style.display = "none";
}

function IncrementaA(indice) {
    var num = Number($("#quantita_" + indice).val());
    if (num < 10000) {
        num += 1;
    }    
    $("#quantita_" + indice).val(num);
}

function DecrementaA(indice) {
    var num = Number($("#quantita_" + indice).val());
    if (num > 0) {
        num -= 1;
    }    
    $("#quantita_" + indice).val(num);
}

function SalvaQtaProdottoA(indice) {
    // evitare che inseriscano un valore non numerico
    var testo = $("#quantita_" + indice).val();
    if (testo == "") { testo = "0"; $("#quantita_" + indice).val("0");} // 2028-08-27
    var n = parseInt(testo);
    if (n < 0) { n = 0; $("#quantita_" + indice).val("0");} // 2024-08-27
    // alert(n);
    // alert(typeof (n));
    if (isNaN(n)) {
        testo = $("#oqt_" + indice).val();
        $("#quantita_" + indice).val(testo);
        // alert("ripristinato valore originale");
    }
    $("#openBox_" + indice).html(String(testo));
    $("#qt_" + indice).val($("#quantita_" + indice).val());
    if ($("#oqt_" + indice).val() != $("#qt_" + indice).val()) {
        $("#ab_" + indice).val(1); // se il valore viene variato viene abilitato il salvataggio
    } else {
        $("#ab_" + indice).val(0); // se risulta uguale al vecchio valore non viene abilitato
    }
    // devo ricalcolare il totale prodotto e scriverlo nella label
    // alert("Nuovo totale");
    var totale = 0.00;
    totale = $("#quantita_" + indice).val() * $("#pz_" + indice).val();
    // alert("totale = " + totale);
    totale = totale.toFixed(2);
    // alert("totale arrot. = " + totale);
    var testo = "€ " + totale;
    // alert("Testo = " + testo);
    $("#tot_" + indice).text(testo);
    // alert("totale trascritto");
    chiudiInserimento(indice);
}

function Entrato(indice) {
    var testo = $("#quantita_" + indice).val();
    if (testo == "0") { testo = ""; $("#quantita_" + indice).val(""); } // 2028-08-27
}

function AnnullaModificheA(indice) {
    $("#quantita_" + indice).val($("#openBox_" + indice).text());
    chiudiInserimento(indice);
}

function CheckSubmitFunction(e) {
    if (!abilitaForm) {
        e.preventDefault();
        someBug();
        return false;
    } else {
        return true;
    }
}

function abilitaSubmit() {
    abilitaForm = true;
    return true;
}

function ControllareStatoSalvataggioDati() {
    flg = true;
    return flg;
}

function aggiungiRiga() {
    // devo aggiungere un nuovo prodotto, come posso fare?
    // controllo che non ci siano elementi da salvare, altrimenti avvisa che occorre salvarli prima di fare le aggiunte
    // alert("Aggiungi " + percorso + "aggiungidati.php");
    window.location.href = percorso + "aggiungiprodotto.php?giorno=" + $("#giorno").val() + "&ordine=" + $("#ordine").val();
    return true;
}

function CambiaData() {
    // 2024-09-03 - Al cambio del valore della data effettua il submit
    var datascelta = $('#giorno').val();
    // alert(datascelta);
    setTimeout(function () { $("#chgdate").submit(); }, 100);
    return true;
}

