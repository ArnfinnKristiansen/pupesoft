<?php
require "../inc/parametrit.inc";

$oikeus = true; // requireissa tarkistetaan t�m�n avulla, onko k�ytt�j�ll� oikeutta tehd� ko. toimintoa

if ($tila == 'tee_kohdistus') {
  require('manuaalinen_suoritusten_kohdistus_tee_kohdistus.php');
}
if ($tila == 'suorituksenvalinta') {
  require('manuaalinen_suoritusten_kohdistus_suorituksen_valinta.php');
}

if ($tila == 'kohdistaminen') {
  require('manuaalinen_suoritusten_kohdistus_suorituksen_kohdistus.php');
}

if ($tila == '') { // asiakkaan valintasivu
  require('manuaalinen_suoritusten_kohdistus_asiakkaan_valinta.php');
}

?>
