// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Script per gestire gli elementi in aggiungidati.php

let vettore = [{ codiceprodotto: "AB001", descrizioneprodotto: "CROISSANT ALLA MELA", prezzounitario: 0.80, unitamisura: "Pz" },
    { codiceprodotto: "AB002", descrizioneprodotto: "CROISSANT ALLA PERA", prezzounitario: 0.80, unitamisura: "Pz" },
    { codiceprodotto: "AB003", descrizioneprodotto: "CROISSANT VUOTO", prezzounitario: 0.80, unitamisura: "Pz" },
    { codiceprodotto: "AB004", descrizioneprodotto: "CROISSANT CON CREMA", prezzounitario: 0.80, unitamisura: "Pz" }];

function prodottoSelezionato(prodotto) {
    // alert("Chiamato prodottoSelezionato");
    // alert(prodotto);
    
    // cerco i dati di questo prodotto e li riporto in maschera
    for (var i = 0; i < vettore.length; i++) {
        var elemento = vettore[i];
        // alert(elemento.codiceprodotto + " - " + elemento.descrizioneprodotto + " - " + elemento.prezzounitario + " - " + elemento.unitamisura);        
        if (elemento.codiceprodotto == prodotto) {
            // riporto questi valori nelle labels e in input quantità indico 0
            $("#unmis").text(elemento.unitamisura);            
            $("#codprod").text(elemento.codiceprodotto);
            
            if (elemento.unitamisura == "Pz") {
                $("#quantita").val(0);
            } else {
                $("#quantita").val(0.00);
            }
            quant = 0;
            // alert(quant);
            $("#descrprod").text(elemento.descrizioneprodotto);
            $("#prezzoprod").text("€ " + elemento.prezzounitario.toFixed(2));
            prezzo = elemento.prezzounitario;
            tot = quant * prezzo;
            $("#totprod").text("€ "+tot.toFixed(2));            
            // alert("Inserito");
        }        
    }    
    return true;
}

function cambioQuantita() {
    quant = parseFloat($("#quantita").val());
    // alert(quant);
    prezzo = parseFloat($("#prezzoprod").text().replace("€ ", ""));
    tot = quant * prezzo;
    $("#totprod").text("€ " + tot.toFixed(2));
}

function AggiungereOrdine() {
    // qui leggiamo i valori registrati in maschera, rieseguiamo i calcolo dei totali e 
    // chiamiamo una Ajax per farli registrare fra gli ordini del giorno per il cliente
    // alert("Aggiungere all'Ordine");
    codprod = $("#codprod").text();
    descprod = $("#descrprod").text();
    um = $("#unmis").text();
    quant = parseFloat($("#quantita").val());
    // alert(quant);
    prezzo = parseFloat($("#prezzoprod").text().replace("€ ", ""));
    tot = quant * prezzo;
    // alert("cp: " + codprod + " - " + descprod + " - " + um + " - " + quant + " - " + prezzo + " - " + tot);
    alert("Aggiunto all'ordine : " + "cp: " + codprod + " - " + descprod + " - " + um + " - " + quant + " - € " + prezzo.toFixed(2) + " - € " + tot.toFixed(2));
}

function ritornaPaginaPrec(giorno) {
    window.location.href = percorso + "giornaliero.php?giorno=" + giorno;
    return true;
}
