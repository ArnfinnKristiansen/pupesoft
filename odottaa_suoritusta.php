<?php

if (php_sapi_name() != 'cli') {
	die("T�t� scripti� voi ajaa vain komentorivilt�!");
}

require_once ('inc/connect.inc');
require_once ('inc/functions.inc');

/*
 * HOW TO:
 *
 * Tarvitaan 1 parametri: yhtio
 *
 */

if (!isset($argv[1]) or $argv[1] == '') {
	echo "Anna yhti�!!!\n";
	die;
}

$yhtiorow = hae_yhtion_parametrit($argv[1]);

$tilaukset = hae_suoritusta_odottavat_tilaukset();

kasittele_tilaukset($tilaukset);

function hae_suoritusta_odottavat_tilaukset() {
	global $yhtiorow;

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tila 	= 'N'
				AND alatila = 'G'
				ORDER BY luontiaika ASC";
	$result = pupe_query($query);

	$tilaukset = array();

	while ($tilaus = mysql_fetch_assoc($result)) {
		$tilaukset[] = $tilaus;
	}

	return $tilaukset;
}

function kasittele_tilaukset($tilaukset) {
	global $kukarow, $yhtiorow;

	echo t("Otetaan") . ' ' . count($tilaukset) . ' ' . t("myyntitilausta k�sittelyyn") . "\n";

	foreach ($tilaukset as $laskurow) {

		// Parametrej� saatanat.php:lle
		$sytunnus 	 	 = $laskurow['ytunnus'];
		$sliitostunnus	 = $laskurow['liitostunnus'];
		$eiliittymaa 	 = "ON";
		$luottorajavirhe = "";
		$jvvirhe 		 = "";
		$ylivito 		 = 0;
		$trattavirhe 	 = "";
		$laji 			 = "MA";
		$grouppaus       = ($yhtiorow["myyntitilaus_saatavat"] == "Y") ? "ytunnus" : "";

		$kukarow = hae_asiakas($laskurow['liitostunnus']);

		ob_start();

		require ("raportit/saatanat.php");

		ob_end_clean();

		if (!empty($luottorajavirhe) or $ylivito > 0) {
			echo t("Lasku") . ' ' . $laskurow['tunnus'] . ' ' . t("pysyy suoritusta odotus tilassa") . "\n";
		}
		else {
			//jos laskut on maksettu, tilaus voidaan laittaa myyntitilaus kesken tilaan
			aseta_tilaus_kesken_tilaan_ja_aseta_uusi_lahto($laskurow);
		}
	}
}

function aseta_tilaus_kesken_tilaan_ja_aseta_uusi_lahto($laskurow) {
	global $yhtiorow, $kukarow;

	$query = "	UPDATE lasku
				SET tila = 'N',
				alatila = ''
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tunnus = '{$laskurow['tunnus']}'
				AND tila = 'N'
				AND alatila = 'G'";
	pupe_query($query);

	if (mysql_affected_rows() > 0) {
		echo t("Lasku") . ' ' . $laskurow['tunnus'] . ' ' . t("asetettiin kesken tilaan") . "\n";

		//tilaus-valmis.inc hoitaa meille j�rkev�n l�hd�n kun tilauksen tila ja alatila on oikein
		$kukarow['kesken'] = $laskurow['tunnus'];

		require("tilauskasittely/tilaus-valmis.inc");
	}
}

function hae_asiakas($liitostunnus) {
	global $yhtiorow;

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio = '{$yhtiorow['yhtio']}'
				AND tunnus = {$liitostunnus}";
	$result = pupe_query($query);

	return mysql_fetch_assoc($result);
}