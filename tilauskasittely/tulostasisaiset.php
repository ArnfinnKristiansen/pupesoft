<?php

	require('../inc/parametrit.inc');

 	echo "<font class='head'>".t("Tulosta sis�isi� laskuja").":</font><hr><br>";

	if ($tee == 'TULOSTA') {

		//valitaan tulostin
		$tulostimet[0] = 'Lasku';

		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}

		if ($tila == 'yksi') {
			if ($laskunro != '') {
				$where = " and laskunro='$laskunro' ";
			}
		}
		elseif ($tila == 'monta') {
			if ($vva != '' and $vvl != '' and $kka != '' and $kkl != '') {
				$where = "	and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";
			}
		}
		else {
			echo t("Ilman hakukriteerej� ei voida jatkaa")."!";
			exit;
		}


		if ($where == '') {
			echo t("Et sy�tt�nyt mit��n j�rkev��")."!<br>";
			exit;
		}

		if ($raportti == "k") {
			$where .= " and vienti!='' ";
		}
		else {
			$where .= " and vienti='' ";
		}

		//hateaan laskun kaikki tiedot
		$query = "	SELECT *
					FROM lasku
					WHERE tila    = 'U'
					and alatila   = 'X'
					and sisainen != ''
					$where
					and yhtio ='$kukarow[yhtio]'
					ORDER BY laskunro";
		$laskurrrresult = mysql_query ($query) or die ("".t("Kysely ei onnistu")." $query");

		while ($laskurow = mysql_fetch_array($laskurrrresult)) {

			$otunnus = $laskurow["tunnus"];

			// haetaan maksuehdon tiedot
			$query  = "	SELECT pankkiyhteystiedot.*, maksuehto.*
						from maksuehto
						left join pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
						where maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$masrow = array();
				if ($laskurow["erpcm"] == "0000-00-00") {
					echo "<font class='error'>".t("VIRHE: Maksuehtoa ei l�ydy")."! $laskurow[maksuehto]!</font>";
				}
			}
			else {
				$masrow = mysql_fetch_array($result);
			}

			//maksuehto tekstin�
			$maksuehto      = t_tunnus_avainsanat($masrow, "teksti", "MAKSUEHTOKV", $kieli);
			$kateistyyppi   = $masrow["kateinen"];

			if ($yhtiorow['laskutyyppi'] == 3) {
				require_once ("tulosta_lasku_simppeli.inc");
				tulosta_lasku($otunnus, $komento["Lasku"], $kieli, $toim, $tee);
				$tee = '';
			}
			else {
				require_once("tulosta_lasku.inc");

				if ($laskurow["tila"] == 'U') {
					$where = " uusiotunnus='$otunnus' ";
				}
				else {
					$where = " otunnus='$otunnus' ";
				}

				// katotaan miten halutaan sortattavan
				// haetaan asiakkaan tietojen takaa sorttaustiedot
				$order_sorttaus = '';

				$asiakas_apu_query = "	SELECT laskun_jarjestys, laskun_jarjestys_suunta
										FROM asiakas
										WHERE yhtio='$kukarow[yhtio]'
										and tunnus='$laskurow[liitostunnus]'";
				$asiakas_apu_res = mysql_query($asiakas_apu_query) or pupe_error($asiakas_apu_query);

				if (mysql_num_rows($asiakas_apu_res) == 1) {
					$asiakas_apu_row = mysql_fetch_array($asiakas_apu_res);
					$sorttauskentta = generoi_sorttauskentta($asiakas_apu_row["laskun_jarjestys"]);
					$order_sorttaus = $asiakas_apu_row["laskun_jarjestys_suunta"];
				}
				else {
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);
					$order_sorttaus = $yhtiorow["laskun_jarjestys_suunta"];
				}

				if ($yhtiorow["laskun_palvelutjatuottet"] == "E") $pjat_sortlisa = "tuotetyyppi,";
				else $pjat_sortlisa = "";

				// haetaan tilauksen kaikki rivit
				$query = "	SELECT tilausrivi.*, tilausrivin_lisatiedot.osto_vai_hyvitys,
							$sorttauskentta,
							if (tuote.tuotetyyppi='K','2 Ty�t','1 Muut') tuotetyyppi
							FROM tilausrivi
							JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
							LEFT JOIN tilausrivin_lisatiedot ON tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus
							WHERE $where
							and tilausrivi.yhtio	= '$kukarow[yhtio]'
							and tilausrivi.tyyppi	= 'L'
							and (tilausrivi.perheid = 0 or tilausrivi.perheid=tilausrivi.tunnus or tilausrivin_lisatiedot.ei_nayteta !='E' or tilausrivin_lisatiedot.ei_nayteta is null)
							and tilausrivi.kpl != 0
							ORDER BY tilausrivi.otunnus, $pjat_sortlisa sorttauskentta $order_sorttaus, tilausrivi.tunnus";
				$result = mysql_query($query) or pupe_error($query);

				//kuollaan jos yht��n rivi� ei l�ydy
				if (mysql_num_rows($result) == 0) {
					echo t("Laskurivej� ei l�ytynyt");
					exit;
				}

				$sivu 	= 1;
				$summa 	= 0;
				$arvo 	= 0;

				// aloitellaan laskun teko
				$page[$sivu] = alku();

				while ($row = mysql_fetch_array($result)) {
					// Rivin toimitusaika
					if ($yhtiorow["tilausrivien_toimitettuaika"] == 'K' and $row["keratty"] == "saldoton") {
						$row["toimitettuaika"] = $row["toimaika"];
					}
					elseif ($yhtiorow["tilausrivien_toimitettuaika"] == 'X') {
						$row["toimitettuaika"] = $row["toimaika"];
					}
					else {
						$row["toimitettuaika"] = $row["toimitettuaika"];
					}

					rivi($page[$sivu]);
				}

				alvierittely($page[$sivu]);

				$query = "	SELECT kassa_alepros
							FROM maksuehto
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$laskurow['maksuehto']}'";
				$maksuehtores = mysql_query($query) or pupe_error($query);
				$maksuehtorow = mysql_fetch_assoc($maksuehtores);

				if ($maksuehtorow['kassa_alepros'] > 0) {
					alvierittely($page[$sivu], $maksuehtorow['kassa_alepros']);
				}

				//keksit��n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/Lasku_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
				fclose($fh);

				// itse print komento...
				if ($komento["Lasku"] == 'email') {
					$liite = $pdffilenimi;
					$kutsu = "Lasku";

					require("../inc/sahkoposti.inc");
				}
				elseif ($komento["Lasku"] != '' and $komento["Lasku"] != 'edi') {
					$line = exec("$komento[Lasku] $pdffilenimi");
				}

				echo t("Sis�iset laskut tulostuu")."....<br>";

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");

				unset($pdf);
				unset($page);

			}
		}

		$tee = '';
		echo "<br>";
	}

	if ($tee == '') {
		//sy�tet��n tilausnumero
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";
		echo "<input type='hidden' name='tila' value='yksi'>";
		echo "<tr><th colspan='2'>".t("Laskunumero")."</th></tr>";
		echo "<tr>";
		echo "<td><input type='text' size='10' name='laskunro'></td>";
		echo "<td><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</form>";
		echo "</table><br>";

		if (!isset($kka))
			$kka = date("m");
		if (!isset($vva))
			$vva = date("Y");
		if (!isset($ppa))
			$ppa = date("d");

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";
		echo "<input type='hidden' name='tila' value='monta'>";
		echo "<tr><th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td></tr>
				<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
		echo "<tr><th>".t("Vain kotimaiset laskut")."</th><td colspan='3'><input type='radio' name='raportti' value='e' checked></td></tr>";
		echo "<tr><th>".t("Vain vientilaskut")."</th><td colspan='3'><input type='radio' name='raportti' value='k'></td></tr>";
		echo "<tr><th></th><td colspan='3'><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</table>";
		echo "</form>";

	}

?>
