<?php

/*
  T�nne tarvitaan:
  $jysum joka on loppusumma joka tilaukselle halutaan
  $tilausnumero TAI $valitut_tilausrivi_tunnukset

  Jos jyvsumma on negatiivinen on se alennuksen m��r�
  Jos jyvsumma sis�lt�� % merkin annetaan erikoisalennusta

  $yhtiorow["jyvita_alennus"]
    ''  = Alennus jokaisen rivin hintaan
    'P' = Alennus annetaan tuotteella vain projektilla
    'T' = Alennus annetaan tuotteella aina
*/

if (strpos($jysum, "%") !== false and empty($valitut_tilausrivi_tunnukset)) {
  $jysum = str_replace("%", "", $jysum);
  $anna_ea = "TOKI";
}

$jysum = round(str_replace(",", ".", $jysum), 2);
$jyvitetaan_ristiin = "";

//Lasketaan tilauksen arvot
if ($laskurow["valkoodi"] != '' and trim(strtoupper($laskurow["valkoodi"])) != trim(strtoupper($yhtiorow["valkoodi"])) and $laskurow["vienti_kurssi"] != 0) {
  $hinta_riv = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
  $hinta_riv2 = "(tilausrivi.hinta/$laskurow[vienti_kurssi])";
}
else {
  $hinta_riv = "tilausrivi.hinta";
  $hinta_riv2 = "tilausrivi.hinta";
}

// jos radiobutton verottomat hinnat on p��ll� ja yhtion parametri verolliset myyntihinnat
// eli otetaan verot pois
if ($tilausrivi_alvillisuus == 'E' and $yhtiorow['alv_kasittely'] == '') {
  $hinta_riv .= " / (1 + (tilausrivi.alv / 100)) ";
  $jyvitetaan_ristiin = "X";
}
//jos radiobutton verolliset hinnat on p��ll� ja yhtion parametri verottomat myyntihinnat
// eli lis�t��n verot
elseif ($tilausrivi_alvillisuus != 'E' and $yhtiorow['alv_kasittely'] != '') {
  $hinta_riv .= " * (1 + (tilausrivi.alv / 100)) ";
  $jyvitetaan_ristiin = "X";
}

$query_ale_lisa = generoi_alekentta('M');

$tilausrivi_where = "";

if (!empty($valitut_tilausrivi_tunnukset)) {
  $tilausrivi_where = " AND tilausrivi.tunnus IN ('".implode("','", $valitut_tilausrivi_tunnukset)."') ";
}

$query = "SELECT
          sum($hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) koko_summa,
          sum($hinta_riv2 * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}) koko_oma_summa,
          sum(if(varattu+jt>0, $hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) plus_summa,
          sum(if(varattu+jt<0, $hinta_riv * (tilausrivi.varattu+tilausrivi.jt) * {$query_ale_lisa}, 0)) nega_summa
          FROM tilausrivi
          WHERE yhtio     = '$kukarow[yhtio]'
          AND otunnus     = '$tilausnumero'
          {$tilausrivi_where}
          and rivihinta   = 0
          and uusiotunnus = 0";
$result = pupe_query($query);
$tarow = mysql_fetch_assoc($result);

$plus_summa = $tarow["plus_summa"];
$nega_summa = $tarow["nega_summa"];

if ($anna_ea == "TOKI" and ($jysum < 0 or $jysum >= 100)) {
  echo "<font class='error'>".t("Erikoisalennus tulee olla")." 0 - 100%!!</font><br>";
}
elseif ($jysum == 0) {
  if ($yhtiorow["jyvita_alennus"] == "") {
    // Nollataan jyvitys
    $query = "UPDATE lasku SET hinta = '0' WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]'";
    $result = pupe_query($query);

    $laskurow["hinta"] = 0;
  }
  else {
    echo "<font class='error'>".t("Jyvitett�v� summa ei saa olla nolla")."!</font><br>";
  }
}
elseif ($jysum < 0 and $nega_summa >= 0) {
  echo "<font class='error'>".t("Et voi jyvitt�� summaa negatiiviseksi jos sinulla ei ole hyvitysrivej�")."!</font><br>";
}
elseif ($jysum > 0 and $plus_summa <= 0) {
  echo "<font class='error'>".t("Et voi jyvitt�� summaa positiiviseksi jos sinulla ei ole veloitusrivej�")."!</font><br>";
}
else {

  if ($anna_ea == "TOKI") {
    //   Annetaan erikoisale
    $query = "UPDATE lasku SET erikoisale = '$jysum' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tilausnumero' and tilaustyyppi = 'N' and tila IN ('V','N','G','L') and alatila NOT IN ('V','X')";
    $upresult = pupe_query($query);

    $laskurow["erikoisale"] = $jysum;
  }
  elseif ($yhtiorow["jyvita_alennus"] == "T" or ($yhtiorow["jyvita_alennus"] == "P" and $toim == "PROJEKTI")) {
    //   Alennus lis�t��n alennustuotteelle
    //  TODO t�m� on osittain turhaa koska projektia ei ole viel� k�yt�ss�, kohta on!
    $query = "SELECT * FROM tuote WHERE yhtio = '$kukarow[yhtio]' and tuoteno = '$yhtiorow[alennus_tuotenumero]'";
    $rhire = pupe_query($query);

    if (mysql_num_rows($rhire) == 1) {
      $trow  = mysql_fetch_assoc($rhire);

      $hinta     = round(($plus_summa+$nega_summa - $jysum), $yhtiorow['hintapyoristys']);
      $trow["alv"] = $laskurow["alv"];
      $nimitys   = $trow["nimitys"];

      list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');

      if ($laskurow["tila"] == "T") {
        $rivityyppi = "T";
      }
      else {
        $rivityyppi = "L";
      }

      $query  = "INSERT INTO tilausrivi (laadittu, hinta, netto, varattu, tilkpl, otunnus, tuoteno, nimitys, yhtio, tyyppi, alv, kommentti) VALUES (now(), '$hinta', 'N', '-1', '-1', '$tilausnumero', '$trow[tuoteno]', '$nimitys', '$kukarow[yhtio]', '$rivityyppi', '$alv', '')";
      $addtil = pupe_query($query);
    }
    else {
      echo t("VIRHE: Alennusta ei voitu antaa alennustuote puuttuu")."!<br>";
    }
  }
  else {

    // Jyvitet��n riveille uusihinta ja tallennetaan sy�tetty summa lasku.hinta-kentt��n
    // huomioidaan hyvitysrivit ja ei muuteta niitten hintoja

    $boob_jyva = 0;

    $ale_query_select_lisa = generoi_alekentta_select('erikseen', 'M');

    if ($jysum < 0 and $nega_summa != 0) {
      // jos jyvityssumma on miinusta ja meill� on hyvitysrivej�, kosketaan vain hyvitysriveihin
      $halu_summa = $jysum - $plus_summa;
      $jypro = $halu_summa / $nega_summa;

      // Rullataan tilausrivej�
      $query = "SELECT tunnus, tuoteno, hinta, {$ale_query_select_lisa} tilkpl
                FROM tilausrivi
                WHERE yhtio     = '$kukarow[yhtio]'
                and varattu + jt < 0
                and otunnus     = '$tilausnumero'
                {$tilausrivi_where}
                and rivihinta   = 0
                and uusiotunnus = 0
                ORDER BY hinta";
    }
    elseif ($jysum > 0 and $plus_summa != 0) {
      // jos jyvityssumma on plussaa ja meill� on veloitusrivej�, kosketaan vain veloitusriveihin
      $halu_summa = $jysum - $nega_summa;
      $jypro = $halu_summa / $plus_summa;

      // Rullataan tilausrivej�
      $query = "SELECT tunnus, tuoteno, hinta, {$ale_query_select_lisa} tilkpl
                FROM tilausrivi
                WHERE yhtio     = '$kukarow[yhtio]'
                and varattu + jt > 0
                and otunnus     = '$tilausnumero'
                {$tilausrivi_where}
                and rivihinta   = 0
                and uusiotunnus = 0
                ORDER BY hinta";
    }
    else {
      echo "<font class='error'>".t("Jyvitys ep�onnistui")."!</font><br>";
      $boob_jyva = 1;
    }

    if ($boob_jyva == 0) {
      $result = pupe_query($query);

      while ($tirow = mysql_fetch_assoc($result)) {
        $uushinta = round($tirow['hinta'] * $jypro, $yhtiorow['hintapyoristys']);

        $query = "UPDATE tilausrivi SET hinta = '$uushinta' WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tirow[tunnus]'";
        $upresult = pupe_query($query);
      }

      //  P�ivitet��n laskulle hinta-kentt��n sy�tetty summa, mutta vain siin� tapauksessa, ett� sy�tetty summa on verollinen.
      if ($yhtiorow['alv_kasittely'] == '' or ($yhtiorow['alv_kasittely'] != '' and $jyvitetaan_ristiin == "X")) {
        $query = "UPDATE lasku SET hinta = '$jysum' WHERE tunnus = '$kukarow[kesken]' and yhtio = '$kukarow[yhtio]'";
        $result = pupe_query($query);

        $laskurow["hinta"] = $jysum;
      }
    }
  }
}

$jysum   = '';
$tila   = '';
$tee   = '';
$kiekat  = "";
$summa    = "";
$anna_ea = "";
$hinta   = "";
