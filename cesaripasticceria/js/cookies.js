// © 2024 - Robert Gasperoni by In The Net di Gasperoni Robert
// Script per il login che richiedere esplicitamente l'autorizzazione ad usare le cookies per regisatrare i token
// non si registrano dati personali tranne che l'account di accesso al servizio
// se non viene accettato allora si esce dal programma con un redirect

function CheckCookies(gettone) {
    if (gettone == undefined || gettone == "") {
        if (navigator.cookieEnabled == true) {
            var conferma = confirm("Autorizzate l'uso di cookies per registrare dati non personali utili al funzionamento della procedura Ordinativi?");
            if (!conferma) {
                window.location.href = 'espulso.php';
            }
            return conferma;
        } else {
            alert("Dovete abilitare le cookies per poter usare la procedura Ordinativi");
            return false;
        }
    }
}