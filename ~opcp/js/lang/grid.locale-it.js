(function($){
/**
 * jqGrid English Translation
 * Tony Tomov tony@trirand.com
 * http://trirand.com/blog/ 
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
**/
$.jgrid = $.jgrid || {};
$.extend($.jgrid,{
	defaults : {
		recordtext: "Record {0} - {1} di {2}",
		emptyrecords: "Nessun record da visualizzare",
		loadtext: "Caricamento...",
		pgtext : "Pagina {0} di {1}"
	},
	search : {
		caption: "Ricerca...",
		Find: "Cerca",
		Reset: "Pulisci",
		odata: [ { oper:'eq', text:'uguale'},{ oper:'ne', text:'non uguale'},{ oper:'lt', text:'minore'},{ oper:'le', text:'minore or uguale'},{ oper:'gt', text:'maggiore'},{ oper:'ge', text:'maggiore o uguale'},{ oper:'bw', text:'inizia con'},{ oper:'bn', text:'non inizia con'},{ oper:'in', text:'è incluso in'},{ oper:'ni', text:'non è incluso in'},{ oper:'ew', text:'termina con'},{ oper:'en', text:'non termina con'},{ oper:'cn', text:'contiene'},{ oper:'nc', text:'non contiene'},{ oper:'nu', text:'is null'},{ oper:'nn', text:'is not null'} ],
		groupOps: [{ op: "AND", text: "tutti" },{ op: "OR",  text: "alcuni" }],
		matchText:" combacia",rulesText:" regole",
		operandTitle : "Click per selezionare le operazioni di ricerca.",
		resetTitle : "Pulisce i valori per la ricerca"
	},
	edit : {
		addCaption: "Aggiungi un Record",
		editCaption: "Modifica Record",
		bSubmit: "Invia",
		bCancel: "Annulla",
		bClose: "Chiudi",
		saveData: "I dati sono stati modificati! Salvo le modifiche?",
		bYes : "Si",
		bNo : "No",
		bExit : "Annulla",
		msg: {
			required:"Il Campo è richiesto",
			number:"Inserire un numero valido",
			minValue:"il valore deve essere maggiore o uguale a ",
			maxValue:"il valore deve essere mionre o uguale a",
			email: "non è una e-mail valida",
			integer: "Inserire un numero intero valido",
			date: "Inserire una data valida",
			url: "Indirizzo URL non valido. Il Prefisso è obbligatorio ('http://' or 'https://')",
			nodefined : " non è definito!",
			novalue : " il valore di ritorno è obbligatorio!",
			customarray : "La funzione personalizzata deve ritornare un array!",
			customfcheck : "La funzione personalizzata deve essere presente in caso di verifica personalizzata!"
			
		}
	},
	view : {
		caption: "Mostra Record",
		bClose: "Chiudi"
	},
	del : {
		caption: "Elimina",
		msg: "Eliminare i record selezionati?",
		bSubmit: "Elimina",
		bCancel: "Annulla"
	},
	nav : {
		edittext: "",
		edittitle: "Modifica la riga selezionata",
		addtext:"",
		addtitle: "Inserisce una nuova riga",
		deltext: "",
		deltitle: "Elimina la riga selezionata",
		searchtext: "",
		searchtitle: "Ricerca i record",
		refreshtext: "",
		refreshtitle: "Ricarica la griglia",
		alertcap: "Attenzione",
		alerttext: "Selezionare una riga",
		viewtext: "",
		viewtitle: "Mostra la riga selezionata"
	},
	col : {
		caption: "Scegliere le colonnne",
		bSubmit: "Ok",
		bCancel: "Annulla"
	},
	errors : {
		errcap : "Errore",
		nourl : "URL non impostato",
		norecords: "Non ci sono record da elaborare",
		model : "il numero di colNames differisce da colModel!"
	},
	formatter : {
		integer : {thousandsSeparator: ",", defaultValue: '0'},
		number : {decimalSeparator:".", thousandsSeparator: ",", decimalPlaces: 2, defaultValue: '0.00'},
		currency : {decimalSeparator:".", thousandsSeparator: ",", decimalPlaces: 2, prefix: "", suffix:"", defaultValue: '0.00'},
		date : {
			dayNames:   [
				"Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab",
				"Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"
			],
			monthNames: [
				"Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic",
				"Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
			],
			AmPm : ["am","pm","AM","PM"],
			S: function (j) {return j < 11 || j > 13 ? ['st', 'nd', 'rd', 'th'][Math.min((j - 1) % 10, 3)] : 'th';},
			srcformat: 'Y-m-d',
			newformat: 'j/n/Y',
			parseRe : /[#%\\\/:_;.,\t\s-]/,
			masks : {
				// see http://php.net/manual/en/function.date.php for PHP format used in jqGrid
				// and see http://docs.jquery.com/UI/Datepicker/formatDate
				// and https://github.com/jquery/globalize#dates for alternative formats used frequently
				// one can find on https://github.com/jquery/globalize/tree/master/lib/cultures many
				// information about date, time, numbers and currency formats used in different countries
				// one should just convert the information in PHP format
				ISO8601Long:"Y-m-d H:i:s",
				ISO8601Short:"Y-m-d",
				// short date:
				//    n - Numeric representation of a month, without leading zeros
				//    j - Day of the month without leading zeros
				//    Y - A full numeric representation of a year, 4 digits
				// example: 3/1/2012 which means 1 March 2012
				ShortDate: "j/n/Y", // in jQuery UI Datepicker: "M/d/yyyy"
				// long date:
				//    l - A full textual representation of the day of the week
				//    F - A full textual representation of a month
				//    d - Day of the month, 2 digits with leading zeros
				//    Y - A full numeric representation of a year, 4 digits
				LongDate: "l, F d, Y", // in jQuery UI Datepicker: "dddd, MMMM dd, yyyy"
				// long date with long time:
				//    l - A full textual representation of the day of the week
				//    F - A full textual representation of a month
				//    d - Day of the month, 2 digits with leading zeros
				//    Y - A full numeric representation of a year, 4 digits
				//    g - 12-hour format of an hour without leading zeros
				//    i - Minutes with leading zeros
				//    s - Seconds, with leading zeros
				//    A - Uppercase Ante meridiem and Post meridiem (AM or PM)
				FullDateTime: "l, F d, Y g:i:s A", // in jQuery UI Datepicker: "dddd, MMMM dd, yyyy h:mm:ss tt"
				// month day:
				//    F - A full textual representation of a month
				//    d - Day of the month, 2 digits with leading zeros
				MonthDay: "F d", // in jQuery UI Datepicker: "MMMM dd"
				// short time (without seconds)
				//    g - 12-hour format of an hour without leading zeros
				//    i - Minutes with leading zeros
				//    A - Uppercase Ante meridiem and Post meridiem (AM or PM)
				ShortTime: "g:i A", // in jQuery UI Datepicker: "h:mm tt"
				// long time (with seconds)
				//    g - 12-hour format of an hour without leading zeros
				//    i - Minutes with leading zeros
				//    s - Seconds, with leading zeros
				//    A - Uppercase Ante meridiem and Post meridiem (AM or PM)
				LongTime: "g:i:s A", // in jQuery UI Datepicker: "h:mm:ss tt"
				SortableDateTime: "Y-m-d\\TH:i:s",
				UniversalSortableDateTime: "Y-m-d H:i:sO",
				// month with year
				//    Y - A full numeric representation of a year, 4 digits
				//    F - A full textual representation of a month
				YearMonth: "F, Y" // in jQuery UI Datepicker: "MMMM, yyyy"
			},
			reformatAfterEdit : false
		},
		baseLinkUrl: '',
		showAction: '',
		target: '',
		checkbox : {disabled:true},
		idName : 'id'
	}
});
})(jQuery);
