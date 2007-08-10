<?php

	require ("../inc/parametrit.inc");
    require_once ("inc/tilinumero.inc");

	$lisa = "";

	if ($tila == 'suoritus_asiakaskohdistus_kaikki') {
		//kohdistetaan t�st� kaikki helpot
		require ("suoritus_asiakaskohdistus_kaikki.php");
		$tila = "";
	}

	if ($tila == 'komm') {
		$query = "UPDATE suoritus set viesti = '$komm' WHERE tunnus='$tunnus' and yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		$tila = 'tarkenna';
	}

	if ($tila == 'tulostakuitti') {

		//Haetaan kirjoitin
		$query  = "	select komento from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kirjoitin]'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) == 0) {
			echo "<font class='error'>".t("Sinulla ei ole oletuskirjoitinta").".</font><br>";
		}
		else {
			$kirjoitinrow = mysql_fetch_array($result);
			$tulostakuitti = $kirjoitinrow["komento"];

			$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and tunnus='$asiakas_tunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$asiakasrow = mysql_fetch_array($result);

			require ("../tilauskasittely/tulosta_kuitti.inc");

			// pdff�n piirto
			$firstpage = alku();
			rivi($firstpage);
			loppu($firstpage);

			$pdffilenimi = "/tmp/kuitti-".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
			fclose($fh);

			// itse print komento...Koska ei ole luotettavaa tapaa tehd� kahta kopiota, niin printataan kahdesti
			$line = exec("$tulostakuitti $pdffilenimi");
    			$line = exec("$tulostakuitti $pdffilenimi");

			//poistetaan tmp file samantien kuleksimasta...
			$line = exec("rm -f $pdffilenimi");

			echo "<font class='message'>".t("Kuittikopio (2 kpl) tulostettu").".</font><br>";
		}

		// nollataan muuttujat niin ei mene mik��n sekasin
		$tila			= "";
		$summa			= "";
		$selite			= "";
		$asiakas_tunnus	= "";
	}

	if ($tila == "kohdista") {
			$myyntisaamiset = 0;

			// katotaan l�ytyyk� tili
			$query = "select tilino from tili where yhtio='$kukarow[yhtio]' and tilino='$vastatili'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 0) {
				echo t("Virheellinen vastatilitieto")."!";
				exit;
			}

			$query = "	SELECT *
						FROM suoritus
						WHERE tunnus = '$tunnus' and yhtio = '$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($result) == 1) {

				$suoritus = mysql_fetch_array($result);

				// Suoritus kuntoon
				$query = "	UPDATE suoritus
							SET asiakas_tunnus = '$atunnus'
							WHERE tunnus = '$tunnus' AND yhtio = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);

				// Tili�inti on voinut muuttua
				$query = "	UPDATE tiliointi
							set tilino = '$vastatili'
							where yhtio = '$kukarow[yhtio]' AND tunnus = '$suoritus[ltunnus]' AND korjattu = ''";
				$result = mysql_query($query) or pupe_error($query);

				echo "<font class='message'>".t("Suoritus kohdistettu")."!</font><br><br>";
			}
			else {
				echo "<font class='error'>".t("Suoritus kateissa")."!</font><br><br>";
				exit;
			}
			$tila = '';
	}

	if ($tila == 'tarkenna') {

		echo "<font class='head'>".t("Suorituksen kohdistaminen asiakkaaseen")."<hr></font>";

		$query = "	SELECT suoritus.yhtio,
					concat_ws('/',yriti.oletus_rahatili, yriti.nimi) tilino,
					tilino_maksaja,
					nimi_maksaja,
					viite,
					viesti,
					suoritus.summa,
					maksupvm,
					kirjpvm,
					concat_ws('/',tili.tilino, tili.nimi) vastatili,
					asiakas_tunnus,
					tili.tilino ttilino,
					yriti.oletus_selvittelytili
					FROM suoritus
					LEFT JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio AND tiliointi.tunnus = suoritus.ltunnus AND tiliointi.korjattu = '')
					LEFT JOIN tili ON (tili.yhtio = suoritus.yhtio and tili.tilino = tiliointi.tilino)
					LEFT JOIN yriti ON (yriti.yhtio = suoritus.yhtio and yriti.tilino = suoritus.tilino)
					WHERE suoritus.tunnus = $tunnus AND suoritus.yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("rahatili")." / ".t("vastatili")."</th>";
		echo "<th>".t("tilino_maksaja")."</th>";
		echo "<th>".t("nimi_maksaja")."</th>";
		echo "<th>".t("viite")."</th>";
		echo "<th>".t("viesti")."</th>";
		echo "<th>".t("summa")."</th>";
		echo "<th>".t("maksupvm")."</th>";
		echo "<th>".t("kirjpvm")."</th>";
		echo "</tr>";

		if (mysql_num_rows($result) > 0) {
			$suoritus = mysql_fetch_array ($result);

			if (!isset($haku["nimi"])) $haku["nimi"] = $suoritus['nimi_maksaja'];

			$asiakas_tunnus	= $suoritus['asiakas_tunnus'];
			$suoritus_summa	= $suoritus['summa'];
			$komm			= $suoritus['viesti'];

			echo "<tr>";
			echo "<td>$suoritus[tilino]<br>$suoritus[vastatili]</td>";
			echo "<td>$suoritus[tilino_maksaja]</td>";
			echo "<td>$suoritus[nimi_maksaja]</td>";
			echo "<td>$suoritus[viite]</td>";
			echo "<td>$suoritus[viesti]</td>";
			echo "<td>$suoritus[summa]</td>";
			echo "<td>$suoritus[maksupvm]</td>";
			echo "<td>$suoritus[kirjpvm]</td>";
			echo "<td></td>";
			echo "</tr>";
		}

		echo "</table>";

		// Mahdollisuus muuttaa viesti�
		echo "	<form action = '$PHP_SELF' method='post'>
				<input type = 'hidden' name='tunnus' value='$tunnus'>
				<input type = 'hidden' name='tila' value='komm'>
				<table>
					<tr>
						<th>".t("Lis�� kommentti suoritukselle")."</th>
						<td><input type = 'text' name = 'komm' size='40' value = '$komm'></td>
						<td class='back'><input type = 'submit' value = '".t("Lis��")."'></td>
					</tr>
				</table>
				</form><br>";

		foreach ($haku as $key => $value) {
			$old   = array("[","{","\\","|","]","}");
			$new   = array("�","�", "�","�","�","�");
			$siivottu = preg_replace('/\b(oy|ab)\b/i', '', strtolower($value));
			$siivottu = preg_replace('/^\s*/', '', $siivottu);
			$siivottu = preg_replace('/\s*$/', '', $siivottu);
			$siivottu = str_replace($old, $new, $siivottu);

			$lisa  .= " and $key like '%$siivottu%'";
			$ulisa .= "&haku[$key]=".urlencode($siivottu);
		}

		//haetaan omat asiakkaat
		$query = "	SELECT *
					FROM asiakas
					WHERE yhtio = '$kukarow[yhtio]' $lisa
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		echo "<form action = '$PHP_SELF?tunnus=$tunnus&tila=$tila' method = 'post'>";

		echo "<table>";
		echo "<tr>";

		echo "<th>".t("ytunnus")."	<br><input type='text' name = 'haku[ytunnus]' value = '$haku[ytunnus]' size='15'></th>";
		echo "<th>".t("nimi")."		<br><input type='text' name = 'haku[nimi]'    value = '$haku[nimi]' size='15'></th>";
		echo "<th>".t("postitp")."	<br><input type='text' name = 'haku[postitp]' value = '$haku[postitp]' size='15'></th>";
		echo "<th valign='top'>".t("avoimia")."<br>".t("laskuja")."</th>";
		echo "<th valign='top'>".t("saamisettili")."</th>";
		echo "<th valign='bottom'><input type='submit' value = '".t("Etsi")."'></th>";
		echo "</tr>";

		echo "</form>";

		while ($trow = mysql_fetch_array ($result)) {

			echo "<form action='$PHP_SELF' method='post'>
					<input type='hidden' name='tila' value='kohdista'>
					<input type='hidden' name='atunnus' value='$trow[tunnus]'>
					<input type='hidden' name='tunnus' value='$tunnus'>";

			echo "<tr>";
			echo "<td>$trow[ytunnus]</td>";
			echo "<td><a href='myyntilaskut_asiakasraportti.php?tila=tee_raportti&tunnus=$trow[tunnus]'>$trow[nimi]</a></td>";
			echo "<td>$trow[postino] $trow[postitp]</td>";

			// Onko asiakkaalla avoimia laskuja
			$query = "	SELECT count(*) maara
						FROM lasku USE INDEX (yhtio_tila_mapvm)
						WHERE yhtio = '$kukarow[yhtio]'
						and mapvm = '0000-00-00'
						and tila = 'U'
						and (ytunnus = '$trow[ytunnus]' or nimi = '$trow[nimi]' or liitostunnus = '$trow[tunnus]')";
			$lresult = mysql_query($query) or pupe_error($query);
			$lasku = mysql_fetch_array ($lresult);

			echo "<td>$lasku[maara]</td>";

			$sel1 = '';
			$sel2 = '';
			$sel3 = '';
			$sel4 = '';
			$sel5 = '';

			if ($suoritus['ttilino'] == $yhtiorow["myyntisaamiset"]) {
				$sel1 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow['factoringsaamiset']) {
				$sel2 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow["selvittelytili"]) {
				$sel3 = "selected";
			}
			if ($suoritus['ttilino'] == $suoritus["oletus_selvittelytili"]) {
				$sel4 = "selected";
			}
			if ($suoritus['ttilino'] == $yhtiorow["konsernimyyntisaamiset"]) {
				$sel5 = "selected";
			}

			echo "<td><select name='vastatili'>";
			echo "<option value='$yhtiorow[myyntisaamiset]' $sel1>"		.t("Myyntisaamiset").		" ($yhtiorow[myyntisaamiset])</option>";
			echo "<option value='$yhtiorow[factoringsaamiset]' $sel2>"	.t("Factoringsaamiset").	" ($yhtiorow[factoringsaamiset])</option>";
//			echo "<option value='$yhtiorow[selvittelytili]' $sel3>"		.t("Selvittelytili").		" ($yhtiorow[selvittelytili])</option>";

			if ($suoritus["oletus_selvittelytili"] != "") {
				echo "<option value='$suoritus[oletus_selvittelytili]' $sel4>".t("Pankkitilin selvittelytili")." ($suoritus[oletus_selvittelytili])</option>";
			}
			if ($trow['konserniyhtio'] != "") {
				echo "<option value='$yhtiorow[konsernimyyntisaamiset]' $sel5>".t("Konsernimyyntisaamiset")." ($yhtiorow[konsernimyyntisaamiset])</option>";
			}
			echo "</select></td>";

			echo "<td><input type='submit' value='".t("kohdista")."'></td>";
			echo "</tr>";
			echo "</form>";
		}

		echo "</table>";
	}

	if ($tila == '') {

		echo "<font class='head'>".t("Kohdistamattomien suorituksien selaus")."</font><hr>";

		echo "<form action = '$PHP_SELF?tila=$tila' method = 'post'>";

		$query = "	SELECT distinct suoritus.tilino, nimi, yriti.valkoodi
					FROM suoritus, yriti
					WHERE suoritus.yhtio = '$kukarow[yhtio]'
					AND kohdpvm = '0000-00-00'
					and yriti.yhtio=suoritus.yhtio
					and yriti.tilino=suoritus.tilino
					$lisa
					ORDER BY nimi";
		$result = mysql_query($query) or pupe_error($query);

		echo "<table>";
		echo "<tr><th>".t("N�yt� vain tapahtumat tililt�")."</th>";
		echo "<td><select name='tilino' onchange='submit()'>";
		echo "<option value=''>".t("Kaikki")."</option>\n";

		while ($row = mysql_fetch_array($result)) {
			$sel = '';
			if ($tilino == $row['tilino']) $sel = 'selected';
			echo "<option value='$row[tilino]' $sel>$row[nimi] ".tilinumero_print($row['tilino'])." $row[valkoodi]</option>\n";
		}
		echo "</select></td></tr>";

		$query = "	SELECT distinct valkoodi
					FROM suoritus
					WHERE yhtio = '$kukarow[yhtio]'
					AND kohdpvm = '0000-00-00'
					$lisa
					ORDER BY valkoodi";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<tr><th>".t("N�yt� vain tapahtumat valuutassa")."</th>";
		echo "<td><select name='valuutta' onchange='submit()'>";
		echo "<option value=''>".t("Kaikki")."</option>\n";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($valuutta == $vrow[0]) $sel = "selected";
			echo "<option value = '$vrow[0]' $sel>$vrow[0]</option>";
		}

		echo "</select></td></tr>";
		echo "</table>";

		echo "<br><font class='message'>".t("Valitse x kohdistaaksesi suorituksia asiakkaisiin tai")." <a href='$PHP_SELF?tila=suoritus_asiakaskohdistus_kaikki'>".t("t�st�")."</a> ".t("kaikki helpot").".</font><br><br>";

		$tila = '';
		$kentat = "nimi_maksaja, kirjpvm, suoritus.summa, suoritus.valkoodi, suoritus.tilino, viite, viesti, concat(asiakas.nimi, ' (', asiakas.ytunnus, ')') asiakas, tiliointi.tilino ttilino, suoritus.tunnus, asiakas_tunnus";
	    $kentankoko = array(15,10,8,5,15,15,20,10,10);
		$array = split(",", $kentat);
		$count = count($array);

		for ($i=0; $i<=$count; $i++) {
				// tarkastetaan onko hakukent�ss� jotakin
				if (strlen($haku[$i]) > 0) {
					$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
					$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
				}
		}

		if (strlen($ojarj) > 0) {
			$jarjestys = $ojarj;
		}
		else{
			$jarjestys = 'kirjpvm';
		}

		if ($tilino != "") {
			$lisa .= " and suoritus.tilino = '$tilino' ";
		}

		if ($valuutta != "") {
			$lisa .= " and suoritus.valkoodi = '$valuutta' ";
		}

		$maxrows = 500;
		$query = "	SELECT $kentat
					FROM suoritus
					LEFT JOIN asiakas ON (asiakas.yhtio = suoritus.yhtio AND asiakas.tunnus = suoritus.asiakas_tunnus)
					LEFT JOIN tiliointi ON (tiliointi.yhtio = suoritus.yhtio and tiliointi.tunnus = suoritus.ltunnus)
					WHERE suoritus.yhtio = '$kukarow[yhtio]' AND kohdpvm = '0000-00-00'  $lisa
				 	ORDER BY $jarjestys";
		$result = mysql_query($query) or pupe_error($query);

        echo "<table><tr><th>x</th>";

        for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
			$sorti = $i + 1;
        	echo "<th><a href='$PHP_SELF?tila=$tila&ojarj=".$sorti.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
        }

		echo "<th></th></tr>";
		echo "<tr><td></td>";

		for ($i = 0; $i < mysql_num_fields($result)-3; $i++) {
			echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
		}

		echo "<td></td>";
		echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";
		echo "</form>";

		$row = 0;

	    while ($maksurow=mysql_fetch_array ($result)) {

			echo "<tr class='aktiivi'>";

			for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
				switch ($i) {
				case 0:
					if ($maksurow["asiakas_tunnus"]!=0) {
						echo "<td></td><td><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>$maksurow[$i]</a></td>";
					}
					else {
						echo "<td><a href='$PHP_SELF?tunnus=$maksurow[tunnus]&tila=tarkenna'>x</a></td><td>$maksurow[$i]</td>";
					}
					break;
				case 3:
	                echo "<td>";
	                echo tilinumero_print($maksurow[$i]);
	                echo "</td>";
					break;
				case 1:
					//if ($maksurow["asiakas_tunnus"]!=0) {
						//echo "<td><a href='../crm/asiakasmemo.php?ytunnus=$maksurow[asiakas_tunnus]'>$maksurow[$i]</a></td>";
					//}
					//else {
						echo "<td>$maksurow[$i]</td>";
					//}
					break;
			 	default:
		    		echo "<td>$maksurow[$i]</td>";
		    		break;
		    	}
			}

			// tehd��n nappi kuitin tulostukseen
			echo "<form method='post' action='$PHP_SELF'>";
			echo "<input type='hidden' name='tila' value='tulostakuitti'>";
			echo "<input type='hidden' name='asiakas_tunnus' value='$maksurow[asiakas_tunnus]'>";
			echo "<input type='hidden' name='summa' value='$maksurow[summa]'>";
			echo "<input type='hidden' name='selite' value='$maksurow[viesti]'>";
			echo "<td><input type='submit' value='".t("Tulosta kuitti")."'></td></tr>";
			echo "</form>";

			$row++;
		}

		echo "</table>";
		if($row >= $maxrows) {
			echo "<br>".t("Kysely on liian iso esitett�v�ksi, ainoastaan ensimm�iset")." $maxrows ".t("rivi� on n�kyvill�. Ole hyv�, ja rajaa hakuehtoja").".";
		}
	}

	require ("inc/footer.inc");
?>
