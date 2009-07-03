<?php

	if (strpos($_SERVER['SCRIPT_NAME'], "tuote_selaus_haku.php")  !== FALSE or
		strpos($_SERVER['SCRIPT_NAME'], "verkkokauppa.php")  !== FALSE) {
		if (file_exists("../inc/parametrit.inc")) {
			require ("../inc/parametrit.inc");
			require ("../verkkokauppa/ostoskori.inc");
			$kori_polku = "../verkkokauppa/ostoskori.php";
		}
		else {
			require ("parametrit.inc");
			require ("ostoskori.inc");
			$kori_polku = "ostoskori.php";
		}
	}

	if ($toim_kutsu == "") {
		$toim_kutsu = "RIVISYOTTO";
	}


	$query    = "select * from lasku where tunnus='$kukarow[kesken]' and yhtio='$kukarow[yhtio]'";
	$result   = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($result);

	//	Hoidetaan ensin asymmentrinen tiedonsiirto!
	// Tarkistetaan tilausrivi
	if ($tee == 'TI' and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {

		if (is_numeric($ostoskori)) {
			$kori = check_ostoskori($ostoskori,$kukarow["oletus_asiakas"]);
			$kukarow["kesken"] = $kori["tunnus"];
		}

		// haetaan avoimen tilauksen otsikko
		$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);

		if (mysql_num_rows($laskures) == 0) {
			echo "<font class='error'>Sinulla ei ole avointa tilausta!</font><br>";
		}
		else {

			// tilauksen tiedot
			$laskurow = mysql_fetch_array($laskures);

			if (is_numeric($ostoskori)) {
				echo "<font class='message'>Lis�t��n tuotteita ostoskoriin $ostoskori.</font><br>";
			}
			else {
				echo "<font class='message'>Lis�t��n tuotteita tilaukselle $kukarow[kesken].</font><br>";
			}

			// K�yd��n l�pi formin kaikki rivit
			foreach ($tilkpl as $yht_i => $kpl) {

				if ((float) $kpl > 0 or ($kukarow["extranet"] == "" and (float) $kpl < 0)) {

					// haetaan tuotteen tiedot
					$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
					$tuoteres = mysql_query($query);

					if (mysql_num_rows($tuoteres) == 0) {
						echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei l�ydy!</font><br>";
					}
					else {
						// tuote l�ytyi ok, lis�t��n rivi
						$trow = mysql_fetch_array($tuoteres);

						$ytunnus         = $laskurow["ytunnus"];
						$kpl             = (float) $kpl;
						$tuoteno         = $trow["tuoteno"];
						$toimaika 	     = $laskurow["toimaika"];
						$kerayspvm	     = $laskurow["kerayspvm"];
						$hinta 		     = "";
						$netto 		     = "";
						$ale 		     = "";
						$alv		     = "";
						$var			 = "";
						$varasto 	     = $laskurow["varasto"];
						$rivitunnus		 = "";
						$korvaavakielto	 = "";
						$jtkielto 		 = $laskurow['jtkielto'];
						$varataan_saldoa = "";
						$myy_sarjatunnus = $tilsarjatunnus[$yht_i];

						if ($tilpaikka[$yht_i] != '') {
							$paikka	= $tilpaikka[$yht_i];
						}
						else {
							$paikka	= "";
						}

						//	Runkataan puute_jt_oletus
						if($verkkokauppa != "" and $yhtiorow["puute_jt_oletus"] == "") {
							$yhtiorow["puute_jt_oletus"] = "J";
						}

						// jos meill� on ostoskori muuttujassa numero, niin halutaan lis�t� tuotteita siihen ostoskoriin
						if (is_numeric($ostoskori)) {
							lisaa_ostoskoriin ($ostoskori, $laskurow["liitostunnus"], $tuoteno, $kpl);
							$kukarow["kesken"] = "";
						}
						elseif (file_exists("../tilauskasittely/lisaarivi.inc")) {
							require ("../tilauskasittely/lisaarivi.inc");
						}
						else {
							require ("lisaarivi.inc");
						}

						echo "<font class='message'>Lis�ttiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

						//Hanskataan sarjanumerollisten tuotteiden lis�varusteet
						if ($tilsarjatunnus[$yht_i] > 0 and $lisatty_tun > 0) {
							require("sarjanumeron_lisavarlisays.inc");

							lisavarlisays($tilsarjatunnus[$yht_i], $lisatty_tun);
						}
					} // tuote ok else
				} // end kpl > 0
			} // end foreach
		} // end tuotel�ytyi else

		$trow			 = "";
		$ytunnus         = "";
		$kpl             = "";
		$tuoteno         = "";
		$toimaika 	     = "";
		$kerayspvm	     = "";
		$hinta 		     = "";
		$netto 		     = "";
		$ale 		     = "";
		$alv		     = "";
		$var			 = "";
		$varasto 	     = "";
		$rivitunnus		 = "";
		$korvaavakielto	 = "";
		$varataan_saldoa = "";
		$myy_sarjatunnus = "";
		$paikka			 = "";
		$tee 			 = "";
	}

	if ($verkkokauppa == "") {
		echo "<font class='head'>".t("Etsi ja selaa tuotteita").":</font><hr>";

		echo "<div id='returnMsg'></div><br";

		if (is_numeric($ostoskori)) {
			echo "<table><tr><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value='poistakori'>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("Tyhjenn� ostoskori")."'>
					</form>";
			echo "</td><td class='back'>";
			echo "	<form method='post' action='$kori_polku'>
					<input type='hidden' name='tee' value=''>
					<input type='hidden' name='ostoskori' value='$ostoskori'>
					<input type='hidden' name='pyytaja' value='haejaselaa'>
					<input type='submit' value='".t("N�yt� ostoskori")."'>
					</form>";
			echo "</td></tr></div>";
		}
		elseif ($kukarow["kesken"] != 0 and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "N") and $verkkokauppa == "") {

			if ($kukarow["extranet"] != "") {
				$toim_kutsu = "EXTRANET";
			}

			echo "	<form method='post' action='tilaus_myynti.php'>
					<input type='hidden' name='toim' value='$toim_kutsu'>
					<input type='hidden' name='aktivoinnista' value='true'>
					<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
					<input type='submit' value='".t("Takaisin tilaukselle")."'>
					</form>";
		}
	}
	elseif ($kukarow["kesken"] != 0 and ($laskurow["tila"] == "L" or $laskurow["tila"] == "N" or $laskurow["tila"] == "N") and $verkkokauppa != "") {
		if(!function_exists("tilaukset_linkki")) {
			function tilaukset_linkki() {
				global $yhtiorow, $kukarow, $haku;

				if($kukarow["kuka"] == "www") {
					return "";
				}

				$val = "";
				if($kukarow["kesken"] > 0) {
					$query = "	SELECT *
								FROM lasku
								WHERE yhtio = '$kukarow[yhtio]' and tila = 'N' and tunnus = '$kukarow[kesken]'";
					$result = mysql_query($query) or pupe_error($query);
					if(mysql_num_rows($result) == 1) {
						$laskurow = mysql_fetch_array($result);
						$query = "	SELECT round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+$laskurow[erikoisale]-(tilausrivi.ale*$laskurow[erikoisale]/100))/100))),$yhtiorow[hintapyoristys]) summa
									FROM tilausrivi
									WHERE yhtio = '$kukarow[yhtio]' and otunnus = '$kukarow[kesken]' and tyyppi != 'D'";
						$result = mysql_query($query) or pupe_error($query);
						$row = mysql_fetch_array($result);

						$val .= "<a href='#' onclick=\"javascript:sndReq('selain', 'verkkokauppa.php?tee=tilatut&osasto={$haku[3]}&try={$haku[4]}')\">".t("Tilaus %s %s, yhteens� %s %s", $kieli, $laskurow["tunnus"], $laskurow["viesti"], number_format($row["summa"], 2, ',', ' '), $laskurow["valkoodi"])."</a><br>";

					}
				}

				if($yhtiorow["alv_kasittely"] != "") {
					$val .=  "<font class='info'>".t("Hinnat eiv�t sis�ll� arvonlis�veroa").".</font>";
				}
				else {
					$val .=  "<font class='info'>".t("Hinnat sis�lt�v�t arvonlis�veron").".</font>";
				}

				return $val;
			}
		}

		echo tilaukset_linkki();
	}

	$kentat	= "tuote.tuoteno,toim_tuoteno,tuote.nimitys,tuote.osasto,tuote.try,tuote.tuotemerkki";
	$nimet	= "Tuotenumero,Toim tuoteno,Nimitys,Osasto,Tuoteryhm�,Tuotemerkki";

	$jarjestys = "sorttauskentta";

	$array = split(",", $kentat);
	$arraynimet = split(",", $nimet);

	$lisa = "";
	$ulisa = "";

	$count = count($array);

	/*

	match againstit ei toimi!!! Kokeile hakusanalla prt firmassa allr

	if (strlen($haku[0]) > 0) {
		$lisa .= " and match (tuote.tuoteno) against ('$haku[0]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[1]) > 0) {
		$lisa .= " and toim_tuoteno like '%$haku[1]%' ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}
	if (strlen($haku[2]) > 0) {
		$lisa .= " and match (tuote.nimitys) against ('$haku[2]*' IN BOOLEAN MODE) ";
		$ulisa .= "&haku[".$i."]=".$haku[$i];
	}

	for ($i=3; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}
	*/

	for ($i=0; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0 && $i <= 1) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0 && $i == 2) {
			$lisa .= " and ".$array[$i]." like '%".$haku[$i]."%'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
		elseif (strlen($haku[$i]) > 0) {
			$lisa .= " and ".$array[$i]."='".$haku[$i]."'";
			$ulisa .= "&haku[".$i."]=".$haku[$i];
		}
	}

	//	Verkkokaupassa selataan vain verkkokauppatuotteita
	if($verkkokauppa != "") {
		$lisa .= " and hinnastoon IN ('W','V') and tuotemerkki != ''";
	}

	if (strlen($ojarj) > 0) {
		$ojarj = urldecode($ojarj);
		$jarjestys = $ojarj;
	}

	if ($poistetut != "") {
		$poislisa  = "";
		$poischeck = "CHECKED";
	}
	else {
		$poislisa  = " and status != 'P' ";
		$poischeck = "";
	}

	if ($poistuvat != "" or (! isset($submit) and $yhtiorow['poistuvat_tuotteet'] == 'X')) {
		$kohtapoislisa  = "";
		$kohtapoischeck = "CHECKED";
	}
	else {
		$kohtapoislisa  = " and status != 'X' ";
		$kohtapoischeck = "";
	}

	if ($lisatiedot != "") {
		$lisacheck = "CHECKED";
	}
	else {
		$lisacheck = "";
	}

	if ($kukarow["extranet"] != "") {
		$avainlisa = " and avainsana.jarjestys < 10000";
	}
	else {
		$avainlisa = "";
	}

	//Otetaan konserniyhti�t hanskaan
	$query	= "	SELECT GROUP_CONCAT(distinct concat(\"'\",yhtio,\"'\")) yhtiot
				from yhtio
				where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
	$pres = mysql_query($query) or pupe_error($query);
	$prow = mysql_fetch_array($pres);

	$yhtiot		= "";
	$konsyhtiot = "";

	//$yhtiot = "yhtio in (".$prow["yhtiot"].")";
	//$konsyhtiot = explode(",", str_replace("'","", $prow["yhtiot"]));

	if($verkkokauppa == "") {
		echo "<table><tr>
				<form action = '$PHP_SELF?toim_kutsu=$toim_kutsu' method = 'post'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";

		echo "<th nowrap valign='top'>
			<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[0]$ulisa'>".t("$arraynimet[0]")."</a><br>
			<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[1]$ulisa'>".t("$arraynimet[1]")."</a></th>";

		echo "<th nowrap valign='top'><br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[2]$ulisa'>".t("$arraynimet[2]")."</a></th>";

		echo "<th nowrap valign='top'>
			<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[3]$ulisa'>".t("$arraynimet[3]")."</a><br>
			<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[4]$ulisa'>".t("$arraynimet[4]")."</a></th>";

		echo "<th nowrap valign='top'>";
		echo "<a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("$arraynimet[5]")."</a>";


		if ($kukarow["extranet"] == "") {
			echo "<br><a href = '$PHP_SELF?toim_kutsu=$toim_kutsu&ojarj=$array[5]$ulisa'>".t("N�yt� poistuvat")." / ".t("lis�tiedot")."</a>";
		}
		echo "</th>";

		echo "</tr><tr>";


		echo "<td nowrap valign='top'>";
		echo "<input type='text' size='10' name = 'haku[0]' value = '$haku[0]'><br>";
		echo "<input type='text' size='10' name = 'haku[1]' value = '$haku[1]'>";
		echo "</td>";

		echo "<td nowrap valign='top'>";
		echo "<input type='text' size='10' name = 'haku[2]' value = '$haku[2]'>";
		echo "</td>";

		$query = "	SELECT DISTINCT avainsana.*, 
					IFNULL((SELECT avainsana_kieli.selitetark
					FROM avainsana as avainsana_kieli
					WHERE avainsana_kieli.yhtio = avainsana.yhtio
					and avainsana_kieli.laji = avainsana.laji
					and avainsana_kieli.selite = avainsana.selite
					and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
					FROM avainsana
					WHERE avainsana.yhtio = '$kukarow[yhtio]' 
					and avainsana.laji = 'OSASTO'
					and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$avainlisa
					ORDER BY avainsana.jarjestys, avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);


		echo "<td nowrap valign='top'><select name='haku[3]'>";
		echo "<option value='' $sel>".t("Ei valintaa")."</option>";

		while($srow = mysql_fetch_array ($sresult)){
			if($haku[3] == $srow["selite"]) {
				$sel = "SELECTED";
			}
			else {
				$sel = '';
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select><br>";

		$query = "	SELECT DISTINCT avainsana.*, 
					IFNULL((SELECT avainsana_kieli.selitetark
					FROM avainsana as avainsana_kieli
					WHERE avainsana_kieli.yhtio = avainsana.yhtio
					and avainsana_kieli.laji = avainsana.laji
					and avainsana_kieli.selite = avainsana.selite
					and avainsana_kieli.kieli = '$kukarow[kieli]' LIMIT 1), avainsana.selitetark) selitetark
					FROM avainsana
					WHERE avainsana.yhtio = '$kukarow[yhtio]' 
					and avainsana.laji = 'TRY'
					and avainsana.kieli in ('$yhtiorow[kieli]', '')
					$avainlisa
					ORDER BY avainsana.jarjestys, avainsana.selite+0";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<select name='haku[4]'>";
		echo "<option value='' $sel>".t("Ei valintaa")."</option>";

		while($srow = mysql_fetch_array ($sresult)){
			if($haku[4] == $srow["selite"]) {
				$sel = "SELECTED";
			}
			else {
				$sel = '';
			}
			echo "<option value='$srow[selite]' $sel>$srow[selite] $srow[selitetark]</option>";
		}
		echo "</select></td>";


		$query = "	SELECT distinct tuotemerkki
					FROM tuote use index (yhtio_tuotemerkki)
					WHERE yhtio='$kukarow[yhtio]'
					$poislisa
					$kohtapoislisa
					and tuotemerkki != ''
					ORDER BY tuotemerkki";
		$sresult = mysql_query($query) or pupe_error($query);

		echo "<td nowrap valign='top'>";
		echo "<select name='haku[5]'>";
		echo "<option value='' $sel>".t("Ei valintaa")."</option>";

		while($srow = mysql_fetch_array ($sresult)){
			if($haku[5] == $srow[0]) {
				$sel = "SELECTED";
			}
			else {
				$sel = '';
			}
			echo "<option value='$srow[0]' $sel>$srow[0]</option>";
		}

		echo "</select><br>";

		echo t("Lis�tiedot")."<input type='checkbox' name='lisatiedot' $lisacheck><br>";

		if ($kukarow["extranet"] == "") {
			echo t("Poistuvat")."<input type='checkbox' name='poistuvat' value='X' $kohtapoischeck>";
			echo t("Poistetut")."<input type='checkbox' name='poistetut' $poischeck>";
		}

		echo "</td>";


		echo "<td class='back' valign='bottom' nowrap><input type='Submit' name='submit' value = '".t("Etsi")."'></td></form></tr>";
		echo "</div><br>";
	}

	// Ei listata mit��n jos k�ytt�j� ei ole tehnyt mit��n rajauksia
	if($lisa == "") {
		if (file_exists("../inc/footer.inc")) {
			require ("../inc/footer.inc");
		}
		else {
			require ("footer.inc");
		}

		exit;
	}

	#TODO t�m� query on hidas tuotenumerohaulla! pit�� optimoida!!
	$query = "	SELECT valitut.sorttauskentta, tuote_wrapper.tuoteno, tuote_wrapper.nimitys, tuote_wrapper.osasto, tuote_wrapper.try, tuote_wrapper.tuotemerkki, tuote_wrapper.myyntihinta,
				tuote_wrapper.nettohinta, tuote_wrapper.aleryhma, tuote_wrapper.status, tuote_wrapper.ei_saldoa, tuote_wrapper.yksikko,
				valitut.toimitiedot, valitut.toim_tuoteno, tuote_wrapper.sarjanumeroseuranta
				FROM tuote tuote_wrapper,
				(	SELECT if(korvaavat.id>0,(select tuoteno from korvaavat korva2 use index (yhtio_id) where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1),tuote.tuoteno) sorttauskentta,
					ifnull(korvaavat.tuoteno, tuote.tuoteno) tuoteno,
					group_concat(concat(toimi.tyyppi_tieto,'##',tuotteen_toimittajat.liitostunnus)) toimitiedot,
					group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '<br>') toim_tuoteno
					FROM tuote
					LEFT JOIN tuotteen_toimittajat use index (yhtio_tuoteno) ON tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					LEFT JOIN toimi use index (PRIMARY) ON toimi.yhtio = tuotteen_toimittajat.yhtio
										and toimi.tunnus        = tuotteen_toimittajat.liitostunnus
										and toimi.tyyppi        = 'S'
										and toimi.tyyppi_tieto != ''
										and toimi.edi_palvelin != ''
										and toimi.edi_kayttaja != ''
										and toimi.edi_salasana != ''
										and toimi.edi_polku    != ''
										and toimi.oletus_vienti in ('C','F','I')
					LEFT JOIN korvaavat use index (yhtio_id) ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat use index (yhtio_tuoteno) where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
					WHERE tuote.yhtio = '$kukarow[yhtio]'
					$lisa
					$poislisa
					$kohtapoislisa
					GROUP BY 1,2
					LIMIT 500
				) valitut
				WHERE tuote_wrapper.yhtio = '$kukarow[yhtio]'
				and valitut.tuoteno = tuote_wrapper.tuoteno
				ORDER BY $jarjestys";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		//Sarjanumeroiden lis�tietoja varten
		if (file_exists("sarjanumeron_lisatiedot_popup.inc")) {
			require("sarjanumeron_lisatiedot_popup.inc");
		}

		if (function_exists("js_popup")) {
			echo js_popup(50);
		}

		echo "<form id = 'lisaa' action=\"javascript:ajaxPost('lisaa', 'tuote_selaus_haku.php?', 'selain', false, true);\" name='lisaa' method='post'>";
		echo "<input type='hidden' name='haku[0]' value = '$haku[0]'>";
		echo "<input type='hidden' name='haku[1]' value = '$haku[1]'>";
		echo "<input type='hidden' name='haku[2]' value = '$haku[2]'>";
		echo "<input type='hidden' name='haku[3]' value = '$haku[3]'>";
		echo "<input type='hidden' name='haku[4]' value = '$haku[4]'>";
		echo "<input type='hidden' name='haku[5]' value = '$haku[5]'>";
		echo "<input type='hidden' name='ojarj'   value = '".urlencode($ojarj)."'>";
		echo "<input type='hidden' name='tee' value = 'TI'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<input type='hidden' name='ostoskori' value='$ostoskori'>";
		echo "<table>";


		$divit = "";
		$valiotsikko = "OK";
		$edtuoteno = $edtry = $edosasto = $edtuotemerkki = "DUMMYDADA";
		$yht_i = 0; // t�� on mei�n indeksi

		while ($row = mysql_fetch_array($result)) {

			//	Onko t�m� sallittu rivi verkkokaupassa?
			$query    = "SELECT * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$row[tuoteno]'";
			$tuoteres = mysql_query($query);
			$trow = mysql_fetch_array($tuoteres);
			$hinnat = alehinta($laskurow, $trow, 1, '', '', '', "hinta,hintaperuste,aleperuste,aperuste,ale");

			if 	(($kukarow["naytetaan_tuotteet"] == "A" or $trow["hinnastoon"] == "V") and ($hinnat["hintaperuste"] < 2 or $hinnat["hintaperuste"] > 12) and ($hinnat["aleperuste"] < 5 or $hinnat["aleperuste"] > 8)) {
				continue;
			}

			if($edtuoteno != $row["sorttauskentta"]) {
				if($verkkokauppa != "") {

					//	Werkkokauppa aloitetaan aina ryhm� tai osasto kent�ll� joka on my�s otsikko!
					if($edtuoteno == "DUMMYDADA") {
						$o["laji"] 	= "try";
						$o["class"] = "head";
						$o["style"]	= "style='text-align: left'";
						$otsikot[] = $o;
					}

					//	Merkki on tyhj�, se vaatii my�s v�liotsikon
					if(strtolower($edtuotemerkki) != strtolower($row["tuotemerkki"])) {
						$o["laji"] 	= "merkki";
						$o["class"] = "message";
						$o["style"]	= "style='text-align: left'";
						$otsikot[] = $o;

						$valiotsikko = "OK";
					}
				}
				else {
					$class="message";
				}

				if(count($otsikot) > 0) {
					foreach($otsikot as $o) {
						if($o["laji"] == "try") {
							$query = "	SELECT concat_ws(' - ', selite, selitetark) nimi
										FROM avainsana
										WHERE yhtio='{$kukarow["yhtio"]}' and laji = 'TRY' and selite = '{$row["try"]}' LIMIT 1";
							$tryres = mysql_query($query) or pupe_error($query);
							$tryrow = mysql_fetch_array($tryres);

							if($tryrow["nimi"] == "") {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><font class='$o[class]'>".t("Lajittelemattomat")."</font></td></tr>";
							}
							else {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><font class='$o[class]'>{$tryrow["nimi"]}</font></td></tr>";
							}
						}
						elseif($o["laji"] == "merkki") {
							if($row["tuotemerkki"] == "") {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><br><font class='$o[class]'>".t("Muut tuotemerkit")."</font></td></tr>";
							}
							else {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><br><font class='$o[class]'>{$row["tuotemerkki"]}</font></td></tr>";
							}
						}
						elseif($o["laji"] == "osasto") {
							$query = "	SELECT concat_ws(' - ', selite, selitetark) nimi
										FROM avainsana
										WHERE yhtio='{$kukarow["yhtio"]}' and laji = 'OSASTO' and selite = '{$row["osasto"]}' LIMIT 1";
							$ores = mysql_query($query) or pupe_error($query);
							$orow = mysql_fetch_array($ores);

							if($orow["nimi"] == "") {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><br><br><font class='$o[class]'>".t("Muut")."</font></td></tr>";
							}
							else {
								echo "<tr><td class = 'back' colspan = '7' $o[style]><br><br><font class='$o[class]'>{$orow["nimi"]}</font></td></tr>";
							}
						}
					}
				}

				if($valiotsikko == "OK") {

					echo "<tr>";
					echo "<th>".t("Tuoteno")."</th>";
					echo "<th>".t("Nimitys")."</th>";

					if ($lisatiedot != "") {
						echo "<th>".t("Toim Tuoteno")."</th>";
					}

					if($verkkokauppa == "" or ($verkkokauppa != "" and $kukarow["kuka"] != "www")) {
						echo "<th style='text-align: right'>".t("Hinta")."</th>";
						if($verkkokauppa == "") {
							echo "<th>".t("Aleryhm�")."</th>";
						}
					}

					if ($verkkokauppa == "" and $lisatiedot != "" and $kukarow["extranet"] == "") {
						echo "<th>".t("Nettohinta")."</th>";
						echo "<th>".t("Status")."</th>";
					}
					if($verkkokauppa == "" or $kukarow["kuka"] != "www") {
						echo "<th style='text-align: center'>".t("Myyt�viss�")."</th>";
					}

			        if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
						echo "<th></th>";
					}
					echo "</tr>";
				}

				unset($otsikot);
				$edosasto 	= $row["osasto"];
				$edtry 			= $row["try"];
				$edtuotemerkki 	= $row["tuotemerkki"];
				$valiotsikko	= "";

			}

			echo "<tr class='aktiivi'>";

			if (strtoupper($row["status"]) == "P") {
				$vari = "tumma";
			}
			else {
				$vari = "";
			}

			$lisakala = "";

			if ($row["sorttauskentta"] == $edtuoteno) {
				$lisakala = "* ";

				if ($vari == "") {
					$vari = 'spec';
				}
			}

			if ($row["sarjanumeroseuranta"] != "") {
				$query	= "	SELECT sarjanumeroseuranta.*, tilausrivi_osto.nimitys nimitys
							FROM sarjanumeroseuranta
							LEFT JOIN tilausrivi tilausrivi_myynti use index (PRIMARY) ON tilausrivi_myynti.yhtio=sarjanumeroseuranta.yhtio and tilausrivi_myynti.tunnus=sarjanumeroseuranta.myyntirivitunnus
							LEFT JOIN tilausrivi tilausrivi_osto   use index (PRIMARY) ON tilausrivi_osto.yhtio=sarjanumeroseuranta.yhtio   and tilausrivi_osto.tunnus=sarjanumeroseuranta.ostorivitunnus
							WHERE sarjanumeroseuranta.yhtio = '$kukarow[yhtio]' and sarjanumeroseuranta.tuoteno = '$row[tuoteno]'
							and (tilausrivi_myynti.tunnus is null)
							and tilausrivi_osto.laskutettuaika != '0000-00-00'";
				$sarjares = mysql_query($query) or pupe_error($query);

				// Sarjanumerollisille tuotteille haetaan nimitys ostopuolen tilausrivilt�
				if ($row["sarjanumeroseuranta"] == "S") {
					$nimitys = "";

					if(mysql_num_rows($sarjares) > 0) {
						$nimitys .= "<table width='100%' valign='top'>";

						$edsarjanimitys = "DUMMYDADA";
						while ($sarjarow = mysql_fetch_array($sarjares)) {
							if($edsarjanimitys != $sarjarow["nimitys"]) {
								if($sarjarow["nimitys"] != "") {
									$nimitys .= "<tr><td valign='top'>$sarjarow[nimitys]</td></tr>";
								}
								else {
									$nimitys .= "<tr><td valign='top'>$row[nimitys]</td></tr>";
								}
							}

							$edsarjanimitys = $sarjarow["nimitys"];
						}

						$nimitys .= "</table>";

						$row["nimitys"] = $nimitys;
					}
				}
			}

			if(!isset($originaalit)) {
				$orginaaalit = table_exists("tuotteen_orginaalit");
			}

			$linkkilisa = "";

			//	Liitet��n originaalitietoja
			if($orginaaalit === true) {
				$id = md5(uniqid());

				$query = "	SELECT *
							FROM tuotteen_orginaalit
							WHERE yhtio = '{$kukarow["yhtio"]}' and tuoteno = '{$row["tuoteno"]}'";
				$orgres = mysql_query($query) or pupe_error($query);

				if(mysql_num_rows($orgres)>0) {
					$linkkilisa = "<div id='$id' class='popup' style=\"width: 300px\">
					<table width='300px' align='center'>
					<caption><font class='head'>Tuotteen originaalit</font></caption>
					<tr>
						<th>".t("Tuotenumero")."</th>
						<th>".t("Merkki")."</th>
						<th>".t("Hinta")."</th>
					</tr>";

					while($orgrow = mysql_fetch_array($orgres)) {

						$linkkilisa .= "<tr>
								<td>{$orgrow["orig_tuoteno"]}</td>
								<td>{$orgrow["merkki"]}</td>
								<td>{$orgrow["orig_hinta"]}</td>
							</tr>";
					}

					$linkkilisa .= "</div></div>";

					if($kukarow["extranet"] != "") {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='pics/lullacons/info.png' height='13'></a>";
					}
					else {
						$linkkilisa .= "&nbsp;&nbsp;<a src='#' onmouseover=\"popUp(event, '$id');\" onmouseout=\"popUp(event, '$id');\"><img src='../pics/lullacons/info.png' height='13'></a>";
					}
				}
			}

			$tuottee_lisatiedot = "href='javascript:sndReq(\"{$orow["osasto"]}_T\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno={$row["tuoteno"]}\", \"{$orow["osasto"]}_P\", true)'";

			if($verkkokauppa == "") {
				if ($kukarow["extranet"] != "") {
					echo "<td valign='top' class='$vari'>$lisakala $row[tuoteno]$linkkilisa</td>";
				}
				else {
					echo "<td valign='top' class='$vari'><a href='../tuote.php?tuoteno=".urlencode($row["tuoteno"])."&tee=Z'>$lisakala $row[tuoteno]</a>$linkkilisa</td>";
				}
				echo "<td valign='top' class='$vari'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</td>";

				if ($lisatiedot != "") {
					echo "<td valign='top' class='$vari'>$row[toim_tuoteno]</td>";
				}
			}
			else {
				if($row["toim_tuoteno"] != "" and $kukarow["kuka"] != "www") {
					$toimlisa = "<br><a id='$row[tuoteno]_P' href='javascript:sndReq(\"{$row["tuoteno"]}_T\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno={$row["tuoteno"]}\", \"{$row["tuoteno"]}_P\")'>{$row["toim_tuoteno"]}</a>";
				}
				else $toimlisa = "";

				echo "<td valign='top' class='$vari' id='tno'>
				<a id='$row[tuoteno]_P2' href='javascript:sndReq(\"{$row["tuoteno"]}_T\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno={$row["tuoteno"]}\", \"{$row["tuoteno"]}_P2\")'>{$row["tuoteno"]}</a>$toimlisa
				</td>";

				echo "<td valign='top' class='$vari'><a id='$row[tuoteno]_P3' href='javascript:sndReq(\"{$row["tuoteno"]}_T\", \"verkkokauppa.php?tee=tuotteen_lisatiedot&tuoteno={$row["tuoteno"]}\", \"{$row["tuoteno"]}_P3\")'>".asana('nimitys_',$row['tuoteno'],$row['nimitys'])."</a></td>";
			}

			//	Extranetk�ytt�jille voidaan n�ytt�� my�s se heid�n asiakashinta..
			// jos kyseess� on extranet asiakas yritet��n n�ytt�� kaikki hinnat oikeassa valuutassa
			if($verkkokauppa == "" or ($verkkokauppa != "" and $kukarow["kuka"] != "www")) {
				if($kukarow["extranet"] != "" and $kukarow["naytetaan_asiakashinta"] != "") {
					// haetaan tuotteen tiedot
					$myyntihinta = number_format($hinnat["hinta"] * (1-($hinnat["ale"]/100)), 2, ',', ' ')." {$laskurow["valkoodi"]}";
				}
				elseif ($kukarow["extranet"] != "") {

					$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[oletus_asiakas]'";
					$oleasres = mysql_query($query) or pupe_error($query);
					$oleasrow = mysql_fetch_array($oleasres);

					if ($oleasrow["valkoodi"] != $yhtiorow["valkoodi"]) {

						$query = "	select *
									from hinnasto
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and valkoodi = '$oleasrow[valkoodi]'
									and laji = ''
									and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
									order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
									limit 1";
						$olhires = mysql_query($query) or pupe_error($query);

						if (mysql_num_rows($olhires) == 1) {
							$olhirow = mysql_fetch_array($olhires);
							$myyntihinta = "$olhirow[hinta] $olhirow[valkoodi]";
						}
						else {
							$query = "select * from valuu where yhtio='$kukarow[yhtio]' and nimi='$oleasrow[valkoodi]'";
							$olhires = mysql_query($query) or pupe_error($query);

							if (mysql_num_rows($oleasres) == 1) {
								$olhirow = mysql_fetch_array($olhires);
								$myyntihinta = number_format(yhtioval($row["myyntihinta"], $olhirow["kurssi"]), 2, ',', ' '). " $yhtiorow[valkoodi]";
							}
						}
					}
					else {
						$myyntihinta = number_format($row["myyntihinta"], 2, ',', ' '). " $yhtiorow[valkoodi]";
					}
				}
				else {
					$query = "	SELECT distinct valkoodi, maa from hinnasto
								where yhtio = '$kukarow[yhtio]'
								and tuoteno = '$row[tuoteno]'
								and laji = ''
								order by maa, valkoodi";
					$hintavalresult = mysql_query($query) or pupe_error($query);

					while ($hintavalrow = mysql_fetch_array($hintavalresult)) {

						// katotaan onko tuotteelle valuuttahintoja
						$query = "	SELECT *
									from hinnasto
									where yhtio = '$kukarow[yhtio]'
									and tuoteno = '$row[tuoteno]'
									and valkoodi = '$hintavalrow[valkoodi]'
									and maa = '$hintavalrow[maa]'
									and laji = ''
									and ((alkupvm <= current_date and if(loppupvm = '0000-00-00','9999-12-31',loppupvm) >= current_date) or (alkupvm='0000-00-00' and loppupvm='0000-00-00'))
									order by ifnull(to_days(current_date)-to_days(alkupvm),9999999999999)
									limit 1";
						$hintaresult = mysql_query($query) or pupe_error($query);

						while ($hintarow = mysql_fetch_array($hintaresult)) {
							$myyntihinta .= "<br>$hintarow[maa]: $hintarow[hinta] $hintarow[valkoodi]";
						}
					}
				}

				echo "<td valign='top' class='$vari' align='right'>$myyntihinta</td>";
			}

			if($verkkokauppa == "") {
				echo "<td valign='top' class='$vari'>$row[aleryhma]</td>";
			}

			if ($verkkokauppa == "" and $lisatiedot != "" and $kukarow["extranet"] == "") {
				echo "<td valign='top' class='$vari'>$row[nettohinta]</td>";
				echo "<td valign='top' class='$vari'>$row[status]</td>";
			}

			$edtuoteno = $row["sorttauskentta"];

			//	Kirjautumatta ei sallita saldocheckej�!
			//echo $kukarow["kuka"]." |�".$verkkokauppa;
			if($verkkokauppa != "" and $kukarow["kuka"] != "www") {
				if($verkkokauppa != "") {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, $kukarow["yhtio"]);

					if($myytavissa > 0) {
						$color = "green";
					}
					else {
						$color = "red";
					}

					$toimlisa = "";
					
					//Arvotaan milloin kamaa on tulossa.. copypaste tuote.php
					$query = "	SELECT lasku.nimi, lasku.tunnus, (tilausrivi.varattu+tilausrivi.jt) kpl, tilausrivi.yksikko,
								date_format(if(tilausrivi.tyyppi!='O' and tilausrivi.tyyppi!='W', tilausrivi.kerayspvm, tilausrivi.toimaika), '%d.%m.%Y') pvm,
								varastopaikat.nimitys varasto, tilausrivi.tyyppi, lasku.laskunro, lasku.tilaustyyppi, tilausrivi.var, lasku2.laskunro as keikkanro
								FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
								JOIN lasku use index (PRIMARY) ON lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus
								LEFT JOIN varastopaikat ON varastopaikat.yhtio = lasku.yhtio and varastopaikat.tunnus = lasku.varasto
								LEFT JOIN lasku as lasku2 ON lasku2.yhtio = tilausrivi.yhtio and lasku2.tunnus = tilausrivi.uusiotunnus
								WHERE tilausrivi.yhtio = '$kukarow[yhtio]'
								and tilausrivi.tyyppi in ('O')
								and tilausrivi.tuoteno = '$row[tuoteno]'
								and tilausrivi.laskutettuaika = '0000-00-00'
								and tilausrivi.varattu + tilausrivi.jt != 0
								and tilausrivi.var not in ('P')
								ORDER BY pvm
								LIMIT 1";
					$jtresult = mysql_query($query) or pupe_error($query);

					if(mysql_num_rows($jtresult)>0) {
						$jtrow = mysql_fetch_array($jtresult);
						$toimlisa = "<font class='info'>$jtrow[kpl] ".ta($kieli, "Y", $jtrow["yksikko"])." $jtrow[pvm]</font>";
					}
					elseif((int) $myytavissa <= 0) {

						$query = "	SELECT toimitusaika
									FROM tuotteen_toimittajat
									WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$row[tuoteno]' and toimitusaika>0
									LIMIT 1";
						$jtresult = mysql_query($query) or pupe_error($query);

						if(mysql_num_rows($jtresult)>0) {
							$jtrow = mysql_fetch_array($jtresult);
							$toimlisa = "<font class='info'>".t("ta: %s pv.", $kieli, $jtrow["toimitusaika"])."</font>";
						}
					}

					if((int) $myytavissa > 0) {
						echo "<td valign='top' class='$color' align = 'right'>". (int) $myytavissa."<br>$toimlisa</td>";
					}
					else {
						echo "<td valign='top' class='$color' align ='right'>$toimlisa</td>";
					}
				}
				elseif ($row['ei_saldoa'] != '' and $kukarow["extranet"] == "") {
						echo "<td valign='top' class='green'>".t("Saldoton")."</td>";
				}
				elseif ($kukarow["extranet"] != "") {

					$query =	"select *
								from tuoteperhe
								join tuote on tuoteperhe.yhtio = tuote.yhtio and tuoteperhe.tuoteno = tuote.tuoteno and ei_saldoa = ''
								where tuoteperhe.yhtio = '$kukarow[yhtio]' and isatuoteno = '$row[tuoteno]' and tyyppi in ('','P')";
					$isiresult = mysql_query($query) or pupe_error($query);

					// katotaan paljonko on myyt�viss�
					$kokonaismyytavissa = 0;

					if ($row['ei_saldoa'] == '') {
						foreach($konsyhtiot as $yhtio) {
							list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], "", 0, $yhtio, "", "", "", "", $laskurow["toim_maa"]);
							$kokonaismyytavissa += $myytavissa;
						}
					}

					$lapset=mysql_num_rows($isiresult);
					$oklapset=0;

					if ($lapset > 0) {
						while ($isirow = mysql_fetch_array($isiresult)) {
							$lapsikokonaismyytavissa = 0;
							foreach($konsyhtiot as $yhtio) {
								list($lapsisaldo, $lapsihyllyssa, $lapsimyytavissa) = saldo_myytavissa($isirow["tuoteno"], "", 0, $yhtio, "", "", "", "", $laskurow["toim_maa"]);
								$lapsikokonaismyytavissa += $lapsimyytavissa;
							}
							if ($lapsikokonaismyytavissa > 0) {
								$oklapset++;
							}
						}
					}

					if ($lapset > 0 and $lapset == $oklapset and ($row['ei_saldoa'] != '' or $kokonaismyytavissa > 0)) {
						echo "<td valign='top' class='green'>".t("On")."</td>";
					}
					elseif ($lapset > 0 and $lapset <> $oklapset) {
						echo "<td valign='top' class='red'>".t("Ei")."</td>";
					}
					elseif ($kokonaismyytavissa > 0 or $row['ei_saldoa'] != '') {
						echo "<td valign='top' class='green'>".t("On")."</td>";
					}
					else {
						echo "<td valign='top' class='red'>".t("Ei")."</td>";
					}
				}
				elseif ($row["sarjanumeroseuranta"] == "S") {

					if (is_resource($sarjares) and mysql_num_rows($sarjares)) {
						mysql_data_seek($sarjares, 0);
					}

					echo "<td valign='top' $csp><table width='100%'>";

					while ($sarjarow = mysql_fetch_array($sarjares)) {

						echo "<tr>
								<td class='$vari' onmouseout=\"popUp(event,'$sarjarow[tunnus]')\" onmouseover=\"popUp(event,'$sarjarow[tunnus]')\" nowrap>
								<a href='sarjanumeroseuranta.php?tuoteno_haku=".urlencode($row["tuoteno"])."&sarjanumero_haku=".urlencode($sarjarow["sarjanumero"])."'>$sarjarow[sarjanumero]</a>
								</td>";

						if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
							echo "<td valign='top' class='$vari' nowrap>";
							echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
							echo "<input type='hidden' name='tilsarjatunnus[$yht_i]' value = '$sarjarow[tunnus]'>";
							echo "<input type='checkbox' name='tilkpl[$yht_i]' value='1'> ";
							echo "</td>";
							$yht_i++;
						}
						echo "</tr>";

					}
					echo "</div>";

					if ($kukarow["kesken"] != 0 or is_numeric($ostoskori)) {
						echo "<td valign='top' align='right' class='$vari' nowrap>";
						echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
						echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
						echo "<input type='submit' value = '".t("Lis��")."'>";
						echo "</td>";
						$yht_i++;
					}

					echo "</td>";
				}
				else {

					if ($laskurow["toim_maa"] != '') {
						$sallitut_maat_lisa = " and (varastopaikat.sallitut_maat like '%$laskurow[toim_maa]%' or varastopaikat.sallitut_maat = '') ";
					}

					// K�yd��n l�pi tuotepaikat
					if ($row["sarjanumeroseuranta"] == "E" or $row["sarjanumeroseuranta"] == "F") {
						$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
									tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
									sarjanumeroseuranta.sarjanumero era,
									concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'),lpad(upper(tuotepaikat.hyllyvali), 5, '0'),lpad(upper(tuotepaikat.hyllytaso), 5, '0')) sorttauskentta,
									varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
						 			FROM tuote
									JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
									JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
									$sallitut_maat_lisa
									and concat(rpad(upper(varastopaikat.alkuhyllyalue),  5, '0'),lpad(upper(varastopaikat.alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
									and concat(rpad(upper(varastopaikat.loppuhyllyalue), 5, '0'),lpad(upper(varastopaikat.loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
									JOIN sarjanumeroseuranta ON sarjanumeroseuranta.yhtio = tuote.yhtio
									and sarjanumeroseuranta.tuoteno = tuote.tuoteno
									and sarjanumeroseuranta.hyllyalue = tuotepaikat.hyllyalue
									and sarjanumeroseuranta.hyllynro  = tuotepaikat.hyllynro
									and sarjanumeroseuranta.hyllyvali = tuotepaikat.hyllyvali
									and sarjanumeroseuranta.hyllytaso = tuotepaikat.hyllytaso
									and sarjanumeroseuranta.myyntirivitunnus = 0
									and sarjanumeroseuranta.era_kpl != 0
									WHERE tuote.$yhtiot
									and tuote.tuoteno = '$row[tuoteno]'
									GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
									ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
					}
					else {
						$query = "	SELECT tuote.yhtio, tuote.tuoteno, tuote.ei_saldoa, varastopaikat.tunnus varasto, varastopaikat.tyyppi varastotyyppi, varastopaikat.maa varastomaa,
									tuotepaikat.oletus, tuotepaikat.hyllyalue, tuotepaikat.hyllynro, tuotepaikat.hyllyvali, tuotepaikat.hyllytaso,
									concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'),lpad(upper(hyllyvali), 5, '0'),lpad(upper(hyllytaso), 5, '0')) sorttauskentta,
									varastopaikat.nimitys, if(varastopaikat.tyyppi!='', concat('(',varastopaikat.tyyppi,')'), '') tyyppi
						 			FROM tuote
									JOIN tuotepaikat ON tuotepaikat.yhtio = tuote.yhtio and tuotepaikat.tuoteno = tuote.tuoteno
									JOIN varastopaikat ON varastopaikat.yhtio = tuotepaikat.yhtio
									$sallitut_maat_lisa
									and concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
									and concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(hyllyalue), 5, '0'),lpad(upper(hyllynro), 5, '0'))
									WHERE tuote.$yhtiot
									and tuote.tuoteno = '$row[tuoteno]'
									ORDER BY tuotepaikat.oletus DESC, varastopaikat.nimitys, sorttauskentta";
					}
					$varresult = mysql_query($query) or pupe_error($query);

					echo "<td valign='top'>";

					if (mysql_num_rows($varresult) > 0) {

						echo "<table width='100%'>";

						// katotaan jos meill� on tuotteita varaamassa saldoa joiden varastopaikkaa ei en�� ole olemassa...
						list($saldo, $hyllyssa, $orvot) = saldo_myytavissa($row["tuoteno"], "ORVOT");
						$orvot *= -1;

						while ($saldorow = mysql_fetch_array ($varresult)) {

							list($saldo, $hyllyssa, $myytavissa, $sallittu) = saldo_myytavissa($saldorow["tuoteno"], '', '', $saldorow["yhtio"], $saldorow["hyllyalue"], $saldorow["hyllynro"], $saldorow["hyllyvali"], $saldorow["hyllytaso"], $laskurow["toim_maa"], '', $saldorow["era"]);

							//	Listataan vain varasto jo se ei ole kielletty
							if($sallittu === TRUE) {
								// hoidetaan pois problematiikka jos meill� on orpoja (tuotepaikattomia) tuotteita varaamassa saldoa
								if ($orvot > 0) {
									if ($myytavissa >= $orvot and $saldorow["yhtio"] == $kukarow["yhtio"]) {
								    	// poistaan orpojen varaamat tuotteet t�lt� paikalta
								    	$myytavissa = $myytavissa - $orvot;
								    	$orvot = 0;
									}
									elseif ($orvot > $myytavissa and $saldorow["yhtio"] == $kukarow["yhtio"]) {
								    	// poistetaan niin paljon orpojen saldoa ku voidaan
								    	$orvot = $orvot - $myytavissa;
								    	$myytavissa = 0;
									}
								}

								echo "<tr>
										<td class='$vari' nowrap>$saldorow[nimitys] $saldorow[tyyppi]</td>
										<td class='$vari' align='right' nowrap>".sprintf("%.2f", $myytavissa)." ".ta($kieli, "Y", $row["yksikko"])."</td>
										</tr>";
							}
						}
						echo "</div></td>";
					}
					echo "</td>";
				}
			}

			if (($row["sarjanumeroseuranta"] == "" or $row["sarjanumeroseuranta"] == "E"  or $row["sarjanumeroseuranta"] == "F" or $kukarow["extranet"] != "") and ($kukarow["kesken"] != 0 or is_numeric($ostoskori))) {
				echo "<td valign='top' align='right' class='$vari' nowrap>";
				echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$row[tuoteno]'>";
				echo "<input type='text' size='3' name='tilkpl[$yht_i]'> ";
				echo "<input type='submit' value = '".t("Lis��")."'>";
				echo "</td>";
				$yht_i++;
			}

			echo "</tr><tr><td colspan='6' class='back'><div id='$row[tuoteno]_T'></div></td></tr>";

		}
		echo "</table>";
		echo "</form>";

		//sarjanumeroiden piilotetut divit
		echo $divit;
	}
	else {
		echo t("Yht��n tuotetta ei l�ytynyt")."!";
	}

	if(mysql_num_rows($result) == 500) {
		echo "<br><br><font class='message'>".t("L�ytyi yli 500 tuotetta, tarkenna hakuasi")."!</font>";
	}

	if (file_exists("../inc/footer.inc")) {
		//require ("../inc/footer.inc");
	}
	else {
		//require ("footer.inc");
	}

?>
