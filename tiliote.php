<?php

	$ok = 0;

	// tehd��n t�ll�nen h�kkyr� niin voidaan scripti� kutsua vaikka perlist�..
	if ((trim($argv[1])=='perl') and (trim($argv[2])!='')) {

		if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$userfile = trim($argv[2]);
		$filenimi = $userfile;

		$ok = 1;
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Tiliotteen, LMP:n, kurssien ja viitemaksujen k�sittely</font><hr><br><br>";
	}

	require ("inc/tilinumero.inc");

	// katotaan onko faili uploadttu
	if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$userfile = $_FILES['userfile']['name'];
		$filenimi = $_FILES['userfile']['tmp_name'];
		$ok = 1;
	}

	if ($ok == 1) {

		$fd = fopen ($filenimi, "r");

		if (!($fd)) {
			echo "<font class='message'>Tiedosto '$filenimi' ei auennut!</font>";
			exit;
		}
		$tietue = fgets($fd);

		// luetaanko kursseja?
		if (substr($tietue, 5, 12) == "Tilivaluutan") {
			lue_kurssit($filenimi, $fd);
			fclose($fd);
			require 'inc/footer.inc';
			die();
		}
		elseif (substr($tietue, 0, 7) == "VK01000") {
			lue_kurssit($filenimi, $fd, 2); // tyyppi kaks
			fclose($fd);
			require 'inc/footer.inc';
			die();
		}

		$query= "LOCK TABLE tiliotedata WRITE, yriti READ";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		// Etsit��n aineistonumero
		$query = "SELECT max(aineisto)+1 aineisto FROM tiliotedata";
		$aineistores = mysql_query($query) or pupe_error($query);
		$aineistorow = mysql_fetch_array($aineistores);

		$xtyyppi = 0;
		$virhe	 = 0;

		while (!feof($fd)) {
			$tietue = str_replace ("{", "�", $tietue);
			$tietue = str_replace ("|", "�", $tietue);
			$tietue = str_replace ("}", "�", $tietue);
			$tietue = str_replace ("[", "�", $tietue);
			$tietue = str_replace ("\\","�", $tietue);
			$tietue = str_replace ("]", "�", $tietue);
			$tietue = str_replace ("'", " ", $tietue);

			if (substr($tietue,0,3) == 'T00' or substr($tietue,0,3) == 'T03' or substr($tietue,0,1) == '0') {
				// Konekielinen tiliote
				if (substr($tietue,0,3) == 'T00') {
					$xtyyppi 	= 1;
					$alkupvm 	= dateconv(substr($tietue,26,6));
					$loppupvm 	= dateconv(substr($tietue,32,6));
					$tilino 	= substr($tietue, 9, 14);
				}

				// Laskujen maksupalvelu LMP
				if (substr($tietue,0,3) == 'T03') {
					$xtyyppi 	= 2;
					$alkupvm	= substr($tietue,38,6);
					$loppupvm 	= $alkupvm;
					$tilino 	= substr($tietue, 9, 14);
				}

				// Saapuvat viitemaksut
				if(substr($tietue,0,1) == '0') {
					$xtyyppi 	= 3;
					$alkupvm	= "20".dateconv(substr($tietue,1,6));
					$loppupvm 	= $alkupvm;

					//Luetaan tilinumero seuraavalta rivilt� ja siirret��n pointteri takaisin nykypaikkaan
					$pointterin_paikka = ftell($fd);
					$tilino 	= fgets($fd);
					$tilino 	= substr($tilino,1,14);
					fseek($fd, $pointterin_paikka);
				}

				$query = "	SELECT *
							FROM yriti
							WHERE tilino = '$tilino'";
				$yritiresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($yritiresult) != 1) {
					echo "<font class='error'> Tili� '$tilino' ei l�ytynyt!</font><br>";
					$xtyyppi = 0;
					$virhe++;
				}
				else {
					$yritirow = mysql_fetch_array ($yritiresult);
				}

				// Onko t�m� aineisto jo ajettu?
				$query= "	SELECT *
							FROM tiliotedata
							WHERE tilino = '$tilino'
							and alku 	 = '$alkupvm'
							and loppu 	 = '$loppupvm'
							and tyyppi 	 = $xtyyppi";
				$tiliotedatares = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($tiliotedatares) > 0) {
					$tiliotedatarow = mysql_fetch_array($tiliotedatares);

					if ($tiliotedatarow["aineisto"] == $aineistorow["aineisto"]) {
						echo "<font class='error'>Aineisto esiintyy tiedostossa moneen kertaan.<br>Tiedosto viallinen, ei voida jatkaa, ota yhteytt� helpdeskiin!<br>Tili = $tilino Ajalta $alkupvm - $loppupvm Yritystunnus $yritirow[yhtio]</font><br><br>";
					}
					else {
						echo "<font class='error'>T�m� aineisto on jo aiemmin k�sitelty! Tili = $tilino Ajalta $alkupvm - $loppupvm Yritystunnus $yritirow[yhtio]</font><br><br>";
					}

					$xtyyppi=0;
					$virhe++;
				}
			}

			if ($xtyyppi > 0 and $xtyyppi <= 3) {
				// Kirjoitetaan tiedosto kantaan
				$query = "INSERT into tiliotedata (yhtio, aineisto, tilino, alku, loppu, tyyppi, tieto) values ('$yritirow[yhtio]', '$aineistorow[aineisto]', '$tilino', '$alkupvm', '$loppupvm', '$xtyyppi', '$tietue')";
				$tiliotedataresult = mysql_query($query) or pupe_error($query);
			}

			$tietue = fgets($fd, 4096);
		}

		//Jos meill� tuli virheit�
		if ($virhe > 0) {
			echo "<font class='error'>Aineisto oli virheellinen. Sit� ei voitu tallentaa j�rjestelm��n.</font>";

			//Poistetaan aineistot tiliotedatasta
			$query = "DELETE FROM tiliotedata WHERE aineisto ='$aineistorow[aineisto]'";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);

			$query = "UNLOCK TABLES";
			$tiliotedataresult = mysql_query($query) or pupe_error($query);

			require("inc/footer.inc");
			exit;
		}

		$query = "UNLOCK TABLES";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);


		// K�sitell��n uudet tietueet
		$query = "	SELECT *
					FROM tiliotedata
					WHERE aineisto = '$aineistorow[0]'
					ORDER BY tunnus";
		$tiliotedataresult = mysql_query($query) or pupe_error($query);

		while ($tiliotedatarow = mysql_fetch_array($tiliotedataresult)) {
			$tietue = $tiliotedatarow['tieto'];

			if ($tiliotedatarow['tyyppi'] == 1) {
				require("inc/tiliote.inc");
			}
			if ($tiliotedatarow['tyyppi'] == 2) {
				require("inc/LMP.inc");
			}
			if ($tiliotedatarow['tyyppi'] == 3) {
				require("inc/viitemaksut.inc");
			}

			// merkataan t�m� tiliotedatarivi k�sitellyksi
			$query = "	UPDATE tiliotedata
						SET kasitelty = now()
						WHERE tunnus = '$tiliotedatarow[tunnus]'";
			$updatekasitelty = mysql_query($query) or pupe_error($query);
		}

		if ($xtyyppi == 1) {
			//echo "nyt PIT�ISI synty� vastavienti<br>";
			$tkesken = 0;
			$maara = $vastavienti;
			$kohdm = $vastavienti_valuutassa;

			echo "<tr><td colspan = '6'>";
			require("inc/teeselvittely.inc");
			echo "</td></tr>";
			echo "</table>";
		}

		if ($xtyyppi == 2) {
			$tkesken = 0;
			$maara = $vastavienti;
			$kohdm = $vastavienti_valuutassa;

			require("inc/teeselvittely.inc");
			echo "</table>";
		}

		if ($xtyyppi == 3) {
			require("inc/viitemaksut_kohdistus.inc");
		}
	}
	else {
		echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>";

		echo "<table>";
		echo "	<tr>
					<th>Pankin aineisto:</th>
					<td><input type='file' name='userfile'></td>
					<td><input type='submit' value='K�sittele tiedosto'></td>
				</tr>";
		echo "</table>";

		echo "</form>";
	}

	require("inc/footer.inc");

function lue_kurssit($file, $handle, $tyyppi = '') {
	global $yhtiorow, $kukarow;

	ini_set("auto_detect_line_endings", 1);
	// luetaan koko file arrayhyn
	$rivit = file($file);

	if ($tyyppi == 2) {
		// eka rivi pois
		array_shift($rivit);
	}
	else {
		// 2 ekaa rivi� pois
		array_shift($rivit);
		array_shift($rivit);
	}

	$valuutat = array();

	foreach ($rivit as $rivi) {

		if ($tyyppi == 2) {
			$valuutta      = substr($rivi, 25, 3);																// valuutan nimi
			$vastavaluutta = substr($rivi, 28, 3);																// vastavaluutta
			$kurssipvm_vv  = substr($rivi, 7, 4);
			$kurssipvm_kk  = substr($rivi, 11, 2);
			$kurssipvm_pp  = substr($rivi, 13, 2);
			$kurssipvm     = "$kurssipvm_vv-$kurssipvm_kk-$kurssipvm_pp";
			$kurssi        = (float) substr($rivi, 31, 13) / 10000000;											// kurssi
		}
		else {
			$valuutta      = substr($rivi, 0, 3);																// valuutan nimi
			$vastavaluutta = "EUR";																				// vastavaluutta
			$kurssipvm_vv  = substr($rivi, 86, 4);
			$kurssipvm_kk  = substr($rivi, 83, 2);
			$kurssipvm_pp  = substr($rivi, 80, 2);
			$kurssipvm     = "$kurssipvm_vv-$kurssipvm_kk-$kurssipvm_pp";
			$kurssi        = (float) str_replace(array(',', ' '), array('.',''), trim(substr($rivi, 5, 20)));	// kurssi
		}

		// ei p�ivitet� jos ollaan jo p�ivitetty tai v��r� vastavaluutta
		if (in_array($valuutta, $valuutat) or $vastavaluutta != $yhtiorow["valkoodi"]) {
			continue;
		}

		$query = "	UPDATE valuu SET
					kurssi = round(1 / $kurssi, 9),
					muutospvm = now(),
					muuttaja = '{$kukarow['kuka']}'
					WHERE yhtio = '{$kukarow['yhtio']}' AND
					nimi = '$valuutta'";
		$result = mysql_query($query) or pupe_error($query);

		// t�m� valuutta on nyt p�ivitetty!
		$valuutat[] = $valuutta;

		if (mysql_affected_rows() != 0) {
			echo "<font class='message'>P�ivitettiin kurssi valuutalle $valuutta: ".round(1/$kurssi, 9)."</font><br>";
		}

		$query = "	INSERT INTO valuu_historia (kotivaluutta, valuutta, kurssi, kurssipvm)
					VALUES ('$vastavaluutta', '$valuutta', round(1 / $kurssi, 9), '$kurssipvm')
		  			ON DUPLICATE KEY UPDATE kurssi = round(1 / $kurssi, 9)";
		$result = mysql_query($query) or pupe_error($query);

	}
}

?>
