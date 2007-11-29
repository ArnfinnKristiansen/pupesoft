<?php
///* T�m� skripti k�ytt�� slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

// tehd��n valtiontilintarkastajille atk-tarkistusmateriaalia

echo "<font class='head'>Atk-tilintarkastus</font><hr>";

if ($alku != '') {
	list($vv, $kk, $pp) = split("-", $alku);
	if (!checkdate($kk, $pp, $vv)) {
		echo "<font class='error'>Virheellinen alkupvm $alku</font><br><br>";
		$alku = '';
	}
}

if ($loppu != '') {
	list($vv, $kk, $pp) = split("-", $loppu);
	if (!checkdate($kk, $pp, $vv)) {
		echo "<font class='error'>Virheellinen loppupvm $loppu</font><br><br>";
		$loppu = '';
	}
}

if ($alku != '' and $loppu != '') {

	/* tili�innit */

	// keksit��n uudelle failille joku varmasti uniikki nimi:
	$file1 = "tilioinnit-".md5(uniqid(rand(),true)).".txt";

	// avataan faili
	$fh = fopen("/tmp/".$file1, "w");

	// haetaan kaikki vuoden tapahtumat.. uh
	$query  = "	SELECT tiliointi.tapvm, tiliointi.tilino, tiliointi.kustp, tiliointi.kohde, tiliointi.projekti, 
				tiliointi.summa, tiliointi.vero, tiliointi.selite, tiliointi.laatija, 
				tiliointi.laadittu, lasku.ytunnus, lasku.nimi, lasku.nimitark, lasku.osoite, lasku.osoitetark, lasku.postino, lasku.postitp
				FROM tiliointi
				JOIN lasku ON lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus
				where tiliointi.yhtio	= '$kukarow[yhtio]'
				and tiliointi.tapvm    >= '$alku'
				and tiliointi.tapvm    <= '$loppu'
				and tiliointi.korjattu	= ''";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)) {

		$rivi  = sprintf("%-10.10s", $row['tapvm']);		// p�iv�m��r� 10 merkki� (vvvv-kk-pp)
		$rivi .= sprintf("%6.6s",    $row['tilino']);		// tilinumero 6 merkki�
		$rivi .= sprintf("%6.6s",    $row['kustp']);		// kustannuspaikka 6 merkki�
		$rivi .= sprintf("%6.6s",    $row['kohde']);		// kohde 6 merkki�
		$rivi .= sprintf("%6.6s",    $row['projekti']);		// projekti 6 merkki�
		$rivi .= sprintf("%13.13s",  $row['summa']);		// summa 13 merkki� (desimaalierotin piste)
		$rivi .= sprintf("%7.7s",    $row['vero']);			// vero 7 merkki� (desimaalierotin piste)
		$rivi .= sprintf("%-50.50s", $row['selite']);		// selite 50 merkki�
		$rivi .= sprintf("%-10.10s", $row['laatija']);		// laatijan nimi 10 merkki�
		$rivi .= sprintf("%-19.19s", $row['laadittu']);		// laadittuaika 19 merkki� (vvvv-kk-pp hh:mm:ss)
		$rivi .= sprintf("%-15.15s", $row['ytunnus']);		// asiakkaan/toimittajan tunniste 15 merkki�
		$rivi .= sprintf("%-45.45s", $row['nimi']);			// asiakkaan/toimittajan nimi 45 merkki�
		$rivi .= sprintf("%-45.45s", $row['nimitark']);		// asiakkaan/toimittajan nimitarkenne 45 merkki�
		$rivi .= sprintf("%-45.45s", $row['osoite']);		// asiakkaan/toimittajan osoite 45 merkki�
		$rivi .= sprintf("%-45.45s", $row['osoitetark']);	// asiakkaan/toimittajan osoitetarkenne 45 merkki�
		$rivi .= sprintf("%-15.15s", $row['postino']);		// asiakkaan/toimittajan postinumero 15 merkki�
		$rivi .= sprintf("%-45.45s", $row['postitp']);		// asiakkaan/toimittajan postitoimipaikka 45 merkki�
		$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

		if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep�onnistui!");
	}

	// suljetaan tiedosto
	fclose($fh);

	/* tilikartta */

	// keksit��n uudelle failille joku varmasti uniikki nimi:
	$file2 = "tilikartta-".md5(uniqid(rand(),true)).".txt";

	// avataan faili
	$fh = fopen("/tmp/".$file2, "w");

	// haetaan kaikki tilit
	$query  = "select * from tili where yhtio='$kukarow[yhtio]' order by tilino";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)) {

		$rivi  = sprintf("%-6.6s",   $row['tilino']);		// tilinumero 6 merkki�
		$rivi .= sprintf("%-35.35s", $row['nimi']);			// selite 35 merkki�
		$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

		if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep�onnistui!");
	}

	// suljetaan tiedosto
	fclose($fh);

	/* kustannuspaikat, projektit ja kohteet */

	// keksit��n uudelle failille joku varmasti uniikki nimi:
	$file3 = "kustprojkoht-".md5(uniqid(rand(),true)).".txt";

	// avataan faili
	$fh = fopen("/tmp/".$file3, "w");

	// haetaan kaikki kustannuspaikat
	$query  = "select * from kustannuspaikka where yhtio='$kukarow[yhtio]' and kaytossa!='E' order by tyyppi, tunnus";
	$result = mysql_query($query) or pupe_error($query);

	while($row = mysql_fetch_array($result)) {

		if ($row['tyyppi'] == 'K') {
			$tyyppi = "Kustannuspaikka";
		}
		elseif ($row['tyyppi'] == 'O') {
			$tyyppi = "Kohde";
		}
		elseif ($row['tyyppi'] == 'P') {
			$tyyppi = "Projekti";
		}
		else {
			$tyyppi = "";
		}

		$rivi  = sprintf("%-15.15s", $tyyppi);				// tyyppi 15 merkki�
		$rivi .= sprintf("%11.11s",  $row['tunnus']);		// tunnus 11 merkki�
		$rivi .= sprintf("%-35.35s", $row['nimi']);			// nimi 35 merkki�
		$rivi .= "\r\n";									// windows rivinvaihto (cr lf)

		if (fwrite($fh, $rivi) === FALSE) die("Tiedoston kirjoitus ep�onnistui!");
	}

	// suljetaan tiedosto
	fclose($fh);

	// tehd��n failista zippi
	exec("cd /tmp;/usr/bin/zip $kukarow[yhtio].zip $file1 $file2 $file3");

	$bound    = uniqid(time()."_");

	$header   = "From: <$yhtiorow[postittaja_email]>\r\n";
	$header  .= "MIME-Version: 1.0\r\n";
	$header  .= "Content-Type: multipart/mixed; boundary=\"$bound\"\r\n";

	$content  = "--$bound\r\n";

	$content .= "Content-Type: application/zip; name=\"$kukarow[yhtio].zip\"\r\n";
	$content .= "Content-Transfer-Encoding: base64\r\n";
	$content .= "Content-Disposition: inline; filename=\"$kukarow[yhtio].zip\"\r\n\r\n";

	$handle  = fopen("/tmp/$kukarow[yhtio].zip", "r");
	$sisalto = fread($handle, filesize("/tmp/$kukarow[yhtio].zip"));
	fclose($handle);

	$content .= chunk_split(base64_encode($sisalto));
	$content .= "\r\n";

	$content .= "--$bound\r\n";

	$boob     = mail($kukarow['eposti'],  $yhtiorow['nimi']." / Tilintarkistus $alku - $loppu / Ajopvm ".date("d.m.Y"), $content, $header, "-f $yhtiorow[postittaja_email]");

	if ($boob===FALSE) {
		echo "S�hk�postin l�hetys osoitteeseen $kukarow[eposti] ep�onnistui!<br><br>";
	}
	else {
		echo "L�hetettiin tiedosto s�hk�postilla osoitteeseen: $kukarow[eposti]<br><br>";
	}

	exec("rm -f /tmp/$file1 /tmp/$file2 /tmp/$file3 /tmp/$kukarow[yhtio].zip");

}

// default arvot yhtiorowlta
if (!isset($alku))  $alku  = substr($yhtiorow['tilikausi_alku'],0,4)  - 1 . substr($yhtiorow['tilikausi_alku'],4);
if (!isset($loppu)) $loppu = substr($yhtiorow['tilikausi_loppu'],0,4) - 1 . substr($yhtiorow['tilikausi_loppu'],4);

echo "<form name='vero' action='$PHP_SELF' method='post' autocomplete='off'>";
echo "<table>";
echo "<tr>";
echo "<th>Anna alkupvm</th>";
echo "<td><input type='text' name='alku' value='$alku'></td>";
echo "</tr><tr>";
echo "<th>Anna alkupvm</th>";
echo "<td><input type='text' name='loppu' value='$loppu'></td>";
echo "</tr>";
echo "</table>";
echo "<br><input type='submit' value='Aja'>";
echo "</form>";

// kursorinohjausta
$formi  = "vero";
$kentta = "alku";

require ("../inc/footer.inc");

?>