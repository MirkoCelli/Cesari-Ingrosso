// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// trovato in https://www.shecodes.io/athena/38917-how-to-display-the-current-date-and-time-in-javascript e https://www.w3schools.com/jsref/jsref_tolocalestring.asp
// mostriamo l'orario corrente in maschera principale

function confermaCambioPassword() {

    return confirm("Vuoi richiedere il reset della Password?");
}

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
setInterval(updateDateTime, 1000);