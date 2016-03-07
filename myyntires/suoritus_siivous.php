<?php

if (php_sapi_name() != 'cli') {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

date_default_timezone_set('Europe/Helsinki');

$pupe_root_polku = dirname(dirname(__FILE__));

ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku);
error_reporting(E_ALL);
ini_set("display_errors", 1);

require "inc/connect.inc";
require "inc/functions.inc";

$suoritus_tunnukset = 728;

$query = "SELECT *
          FROM suoritus
          WHERE tunnus in ({$suoritus_tunnukset})";
$suoritus_result = pupe_query($query);

while ($suoritus_row = mysql_fetch_assoc($suoritus_result)) {
  // tarvittavat tiedot
  $maksupaiva       = $suoritus_row['maksupvm'];
  $suoritus_tunnus  = $suoritus_row['tunnus'];
  $tiliointi_tunnus = $suoritus_row['ltunnus'];
  $yhtio            = $suoritus_row['yhtio'];

  // haetaan lasku, johon suoritus on kohdistettu
  $query = "SELECT *
            FROM suorituksen_kohdistus
            WHERE suoritustunnus = {$suoritus_tunnus}";
  $kohdistus_res = pupe_query($query);

  if (mysql_num_rows($kohdistus_res) != 1) {
    echo "Virhe, laskua ei l�ytynyt suoritukselle {$suoritus_tunnus}\n";
    continue;
  }

  $kohdistus_row = mysql_fetch_assoc($kohdistus_res);
  $laskun_tunnus = $kohdistus_row['laskutunnus'];

  // haetaan suorituksen tili�innit
  $query = "SELECT *
            FROM tiliointi
            WHERE yhtio = '{$yhtio}'
            AND ltunnus = (SELECT ltunnus FROM tiliointi WHERE tunnus = {$tiliointi_tunnus})";
  $suoritus_tiliointi_res = pupe_query($query);

  if (mysql_num_rows($suoritus_tiliointi_res) == 0) {
    echo "Virhe, tili�intej� ei l�ytynyt suoritukselle {$query}\n";
    continue;
  }

  // haetaan lasku
  $query = "SELECT *
            FROM lasku
            WHERE tunnus = {$laskun_tunnus}";
  $lasku_res = pupe_query($query);

  if (mysql_num_rows($lasku_res) != 1) {
    echo "Virhe, laskua ei l�ytynyt {$laskun_tunnus}\n";
    continue;
  }

  $lasku_row = mysql_fetch_assoc($lasku_res);

  // haetaan laskun tili�innit
  $query = "SELECT *
            FROM tiliointi
            WHERE yhtio = '{$yhtio}'
            AND ltunnus = {$laskun_tunnus}
            AND korjattu = ''";
  $lasku_tiliointi_res = pupe_query($query);

  if (mysql_num_rows($lasku_tiliointi_res) == 0) {
    echo "Virhe, tili�intej� ei l�ytynyt laskulle {$laskun_tunnus}\n";
    continue;
  }

  // t��ll� kun ollaan, niin kaikki info l�ytyi!

  // korjataan suorituksen tili�innit
  while ($suoritus_tiliointi_row = mysql_fetch_assoc($suoritus_tiliointi_res)) {
    $query = "UPDATE tiliointi SET tapvm = '{$maksupaiva}'
              WHERE tunnus = {$suoritus_tiliointi_row['tunnus']}";
    pupe_query($query);

    echo "suoritus: tili {$suoritus_tiliointi_row['tilino']}, pvm {$suoritus_tiliointi_row['tapvm']}, summa {$suoritus_tiliointi_row['summa']}\n";
  }

  // korjataan laskun tili�innit
  while ($lasku_tiliointi_row = mysql_fetch_assoc($lasku_tiliointi_res)) {
    $query = "UPDATE tiliointi SET tapvm = '{$maksupaiva}'
              WHERE tunnus = {$lasku_tiliointi_row['tunnus']}";
    pupe_query($query);

    echo "lasku: tili {$lasku_tiliointi_row['tilino']}, pvm {$lasku_tiliointi_row['tapvm']}, summa {$lasku_tiliointi_row['summa']}\n";
  }

  // korjataan suorituksen p�iv�ykset
  $query = "UPDATE suoritus SET
            kirjpvm = '{$maksupaiva}',
            kohdpvm = '{$maksupaiva}'
            WHERE tunnus = {$suoritus_row['tunnus']}";
  pupe_query($query);

  echo "suorituksen kirjaus {$suoritus_row['kirjpvm']} --> {$maksupaiva}\n";
  echo "suorituksen kohdistus {$suoritus_row['kohdpvm']} --> {$maksupaiva}\n";

  // korjataan kohdistuksen suoritusp�iv�ys
  $query = "UPDATE suorituksen_kohdistus SET
            kirjauspvm = '{$maksupaiva}'
            WHERE tunnus = {$kohdistus_row['tunnus']}";
  pupe_query($query);

  echo "kohdistuksen suorityspvm {$kohdistus_row['kirjauspvm']} --> {$maksupaiva}\n";

  // korjataan laskun maksup�iv�m��r�
  $query = "UPDATE lasku SET
            mapvm = '{$maksupaiva}'
            WHERE tunnus = {$lasku_row['tunnus']}";
  pupe_query($query);

  echo "laskun maksupvm {$lasku_row['mapvm']} --> {$maksupaiva}\n";
}
