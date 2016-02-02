<?php

// Kutsutaanko CLI:st�
if (php_sapi_name() != 'cli') {

  $_cli = false;

  if (strpos($_SERVER['SCRIPT_NAME'], "saapuminen_ulkoiseen_jarjestelmaan.php") !== false) {
    require "inc/parametrit.inc";
  }
}
else {

  $_cli = true;

  date_default_timezone_set('Europe/Helsinki');

  if (trim($argv[1]) == '') {
    die ("Et antanut l�hett�v�� yhti�t�!\n");
  }

  if (trim($argv[2]) == '') {
    die ("Et antanut saapumisnumeroa!\n");
  }

  // lis�t��n includepathiin pupe-root
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

  // otetaan tietokanta connect ja funktiot
  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  // Sallitaan vain yksi instanssi t�st� skriptist� kerrallaan
  pupesoft_flock();

  $yhtio = mysql_escape_string(trim($argv[1]));
  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan kukarow
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '{$yhtio}'
            AND kuka    = 'admin'";
  $kukares = pupe_query($query);

  if (mysql_num_rows($kukares) != 1) {
    exit("VIRHE: Admin k�ytt�j� ei l�ydy!\n");
  }

  $kukarow = mysql_fetch_assoc($kukares);

  $saapumisnro = $argv[2];

  if (!empty($argv[3])) {
    $ordercode = $argv[3];
  }
}

$ftp_chk = (!empty($ftp_logmaster_host) and !empty($ftp_logmaster_user));
$ftp_chk = ($ftp_chk and !empty($ftp_logmaster_pass) and !empty($ftp_logmaster_path));

if (!$ftp_chk) {
  die ("FTP-tiedot ovat puutteelliset!\n");
}

// Tarvitaan:
// $saapumisnro
// ordercode (vapaaehtoinen) (u = new, m = change, p = delete)

$saapumisnro = (int) $saapumisnro;
$ordercode = !isset($ordercode) ? 'U' : $ordercode;

$xmlstr  = '<?xml version="1.0" encoding="Windows-1257"?>';
$xmlstr .= '<Message>';
$xmlstr .= '</Message>';

$xml = new SimpleXMLElement($xmlstr);

$query = "SELECT *
          FROM lasku
          WHERE yhtio = '{$kukarow['yhtio']}'
          AND tila    = 'K'
          AND alatila = ''
          AND tunnus  = '{$saapumisnro}'";
$res = pupe_query($query);
$row = mysql_fetch_assoc($res);

$header = $xml->addChild('MessageHeader');

$header->addChild('MessageType', 'inboundDelivery');
$header->addChild('Sender', $yhtiorow['nimi']);
$header->addChild('Receiver', 'LogMaster');

$query = "SELECT DISTINCT otunnus
          FROM tilausrivi
          WHERE yhtio     = '{$kukarow['yhtio']}'
          AND tyyppi      = 'O'
          AND uusiotunnus = '{$saapumisnro}'";
$otunnukset_res = pupe_query($query);

while ($otunnukset_row = mysql_fetch_assoc($otunnukset_res)) {

  $query = "SELECT *
            FROM lasku
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tila    = 'O'
            AND tunnus  = '{$otunnukset_row['otunnus']}'";
  $ostotilaus_res = pupe_query($query);
  $ostotilaus_row = mysql_fetch_assoc($ostotilaus_res);

  $body = $xml->addChild('VendReceiptsList');
  $body->addChild('PurchId', $ostotilaus_row['tunnus']);
  $body->addChild('ReceiptsListId', $row['laskunro']);

  // U = new
  // M = change
  // P = delete
  $body->addChild('OrderCode', $ordercode);
  $body->addChild('OrderType', 'PO');
  $body->addChild('ReceiptsListDate', tv1dateconv($row['luontiaika']));
  $body->addChild('DeliveryDate', tv1dateconv($ostotilaus_row['toimaika']));
  $body->addChild('Warehouse', $ostotilaus_row['varasto']);

  $query = "SELECT *
            FROM toimi
            WHERE yhtio = '{$kukarow['yhtio']}'
            AND tunnus  = '{$ostotilaus_row['liitostunnus']}'";
  $toimires = pupe_query($query);
  $toimirow = mysql_fetch_assoc($toimires);

  $vendor = $body->addChild('Vendor');
  $vendor->addChild('VendAccount', $toimirow['toimittajanro']);
  $vendor->addChild('VendName', $ostotilaus_row['nimi']);
  $vendor->addChild('VendStreet', $ostotilaus_row['osoite']);
  $vendor->addChild('VendPostCode', $ostotilaus_row['postino']);
  $vendor->addChild('VendCity', $ostotilaus_row['postitp']);
  $vendor->addChild('VendCountry', $ostotilaus_row['maa']);
  $vendor->addChild('VendInfo', '');

  $purchaser = $body->addChild('Purchaser');
  $purchaser->addChild('PurcAccount', $yhtiorow['ytunnus']);
  $purchaser->addChild('PurcName', $yhtiorow['nimi']);
  $purchaser->addChild('PurcStreet', $yhtiorow['osoite']);
  $purchaser->addChild('PurcPostCode', $yhtiorow['postino']);
  $purchaser->addChild('PurcCity', $yhtiorow['postitp']);
  $purchaser->addChild('PurcCountry', $yhtiorow['maa']);

  $query = "SELECT *
            FROM tilausrivi
            WHERE yhtio     = '{$kukarow['yhtio']}'
            AND tyyppi      = 'O'
            AND otunnus     = '{$otunnukset_row['otunnus']}'
            AND uusiotunnus = '{$saapumisnro}'";
  $rivit_res = pupe_query($query);

  $i = 1;

  while ($rivit_row = mysql_fetch_assoc($rivit_res)) {

    $lines = $body->addChild('Lines');
    $lines->addChild('Line', $i);
    $lines->addChild('TransId', $rivit_row['tunnus']);
    $lines->addChild('ItemNumber', $rivit_row['tuoteno']);
    $lines->addChild('OrderedQuantity', $rivit_row['varattu']);
    $lines->addChild('Unit', $rivit_row['yksikko']);
    $lines->addChild('Price', $rivit_row['hinta']);
    $lines->addChild('CurrencyCode', $ostotilaus_row['valkoodi']);
    $lines->addChild('RowInfo', $rivit_row['kommentti']);

    $i++;
  }
}

$xml_chk = (isset($xml->VendReceiptsList) and isset($xml->VendReceiptsList->Lines));

if ($xml_chk and $ftp_chk) {
  $filename = $pupe_root_polku."/dataout/logmaster_inbound_delivery_".md5(uniqid()).".xml";

  if (file_put_contents($filename, $xml->asXML())) {

    if ($_cli) {
      echo "\n", t("Tiedoston luonti onnistui"), "\n";
    }
    else {
      echo "<br /><font class='message'>", t("Tiedoston luonti onnistui"), "</font><br />";
    }

    $ftphost = $ftp_logmaster_host;
    $ftpuser = $ftp_logmaster_user;
    $ftppass = $ftp_logmaster_pass;
    $ftppath = $ftp_logmaster_path;
    $ftpfile = realpath($filename);

    require "inc/ftp-send.inc";

    $query = "UPDATE lasku SET
              sisviesti3  = 'ei_vie_varastoon'
              WHERE yhtio = '{$yhtio}'
              AND tila    = 'K'
              AND tunnus  = '{$saapumisnro}'";
    $updres = pupe_query($query);
  }
  else {

    if ($_cli) {
      echo "\n", t("Tiedoston luonti ep�onnistui"), "\n";
    }
    else {
      echo "<br /><font class='error'>", t("Tiedoston luonti ep�onnistui"), "</font><br />";
    }
  }
}
