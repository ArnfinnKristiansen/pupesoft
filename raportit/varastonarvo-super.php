<?php

	// k�ytet��n slavea
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (isset($tee) and $tee == "lataa_tiedosto") {
		readfile("/tmp/".$tmpfilenimi);	
		exit;
	}
	else {

		echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

		if (!isset($pp)) $pp = date("d");
		if (!isset($kk)) $kk = date("m");
		if (!isset($vv)) $vv = date("Y");

		// tutkaillaan saadut muuttujat
		$osasto = trim($osasto);
		$try    = trim($try);
		$pp 	= sprintf("%02d", trim($pp));
		$kk 	= sprintf("%02d", trim($kk));
		$vv 	= sprintf("%04d", trim($vv));

		// h�rski oikeellisuustzekki
		if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

		// n�it� k�ytet��n queryss�
		$sel_osasto = "";
		$sel_tuoteryhma = "";

		echo " <SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">
			<!--

			function toggleAll(toggleBox) {

				var currForm = toggleBox.form;
				var isChecked = toggleBox.checked;
				var nimi = toggleBox.name;

				for (var elementIdx=1; elementIdx<currForm.elements.length; elementIdx++) {
					if (currForm.elements[elementIdx].type == 'checkbox' && currForm.elements[elementIdx].name.substring(0,7) == nimi && currForm.elements[elementIdx].value != '".t("Ei valintaa")."') {
						currForm.elements[elementIdx].checked = isChecked;
					}
				}
			}

			//-->
			</script>";

		// piirrell��n formi
		echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";

		echo "<table><tr valign='top'><td><table><tr><td class='back'>";

		// n�ytet��n soveltuvat osastot
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','OSASTO_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='OSASTO' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuoteosasto").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_osa' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_osasto!="") {
				if (in_array($rivi['selite'],$mul_osasto)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_osasto[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";

		echo "<td><table><tr><td valign='top' class='back'>";

		// n�ytet��n soveltuvat tryt
		$query = "SELECT avainsana.selite, ".avain('select')." FROM avainsana ".avain('join','TRY_')." WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='TRY' order by avainsana.selite+0";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuoterym�").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_try' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_try!="") {
				if (in_array($rivi['selite'],$mul_try)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_try[]' value='$rivi[selite]' $mul_check></td><td>$rivi[selite] - $rivi[selitetark]</td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";
		
		echo "<td><table><tr><td valign='top' class='back'>";

		// n�ytet��n soveltuvat tuotemerkit
		$query = "	SELECT distinct tuotemerkki FROM tuote use index (yhtio_tuotemerkki) WHERE yhtio='$kukarow[yhtio]' and tuotemerkki != '' ORDER BY tuotemerkki";
		$res2  = mysql_query($query) or die($query);

		if (mysql_num_rows($res2) > 11) {
			echo "<div style='height:265;overflow:auto;'>";
		}

		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tuotemerkki").":</th></tr>";
		echo "<tr><td><input type='checkbox' name='mul_tmr' onclick='toggleAll(this);'></td><td nowrap>".t("Ruksaa kaikki")."</td></tr>";

		while ($rivi = mysql_fetch_array($res2)) {
			$mul_check = '';
			if ($mul_tmr!="") {
				if (in_array($rivi['tuotemerkki'], $mul_tmr)) {
					$mul_check = 'CHECKED';
				}
			}

			echo "<tr><td><input type='checkbox' name='mul_tmr[]' value='$rivi[tuotemerkki]' $mul_check></td><td> $rivi[tuotemerkki] </td></tr>";
		}

		echo "</table>";

		if (mysql_num_rows($res2) > 11) {
			echo "</div>";
		}

		echo "</table>";
		echo "</td>";

		echo "</tr>";
		echo "</table>";

		echo "<br><table>";
		
		$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		echo "<tr><th valign=top>" . t('Varastot') . "<br /><br /><span style='font-size: 0.8em;'>"
			. t('Saat kaikki varastot jos et valitse yht��n') 
			. "</span></th>
		    <td>";
        
		$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();
		
        while ($varow = mysql_fetch_array($vares)) {
			$sel = '';
			if (in_array($varow['tunnus'], $varastot)) {
				$sel = 'checked';
			}
			
			echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
		}
		
		echo "
		    </td>
		</tr>";
		echo "</table>";
		
		echo "<br><table>";
		echo "<tr>";
		echo "<th>Sy�t� vvvv-kk-pp:</th>";
		echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Tyyppi:</th>";

		$sel1 = "";
		$sel2 = "";
		$sel3 = "";

		if ($tyyppi == "A") {
			$sel1 = "SELECTED";
		}
		elseif($tyyppi == "B") {
			$sel2 = "SELECTED";
		}
		elseif($tyyppi == "C") {
			$sel3 = "SELECTED";
		}

		echo "<td>
				<select name='tyyppi'>
				<option value='A' $sel1>".t("N�ytet��n tuotteet joilla on saldoa")."</option>
				<option value='B' $sel2>".t("N�ytet��n tuotteet joilla ei ole saldoa")."</option>
				<option value='C' $sel3>".t("N�ytet��n kaikki tuotteet")."</option>
				</select>
				</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Varastonarvorajaus:</th>";
		echo "<td>Alaraja:<input type='text' name='alaraja' size='7' value='$alaraja'> Yl�raja:<input type='text' name='ylaraja' size='7' value='$ylaraja'></td>";
		echo "</tr>";
		
		
		$sel1 = "";
		$sel2 = "";

		if ($summaustaso == "S") {
			$sel1 = "SELECTED";
		}
		elseif($summaustaso == "P") {
			$sel2 = "SELECTED";
		}
		echo "<tr>";
		echo "<th>Summaustaso:</th>";
		
		echo "<td>
				<select name='summaustaso'>
				<option value='S' $sel1>".t("Varastonarvo summattuna")."</option>
				<option value='P' $sel2>".t("Varastonarvo varastopaikoittain (HUOM: Vain nykyinen varastonarvo lasketaan.)")."</option>
				</select>
				</td>";
		echo "</tr>";
		
		
		echo "<tr><th valign='top'>".t("Tuotelista")."</th><td><textarea name='tuotteet' rows='5' cols='15'>$tuotteet</textarea></td></tr>";
		
		echo "</table>";
		echo "<br>";

		if($valitaan_useita == '') {
			echo "<input type='submit' value='Laske varastonarvot'>";
		}
		else {
			echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
		}

		echo "</form>";


		if (!empty($varastot) or count($mul_osasto) > 0 or count($mul_try) > 0 or count($mul_tmr) > 0 or $tuotteet != '') {

			$lisa = "";
			
			if (count($mul_osasto) > 0) {
				$sel_osasto = "('".str_replace(',','\',\'',implode(",", $mul_osasto))."')";
				$lisa .= " and tuote.osasto in $sel_osasto ";
			}
			if (count($mul_try) > 0) {
				$sel_tuoteryhma = "('".str_replace(',','\',\'',implode(",", $mul_try))."')";
				$lisa .= " and tuote.try in $sel_tuoteryhma ";
			}
			if (count($mul_tmr) > 0) {
				$sel_tuotemerkki = "('".str_replace(',','\',\'',implode(",", $mul_tmr))."')";
				$lisa .= " and tuote.tuotemerkki in $sel_tuotemerkki ";
			}
			
			if ($tuotteet != '') {				
				$tuotteet = explode("\n", $tuotteet);
				$tuoterajaus = "";
				
				foreach($tuotteet as $tuote) {
					if (trim($tuote) != '') {
						$tuoterajaus .= "'".trim($tuote)."',";
					}
				}
				
				$lisa .= "and tuote.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
			}

			if (!empty($varastot)) {				
				$lisa .= " and varastopaikat.tunnus IN (" . implode(', ', $varastot) . ")";
            }
			
			// tuotteen m��r� varastossa nyt
			if ($summaustaso == "S") {
				$saldolisa = " sum(saldo) varasto";
				$saldogrou = " GROUP BY 1,2,3,4,5,6,7,8,9,10,11";
			}
			else {
				$saldolisa = " hyllyalue, hyllynro, hyllyvali, hyllytaso, saldo varasto";
				$saldogrou = " ";
			}
			
			
			if ($tuoteryhma == "tyhjat") {
				$trylisa2 .= " HAVING try='0' ";
			}
			
			if ($osasto == "tyhjat") {
				if ($tuoteryhma == "tyhjat") {
					$trylisa2 .= " or osasto='0' ";
				}
				else {
					$trylisa2 .= " HAVING osasto='0' ";
				}
			}
		
			// haetaan halutut tuotteet
			$query  = "	SELECT 
						tuote.tuoteno, 
						if(atry.selite is not null, atry.selite, 0) try, 
						if(aosa.selite is not null, aosa.selite, 0) osasto,
						tuote.tuotemerkki, tuote.nimitys, tuote.kehahin, 
						tuote.epakurantti25pvm, tuote.epakurantti50pvm, tuote.epakurantti75pvm, tuote.epakurantti100pvm, 
						tuote.sarjanumeroseuranta,
						group_concat(tuotepaikat.tunnus) paikkatun,
						$saldolisa
						FROM tuote
						$varlisa
						LEFT JOIN avainsana atry use index (yhtio_laji_selite) on atry.yhtio=tuote.yhtio and atry.selite=tuote.try and atry.laji='TRY'
						LEFT JOIN avainsana aosa use index (yhtio_laji_selite) on aosa.yhtio=tuote.yhtio and aosa.selite=tuote.osasto and aosa.laji='OSASTO'
						JOIN tuotepaikat ON tuotepaikat.yhtio=tuote.yhtio and tuotepaikat.tuoteno=tuote.tuoteno 
						JOIN varastopaikat ON (varastopaikat.yhtio=tuotepaikat.yhtio and
		                concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
		                concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')))
						$varlisa
						WHERE tuote.yhtio = '$kukarow[yhtio]'
						and tuote.ei_saldoa = ''
						$lisa
						$saldogrou
						ORDER BY tuote.osasto, tuote.try, tuote.tuoteno";
			$result = mysql_query($query) or pupe_error($query);
						
			$lask  = 0;
			$varvo = 0; // t�h�n summaillaan
	
			if(include('Spreadsheet/Excel/Writer.php')) {

				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}
	
			if(isset($workbook)) {
				$excelsarake = 0;
				
				if ($summaustaso == "P") {
					$worksheet->write($excelrivi, $excelsarake, t("Varasto"), 	$format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllyalue"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllynro"), 	$format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllyvali"), $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("Hyllytaso"), $format_bold);
					$excelsarake++;
				}
				elseif (!empty($varastot)) {
					$worksheet->write($excelrivi, $excelsarake, t("Varastot"), 	$format_bold);
					$excelsarake++;
				}
				
				if ($tuotemerkki != '') {
					$worksheet->write($excelrivi, $excelsarake, t("Tuotemerkki"), 	$format_bold);
					$excelsarake++;
				}
				
				$worksheet->write($excelrivi, $excelsarake, t("Osasto"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tuoteryhm�"), 	$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Tuoteno"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Nimitys"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Saldo"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Kehahin"), 		$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Varastonarvo"), 	$format_bold);
				$excelsarake++;
				$worksheet->write($excelrivi, $excelsarake, t("Kiertonopeus 12kk"), $format_bold);
				
				$excelrivi++;
				$excelsarake = 0;
			}

			require_once ('inc/ProgressBar.class.php');
			$bar = new ProgressBar();
			$elements = mysql_num_rows($result); // total number of elements to process
			$bar->initialize($elements); // print the empty bar
			

			while ($row = mysql_fetch_array($result)) {
				
				$bar->increase();
				$kehahin = 0;
		
				// Jos tuote on sarjanumeroseurannassa niin kehahinta lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole myyty(=laskutettu))
				if ($row["sarjanumeroseuranta"] == "S") {
					$query	= "	SELECT avg(tilausrivi_osto.rivihinta/tilausrivi_osto.kpl) kehahin
								FROM sarjanumeroseuranta
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
								LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' 
								and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus != -1
								and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
								and tilausrivi_osto.laskutettuaika != '0000-00-00'";
					$sarjares = mysql_query($query) or pupe_error($query);
					$sarjarow = mysql_fetch_array($sarjares);
						
					$kehahin = sprintf('%.2f', $sarjarow["kehahin"]);
				}
				else {
					$kehahin = sprintf('%.2f', $row["kehahin"]);
				}
				
				if ($summaustaso == "S" and ($pp != date("d") or $kk != date("m") or $vv != date("Y"))) {
					// tuotteen muutos varastossa annetun p�iv�n j�lkeen
					$query = "	SELECT sum(kpl * if(laji in ('tulo','valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
					 			FROM tapahtuma use index (yhtio_tuote_laadittu)
					 			WHERE yhtio = '$kukarow[yhtio]'
					 			and tuoteno = '$row[tuoteno]'
					 			and laadittu > '$vv-$kk-$pp 23:59:59'";
					$mres = mysql_query($query) or pupe_error($query);
					$mrow = mysql_fetch_array($mres);					
				}
				else {
					$mrow["muutoshinta"]	= 0;
					$mrow["muutoskpl"]		= 0;
					
					$pp = date("d");
					$kk = date("m");
					$vv = date("Y");
				}
				
				// katotaan onko tuote ep�kurantti nyt
				$kerroin = 1;
				
				if ($row['epakurantti25pvm'] != '0000-00-00') {
					$kerroin = 0.75;
				}
				if ($row['epakurantti50pvm'] != '0000-00-00') {
					$kerroin = 0.5;
				}
				if ($row['epakurantti75pvm'] != '0000-00-00') {
					$kerroin = 0.25;
				}
				if ($row['epakurantti100pvm'] != '0000-00-00') {
					$kerroin = 0;
				}
                
				// arvo historiassa: lasketaan (nykyinen varastonarvo) - muutoshinta
				$muutoshinta = ($row["varasto"] * $kehahin * $kerroin) - $mrow["muutoshinta"];

				// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
				$muutoskpl = $row["varasto"] - $mrow["muutoskpl"];

				if($tyyppi == "C") {
					$ok = "GO";
				}
				elseif ($tyyppi == "A" and $muutoskpl != 0) {
					$ok = "GO";
				}		
				elseif ($tyyppi == "B" and $muutoskpl == 0) {
					$ok = "GO";
				}
				else {
					$ok = "NO-GO";
				}
	
				if ($muutoshinta < $alaraja and $alaraja != '') {
					$ok = "NO-GO";
				}
	
				if ($muutoshinta > $ylaraja and $ylaraja != '') {
					$ok = "NO-GO";
				}
	
				if ($ok == "GO") {
		
					$lask++;
		
					// summataan varastonarvoa
					$varvo += $muutoshinta;

					// yritet��n kaivaa listaan viel� sen hetkinen kehahin jos se halutaan kerran n�hd�
					$kehasilloin = $kehahin * $kerroin; // nykyinen kehahin

					// jos ollaan annettu t�m� p�iv� niin ei ajeta t�t� , koska nykyinen kehahin on oikein ja n�in on nopeempaa! wheee!
					if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
						// katotaan mik� oli tuotteen viimeisin hinta annettuna p�iv�n� tai sitten sit� ennen
						$query = "	SELECT hinta
									FROM tapahtuma use index (yhtio_tuote_laadittu)
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laadittu <= '$vv-$kk-$pp 23:59:59'
									and hinta <> 0
									ORDER BY laadittu desc
									LIMIT 1";
						$ares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($ares) == 1) {
							// l�ydettiin keskihankintahinta tapahtumista k�ytet��n
							$arow = mysql_fetch_array($ares);
							$kehasilloin = $arow["hinta"];
							$kehalisa = "";
						}
						else {
							// ei l�ydetty alasp�in, kokeillaan kattoo l�hin hinta yl�sp�in
							$query = "	SELECT hinta
										FROM tapahtuma use index (yhtio_tuote_laadittu)
										WHERE yhtio = '$kukarow[yhtio]'
										and tuoteno = '$row[tuoteno]'
										and laadittu >= '$vv-$kk-$pp 23:59:59'
										and hinta <> 0
										ORDER BY laadittu
										LIMIT 1";
							$ares = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($ares) == 1) {
								// l�ydettiin keskihankintahinta tapahtumista k�ytet��n
								$arow = mysql_fetch_array($ares);
								$kehasilloin = $arow["hinta"];
								$kehalisa = "";
							}
							else {
								$kehalisa = "~";	
							}
						}
					}
					else {
						// haetaan tuotteen myydyt kappaleet
						$query  = "	SELECT ifnull(sum(kpl),0) kpl 
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika) 
									WHERE yhtio='$kukarow[yhtio]' and tyyppi='L' and tuoteno='$row[tuoteno]' and laskutettuaika <= '$vv-$kk-$pp' and laskutettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)";
						$xmyyres = mysql_query($query) or pupe_error($query);
						$xmyyrow = mysql_fetch_array($xmyyres);

						// haetaan tuotteen kulutetut kappaleet
						$query  = "	SELECT ifnull(sum(kpl),0) kpl 
									FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika) 
									WHERE yhtio='$kukarow[yhtio]' and tyyppi='V' and tuoteno='$row[tuoteno]' and toimitettuaika <= '$vv-$kk-$pp' and toimitettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)";
						$xkulres = mysql_query($query) or pupe_error($query);
						$xkulrow = mysql_fetch_array($xkulres);

						// lasketaan varaston kiertonopeus
						if ($muutoskpl > 0) {
							$kierto = round(($xmyyrow["kpl"] + $xkulrow["kpl"]) / $muutoskpl, 2);
						}
						else {
							$kierto = 0;
						}
					}

					if(isset($workbook)) {						
						if ($summaustaso == "P") {
							$rivipaikka = kuuluukovarastoon($row["hyllyalue"], $row["hyllynro"]);
							
							if ($rivipaikka > 0) {
								$query  = "SELECT nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus = '$rivipaikka'";
								$paikkaresult = mysql_query($query) or pupe_error($query);
								$paikkarow    = mysql_fetch_array($paikkaresult);
							}
							else {
								$paikkarow = array();
							}
							
							$worksheet->write($excelrivi, $excelsarake, $paikkarow["nimitys"], 	$format_bold);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["hyllyalue"], $format_bold);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["hyllynro"], 	$format_bold);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["hyllyvali"], $format_bold);
							$excelsarake++;
							$worksheet->write($excelrivi, $excelsarake, $row["hyllytaso"], $format_bold);
							$excelsarake++;
							
						}
						elseif (! empty($varastot)) {
							$worksheet->write($excelrivi, $excelsarake, $row["varastot"]);
							$excelsarake++;
						}
						
						if ($tuotemerkki != '') {
							$worksheet->write($excelrivi, $excelsarake, $row["tuotemerkki"]);
							$excelsarake++;
						}
						
						$worksheet->write($excelrivi, $excelsarake, $row["osasto"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $row["try"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $row["tuoteno"]);
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, asana('nimitys_',$row['tuoteno'],$row['nimitys']));
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$muutoskpl));
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$kehasilloin));
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$muutoshinta));
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, sprintf("%.02f",$kierto));
						$excelsarake++;
						$worksheet->write($excelrivi, $excelsarake, $kehalisa);
			
						$excelrivi++;
						$excelsarake = 0;
					}
				}
			}
			
	
			echo "<br><br>L�ytyi $lask tuotetta.<br><br>";

			if(isset($workbook)) {	
				// We need to explicitly close the workbook
				$workbook->close();

				echo "<table>";
				echo "<tr><th>".t("Tallenna tulos").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='Varastonarvo.xls'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
			
			echo "<table>";
			echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
			echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
			echo "</table>";

		}

		require ("../inc/footer.inc");
	}
?>