<?php

	if ($_POST["toim"] == "yhtion_parametrit") {
		$apucss = $_POST["css"];
		$apucsspieni = $_POST["css_pieni"];
		$apucssextranet = $_POST["css_extranet"];
	}
	else {
		unset($apucss);
		unset($apucsspieni);
		unset($apucssextranet);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "yllapito.php")  !== FALSE) {
		require ("inc/parametrit.inc");
	}

	// pikkuh�kki, ettei rikota css kentt��
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucss)) {
		$t[$cssi] = $apucss;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucsspieni)) {
		$t[$csspienii] = $apucsspieni;
	}
	if ($_POST["toim"] == "yhtion_parametrit" and isset($apucssextranet)) {
		$t[$cssextraneti] = $apucssextranet;
	}

	require ("inc/$toim.inc");

	if ($otsikko == "") {
		$otsikko = $toim;
	}
	if ($otsikko_nappi == "") {
		$otsikko_nappi = $toim;
	}


	echo "<font class='head'>".t("$otsikko")."</font><hr>";

	if ($oikeurow['paivitys'] != '1') { // Saako paivittaa
		if ($uusi == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta lis�t� t�t� tietoa")."</b><br>";
			$uusi = '';
			exit;
		}
		if ($del == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta poistaa t�t� tietoa")."</b><br>";
			$del = '';
			$tunnus = 0;
			exit;
		}
		if ($upd == 1) {
			echo "<b>".t("Sinulla ei ole oikeutta muuttaa t�t� tietoa")."</b><br>";
			$upd = '';
			$uusi = 0;
			$tunnus = 0;
			exit;
		}
	}

	// Tietue poistetaan
	if ($del == 1 and $saakopoistaa == "") {
		$query = "	DELETE from $toim
					WHERE tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$tunnus = 0;

		// Siirryt��n takaisin sielt� mist� tultiin
		if ($lopetus != '') {
			// Jotta urlin parametrissa voisi p��ss�t� toisen urlin parametreineen
			$lopetus = str_replace('!!!!','?', $lopetus);
			$lopetus = str_replace('!!','&',  $lopetus);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
			exit;
		}
	}

	// Jotain p�ivitet��n tietokontaan
	if ($upd == 1) {
		// Luodaan puskuri, jotta saadaan taulukot kuntoon
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		// Tarkistetaan
		$errori = '';
		for ($i=1; $i < mysql_num_fields($result)-1; $i++) {
			require "inc/".$toim."tarkista.inc";
		}

		if ($errori != '') {
			echo "<font class='error'>".t("Jossain oli jokin virhe! Ei voitu paivitt��!")."</font>";
		}

		// Luodaan tietue
		if ($errori == "") {
			if ($tunnus == "") {
				// Taulun ensimm�inen kentt� on aina yhti�
				$query = "INSERT into $toim SET yhtio='$kukarow[yhtio]' ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i])) {
						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);

						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";
					}
				}
			}
			// P�ivitet��n
			else {
				// Taulun ensimm�inen kentt� on aina yhti�
				$query = "UPDATE $toim SET yhtio='$kukarow[yhtio]' ";

				for ($i=1; $i < mysql_num_fields($result); $i++) {
					if (isset($t[$i])) {
						if(mysql_field_type($result,$i)=='real') $t[$i] = str_replace ( ",", ".", $t[$i]);

						$query .= ", ". mysql_field_name($result,$i)."='".$t[$i]."' ";
					}
				}

				$query .= " where tunnus = $tunnus";
			}
			$result = mysql_query($query) or pupe_error($query);

			if ($tunnus == '') {
				$tunnus = mysql_insert_id();
			}

			// Siirryt��n takaisin sielt� mist� tultiin
			if ($lopetus != '') {
				// Jotta urlin parametrissa voisi p��ss�t� toisen urlin parametreineen
				$lopetus = str_replace('!!!!','?', $lopetus);
				$lopetus = str_replace('!!','&',  $lopetus);

				$lopetus .= "&yllapidossa=$toim&yllapidontunnus=$tunnus";

				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=$lopetus'>";
				exit;
			}

			$uusi = 0;
			$tunnus = 0;
		}
	}

	// Nyt selataan
	if (($tunnus == 0) and ($uusi == 0) and ($errori == '')) {
        $array = split(",", $kentat);
        $count = count($array);
        for ($i=0; $i<=$count; $i++) {
                if (strlen($haku[$i]) > 0) {
                        $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
                        $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
                }
        }
        if (strlen($ojarj) > 0) {
                $jarjestys = $ojarj;
        }

        $query = "SELECT " . $kentat . " FROM $toim WHERE yhtio = '$kukarow[yhtio]' $lisa ";
        $query .= "$ryhma ORDER BY $jarjestys LIMIT 350";

		$result = mysql_query($query) or pupe_error($query);

		echo "	<table><tr>
				<form action='yllapito.php' method='post'>
				<input type='hidden' name='toim' value='$toim'>
				<input type='hidden' name='rajattu_nakyma' value='$rajattu_nakyma'>
				<input type='hidden' name='alias_set' value='$alias_set'>";

		for ($i = 1; $i < mysql_num_fields($result); $i++) {
			echo "<th><a href='yllapito.php?toim=$toim&rajattu_nakyma=$rajattu_nakyma&alias_set=$alias_set&ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

			if 	(mysql_field_len($result,$i)>10) $size='20';
			elseif	(mysql_field_len($result,$i)<5)  $size='5';
			else	$size='10';

			echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
			echo "</th>";
		}

		echo "<td class='back' valign='bottom'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></form>";

		if ($toim != "yhtio" and $toim != "yhtion_parametrit") {
			echo "	<form action = 'yllapito.php' method = 'post'>
					<input type='hidden' name='uusi' value='1'>
					<input type='hidden' name='toim' value='$toim'>
					<input type='hidden' name='rajattu_nakyma' value='$rajattu_nakyma'>
					<input type='hidden' name='alias_set' value='$alias_set'>
					<td class='back' valign='bottom'><input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></td></form>";
		}

		echo "</tr>";

		while ($trow=mysql_fetch_array ($result)) {
			echo "<tr>";
			for ($i=1; $i<mysql_num_fields($result); $i++) {
				if ($i == 1) {
					if (trim($trow[1]) == '') $trow[1] = "".t("*tyhj�*")."";
					echo "<td><a href='yllapito.php?toim=$toim&rajattu_nakyma=$rajattu_nakyma&alias_set=$alias_set&tunnus=$trow[0]'>$trow[1]</a></td>";
				}
				else {
					echo "<td>$trow[$i]</td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	// Nyt n�ytet��n vanha tai tehd��n uusi(=tyhj�)
	if (($tunnus > 0) or ($uusi != 0) or ($errori != '')) {
		if ($oikeurow['paivitys'] != 1) {
			echo "<b>".t("Sinulla ei ole oikeuksia p�ivitt�� t�t� tietoa")."</b><br>";
		}
		echo "<form action = 'yllapito.php' method = 'post'>";
		echo "<input type = 'hidden' name = 'toim' value = '$toim'>";
		echo "<input type = 'hidden' name = 'rajattu_nakyma' value = '$rajattu_nakyma'>";
		echo "<input type = 'hidden' name = 'alias_set' value = '$alias_set'>";
		echo "<input type = 'hidden' name = 'tunnus' value = '$tunnus'>";
		echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";
		echo "<input type = 'hidden' name = 'upd' value = '1'>";

		$al_lisa = "";

		if ($alias_set != '') {
			if ($rajattu_nakyma != '') {
				$al_lisa = " and selitetarktark = '$alias_set' ";
			}
			else {
				$al_lisa = " and (selitetarktark = '$alias_set' or selitetarktark = '') ";
			}
			
			$query = "	SELECT count(*) countti
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite like '$toim.%'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);
			$aliarow = mysql_fetch_array($al_res);
			
			if ($aliarow['countti'] == 0) {
				$alias_set = '';
				$rajattu_nakyma = '';
			}
			
		}


		// Kokeillaan geneerist�
		$query = "	SELECT *
					FROM $toim
					WHERE tunnus = '$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$trow = mysql_fetch_array($result);

		echo "<table width='100%'><tr><td class='back' valign='top'>";
		echo "<table>";

		for ($i=0; $i < mysql_num_fields($result) - 1; $i++) {

			$nimi = "t[$i]";

			if (isset($t[$i])) {
				$trow[$i] = $t[$i];
			}

			require ("inc/$toim"."rivi.inc");

			if 	(mysql_field_len($result,$i)>10) 	$size='35';
			elseif	(mysql_field_len($result,$i)<5)	$size='5';
			else	$size='10';

			$maxsize = mysql_field_len($result,$i); // Jotta t�t� voidaan muuttaa

			//Haetaan tietokantasarakkeen nimialias
			$al_nimi   = mysql_field_name($result, $i);

			$query = "	SELECT *
						FROM avainsana
						WHERE yhtio = '$kukarow[yhtio]'
						and laji='MYSQLALIAS'
						and selite='$toim.$al_nimi'
						$al_lisa";
			$al_res = mysql_query($query) or pupe_error($query);

			if(mysql_num_rows($al_res) > 0) {
				$al_row = mysql_fetch_array($al_res);

				$otsikko = $al_row["selitetark"];
			}
			else {
				$otsikko = t(mysql_field_name($result, $i));

				if ($rajattu_nakyma != '') {
 					$ulos = "";
 					$tyyppi = 0;
 				}
			}

			// $tyyppi --> 0 rivi� ei n�ytet� ollenkaan
			// $tyyppi --> 1 rivi n�ytet��n normaalisti
			// $tyyppi --> 2 rivi n�ytet��n, mutta sit� ei voida muokata, eik� sen arvoa p�vitet�
			// $tyyppi --> 3 rivi n�ytet��n, mutta sit� ei voida muokata, mutta sen arvo p�ivitet��n
			// $tyyppi --> 4 rivi� ei n�ytet� ollenkaan, mutta sen arvo p�ivitet��n

			if ($tyyppi > 0 and $tyyppi < 4) {
				echo "<tr>";
				echo "<th align='left'>$otsikko</th>";
			}

			if ($jatko == 0) {
				echo $ulos;
			}
			elseif ($tyyppi == 1) {
				echo "<td><input type = 'text' name = '$nimi' value = '$trow[$i]' size='$size' maxlength='$maxsize'></td>";
			}
			elseif ($tyyppi == 2) {
				echo "<td>$trow[$i]</td>";
			}
			elseif($tyyppi == 3) {
				echo "<td>$trow[$i]<input type = 'hidden' name = '$nimi' value = '$trow[$i]'></td>";
			}
			elseif($tyyppi == 4) {
				echo "<input type = 'hidden' name = '$nimi' value = '$trow[$i]'>";
			}

			if (isset($virhe[$i])) {
				echo "<td class='back'><font class='error'>$virhe[$i]</font></td>\n";
			}

			if ($tyyppi > 0 and $tyyppi < 4) {
				echo "</tr>";
			}
		}
		echo "</table>";

		if ($uusi == 1) {
			$nimi = "".t("Perusta $otsikko_nappi")."";
		}
		else {
			$nimi = "".t("P�ivit� $otsikko_nappi")."";
		}

		echo "<br><input type = 'submit' value = '$nimi'>";
		echo "</form>";

		if ($saakopoistaa == "") {
			// Tehd��n "poista tietue"-nappi
			if ($uusi != 1 and $toim != "yhtio" and $toim != "yhtion_parametrit") {
				echo "<SCRIPT LANGUAGE=JAVASCRIPT>
							function verify(){
									msg = '".t("Haluatko todella poistaa t�m�n tietueen?")."';
									return confirm(msg);
							}
					</SCRIPT>";

				echo "<br><br>
					<form action = 'yllapito.php' method = 'post' onSubmit = 'return verify()'>
					<input type = 'hidden' name = 'toim' value='$toim'>
					<input type = 'hidden' name = 'rajattu_nakyma' value = '$rajattu_nakyma'>
					<input type = 'hidden' name = 'alias_set' value = '$alias_set'>
					<input type = 'hidden' name = 'tunnus' value = '$tunnus'>
					<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
					<input type = 'hidden' name = 'del' value ='1'>
					<input type = 'submit' value = '".t("Poista $otsikko_nappi")."'></form>";
			}
		}

		echo "</td><td class='back' valign='top'>";

		if ($errori == '' and $uusi != 1 and $toim == "tuote") {
			require ("inc/tuotteen_toimittajat.inc");
		}

		if ($errori == '' and $uusi != 1 and $toim == "yhtio") {
			require ("inc/yhtion_toimipaikat.inc");
		}

		if ($errori == '' and $uusi != 1 and $toim == "toimi") {
			require ("inc/toimittajan_yhteyshenkilo.inc");
		}

		if ($errori == '' and $uusi != 1 and $toim == "sarjanumeron_lisatiedot") {
			require ("inc/sarjanumeron_lisatiedot_kuvat.inc");
		}

		echo "</td></tr></table>";

	}
	elseif ($toim != "yhtio" and $toim != "yhtion_parametrit") {
		echo "<br>
				<form action = 'yllapito.php' method = 'post'>
				<input type = 'hidden' name = 'toim' value='$toim'>
				<input type = 'hidden' name = 'uusi' value ='1'>
				<input type = 'hidden' name = 'rajattu_nakyma' value = '$rajattu_nakyma'>
				<input type = 'hidden' name = 'alias_set' value = '$alias_set'>
				<input type = 'submit' value = '".t("Uusi $otsikko_nappi")."'></form>";
	}

	require ("inc/footer.inc");
?>
