<?php

if ($kieli == '') {
  $querykiel = "SELECT kieli
                FROM asiakas
                WHERE yhtio = '$kukarow[yhtio]'
                and tunnus  = '$laskurow[liitostunnus]'";
  $kielresult = pupe_query($querykiel);
  $kielrow = mysql_fetch_assoc($kielresult);

  $kieli = strtolower($kielrow['kieli']);
}

pupesoft_log('toimitusvahvistus_email', "Valmistellaan tilauksien {$otunnukset} toimitusvahvistuksien l�hett�mist� s�hk�postiin");

$toimitusvahvistus_email_lahetys = true;

if (!isset($rakir_row) or !isset($rakir_row['toimitusvahvistus']) or !isset($rakir_row['asiakas_email']) or !isset($tunnukset) or !isset($otunnukset)) {
  $toimitusvahvistus_email_lahetys = false;

  $msg = "Vaadittavia muuttujia puuttui!";

  if (!isset($rakir_row)) $msg .= " Rahtikirjan rivi� ei l�ytynyt (rakir_row).";
  if (!isset($rakir_row['toimitusvahvistus'])) $msg .= " Toimitusvahvistusta ei ole asetettu (rakir_row[toimitusvahvistus]).";
  if (!isset($rakir_row['asiakas_email'])) $msg .= " Asiakkaan s�hk�postia ei ole asetettu (rakir_row[asiakas_email]).";
  if (!isset($tunnukset)) $msg .= " Tunnukset-muuttujaa ei ole asetettu.";
  if (!isset($otunnukset)) $msg .= " Otunnukset-muuttujaa ei ole asetettu.";

  pupesoft_log('toimitusvahvistus_email', $msg);
}

if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc' or $rakir_row['asiakas_email'] == '' or $tunnukset == '' or $otunnukset == '') {
  $toimitusvahvistus_email_lahetys = false;

  $msg = "Vaadittavia muuttujia puuttui!";

  if ($rakir_row['toimitusvahvistus'] != 'toimitusvahvistus_email.inc') $msg .= " Toimitusvahvistus ei ole toimitusvahvistus_email.inc ({$rakir_row['toimitusvahvistus']}).";
  if ($rakir_row['asiakas_email'] == '') $msg .= " Asiakkaan s�hk�posti on tyhj�.";
  if ($tunnukset == '') $msg .= " Tunnukset-muuttuja on tyhj�.";
  if ($otunnukset == '') $msg .= " Otunnukset-muuttuja on tyhj�.";

  pupesoft_log('toimitusvahvistus_email', $msg);
}

if ($toimitusvahvistus_email_lahetys) {
  pupesoft_log('toimitusvahvistus_email', "Tarvittavat muuttujat l�ytyy ja tarkistukset tehty! Aloitetaan s�hk�postin generointi ja l�hett�minen.");

  $_toimvahvistus = mysql_fetch_assoc(t_avainsana("TOIMVAHVISTUS", $kieli, " and selite = 'toimitusvahvistus_email.inc' "));

  if ($_toimvahvistus['selitetark_2'] == "eihintaa") {
    $_hinta = "";
  }
  else {
    $_hinta = " ".t("Hinta", $kieli);
  }

  $kenelle_emailataan = array();

  if (!empty($_toimvahvistus['selitetark_3'])) {
    // Haetaan myyj�n s�hk�postiosoite
    $myyjaquery = "SELECT DISTINCT(kuka.eposti) myyjan_email
                   FROM lasku
                   JOIN kuka ON (lasku.yhtio = kuka.yhtio AND lasku.myyja = kuka.tunnus)
                   WHERE lasku.yhtio  = '{$kukarow['yhtio']}'
                   AND lasku.tunnus   IN ({$otunnukset})
                   AND lasku.myyja   != ''
                   AND kuka.eposti   != ''
                   LIMIT 1";
    $myyjaresult = pupe_query($myyjaquery);
    $myyjarow = mysql_fetch_assoc($myyjaresult);

    if ($_toimvahvistus['selitetark_3'] == 'M') {
      // Email l�htee vain myyj�lle
      $kenelle_emailataan[] = $myyjarow['myyjan_email'];
    }
    elseif ($_toimvahvistus['selitetark_3'] == 'K') {
      // Email l�htee myyj�lle ja asiakkaalle
      $kenelle_emailataan[] = $myyjarow['myyjan_email'];
      $kenelle_emailataan[] = $rakir_row['asiakas_email'];
    }
  }
  else {
    // Email l�htee vain asiakkaalle
    $kenelle_emailataan[] = $rakir_row['asiakas_email'];
  }

  $body = t("Hei", $kieli).",\n\n".t("Seuraavat tilaamanne tuotteet ovat toimituksessa", $kieli).":\n\n\n".t("Tuotenumero", $kieli)." ".t("M��r�", $kieli)." ".t("Nimitys", $kieli).$_hinta."\n\n";

  $query_ale_lisa = generoi_alekentta('M');

  $query = "SELECT tilausrivi.tuoteno,
            tilausrivi.varattu+tilausrivi.kpl as maara,
            tilausrivi.nimitys,
            round(tilausrivi.hinta * tilausrivi.varattu * {$query_ale_lisa}, 2) hinta,
            lasku.valkoodi
            FROM lasku
            JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'L' AND tilausrivi.toimitettu != '' AND tilausrivi.toimitettuaika != '0000-00-00 00:00:00')
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tunnus  IN ({$otunnukset})";
  $rivi_loop_res = pupe_query($query);

  while ($rivi_loop_row = mysql_fetch_assoc($rivi_loop_res)) {

    if ($_toimvahvistus['selitetark_2'] == "eihintaa") {
      $_hinta = "";
    }
    else {
      $_hinta = "{$rivi_loop_row['hinta']} {$rivi_loop_row['valkoodi']}";
    }

    $body .= "{$rivi_loop_row['tuoteno']} {$rivi_loop_row['maara']} {$rivi_loop_row['nimitys']} $_hinta\n\n";
  }

  $body .= "\n";
  $body .= t("Seurantakoodit", $kieli).":\n\n";

  $_tilausnumerot = explode(',', $tunnukset);
  $linkit = tilauksen_seurantalinkit($_tilausnumerot);

  foreach ($linkit as $seurantalinkki) {
    $link = $seurantalinkki['link'];
    $nro  = $seurantalinkki['id'];

    if (empty($link)) {
      $body .= "{$nro}\n";
    }
    else {
      $body .= "{$link}\n";
    }
  }

  $body .= "\n";

  $body .= t("Tilauksen toimitusosoite", $kieli).":\n\n";

  $maan_tiedot = hae_maa(array('maakoodi' => $rakir_row['toim_maa']));

  $body .= "{$rakir_row['toim_nimi']} {$rakir_row['toim_nimitark']}\n\n";
  $body .= "{$rakir_row['toim_osoite']}\n\n";
  $body .= "{$rakir_row['toim_postino']} {$rakir_row['toim_postitp']}\n\n";
  $body .= "{$maan_tiedot['nimi']}\n\n";

  foreach ($kenelle_emailataan as $key => $email_kuka) {
    // S�hk�postin l�hetykseen parametrit
    $parametri = array(
      "to"      => $email_kuka,
      "cc"      => "",
      "subject" => t("Toimitusvahvistus", $kieli),
      "ctype"   => "text",
      "body"    => $body,
    );

    if (pupesoft_sahkoposti($parametri)) {
      echo t("S�hk�postin l�hetys onnistui").": {$email_kuka}<br />";
      pupesoft_log('toimitusvahvistus_email', "S�hk�postin l�hetys onnistui osoitteeseen {$email_kuka}.");
    }
    else {
      pupesoft_log('toimitusvahvistus_email', "S�hk�postin l�hetys ep�onnistui osoitteeseen {$email_kuka}.");
    }
  }
}
else {
  pupesoft_log('toimitusvahvistus_email', "Toimitusvahvistus-s�hk�postia ei l�hetet� virheiden vuoksi.");
}
