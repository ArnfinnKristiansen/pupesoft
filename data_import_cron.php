<?php

	/* DATA IMPORT CRON LOOP */
	/* Ajetaan cronista ja t�m� sis��nlukee luedata-tiedostot datain hakemistosta */

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	// Laitetaan unlimited max time
	ini_set("max_execution_time", 0);

	$data_import_lock_file = "/tmp/data_import.lock";

	// Jos meill� ei ole lukkofile�, voidaan loopata
	if (!file_exists($data_import_lock_file)) {

		// Tehd��n lukkofile
		touch($data_import_lock_file);

		$pupe_root_polku = dirname(__FILE__);
		require ("{$pupe_root_polku}/inc/connect.inc");
		require ("{$pupe_root_polku}/inc/functions.inc");

		// Loopataan DATAIN -hakemisto l�pi
		if ($handle = opendir($pupe_root_polku."/datain")) {
		    while (false !== ($file = readdir($handle))) {

				// Etsit��n "lue-data#" -alkuisia filej�, jotka loppuu ".CSV"
				if (substr($file, 0, 9) == "lue-data#" and substr($file, -4) == ".CSV") {

					// Filename on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.CSV
					$filen_tiedot = explode("#", $file);

					// Ei k�sitell� jos filename ei ole oikeaa muotoa
					if (count($filen_tiedot) == 6) {

						$kuka = $filen_tiedot[1];
						$yhtio = $filen_tiedot[2];
						$taulu = $filen_tiedot[3];
						$random = $filen_tiedot[4];
						$jarjestys = $filen_tiedot[5];

						// Logfile on muotoa: lue-data#username#yhtio#taulu#randombit#jarjestys.LOG
						$logfile = "lue-data#{$kuka}#{$yhtio}#{$taulu}#{$random}#{$jarjestys}.LOG";

						// Ajetaan lue_data t�lle tiedostolle
						passthru("/usr/bin/php ".escapeshellarg($pupe_root_polku."/lue_data.php")." ".escapeshellarg($yhtio)." ".escapeshellarg($taulu)." ".escapeshellarg($pupe_root_polku."/datain/".$file)." ".escapeshellarg($pupe_root_polku."/datain/".$logfile));

						// Siirret��n file k�sitellyksi
						rename($file, $file.".DONE");
					}
				}
		    }
		    closedir($handle);
		}

		// Poistetaan lukkofile
		unlink($data_import_lock_file);

	}
