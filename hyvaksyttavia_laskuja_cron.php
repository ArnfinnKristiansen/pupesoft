<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!\n");
}

date_default_timezone_set('Europe/Helsinki');

if (trim($argv[1]) == '') {
  die ("Et antanut yhti�t�!\n");
}

// lis�t��n includepathiin pupe-root
ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . dirname(__FILE__));

// otetaan tietokanta connect ja funktiot
require "inc/connect.inc";
require "inc/functions.inc";

// Logitetaan ajo
cron_log();

// Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
pupesoft_flock();

$yhtio    = mysql_real_escape_string(trim($argv[1]));
$yhtiorow = hae_yhtion_parametrit($yhtio);
$kukarow  = hae_kukarow('admin', $yhtiorow['yhtio']);

$query = "SELECT
            eposti,
            count(*) AS hyvaksyttavia
          FROM lasku
            JOIN kuka
              ON (kuka.kuka = lasku.hyvaksyja_nyt
              AND kuka.yhtio = lasku.yhtio)
          WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
            AND tila = 'H'
            AND alatila != 'H'
            AND hyvaksyja_nyt != ''
          GROUP BY lasku.hyvaksyja_nyt";

$result = pupe_query($query);

while ($row = mysql_fetch_assoc($result)) {
  $sana = (int) $row["hyvaksyttavia"] > 1 ? "laskua" : "lasku";

  $body = <<<TEXT
Hei,

Sinulla on {$row["hyvaksyttavia"]} {$sana} hyv�ksytt�v�n�.
TEXT;

  $email = $row["eposti"];

  $email_params = array(
    "to"      => $email,
    "cc"      => "",
    "subject" => "Hyv�ksytt�vi� laskuja",
    "body"    => $body
  );

  pupesoft_sahkoposti($email_params);
}
