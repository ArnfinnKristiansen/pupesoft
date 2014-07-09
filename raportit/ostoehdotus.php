<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 1;

// Ei k�ytet� pakkausta
$compression = FALSE;

if (isset($_POST["tee"])) {
  if ($_POST["tee"] == 'lataa_tiedosto') $lataa_tiedosto = 1;
  if ($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
}

require("../inc/parametrit.inc");

if (isset($tee) and $tee == "lataa_tiedosto") {
  readfile("/tmp/".$tmpfilenimi);
  exit;
}

include('inc/pupeExcel.inc');
$worksheet 	 = new pupeExcel();
$format_bold = array("bold" => TRUE);
$excelrivi = 0;

//arrayt otsikoiden ja tuoterivien k�sittelyyn - rivien kirjoitus tehd��n arrayden kautta koska write komento haluaa tiet�� sarakkeen numeron ja sarakkeiden m��r� on helponta vaan k�sitell� loopilla
$headerivi = array();

require_once ('inc/ProgressBar.class.php');

echo "<font class='head'>".t("Ostoehdotus")."</font><hr>";

$useampi_yhtio = 0;
if (is_array($valitutyhtiot)) {
  foreach ($valitutyhtiot as $yhtio) {
    $yhtiot .= "'$yhtio',";
    $useampi_yhtio++;
  }
  $yhtiot = substr($yhtiot, 0, -1);
}

if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

// Jos jt-rivit varaavat saldoa niin se vaikuttaa asioihin
if ($yhtiorow["varaako_jt_saldoa"] != "") {
  $lisavarattu = " + tilausrivi.varattu";
}
else {
  $lisavarattu = "";
}

function myynnit($myynti_varasto = '', $myynti_maa = '') {

  // otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
  extract($GLOBALS);

  $laskuntoimmaa = "";
  $varastotapa   = "  JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio  AND varastopaikat.tunnus = tilausrivi.varasto) ";

  if ($myynti_varasto != "") {
    $varastotapa .= " and varastopaikat.tunnus = '$myynti_varasto' ";
  }
  elseif ($erikoisvarastot != "") {
    $varastotapa .= " and varastopaikat.tyyppi = '' ";
  }
  else {
    $varastotapa = "";
  }

  if ($myynti_maa != "") {
    $laskuntoimmaa = " and lasku.toim_maa = '$myynti_maa' ";
  }

  // tutkaillaan myynti
  $query = "SELECT
            sum(if(tilausrivi.tyyppi = 'L' and laadittu >= '$vva1-$kka1-$ppa1 00:00:00' and laadittu <= '$vvl1-$kkl1-$ppl1 23:59:59' and var='P', tilkpl,0)) puutekpl1,
            sum(if(tilausrivi.tyyppi = 'L' and laadittu >= '$vva2-$kka2-$ppa2 00:00:00' and laadittu <= '$vvl2-$kkl2-$ppl2 23:59:59' and var='P', tilkpl,0)) puutekpl2,
            sum(if(tilausrivi.tyyppi = 'L' and laadittu >= '$vva3-$kka3-$ppa3 00:00:00' and laadittu <= '$vvl3-$kkl3-$ppl3 23:59:59' and var='P', tilkpl,0)) puutekpl3,
            sum(if(tilausrivi.tyyppi = 'L' and laadittu >= '$vva4-$kka4-$ppa4 00:00:00' and laadittu <= '$vvl4-$kkl4-$ppl4 23:59:59' and var='P', tilkpl,0)) puutekpl4,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,kpl,0)) kpl1,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,kpl,0)) kpl2,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,kpl,0)) kpl3,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,kpl,0)) kpl4,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva1ed-$kka1ed-$ppa1ed' and laskutettuaika <= '$vvl1ed-$kkl1ed-$ppl1ed' ,kpl,0)) EDkpl1,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva2ed-$kka2ed-$ppa2ed' and laskutettuaika <= '$vvl2ed-$kkl2ed-$ppl2ed' ,kpl,0)) EDkpl2,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva3ed-$kka3ed-$ppa3ed' and laskutettuaika <= '$vvl3ed-$kkl3ed-$ppl3ed' ,kpl,0)) EDkpl3,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4ed-$kka4ed-$ppa4ed' and laskutettuaika <= '$vvl4ed-$kkl4ed-$ppl4ed' ,kpl,0)) EDkpl4,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,tilausrivi.kate,0)) kate1,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,tilausrivi.kate,0)) kate2,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,tilausrivi.kate,0)) kate3,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,tilausrivi.kate,0)) kate4,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva1-$kka1-$ppa1' and laskutettuaika <= '$vvl1-$kkl1-$ppl1' ,rivihinta,0)) rivihinta1,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva2-$kka2-$ppa2' and laskutettuaika <= '$vvl2-$kkl2-$ppl2' ,rivihinta,0)) rivihinta2,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva3-$kka3-$ppa3' and laskutettuaika <= '$vvl3-$kkl3-$ppl3' ,rivihinta,0)) rivihinta3,
            sum(if(tilausrivi.tyyppi = 'L' and laskutettuaika >= '$vva4-$kka4-$ppa4' and laskutettuaika <= '$vvl4-$kkl4-$ppl4' ,rivihinta,0)) rivihinta4,
            sum(if((tilausrivi.tyyppi = 'L' or tilausrivi.tyyppi = 'V') and tilausrivi.var not in ('P','J','O','S'), tilausrivi.varattu, 0)) ennpois,
            sum(if(tilausrivi.tyyppi = 'L' and tilausrivi.var in ('J','S'), tilausrivi.jt $lisavarattu, 0)) jt,
            sum(if(tilausrivi.tyyppi = 'E' and tilausrivi.var != 'O', tilausrivi.varattu, 0)) ennakko
            FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
            JOIN lasku USE INDEX (PRIMARY) on (lasku.yhtio = tilausrivi.yhtio and lasku.tunnus = tilausrivi.otunnus $laskuntoimmaa)
            JOIN asiakas USE INDEX (PRIMARY) on (asiakas.yhtio = lasku.yhtio and asiakas.tunnus = lasku.liitostunnus $lisaa3)
            $varastotapa
            WHERE tilausrivi.yhtio in ($yhtiot)
            and tilausrivi.tyyppi  in ('L','V','E')
            and tilausrivi.tuoteno = '$row[tuoteno]'
            and ((tilausrivi.laskutettuaika >= '$apvm' and tilausrivi.laskutettuaika <= '$lpvm') or tilausrivi.laskutettuaika = '0000-00-00')";
  $result   = pupe_query($query);
  $laskurow = mysql_fetch_array($result);

  $katepros1 = 0;
  $katepros2 = 0;
  $katepros3 = 0;
  $katepros4 = 0;

  if ($laskurow['rivihinta1'] <> 0) $katepros1 = round($laskurow['kate1'] / $laskurow['rivihinta1'] * 100,0);
  if ($laskurow['rivihinta2'] <> 0) $katepros2 = round($laskurow['kate2'] / $laskurow['rivihinta2'] * 100,0);
  if ($laskurow['rivihinta3'] <> 0) $katepros3 = round($laskurow['kate3'] / $laskurow['rivihinta3'] * 100,0);
  if ($laskurow['rivihinta4'] <> 0) $katepros4 = round($laskurow['kate4'] / $laskurow['rivihinta4'] * 100,0);

  // Myydyt kappaleet
  $tuoterivi[] = str_replace(".",",",$laskurow['kpl1']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kpl2']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kpl3']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kpl4']);
  $tuoterivi[] = str_replace(".",",",$laskurow['EDkpl1']);
  $tuoterivi[] = str_replace(".",",",$laskurow['EDkpl2']);
  $tuoterivi[] = str_replace(".",",",$laskurow['EDkpl3']);
  $tuoterivi[] = str_replace(".",",",$laskurow['EDkpl4']);

  // Kate
  $tuoterivi[] = str_replace(".",",",$laskurow['kate1']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kate2']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kate3']);
  $tuoterivi[] = str_replace(".",",",$laskurow['kate4']);
  $tuoterivi[] = str_replace(".",",",$katepros1);
  $tuoterivi[] = str_replace(".",",",$katepros2);
  $tuoterivi[] = str_replace(".",",",$katepros3);
  $tuoterivi[] = str_replace(".",",",$katepros4);

  // Puute kappaleet
  $tuoterivi[] = str_replace(".",",",$laskurow['puutekpl1']);
  $tuoterivi[] = str_replace(".",",",$laskurow['puutekpl2']);
  $tuoterivi[] = str_replace(".",",",$laskurow['puutekpl3']);
  $tuoterivi[] = str_replace(".",",",$laskurow['puutekpl4']);

  // Ennakkopoistot ja jt:t
  $tuoterivi[] = str_replace(".",",",$laskurow['ennpois']);
  $tuoterivi[] = str_replace(".",",",$laskurow['jt']);
  $tuoterivi[] = str_replace(".",",",$laskurow['ennakko']);

}

function saldot($myynti_varasto = '', $myynti_maa = '') {

    // Otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
    extract($GLOBALS);

    $varastotapa = "";

    if ($myynti_varasto != "") {
      $varastotapa = " and varastopaikat.tunnus = '$myynti_varasto' ";
    }
    elseif ($erikoisvarastot != "") {
      $varastotapa .= " and varastopaikat.tyyppi = '' ";
    }

    if ($myynti_maa != "") {
      $varastotapa .= " and varastopaikat.maa = '$myynti_maa' ";
    }

    // Kaikkien valittujen varastojen saldo per maa
    $query = "SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
              FROM tuotepaikat
              JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
              $varastotapa
              WHERE tuotepaikat.yhtio in ($yhtiot)
              and tuotepaikat.tuoteno = '$row[tuoteno]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      while ($varrow = mysql_fetch_array($result)) {
        $tuoterivi[] = str_replace(".",",",$varrow['saldo']);
        $tuoterivi[] = str_replace(".",",",$varrow['halytysraja']);
      }
    }
    else {
      $tuoterivi[] = "0";
      $tuoterivi[] = "0";
    }
}

function ostot($myynti_varasto = '', $myynti_maa = '') {
    // Otetaan kaikki muuttujat mukaan funktioon mit� on failissakin
    extract($GLOBALS);

    $varastotapa = " JOIN varastopaikat ON (varastopaikat.yhtio = tilausrivi.yhtio AND varastopaikat.tunnus = tilausrivi.varasto)";

    if ($myynti_varasto != "") {
      $varastotapa .= " AND varastopaikat.tunnus = '$myynti_varasto' ";
    }
    elseif ($erikoisvarastot != "" and $myynti_maa == "") {
      $query    = "SELECT group_concat(tunnus) FROM varastopaikat WHERE yhtio IN ($yhtiot) AND tyyppi = ''";
      $result   = pupe_query($query);
      $laskurow = mysql_fetch_array($result);

      if ($laskurow[0] != "") {
        $varastotapa .= " and varastopaikat.tunnus in ($laskurow[0]) ";
      }
    }
    elseif ($myynti_maa != "") {
      $query    = "SELECT group_concat(tunnus) FROM varastopaikat WHERE yhtio IN ($yhtiot) AND maa = '$myynti_maa'";

      if ($erikoisvarastot != "") {
        $query .= " and tyyppi = '' ";
      }

      $result   = pupe_query($query);
      $laskurow = mysql_fetch_array($result);

      if ($laskurow[0] != "") {
        $varastotapa .= " and varastopaikat.tunnus in ($laskurow[0]) ";
      }
    }
    else {
      $varastotapa = "";
    }

    // Tilauksessa/siirtolistalla jt
    $query = "SELECT
              sum(if (tilausrivi.tyyppi = 'O', tilausrivi.varattu, 0)) tilattu,
              sum(if (tilausrivi.tyyppi = 'G', tilausrivi.jt $lisavarattu, 0)) siirtojt
              FROM tilausrivi use index (yhtio_tyyppi_tuoteno_laskutettuaika)
              $varastotapa
              WHERE tilausrivi.yhtio IN ($yhtiot)
              AND tilausrivi.tyyppi  IN ('O','G')
              AND tilausrivi.tuoteno = '$row[tuoteno]'
              AND tilausrivi.varattu + tilausrivi.jt > 0";
    $result = pupe_query($query);
    $ostorow = mysql_fetch_array($result);

    // Siirtolista jt kpl
    $tuoterivi[] = str_replace(".",",",$ostorow['siirtojt']);
    // Tilattu kpl
    $tuoterivi[] = str_replace(".",",",$ostorow['tilattu']);
}

// org_rajausta tarvitaan yhdess� selectiss� joka trigger�i taas toisen asian.
$org_rajaus = $abcrajaus;
list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

if (!isset($abcrajaustapa)) $abcrajaustapa = "TK";

list($ryhmanimet, $ryhmaprossat, , , , ) = hae_ryhmanimet($abcrajaustapa);

// Tarvittavat p�iv�m��r�t
if (!isset($kka1)) $kka1 = date("m",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($vva1)) $vva1 = date("Y",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($ppa1)) $ppa1 = date("d",mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
if (!isset($kkl1)) $kkl1 = date("m");
if (!isset($vvl1)) $vvl1 = date("Y");
if (!isset($ppl1)) $ppl1 = date("d");

if (!isset($kka2)) $kka2 = date("m",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($vva2)) $vva2 = date("Y",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($ppa2)) $ppa2 = date("d",mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
if (!isset($kkl2)) $kkl2 = date("m");
if (!isset($vvl2)) $vvl2 = date("Y");
if (!isset($ppl2)) $ppl2 = date("d");

if (!isset($kka3)) $kka3 = date("m",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($vva3)) $vva3 = date("Y",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($ppa3)) $ppa3 = date("d",mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
if (!isset($kkl3)) $kkl3 = date("m");
if (!isset($vvl3)) $vvl3 = date("Y");
if (!isset($ppl3)) $ppl3 = date("d");

if (!isset($kka4)) $kka4 = date("m",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($vva4)) $vva4 = date("Y",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($ppa4)) $ppa4 = date("d",mktime(0, 0, 0, date("m")-12, date("d"), date("Y")));
if (!isset($kkl4)) $kkl4 = date("m");
if (!isset($vvl4)) $vvl4 = date("Y");
if (!isset($ppl4)) $ppl4 = date("d");

//Edellisen vuoden vastaavat kaudet
$kka1ed = date("m",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$vva1ed = date("Y",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$ppa1ed = date("d",mktime(0, 0, 0, $kka1, $ppa1, $vva1-1));
$kkl1ed = date("m",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$vvl1ed = date("Y",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));
$ppl1ed = date("d",mktime(0, 0, 0, $kkl1, $ppl1, $vvl1-1));

$kka2ed = date("m",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$vva2ed = date("Y",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$ppa2ed = date("d",mktime(0, 0, 0, $kka2, $ppa2, $vva2-1));
$kkl2ed = date("m",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$vvl2ed = date("Y",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));
$ppl2ed = date("d",mktime(0, 0, 0, $kkl2, $ppl2, $vvl2-1));

$kka3ed = date("m",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$vva3ed = date("Y",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$ppa3ed = date("d",mktime(0, 0, 0, $kka3, $ppa3, $vva3-1));
$kkl3ed = date("m",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$vvl3ed = date("Y",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));
$ppl3ed = date("d",mktime(0, 0, 0, $kkl3, $ppl3, $vvl3-1));

$kka4ed = date("m",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$vva4ed = date("Y",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$ppa4ed = date("d",mktime(0, 0, 0, $kka4, $ppa4, $vva4-1));
$kkl4ed = date("m",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$vvl4ed = date("Y",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));
$ppl4ed = date("d",mktime(0, 0, 0, $kkl4, $ppl4, $vvl4-1));

//katotaan pienin alkupvm ja isoin loppupvm
$apaiva1 = (int) date('Ymd',mktime(0,0,0,$kka1,$ppa1,$vva1));
$apaiva2 = (int) date('Ymd',mktime(0,0,0,$kka2,$ppa2,$vva2));
$apaiva3 = (int) date('Ymd',mktime(0,0,0,$kka3,$ppa3,$vva3));
$apaiva4 = (int) date('Ymd',mktime(0,0,0,$kka4,$ppa4,$vva4));
$apaiva5 = (int) date('Ymd',mktime(0,0,0,$kka1ed,$ppa1ed,$vva1ed));
$apaiva6 = (int) date('Ymd',mktime(0,0,0,$kka2ed,$ppa2ed,$vva2ed));
$apaiva7 = (int) date('Ymd',mktime(0,0,0,$kka3ed,$ppa3ed,$vva3ed));
$apaiva8 = (int) date('Ymd',mktime(0,0,0,$kka4ed,$ppa4ed,$vva4ed));

$lpaiva1 = (int) date('Ymd',mktime(0,0,0,$kkl1,$ppl1,$vvl1));
$lpaiva2 = (int) date('Ymd',mktime(0,0,0,$kkl2,$ppl2,$vvl2));
$lpaiva3 = (int) date('Ymd',mktime(0,0,0,$kkl3,$ppl3,$vvl3));
$lpaiva4 = (int) date('Ymd',mktime(0,0,0,$kkl4,$ppl4,$vvl4));
$lpaiva5 = (int) date('Ymd',mktime(0,0,0,$kkl1ed,$ppl1ed,$vvl1ed));
$lpaiva6 = (int) date('Ymd',mktime(0,0,0,$kkl2ed,$ppl2ed,$vvl2ed));
$lpaiva7 = (int) date('Ymd',mktime(0,0,0,$kkl3ed,$ppl3ed,$vvl3ed));
$lpaiva8 = (int) date('Ymd',mktime(0,0,0,$kkl4ed,$ppl4ed,$vvl4ed));

$apienin = 99999999;
$lsuurin = 0;

if ($apaiva1 <= $apienin and $apaiva1 != 19700101) $apienin = $apaiva1;
if ($apaiva2 <= $apienin and $apaiva2 != 19700101) $apienin = $apaiva2;
if ($apaiva3 <= $apienin and $apaiva3 != 19700101) $apienin = $apaiva3;
if ($apaiva4 <= $apienin and $apaiva4 != 19700101) $apienin = $apaiva4;
if ($apaiva5 <= $apienin and $apaiva5 != 19700101) $apienin = $apaiva5;
if ($apaiva6 <= $apienin and $apaiva6 != 19700101) $apienin = $apaiva6;
if ($apaiva7 <= $apienin and $apaiva7 != 19700101) $apienin = $apaiva7;
if ($apaiva8 <= $apienin and $apaiva8 != 19700101) $apienin = $apaiva8;

if ($lpaiva1 >= $lsuurin and $lpaiva1 != 19700101) $lsuurin = $lpaiva1;
if ($lpaiva2 >= $lsuurin and $lpaiva2 != 19700101) $lsuurin = $lpaiva2;
if ($lpaiva3 >= $lsuurin and $lpaiva3 != 19700101) $lsuurin = $lpaiva3;
if ($lpaiva4 >= $lsuurin and $lpaiva4 != 19700101) $lsuurin = $lpaiva4;
if ($lpaiva5 >= $lsuurin and $lpaiva5 != 19700101) $lsuurin = $lpaiva5;
if ($lpaiva6 >= $lsuurin and $lpaiva6 != 19700101) $lsuurin = $lpaiva6;
if ($lpaiva7 >= $lsuurin and $lpaiva7 != 19700101) $lsuurin = $lpaiva7;
if ($lpaiva8 >= $lsuurin and $lpaiva8 != 19700101) $lsuurin = $lpaiva8;

if ($apienin == 99999999 and $lsuurin == 0) {
  $apienin = $lsuurin = date('Ymd'); // jos mit��n ei l�ydy niin NOW molempiin. :)
}

$apvm = substr($apienin,0,4)."-".substr($apienin,4,2)."-".substr($apienin,6,2);
$lpvm = substr($lsuurin,0,4)."-".substr($lsuurin,4,2)."-".substr($lsuurin,6,2);


// katsotaan tarvitaanko menn� toimittajahakuun
if (($ytunnus != "" and $toimittajaid == "") or ($edytunnus != $ytunnus)) {
  if ($edytunnus != $ytunnus) $toimittajaid = "";
  require ("inc/kevyt_toimittajahaku.inc");
  $ytunnus = $toimittajarow["ytunnus"];
  $tee = "";
}

// tehd��n itse raportti
if ($tee == "RAPORTOI" and isset($ehdotusnappi)) {
  // haetaan nimitietoa
  if ($tuoryh != '') {
    // tehd��n avainsana query
    $sresult = t_avainsana("TRY", "", "and avainsana.selite ='$tuoryh'", $yhtiot);
    $trow1 = mysql_fetch_array($sresult);
  }
  if ($osasto != '') {
    // tehd��n avainsana query
    $sresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$osasto'", $yhtiot);
    $trow2 = mysql_fetch_array($sresult);
  }
  if ($toimittajaid != '') {
    $query = "SELECT nimi
              FROM toimi
              WHERE yhtio in ($yhtiot) and tunnus='$toimittajaid'";
    $sresult = pupe_query($query);
    $trow3 = mysql_fetch_array($sresult);
  }

  $lisaa  = ""; // tuote-rajauksia
  $lisaa2 = ""; // toimittaja-rajauksia
  $lisaa3 = ""; // asiakas-rajauksia

  if ($osasto != '') {
    $lisaa .= " and tuote.osasto = '$osasto' ";
  }
  if ($tuoryh != '') {
    $lisaa .= " and tuote.try = '$tuoryh' ";
  }
  if ($tuotemerkki != '') {
    $lisaa .= " and tuote.tuotemerkki = '$tuotemerkki' ";
  }
  if ($poistetut != '') {
    $lisaa .= " and tuote.status != 'P' ";
  }
  if ($poistuva != '') {
    $lisaa .= " and tuote.status != 'X' ";
  }
  if ($eihinnastoon != '') {
    $lisaa .= " and tuote.hinnastoon != 'E' ";
  }
  if ($vainuudet != '') {
    $lisaa .= " and tuote.luontiaika >= date_sub(current_date, interval 12 month) ";
  }
  if ($eiuusia != '') {
    $lisaa .= " and tuote.luontiaika < date_sub(current_date, interval 12 month) ";
  }
  if ($toimittajaid != '') {
    $lisaa2 .= " JOIN tuotteen_toimittajat ON (tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno and liitostunnus = '$toimittajaid') ";
  }
  if ($eliminoikonserni != '') {
    $lisaa3 .= " and asiakas.konserniyhtio = '' ";
  }

  $abcnimi = $ryhmanimet[$abcrajaus];

  $varastot_paikoittain = "";

  if (is_array($valitutvarastot) and count($valitutvarastot) > 0) {
    $varastot_paikoittain = "KYLLA";
  }

  $maa_varastot       = "";
  $varastot_maittain    = "";

  if (is_array($valitutmaat) and count($valitutmaat) > 0) {
    $varastot_maittain = "KYLLA";

    // katsotaan valitut varastot
    $query = "SELECT *
              FROM varastopaikat
              WHERE yhtio in ($yhtiot)
              ORDER BY yhtio, tyyppi, nimitys";
    $vtresult = pupe_query($query);

    while ($vrow = mysql_fetch_array($vtresult)) {
      if (in_array($vrow["maa"], $valitutmaat)) {
        $maa_varastot .= "'".$vrow["tunnus"]."',";
      }
    }
  }

  $maa_varastot      = substr($maa_varastot,0,-1);
  $maa_varastot_yhtiot = substr($maa_varastot_yhtiot,0,-1);

  // katotaan JT:ss� olevat tuotteet ABC-analyysi� varten, koska ne pit�� includata aina!
  $query = "SELECT group_concat(distinct concat(\"'\",tilausrivi.tuoteno,\"'\") separator ',')
            FROM tilausrivi USE INDEX (yhtio_tyyppi_var_keratty_kerattyaika_uusiotunnus)
            JOIN tuote USE INDEX (tuoteno_index) ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno $lisaa)
            WHERE tilausrivi.yhtio in ($yhtiot)
            and tyyppi             IN  ('L','G')
            and var                = 'J'
            and jt $lisavarattu > 0";
  $vtresult = pupe_query($query);
  $vrow = mysql_fetch_array($vtresult);

  $jt_tuotteet = "''";

  if ($vrow[0] != "") {
    $jt_tuotteet = $vrow[0];
  }

  if ($abcrajaus != "") {
    // joinataan ABC-aputaulu katteen mukaan lasketun luokan perusteella
    $abcjoin = " JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio
          and abc_aputaulu.tuoteno = tuote.tuoteno
          and abc_aputaulu.tyyppi = '$abcrajaustapa'
          and (luokka <= '$abcrajaus' or luokka_osasto <= '$abcrajaus' or luokka_try <= '$abcrajaus' or tuote_luontiaika >= date_sub(current_date, interval 12 month) or abc_aputaulu.tuoteno in ($jt_tuotteet))) ";
  }
  else {
    $abcjoin = " LEFT JOIN abc_aputaulu use index (yhtio_tyyppi_tuoteno) ON (abc_aputaulu.yhtio = tuote.yhtio and abc_aputaulu.tuoteno = tuote.tuoteno and abc_aputaulu.tyyppi = '$abcrajaustapa') ";
  }

  // t�ss� haetaan sitten listalle soveltuvat tuotteet
  $query = "SELECT
            group_concat(tuote.yhtio) yhtio,
            tuote.tuoteno,
            tuote.halytysraja,
            tuote.tahtituote,
            tuote.status,
            tuote.nimitys,
            tuote.myynti_era,
            tuote.myyntihinta,
            tuote.epakurantti25pvm,
            tuote.epakurantti50pvm,
            tuote.epakurantti75pvm,
            tuote.epakurantti100pvm,
            tuote.tuotemerkki,
            tuote.osasto,
            tuote.try,
            tuote.aleryhma,
            if(tuote.epakurantti100pvm = '0000-00-00', if(tuote.epakurantti75pvm = '0000-00-00', if(tuote.epakurantti50pvm = '0000-00-00', if(tuote.epakurantti25pvm = '0000-00-00', tuote.kehahin, tuote.kehahin * 0.75), tuote.kehahin * 0.5), tuote.kehahin * 0.25), 0) kehahin,
            abc_aputaulu.luokka abcluokka,
            abc_aputaulu.luokka_osasto abcluokka_osasto,
            abc_aputaulu.luokka_try abcluokka_try,
            tuote.luontiaika
            FROM tuote
            $lisaa2
            $abcjoin
            LEFT JOIN korvaavat ON (tuote.yhtio = korvaavat.yhtio and tuote.tuoteno = korvaavat.tuoteno)
            WHERE
            tuote.yhtio           in ($yhtiot)
            $lisaa
            and tuote.ei_saldoa   = ''
            and tuote.tuotetyyppi NOT IN ('A', 'B')
            and tuote.ostoehdotus = ''
            GROUP BY tuote.tuoteno
            ORDER BY id, tuote.tuoteno, yhtio";
  $res = pupe_query($query);

  echo t("Tuotteita")." ".mysql_num_rows($res)." ".t("kpl").".<br>\n";
  flush();

  $rivi = "";

  $bar = new ProgressBar();
  $elements = mysql_num_rows($res); //total number of elements to process
  $bar->initialize($elements); //print the empty bar

  // Laitetaan otsikkotiedot otsikkoarrayn sis��n
  if ($useampi_yhtio > 1) {
    $headerivi[] = ucfirst(t("yhtio"));
  }

  $headerivi[] = ucfirst(t("tuoteno"));
  $headerivi[] = ucfirst(t("osasto"));
  $headerivi[] = ucfirst(t("try"));
  $headerivi[] = ucfirst(t("tuotemerkki"));
  $headerivi[] = ucfirst(t("t�htituote"));
  $headerivi[] = ucfirst(t("status"));
  $headerivi[] = ucfirst(t("abc"));
  $headerivi[] = ucfirst(t("abc osasto"));
  $headerivi[] = ucfirst(t("abc try"));
  $headerivi[] = ucfirst(t("luontiaika"));
  $headerivi[] = ucfirst(t("tuotteen h�lytysraja"));
  $headerivi[] = ucfirst(t("ostoer�"));
  $headerivi[] = ucfirst(t("myyntier�"));
  $headerivi[] = ucfirst(t("toimittaja"));
  $headerivi[] = ucfirst(t("toim tuoteno"));
  $headerivi[] = ucfirst(t("nimitys"));
  $headerivi[] = ucfirst(t("toim nimitys"));
  $headerivi[] = ucfirst(t("ostohinta"));
  $headerivi[] = ucfirst(t("myyntihinta"));
  $headerivi[] = ucfirst(t("ep�kurantti25%"));
  $headerivi[] = ucfirst(t("ep�kurantti50%"));
  $headerivi[] = ucfirst(t("ep�kurantti75%"));
  $headerivi[] = ucfirst(t("ep�kurantti100%"));
  $headerivi[] = ucfirst(t("tuotekerroin"));
  $headerivi[] = ucfirst(t("aleryhm�"));
  $headerivi[] = ucfirst(t("keskihankintahinta"));

  // rullataan l�p� maittain
  if ($varastot_maittain == "KYLLA") {
    foreach ($valitutmaat as $maa) {
      //kirjoitetaan myyntiotsikot (myyntifunktioslle)
      // Myydyt kappaleet
      $headerivi[] = ucfirst(t("$maa kpl1"));
      $headerivi[] = ucfirst(t("$maa kpl2"));
      $headerivi[] = ucfirst(t("$maa kpl3"));
      $headerivi[] = ucfirst(t("$maa kpl4"));
      $headerivi[] = ucfirst(t("$maa edkpl1"));
      $headerivi[] = ucfirst(t("$maa edkpl2"));
      $headerivi[] = ucfirst(t("$maa edkpl3"));
      $headerivi[] = ucfirst(t("$maa edkpl4"));

      // Kate
      $headerivi[] = ucfirst(t("$maa kate1"));
      $headerivi[] = ucfirst(t("$maa kate2"));
      $headerivi[] = ucfirst(t("$maa kate3"));
      $headerivi[] = ucfirst(t("$maa kate4"));
      $headerivi[] = ucfirst(t("$maa katepro1"));
      $headerivi[] = ucfirst(t("$maa katepro2"));
      $headerivi[] = ucfirst(t("$maa katepro3"));
      $headerivi[] = ucfirst(t("$maa katepro4"));

      // Puute kappaleet
      $headerivi[] = ucfirst(t("$maa puutekpl1"));
      $headerivi[] = ucfirst(t("$maa puutekpl2"));
      $headerivi[] = ucfirst(t("$maa puutekpl3"));
      $headerivi[] = ucfirst(t("$maa puutekpl4"));

      // Ennakkopoistot ja jt:t
      $headerivi[] = ucfirst(t("$maa ennpois kpl"));
      $headerivi[] = ucfirst(t("$maa jt kpl"));
      $headerivi[] = ucfirst(t("$maa ennakkotilaus kpl"));

      if ($erikoisvarastot != "") {
        $varastotapa = " AND varastopaikat.tyyppi = '' ";
      }

      $varastotapa .= " AND varastopaikat.maa = '$maa'";

      //kirjoitetaan saldo otsikot (saldofunkitolle)
      // Kaikkien valittujen varastojen saldo per maa
      $query = "SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
                FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
                $varastotapa
                WHERE tuotepaikat.yhtio in ($yhtiot)
                and tuotepaikat.tuoteno = '$row[tuoteno]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        while ($varrow = mysql_fetch_array($result)) {
          $headerivi[] = ucfirst(t("$maa saldo"));
          $headerivi[] = ucfirst(t("$maa h�lytysraja"));
        }
      }
      else {
        $headerivi[] = ucfirst(t("$maa saldo"));
        $headerivi[] = ucfirst(t("$maa h�lytysraja"));
      }

      //kirjoitetaan osto otsikot (ostofunktille)
      // siirtolista jt kpl
      $headerivi[] = ucfirst(t("$maa siirtojt kpl"));
      // tilattu kpl
      $headerivi[] = ucfirst(t("$maa tilattu kpl"));
    }
  }

  // sitten rullataan l�pi varastoittain
  if ($varastot_paikoittain == "KYLLA") {
    foreach ($valitutvarastot as $varastotunnus) {
      $query = "SELECT nimitys from varastopaikat where yhtio in ($yhtiot) and tunnus = '$varastotunnus'";
      $varastorow = mysql_fetch_array(pupe_query($query));
      $varastonimi = $varastorow["nimitys"];

      //kirjoitetaan myyntiotsikot (myyntifunktioslle)
      // Myydyt kappaleet
      $headerivi[] = ucfirst(t("$varastonimi kpl1"));
      $headerivi[] = ucfirst(t("$varastonimi kpl2"));
      $headerivi[] = ucfirst(t("$varastonimi kpl3"));
      $headerivi[] = ucfirst(t("$varastonimi kpl4"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl1"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl2"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl3"));
      $headerivi[] = ucfirst(t("$varastonimi edkpl4"));

      // Kate
      $headerivi[] = ucfirst(t("$varastonimi kate1"));
      $headerivi[] = ucfirst(t("$varastonimi kate2"));
      $headerivi[] = ucfirst(t("$varastonimi kate3"));
      $headerivi[] = ucfirst(t("$varastonimi kate4"));
      $headerivi[] = ucfirst(t("$varastonimi katepro1"));
      $headerivi[] = ucfirst(t("$varastonimi katepro2"));
      $headerivi[] = ucfirst(t("$varastonimi katepro3"));
      $headerivi[] = ucfirst(t("$varastonimi katepro4"));

      // Puute kappaleet
      $headerivi[] = ucfirst(t("$varastonimi puutekpl1"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl2"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl3"));
      $headerivi[] = ucfirst(t("$varastonimi puutekpl4"));

      // Ennakkopoistot ja jt:t
      $headerivi[] = ucfirst(t("$varastonimi ennpois kpl"));
      $headerivi[] = ucfirst(t("$varastonimi jt kpl"));
      $headerivi[] = ucfirst(t("$varastonimi ennakkotilaus kpl"));

      //kirjoitetaan saldo otsikot (saldofunkitolle)
      $varastotapa = " and varastopaikat.tunnus = '$varastotunnus' ";

      // Kaikkien valittujen varastojen saldo
      $query = "SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
                FROM tuotepaikat
                JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
                $varastotapa
                WHERE tuotepaikat.yhtio in ($yhtiot)
                and tuotepaikat.tuoteno = '$row[tuoteno]'";
      $result = pupe_query($query);

      if (mysql_num_rows($result) > 0) {
        while ($varrow = mysql_fetch_array($result)) {
          $headerivi[] = ucfirst(t("$varastotunnus saldo"));
          $headerivi[] = ucfirst(t("$varastotunnus h�lytysraja"));
        }
      }
      else {
        $headerivi[] = ucfirst(t("$varastotunnus saldo"));
        $headerivi[] = ucfirst(t("$varastotunnus h�lytysraja"));
      }

      //kirjoitetaan osto otsikot (ostofunktille)
      // siirtolista jt kpl
      $headerivi[] = ucfirst(t("$varastotunnus siirtojt kpl"));
      // tilattu kpl
      $headerivi[] = ucfirst(t("$varastotunnus tilattu kpl"));
    }
  }

  // sitte viel� totalit
  //kirjoitetaan myyntiotsikot (myyntifunktiolle)
  // Myydyt kappaleet
  $headerivi[] = ucfirst(t("Total kpl1"));
  $headerivi[] = ucfirst(t("Total kpl2"));
  $headerivi[] = ucfirst(t("Total kpl3"));
  $headerivi[] = ucfirst(t("Total kpl4"));
  $headerivi[] = ucfirst(t("Total edkpl1"));
  $headerivi[] = ucfirst(t("Total edkpl2"));
  $headerivi[] = ucfirst(t("Total edkpl3"));
  $headerivi[] = ucfirst(t("Total edkpl4"));

  // Kate
  $headerivi[] = ucfirst(t("Total kate1"));
  $headerivi[] = ucfirst(t("Total kate2"));
  $headerivi[] = ucfirst(t("Total kate3"));
  $headerivi[] = ucfirst(t("Total kate4"));
  $headerivi[] = ucfirst(t("Total katepro1"));
  $headerivi[] = ucfirst(t("Total katepro2"));
  $headerivi[] = ucfirst(t("Total katepro3"));
  $headerivi[] = ucfirst(t("Total katepro4"));

  // Puute kappaleet
  $headerivi[] = ucfirst(t("Total puutekpl1"));
  $headerivi[] = ucfirst(t("Total puutekpl2"));
  $headerivi[] = ucfirst(t("Total puutekpl3"));
  $headerivi[] = ucfirst(t("Total puutekpl4"));

  // Ennakkopoistot ja jt:t
  $headerivi[] = ucfirst(t("Total ennpois kpl"));
  $headerivi[] = ucfirst(t("Total jt kpl"));
  $headerivi[] = ucfirst(t("Total ennakkotilaus kpl"));

  //kirjoitetaan saldo otsikot (saldofunkitolle)
  if ($erikoisvarastot != "") {
    $varastotapa = " AND varastopaikat.tyyppi = '' ";
  }

  // Kaikkien valittujen varastojen saldo per maa
  $query = "SELECT ifnull(sum(saldo),0) saldo, ifnull(sum(halytysraja),0) halytysraja
            FROM tuotepaikat
            JOIN varastopaikat ON (varastopaikat.yhtio = tuotepaikat.yhtio AND varastopaikat.tunnus = tuotepaikat.varasto)
            $varastotapa
            WHERE tuotepaikat.yhtio in ($yhtiot)
            and tuotepaikat.tuoteno = '$row[tuoteno]'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) > 0) {
    while ($varrow = mysql_fetch_array($result)) {
      $headerivi[] = ucfirst(t("Total saldo"));
      $headerivi[] = ucfirst(t("Total h�lytysraja"));
    }
  }
  else {
    $headerivi[] = ucfirst(t("Total saldo"));
    $headerivi[] = ucfirst(t("Total h�lytysraja"));
  }

  // kirjoitetaan osto otsikot (ostofunktille)
  // siirtolista jt kpl
  $headerivi[] = ucfirst(t("Total siirtojt kpl"));
  // tilattu kpl
  $headerivi[] = ucfirst(t("Total tilattu kpl"));

  //LOOP
  //kirjoitetaan otsikot tiedostoon
  for ($i=0; $i < count($headerivi); $i++) {
    $worksheet->write($excelrivi, $i, ucfirst(t($headerivi[$i])), $format_bold);
  }
  

  //siirryt��n seuraavalle riville
  $excelrivi++;

  // loopataan tuotteet l�pi
  while ($row = mysql_fetch_array($res)) {

    $toimilisa = "";
    if ($toimittajaid != '') $toimilisa = " and tuotteen_toimittajat.liitostunnus = '$toimittajaid' ";

    // haetaan tuotteen toimittajatietoa
    $query = "SELECT group_concat(toimi.ytunnus             order by tuotteen_toimittajat.tunnus separator '/') toimittaja,
              group_concat(distinct tuotteen_toimittajat.osto_era   order by tuotteen_toimittajat.tunnus separator '/') osto_era,
              group_concat(distinct tuotteen_toimittajat.toim_tuoteno order by tuotteen_toimittajat.tunnus separator '/') toim_tuoteno,
              group_concat(distinct tuotteen_toimittajat.toim_nimitys order by tuotteen_toimittajat.tunnus separator '/') toim_nimitys,
              group_concat(distinct tuotteen_toimittajat.ostohinta   order by tuotteen_toimittajat.tunnus separator '/') ostohinta,
              group_concat(distinct tuotteen_toimittajat.tuotekerroin order by tuotteen_toimittajat.tunnus separator '/') tuotekerroin
              FROM tuotteen_toimittajat
              JOIN toimi ON toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus
              WHERE tuotteen_toimittajat.yhtio in ($yhtiot)
              and tuotteen_toimittajat.tuoteno = '$row[tuoteno]'
              $toimilisa";
    $result   = pupe_query($query);
    $toimirow = mysql_fetch_array($result);

    // kaunistellaan kentti�
    if ($row["luontiaika"] == "0000-00-00 00:00:00") $row["luontiaika"] = "";
    if ($row['epakurantti25pvm'] == '0000-00-00')     $row['epakurantti25pvm'] = "";
    if ($row['epakurantti50pvm'] == '0000-00-00')     $row['epakurantti50pvm'] = "";
    if ($row['epakurantti75pvm'] == '0000-00-00')     $row['epakurantti75pvm'] = "";
    if ($row['epakurantti100pvm'] == '0000-00-00')     $row['epakurantti50pvm'] = "";

    // haetaan abc luokille nimet
    $abcnimi = $ryhmanimet[$row["abcluokka"]];
    $abcnimi2 = $ryhmanimet[$row["abcluokka_osasto"]];
    $abcnimi3 = $ryhmanimet[$row["abcluokka_try"]];

    // kirjoitetaan itse rivi�;
    if ($useampi_yhtio > 1) {
      $tuoterivi[] = $row["yhtio"];
    }

    $tuoterivi[] = $row["tuoteno"];
    $tuoterivi[] = $row["osasto"];
    $tuoterivi[] = $row["try"];
    $tuoterivi[] = $row["tuotemerkki"];
    $tuoterivi[] = $row["tahtituote"];
    $tuoterivi[] = $row["status"];
    $tuoterivi[] = $abcnimi;
    $tuoterivi[] = $abcnimi2;
    $tuoterivi[] = $abcnimi3;
    $tuoterivi[] = $row["luontiaika"];
    $tuoterivi[] = str_replace(".",",",$row['halytysraja']);
    $tuoterivi[] = $toimirow["osto_era"];
    $tuoterivi[] = str_replace(".",",",$row['myynti_era']);
    $tuoterivi[] = $toimirow["toimittaja"];
    $tuoterivi[] = $toimirow["toim_tuoteno"];
    $tuoterivi[] = t_tuotteen_avainsanat($row, 'nimitys');
    $tuoterivi[] = $toimirow["toim_nimitys"];
    $tuoterivi[] = str_replace(".",",",$toimirow['ostohinta']);
    $tuoterivi[] = str_replace(".",",",$row['myyntihinta']);
    $tuoterivi[] = $row["epakurantti25pvm"];
    $tuoterivi[] = $row["epakurantti50pvm"];
    $tuoterivi[] = $row["epakurantti75pvm"];
    $tuoterivi[] = $row["epakurantti100pvm"];
    $tuoterivi[] = str_replace(".",",",$toimirow['tuotekerroin']);
    $tuoterivi[] = $row["aleryhma"];
    $tuoterivi[] = str_replace(".",",",$row["kehahin"]);

    // Rullataan l�p� maittain
    if ($varastot_maittain == "KYLLA") {
      foreach ($valitutmaat as $maa) {
        // Haetaan tuotteen myyntitiedot
        myynnit('', $maa);
        // Haetaan tuotteen saldotiedot
        saldot('', $maa);
        // Haetaan tuotteen ostotiedot
        ostot('', $maa);
      }
    }

    // Sitten rullataan l�pi varastoittain
    if ($varastot_paikoittain == "KYLLA") {
      foreach ($valitutvarastot as $varastotunnus) {
        // Haetaan tuotteen myyntitiedot
        myynnit($varastotunnus);
        // Haetaan tuotteen saldotiedot
        saldot($varastotunnus);
        // Haetaan tuotteen ostotiedot
        ostot($varastotunnus);
      }
    }

    // Sitten viel� totalit
    myynnit();
    saldot();
    ostot();

    //kirjoitetaan tuoterivin tiedot tiedostoon
    for ($i=0; $i < count($tuoterivi); $i++) {
      $worksheet->write($excelrivi, $i, ucfirst(t($tuoterivi[$i])), $format_bold);
    }

    //siirryt��n seuraavalle riville
    $excelrivi++;
    $tuoterivi = array();

    $bar->increase(); //calls the bar with every processed element

  }

    $excelnimi = $worksheet->close();

    echo "<br><br>";
    echo "<table>";
    echo "<tr><th>".t("Tallenna raportti (xlsx)").":</th>";
    echo "<form method='post' class='multisubmit'>";
    echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
    echo "<input type='hidden' name='kaunisnimi' value='Ostoehdotus.xlsx'>";
    echo "<input type='hidden' name='tmpfilenimi' value='$excelnimi'>";
    echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
    echo "</table><br>";
}

// n�ytet��n k�ytt�liittym�..
if ($tee == "" or !isset($ehdotusnappi)) {

  $abcnimi = $ryhmanimet[$abcrajaus];

  echo "  <form method='post' autocomplete='off'>
      <input type='hidden' name='tee' value='RAPORTOI'>

      <table>";

  echo "<tr><th>".t("Osasto")."</th><td colspan='3'>";

  // tehd��n avainsana query
  $sresult = t_avainsana("OSASTO", "", "", $yhtiot);

  echo "<select name='osasto'>";
  echo "<option value=''>".t("N�yt� kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($osasto == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
  }
  echo "</select>";

  echo "</td></tr>
      <tr><th>".t("Tuoteryhm�")."</th><td colspan='3'>";

  // tehd��n avainsana query
  $sresult = t_avainsana("TRY", "", "", $yhtiot);

  echo "<select name='tuoryh'>";
  echo "<option value=''>".t("N�yt� kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($tuoryh == $srow["selite"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[selite]' $sel>$srow[selite] - $srow[selitetark]</option>";
  }
  echo "</select>";

  echo "</td></tr>
      <tr><th>".t("Tuotemerkki")."</th><td colspan='3'>";

  //Tehd��n osasto & tuoteryhm� pop-upit
  $query = "SELECT distinct tuotemerkki
            FROM tuote
            WHERE yhtio      in ($yhtiot)
            and tuotemerkki != ''
            ORDER BY tuotemerkki";
  $sresult = pupe_query($query);

  echo "<select name='tuotemerkki'>";
  echo "<option value=''>".t("N�yt� kaikki")."</option>";

  while ($srow = mysql_fetch_array($sresult)) {
    $sel = '';
    if ($tuotemerkki == $srow["tuotemerkki"]) {
      $sel = "selected";
    }
    echo "<option value='$srow[tuotemerkki]' $sel>$srow[tuotemerkki]</option>";
  }
  echo "</select>";

  echo "</td></tr>";

  echo "<tr><th>".t("ABC-luokkarajaus ja rajausperuste")."</th><td>";

  echo "<select name='abcrajaus' onchange='submit()'>";
  echo "<option  value=''>".t("Valitse")."</option>";

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TM") $selabc = "SELECTED";

    echo "<option  value='$i##TM' $selabc>".t("Myynti").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TK") $selabc = "SELECTED";

    echo "<option  value='$i##TK' $selabc>".t("Myyntikate").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TR") $selabc = "SELECTED";

    echo "<option  value='$i##TR' $selabc>".t("Myyntirivit").": {$ryhmanimet[$i]} $teksti</option>";
  }

  $teksti = "";
  for ($i=0; $i < count($ryhmaprossat); $i++) {
    $selabc = "";

    if ($i > 0) $teksti = t("ja paremmat");
    if ($org_rajaus == "{$i}##TP") $selabc = "SELECTED";

    echo "<option  value='$i##TP' $selabc>".t("Myyntikappaleet").": {$ryhmanimet[$i]} $teksti</option>";
  }

  echo "</select>";

  list($abcrajaus,$abcrajaustapa) = explode("##",$abcrajaus);

  echo "<tr><th>".t("Toimittaja")."</th><td colspan='3'><input type='text' size='20' name='ytunnus' value='$ytunnus'></td></tr>";
  echo "<input type='hidden' name='edytunnus' value='$ytunnus'>";
  echo "<input type='hidden' name='toimittajaid' value='$toimittajaid'>";

  echo "</table><table><br>";

  echo "  <tr>
      <th></th><th colspan='3'>".t("Alkup�iv�m��r� (pp-kk-vvvv)")."</th>
      <th></th><th colspan='3'>".t("Loppup�iv�m��r� (pp-kk-vvvv)")."</th>
      </tr>";

  echo "  <tr><th>".t("Kausi 1")."</th>
      <td><input type='text' name='ppa1' value='$ppa1' size='5'></td>
      <td><input type='text' name='kka1' value='$kka1' size='5'></td>
      <td><input type='text' name='vva1' value='$vva1' size='5'></td>
      <td class='back'>&nbsp;-&nbsp;</td>
      <td><input type='text' name='ppl1' value='$ppl1' size='5'></td>
      <td><input type='text' name='kkl1' value='$kkl1' size='5'></td>
      <td><input type='text' name='vvl1' value='$vvl1' size='5'></td>
      </tr>";

  echo "  <tr><th>".t("Kausi 2")."</th>
      <td><input type='text' name='ppa2' value='$ppa2' size='5'></td>
      <td><input type='text' name='kka2' value='$kka2' size='5'></td>
      <td><input type='text' name='vva2' value='$vva2' size='5'></td>
      <td class='back'>&nbsp;-&nbsp;</td>
      <td><input type='text' name='ppl2' value='$ppl2' size='5'></td>
      <td><input type='text' name='kkl2' value='$kkl2' size='5'></td>
      <td><input type='text' name='vvl2' value='$vvl2' size='5'></td>
      </tr>";

  echo "  <tr><th>".t("Kausi 3")."</th>
      <td><input type='text' name='ppa3' value='$ppa3' size='5'></td>
      <td><input type='text' name='kka3' value='$kka3' size='5'></td>
      <td><input type='text' name='vva3' value='$vva3' size='5'></td>
      <td class='back'>&nbsp;-&nbsp;</td>
      <td><input type='text' name='ppl3' value='$ppl3' size='5'></td>
      <td><input type='text' name='kkl3' value='$kkl3' size='5'></td>
      <td><input type='text' name='vvl3' value='$vvl3' size='5'></td>
      </tr>";

  echo "  <tr><th>".t("Kausi 4")."</th>
      <td><input type='text' name='ppa4' value='$ppa4' size='5'></td>
      <td><input type='text' name='kka4' value='$kka4' size='5'></td>
      <td><input type='text' name='vva4' value='$vva4' size='5'></td>
      <td class='back'>&nbsp;-&nbsp;</td>
      <td><input type='text' name='ppl4' value='$ppl4' size='5'></td>
      <td><input type='text' name='kkl4' value='$kkl4' size='5'></td>
      <td><input type='text' name='vvl4' value='$vvl4' size='5'></td>
      </tr>";

  echo "</table><table><br>";

  $chk = "";
  if ($eliminoi != "") $chk = "checked";
  echo "<tr><th>".t("�l� huomioi konsernimyynti�")."</th><td colspan='3'><input type='checkbox' name='eliminoi' $chk></td></tr>";

  $chk = "";
  if ($erikoisvarastot != "") $chk = "checked";
  echo "<tr><th>".t("�l� huomioi erikoisvarastoja")."</th><td colspan='3'><input type='checkbox' name='erikoisvarastot' $chk></td></tr>";

  $chk = "";
  if ($poistetut != "") $chk = "checked";
  echo "<tr><th>".t("�l� n�yt� poistettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistetut' $chk></td></tr>";

  $chk = "";
  if ($poistuva != "") $chk = "checked";
  echo "<tr><th>".t("�l� n�yt� poistuvia tuotteita")."</th><td colspan='3'><input type='checkbox' name='poistuva' $chk></td></tr>";

  $chk = "";
  if ($eihinnastoon != "") $chk = "checked";
  echo "<tr><th>".t("�l� n�yt� tuotteita joita ei n�ytet� hinnastossa")."</th><td colspan='3'><input type='checkbox' name='eihinnastoon' $chk></td></tr>";

  if ($abcrajaus != "") {
    echo "<tr><td class='back'><br></td></tr>";
    echo "<tr><th colspan='4'>".t("ABC-rajaus")." $ryhmanimet[$abcrajaus]</th></tr>";

    $chk = "";
    if ($eiuusia != "") $chk = "checked";
    echo "<tr><th>".t("�l� listaa 12kk sis�ll� perustettuja tuotteita")."</th><td colspan='3'><input type='checkbox' name='eiuusia' $chk></td></tr>";

    $chk = "";
    if ($vainuudet != "") $chk = "checked";
    echo "<tr><th>".t("Listaa vain 12kk sis�ll� perustetut tuotteet")."</th><td colspan='3'><input type='checkbox' name='vainuudet' $chk></td></tr>";
  }

  echo "</table><table><br>";

  // yhti�valinnat
  $query  = "SELECT distinct yhtio, nimi
             from yhtio
             where konserni  = '$yhtiorow[konserni]'
             and konserni   != ''";
  $presult = pupe_query($query);

  $useampi_yhtio = 0;

  if (mysql_num_rows($presult) > 0) {

    echo "<tr><th>".t("Huomioi yhti�n saldot, myynnit ja ostot").":</th></tr>";
    $yhtiot = "";

    while ($prow = mysql_fetch_array($presult)) {

      $chk = "";
      if (is_array($valitutyhtiot) and in_array($prow["yhtio"], $valitutyhtiot) != '') {
        $chk = "CHECKED";
        $yhtiot .= "'$prow[yhtio]',";
        $useampi_yhtio++;
      }
      elseif ($prow["yhtio"] == $kukarow["yhtio"]) {
        $chk = "CHECKED";
      }

      echo "<tr><td><input type='checkbox' name='valitutyhtiot[]' value='$prow[yhtio]' $chk onClick='submit();'> $prow[nimi]</td></tr>";
    }

    $yhtiot = substr($yhtiot,0,-1);

    if ($yhtiot == "") $yhtiot = "'$kukarow[yhtio]'";

    echo "</table><table><br>";
  }

  // katsotaan onko firmalla varastoja useassa maassa
  $query = "SELECT distinct maa
            from varastopaikat
            where maa != ''
            and yhtio  in ($yhtiot)
            order by yhtio, maa";
  $vtresult = pupe_query($query);

  // useampi maa l�ytyy, annetaan mahdollisuus tutkailla saldoja per maa
  if (mysql_num_rows($vtresult) > 1) {

    echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot per varaston maa:")."</th></tr>";

    while ($vrow = mysql_fetch_array($vtresult)) {

      $chk = "";
      if (is_array($valitutmaat) and in_array($vrow["maa"], $valitutmaat) != '') {
        $chk = "CHECKED";
      }

      echo "<tr><td><input type='checkbox' name='valitutmaat[]' value='$vrow[maa]' $chk>$vrow[maa] - ".maa($vrow["maa"])."</td></tr>";
    }

    echo "</table><table><br>";
  }

  //Valitaan varastot joiden saldot huomioidaan
  $query = "SELECT *
            FROM varastopaikat
            WHERE yhtio in ($yhtiot)
            ORDER BY yhtio, tyyppi, nimitys";
  $vtresult = pupe_query($query);

  $vlask = 0;

  if (mysql_num_rows($vtresult) > 0) {

    echo "<tr><th>".t("Huomioi saldot, myynnit ja ostot varastoittain:")."</th></tr>";

    while ($vrow = mysql_fetch_array($vtresult)) {

      $chk = "";
      if (is_array($valitutvarastot) and in_array($vrow["tunnus"], $valitutvarastot) != '') {
        $chk = "CHECKED";
      }

      echo "<tr><td><input type='checkbox' name='valitutvarastot[]' value='$vrow[tunnus]' $chk>";

      if ($useampi_yhtio > 1) {
        $query = "SELECT nimi from yhtio where yhtio='$vrow[yhtio]'";
        $yhtres = pupe_query($query);
        $yhtrow = mysql_fetch_array($yhtres);
        echo "$yhtrow[nimi]: ";
      }

      echo "$vrow[nimitys] ";

      if ($vrow["tyyppi"] != "") {
        echo " *$vrow[tyyppi]* ";
      }
      if ($useampi_maa == 1) {
        echo "(".maa($vrow["maa"]).")";
      }

      echo "</td></tr>";
    }
  }
  else {
    echo "<font class='error'>".t("Yht��n varastoa ei l�ydy, raporttia ei voida ajaa")."!</font>";
    exit;
  }

  echo "</table>";
  echo "<br><input type='submit' name='ehdotusnappi' value = '".t("Aja ostoehdotus")."'></form>";
}

require ("../inc/footer.inc");
