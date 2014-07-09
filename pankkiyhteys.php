<?php

const SEPA_OSOITE = "https://sepa.devlab.fi/api/";
const ACCESS_TOKEN = "Bexvxb10H1XBT36x42Lv8jEEKnA6";

require("inc/parametrit.inc");

echo "<font class='head'>" . t('SEPA-pankkiyhteys') . "</font>";
echo "<hr>";

if (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"] != 'on') {
  echo "<font class='error'>";
  echo t("Voit k�ytt�� pankkiyhteytt� vain salatulla yhteydell�!");
  echo "</font>";
  exit;
}

$tee = isset($tee) ? $tee : '';
$target_id = isset($target_id) ? $target_id : '';

if ($tee == "uusi_pankkiyhteys") {
  uusi_pankkiyhteys_formi();
}

if ($tee == "tarkista_lataa_sertifikaatti_formi") {
  if (!avaimet_ja_salasana_kunnossa()) {
    $tee = "lataa_sertifikaatti_formi";
  }

  $tee = "lataa_sertifikaatti";
}

if ($tee == "lataa_sertifikaatti") {
  $private_key = file_get_contents($_FILES["private_key"]["tmp_name"]);
  $sertifikaatti = file_get_contents($_FILES["certificate"]["tmp_name"]);

  $salatut_tunnukset = array(
    "private_key"   => salaa($private_key, $salasana),
    "sertifikaatti" => salaa($sertifikaatti, $salasana)
  );

  $target_id = hae_target_id($sertifikaatti, $private_key, $customer_id);

  if (tallenna_tunnukset($salatut_tunnukset, $customer_id, $target_id, $tili)) {
    echo "Tunnukset lis�tty";
  }
  else {
    virhe("Tunnukset eiv�t tallentuneet tietokantaan");
  }
}

if ($tee == "lataa_sertifikaatti_formi") {
  sertifikaatin_lataus_formi();
}

if ($tee == "hae_tiliote") {
  if (isset($salasana) and salasana_kunnossa()) {
    lataa_kaikki("TITO");
  }
  else {
    salasana_formi();
  }
}

if ($tee == "hae_viiteaineisto") {
  if (isset($salasana) and salasana_kunnossa()) {
    lataa_kaikki("KTL");
  }
  else {
    salasana_formi();
  }
}

if ($tee == "laheta_maksuaineisto") {
  if ($salasana and salasana_kunnossa() and maksuaineisto_kunnossa()) {
    $maksuaineisto = file_get_contents($_FILES["maksuaineisto"]["tmp_name"]);
    $tunnukset = hae_tunnukset_ja_pura_salaus($tili, $salasana);

    $vastaus = laheta_maksuaineisto($tunnukset, $maksuaineisto);

    if ($vastaus) {
      echo "<table>";
      echo "<tbody>";

      foreach ($vastaus[1] as $key => $value) {
        echo "<tr>";
        echo "<td>{$key}</td>";
        echo "<td>{$value}</td>";
        echo "</tr>";
      }

      echo "</tbody";
      echo "</table>";
    }
  }
  else {
    salasana_formi();
  }
}

if ($tee == "valitse_komento") {
  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tili' value='{$tili}'/>";
  echo "<table>";
  echo "<tbody>";
  echo "<tr>";
  echo "<td>Mit� haluat tehd�?</td>";
  echo "<td>";
  echo "<select name='tee'>";
  echo "<option value='lataa_sertifikaatti_formi'>" . t('Lataa sertifikaatti j�rjestelm��n') . "</option>";
  echo "<option value='hae_tiliote'>" . t("Hae tiliote") . "</option>";
  echo "<option value='hae_viiteaineisto'>" . t("Hae viiteaineisto") . "</option>";
  echo "<option value='laheta_maksuaineisto'>" . t("L�het� maksuaineisto") . "</option>";
  echo "</select>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "<input type='submit' value='" . t('OK') . "'>";
  echo "</form>";
}

if ($tee == "") {
  pankkiyhteyden_valinta_formi();
}

/**
 * @param $tili
 * @param $yhtio
 *
 * @return array
 */
function hae_avain_sertifikaatti_ja_customer_id($tili) {
  global $yhtiorow, $kukarow;

  $query = "SELECT private_key, certificate, sepa_customer_id
            FROM yriti
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND tunnus = {$tili}";
  $result = pupe_query($query);

  $rivi = mysql_fetch_assoc($result);

  return $rivi;
}

/**
 * @param $salattu_data
 * @param $salasana
 *
 * @return string
 */
function pura_salaus($salattu_data, $salasana) {
  $avain = hash("SHA256", $salasana, true);

  $salattu_data_binaari = base64_decode($salattu_data);

  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
  $iv = substr($salattu_data_binaari, 0, $iv_size);

  $salattu_data_binaari = substr($salattu_data_binaari, $iv_size);

  return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $avain, $salattu_data_binaari, MCRYPT_MODE_CBC, $iv);
}

/**
 * @return bool
 */
function avaimet_ja_salasana_kunnossa() {
  $virheet_maara = 0;

  if (!$_FILES["certificate"]["tmp_name"]) {
    $virheet_maara++;
    virhe("Sertifikaatti t�ytyy antaa");
  }

  if (!$_FILES["private_key"]["tmp_name"]) {
    $virheet_maara++;
    virhe("Avain t�ytyy antaa");
  }

  if (!$_POST["customer_id"]) {
    $virheet_maara++;
    virhe("Asiakastunnus t�ytyy antaa");
  }

  if (empty($_POST["salasana"])) {
    $virheet_maara++;
    virhe("Salasana t�ytyy antaa");
  }

  if ($_POST["salasana"] != $_POST["salasanan_vahvistus"]) {
    $virheet_maara++;
    virhe("Salasanan vahvistus ei vastannut salasanaa");
  }

  if ($virheet_maara == 0) {
    return true;
  }

  return false;
}

/**
 * @param $tiedostotyyppi
 * @param $tunnukset
 *
 * @return array
 */
function hae_viitteet($tiedostotyyppi, $tunnukset) {
  global $yhtiorow, $kukarow;

  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => $tiedostotyyppi,
      "target_id"   => "11111111A1"
    ),
    "url"     => "" . SEPA_OSOITE . "nordea/download_file_list",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  $tiedostot = $vastaus[1]["files"];
  $viitteet = array();

  foreach ($tiedostot as $tiedosto) {
    array_push($viitteet, $tiedosto["fileReference"]);
  }

  return $viitteet;
}

/**
 * @param $viitteet
 * @param $tiedostotyyppi
 * @param $tunnukset
 *
 * @return bool
 */
function lataa_tiedostot($viitteet, $tiedostotyyppi, $tunnukset) {
  global $yhtiorow, $kukarow;

  $onnistuneet = 0;

  foreach ($viitteet as $viite) {
    $parameters = array(
      "method"  => "POST",
      "data"    => array(
        "cert"           => base64_encode($tunnukset["sertifikaatti"]),
        "private_key"    => base64_encode($tunnukset["avain"]),
        "customer_id"    => $tunnukset["customer_id"],
        "file_type"      => $tiedostotyyppi,
        "target_id"      => "11111111A1",
        "file_reference" => $viite
      ),
      "url"     => "" . SEPA_OSOITE . "nordea/download_file",
      "headers" => array(
        "Content-Type: application/json",
        "Authorization: Token token=" . ACCESS_TOKEN
      )
    );

    $vastaus = pupesoft_rest($parameters);

    if ($vastaus[0] == 200) {
      $onnistuneet++;
    }

    if (!is_dir("/tmp/{$tiedostotyyppi}")) {
      mkdir("/tmp/{$tiedostotyyppi}");
    }

    file_put_contents("/tmp/{$tiedostotyyppi}/{$viite}", base64_decode($vastaus[1]["data"]));
  }

  if (count($viitteet) == $onnistuneet) {
    return true;
  }

  return false;
}

function salasana_formi() {
  global $yhtiorow, $kukarow;

  $komento = $_POST["tee"];

  if ($komento == "laheta_maksuaineisto") {
    $enctype = "enctype='multipart/form-data'";
  }
  else {
    $enctype = "";
  }

  echo "<form method='post' action='pankkiyhteys.php' {$enctype}>";
  echo "<input type='hidden' name='tee' value='{$komento}'/>";
  echo "<input type='hidden' name='tili' value='{$_POST["tili"]}'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='salasana'>" . t("Salasana, jolla salasit tunnukset") . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='password' name='salasana' id='salasana'/>";
  echo "</td>";
  echo "</tr>";

  if ($komento == "laheta_maksuaineisto") {
    echo "<tr>";
    echo "<td>";
    echo "<label for='maksuainesto'>" . t("Maksuaineisto") . "</label>";
    echo "</td>";
    echo "<td>";
    echo "<input type='file' name='maksuaineisto' id='maksuaineisto'/>";
    echo "</td>";
    echo "</tr>";
  }
  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' value='" . t("Hae") . "'/>";
  echo "</td>";
  echo "</tr>";
  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}

/**
 * @param $tili
 * @param $salasana
 *
 * @return array
 */
function hae_tunnukset_ja_pura_salaus($tili, $salasana) {
  global $yhtiorow, $kukarow;

  $haetut_tunnukset = hae_avain_sertifikaatti_ja_customer_id($tili);

  $avain = pura_salaus($haetut_tunnukset["private_key"], $salasana);
  $sertifikaatti = pura_salaus($haetut_tunnukset["certificate"], $salasana);
  $customer_id = $haetut_tunnukset["sepa_customer_id"];

  return array(
    "avain"         => $avain,
    "sertifikaatti" => $sertifikaatti,
    "customer_id"   => $customer_id
  );
}

/**
 * @param $tunnukset
 * @param $maksuaineisto
 *
 * @return array
 */
function laheta_maksuaineisto($tunnukset, $maksuaineisto) {
  $parameters = array(
    "method"  => "POST",
    "data"    => array(
      "cert"        => base64_encode($tunnukset["sertifikaatti"]),
      "private_key" => base64_encode($tunnukset["avain"]),
      "customer_id" => $tunnukset["customer_id"],
      "file_type"   => "NDCORPAYS",
      "target_id"   => "11111111A1",
      "content"     => $maksuaineisto
    ),
    "url"     => "" . SEPA_OSOITE . "nordea/upload_file",
    "headers" => array(
      "Content-Type: application/json",
      "Authorization: Token token=" . ACCESS_TOKEN
    )
  );

  $vastaus = pupesoft_rest($parameters);

  if (!vastaus_kunnossa($vastaus)) {
    return false;
  }

  return $vastaus;
}

function salasana_kunnossa() {
  global $yhtiorow, $kukarow;

  if (isset($_POST["salasana"]) and empty($_POST["salasana"])) {
    virhe("Salasana t�ytyy antaa");

    return false;
  }

  return true;
}

function maksuaineisto_kunnossa() {
  global $yhtiorow, $kukarow;

  if (isset($_FILES["maksuaineisto"]) and !$_FILES["maksuaineisto"]["tmp_name"]) {
    virhe("Maksuaineisto puuttuu");

    return false;
  }

  return true;
}

/**
 * @param $vastaus
 *
 * @return bool
 */
function vastaus_kunnossa($vastaus) {
  global $yhtiorow, $kukarow;

  switch ($vastaus[0]) {
    case 200:
      return true;
    case 500:
      virhe("Pankki ei vastaa kyselyyn, yrit� my�hemmin uudestaan");

      return false;
    case 503:
      virhe("Pankki ei vastaa kyselyyn toivotulla tavalla, yrit� my�hemmin uudestaan");

      return false;
    case 0:
      virhe("Sepa-palvelimeen ei jostain syyst� saada yhteytt�, yrit� my�hemmin uudestaan");

      return false;
  }
}

function sertifikaatin_lataus_formi() {
  global $yhtiorow, $kukarow;

  echo "<form action='pankkiyhteys.php' method='post' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='tee' value='tarkista_lataa_sertifikaatti_formi'/>";
  echo "<input type='hidden' name='tili' value='{$_POST["tili"]}'/>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='private_key'>" . t('Yksityinen avain') . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='file' name='private_key' id='private_key'>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='certificate'>" . t('Sertifikaatti') . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='file' name='certificate' id='certificate'/>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><label for='customer_id'>Sertifikaattiin yhdistetty asiakastunnus</label></td>";
  echo "<td><input type='text' name='customer_id' id='customer_id'/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='salasana'>" . t("Salasana, jolla tiedot suojataan") . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='password' name='salasana' id='salasana'/>";
  echo "</td>";
  echo "<td class='back'>Huom. salasanaa ei voi mitenk��n palauttaa, jos se unohtuu</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td>";
  echo "<label for='salasanan_vahvistus'>" . t("Salasanan vahvistus") . "</label>";
  echo "</td>";
  echo "<td>";
  echo "<input type='password' name='salasanan_vahvistus' id='salasanan_vahvistus'/>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' name='submit' value='" . t('Tallenna tunnukset') . "'/>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table";
  echo "</form>";
}

function virhe($viesti) {
  echo "<font class='error'>{$viesti}</font><br/>";
}

function ok($viesti) {
  echo "<font class='ok'>{$viesti}</font><br/>";
}

/**
 * @param $tiedostotyyppi
 */
function lataa_kaikki($tiedostotyyppi) {
  global $yhtiorow, $kukarow;

  $tunnukset = hae_tunnukset_ja_pura_salaus($_POST["tili"], $_POST["salasana"]);

  $viitteet = hae_viitteet($tiedostotyyppi, $tunnukset);

  if ($viitteet and lataa_tiedostot($viitteet, $tiedostotyyppi, $tunnukset)) {
    echo "Tiedostot ladattu";
  }
}

/**
 * @return array
 */
function hae_kaytossa_olevat_tilit() {
  global $yhtiorow, $kukarow;

  $query = "SELECT tunnus, nimi
            FROM yriti
            WHERE yhtio = '{$kukarow["yhtio"]}'
            AND sepa_customer_id != ''";
  $result = pupe_query($query);

  $kaytossa_olevat_tilit = array();

  while ($rivi = mysql_fetch_assoc($result)) {
    array_push($kaytossa_olevat_tilit, $rivi);
  }

  return $kaytossa_olevat_tilit;
}

function pankkiyhteyden_valinta_formi() {
  global $yhtiorow, $kukarow;

  $kaytossa_olevat_tilit = hae_kaytossa_olevat_tilit();

  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='valitse_komento'/>";
  echo "<table>";
  echo "<tbody>";

  if ($kaytossa_olevat_tilit) {
    echo "<tr>";
    echo "<td>K�yt�ss� olevat pankkiyhteydet</td>";
    echo "<td>";
    echo "<select name='tili'>";

    foreach ($kaytossa_olevat_tilit as $tili) {
      echo "<option value='{$tili["tunnus"]}'>{$tili["nimi"]}</option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";
    echo "</table>";
    echo "<input type='submit' value='" . t('Valitse tili') . "'>";
    echo "<br/><br/>";
  }

  echo "</form>";

  echo "<form method='post' action='pankkiyhteys.php'>";
  echo "<input type='hidden' name='tee' value='uusi_pankkiyhteys'/>";
  echo "<input type='submit' name='uusi_pankkiyhteys' value='" . t("Uusi pankkiyhteys") . "'/>";
  echo "</form>";
}
