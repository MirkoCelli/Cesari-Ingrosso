<html>
 <body>
  <H1>Ordine Cliente</h1>
  <div id="ClientiDIV">
   <table align="left">
	<tr><td colspan="2">
	    <table id="navgridClienti"></table>
		<div id="pagernavClienti"></div>
		<!-- definisce il navigatore e la griglia -->	
		<script src="js/ordiniclientepage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsClienti"></div>			
	</td></tr>
    <!-- Elenco Ordini per il Cliente selezionato -->
    <tr>
		<td>
	     <table id="navgridOrdini"></table>
		 <div id="pagernavOrdini"></div>
		 <!-- definisce il navigatore e la griglia -->	
		 <br /><br />
		 <div id="fieldsOrdini"></div>			
	    </td>
	    <td>
	     <table id="navgridDettOrd"></table>
		 <div id="pagernavDettOrd"></div>
		 <!-- definisce il navigatore e la griglia -->	
		 <br /><br />
		 <div id="fieldsDettOrd"></div>			
	    </td>
	</tr>
   </table>
  </div>
 </body>
</html>