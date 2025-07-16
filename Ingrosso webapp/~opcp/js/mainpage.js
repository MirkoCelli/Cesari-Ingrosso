// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// trovato in https://www.shecodes.io/athena/38917-how-to-display-the-current-date-and-time-in-javascript e https://www.w3schools.com/jsref/jsref_tolocalestring.asp
// mostriamo l'orario corrente in maschera principale

// create a function to update the date and time
function updateDateTime() {
    // create a new `Date` object
    const now = new Date();

    // get the current date and time as a string
    const currentDateTime = now.toLocaleString("it-IT", { dateStyle: "medium", timeStyle: "medium" } );

    // update the `textContent` property of the `span` element with the `id` of `datetime`
    document.querySelector('#datetime').textContent = currentDateTime;
}

// call the `updateDateTime` function every second
// setInterval(updateDateTime, 1000);

// per la sezione Produzione

function CambiaPaginaProduzione() {
    // alert($("#giorno").val());
    // alert("Cambia Data");
    var gg = document.getElementById("giorno").value;// jQuery("#giorno").val();
    // alert(gg);
    loadPageToDiv2('produzione.php?giorno=' + gg + '&passo=1', '#pagina', 7);
    return false;
}
