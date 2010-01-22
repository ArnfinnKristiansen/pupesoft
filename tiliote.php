<?php

	$ok = 0;

	// tehd��n t�ll�nen h�kkyr� niin voidaan scripti� kutsua vaikka perlist�..
	if (trim($argv[1]) == 'perl' and trim($argv[2]) != '') {

		if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

		require ("inc/connect.inc");
		require ("inc/functions.inc");

		$userfile = trim($argv[2]);
		$filenimi = $userfile;

		$ok = 1;
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Tiliotteen, LMP:n, kurssien, verkkolaskujen ja viitemaksujen k�sittely</font><hr><br><br>";
	}

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

		if (substr($tietue, 0, 9) == "<SOAP-ENV" or substr($tietue, 0, 5) == "<?xml") {
			// Finvoice verkkolasku
			fclose($fd);

			require("verkkolasku-in.php");
		}
		elseif (substr($tietue, 5, 12) == "Tilivaluutan") {
			// luetaanko kursseja

			lue_kurssit($filenimi, $fd);
			fclose($fd);
		}
		elseif (substr($tietue, 0, 7) == "VK01000") {
			// luetaanko kursseja? tyyppi kaks

			lue_kurssit($filenimi, $fd, 2);
			fclose($fd);
		}
		else {
			// T�m� oli tiliote tai viiteaineisto

			require ("inc/tilinumero.inc");

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
								WHERE tilino = '$tilino'
								and yriti.kaytossa = ''";
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
				require("myyntires/suoritus_asiakaskohdistus_kaikki.php");
			}
		}
	}


	echo "<form enctype='multipart/form-data' name='sendfile' action='$PHP_SELF' method='post'>";
	echo "<table>";
	echo "	<tr>
				<th>Pankin aineisto:</th>
				<td><input type='file' name='userfile'></td>
				<td class='back'><input type='submit' value='K�sittele tiedosto'></td>
			</tr>";
	echo "</table>";

	echo "</form>";

	require("inc/footer.inc");

?>