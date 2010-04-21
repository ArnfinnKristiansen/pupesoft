<?php

	require ("inc/parametrit.inc");

	if (!isset($tyyppi)) $tyyppi='t';

	echo "<font class='head'>".t("Toimittajan tili�intis��nn�t")."</font><hr>";

	if ($tee == 'S' or $tee == 'N' or $tee == 'Y') {

		if ($tee == 'S') { // S = selaussanahaku
			$lisat = "and selaus like '%" . $nimi . "%'";
		}

		if ($tee == 'N') { // N = nimihaku
			$lisat = "and nimi like '%" . $nimi . "%'";
		}

		if ($tee == 'Y') { // Y = yritystunnushaku
			$lisat = "and ytunnus = '$nimi'";
		}

		$query = "	SELECT tunnus, ytunnus, nimi, postitp
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' $lisat
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n toimittajaa")."</font>";
		}

		if (mysql_num_rows($result) > 40) {
			echo "<font class='error'>".t("Haulla l�ytyi liikaa toimittajia. Tarkenna hakua")."</font>";
		}
		else {
			echo "<table><tr>";
			for ($i = 1; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th></th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<form action = '$PHP_SELF' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[0]'>";
				for ($i=1; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "<td><input type='submit' value='".t("Valitse")."'></td></tr></form>";
			}
			echo "</table>";
			exit;
		}
	}

	if ($tee == 'P') {
		// Olemassaolevaa s��nt�� muutetaan, joten poistetaan rivi ja annetaan perustettavaksi
		$query = "	SELECT *
					FROM tiliointisaanto
					WHERE tunnus = '$rtunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo t("Tili�intis��nt�� ei l�ydy")."! $query";
			exit;
		}

		$tiliointirow = mysql_fetch_array($result);
		$mintuote	= $tiliointirow['mintuote'];
		$maxtuote	= $tiliointirow['maxtuote'];
		$kuvaus		= $tiliointirow['kuvaus'];
		$kuvaus2	= $tiliointirow['kuvaus2'];
		$tilino		= $tiliointirow['tilino'];
		$kustp		= $tiliointirow['kustp'];
		$ok 		= 1;

		$query = "DELETE from tiliointisaanto WHERE tunnus = '$rtunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}

	if ($tee == 'U') {

		$query = "	SELECT tunnus
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]'
					$lisat
					ORDER BY selaus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			$virhe	= "<font class='error'>".t("Haulla ei l�ytynyt yht��n toimittajaa")."</font>";
			$ok		= 1;
			$tee	= '';
		}

		if ($kustp != 0) {
			$query = "	SELECT tunnus
						FROM kustannuspaikka
						WHERE tunnus = '$kustp'
						and yhtio = '$kukarow[yhtio]'
						and tyyppi = 'K'
						and kaytossa != 'E'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$virhe = "<font class='error'>".t("Kustannuspaikkaa ei l�ydy")."!</font>";
				$ok = 1;
				$tee = '';
			}
		}

		// Tarkistetaan s��nt�
		if ($tyyppi == 't') {

			if ($mintuote != '' and $maxtuote == '') $maxtuote = $mintuote;
			if ($maxtuote != '' and $mintuote == '') $mintuote = $maxtuote;

			if ($mintuote != '') {
				if ($mintuote > $maxtuote) {
					$virhe	= "<font class='error'>".t("Minimituote on pienempi kuin maksimituote")."!</font>";
					$ok		= 1;
					$tee	= '';
				}
			}

			$query = "	SELECT tilino
						FROM tili
						WHERE tilino = '$tilino' and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				$virhe = "<font class='error'>".t("Tili� ei l�ydy")."!</font>";
				$ok = 1;
				$tee = '';
			}

			//Onko t�lle v�lille jo s��nt�?
			if ($mintuote != '') {
				$query = "	SELECT mintuote, maxtuote
							FROM tiliointisaanto
							WHERE ttunnus = '$tunnus'
							and yhtio = '$kukarow[yhtio]'
							and mintuote <= '$mintuote'
							and maxtuote >= '$mintuote'
							and tilino != 0";
				$result = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($result) == 0) {
					$query = "	SELECT mintuote, maxtuote
								FROM tiliointisaanto
								WHERE ttunnus = '$tunnus'
								and yhtio = '$kukarow[yhtio]'
								and mintuote <= '$maxtuote'
								and maxtuote >= '$maxtuote'
								and tilino != 0";
					$result = mysql_query($query) or pupe_error($query);

					if (mysql_num_rows($result) != 0) {
						$virhe = "<font class='error'>".t("T�lle v�lille on jo s��nt�")." 1</font>";
						$ok = 1;
						$tee = '';
					}
				}
				else {
					$virhe = "<font class='error'>".t("T�lle v�lille on jo s��nt�")." 2</font>";
					$ok = 1;
					$tee = '';
				}
			}
		}
		elseif ($tyyppi == 'a') {
			if ($mintuote != '' or $maxtuote != '' or $tilino != '') {
				$virhe = t("Sis�inen virhe")."!";
				$ok = 1;
				$tee = '';
			}
			elseif ($kuvaus == '') {
				$virhe = t("Asiakastunnnus on pakollinen tieto")."!";
				$ok = 1;
				$tee = '';
			}
		}
	}

	if ($tee == 'U') {
		// Lis�t��n s��nt�
		$query = "INSERT into tiliointisaanto VALUES (
				'$kukarow[yhtio]',
				'$tyyppi',
				'$tunnus',
				'$mintuote',
				'$maxtuote',
				'$kuvaus',
				'$kuvaus2',
				'$tilino',
				'$kustp',
				'')";
		$result = mysql_query($query) or pupe_error($query);
	}

	if (isset($tunnus) and $tunnus > 0) {
		// Toimittaja on valittu ja sille annetaan s��nt�j�
		$query = "	SELECT ytunnus, concat_ws(' ', nimi, nimitark) nimi, concat_ws(' ', postino, postitp) osoite
					FROM toimi
					WHERE tunnus = '$tunnus'
					and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Toimittaja katosi")."</font><br>";
			exit;
		}

		echo "<table><tr>";
		for ($i = 0; $i < mysql_num_fields($result); $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($toimittajarow = mysql_fetch_array($result)) {
			for ($i=0; $i<mysql_num_fields($result); $i++) {
				echo "<td>$toimittajarow[$i]</td>";
			}
		}
		echo "</tr></table><br>";

		$sel[] = array();
		$sel[$tyyppi] = "SELECTED";

		echo "<font class='head'>".t("S��nn�t")."</font><hr>
				<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tunnus' value='$tunnus'>
				<table>
				<tr>
				<th>".t("Valitse tili�intis��nn�n tyyppi").":</th>
				<td><select name='tyyppi' onchange='submit();'>
				<option value='t' $sel[t]>1 ".t("Tuotes��nn�t")."</option>
				<option value='o' $sel[o]>2 ".t("Toimitusosoiteet")."</option>
				<option value='a' $sel[a]>3 ".t("Asiakastunnukset")."</option>
				<option value='k' $sel[k]>4 ".t("Kauttalaskutukset")."</option>
				</select></td>
				<td class='back'><input type='submit' value='".t("N�yt�")."'></td>
				</tr>
				</table>
				</form><br><br>
				<table>";

		// N�ytet��n vanhat s��nn�t muutosta varten
		if ($tyyppi == 't') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.mintuote, tiliointisaanto.maxtuote, tiliointisaanto.kuvaus, concat(tili.tilino,'/',tili.nimi) tilinumero, kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp
						FROM tiliointisaanto
						LEFT JOIN tili ON tili.yhtio = tiliointisaanto.yhtio and tili.tilino = tiliointisaanto.tilino
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus	= '$tunnus'
						and tiliointisaanto.tyyppi 		= 't'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						order by tiliointisaanto.mintuote";
		}
		elseif ($tyyppi == 'o') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Nimi, tiliointisaanto.kuvaus2 Osoite, tiliointisaanto.mintuote Postino, tiliointisaanto.maxtuote Postitp, kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= 'o'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						order by tiliointisaanto.kuvaus";
		}
		elseif ($tyyppi == 'a') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Asiakastunnus, kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= 'a'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						and tiliointisaanto.tilino 		= 0";
		}
		elseif ($tyyppi == 'k') {
			$query = "	SELECT tiliointisaanto.tunnus, tiliointisaanto.kuvaus Kauttalaskutus, kustannuspaikka.nimi Kustannuspaikka, tiliointisaanto.kustp
						FROM tiliointisaanto
						LEFT JOIN kustannuspaikka ON tiliointisaanto.yhtio = kustannuspaikka.yhtio and tiliointisaanto.kustp = kustannuspaikka.tunnus
						WHERE tiliointisaanto.ttunnus 	= '$tunnus'
						and tiliointisaanto.tyyppi 		= 'k'
						and tiliointisaanto.yhtio 		= '$kukarow[yhtio]'
						order by tiliointisaanto.kuvaus";
		}

		$result = mysql_query($query) or pupe_error($query);

		echo "<tr>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";

		while ($tiliointirow = mysql_fetch_array($result)) {
			echo "<tr>";
			for ($i = 1; $i<mysql_num_fields($result)-1; $i++) {
				echo "<td>$tiliointirow[$i]</td>";
			}
			echo "<td class='back'>
					<form action = '$PHP_SELF' method='post'>
					<input type='hidden' name='tunnus' value = '$tunnus'>
					<input type='hidden' name='rtunnus' value = '$tiliointirow[0]'>
					<input type='hidden' name='tee' value = 'P'>
					<input type='hidden' name='tyyppi' value = '$tyyppi'>
					<input type='Submit' value = '".t("Muuta tili�intis��nt��")."'>
				</td></tr></form>";
		}

		// Annetaan mahdollisuus tehd� uusi tili�inti
		if ($ok != 1) {
			// Annetaan tyhj�t tiedot, jos rivi oli virheet�n
			$maxtuote	= '';
			$mintuote	= '';
			$kuvaus		= '';
			$kuvaus2	= '';
			$kustp		= '';
			$tilino		= '';
		}

		$query = "	SELECT tunnus, nimi
					FROM kustannuspaikka
					WHERE yhtio = '$kukarow[yhtio]'
					and tyyppi = 'K'
					and kaytossa <> 'E'
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		$ulos = "<select name = 'kustp'><option value = ' '>".t("Ei kustannuspaikkaa")."</option>";

		while ($kustannuspaikkarow=mysql_fetch_array ($result)) {
			$valittu = "";
			if ($kustannuspaikkarow[0] == $kustp) {
				$valittu = "selected";
			}
			$ulos .= "<option value = '$kustannuspaikkarow[0]' $valittu>$kustannuspaikkarow[1]</option>";
		}
		$ulos .= "</select><br>";

		echo "<tr>
				<form action = '$PHP_SELF' method='post'>
				<input type='hidden' name='tee' value = 'U'>
				<input type='hidden' name='tunnus' value = '$tunnus'>
				<input type='hidden' name='tyyppi' value = '$tyyppi'>";

		if ($tyyppi == 't') {
			echo "	<td><input type='text' name='mintuote' size='15' value = '$mintuote'></td>
					<td><input type='text' name='maxtuote' size='15' value = '$maxtuote'></td>
					<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>
					<td><input type='text' name='tilino' size='5' value = '$tilino'></td>";
		}
		elseif ($tyyppi == 'o') {
			echo "	<td><input type='text' name='kuvaus' size='35' value = '$kuvaus'></td>
					<td><input type='text' name='kuvaus2' size='35' value = '$kuvaus2'></td>
					<td><input type='text' name='mintuote' size='15' value = '$mintuote'></td>
					<td><input type='text' name='maxtuote' size='15' value = '$maxtuote'></td>";
		}
		elseif ($tyyppi == 'a') {
			echo "	<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>";
		}
		elseif ($tyyppi == 'k') {
			echo "	<td><input type='text' name='kuvaus' size='15' value = '$kuvaus'></td>";
		}

		echo "<td>$ulos</td><td class='back'><input type='Submit' value = '".t("Lis�� tili�intis��nt�")."'>$virhe</td>";
		echo "</tr></form></table>";
	}
	else {
		// T�ll� ollaan, jos olemme vasta valitsemassa toimittajaa
		echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
				<table>
				<td>".t("Valitse toimittaja")."</td>
				<td><input type = 'text' name = 'nimi'></td>
				<td><select name='tee'><option value = 'N'>".t("Toimittajan nimi")."
				<option value = 'S'>".t("Toimittajan selaussana")."
				<option value = 'Y'>".t("Y-tunnus")."
				</select>
				</td>
				<td><input type = 'submit' value = '".t("Valitse")."'></td>
				</tr>
				</table>
				</form>";

		$formi = 'valinta';
		$kentta = 'nimi';
	}

	require ("inc/footer.inc");

?>