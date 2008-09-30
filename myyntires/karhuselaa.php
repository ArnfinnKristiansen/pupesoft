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

?>

<form name="karhu_selaa" action="" method="post">
<table>
	<tr>
		<th><?php echo t('Ytunnus') ?>:</th><td><input type="text" name="ytunnus"></td>
	</tr>
	<tr>
		<th><?php echo t('Laskunro') ?>:</th><td><input type="text" name="laskunro"></td>
		<td class="back"><input type="submit" name="tee" value="Hae"></td>
	</tr>
</table>
</form>

<?php

if (isset($_POST['tee']) and $_POST['tee'] == 'Hae') {

	if (!empty($_POST['laskunro'])) {
		$where = sprintf("and lasku.laskunro = %d", (int) $_POST['laskunro']);
		$limit = "GROUP BY karhu_lasku.ktunnus ORDER BY tunnus desc LIMIT 1";
	}
	elseif (!empty($_POST['ytunnus'])) {
		$where = sprintf("and lasku.ytunnus = '%s'", (int) $_POST['ytunnus']);
		$limit = "ORDER BY tunnus desc LIMIT 1";
	}
	else {
		$where = "and lasku.mapvm = '0000-00-00'";
		$limit = "";
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
					<th>".t('Ytunnus')."</th>
					<th>".t('Asiakas')."</th>
					<th>".t('Laskunro')."</th>
					<th>".t('Summa')."</th>
					<th>".t('Maksettu')."</th>";

		if ($toim == "TRATTA") {
			echo "<th>".t('Tratta pvm')."</th>";
			echo "<th>".t('Er�p�iv�')."</th>";
			echo "<th>".t('Trattakertoja')."</th>";
		}
		else {
			echo "<th>".t('Karhuamis pvm')."</th>";
			echo "<th>".t('Er�p�iv�')."</th>";
			echo "<th>".t('Karhukertoja')."</th>";
		}

		echo "</tr>";

		$query = "	SELECT lasku.laskunro, lasku.summa, lasku.saldo_maksettu, lasku.liitostunnus, karhu_lasku.ktunnus,
					if(lasku.nimi != lasku.toim_nimi and lasku.toim_nimi != '', concat_ws('<br>', lasku.nimi, lasku.toim_nimi), lasku.nimi) nimi,
					karhukierros.pvm, lasku.erpcm, lasku.ytunnus, karhu_lasku.ltunnus
					FROM karhu_lasku
					JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus in ($ktunnus[liitostunnus]))
					JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
					WHERE karhu_lasku.ktunnus in ($ktunnus[tunnus])
					ORDER BY ytunnus, pvm, laskunro";
		$res = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($res)) {

			$query = "SELECT count(distinct ktunnus) as kertoja from karhu_lasku where ltunnus={$row['ltunnus']}";
			$ka_res = mysql_query($query);
			$karhuttu = mysql_fetch_array($ka_res);

			$query = "	SELECT group_concat(karhu_lasku.ltunnus) laskutunnukset
						FROM karhu_lasku
						JOIN lasku ON (lasku.tunnus = karhu_lasku.ltunnus and lasku.liitostunnus = $row[liitostunnus])
						JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus and karhukierros.yhtio = '$kukarow[yhtio]' and karhukierros.tyyppi = '$tyyppi')
						WHERE karhu_lasku.ktunnus = '$row[ktunnus]'";
			$la_res = mysql_query($query) or pupe_error($query);
			$tunnukset = mysql_fetch_array($la_res);

			echo "<tr>
					<td valign='top'>$row[ktunnus]</td>	
					<td valign='top'>$row[ytunnus]</td>
					<td valign='top'>$row[nimi]</td>
					<td valign='top'>$row[laskunro]</td>
					<td valign='top'>$row[summa]</td>
					<td valign='top'>$row[saldo_maksettu]</td>
					<td valign='top'>".tv1dateconv($row['pvm'])."</td>
					<td valign='top'>".tv1dateconv($row['erpcm'])."</td>
					<td valign='top' style='text-align: right;'>$karhuttu[kertoja]</td>
					<td valign='top' class='back'>";

				if ($toim == "TRATTA") {
					echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$row[ktunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_tratta&nayta_pdf=1'>N�yt� tratta</a><br>";
				}
				else {
					echo " <a href='".$palvelin2."muutosite.php?karhutunnus=$row[ktunnus]&lasku_tunnus[]=$tunnukset[laskutunnukset]&tee=tulosta_karhu&nayta_pdf=1'>N�yt� karhu</a><br>";
				}
					
			echo "	</td>
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