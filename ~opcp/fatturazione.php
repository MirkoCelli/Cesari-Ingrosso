<html>
 <body>  
  <div id="FattureDIV">
      <table align="left">          
          <tr>
              <td>
                  <table>
                      <tr>
                          <td>
                              <font size="3">Fatturazione</font>
                          </td>
                          <td>
                              <select id="mese" name="mese" onchange="return CambiaPeriodo();">
                                  <option value="1">GENNAIO</option>
                                  <option value="2">FEBBRAIO</option>
                                  <option value="3">MARZO</option>
                                  <option value="4">APRILE</option>
                                  <option value="5">MAGGIO</option>
                                  <option value="6">GIUGNO</option>
                                  <option value="7">LUGLIO</option>
                                  <option value="8">AGOSTO</option>
                                  <option value="9">SETTEMBRE</option>
                                  <option value="10">OTTOBRE</option>
                                  <option value="11">NOVEMBRE</option>
                                  <option value="12">DICEMBRE</option>
                              </select>
                          </td>
                          <td>
                              <label for="inizioperiodo">Dalla data:</label>
                              <input id="inizioperiodo" name="inizioperiodo" type="date" value="<?= date('Y-m-01') ?>" />
                          </td>
                          <td>
                              <label for="fineperiodo">alla data:</label>
                              <input id="fineperiodo" name="fineperiodo" type="date" value="<?= date('Y-m-t') ?>" />
                          </td>
                          <td>
                              <button id="cambia" name="cambia" onclick="return RivalutaPeriodo();" disabled>Rivaluta</button>
                          </td>
                          <!-- 07/08/2024 - campo per indicare la data del giorno pe ri totali di ogni singolo cliente e il totale complessivo giornaliero -->
                          <td>
                              <label for="giornoperiodo">Data per il giornaliero di tutti i clienti:</label>
                              <input id="giornoperiodo" name="giornoperiodo" type="date" value="<?= date('Y-m-d') ?>" />
                              <button id="giornaliero" name="giornaliero" onclick="return MostraGiornaliero();">Giornaliero</button>
                          </td>
                          <td>&nbsp;&nbsp;&nbsp;</td>
                          <!-- 07/08/2024 - campo per indicare la data del giorno pe ri totali di ogni singolo cliente e il totale complessivo giornaliero -->
                          <td>
                              <button id="generale" name="generale" onclick="return RiepilogoGeneralePeriodo();">Riepilogo Generale</button>
                          </td>
                          <!-- 15/08/2024 - Generazione file Excel CSV per il Riepilogo Mensile della Clientela -->
                          <td>
                              <button id="riepmese" name="riepmese" onclick="return GeneraCSVRiepilogoMensileClientela();">Riepilogo Mensile Clientela</button>                              
                          </td>
                          <!-- 15/08/2024 - Generazione della fatturazione Mensile della Clientela -->
                          <td>
                              <button id="fattmese" name="fattmese" onclick="return GeneraFattureMensileClientela();">Fatturazione Mensile Clientela</button>
                          </td>

                      </tr>
                  </table>
              </td>
          </tr>
          <tr>
              <td>
                  <table id="navgridFatture"></table>
                  <div id="pagernavFatture"></div>
                  <!-- definisce il navigatore e la griglia -->
                  <script src="js/fatturazionepage.js" type="text/javascript"></script>
                  <br /><br />
                  <div id="fieldsFatture"></div>
              </td>
          </tr>          
          <tr valign="top">
              <td>
                  <div id="dettagliFatturazione"></div>
              </td>
          </tr>
      </table>
  </div>
 </body>
</html>