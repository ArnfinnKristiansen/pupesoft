<?php
/**
 *
 *
 */

if (empty($argv)) {
    die('<p>T�m�n scriptin voi ajaa ainoastaan komentorivilt�.</p>');
}

if ($argv[1] == '') {
	die("Yhti� on annettava!!");
}

require 'inc/connect.inc';
require 'inc/functions.inc';

// kaikki virheilmotukset
//ini_set('error_reporting', E_ALL | E_STRICT);

$path            = '/tmp/';
$path_nimike     = $path . 'NIMIKE.txt';
$path_asiakas    = $path . 'ASIAKAS.txt';
$path_toimittaja = $path . 'TOIMITTAJA.txt';
$path_varasto    = $path . 'VARASTO.txt';
$path_tapahtumat = $path . 'TAPAHTUMAT.txt';
$path_myynti     = $path . 'MYYNTI.txt';

/*$kukarow = array(
	'kuka'  => 'macce',
	'yhtio' => $argv[1];
);*/

$kukarow = array();

$kukarow["yhtio"] = $argv[1];

$query = "SELECT * from yhtio where yhtio='{$kukarow['yhtio']}'";
$res = mysql_query($query) or pupe_error($query);
$yhtiorow = mysql_fetch_assoc($res);


$query = "SELECT * from yhtion_parametrit where yhtio='{$kukarow['yhtio']}'";
$res = mysql_query($query) or pupe_error($query);
$params = mysql_fetch_assoc($res);

$yhtiorow = array_merge($yhtiorow, $params);

//testausta varten limit
//$limit = "limit 500";

$limit = '';

// ajetaan kaikki operaatiot
nimike($limit);
asiakas();
toimittaja();
varasto($limit);
varastotapahtumat($limit);
myynti();

function nimike($limit = '') {
	global $kukarow, $path_nimike;

	$query = "SELECT tuoteno from tuote where yhtio='{$kukarow['yhtio']}' $limit";
	$rest = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($rest);
	if ($rows == 0) {
		echo "Yht��n tuotetta ei l�ytynyt\n";
		die();
	}

	$fp = fopen($path_nimike, 'w+');

	$row = 0;

	$data = array(
		'nimiketunnus'     => null,
		'nimitys'          => null,
		'yksikko'          => null,
		'tuoteryhma'       => null,
		'osasto'	       => null,
		'kustannuspaikka'  => null,
		'varastotunnus'    => null,
		'hintayksikko'     => null,
		'varastoimiskoodi' => null,
		'nimikelaji'       => null,
		'ostaja'           => null,
		'paino'            => null,
		'toimittajatunnus' => null,
		'toimittajannimiketunnus' => null
	);

	create_headers($fp, array_keys($data));

	while ($tuoteno = mysql_fetch_assoc($rest)) {

		// mones t�m� on
		$row++;

        #JOIN tuotteen_toimittajat ON tuote.tuoteno=tuotteen_toimittajat.tuoteno and tuote.yhtio=tuotteen_toimittajat.yhtio
		$query = "	SELECT
		          	tuote.tuoteno        	nimiketunnus,
			        nimitys              	nimitys,
			        yksikko              	yksikko,
			        avainsana.selitetark 	tuoteryhma,
			        avainsana_os.selitetark	osasto,
					tuote.kustp				kustannuspaikka,
			        #varastotunnus,
			        ei_varastoida 			varastoimiskoodi,
			        tuotetyyppi    			nimikelaji,
			        kuka.kuka      			ostaja,
			        tuotemassa     			paino
			        #lava,
			        #lavakerros
			        from tuote
			        JOIN avainsana ON avainsana.selite=tuote.try and avainsana.yhtio=tuote.yhtio
			        JOIN avainsana avainsana_os ON avainsana_os.selite=tuote.osasto and avainsana_os.yhtio=tuote.yhtio
					JOIN kuka ON kuka.myyja=tuote.ostajanro
					WHERE tuote.yhtio='{$kukarow['yhtio']}'
					and tuote.tuoteno='{$tuoteno['tuoteno']}'";
		$res = mysql_query($query) or pupe_error($query);

		$query = "	SELECT tuotteen_toimittajat.liitostunnus toimittajatunnus, tuotteen_toimittajat.toim_tuoteno toimittajannimiketunnus
					FROM tuotteen_toimittajat
					WHERE tuoteno = '{$tuoteno['tuoteno']}'
					AND yhtio = '{$kukarow['yhtio']}'";
		$tuot_toim_res = mysql_query($query) or pupe_error($query);
		$tuot_toim_row = mysql_fetch_array($tuot_toim_res);

		while ($tuote = mysql_fetch_assoc($res)) {

			if (trim($tuote['varastoimiskoodi']) != '') {
				// tuotetta ei varastoida
				$tuote['varastoimiskoodi'] = '0';
			} else {
				$tuote['varastoimiskoodi'] = '1';
			}

			// hintayksikko aina 1
			$tuote['hintayksikko'] = '1';

			$query = "SELECT hyllyalue, hyllynro from tuotepaikat where tuoteno='{$tuoteno['tuoteno']}' and oletus != '' and yhtio='{$kukarow['yhtio']}' limit 1";
			$res = mysql_query($query) or pupe_error($query);
			$paikka = mysql_fetch_assoc($res);

			// mik� varasto
			$tuote['varastotunnus'] = kuuluukovarastoon($paikka['hyllyalue'], $paikka['hyllynro']);

			$data = array(
				'nimiketunnus'     => $tuote['nimiketunnus'],
				'nimitys'          => $tuote['nimitys'],
				'yksikko'          => $tuote['yksikko'],
				'tuoteryhma'       => $tuote['tuoteryhma'],
				'osasto'	       => $tuote['osasto'],
				'kustannuspaikka'  => $tuote['kustannuspaikka'],
				'varastotunnus'    => $tuote['varastotunnus'],
				'hintayksikko'     => $tuote['hintayksikko'],
				'varastoimiskoodi' => $tuote['varastoimiskoodi'],
				'nimikelaji'       => $tuote['nimikelaji'],
				'ostaja'           => $tuote['ostaja'],
				'paino'            => $tuote['paino'],
				'toimittajatunnus' => $tuot_toim_row['toimittajatunnus'],
				'toimittajannimiketunnus' => $tuot_toim_row['toimittajannimiketunnus']
			);

			$data = implode("\t", $data);
			//echo '.';

			if (! fwrite($fp, $data . "\n")) {
				echo "Failed writing row.\n";
				die();
			}
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
	}

	fclose($fp);
	echo "\nDone.\n";
}

function asiakas() {
	global $path_asiakas, $kukarow;

	echo "Asiakkaat...";

	$query = "SELECT tunnus, nimi, nimitark, ryhma, if(myyjanro=0,'',myyjanro) myyjanro from asiakas where yhtio='{$kukarow['yhtio']}'";
	$rest = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($rest);
	$row = 0;

	if ($rows == 0) {
		echo "Yht��n asiakasta ei l�ytynyt\n";
		die();
	}

	$fp = fopen($path_asiakas, 'w+');

	$data = array(
		'asiakastunnus'  => null,
		'asiakkaan nimi' => null,
		'asiakasryhma'   => null,
		'myyjatunnus'    => null,
		'laskutustunnus' => null,
	);

	create_headers($fp, array_keys($data));

	while ($asiakas = mysql_fetch_array($rest)) {
		$row++;

		$data = array(
			'asiakastunnus'  => $asiakas['tunnus'],
			'asiakkaan nimi' => $asiakas['nimi'] . ' ' . $asiakas['nimitark'],
			'asiakasryhma'   => $asiakas['ryhma'],
			'myyjatunnus'    => $asiakas['myyjanro'],
			'laskutustunnus' => $asiakas['tunnus'],
		);

		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
	}

	fclose($fp);
	echo "Done.\n";
}

function toimittaja() {
	global $path_toimittaja, $kukarow;

	echo "Toimittajat...";
	$query = "SELECT tunnus, nimi, nimitark, yhteyshenkilo from toimi where yhtio='{$kukarow['yhtio']}'";
	$rest = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($rest);
	$row = 0;
	if ($rows == 0) {
		echo "Yht��n toimittajaa ei l�ytynyt\n";
		die();
	}

	$fp = fopen($path_toimittaja, 'w+');

	$data = array(
		'toimittajatunnus'  => null,
		'toimittajan nimi'  => null,
		'ostajatunnus'      => null,
	);

	create_headers($fp, array_keys($data));

	while ($asiakas = mysql_fetch_array($rest)) {
		$row++;

		$data = array(
			'toimittajatunnus'  => $asiakas['tunnus'],
			'toimittajan nimi'  => $asiakas['nimi'] . ' ' . $asiakas['nimitark'],
			'ostajatunnus'      => $asiakas['yhteyshenkilo'],
		);

		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
	}

	fclose($fp);
	echo "Done.\n";
}

function varasto($limit = '') {
	global $path_varasto, $kukarow;

	echo "Varasto... ";
	$fp = fopen($path_varasto, 'w+');

	$query = "	SELECT tuotepaikat.tuoteno nimiketunnus, sum(tuotepaikat.saldo) saldo, tuote.kehahin keskihinta, varastopaikat.tunnus varastotunnus,
				(	SELECT tuotteen_toimittajat.toimitusaika
					FROM tuotteen_toimittajat
					WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
					AND tuotteen_toimittajat.tuoteno = tuotepaikat.tuoteno
					AND tuotteen_toimittajat.toimitusaika != ''
					LIMIT 1) toimitusaika
				FROM tuotepaikat
				JOIN varastopaikat ON
				concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
				concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
				and varastopaikat.yhtio=tuotepaikat.yhtio
				JOIN tuote ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
				WHERE tuote.ei_saldoa = ''
				AND tuotepaikat.yhtio = '{$kukarow['yhtio']}'
				GROUP BY 1,4
				ORDER BY 1
				$limit";
	$res = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($res);
	$row = 0;
	if ($rows == 0) {
		echo "Yht��n varastoa ei l�ytynyt\n";
		die();
	}

	$headers = array(
		'nimiketunnus',
		'saldo',
		'keskihinta',
		'varastotunnus',
		'toimitusaika',
		'tilattu',
		'varattu',
	);

	// tehd��n otsikot
	create_headers($fp, $headers);

	while ($trow = mysql_fetch_assoc($res)) {
		$row++;

		$query = "	SELECT
					sum(if(tilausrivi.tyyppi='O', tilausrivi.varattu, 0)) tilattu,
					sum(if(tilausrivi.tyyppi='L', tilausrivi.varattu, 0)) varattu
					FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
					WHERE yhtio = '{$kukarow['yhtio']}'
					and tyyppi in ('L','O')
					and tuoteno = '{$trow['nimiketunnus']}'
					and laskutettuaika = '0000-00-00'
					and tilausrivi.var not in ('P','J','S')";
		$result = mysql_query($query) or pupe_error($query);
		$kplv = 0;
		$kplt = 0;
		while ($ennp = mysql_fetch_array($result)) {
			$kplv += $ennp['varattu'];
			$kplt += $ennp['tilattu'];
		}

		$trow['tilattu'] = $kplt;
		$trow['varattu'] = $kplv;

		$data = implode("\t", $trow);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
	}

	fclose($fp);
	echo "Done.\n";
}

function varastotapahtumat($limit = '') {
	global $path_tapahtumat, $kukarow;

	echo "Varastotapahtumat... ";
	if (! $fp = fopen($path_tapahtumat, 'w+')) {
		die("Ei voitu avata filea $path_tapahtumat");
	}

	$date = date('Y-m-d', mktime(0, 0, 0, date('m')-2, date('d'), date('Y')));

    $query = "SELECT tilausrivi.tuoteno nimiketunnus,
			tilausrivi.laskutettuaika   tapahtumapaiva,
			tilausrivi.tyyppi           tapahtumalaji,
			tilausrivi.rivihinta        hinta, # rivin veroton arvo
			tilausrivi.kate             kate,
			tilausrivi.kpl              tapahtumamaara,
			lasku.laskunro              laskunumero,
			lasku.liitostunnus          asiakastunnus,
			lasku.myyja                 myyjatunnus,
			varastopaikat.tunnus        varastotunnus,
			lasku.liitostunnus			toimitusasiakas,
			lasku.yhtio_toimipaikka		toimipaikka
			FROM tilausrivi
			JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.uusiotunnus and lasku.yhtio=tilausrivi.yhtio
			JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio
			JOIN varastopaikat ON
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
            WHERE tilausrivi.tyyppi IN('L', 'O') and tilausrivi.laskutettuaika >= '$date' and tilausrivi.yhtio='{$kukarow['yhtio']}'
			ORDER BY tilausrivi.laskutettuaika
			$limit";
    $res = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($res);
	$row = 0;
	if ($rows == 0) {
		echo "Yht��n varastotapahtumaa ei l�ytynyt\n";
		die();
	}

	$headers = array(
		'nimiketunnus'   => null,
		'asiakastunnus'  => null,
		'tapahtumapaiva' => null,
		'tapahtumalaji'  => null,
		'myyntiarvo'     => null,
		'ostoarvo'       => null,
		'tapahtumamaara' => null,
		'laskunumero'    => null,
		'myyjatunnus'    => null,
		'varastotunnus'  => null,
		'toimitusasiakas' => null,
		'toimipaikka'	 => null,
	);

	// tehd��n otsikot
	create_headers($fp, array_keys($headers));

    while ($trow = mysql_fetch_assoc($res)) {
		$row++;

		switch($trow['tapahtumalaji']) {
			// ostot
			case 'O':

				// 1 = saapuminen tai oston palautus
				$trow['tapahtumalaji'] = 1;

				// ostoarvo
				$trow['ostoarvo'] = $trow['hinta'];

				// myyntiarvo on 0
				$trow['myyntiarvo'] = 0;

				// jos kpl alle 0 niin t�m� on oston palautus
				// jolloin hinta my�s miinus
				if ($trow['tapahtumamaara'] < 0) {
					// tapahtumamaara on aina positiivinen logisticarissa
					$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
				}

		        break;

			// myynnit
			case 'L':

				// 2 = otto tai myynninpalautus
				$trow['tapahtumalaji'] = 2;

				$trow['myyntiarvo'] = $trow['hinta'];

				// ostoarvo
				$trow['ostoarvo'] = $trow['hinta'] - $trow['kate'];

				// t�m� on myynninpalautus eli myyntiarvo on negatiivinen
				if ($trow['tapahtumamaara'] < 0) {
					// tapahtumamaara on aina positiivinen logisticarissa
					$trow['tapahtumamaara'] = -1 * $trow['tapahtumamaara'];
				}

				break;
		}

		unset($trow['hinta']);
		unset($trow['kate']);

		$data = array_merge($headers, $trow);

		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
    }

	fclose($fp);
	echo "Done.\n";
}

function myynti() {
	global $path_myynti, $kukarow, $yhtiorow;

	echo "Myynnit... ";
	if (! $fp = fopen($path_myynti, 'w+')) {
		die("Ei voitu avata filea $path_myynti");
	}

	$date = date('Y-m-d', mktime(0, 0, 0, date('m')-2, date('d'), date('Y')));

    $query = "SELECT tilausrivi.tuoteno nimiketunnus,
			tilausrivi.toimaika toimituspaiva,
			tilausrivi.tyyppi tapahtumalaji,
			tilausrivi.hinta  / if('{$yhtiorow['alv_kasittely']}' = '' and tilausrivi.alv<500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)) rivihinta,
			tilausrivi.varattu tapahtumamaara,
			lasku.liitostunnus asiakastunnus,
			lasku.myyja myyjatunnus,
			lasku.tunnus tilausnro,
			varastopaikat.tunnus varastotunnus,
			lasku.liitostunnus toimitusasiakas,
			lasku.yhtio_toimipaikka	toimipaikka
			FROM tilausrivi
			JOIN lasku USE INDEX (PRIMARY) ON lasku.tunnus=tilausrivi.uusiotunnus and lasku.yhtio=tilausrivi.yhtio
			JOIN tuotepaikat USE INDEX (tuote_index) ON tuotepaikat.tuoteno=tilausrivi.tuoteno and tuotepaikat.hyllyvali=tilausrivi.hyllyvali and tuotepaikat.hyllytaso=tilausrivi.hyllytaso AND tilausrivi.hyllyalue=tuotepaikat.hyllyalue and tilausrivi.hyllynro=tuotepaikat.hyllynro and tilausrivi.yhtio=tuotepaikat.yhtio
			JOIN varastopaikat ON
			concat(rpad(upper(alkuhyllyalue),  5, '0'),lpad(upper(alkuhyllynro),  5, '0')) <= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0')) and
			concat(rpad(upper(loppuhyllyalue), 5, '0'),lpad(upper(loppuhyllynro), 5, '0')) >= concat(rpad(upper(tuotepaikat.hyllyalue), 5, '0'),lpad(upper(tuotepaikat.hyllynro), 5, '0'))
			WHERE
			tilausrivi.varattu != 0
			AND tilausrivi.tyyppi IN('L', 'O')
			AND tilausrivi.laskutettuaika = '0000-00-00' AND tilausrivi.laadittu >= '$date 00:00:00'
			AND tilausrivi.yhtio='{$kukarow['yhtio']}'
			ORDER BY tilausrivi.laskutettuaika";
	$res = mysql_query($query) or pupe_error($query);

	$rows = mysql_num_rows($res);
	$row = 0;
	if ($rows == 0) {
		echo "Yht��n myyntitapahtumaa ei l�ytynyt\n";
		die();
	}

	$headers = array(
		'nimiketunnus'   => null,
		'asiakastunnus'  => null,
		'toimituspaiva'  => null,
		'tapahtumalaji'  => null,
		'myyntiarvo'     => null,
		'ostoarvo'       => null,
		'tapahtumamaara' => null,
		'tilausnro'      => null,
		'myyjatunnus'    => null,
		'varastotunnus'  => null,
		'toimitusasiakas' => null,
		'toimipaikka'	 => null,
	);

	// tehd��n otsikot
	create_headers($fp, array_keys($headers));

	while ($trow = mysql_fetch_assoc($res)) {
		$row++;

		$trow['myyntiarvo'] = $trow['rivihinta'];
		$trow['ostoarvo']   = $trow['rivihinta'];

		unset($trow['rivihinta']);

		switch ($trow['tapahtumalaji']) {
			case 'L':
				$trow['tapahtumalaji'] = '4';
				break;
			case 'O':
				$trow['tapahtumalaji'] = '3';
				break;
		}
		$data = array_merge($headers, $trow);

		$data = implode("\t", $data);

		if (! fwrite($fp, $data . "\n")) {
			echo "Failed writing row.\n";
			die();
		}

		$progress = floor(($row/$rows) * 40);
		$str = sprintf("%10s", "$row/$rows");

		$hash = '';
		for ($i=0; $i < (int) $progress; $i++) {
			$hash .= "#";
		}

		echo sprintf("%s  |%-40s|\r", $str, $hash);
    }

	fclose($fp);
	echo "Done.\n";
}

function create_headers($fp, array $cols) {
	$data = implode("\t", $cols) . "\n";
	fwrite($fp, $data);
}
?>