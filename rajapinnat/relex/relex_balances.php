<?php

/*
 * Siirret��n varastosaldot Relexiin
 * 2.3 BALANCES
*/

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

if (!isset($argv[1]) or $argv[1] == '') {
  die("Yhti� on annettava!!");
}

ini_set("memory_limit", "1G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhti�
$yhtio = mysql_real_escape_string($argv[1]);

// Tallannetan rivit tiedostoon
$filepath = "/tmp/input_balances_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep�onnistui: $filepath\n");
}

// Otsikkotieto
$header = "location;product;quantity;type\n";
fwrite($fp, $header);

// Haetaan tuotteiden saldot per varasto
$query = "SELECT
          tuotepaikat.tuoteno tuote,
          varastopaikat.nimitys varasto,
          sum(tuotepaikat.saldo) saldo
          FROM tuote
          JOIN tuotepaikat ON (tuote.tuoteno = tuotepaikat.tuoteno and tuote.yhtio = tuotepaikat.yhtio)
          JOIN varastopaikat ON (varastopaikat.tunnus = tuotepaikat.varasto)
          WHERE tuote.yhtio     = '$yhtio'
          AND tuote.status     != 'P'
          AND tuote.ei_saldoa   = ''
          AND tuote.tuotetyyppi = ''
          AND tuote.ostoehdotus = ''
          GROUP BY 1,2
          ORDER BY 1,2
          LIMIT 1000";
$res = pupe_query($query);

// Kerrotaan montako rivi� k�sitell��n
$rows = mysql_num_rows($res);

echo "Saldorivej� {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {
  $rivi = "{$row['varasto']};{$row['tuote']};{$row['saldo']};BALANCE\n";
  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K�sitell��n rivi� {$k_rivi}\n";
  }
}

fclose($fp);

echo "Valmis.\n";
