<?php

/*
 * Siirret��n varastotiedot Relexiin
 * 5.1 LOCATION DATA
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

$paiva_ajo = FALSE;

if (isset($argv[2]) and $argv[1] != '') {
  $paiva_ajo = TRUE;
}

ini_set("memory_limit", "5G");

// Otetaan includepath aina rootista
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(dirname(dirname(__FILE__))).PATH_SEPARATOR."/usr/share/pear");

require 'inc/connect.inc';
require 'inc/functions.inc';

// Yhti�
$yhtio = mysql_real_escape_string($argv[1]);

$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

// Tallennetaan rivit tiedostoon
$filepath = "/tmp/location_update_{$yhtio}_".date("Y-m-d").".csv";

if (!$fp = fopen($filepath, 'w+')) {
  die("Tiedoston avaus ep�onnistui: $filepath\n");
}

// Otsikkotieto
$header = "code;name;replenished;chain_code\n";
fwrite($fp, $header);

// Haetaan varastot
$query = "SELECT
          yhtio.maa,
          varastopaikat.tunnus,
          concat_ws(' ', varastopaikat.nimitys, varastopaikat.nimi, varastopaikat.nimitark) nimi,
          varastopaikat.tyyppi
          FROM varastopaikat
          JOIN yhtio ON (varastopaikat.yhtio = yhtio.yhtio)
          WHERE varastopaikat.yhtio  = '$yhtio'
          AND varastopaikat.tyyppi  != 'P'
          ORDER BY varastopaikat.tunnus";
$res = pupe_query($query);

// Kerrotaan montako rivi� k�sitell��n
$rows = mysql_num_rows($res);

echo "Varastorivej� {$rows} kappaletta.\n";

$k_rivi = 0;

while ($row = mysql_fetch_assoc($res)) {

  $replensihed = "yes";

  if ($row['tyyppi'] != "") {
    $replensihed = "no";
  }

  $rivi  = "{$row['maa']}-{$row['tunnus']};";
  $rivi .= pupesoft_csvstring($row['nimi']).";";
  $rivi .= "{$replensihed};";
  $rivi .= "";
  $rivi .= "\n";

  fwrite($fp, $rivi);

  $k_rivi++;

  if ($k_rivi % 1000 == 0) {
    echo "K�sitell��n rivi� {$k_rivi}\n";
  }
}

fclose($fp);

// Tehd��n FTP-siirto
if ($paiva_ajo and !empty($relex_ftphost)) {
  $ftphost = $relex_ftphost;
  $ftpuser = $relex_ftpuser;
  $ftppass = $relex_ftppass;
  $ftppath = "/data/input";
  $ftpfile = $filepath;
  require "inc/ftp-send.inc";
}

echo "Valmis.\n";