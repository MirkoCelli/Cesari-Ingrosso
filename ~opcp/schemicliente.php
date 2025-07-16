<html>
 <body>
  <H1>Schemi di Default Ordini per Cliente</h1>
  <div id="ClientiDIV">
   <table align="left">
	<tr><td colspan="2">
	    <table id="navgridClienti"></table>
		<div id="pagernavClienti"></div>
		<!-- definisce il navigatore e la griglia -->	
		<script src="js/schemiclientepage.js" type="text/javascript"></script>
		<br /><br />
		<div id="fieldsClienti"></div>			
	</td></tr>
    <!-- Elenco Ordini per il Cliente selezionato -->
	<tr>
	   <td colspan="2">
	     <table id="navgridSchemi"></table>
		 <div id="pagernavSchemi"></div>
		 <!-- definisce il navigatore e la griglia -->	
		 <br /><br />
		 <div id="fieldsSchemi"></div>			
	    </td>
		<td><textarea type="text" name="status" id="status" value="" hidden /></td><!-- mettere attributo hidden per nascondere la textarea -->
	</tr>
    <tr>
		<td>
	     <table id="navgridGiorni"></table>
		 <div id="pagernavGiorni"></div>
		 <!-- definisce il navigatore e la griglia -->	
		 <br /><br />
		 <div id="fieldsGiorni"></div>			
	    </td>
	    <td>
	     <table id="navgridDettSchema"></table>
		 <div id="pagernavDettSchema"></div>
		 <!-- definisce il navigatore e la griglia -->	
		 <br /><br />
		 <div id="fieldsDettSchema"></div>			
	    </td>
	</tr>
   </table>
  </div>
 </body>
</html>