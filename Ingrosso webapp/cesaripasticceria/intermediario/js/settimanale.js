// 2024-09-03 - settimanale.js per il change date

function CambiaData() {
    // 2024-09-03 - Al cambio del valore della data effettua il submit
    var datascelta = $('#giorno').val();
    // alert(datascelta);
    setTimeout(function () { $("#chgdate").submit(); }, 100);
    return true;
}