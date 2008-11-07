<?php
	require ("inc/parametrit.inc");
	echo "<font class='head'>Toimittajan verkkolaskujen siirto levylle</font><hr><br>";

	// N�ytet��n toimittajan laskuja joille lis�tietoja voi sy�tt��
	if ($tee == 'A') { 
		$query = "SELECT tosite, lasku.tunnus, ebid
					  FROM lasku, tiliointi
					  WHERE lasku.yhtio='$kukarow[yhtio]' and ytunnus='$tunnus' and ebid <> '' and
					  		tila in ('H','Y','M','P','Q') and
					  		lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and 
					  		tiliointi.tilino='$yhtiorow[ostovelat]' and tiliointi.tapvm=lasku.tapvm";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());

		echo "Haettavia laskuja on ". mysql_num_rows($result) . " kpl<br>";

		while ($laskurow=mysql_fetch_array($result)) {
			$ebid=$laskurow['ebid'];
			echo "Haen $ebid";
			
			$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
			$salasana		   = $yhtiorow['verkkosala_vas'];
			
			$timestamppi = gmdate("YmdHis")."Z";
			
			$urlhead = "http://www.verkkolasku.net";
			$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";
			
			$digest	 = md5($urlmain . "&" . $salasana);
			$url	 = $urlhead.$urlmain."&DIGEST=$digest";
			
			$sisalto = file_get_contents($url);
			$tyofile="/tmp/" . $laskurow['tunnus'];
//			$tyofile="/var/data/kuvat/" . $laskurow['tunnus'];
			$lopullinen='/tmp/' . $laskurow['tunnus'];
//			$lopullinen='/var/data/kuvat/' . $laskurow['tosite'];
			$handle = fopen("$tyofile.pdf","w+");
			fwrite($handle, $sisalto);
			fclose ($handle);
			echo "--> $laskurow[tunnus]";
			$kasky="montage -density 144 -geometry x860 -tile 1x100 " . $tyofile . ".pdf " . $lopullinen . ".gif";
			exec($kasky);
			$kasky = "rm  " . $tyofile . ".pdf";
			exec($kasky);
//			$kasky="mv " . $lopullinen . ".gif " . $lopullinen;
//			exec($kasky);
			echo "--> $lopullinen.gif<br>";
			flush();
		}
		echo "Done!";
	}

// be test begin -----------------------------------
	
	// haetaan kaikkien toimittajien kaikki laskujen kuvat
	if ($tee == 'all') { 
		$lisa='';
		if ($kausi != '') $lisa = "and DATE_FORMAT(lasku.tapvm, '%Y-%m') = '$kausi'";
		$query = "SELECT tosite, lasku.tunnus, ebid
					  FROM lasku, tiliointi
					  WHERE lasku.yhtio='$kukarow[yhtio]' and ebid <> '' $lisa and
					  		tila in ('Y','M','P','Q') and
					  		lasku.yhtio=tiliointi.yhtio and lasku.tunnus=tiliointi.ltunnus and 
					  		tiliointi.tilino='$yhtiorow[ostovelat]' and tiliointi.tapvm=lasku.tapvm";
		$result = mysql_query ($query)
				or die ("Kysely ei onnistu $query<br>".mysql_error());

		echo "Haettavia laskuja on ". mysql_num_rows($result) . " kpl<br>";

		while ($laskurow=mysql_fetch_array($result)) {
			$ebid=$laskurow['ebid'];
			echo "Haen $ebid";
			
			$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
			$salasana		   = $yhtiorow['verkkosala_vas'];
			
			$timestamppi = gmdate("YmdHis")."Z";
			
			$urlhead = "http://www.verkkolasku.net";
			$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";
			
			$digest	 = md5($urlmain . "&" . $salasana);
			$url	 = $urlhead.$urlmain."&DIGEST=$digest";
			
			$sisalto = file_get_contents($url);
			$tyofile="/tmp/" . $laskurow['tunnus'];
//			$tyofile="/var/data/kuvat/" . $laskurow['tunnus'];
			$lopullinen='/tmp/' . $laskurow['tunnus'];
//			$lopullinen='/var/data/kuvat/' . $laskurow['tosite'];
			$handle = fopen("$tyofile.pdf","w+");
			fwrite($handle, $sisalto);
			fclose ($handle);
			echo "--> $laskurow[tunnus]";
			$kasky="montage -density 144 -geometry x860 -tile 1x100 " . $tyofile . ".pdf " . $lopullinen . ".gif";
			exec($kasky);
			$kasky = "rm  " . $tyofile . ".pdf";
			exec($kasky);
//			$kasky="mv " . $lopullinen . ".gif " . $lopullinen;
//			exec($kasky);
			echo "--> $lopullinen.gif<br>";
			flush();
		}
		echo "Done!";
	}
	
	//	Siirret��n tietokantaan turvaan kaikki siirt�m�tt�m�t!
	if($tee == "siirrasiirtamattomat") {
		
				$query = "	SELECT *
							FROM lasku
							WHERE yhtio='$kukarow[yhtio]' and ebid <> '' and tila in ('Y','M','P','Q')";
				$result = mysql_query ($query)
						or die ("Kysely ei onnistu $query<br>".mysql_error());

				echo "Haettavia laskuja on ". mysql_num_rows($result) . " kpl<br>";

				while ($laskurow=mysql_fetch_array($result)) {
					$ebid=$laskurow['ebid'];
					echo "<font class='message'>K�sitell��n lasku {$laskurow["nimi"]} {$laskurow["summa"]} {$laskurow["valkoodi"]}</font><br>ebid: {$laskurow["ebid"]}<br";

					$verkkolaskutunnus = $yhtiorow['verkkotunnus_vas'];
					$salasana		   = $yhtiorow['verkkosala_vas'];

					$timestamppi = gmdate("YmdHis")."Z";

					$urlhead = "http://www.verkkolasku.net";
					$urlmain = "/view/ebs-2.0/$verkkolaskutunnus/visual?DIGEST-ALG=MD5&DIGEST-KEY-VERSION=1&EBID=$ebid&TIMESTAMP=$timestamppi&VERSION=ebs-2.0";

					$digest	 = md5($urlmain . "&" . $salasana);
					$url	 = $urlhead.$urlmain."&DIGEST=$digest";

					$sisalto = file_get_contents($url);
					if($sisalto != "") {
						$tyofile="/tmp/" . $laskurow['tunnus'].".pdf";
						
						if(file_put_contents($tyofile, $sisalto)) {
							if(tallenna_liite($tyofile, "lasku", $laskurow["tunnus"], "{$laskurow["nimi"]} {$laskurow["summa"]} {$laskurow["valkoodi"]}")) {
								$query = "	UPDATE lasku SET
												ebid = '',
												sisviesti2 = '{$laskurow["ebid"]}'
											WHERE yhtio = '{$kukarow["yhtio"]}' and tunnus = '{$laskurow["tunnus"]}'";
								$updres = mysql_query($query) or pupe_error($query);
											
								echo "<font class=''>".t("Lasku tallennettiin liitteeksi")."</font><br>";
							}
							else {
								echo "<font class='error'>".t("Laskua ei voitu tallentaa liitteeksi!")."</font><br>";
							}						
						}
						else {
							echo "<font class='error'>".t("Laskua ei voitu tallentaa!")."</font><br>";
						}
						
						unlink($tyofile);						
					}
					else {
						echo "<font class='error'>".t("Laskua ei l�ytynyt!")."</font><br>";
					}
					flush();
				}
				echo "Done!";
	}

// be test end


	if ($tee == '') {	//valitaan toimittaja jolle halutaan kohdistaa
		echo "Valitse toimittaja:<br><br>";

		if ($tila == 'S') { // S = selaussanahaku
			$lisat = "and selaus like '%" . $nimi . "%'";
		}

		if ($tila == 'N') { // N = nimihaku
			$lisat = "and nimi like '%" . $nimi . "%'";
		}

		if ($tila == 'Y') { // Y = yritystunnushaku
			$lisat = "and ytunnus = '$nimi'";
		}

		$query = "SELECT tunnus, ytunnus, nimi, postitp
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' $lisat
					ORDER BY nimi";

		$result = mysql_query ($query) or die ("Kysely ei onnistu $query");
		if (mysql_num_rows($result) == 0) {
			echo "<b>Haulla ei l�ytynyt yht��n toimittajaa</b>";
		}
		if (mysql_num_rows($result) > 20 && $tila != '') {
			echo "<b>Haulla l�ytyi liikaa toimittajia. Tarkenna hakua!</b><br><br>";
			$tila = '';
		}
		elseif(mysql_num_rows($result) <= 20) {
			echo "<table><tr>";
			for ($i = 0; $i < mysql_num_fields($result); $i++) {
				echo "<th>" . t(mysql_field_name($result,$i))."</th>";
			}
			echo "<th></th></tr>";

			while ($trow=mysql_fetch_array ($result)) {
				echo "<form action = '$PHP_SELF' method='post'>
						<tr>
						<input type='hidden' name='tunnus' value='$trow[ytunnus]'>
						<input type='hidden' name='tee' value='A'>";
				for ($i=0; $i<mysql_num_fields($result); $i++) {
					echo "<td>$trow[$i]</td>";
				}
				echo "<td><input type='Submit' value='Valitse'></td>
						</tr></form>";
			}
			echo "</table>";
			$tila = 'ei_tyhja';
		}
		if ($tila == '') {
			echo "<form name = 'valinta' action = '$PHP_SELF' method='post'>
					<td><input type = 'text' name = 'nimi'></td>
					<td><select name='tila'>
					<option value = 'N'>Toimittajan nimi
					<option value = 'S'>Toimittajan selaussana
					<option value = 'Y'>Y-tunnus
					</select>
					</td>
					<td><input type = 'submit' value = 'Valitse'></td>
					</tr></table></form>";
			$formi = 'valinta';
			$kentta = 'nimi';
		}

//be test begin --------------------------------------
		echo "<br><br><form action = '$PHP_SELF' method='post'>
						Hae kaikkien toimittajien kaikki kuvat kaudelta
						<input type='hidden' name='tunnus' value='$trow[ytunnus]'>
						<input type='hidden' name='tee' value='all'>
						<input type='text' name='kausi' value=''>
						 esim 2004-08
						<input type='Submit' value='Hae'>
						</form>";

		echo "<br><br><form action = '$PHP_SELF' method='post'>
						<input type='hidden' name='tunnus' value='$trow[ytunnus]'>
						<input type='hidden' name='tee' value='siirrasiirtamattomat'>
						<input type='Submit' value='".t("Siirr� KAIKKI noutamattomat laskut liittetiedostoiksi tietokantaan")."'>
						</form>";

// be test end----------------------------------------
	}
	
	require ("inc/footer.inc");
?>
