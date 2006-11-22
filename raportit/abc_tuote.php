<?php

// t�m� skripti k�ytt�� slave-tietokantapalvelinta
$useslave = 1;

require ("../inc/parametrit.inc");

if ($toim == "") {
	$toim = "myynti";
}

if ($tee == '') {
	echo "<font class='head'>".t("ABC-Analysointia tuotteille")."<hr></font>";
	echo "<br><br><b>".t("Valitse toiminto").":</b><br><br>";
	echo "<a href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >1. ".t("ABC-luokkayhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRYYHTEENVETO&toim=$toim'>2. ".t("Osasto/Ryhm� yhteenveto")."</a><br>";
	echo "<a href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >3. ".t("Osasto/Ryhm�")."</a><br>";
	echo "<a href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >4. ".t("Kaikki luokat tekstin�")."</a><br>";
}

// jos kaikki tarvittavat tiedot l�ytyy menn��n queryyn
if ($tee == 'YHTEENVETO') {
	require ("abc_tuote_yhteenveto.php");
}

if ($tee == 'LUOKKA') {
	require ("abc_tuote_luokka.php");
}

if ($tee == 'OSASTOTRY') {
	require ("abc_tuote_osastotry.php");
}

if ($tee == 'OSASTOTRYYHTEENVETO') {
	require ("abc_tuote_osastotry_yhteenveto.php");
}

if ($tee == 'TUOTE') {
	require ("abc_tuote_tuotehistoria.php");
}

if ($tee == 'PITKALISTA') {
	require ("abc_kaikki_taullask.php");
}

require ("../inc/footer.inc");

?>