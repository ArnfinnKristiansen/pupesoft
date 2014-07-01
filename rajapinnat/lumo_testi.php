<?php
// Kutsutaanko CLI:st�
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
  $php_cli = TRUE;
}

date_default_timezone_set('Europe/Helsinki');

// Kutsutaanko CLI:st�
if (!$php_cli) {
  die ("T�t� scripti� voi ajaa vain komentorivilt�!");
}

$pupe_root_polku = dirname(dirname(__FILE__));

require "{$pupe_root_polku}/inc/connect.inc";
require "{$pupe_root_polku}/inc/functions.inc";

//error_reporting(E_ALL);

require "{$pupe_root_polku}/rajapinnat/lumo_client.php";

if(isset($lumo_service_address) and isset($lumo_service_port)) {
  $clientti = new LumoClient($lumo_service_address, $lumo_service_port);

  if ($clientti->getErrorCount() > 0) {
    exit;
  }
  
  //$onnistuuko = $clientti->startTransaction(10001);
  $onnistuuko = $onnistuuko === TRUE ? "OK" : "HYLKY";

  echo "skripti loppu, palautti \n $onnistuuko \n";
  if ($onnistuuko) {
    
    echo "kysyt��n kuittia \n";
    echo $clientti->getCustomerReceipt(); 
    
    //echo "kysyt��n 2kuittia \n";
    //echo $clientti->getMerchantReceipt();
  }
}
