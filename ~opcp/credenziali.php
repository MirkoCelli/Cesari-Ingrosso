<html>
 <body>
  <H1>Credenziali</h1>
  <div id="ClientiDIV">
   <table align="left">
	<tr><td>
	    <table id="navgridClienti"></table>
		<div id="pagernavClienti"></div>
		<!-- definisce il navigatore e la griglia -->	
		<script src="js/credenzialipage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsClienti"></div>
			
	</td></tr>
   </table>
   <table>
	 <tr>
	   <td>Selezionare il tipo di soggetto:
		   <select name="tiposoggetto" id="tiposoggetto">
			   <option value="1">CLIENTE</option>
			   <option value="2">INTERMEDIARIO</option>
			   <option value="3">RESPONSABILE</option>
		   </select>
	   </td>
	   <td>Nome soggetto (anche parziale): <input name="nomesoggetto" id="nomesoggetto" type="text" value=""/></td>
	   <td><button name="ricercasoggetto" id="ricercasoggetto" onclick="return RicercaSoggetti();">Ricerca</button></td>
	 </tr>
	 <tr>
	   <td colspan="3">
		   <div name="ElencoSoggetti" id="ElencoSoggetti"></div>
	   </td>
	 </tr>
   </table>
  </div>
 </body>
</html>