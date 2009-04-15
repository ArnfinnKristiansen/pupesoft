<?php

include '../inc/parametrit.inc';

if ($toim == "TRATTA") {
	echo "<font class='head'>".t("Selaa trattoja")."</font><hr />";
	$tyyppi = "T";
}
else {
	echo "<font class='head'>".t("Selaa karhuja")."</font><hr />";
	$tyyppi = "";
}

echo "<form name='karhu_selaa' action='' method='post'>
		<table>
		<tr>
			<th>".t("Ytunnus")."</th><td><input type='text' name='ytunnus'></td>
		</tr>
		<tr>
			<th>".t("Laskunro")."</th><td><input type='text' name='laskunro'></td>
			<td class='back'><input type='submit' name='tee1' value='".t("Hae")."'></td>
			<td class='back'><input type='submit' name='tee2' value='".t("N�yt� kaikki avoimet")."'></td>
		</tr>
		</table>
		</form>";

if (isset($_POST['tee1']) or isset($_POST['tee2'])) {

	if (!empty($_POST['laskunro'])) {
		$where  = sprintf("and lasku.laskunro = %d", (int) $_POST['laskunro']);
		$malisa = " ";
		$limit  = "GROUP BY karhu_lasku.ktunnus ORDER BY tunnus desc LIMIT 1";
	}
	elseif (!empty($_POST['ytunnus'])) {
		$where  = sprintf("and lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
		$malisa = " ";
		$limit  = "ORDER BY tunnus desc LIMIT 1";
	}
	else {
		$where  = " and lasku.mapvm = '0000-00-00'";
		$malisa = " and lasku.mapvm = '0000-00-00' ";
		$limit  = "";
	}

	// haetaan uusin karhukierros/karhukerta
	$query = "	SELECT ifnull(group_concat(distinct karhu_lasku.ktunnus), 0) as tunnus, ifnull(group_concat(distinct liitostunnus), 0) as liitostunnus
				FROM karhu_lasku
				JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.yhtio = '$kukarow[yhtio]' $where)
				JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = lasku.yhtio and karhukierros.tyyppi = '$tyyppi')
				$limit";
	$res = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($res) > 0) {

		$ktunnus = mysql_fetch_array($res);

		echo "<br>
			<table>
				<tr>
					<th>".t('Kierros')."</th>
					<th>".t('Ytunnus')."<br>".t('Asiakas')."</th>
					<th>".t('Laskunro')."</th>
					<th>".t('Summa')."</th>
					<th>".t('Maksettu')."</th>
					<th>".t('Laskun er�p�iv�')."</th>";

		if ($toim == "TRATTA") {
			echo "<th>".t('Tratta pvm')."<br>".t('Er�p�iv�')."</th>";
			echo "<th>".t('Trattakertoja')."</th>";
		}
		else {
			echo "<th>".t('Karhuamis pvm')."<br>".t('Er�p�iv�')."</th>";
			echo "<th>".t('Karhukertoja')."</th>";
		}

		echo "</tr>";

		$query = "	SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu, lasku.liitostunnus, karhu_lasku.ktunnus,
					if(lasku.nimi != lasku.toim_nimi and lasku.toim_nimi != '', concat_ws('<br>', lasku.nimi, lasku.toim_nimi), lasku.nimi) nimi,
					karhukierros.pvm, lasku.erpcm, lasku.mapvm, lasku.ytunnus, karhu_lasku.ltunnus
					FROM karhu_lasku
					JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus in ($ktunnus[liitostunnus]))
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
					WHERE karhu_lasku.ktunnus in ($ktunnus[tunnus])
					$malisa
					ORDER BY ytunnus, pvm, laskunro";
		$res = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($res)) {

			$query = "	SELECT count(distinct ktunnus) as kertoja
						FROM karhu_lasku
						JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus AND karhukierros.tyyppi = '$tyyppi')
						WHERE ltunnus={$row['ltunnus']}";					
			$ka_res = mysql_query($query);
			$karhuttu = mysql_fetch_array($ka_res);

			$query = "	SELECT group_concat(karhu_lasku.ltunnus) laskutunnukset
						FROM karhu_lasku
						JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = $row[liitostunnus])
						JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
						WHERE karhu_lasku.ktunnus = '$row[ktunnus]'";
			$la_res = mysql_query($query) or pupe_error($query);
			$tunnukset = mysql_fetch_array($la_res);

			if ($toim == "TRATTA") {
				$yhtiorow['karhuerapvm'] = 7; // t�m� on hardcoodattu tratan tulostukseen
			}

			if ($yhtiorow['karhuerapvm'] > 0) {
				$paiva = substr($row["pvm"], 8, 2);
				$kuu   = substr($row["pvm"], 5, 2);
				$year  = substr($row["pvm"], 0, 4);
				
				$erapaiva = tv1dateconv(date("Y-m-d",mktime(0, 0, 0, $kuu, $paiva+$yhtiorow['karhuerapvm'], $year)));
			}
			else {
				$erapaiva = t("HETI");
			}

			echo "<tr>
					<td valign='top'>$row[ktunnus]</td>	
					<td valign='top'>$row[ytunnus]<br>$row[nimi]</td>
					<td valign='top'><a href = '../tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=$row[laskunro]'>$row[laskunro]</a></td>
					<td valign='top' style='text-align: right;'>$row[summa]</td>";
					
					
			if ($row["mapvm"] != "0000-00-00") {
				echo "	<td valign='top'>".tv1dateconv($row["mapvm"])."</td>";
			}
			else {			
				echo "	<td valign='top' style='text-align: right;'>$row[saldo_maksettu]</td>";
			}
			
			
			echo "	<td valign='top'>".tv1dateconv($row['erpcm'])."</td>
					<td valign='top'>".tv1dateconv($row['pvm'])."<br>$erapaiva</td>
					<td valign='top' style='text-align: right;'>$karhuttu[kertoja]<br>";

				if ($toim == "TRATTA") {
					echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$row[ktunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_tratta&nayta_pdf=1'>N�yt� tratta</a><br>";
				}
				else {
					echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$row[ktunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_karhu&nayta_pdf=1'>N�yt� karhu</a><br>";
				}
					
			echo "</td>
				</tr>";
		}

		echo "</table>";

	}
	else {
		echo "<br><font class='message'>Yht��n laskua ei l�ytynyt!</font>";
	}
}

include '../inc/footer.inc';

?>