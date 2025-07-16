// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// trovato in https://www.shecodes.io/athena/38917-how-to-display-the-current-date-and-time-in-javascript e https://www.w3schools.com/jsref/jsref_tolocalestring.asp
// mostriamo l'orario corrente in maschera principale

// create a function to update the date and time
function updateDateTime() {
    // create a new `Date` object
    const now = new Date();

    // get the current date and time as a string
    const currentDateTime = now.toISOString();
    var giorno = now.toLocaleDateString();
    var ora = now.toLocaleTimeString();
    
    var currentDate1 = currentDateTime.substring(0, 10);
    
    const currentDate = currentDate1.substring(8,10) + "/" + currentDate1.substring(5,7) + "/" + currentDate1.substring(0, 4); 
    
    const currentTime = currentDateTime.substring(11, 19);
    var orario = currentDate + "  " + currentTime;
    
    // update the `textContent` property of the `span` element with the `id` of `datetime`
    // document.querySelector('#datetime').textContent = orario;
    document.querySelector('#date').textContent = giorno; // currentDate;
    document.querySelector('#time').textContent = ora;  // currentTime;
}

// 14/10/2024 - Funzione per abilitare/disabilitare il cliente
function cambioStatoCliente(cliente, stato) {
    if (stato == 0) {
        if (true) // (confirm("Vuoi disattivare il servizio di consegna?")) 
        {
            // esegue uno script di riattivazione e poi fa il reload della pagina mainpage.php
            var urlStatoCliente = percorso + "cambiostatocliente.php?id=" + cliente + "&stato=" + stato;
            // alert(urlStatoCliente);
            // $.get(urlStatoCliente); // , function () { alert("Utenza Attivata con successo!"); }).fail(function () { alert("Ho avuto un problema, operazione annullata");});
            $.get(urlStatoCliente, function () {
                // alert("Utenza disttivata con successo!");
                setTimeout(function () {
                    // alert('Cambio Stato');
                    window.location.reload();
                }, 100);}).fail(function () { alert("Ho avuto un problema, operazione annullata");});
        }
    } else {
        if (true) // (confirm("Vuoi attivare il servizio di consegna?"))
        {
            // esegue uno script di disattivazione e poi fa il reload della pagina mainpage.php
            var urlStatoCliente = percorso + "cambiostatocliente.php?id=" + cliente + "&stato=" + stato;
            // alert(urlStatoCliente);
            // $.get(urlStatoCliente); // , function () { alert("Utenza Disattivata con successo!"); }).fail(function () { alert("Ho avuto un problema, operazione annullata"); });
            $.get(urlStatoCliente, function () {
                // alert("Utenza attivata con successo!");
                setTimeout(function () {
                    // alert('Cambio Stato');
                    window.location.reload();
                }, 100);}).fail(function () { alert("Ho avuto un problema, operazione annullata"); });
        } 
    }
    // ricarica la pagina mainpage.php dopo 2 secondi
    /*
    setTimeout(function () {
        // alert('Cambio Stato');
        window.location.reload();
    }, 2000);
    */
}

// 2024-10-31 - Cambia lo stato di visibilità della ruota di attesa
function mostraLoader() {
    // alert('Loader');
    $('.page_loader').css({ 'display': 'block' });
}

// call the `updateDateTime` function every second
setInterval(updateDateTime, 1000);