<?php

// t�m� skripti k�ytt�� slave-tietokantapalvelinta
$useslave = 1;

require ("../inc/parametrit.inc");

if ($toim == "") {
	$toim = "myynti";
}

if ($tee == '') {
	echo "<font class='head'>".t("ABC-Analysointia tuotteille")."<hr></font>";
	echo "<font class='message'>";

	if ($toim == "myynti") {
		echo "ABC-luokat myynnin mukaan.";
	}
	else {
		echo "ABC-luokat katteen mukaan.";
	}

	echo "<br><br>".t("Valitse toiminto").":<br><br>";
	echo "</font>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=YHTEENVETO&toim=$toim'         >".t("ABC-luokkayhteenveto")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=OSASTOTRYYHTEENVETO&toim=$toim'>".t("Tuoteosasto/tuoteryhm�yhteenveto")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=OSASTOTRY&toim=$toim'          >".t("Tuoteosaston ja/tai tuoteryhm�n luokat")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=LUOKKA&toim=$toim'             >".t("Luokan tuotteet")."</a><br>";
	echo "<li><a class='menu' href='$PHP_SELF?tee=PITKALISTA&toim=$toim'         >".t("Kaikki luokat tekstin�")."</a><br>";
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

if ($tee == 'PITKALISTA') {
	require ("abc_kaikki_taullask.php");
}

require ("../inc/footer.inc");

?>