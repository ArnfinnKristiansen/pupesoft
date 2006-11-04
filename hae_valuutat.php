<?php

	require ("inc/parametrit.inc");

	echo "<font class='head'>Valuuttakurssien p�ivitys<hr></font>";

	ob_start();

	$val_palautus = @readfile("http://www.suomenpankki.fi/ohi/fin/0_new/0.1_valuuttak/fix-rec.txt");

	if ($val_palautus !== FALSE) {
		$val_palautus = ob_get_contents();
	}
	else {
		unset ($val_palautus);
	}

	ob_end_clean();

	if (isset($val_palautus)) {

		// splitataan rivit rivinvaihdosta
		$rivit = explode("\n",$val_palautus);

		// k�yd��n l�pi riveitt�in
		foreach ($rivit as $rivi) {

			// splitataan rivi spacesta
			$arvot = explode(" ", $rivi);

			if ((float) $arvot[2] != 0) {
				// haetaan valuuttakoodi
				$valuutta = explode("/", $arvot[1]);

				// haetaan kurssi
				$kurssi = round(1 / (float) $arvot[2], 6);

				// varmistetaan, ett� oli yhti� kurssi on sama ku tuli boffin saitilta
				if ($yhtiorow["valkoodi"] == $valuutta[1]) {

					$query = "update valuu set kurssi='$kurssi' where yhtio='$kukarow[yhtio]' and nimi='$valuutta[0]'";
					$result = mysql_query($query) or pupe_error($query);

					echo "<font class='message'>Haettiin $arvot[0] kurssi valuutalle $valuutta[0]: $kurssi</font>";

					if (mysql_affected_rows() != 0) {
						echo "<font class='message'> ... Kurssi p�ivitetty.</font>";
					}

					echo "<br>";
				}
			}
		}
	}
	else {
		echo "<font class='error'>Valuuttakurssien p�ivitys ep�onnistui!</font><br>";
	}

	require ("inc/footer.inc");

?>