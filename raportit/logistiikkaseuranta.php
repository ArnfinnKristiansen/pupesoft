<?php

	require ("../inc/parametrit.inc");

	echo "<font class='head'>".t("Logistiikkaseuranta")."</font><hr>";

	$query = "	SELECT tunnus, nimi, toimitustapa, tila, alatila, tilaustyyppi, toimitustavan_lahto, varasto
				FROM lasku
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tila 	= 'L'
				AND alatila in ('A','B','C','D','X')
				and luontiaika >= '2012-04-01 00:00:00'
				and luontiaika <= '2012-04-03 23:59:59'
				ORDER BY tunnus";
	$lasku_res = pupe_query($query);

	$kala = 1;

	echo "<table>";

	while ($laskurow = mysql_fetch_assoc($lasku_res)) {

		$laskutyyppi = $laskurow["tila"];
		$alatila	 = $laskurow["alatila"];

		//tehd��n selv�kielinen tila/alatila
		require "inc/laskutyyppi.inc";

		$tarkenne = " ";

		if ($laskurow["tila"] == "V" and $laskurow["tilaustyyppi"] == "V") {
			$tarkenne = " (".t("Asiakkaalle").") ";
		}
		elseif ($laskurow["tila"] == "V" and  $laskurow["tilaustyyppi"] == "W") {
			$tarkenne = " (".t("Varastoon").") ";
		}
		elseif(($laskurow["tila"] == "N" or $laskurow["tila"] == "L") and $laskurow["tilaustyyppi"] == "R") {
			$tarkenne = " (".t("Reklamaatio").") ";
		}
		elseif(($laskurow["tila"] == "N" or $laskurow["tila"] == "L") and $laskurow["tilaustyyppi"] == "A") {
			$laskutyyppi = "Ty�m��r�ys";
		}
		elseif($laskurow["tila"] == "N" and $laskurow["tilaustyyppi"] == "E") {
			$laskutyyppi = "Ennakkotilaus kesken";
		}

		$rivi 		= "";
		$naytarivi  = FALSE;

		// Tilauksen tiedot
		$rivi .= "<tr>";
		$rivi .= "<td class='spec'>$kala / <a target='Asiakkaantilaukset' href='asiakkaantilaukset.php?tee=NAYTATILAUS&toim=MYYNTI&tunnus=$laskurow[tunnus]'>$laskurow[tunnus]</a></td>";
		$rivi .= "<td class='spec'>$laskurow[nimi]</td>";
		$rivi .= "<td class='spec'>$laskurow[toimitustapa] / $laskurow[toimitustavan_lahto]</td>";
		$rivi .= "<td class='spec'>".t("$laskutyyppi")."$tarkenne".t("$alatila")."</td>";
		$rivi .= "</tr>";

		// Tilausrivit
		$query = "	SELECT tilausrivi.*, keraysvyohyke.nimitys kervyohyke
					FROM tilausrivi
					JOIN tuote ON (tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno and tuote.ei_saldoa ='')
					JOIN tilausrivin_lisatiedot ON (tilausrivi.yhtio = tilausrivin_lisatiedot.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus and tilausrivin_lisatiedot.ohita_kerays = '')
					LEFT JOIN varaston_hyllypaikat ON (varaston_hyllypaikat.yhtio = tilausrivi.yhtio AND varaston_hyllypaikat.hyllyalue = tilausrivi.hyllyalue AND varaston_hyllypaikat.hyllynro = tilausrivi.hyllynro AND varaston_hyllypaikat.hyllyvali = tilausrivi.hyllyvali AND varaston_hyllypaikat.hyllytaso = tilausrivi.hyllytaso)
					LEFT JOIN keraysvyohyke ON (varaston_hyllypaikat.yhtio = keraysvyohyke.yhtio AND varaston_hyllypaikat.keraysvyohyke = keraysvyohyke.tunnus)
					WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
					and tilausrivi.otunnus = '{$laskurow['tunnus']}'
					and (tilausrivi.varattu+tilausrivi.kpl) > 0
					ORDER BY tilausrivi.tunnus";
		$tilausrivi_res = pupe_query($query);

		$rivi .= "<tr>";
		$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
		$rivi .= "<table style='width:100%; height:100%;'>";

		$rivi .= "<tr>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tuote</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Nimitys</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tilkpl</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kpl</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Var</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker�yser�</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Vy�hyke</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Tilrivi laadittu</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� laadittu</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker��j�</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Ker�ttyaika</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� kpl</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kerer� ker�tty</th>";
		$rivi .= "</tr>";

		$ohita_kerays = array();

		while ($tilausrivirow = mysql_fetch_assoc($tilausrivi_res)) {

			// Kerayser�/er�t
			$query = "	SELECT group_concat(luontiaika) luontiaika,
						group_concat(nro) nro,
						group_concat(pakkausnro) pakkausnro,
						round(sum(kpl)) kpl,
						round(sum(kpl_keratty)) kpl_keratty
						FROM kerayserat
						WHERE yhtio 	= '{$kukarow['yhtio']}'
						and otunnus 	= '{$tilausrivirow['otunnus']}'
						and tilausrivi 	= '{$tilausrivirow['tunnus']}'";
			$keraysera_res = pupe_query($query);
			$kerayserarow = mysql_fetch_assoc($keraysera_res);

			$pakkaus_kirjain = ($kerayserarow["pakkausnro"] > 0) ? chr(64+$kerayserarow["pakkausnro"]) : "";

			// Tsekataan viel� ohita_kerays jutut!
			if ($tilausrivirow['perheid'] > 0 and $tilausrivirow['tunnus'] != $tilausrivirow['perheid']) {

				// haetaan is�
				$query_isa = " SELECT tuoteno
							   FROM tilausrivi
							   WHERE yhtio = '{$kukarow['yhtio']}'
							   AND tunnus  = '{$tilausrivirow['perheid']}'
							   AND perheid = '{$tilausrivirow['perheid']}'";
				$isa_chk_res = pupe_query($query_isa);
				$isa_chk_row = mysql_fetch_assoc($isa_chk_res);

				$query_ohita = " SELECT ohita_kerays
								 FROM tuoteperhe
								 WHERE yhtio 	= '{$kukarow['yhtio']}'
								 AND tyyppi 	= 'P'
								 AND isatuoteno = '{$isa_chk_row['tuoteno']}'
								 AND tuoteno 	= '{$tilausrivirow['tuoteno']}'";
				$ohita_chk_res = pupe_query($query_ohita);
				$ohita_chk_row = mysql_fetch_assoc($ohita_chk_res);

				if ($ohita_chk_row['ohita_kerays'] != '') {
					$ohita_kerays[$tilausrivirow['tunnus']] = TRUE;
				}
			}

			$kereraekotus = (isset($ohita_kerays[$tilausrivirow['tunnus']])) ? "KER�YS OHITETEAAN" : $kerayserarow["nro"];

			$rivi .= "<tr>";
			$rivi .= "<td><a target='Tuotekysely' href='{$palvelin2}tuote.php?tee=Z&tuoteno=".urlencode($tilausrivirow["tuoteno"])."'>$tilausrivirow[tuoteno]</a></td>";
			$rivi .= "<td>".substr($tilausrivirow["nimitys"],0,20)."</td>";
			$rivi .= "<td align='right'>".(float) ($tilausrivirow["tilkpl"])."</td>";
			$rivi .= "<td align='right'>".(float) ($tilausrivirow["varattu"]+$tilausrivirow["kpl"])."</td>";
			$rivi .= "<td>$tilausrivirow[var]</td>";
			$rivi .= "<td>$kereraekotus</td>";
			$rivi .= "<td>$pakkaus_kirjain</td>";
			$rivi .= "<td>$tilausrivirow[kervyohyke]</td>";
			$rivi .= "<td>".tv1dateconv($tilausrivirow["laadittu"], "PITKA", "LYHYT")."</td>";
			$rivi .= "<td>".tv1dateconv($kerayserarow["luontiaika"], "PITKA", "LYHYT")."</td>";
			$rivi .= "<td>$tilausrivirow[keratty]</td>";
			$rivi .= "<td>".tv1dateconv($tilausrivirow["kerattyaika"], "PITKA", "LYHYT")."</td>";
			$rivi .= "<td align='right'>$kerayserarow[kpl]</td>";
			$rivi .= "<td align='right'>$kerayserarow[kpl_keratty]</td>";
			$rivi .= "</tr>";

			if ($tilausrivirow["kpl"] != 0 and $tilausrivirow["keratty"] == "" and !isset($ohita_kerays[$tilausrivirow['tunnus']])) {
				$naytarivi = TRUE;
			}
		}

		$rivi .= "</table>";
		$rivi .= "</td>";
		$rivi .= "</tr>";

		// Rahtikirjan  tiedot kannasta
		$rivi .= "<tr>";
		$rivi .= "<td class='back' colspan='4' style='padding:0px; margin:0px;'>";
		$rivi .= "<table style='width:100%; height:100%;'>";

		$rivi .= "<tr>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Rahtikirja</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kollit</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kilot</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kuutiot</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
		$rivi .= "</tr>";

		$query = "	SELECT rahtikirjanro, kollit, round(kilot,2) kilot, kuutiot, pakkaus
					FROM rahtikirjat
					WHERE yhtio	= '{$kukarow['yhtio']}'
					and otsikkonro = '{$laskurow['tunnus']}'";
		$rakir_res = pupe_query($query);

		while ($rakirrow = mysql_fetch_assoc($rakir_res)) {
			$rivi .= "<tr>";
			$rivi .= "<td>$rakirrow[rahtikirjanro]</td>";
			$rivi .= "<td>$rakirrow[kollit]</td>";
			$rivi .= "<td>$rakirrow[kilot]</td>";
			$rivi .= "<td>$rakirrow[kuutiot]</td>";
			$rivi .= "<td>$rakirrow[pakkaus]</td>";
			$rivi .= "</tr>";
		}

		/*
		// Rahtikirjan  tiedot, nii ku ne pit�is olla
		$rivi .= "<tr>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Rahtikirja</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kollit</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kilot</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Kuutiot</th>";
		$rivi .= "<th style='font-size:10px; padding:1px; margin:0px;'>Pakkaus</th>";
		$rivi .= "</tr>";

		$query = "	SELECT
					kerayserat.nro,
					IFNULL(pakkaus.pakkaus, 'MUU KOLLI') pakkaus,
					IFNULL(pakkaus.pakkauskuvaus, 'MUU KOLLI') pakkauskuvaus,
					IFNULL(pakkaus.oma_paino, 0) oma_paino,
					IF(pakkaus.puukotuskerroin is not null and pakkaus.puukotuskerroin > 0, pakkaus.puukotuskerroin, 1) puukotuskerroin,
					SUM(tuote.tuotemassa * kerayserat.kpl_keratty) tuotemassa,
					SUM(tuote.tuoteleveys * tuote.tuotekorkeus * tuote.tuotesyvyys * kerayserat.kpl_keratty) as kuutiot,
					COUNT(distinct kerayserat.pakkausnro) AS kollit
					FROM kerayserat
					LEFT JOIN pakkaus ON (pakkaus.yhtio = kerayserat.yhtio AND pakkaus.tunnus = kerayserat.pakkaus)
					JOIN tilausrivi ON (tilausrivi.yhtio = kerayserat.yhtio AND tilausrivi.tunnus = kerayserat.tilausrivi)
					JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
					WHERE kerayserat.yhtio 	= '{$kukarow['yhtio']}'
					AND kerayserat.otunnus 	= '{$laskurow['tunnus']}'
					GROUP BY 1,2,3,4
					ORDER BY kerayserat.pakkausnro";
		$rakir_res = pupe_query($query);

		while ($rakirrow = mysql_fetch_assoc($rakir_res)) {

			$kilot = round($rakirrow["tuotemassa"] + $rakirrow["oma_paino"], 2);
			$kuutiot = round($rakirrow["kuutiot"] * $rakirrow["puukotuskerroin"], 4);

			$rivi .= "<tr>";
			$rivi .= "<td>{$laskurow['tunnus']}</td>";
			$rivi .= "<td>$rakirrow[kollit]</td>";
			$rivi .= "<td>$kilot</td>";
			$rivi .= "<td>$kuutiot</td>";
			$rivi .= "<td>$rakirrow[pakkaus]</td>";
			$rivi .= "</tr>";
		}

		*/

		$rivi .= "</table>";
		$rivi .= "</td>";
		$rivi .= "</tr>";

		// V�lirivi
		$rivi .= "<tr>";
		$rivi .= "<td class='back' colspan='4' style='height:5px;'></td>";
		$rivi .= "</tr>";

		if ($naytarivi) {
			echo "$rivi";

			$kala++;
		}
	}

	echo "</table>";

	require ("inc/footer.inc");
?>