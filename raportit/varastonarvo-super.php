<?php

	// k�ytet��n slavea
	$useslave = 1;

	if (isset($_POST["tee"])) {
		if($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	require("../inc/parametrit.inc");

	if (!function_exists("force_echo")) {
		function force_echo($teksti) {
			global $kukarow, $yhtiorow;
//			ob_end_flush();
//	    	ob_start();
			echo t("$teksti<br>");
			ob_flush();
			flush();
		}
	}

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
		$sel_osasto 	= "";
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

		$varastot2 = array();

		if ($supertee == "RAPORTOI") {

			$tuote_lisa  = ""; // tuoterajauksia
			$having_lisa = ""; // having ehtoja
			$order_lisa = ""; // sorttausj�rjestys
			$paikka_lisa = "";

			// jos summaustaso on paikka, otetaan paikat mukaan selectiin
			if ($summaustaso == "P") {
				$paikka_lisa = ", tmp_tuotepaikat.hyllyalue,
								  tmp_tuotepaikat.hyllynro,
								  tmp_tuotepaikat.hyllyvali,
								  tmp_tuotepaikat.hyllytaso";
			}

			// laitetaan varastopaikkojen tunnukset mysql-muotoon
			if (!empty($varastot)) {
				$varastontunnukset = " AND varastopaikat.tunnus IN (";
				foreach ($varastot as $varasto) {
					$varastontunnukset .= "$varasto";
					if (count($varastot) > 1 and end($varastot) != $varasto) {
						$varastontunnukset .= ",";
					}
				}
				$varastontunnukset .= ")";
				$order_lisa = "varastonnimi, osasto, try, tuoteno";
            }
			else {
				$order_lisa = "tuote.osasto, tuote.try, tuote.tuoteno";
			}

			// tehd��n tuoterajauksia
			if (count($mul_osasto) > 0) {
				$sel_osasto = "('".str_replace(',','\',\'',implode(",", $mul_osasto))."')";
				$tuote_lisa .= " and tuote.osasto in $sel_osasto ";
			}
			if (count($mul_try) > 0) {
				$sel_tuoteryhma = "('".str_replace(',','\',\'',implode(",", $mul_try))."')";
				$tuote_lisa .= " and tuote.try in $sel_tuoteryhma ";
			}
			if (count($mul_tmr) > 0) {
				$sel_tuotemerkki = "('".str_replace(',','\',\'',implode(",", $mul_tmr))."')";
				$tuote_lisa .= " AND tuote.tuotemerkki in $sel_tuotemerkki ";
			}

			if ($epakur == 'epakur') {
				$tuote_lisa .= " AND (tuote.epakurantti100pvm != '0000-00-00' OR tuote.epakurantti75pvm != '0000-00-00' OR tuote.epakurantti50pvm != '0000-00-00' OR tuote.epakurantti25pvm != '0000-00-00') ";
			}
			elseif ($epakur == 'ei_epakur') {
				$tuote_lisa .= " AND tuote.epakurantti100pvm = '0000-00-00' ";
			}

			if ($tuotteet_lista != '') {
				$tuotteet = explode("\n", $tuotteet_lista);
				$tuoterajaus = "";
				foreach($tuotteet as $tuote) {
					if (trim($tuote) != '') {
						$tuoterajaus .= "'".trim($tuote)."',";
					}
				}
				$tuote_lisa .= "and tuote.tuoteno in (".substr($tuoterajaus, 0, -1).") ";
			}

			if ($tuoteryhma == "tyhjat" and $osasto == "tyhjat") {
				$having_lisa .= "HAVING (try = '0' or osasto = '0') ";
			}
			elseif ($osasto == "tyhjat") {
				$having_lisa .= "HAVING osasto = '0' ";
			}
			elseif ($tuoteryhma == "tyhjat") {
				$having_lisa .= "HAVING try = '0' ";
			}

			force_echo("Haetaan k�sitelt�vien tuotteiden varastopaikat historiasta.");

			// t�t� ei pit�isi ikin� olla, kun tempit on per connectio, mutta varmuudenvuoksi
			$query = "DROP TEMPORARY TABLE IF EXISTS tmp_tuotepaikat";
			$result = mysql_query($query) or pupe_error($query);
			
			// haetaan kaikki distinct tuotepaikat ja tehd��n temp table (t�m� n�ytt�� ep�tehokkaalta, mutta on testattu ja t�m� _on_ nopein tapa joinata ja tehd� asia)
			$query = "	CREATE TEMPORARY TABLE tmp_tuotepaikat
						(SELECT DISTINCT
						tapahtuma.tuoteno,
						tapahtuma.hyllyalue,
						tapahtuma.hyllynro,
						tapahtuma.hyllyvali,
						tapahtuma.hyllytaso
						FROM tapahtuma USE INDEX (yhtio_laadittu_hyllyalue_hyllynro)
						JOIN tuote ON	(tuote.yhtio = tapahtuma.yhtio
										AND tuote.tuoteno = tapahtuma.tuoteno
										AND tuote.ei_saldoa = ''
										$tuote_lisa)
						WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
						AND tapahtuma.laadittu > '$vv-$kk-$pp 23:59:59'
						AND tapahtuma.hyllyalue != ''
						AND tapahtuma.hyllynro != ''
						AND tapahtuma.hyllyvali != ''
						AND tapahtuma.hyllytaso != '')
						UNION
						(SELECT DISTINCT
						tuotepaikat.tuoteno,
						tuotepaikat.hyllyalue,
						tuotepaikat.hyllynro,
						tuotepaikat.hyllyvali,
						tuotepaikat.hyllytaso
						FROM tuotepaikat USE INDEX (tuote_index)
						JOIN tuote ON	(tuote.yhtio = tuotepaikat.yhtio
										AND tuote.tuoteno = tuotepaikat.tuoteno
										AND tuote.ei_saldoa = ''
										$tuote_lisa)
						WHERE tuotepaikat.yhtio = '$kukarow[yhtio]')";
			$result = mysql_query($query) or pupe_error($query);

			$query = "SELECT count(*) FROM tmp_tuotepaikat";
			$result = mysql_query($query) or pupe_error($query);
			$row = mysql_fetch_array($result);

			echo "L�ytyi $row[0] varastopaikkaa.<br>";

			force_echo("Haetaan k�sitelt�vien tuotteiden tiedot.");

			// haetaan halutut tuotteet
			$query  = "	SELECT DISTINCT
						varastopaikat.nimitys varastonnimi,
						varastopaikat.tunnus varastotunnus,
						tuote.tuoteno,
						if(atry.selite is not null, atry.selite, 0) try,
						if(aosa.selite is not null, aosa.selite, 0) osasto,
						tuote.tuotemerkki,
						tuote.nimitys,
						tuote.kehahin,
						if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25) kehahin_nyt,
						tuote.epakurantti25pvm,
						tuote.epakurantti50pvm,
						tuote.epakurantti75pvm,
						tuote.epakurantti100pvm,
						tuote.sarjanumeroseuranta,
						tuote.vihapvm
						$paikka_lisa
						FROM tmp_tuotepaikat
						JOIN tuote USE INDEX (tuoteno_index) ON	(tuote.yhtio = '$kukarow[yhtio]'
																AND tuote.tuoteno = tmp_tuotepaikat.tuoteno)
						JOIN varastopaikat ON	(varastopaikat.yhtio = tuote.yhtio
												AND concat(rpad(upper(varastopaikat.alkuhyllyalue), 5, '0'), lpad(upper(varastopaikat.alkuhyllynro), 5, '0')) <= concat(rpad(upper(tmp_tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tmp_tuotepaikat.hyllynro), 5, '0'))
												AND concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'), lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tmp_tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tmp_tuotepaikat.hyllynro), 5, '0'))
												$varastontunnukset)
						LEFT JOIN avainsana atry USE INDEX (yhtio_laji_selite) ON	(atry.yhtio = tuote.yhtio
																					and atry.kieli = '$yhtiorow[kieli]'
																					and atry.selite = tuote.try
																					and atry.laji = 'TRY')
						LEFT JOIN avainsana aosa USE INDEX (yhtio_laji_selite) ON 	(aosa.yhtio = tuote.yhtio
																					and aosa.kieli = '$yhtiorow[kieli]'
																					and aosa.selite = tuote.osasto
																					and aosa.laji = 'OSASTO')
						$having_lisa
						ORDER BY $order_lisa";
			$result = mysql_query($query) or pupe_error($query);

			$lask   = 0;
			$varvo  = 0; // t�h�n summaillaan
			$bvarvo = 0; // bruttovarastonarvo

			if(@include('Spreadsheet/Excel/Writer.php')) {
				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;
			}

			if(isset($workbook)) {
				$excelsarake = 0;

				$worksheet->writeString($excelrivi, $excelsarake, t("Varasto"), 		$format_bold);
				$excelsarake++;

				if ($summaustaso == "P") {
					$worksheet->writeString($excelrivi, $excelsarake, t("Hyllyalue"), 		$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, t("Hyllynro"), 		$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, t("Hyllyvali"), 		$format_bold);
					$excelsarake++;
					$worksheet->writeString($excelrivi, $excelsarake, t("Hyllytaso"), 		$format_bold);
					$excelsarake++;
				}

				if ($sel_tuotemerkki != '') {
					$worksheet->writeString($excelrivi, $excelsarake, t("Tuotemerkki"), 	$format_bold);
					$excelsarake++;
				}

				$worksheet->writeString($excelrivi, $excelsarake, t("Osasto"), 				$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteryhm�"), 			$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Tuoteno"), 			$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Nimitys"), 			$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Saldo"), 				$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Kehahin"), 			$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Varastonarvo"), 		$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Bruttovarastonarvo"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Kiertonopeus 12kk"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Viimeisin laskutus"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Ep�kurantti 25%"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Ep�kurantti 50%"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Ep�kurantti 75%"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Ep�kurantti 100%"), 	$format_bold);
				$excelsarake++;
				$worksheet->writeString($excelrivi, $excelsarake, t("Viimeinen hankintap�iv�"), 	$format_bold);
				$excelrivi++;
				$excelsarake = 0;
			}

			$elements = mysql_num_rows($result); // total number of elements to process

			echo "L�ytyi $elements tietuetta.<br><br>";
			echo "Lasketaan varastonarvo.<br>";

			if ($elements > 0) {
				require_once ('inc/ProgressBar.class.php');
				$bar = new ProgressBar();
				$bar->initialize($elements); // print the empty bar
			}

			while ($row = mysql_fetch_array($result)) {

				$kpl = 0;
				$varaston_arvo = 0;
				$bruttovaraston_arvo = 0;
				$bar->increase();

				// Jos tuote on sarjanumeroseurannassa niin varastonarvo lasketaan yksil�iden ostohinnoista (ostetut yksil�t jotka eiv�t viel� ole laskutettu)
				if ($row["sarjanumeroseuranta"] == "S" or $row["sarjanumeroseuranta"] == "U") {

					// jos summaustaso on per paikka, otetaan varastonarvo vain silt� paikalta
					if ($summaustaso == "P") {
						$summaus_lisa = "and sarjanumeroseuranta.hyllyalue = '$row[hyllyalue]'
											and sarjanumeroseuranta.hyllynro = '$row[hyllynro]'
											and sarjanumeroseuranta.hyllyvali = '$row[hyllyvali]'
											and sarjanumeroseuranta.hyllytaso = '$row[hyllytaso]'";
					}
					else {
						$summaus_lisa = "";
					}

					$query	= "	SELECT sarjanumeroseuranta.tunnus
								FROM sarjanumeroseuranta
								JOIN varastopaikat ON (varastopaikat.yhtio = sarjanumeroseuranta.yhtio
														and concat(rpad(upper(alkuhyllyalue), 5, '0'), lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
														and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(sarjanumeroseuranta.hyllyalue), 5, '0'), lpad(upper(sarjanumeroseuranta.hyllynro), 5, '0'))
														and varastopaikat.tunnus = '$row[varastotunnus]')
								LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON (tilausrivi_myynti.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus = sarjanumeroseuranta.myyntirivitunnus)
								LEFT JOIN tilausrivi tilausrivi_osto use index (PRIMARY) ON (tilausrivi_osto.yhtio = sarjanumeroseuranta.yhtio and tilausrivi_osto.tunnus = sarjanumeroseuranta.ostorivitunnus)
								WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]'
								and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
								and sarjanumeroseuranta.myyntirivitunnus != -1
								$summaus_lisa
								and (tilausrivi_myynti.tunnus is null or tilausrivi_myynti.laskutettuaika = '0000-00-00')
								and tilausrivi_osto.laskutettuaika != '0000-00-00'";
					$vararvores = mysql_query($query) or pupe_error($query);

					while ($vararvorow = mysql_fetch_array($vararvores)) {
						$varaston_arvo += sarjanumeron_ostohinta("tunnus", $vararvorow["tunnus"]);
						$bruttovaraston_arvo = $varaston_arvo;
						$kpl++; // saldo
					}
				}
				else {

					// jos summaustaso on per paikka, otetaan varastonarvo vain silt� paikalta
					if ($summaustaso == "P") {
						$summaus_lisa = "and tuotepaikat.hyllyalue = '$row[hyllyalue]'
											and tuotepaikat.hyllynro = '$row[hyllynro]'
											and tuotepaikat.hyllyvali = '$row[hyllyvali]'
											and tuotepaikat.hyllytaso = '$row[hyllytaso]'";
					}
					else {
						$summaus_lisa = "";
					}

					$query = "	SELECT
								sum(tuotepaikat.saldo) saldo,
								sum(tuotepaikat.saldo*if(tuote.epakurantti75pvm='0000-00-00', if(tuote.epakurantti50pvm='0000-00-00', if(tuote.epakurantti25pvm='0000-00-00', tuote.kehahin, tuote.kehahin*0.75), tuote.kehahin*0.5), tuote.kehahin*0.25)) varasto,
								sum(tuotepaikat.saldo*tuote.kehahin) bruttovarasto
								FROM tuotepaikat
								JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio and tuote.ei_saldoa = '')
								JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio
														and concat(rpad(upper(alkuhyllyalue), 5, '0'), lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
														and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'), lpad(upper(tuotepaikat.hyllynro), 5, '0'))
														and varastopaikat.tunnus = '$row[varastotunnus]')
								WHERE tuotepaikat.yhtio = '$kukarow[yhtio]'
								and tuotepaikat.tuoteno = '$row[tuoteno]'
								$summaus_lisa";
					$vararvores = mysql_query($query) or pupe_error($query);
					$vararvorow = mysql_fetch_array($vararvores);

					$kpl = $vararvorow["saldo"];
					$varaston_arvo = $vararvorow["varasto"];
					$bruttovaraston_arvo = $vararvorow["bruttovarasto"];
				}

				// jos summaustaso on per paikka, otetaan varastonmuutos vain silt� paikalta
				if ($summaustaso == "P") {
					$summaus_lisa = "and tapahtuma.hyllyalue = '$row[hyllyalue]'
										and tapahtuma.hyllynro = '$row[hyllynro]'
										and tapahtuma.hyllyvali = '$row[hyllyvali]'
										and tapahtuma.hyllytaso = '$row[hyllytaso]'";
				}
				else {
					$summaus_lisa = "";
				}

				// tuotteen muutos varastossa annetun p�iv�n j�lkeen
				$query = "	SELECT sum(kpl * if(laji in ('tulo', 'valmistus'), kplhinta, hinta)) muutoshinta, sum(kpl) muutoskpl
				 			FROM tapahtuma use index (yhtio_tuote_laadittu)
							JOIN varastopaikat ON (varastopaikat.yhtio = tapahtuma.yhtio
													and concat(rpad(upper(alkuhyllyalue), 5, '0'), lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
													and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tapahtuma.hyllyalue), 5, '0'), lpad(upper(tapahtuma.hyllynro), 5, '0'))
													and varastopaikat.tunnus = '$row[varastotunnus]')
				 			WHERE tapahtuma.yhtio = '$kukarow[yhtio]'
				 			and tapahtuma.tuoteno = '$row[tuoteno]'
				 			and tapahtuma.laadittu > '$vv-$kk-$pp 23:59:59'
							$summaus_lisa";
				$muutosres = mysql_query($query) or pupe_error($query);
				$muutosrow = mysql_fetch_array($muutosres);

				// saldo historiassa: lasketaan nykyiset kpl - muutoskpl
				$muutoskpl = $kpl - $muutosrow["muutoskpl"];

				// arvo historiassa: lasketaan nykyinen arvo - muutosarvo
				$muutoshinta = $varaston_arvo - $muutosrow["muutoshinta"];
				$bmuutoshinta = $bruttovaraston_arvo - $muutosrow["muutoshinta"];

				if($tyyppi == "C") {
					$ok = "GO";
				}
				elseif ($tyyppi == "A" and $muutoskpl != 0) {
					$ok = "GO";
				}
				elseif ($tyyppi == "B" and $muutoskpl == 0) {
					$ok = "GO";
				}
				elseif ($tyyppi == "D" and $muutoskpl < 0) {
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
					// summataan varastonarvoa
					$varvo += $muutoshinta;
					$bvarvo += $bmuutoshinta;
					$lask++;

					// yritet��n kaivaa listaan viel� sen hetkinen kehahin jos se halutaan kerran n�hd�
					$kehasilloin = $row["kehahin_nyt"];		// nykyinen kehahin
					$bkehasilloin = $row["kehahin"];		// brutto kehahin

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
						$kehasilloin  = $arow["hinta"];
						$bkehasilloin = $arow["hinta"];
						$kehalisa = "";
					}
					else {
						// ei l�ydetty alasp�in, kokeillaan kattoo l�hin hinta yl�sp�in
						$query = "	SELECT hinta
									FROM tapahtuma use index (yhtio_tuote_laadittu)
									WHERE yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and laadittu > '$vv-$kk-$pp 23:59:59'
									and hinta <> 0
									ORDER BY laadittu
									LIMIT 1";
						$ares = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($ares) == 1) {
							// l�ydettiin keskihankintahinta tapahtumista k�ytet��n
							$arow = mysql_fetch_array($ares);
							$kehasilloin  = $arow["hinta"];
							$bkehasilloin = $arow["hinta"];
							$kehalisa = "";
						}
						else {
							$kehalisa = "~";
						}
					}

					// jos summaustaso on per paikka, otetaan myynti ja kulutus vain silt� paikalta
					if ($summaustaso == "P") {
						$summaus_lisa = "and tilausrivi.hyllyalue = '$row[hyllyalue]'
											and tilausrivi.hyllynro = '$row[hyllynro]'
											and tilausrivi.hyllyvali = '$row[hyllyvali]'
											and tilausrivi.hyllytaso = '$row[hyllytaso]'";
					}
					else {
						$summaus_lisa = "";
					}

					// haetaan tuotteen myydyt kappaleet
					$query  = "	SELECT ifnull(sum(tilausrivi.kpl),0) kpl, max(tilausrivi.laskutettuaika) laskutettuaika
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
														and concat(rpad(upper(alkuhyllyalue), 5, '0'), lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
														and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
														and varastopaikat.tunnus = '$row[varastotunnus]')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi = 'L'
								and tilausrivi.tuoteno = '$row[tuoteno]'
								and tilausrivi.laskutettuaika <= '$vv-$kk-$pp'
								and tilausrivi.laskutettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)
								$summaus_lisa";
					$xmyyres = mysql_query($query) or pupe_error($query);
					$xmyyrow = mysql_fetch_array($xmyyres);

					// haetaan tuotteen kulutetut kappaleet
					$query  = "	SELECT ifnull(sum(tilausrivi.kpl),0) kpl
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio
														and concat(rpad(upper(alkuhyllyalue), 5, '0'), lpad(upper(alkuhyllynro), 5, '0')) <= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
														and concat(rpad(upper(loppuhyllyalue), 5, '0'), lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tilausrivi.hyllyalue), 5, '0'), lpad(upper(tilausrivi.hyllynro), 5, '0'))
														and varastopaikat.tunnus = '$row[varastotunnus]')
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi = 'V'
								and tilausrivi.tuoteno = '$row[tuoteno]'
								and tilausrivi.toimitettuaika <= '$vv-$kk-$pp'
								and tilausrivi.toimitettuaika >= date_sub('$vv-$kk-$pp', INTERVAL 12 month)
								$summaus_lisa";
					$xkulres = mysql_query($query) or pupe_error($query);
					$xkulrow = mysql_fetch_array($xkulres);

					// lasketaan varaston kiertonopeus
					if ($muutoskpl > 0) {
						$kierto = round(($xmyyrow["kpl"] + $xkulrow["kpl"]) / $muutoskpl, 2);
					}
					else {
						$kierto = 0;
					}

					if (isset($workbook)) {

						$worksheet->writeString($excelrivi, $excelsarake, $row["varastonnimi"], 	$format_bold);
						$excelsarake++;

						if ($summaustaso == "P") {
							$worksheet->writeString($excelrivi, $excelsarake, $row["hyllyalue"], 		$format_bold);
							$excelsarake++;
							$worksheet->writeString($excelrivi, $excelsarake, $row["hyllynro"], 		$format_bold);
							$excelsarake++;
							$worksheet->writeString($excelrivi, $excelsarake, $row["hyllyvali"], 		$format_bold);
							$excelsarake++;
							$worksheet->writeString($excelrivi, $excelsarake, $row["hyllytaso"], 		$format_bold);
							$excelsarake++;
						}

						if ($sel_tuotemerkki != '') {
							$worksheet->writeString($excelrivi, $excelsarake, $row["tuotemerkki"]);
							$excelsarake++;
						}

						$worksheet->writeString($excelrivi, $excelsarake, $row["osasto"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["try"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, $row["tuoteno"]);
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, asana('nimitys_',$row['tuoteno'],$row['nimitys']));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$muutoskpl));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$kehasilloin));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$muutoshinta));
						$excelsarake++;
						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.06f",$bmuutoshinta));
						$excelsarake++;

						$worksheet->writeNumber($excelrivi, $excelsarake, sprintf("%.02f",$kierto));
						$excelsarake++;
						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($xmyyrow["laskutettuaika"]));
						$excelsarake++;

						if ($row['epakurantti25pvm'] != '0000-00-00') {
							$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti25pvm']));
						}
						$excelsarake++;
						if ($row['epakurantti50pvm'] != '0000-00-00') {
							$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti50pvm']));
						}
						$excelsarake++;
						if ($row['epakurantti75pvm'] != '0000-00-00') {
							$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti75pvm']));
						}
						$excelsarake++;
						if ($row['epakurantti100pvm'] != '0000-00-00') {
							$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row['epakurantti100pvm']));
						}
						$excelsarake++;

						$worksheet->writeString($excelrivi, $excelsarake, tv1dateconv($row["vihapvm"]));
						$excelsarake++;

						$worksheet->writeString($excelrivi, $excelsarake, $kehalisa);

						$excelrivi++;
						$excelsarake = 0;
					}

					$varastot2[$row["varastonnimi"]]["netto"] += $muutoshinta;
					$varastot2[$row["varastonnimi"]]["brutto"] += $bmuutoshinta;
				}
			}

			echo "<br>";
			echo "<table>";
			echo "<tr><th>".t("Varasto")."</th><th>".t("Varastonarvo")."</th><th>".t("Bruttovarastonarvo")."</th></tr>";

			ksort($varastot2);

			foreach ($varastot2 AS $varasto => $arvot) {
				echo "<tr><td>$varasto</td>";
				foreach ($arvot AS $arvo) {
					if ($arvo != '') {
						echo "<td align='right'>".sprintf("%.2f",$arvo)."</td>";
					}
					else {
						echo "<td>&nbsp;</td>";
					}
				}
				echo "</tr>";
			}

			echo "<tr><th>".t("Pvm")."</th><th colspan='2'>".t("Yhteens�")."</th></tr>";
			echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td>";
			echo "<td align='right'>".sprintf("%.2f",$bvarvo)."</td></tr>";
			echo "</table><br>";
		}

		if(isset($workbook)) {
			$workbook->close();
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
			echo "<input type='hidden' name='kaunisnimi' value='Varastonarvo.xls'>";
			echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
			echo "<table>";
			echo "<tr><th>".t("Tallenna Excel-aineisto").":</th>";
			echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr>";
			echo "</table><br>";
			echo "</form>";
		}

		// piirrell��n formi
		echo "<form action='$PHP_SELF' name='formi' method='post' autocomplete='OFF'>";
		echo "<input type='hidden' name='supertee' value='RAPORTOI'>";

		echo "<table><tr valign='top'><td><table><tr><td class='back'>";

		// n�ytet��n soveltuvat osastot
		// tehd��n avainsana query
		$res2 = avainsana("OSASTO", $kukarow['kieli']);

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
		// tehd��n avainsana query
		$res2 = avainsana("TRY", $kukarow['kieli']);

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
		$query = "SELECT distinct tuotemerkki FROM tuote use index (yhtio_tuotemerkki) WHERE yhtio = '$kukarow[yhtio]' and tuotemerkki != '' ORDER BY tuotemerkki";
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


		if ($osasto != "") {
			$rukOchk = "CHECKED";
		}
		else {
			$rukOchk = "";
		}
		if ($tuoteryhma != "") {
			$rukTchk = "CHECKED";
		}
		else {
			$rukTchk = "";
		}

		echo "<br><table>
			<tr>
			<th>".t("Listaa vain tuotteet, jotka ei kuulu mihink��n osastoon")."</th>
			<td><input type='checkbox' name='osasto' value='tyhjat' $rukOchk></td>
			</tr>
			<tr>
			<th>".t("Listaa vain tuotteet, jotka ei kuulu mihink��n tuoteryhm��n")."</th>
			<td><input type='checkbox' name='tuoteryhma' value='tyhjat' $rukTchk></td>
			</tr></table>";

		echo "<br><table>";

		$epakur_chk1 = "";
		$epakur_chk2 = "";
		$epakur_chk3 = "";

		if ($epakur == 'kaikki') {
			$epakur_chk1 = ' selected';
		}
		elseif ($epakur == 'epakur') {
			$epakur_chk2 = ' selected';
		}
		elseif ($epakur == 'ei_epakur') {
			$epakur_chk3 = ' selected';
		}

		echo "<tr>";
		echo "<th rowspan='2' valign=top>",t("Tuoterajaus"),":</th><td>";
		echo "<select name='epakur'>";
		echo "<option value='kaikki'$epakur_chk1>",t("N�yt� kaikki tuotteet"),"</option>";
		echo "<option value='epakur'$epakur_chk2>",t("N�yt� vain ep�kurantit tuotteet"),"</option>";
		echo "<option value='ei_epakur'$epakur_chk3>",t("N�yt� varastonarvoon vaikuttavat tuotteet"),"</option>";
		echo "</select>";
		echo "</td></tr>";

		echo "<tr>";

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
		elseif($tyyppi == "D") {
			$sel4 = "SELECTED";
		}

		echo "<td>
				<select name='tyyppi'>
				<option value='A' $sel1>".t("N�ytet��n tuotteet joilla on saldoa")."</option>
				<option value='B' $sel2>".t("N�ytet��n tuotteet joilla ei ole saldoa")."</option>
				<option value='C' $sel3>".t("N�ytet��n kaikki tuotteet")."</option>
				<option value='D' $sel4>".t("N�ytet��n miinus-saldolliset tuotteet")."</option>
				</select>
				</td>";
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
				<option value='S' $sel1>".t("Varastonarvo varastoittain")."</option>
				<option value='P' $sel2>".t("Varastonarvo varastopaikoittain")."</option>
				</select>
				</td>";
		echo "</tr>";

		$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$vares = mysql_query($query) or pupe_error($query);

		echo "<tr>
				<th valign=top>".t('Valitse varastot').":</th>
		    <td>";

		$varastot = (isset($_POST['varastot']) && is_array($_POST['varastot'])) ? $_POST['varastot'] : array();

        while ($varow = mysql_fetch_array($vares)) {
			$sel = '';
			if (in_array($varow['tunnus'], $varastot)) {
				$sel = 'checked';
			}

			echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' $sel/>{$varow['nimitys']}<br />\n";
		}

		echo "</td><td class='back' valign='top'>".t('Saat kaikki varastot jos et valitse mit��n').".</td></tr>";

		echo "<tr>";
		echo "<th>Sy�t� vvvv-kk-pp:</th>";
		echo "<td><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th valign='top'>Varastonarvorajaus:</th>";
		echo "<td>Alaraja: <input type='text' name='alaraja' size='7' value='$alaraja'><br>Yl�raja: <input type='text' name='ylaraja' size='7' value='$ylaraja'></td>";
		echo "</tr>";

		echo "<tr><th valign='top'>".t("Tuotelista")."</th><td><textarea name='tuotteet_lista' rows='5' cols='35'>$tuotteet_lista</textarea></td></tr>";

		echo "</table>";
		echo "<br>";

		if($valitaan_useita == '') {
			echo "<input type='submit' value='Laske varastonarvot'>";
		}
		else {
			echo "<input type='submit' name='valitaan_useita' value='Laske varastonarvot'>";
		}

		echo "</form>";

		require ("../inc/footer.inc");
	}
?>
