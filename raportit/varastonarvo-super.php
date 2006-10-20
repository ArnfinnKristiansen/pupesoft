<?php

// k�ytet��n slavea jos sellanen on
$useslave = 1;

require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Varastonarvo tuotteittain")."</font><hr>";

if (!isset($pp)) $pp = date("d");
if (!isset($kk)) $kk = date("m");
if (!isset($vv)) $vv = date("Y");

// tutkaillaan saadut muuttujat
$osasto = trim($osasto);
$try    = trim($try);
$pp 	= sprintf("%02d", trim($pp));
$kk 	= sprintf("%02d", trim($kk));
$vv 	= sprintf("%04d", trim($vv));

if ($osasto == "") $osasto = trim($osasto2);
if ($try    == "")    $try = trim($try2);

// h�rski oikeellisuustzekki
if ($pp == "00" or $kk == "00" or $vv == "0000") $tee = $pp = $kk = $vv = "";

// piirrell��n formi
echo "<form action='$PHP_SELF' method='post' autocomplete='OFF'>";
echo "<table>";

echo "<tr>";
echo "<th>Sy�t� tai valitse osasto:</th>";
echo "<td><input type='text' name='osasto' size='10'></td>";

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='OSASTO'
			order by selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='osasto2'>";
echo "<option value=''>Kaikki</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($osasto == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Sy�t� tai valitse tuoteryhm�:</th>";
echo "<td><input type='text' name='try' size='10'></td>";

$query = "	SELECT distinct selite, selitetark
			FROM avainsana
			WHERE yhtio='$kukarow[yhtio]' and laji='TRY'
			order by selite+0";
$sresult = mysql_query($query) or pupe_error($query);

echo "<td><select name='try2'>";
echo "<option value=''>Kaikki</option>";

while ($srow = mysql_fetch_array($sresult)) {
	if ($try == $srow[0]) $sel = "selected";
	else $sel = "";
	echo "<option value='$srow[0]' $sel>$srow[0] $srow[1]</option>";
}

echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<th>Sy�t� vvvv-kk-pp:</th>";
echo "<td colspan='2'><input type='text' name='vv' size='7' value='$vv'><input type='text' name='kk' size='5' value='$kk'><input type='text' name='pp' size='5' value='$pp'></td>";
echo "</tr>";

echo "<tr>";
echo "<th>N�ytet��nk� tuotteet:</th>";
echo "<td colspan='2'><input type='checkbox' name='naytarivit'> (Listaus l�hetet��n s�hk�postiisi)</td>";
echo "</tr>";

echo "</table>";

echo "<br>";
echo "<input type='hidden' name='tee' value='tee'>";
echo "<input type='submit' value='Laske varastonarvot'>";
echo "</form>";

if ($tee == "tee") {

	$lisa = ""; /// no hacking
	if ($try != "")    $lisa .= "and try = '$try'";
	if ($osasto != "") $lisa .= "and osasto = '$osasto'";

	// haetaan halutut tuotteet
	$query  = "	SELECT tuoteno, osasto, try, nimitys, kehahin, epakurantti1pvm, epakurantti2pvm
				FROM tuote
				WHERE yhtio = '$kukarow[yhtio]'
				and ei_saldoa = ''
				$lisa
				ORDER BY osasto, try, tuoteno";
	$result = mysql_query($query) or pupe_error($query);
	echo "<font class='message'>".t("L�ytyi"). " ";
	flush();
	echo mysql_num_rows($result)." ".t("tuotetta")."...</font><br><br>";
	flush();

	$varvo = 0; // t�h�n summaillaan

	if ($naytarivit != "") {
		$ulos  = "osasto\t";
		$ulos .= "try\t";
		$ulos .= "tuoteno\t";
		$ulos .= "nimitys\t";
		$ulos .= "saldo\t";
		$ulos .= "kehahin\t";
		$ulos .= "vararvo\n";
	}

	while ($row = mysql_fetch_array($result)) {

	   // tuotteen m��r� varastossa nyt
	   $query = "	SELECT sum(saldo) varasto
		   			FROM tuotepaikat use index (tuote_index)
		   			WHERE yhtio = '$kukarow[yhtio]'
		   			and tuoteno = '$row[tuoteno]'";
	   $vres = mysql_query($query) or pupe_error($query);
	   $vrow = mysql_fetch_array($vres);
	   $vkpl = $vrow["varasto"];

	   // tuotteen muutos varastossa annetun p�iv�n j�lkeen
	   $query = "	SELECT sum(kpl) muutos
		   			FROM tapahtuma use index (yhtio_tuote_laadittu)
		   			WHERE yhtio = '$kukarow[yhtio]'
		   			and tuoteno = '$row[tuoteno]'
		   			and laadittu > '$vv-$kk-$pp 23:59:59'";
	   $mres = mysql_query($query) or pupe_error($query);
	   $mrow = mysql_fetch_array($mres);
	   $mkpl = $mrow["muutos"];

	   // paljon saldo oli
	   $saldo = $vkpl - $mkpl;

	   // ei haeta keskihankintahintaa eik� teh� matikkaa jos saldo oli tuolloin nolla... s��stet��n tehoja!
		if ($saldo <> 0) {

			$arvo = $row["kehahin"]; // tuotteen kehahin

			// katotaan oliko tuote silloin ep�kurantti vai ei
			// verrataan v�h�n p�iv�m��ri�. onpa vittumaista PHP:ss�!
			list($vv1,$kk1,$pp1) = split("-",$row["epakurantti1pvm"]);
			list($vv2,$kk2,$pp2) = split("-",$row["epakurantti2pvm"]);

			$epa1 = (int) date('Ymd',mktime(0,0,0,$kk1,$pp1,$vv1));
			$epa2 = (int) date('Ymd',mktime(0,0,0,$kk2,$pp2,$vv2));
			$raja = (int) date('Ymd',mktime(0,0,0,$kk, $pp, $vv ));

			// jos tuote on merkattu puoliep�kurantiksi, ja se on ajassa ennen meid�n rajausta puoliettaan arvo
			if ($row['epakurantti1pvm'] != '0000-00-00' and $epa1 <= $raja) {
				$arvo = $arvo / 2;
			}

			// jos tuote on merkattu t�ysep�kurantiksi, ja se on ajassa ennen meid�n rajausta nollataan arvo
			if ($row['epakurantti2pvm'] != '0000-00-00' and $epa2 <= $raja) {
				$arvo = 0;
			}

			// jos ollaan annettu t�m� p�iv� niin ei ajeta t�t�, koska nykyinen kehahin on oikein ja n�in on nopeempaa! wheee!
			if ($pp != date("d") or $kk != date("m") or $vv != date("Y")) {
				// katotaan mik� oli tuotteen viimeisin hinta annettuna p�iv�n� tai sitten sit� ennen
				$query = "	SELECT hinta
							FROM tapahtuma use index (yhtio_tuote_laadittu)
							WHERE yhtio = '$kukarow[yhtio]'
							and tuoteno = '$row[tuoteno]'
							and laadittu <= '$vv-$kk-$pp 23:59:59'
							and hinta <> 0
							ORDER BY laadittu desc
							LIMIT 1";
				$ares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($ares) == 1) {
					// l�ydettiin keskihankintahinta tapahtumista k�ytet��n sit�
					$arow = mysql_fetch_array($ares);
					$arvo = $arow["hinta"];
				}
				else {
					// echo "Ei l�ydetty kehahintaa tapahtumista <= $vv-$kk-$pp! K�ytet��n tuotteen $row[tuoteno] nykyist� kehahintaa!<br>";
				}
			}

			// t�m�n tuotteen varastonarvo historiasta
			$apu = $saldo * $arvo;

			// summataan varastonarvoa
			$varvo += $apu;

			if ($naytarivit != "") {
	   			$ulos .= "$row[osasto]\t";
	   			$ulos .= "$row[try]\t";
	   			$ulos .= "$row[tuoteno]\t";
	   			$ulos .= "$row[nimitys]\t";
	   			$ulos .= str_replace(".",",",$saldo)."\t";
	   			$ulos .= str_replace(".",",",$arvo)."\t";
	   			$ulos .= str_replace(".",",",$apu)."\n";
			}

		} // end saldo

	} // end while

	if ($naytarivit != "") {

		// l�hetet��n meili
		$bound = uniqid(time()."_") ;

		$header  = "From: <mailer@pupesoft.com>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;

		$content = "--$bound\n";

		$content .= "Content-Type: text/x-comma-separated-values; name=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: attachment; filename=\"".t("varastonarvo")."-$kukarow[yhtio].txt\"\n\n";

		$content .= chunk_split(base64_encode($ulos));
		$content .= "\n" ;

		$content .= "--$bound\n";

		$boob = mail($kukarow["eposti"],  "$yhtiorow[nimi] - ".t("Varastonarvo"), $content, $header);

		echo "<font class='message'>".t("L�hetet��n s�hk�posti");
		if ($boob === FALSE) echo " - ".t("Email l�hetys ep�onnistui!")."<br>";
		else echo " $kukarow[eposti].<br>";
		echo "</font><br>";
	}

	echo "<table>";
	echo "<tr><th>Pvm</th><th>Varastonarvo</th></tr>";
	echo "<tr><td>$vv-$kk-$pp</td><td align='right'>".sprintf("%.2f",$varvo)."</td></tr>";
	echo "</table>";

}

require ("../inc/footer.inc");

?>
