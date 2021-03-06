<?php

if ($faarao == 'TUTKHM') {
  $vikaa  = 0;
  $tarkea  = 0;
  $lask  = 0;
  $rivino  = 0;

  if (is_uploaded_file($_FILES['userfile']['tmp_name'])==TRUE) {

    $path_parts = pathinfo($_FILES['userfile']['name']);
    $name  = strtoupper($path_parts['filename']);
    $ext  = strtoupper($path_parts['extension']);

    if ($ext != "TXT" and $ext != "CSV") {
      die ("<font class='error'><br>".t("Ainoastaan .txt ja .cvs tiedostot sallittuja")."!</font>");
    }

    if ($_FILES['userfile']['size']==0) {
      die ("<font class='error'><br>".t("Tiedosto on tyhj�")."!</font>");
    }

    $file = fopen($_FILES['userfile']['tmp_name'], "r") or die (t("Tiedoston avaus ep�onnistui")."!");
    $rivi = fgets($file);

    while (!feof($file)) {

      $rivino++;
      $rivi = explode("\t", $rivi);
      // Eka layout on 0 tilausnro, 1 toim_tuoteno, 2 tuoteno, 3 kpl, 4 hinta, 5 kuluhinta
      $tilnro     = trim($rivi[0]);
      $toim_tuoteno  = trim($rivi[1]);
      $tuoteno    = trim($rivi[2]);
      $kpl      = sprintf('%.2f', str_replace(',', '.', $rivi[3]));
      $hinta      = hintapyoristys(str_replace(',', '.', $rivi[4]));
      //$kuluhinta  = sprintf('%.2f', str_replace(',', '.', $rivi[5]));

      $tuote_etsi = "";

      // toimittajan tuotenumero ylikirjottaa tuotteen?
      if ($toim_tuoteno != "") {
        $query = "SELECT GROUP_CONCAT(distinct concat(\"'\", tuoteno, \"'\") SEPARATOR ',') tuoteno
                  FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
                  WHERE yhtio      = '$kukarow[yhtio]'
                  and toim_tuoteno = '$toim_tuoteno'
                  and liitostunnus = '$toimittajaid'";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
          $rivi = mysql_fetch_array($result);
          $tuote_etsi = $rivi["tuoteno"];
        }
        else {
          $tuote_etsi = "";
        }
      }

      if ($tuoteno != "" and $tuote_etsi == "") {
        $tuote_etsi = "'$tuoteno'";
      }

      if ($tuote_etsi != "") {

        //Etsit��n hyv�� osumaa!
        $query = "SELECT tilausrivi.*
                  FROM tilausrivi, lasku, tuote
                  WHERE lasku.ytunnus        = '$laskurow[ytunnus]'
                  and lasku.yhtio            = '$kukarow[yhtio]'
                  and tilausrivi.otunnus     = lasku.tunnus
                  and tilausrivi.yhtio       = lasku.yhtio
                  and tilausrivi.yhtio       = tuote.yhtio
                  and tilausrivi.tuoteno     = tuote.tuoteno
                  and lasku.tila             = 'O'
                  and lasku.alatila          = 'A'
                  and tilausrivi.tyyppi      = 'O'
                  and tilausrivi.uusiotunnus = 0
                  and tilausrivi.tuoteno     in ($tuote_etsi)
                  ORDER BY tilausrivi.otunnus, tilausrivi.varattu";
        $result = pupe_query($query);

        $ok=0;

        if (mysql_num_rows($result) > 0) {

          while ($rivirow = mysql_fetch_array($result)) {

            $query = "SELECT tuotekerroin
                      FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
                      WHERE yhtio      = '$kukarow[yhtio]'
                      and toim_tuoteno = '$toim_tuoteno'
                      and liitostunnus = '$toimittajaid'
                      and tuoteno      = '$rivirow[tuoteno]'";
            $kerroinres = pupe_query($query);

            if (mysql_num_rows($kerroinres) > 1) {
              echo "<font class='error'>Samalle toimittajan tuotteelle l�ytyi useita tuotteita! K�ytet��n ensimm�ist�!</font><br>";
            }

            if (mysql_num_rows($kerroinres) > 0) {
              $kerroinrow = mysql_fetch_array($kerroinres);

              if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

              $apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
            }
            else {
              $apukpl = $kpl;
            }

            ///* Testataan eka, l�ytyyk� tilausnumero, tuotenumero, kpl m�tchi� *///
            if ($rivirow['varattu'] == $apukpl and $rivirow['otunnus'] == $tilnro) {
              $ok = 1;
              break;
            }
          }

          if ($ok != 1) {

            mysql_data_seek($result, 0);

            while ($rivirow = mysql_fetch_array($result)) {

              $query = "SELECT tuotekerroin
                        FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
                        WHERE yhtio      = '$kukarow[yhtio]'
                        and toim_tuoteno = '$toim_tuoteno'
                        and liitostunnus = '$toimittajaid'
                        and tuoteno      = '$rivirow[tuoteno]'";
              $kerroinres = pupe_query($query);

              if (mysql_num_rows($kerroinres) > 1) {
                echo "<font class='error'>Samalle toimittajan tuotteelle l�ytyi useita tuotteita! K�ytet��n ensimm�ist�!</font><br>";
              }

              if (mysql_num_rows($kerroinres) > 0) {
                $kerroinrow = mysql_fetch_array($kerroinres);

                if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

                $apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
              }
              else {
                $apukpl = $kpl;
              }

              ///* Testataan toka, l�ytyyk� tuotenumero, kpl m�tchi� *///
              if ($rivirow['varattu'] == $apukpl) {
                $ok = 1;
                break;
              }
            }
          }

          if ($ok != 1 and $hyvaksy != '') {

            mysql_data_seek($result, 0);

            while ($rivirow = mysql_fetch_array($result)) {

              $query = "SELECT tuotekerroin
                        FROM tuotteen_toimittajat use index (yhtio_toimtuoteno)
                        WHERE yhtio      = '$kukarow[yhtio]'
                        and toim_tuoteno = '$toim_tuoteno'
                        and liitostunnus = '$toimittajaid'
                        and tuoteno      = '$rivirow[tuoteno]'";
              $kerroinres = pupe_query($query);

              if (mysql_num_rows($kerroinres) > 1) {
                echo "<font class='error'>Samalle toimittajan tuotteelle l�ytyi useita tuotteita! K�ytet��n ensimm�ist�!</font><br>";
              }

              if (mysql_num_rows($kerroinres) > 0) {
                $kerroinrow = mysql_fetch_array($kerroinres);

                if ($kerroinrow["tuotekerroin"] == 0) $kerroinrow["tuotekerroin"] = 1;

                $apukpl = $kpl / $kerroinrow["tuotekerroin"]; // huomioidaan tuotekerroin
              }
              else {
                $apukpl = $kpl;
              }

              // Testataan kolmanneks, l�ytyyk� edes tuotenumero ja kappale m�tchi� jossa kpl:�� on riitt�v�sti
              // T�nne ei menn� ollenkaan, jos loput j�tet��n JT:seen ja kohdistettavan m��r� on nolla,
              // koska turha tehd� mit��n kun koko rivi j�� kuitenkin JT:seen
              if ($rivirow['varattu'] > $apukpl and ($hyvaksy == "LOP" or ($apukpl > 0 and $hyvaksy == "LOJ"))) {

                $filehinta = $hinta;

                // Poistetaan vanha rivi
                $query = "DELETE from tilausrivi
                          WHERE tunnus = '$rivirow[tunnus]'
                          and yhtio    = '$kukarow[yhtio]'
                          and tyyppi   = 'O'
                          and otunnus  = '$rivirow[otunnus]'";
                $poistoresult = pupe_query($query);

                // Jos ollaan poistamassa loput & kohdistettava m��r� on 0,
                // niin ei menn� tekem��n uusia rivej� koska niist� tulisi 0-rivej�
                if ($hyvaksy == "LOP" and $apukpl <= 0) {
                  break;
                }

                // Lis�t��n rivi ja splitataan se k�ytt�j�n toiveita mukaillen
                $kpl         = $apukpl;
                $tuoteno      = $rivirow["tuoteno"];
                $toimaika      = $rivirow["toimaika"];
                $kerayspvm      = $rivirow["kerayspvm"];

                if ($paivi != 'JOO') {
                  $hinta      = $rivirow["hinta"];
                }
                elseif ($hinta == 0) {
                  $hinta      = $rivirow["hinta"];
                }
                else {
                  $hinta      = $hinta;
                }

                $netto        = $rivirow["netto"];
                $var         = $hyvaksy."#!�!#".$rivirow['varattu'];
                $kukarow["kesken"]  = $rivirow["otunnus"];
                $laskurow["tila"]   = "O";
                $alv        = $rivirow["alv"];
                $kommentti      = $rivirow["kommentti"];
                $rivitunnus      = $rivirow["tunnus"];

                for ($alepostfix = 1; $alepostfix <= 1; $alepostfix++) {
                  ${'ale'.$alepostfix} = $rivirow["ale{$alepostfix}"];
                }

                $query = "SELECT *
                          FROM tuote
                          WHERE yhtio = '$kukarow[yhtio]'
                          and tuoteno = '$rivirow[tuoteno]'";
                $tuoteresult = pupe_query($query);
                $trow = mysql_fetch_array($tuoteresult);

                require 'lisaarivi.inc';

                $hinta = $filehinta;
                $ok = 1;
                break;
              }
            }
          }

          //Nyt t�m� merkataan valituksi
          if ($ok == 1) {

            /*if ($kuluhinta != 0) {
              $kululisa = ", kate='$kuluhinta' ";
            }
            else {*/
            $kululisa = "";
            //}

            if ($paivi == 'JOO' and $hinta != 0) {
              $hintalisa = ", hinta='$hinta' ";
            }
            else {
              $hintalisa = '';
            }

            //Tarksitetaan viel� ennen p�ivityst�, ettei saapumsita ole poistettu ja infotaan k�ytt�j��, jos n�in on k�ynyt
            $query = "SELECT tila
                      FROM lasku
                      WHERE yhtio     = '{$kukarow[yhtio]}'
                      AND tila        = 'K'
                      AND alatila     = ''
                      AND vanhatunnus = 0
                      AND tunnus      = '$otunnus'";
            $res = pupe_query($query);
            if (mysql_num_rows($res) == 1) {
              $query = "UPDATE tilausrivi SET uusiotunnus='$otunnus' $kululisa $hintalisa WHERE tunnus = '$rivirow[tunnus]' AND yhtio='$kukarow[yhtio]'";
              $result = pupe_query($query);
              echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $rivirow[tuoteno] ".t("M��r�").": $apukpl ".t("kohdistettu laskuun")."!</font><br>";

              if ($hinta != 0 and $hinta != $rivirow["hinta"] and $paivi != 'JOO') {
                echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $rivirow[tuoteno] ".t("Hinta on v��r�")."!</font><br>";
              }

              if ($paivi == 'JOO') {
                if ($hinta != 0) {
                  echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $paivirow[tuoteno] ".t("P�ivitettiin tiedostossa ollut hinta").": $hinta ".t("riville")."!</font><br>";
                }
                else {
                  echo "<font class='message'>".t("Rivi")." $rivino ".t("Tuotenumero").": $paivirow[tuoteno] ".t("EI p�ivitetty tiedostossa ollutta hintaa riville, koska se oli tyhj� tai nolla")."!</font><br>";
                }
              }
            } else {
              echo "<font class='error'>".t("VIRHE: Saapumiselle ei voi liitt�� rivej�, koska se on poistettu")."!</font><br>";
            }
          }
        }
      }
      if ($ok==0) echo "<font class='error'>".t("Rivi")." $rivino ".t("Tuotenumero").": $tuote_etsi ".t("M��r�").": $apukpl ".t("EI voitu kohdistaa laskuun")."!<br></font>";

      //haetaan seuraava rivi
      $rivi = fgets($file);
    }
  }

  $tila = '';
}
else {
  echo "<form method='post' name='sendfile' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='otunnus' value='$otunnus'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";
  echo "<input type='hidden' name='tee' value='$tee'>";
  echo "<input type='hidden' name='toiminto' value='kohdista'>";
  echo "<input type='hidden' name='tila' value='FAILISTA'>";
  echo "<input type='hidden' name='faarao' value='TUTKHM'>";



  echo "<font class='message'>".t("Tiedostomuoto").":</font><br><br>

      <table>
      <tr><th colspan='6'>".t("Sarkaineroteltu tekstitiedosto").".</th></tr>
      <tr>
      <td>".t("Tilausnumero")."</td>
      <td>".t("Toim_tuoteno")."</td>
      <td>".t("Tuoteno")."</td>
      <td>".t("M��r�")."</td>
      <td>".t("Hinta")."</td>";
  //    <td>".t("Kuluhinta")."</td>
  echo "  </tr>
      </table>

      <br>
      <table>";
  echo "<tr><th>".t("Valitse kohdistustapa").": </th><td><select name='hyvaksy'>";
  echo "<option value=''>".t("Tiedostossa oleva kpl on oltava sama kuin rivill� oleva kpl")."</option>";
  echo "<option value='LOJ'>".t("Kohdista tiedostossa oleva kpl ja merkkaa erotus JT:ksi")."</option>";
  echo "<option value='LOP'>".t("Kohdista tiedostossa oleva kpl ja poista ylij��v�t kpl:�t")."</option>";
  echo "  </select></td></tr>";

  echo "<tr><th>".t("P�ivitet��nk� hinnat?").": </th><td><select name='paivi'>";
  echo "<option value=''>".t("Tiedostossa oleva hinta EI P�IVITET� riville")."</option>";
  echo "<option value='JOO'>".t("Tiedostossa oleva hinta P�IVITET��N riville")."</option>";
  echo "  </select></td></tr>";

  echo "<tr><th>".t("Valitse tiedosto").":</th>
        <td><input name='userfile' type='file'></td>
        <td class='back'><input type='submit' value='".t("Aja")."'></td>
      </tr>
      </table>

      </form>";
  //pys�hdyt��n t�h�n
  exit;
}
