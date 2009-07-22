<?php
	///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
	$useslave = 1;
	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Merivakuutuslistaus")."</font><hr>";

	if ($tee != '') {

		$query = "	SELECT tunnus, nimi, ytunnus, toimitusehto, kuljetusmuoto,
					round(summa + rahti + (rahti_etu * if(maksu_kurssi=0, vienti_kurssi, maksu_kurssi)) + rahti_huolinta,2) arvo,
					round((summa + rahti + (rahti_etu * if(maksu_kurssi=0, vienti_kurssi, maksu_kurssi)) + rahti_huolinta) * 1.10,2) vakarvo,
					round(((summa + rahti + (rahti_etu * if(maksu_kurssi=0, vienti_kurssi, maksu_kurssi)) + rahti_huolinta) * 1.10) * 0.001114 ,2) fenarvo
					FROM lasku
					WHERE yhtio='$kukarow[yhtio]'
					and tapvm >='$vva-$kka-$ppa 00:00:00'
					and tapvm <='$vvl-$kkl-$ppl 23:59:59'
					and tila in ('H','Y','M','P','Q','K')
					and vienti in ('C','F','I')
					and kuljetusmuoto='$kuljetusmuoto'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table><tr>
				<th>".t("Tilaus")."</th>
				<th>".t("Toimittaja")."</th>
				<th>".t("Ytunnus")."</th>
				<th>".t("Toimitusehto")."</th>
				<th>".t("Kuljetusmuoto")."</th>
				<th>".t("Arvo")."</th>
				<th>".t("Vakuutusarvo")."</th>
				<th>".t("Fennia-arvo")."</th>";
		echo "</tr>";

		$arvo	 = 0;
		$vakarvo = 0;
		$fenarvo = 0;

		while ($row = mysql_fetch_array($result)) {

			echo "<tr>";
			echo "<td>$row[tunnus]</td>";
			echo "<td>$row[nimi]</td>";
			echo "<td>$row[ytunnus]</td>";
			echo "<td>$row[toimitusehto]</td>";

			$kmresult = t_avainsana("KM", "", "and avainsana.selite = '$row[kuljetusmuoto]'");
			$kmrow = mysql_fetch_array($kmresult);

			echo "<td>$kmrow[selite] - $kmrow[selitetark]</td>";

			echo "<td>$row[arvo]</td>";
			echo "<td>$row[vakarvo]</td>";
			echo "<td>$row[fenarvo]</td>";
			echo "</tr>";


			$arvo	 += $row["arvo"];
			$vakarvo += $row["vakarvo"];
			$fenarvo += $row["fenarvo"];
		}

		echo "<tr>";
		echo "<td class='back' colspan='4'></td>";
		echo "<th>".t("Yhteens�")."</th>";
		echo "<th>$arvo</th>";
		echo "<th>$vakarvo</th>";
		echo "<th>$fenarvo</th>";
		echo "</tr>";

		echo "</table>";
	}


	//K�ytt�liittym�
	echo "<br><br><br>";
	echo "<table><form method='post' action='$PHP_SELF'>";

	// ehdotetaan 7 p�iv�� taaksep�in
	if (!isset($kka))
		$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($vva))
		$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
	if (!isset($ppa))
		$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));

	if (!isset($kkl))
		$kkl = date("m");
	if (!isset($vvl))
		$vvl = date("Y");
	if (!isset($ppl))
		$ppl = date("d");

	echo "<input type='hidden' name='tee' value='kaikki'>";

	echo "<tr><th>".t("Kuljetusmuoto").":</th><td colspan='3'>
					<select NAME='kuljetusmuoto'>";

	$result = t_avainsana("KM");
	
	while($row = mysql_fetch_array($result)){
		$sel = '';
		if($row["selite"] == $kuljetusmuoto) {
			$sel = 'selected';
		}
		echo "<option value='$row[selite]' $sel>$row[selitetark]</option>";
	}
	echo "</select></td>";


	echo "<tr><th>".t("Sy�t� alkup�iv�m��r�")."</th>
			<td><input type='text' name='ppa' value='$ppa' size='3'></td>
			<td><input type='text' name='kka' value='$kka' size='3'></td>
			<td><input type='text' name='vva' value='$vva' size='5'></td>
			</tr><tr><th>".t("Sy�t� loppup�iv�m��r�")."</th>
			<td><input type='text' name='ppl' value='$ppl' size='3'></td>
			<td><input type='text' name='kkl' value='$kkl' size='3'></td>
			<td><input type='text' name='vvl' value='$vvl' size='5'></td>";
	echo "<td class='back'><input type='submit' value='".t("Aja raportti")."'></td></tr></table>";

	require ("../inc/footer.inc");

?>