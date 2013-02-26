<?php

	//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>",t("Ker�tyt rivit"),"</font><hr>";

	if (!isset($tee)) $tee = '';

	if (!isset($kka)) $kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($vva)) $vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	if (!isset($ppa)) $ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

	if (!isset($kkl)) $kkl = date("m");
	if (!isset($vvl)) $vvl = date("Y");
	if (!isset($ppl)) $ppl = date("d");

	if (!isset($tapa)) $tapa = "";
	if (!isset($eipoistettuja)) $eipoistettuja = "";
	if (!isset($varastot)) $varastot = array();

	//K�ytt�liittym�
	echo "<form method='post'>";
	echo "<table>";

	echo "<input type='hidden' name='tee' value='kaikki'>";

	$sel = array($tapa => " selected") + array("keraaja" => "", "kerpvm" => "", "kerkk" => "");

	echo "<tr>";
	echo "<th>",t("Valitse tapa"),"</th>";
	echo "<td colspan='3'>";
	echo "<select name='tapa'>";
	echo "<option value='keraaja'{$sel['keraaja']}>",t("Ker��jitt�in"),"</option>";
	echo "<option value='kerpvm'{$sel['kerpvm']}>",t("P�ivitt�in"),"</option>";
	echo "<option value='kerkk'{$sel['kerkk']}>",t("Kuukausittain"),"</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	$query  = "	SELECT tunnus, nimitys
				FROM varastopaikat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tyyppi != 'P'
				ORDER BY tyyppi, nimitys";
	$vares = pupe_query($query);

	echo "<tr>";
	echo "<th valign=top>",t('Varastot'),"<br /><br /><span style='font-size: 0.8em;'>",t('Saat kaikki varastot jos et valitse yht��n'),"</span></th>";
	echo "<td colspan='3'>";

    while ($varow = mysql_fetch_assoc($vares)) {
		$sel = in_array($varow['tunnus'], $varastot) ? 'checked' : '';

		echo "<input type='checkbox' name='varastot[]' value='{$varow['tunnus']}' {$sel}/>{$varow['nimitys']}<br />\n";
	}

	echo "</td></tr>";


	echo "<tr>";
	echo "<th>",t("Sy�t� p�iv�m��r� (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppa' value='{$ppa}' size='3'></td>";
	echo "<td><input type='text' name='kka' value='{$kka}' size='3'></td>";
	echo "<td><input type='text' name='vva' value='{$vva}' size='5'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>",t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)"),"</th>";
	echo "<td><input type='text' name='ppl' value='{$ppl}' size='3'></td>";
	echo "<td><input type='text' name='kkl' value='{$kkl}' size='3'></td>";
	echo "<td><input type='text' name='vvl' value='{$vvl}' size='5'></td>";
	echo "</tr>";

	$chk = $eipoistettuja != '' ? " checked" : "";

	echo "<tr>";
	echo "<th>",t("�l� n�yt� poistettujen k�ytt�jien rivej�"),"</th>";
	echo "<td colspan='3'><input type='checkbox' name='eipoistettuja'{$chk}></td>";
	echo "</tr>";

	echo "<tr><td colspan='4' class='back'></td></tr>";
	echo "<tr><td colspan='4' class='back'><input type='submit' value='",t("Aja raportti"),"'></td></tr>";
	echo "</table>";
	echo "</form>";

	echo "<br /><br />";

	if ($tee != '') {

		if ($eipoistettuja != "") {
			$lefti = "";
		}
		else {
			$lefti = "LEFT";
		}

		if (count($varastot) > 0) {
			$lisa = " and varastopaikat.tunnus IN (".implode(', ', $varastot).")";
        }
		else {
			$lisa = "";
		}

		if ($tapa == 'keraaja') {

			$query = "	SELECT tilausrivi.keratty,
						tilausrivi.otunnus,
						tilausrivi.kerattyaika,
						lasku.lahetepvm,
						kuka.nimi,
						kuka.keraajanro,
						SEC_TO_TIME(UNIX_TIMESTAMP(kerattyaika) - UNIX_TIMESTAMP(lahetepvm)) aika,
						SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi = 'L', 1, 0)) kappaleet,
						SUM(IF(tilausrivi.var != 'P' AND tilausrivi.tyyppi = 'G', 1, 0)) siirrot,
						ROUND(SUM(IF(tilausrivi.var != 'P', tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl, 0)), 2) kerkappaleet,
						ROUND(SUM(IF(tilausrivi.var != 'P', (tilausrivi.jt + tilausrivi.varattu + tilausrivi.kpl) * tuote.tuotemassa, 0)), 2) kerkilot,
						COUNT(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN lasku USE INDEX (PRIMARY) ON (lasku.yhtio = tilausrivi.yhtio AND lasku.tunnus = tilausrivi.otunnus AND lasku.eilahetetta = '' AND lasku.sisainen = '')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
		                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
		                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tilausrivi.var IN ('','H','P')
						AND tilausrivi.tyyppi IN ('L','G')
						AND tilausrivi.keratty != ''
						{$lisa}
						GROUP BY tilausrivi.keratty, tilausrivi.otunnus
						ORDER BY tilausrivi.keratty, tilausrivi.kerattyaika";
			$result = pupe_query($query);

			echo "<table>";
			echo "<tr>";
			echo "<th nowrap>",t("Nimi"),"</th>";
			echo "<th nowrap>",t("Ker��j�nro"),"</th>";
			echo "<th nowrap>",t("Tilaus"),"</th>";
			echo "<th nowrap>",t("L�hete tulostettu"),"</th>";
			echo "<th nowrap>",t("Tilaus ker�tty"),"</th>";
			echo "<th nowrap>",t("K�ytetty aika"),"</th>";
			echo "<th norwap>",t("Puuterivit"),"</th>";
			echo "<th norwap>",t("Siirrot"),"</th>";
			echo "<th nowrap>",t("Ker�tyt"),"</th>";
			echo "<th nowrap>",t("Yhteens�"),"</th>";
			echo "<th nowrap>",t("Kappaleet"),"<br />",t("Yhteens�"),"</th>";
			echo "<th nowrap>",t("Kilot"),"<br />",t("Yhteens�"),"</th>";
			echo "</tr>";

			$lask		= 0;
			$edkeraaja	= 'EKADUUD';
			$psummayht	= 0;
			$ksummayht	= 0;
			$ssummayht	= 0;
			$summayht	= 0;
			$psumma	= 0;
			$ksumma	= 0;
			$ssumma	= 0;
			$summa	= 0;
			$kapsu	= 0;
			$kilsu	= 0;
			$kapsuyht = 0;
			$kilsuyht = 0;

			while ($row = mysql_fetch_assoc($result)) {

				if ($edkeraaja != $row["keratty"] and $summa > 0 and $edkeraaja != "EKADUUD") {
					echo "<tr>";
					echo "<th colspan='6'>",t("Yhteens�"),":</th>";
					echo "<td class='tumma' align='right'>{$psumma}</td>";
					echo "<td class='tumma' align='right'>{$ssumma}</td>";
					echo "<td class='tumma' align='right'>{$ksumma}</td>";
					echo "<td class='tumma' align='right'>{$summa}</td>";
					echo "<td class='tumma' align='right'>{$kapsu}</td>";
					echo "<td class='tumma' align='right'>{$kilsu}</td>";
					echo "</tr>";
					echo "<tr><td class='back'><br /></td></tr>";

					echo "<tr>";
					echo "<th nowrap>",t("Nimi"),"</th>";
					echo "<th nowrap>",t("Ker��j�nro"),"</th>";
					echo "<th nowrap>",t("Tilaus"),"</th>";
					echo "<th nowrap>",t("L�hete tulostettu"),"</th>";
					echo "<th nowrap>",t("Tilaus ker�tty"),"</th>";
					echo "<th nowrap>",t("K�ytetty aika"),"</th>";
					echo "<th norwap>",t("Puuterivit"),"</th>";
					echo "<th norwap>",t("Siirrot"),"</th>";
					echo "<th nowrap>",t("Ker�tyt"),"</th>";
					echo "<th nowrap>",t("Yhteens�"),"</th>";
					echo "<th nowrap>",t("Yhteens�"),"<br />",t("kappaleet"),"</th>";
					echo "<th nowrap>",t("Yhteens�"),"<br />",t("kilot"),"</th>";
					echo "</tr>";

					$psumma	= 0;
					$ksumma	= 0;
					$ssumma	= 0;
					$summa	= 0;
					$kapsu	= 0;
					$kilsu	= 0;
				}

				echo "<tr>";
				echo "<td>{$row['nimi']} ({$row['keratty']})</td>";
				echo "<td>{$row['keraajanro']}</td>";
				echo "<td>{$row['otunnus']}</td>";
				echo "<td>",tv1dateconv($row["lahetepvm"], "P"),"</td>";
				echo "<td>",tv1dateconv($row["kerattyaika"], "P"),"</td>";
				echo "<td>{$row['aika']}</td>";
				echo "<td align='right'>{$row['puutteet']}</td>";
				echo "<td align='right'>{$row['siirrot']}</td>";
				echo "<td align='right'>{$row['kappaleet']}</td>";
				echo "<td align='right'>{$row['yht']}</td>";
				echo "<td align='right'>{$row['kerkappaleet']}</td>";
				echo "<td align='right'>{$row['kerkilot']}</td>";
				echo "</tr>";

				$psumma	+= $row["puutteet"];
				$ksumma	+= $row["kappaleet"];
				$ssumma	+= $row["siirrot"];
				$summa	+= $row["yht"];
				$kapsu	+= $row["kerkappaleet"];
				$kilsu	+= $row["kerkilot"];

				// yhteens�
				$psummayht	+= $row["puutteet"];
				$ksummayht	+= $row["kappaleet"];
				$ssummayht	+= $row["siirrot"];
				$summayht	+= $row["yht"];
				$kapsuyht	+= $row["kerkappaleet"];
				$kilsuyht	+= $row["kerkilot"];

				$lask++;
				$edkeraaja = $row["keratty"];
			}

			if ($summa > 0) {
				echo "<tr>";
				echo "<th colspan='6'>",t("Yhteens�"),":</th>";
				echo "<td class='tumma' align='right'>{$psumma}</td>";
				echo "<td class='tumma' align='right'>{$ssumma}</td>";
				echo "<td class='tumma' align='right'>{$ksumma}</td>";
				echo "<td class='tumma' align='right'>{$summa}</td>";
				echo "<td class='tumma' align='right'>{$kapsu}</td>";
				echo "<td class='tumma' align='right'>{$kilsu}</td>";
				echo "</tr>";
				echo "<tr><td class='back'><br /></td></tr>";
			}

			// Kaikki yhteens�
			echo "<tr>";
			echo "<th colspan='6'>",t("Kaikki yhteens�"),":</th>";
			echo "<td class='tumma' align='right'>{$psummayht}</td>";
			echo "<td class='tumma' align='right'>{$ssummayht}</td>";
			echo "<td class='tumma' align='right'>{$ksummayht}</td>";
			echo "<td class='tumma' align='right'>{$summayht}</td>";
			echo "<td class='tumma' align='right'>{$kapsuyht}</td>";
			echo "<td class='tumma' align='right'>{$kilsuyht}</td>";
			echo "</tr>";

			echo "</table><br />";
		}

		if (($tapa == 'kerpvm') or ($tapa == 'kerkk')) {

			$grp = $tapa == 'kerkk' ? 'left(kerattyaika, 7)' : 'pvm';

			$query = "	SELECT LEFT(tilausrivi.kerattyaika,10) pvm,
						LEFT(tilausrivi.kerattyaika,10) kerattyaika,
						SUM(IF(tilausrivi.var  = 'P', 1, 0)) puutteet,
						SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi='L', 1, 0)) kappaleet,
						SUM(IF(tilausrivi.var != 'P' and tilausrivi.tyyppi='G', 1, 0)) siirrot,
						COUNT(*) yht
						FROM tilausrivi USE INDEX (yhtio_tyyppi_kerattyaika)
						JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus AND tilausrivin_lisatiedot.ohita_kerays = '')
						JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno AND tuote.ei_saldoa = '')
						{$lefti} JOIN kuka USE INDEX (kuka_index) ON (kuka.yhtio = tilausrivi.yhtio AND kuka.kuka = tilausrivi.keratty)
						LEFT JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND
		                CONCAT(RPAD(UPPER(alkuhyllyalue),  5, '0'),LPAD(UPPER(alkuhyllynro),  5, '0')) <= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')) AND
		                CONCAT(RPAD(UPPER(loppuhyllyalue), 5, '0'),LPAD(UPPER(loppuhyllynro), 5, '0')) >= CONCAT(RPAD(UPPER(tilausrivi.hyllyalue), 5, '0'),LPAD(UPPER(tilausrivi.hyllynro), 5, '0')))
						WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
						AND tilausrivi.kerattyaika >= '{$vva}-{$kka}-{$ppa} 00:00:00'
						AND tilausrivi.kerattyaika <= '{$vvl}-{$kkl}-{$ppl} 23:59:59'
						AND tilausrivi.var IN ('','H','P')
						AND tilausrivi.tyyppi IN ('L','G')
						{$lisa}
						GROUP BY {$grp}
						ORDER BY 1";
			$result = pupe_query($query);

			echo "<table>";

			echo "<tr>";
			echo "<th>",t("Pvm"),"</th>";
			echo "<th>",t("Puutteet"),"</th>";
			echo "<th>",t("Siirrot"),"</th>";
			echo "<th>",t("Ker�tyt"),"</th>";
			echo "<th>",t("Yhteens�"),"</th>";
			echo "</tr>";

			$psummayht	= 0;
			$ksummayht	= 0;
			$ssummayht	= 0;
			$summayht	= 0;

			if ($tapa == 'kerkk') {

				while ($ressu = mysql_fetch_assoc($result)) {
					echo "<tr>";
					echo "<td align='right'>",substr($ressu['kerattyaika'], 0, 7),"</td>";
					echo "<td align='right'>{$ressu['puutteet']}</td>";
					echo "<td align='right'>{$ressu['siirrot']}</td>";
					echo "<td align='right'>{$ressu['kappaleet']}</td>";
					echo "<td align='right'>{$ressu['yht']}</td>";
					echo "</tr>";

					// yhteens�
					$psummayht	+= $ressu["puutteet"];
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];
				}
			}
			else {

				while ($ressu = mysql_fetch_assoc($result)) {

					// $kerattyaika[$apu] = $ressu['kerattyaika'];

					// yhteens�
					$psummayht	+= $ressu["puutteet"];
					$ksummayht	+= $ressu["kappaleet"];
					$ssummayht	+= $ressu["siirrot"];
					$summayht	+= $ressu["yht"];

					echo "<tr>";
					echo "<td>",tv1dateconv($ressu['pvm'],"P"),"</td>";
					echo "<td align='right'>{$ressu['puutteet']}</td>";
					echo "<td align='right'>{$ressu['siirrot']}</td>";
					echo "<td align='right'>{$ressu['kappaleet']}</td>";
					echo "<td align='right'>{$ressu['yht']}</td>";
					echo "</tr>";

				}
			}

			echo "<tr>";
			echo "<th>",t("Yhteens�"),"</th>";
			echo "<td class='tumma' align='right'>{$psummayht}</td>";
			echo "<td class='tumma' align='right'>{$ssummayht}</td>";
			echo "<td class='tumma' align='right'>{$ksummayht}</td>";
			echo "<td class='tumma' align='right'>{$summayht}</td>";
			echo "</tr>";
			echo "</table><br>";
		}
	}

	require ("inc/footer.inc");
