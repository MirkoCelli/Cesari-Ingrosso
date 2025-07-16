// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Script per gestire gli elementi in schemadefault.php

var abilitaForm = false;

function apriInserimento(indice) {
    // alert(indice);
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
    var n = parseInt(testo);
    // alert(n);
    // alert(typeof (n));
    if (isNaN(n)) {
        testo = $("#oqt_" + indice).val();
        $("#quantita_" + indice).val(testo);
        // alert("ripristinato valore originale");
    }
    $("#openBox_" + indice).html(String(testo));
    $("#qt_" + indice).val(testo);
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

function siglagiorno(gg) {
    var risp = "";
    var ggn = Number(gg);
    switch (ggn) {
        case 1:
            risp = "Lu";
            break;
        case 2:
            risp = "Ma";
            break;
        case 3:
            risp = "Me";
            break;
        case 4:
            risp = "Gi";
            break;
        case 5:
            risp = "Ve";
            break;
        case 6:
            risp = "Sa";
            break;
        case 7:
            risp = "Do";
            break;
    }
    return risp;
}

function clonareGiornoSettimana() {
    // indicare il giorno di pertenza e il giorno di arrivo
    // il lunedì viene sempre escluso
    // non si clona mai lo stesso giorno
    // si da per scontato che tutti i prodotti siano ordinati e tutti presenti
    // per semplificare la copiatura delle quantità 
    var inizio = $("#inizio").val();
    var fine = $("#fine").val();
    if (inizio == "1" || fine == "1") {
        alert("Non è ammesso il lunedì");
        return false;
    }
    if (inizio == fine) {
        alert("Non si clona sullo stesso giorno");
        return false;
    }
    // copiare i valori da inizio a fine
    // alert("inizio clonazione");
    var n = $("#numelem").val();
    for (let i = 1; i <= n; i++) {
        var elem_inizio = siglagiorno(inizio) + '_' + i.toString();
        var elem_fine = siglagiorno(fine) + '_' + i.toString();
        // alert(elem_inizio + " --> " + elem_fine);
        $("#quantita_" + elem_fine).val($("#quantita_" + elem_inizio).val());
        SalvaQtaProdottoA(elem_fine);
        // alert("Salvato in " + elem_fine);
    }
    alert("clonazione completata");
    return true;
}
