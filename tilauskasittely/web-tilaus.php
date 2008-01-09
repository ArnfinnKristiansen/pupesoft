#!/usr/bin/php
<?php
//T�� on rikki!
/*
if ($argc == 0) die (t("T�t� scripti� voi ajaa vain komentorivilt�")."!");

// luetaan tilaustiedostoja sis��n.. formaatti on perus arvoparikamaa.
// eli kuus ekaa merkki� kertoo mit� kamaa tulossa, sitten tulee sis�lt�
// n�ytt�� t�lt�:
//
// YHTIO:arwi								# yhtio
// KENEN:joni								# kenelle myyj�lle tilaus tehd��n, laatija on aina web-tilaus
// ASNUM:01066871							# asiakkaan ytunnus
// KOMM1:kommentti							# kommentti otsikolle
// TUOTE:tuotenumero\tkpl\trivikommentti\tH	# tuoteno tabi kpl tabi rivikommentti tabi VAR-kentt�
// TILAT:VALMIS								# jos t�ss� lukee valmis niin requirataan tilaus valmis..

// otetaan eka parametri komentorivilt� tiedostonimeksi
$file = trim($argv[1]);

if (!file_exists($file)) {
	die(t("Tiedostoa")." $file ".t("ei l�ydy").".");
}

require ("../inc/connect.inc");
require ("../inc/functions.inc");

if (!$handle = fopen($file, "r")) die(t("Tiedoston avaus ep�onnistui")."!");

// nollataan parit muuttujat
$laskuri1 = 0;
$laskuri2 = 0;
$laskuri3 = 0;

// luetaan eka rivi tiedostosta
$rivi = fgets($handle, 4096);

while (!feof($handle)) {

	// poista yks�is- ja kaksoishipsut sek� backslashit...
	$poista	  = array("'", "\\", "\"");
	$rivi	  = str_replace($poista,"",$rivi);

	// otetaan rivilt� tunniste ja loput
	$tunniste = substr($rivi, 0, 5);
	$loput    = trim(substr($rivi, 6));

	switch ($tunniste) {

		case "YHTIO":

			$tunnus   = "";

			$query = "select * from yhtio where yhtio='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("Yhti�t�")." $loput ".t("ei l�ydy")."");

			$yhtiorow = mysql_fetch_array($result);
			$kukarow['yhtio'] = $yhtiorow['yhtio'];

			echo t("Tilaus yritykselle")." $yhtiorow[nimi]\n";
			break;

		case "KENEN":

			$query = "select * from kuka where yhtio='$kukarow[yhtio]' and kuka='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("K�ytt�j��")." $loput ".t("ei l�ydy")."");

			$kukarow = mysql_fetch_array($result);
			echo t("Myyj�")." $kukarow[nimi]\n";
			break;

		case "ASNUM":

			$loput = (int) $loput; // asiakasnumero numeroks
			$query = "select * from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$loput'";
			$result = mysql_query($query) or die($query);

			if (mysql_num_rows($result) != 1) die(t("Asiakasta")." $loput ".t("ei l�ydy")."");

			$asiakasrow = mysql_fetch_array($result);
			echo t("Asiakas")." $asiakasrow[nimi]\n";
			break;
			
		case "TILAT":

			if (strtoupper($loput) == "VALMIS") { // jos t�ss� lukee valmis, niin laitetaankin tilaus valmiiksi
				require ("tilaus-valmis.inc");
			}
			break;

		case "KOMM1":

			$laskuri3++; // otsikoiden m��r�
			$kommentti = trim($loput);

			// viimeinen otsikko kentt�, tehd��n nyt otsikko...
			$query  = "insert into lasku set
						alatila			= '',
						alv 			= '$asiakasrow[alv]',
						chn				= '$asiakasrow[chn]',
						comments 		= '$asiakasrow[comments]',
						kerayspvm 		=  now(),
						ketjutus		= '$asiakasrow[ketjutus]',
						laatija			= '".t("webtilaus")."',
						laskutusvkopv	= '$asiakasrow[laskutusvkopv]',
						luontiaika		=  now(),
						maa 			= '$asiakasrow[maa]',
						maksuehto 		= '$asiakasrow[maksuehto]',
						myyja 			= '$kukarow[kuka]',
						nimi			= '$asiakasrow[nimi]',
						nimitark 		= '$asiakasrow[nimitark]',
						osoite 			= '$asiakasrow[osoite]',
						ovttunnus 		= '$asiakasrow[ovttunnus]',
						postino 		= '$asiakasrow[postino]',
						postitp 		= '$asiakasrow[postitp]',
						tila			= 'N',
						erikoisale		= '$asiakasrow[erikoisale]',
						tilausvahvistus = '$asiakasrow[tilausvahvistus]',
						toim_maa 		= '$asiakasrow[maa]',
						toim_nimi 		= '$asiakasrow[nimi]',
						toim_nimitark 	= '$asiakasrow[nimitark]',
						toim_osoite 	= '$asiakasrow[osoite]',
						toim_ovttunnus	= '$asiakasrow[toim_ovttunnus]',
						toim_postino 	= '$asiakasrow[postino]',
						toim_postitp 	= '$asiakasrow[postitp]',
						toimaika 		=  now(),
						toimitusehto 	= '$asiakasrow[toimitusehto]',
						toimitustapa 	= '$asiakasrow[toimitustapa]',
						verkkotunnus	= '$asiakasrow[verkkotunnus]',
						liitostunnus    = '$asiakasrow[tunnus]',
						vienti 			= '$asiakasrow[vienti]',
						viesti 			= '$kommentti',
						yhtio			= '$kukarow[yhtio]',
						ytunnus			= '$asiakasrow[ytunnus]'";
			$result = mysql_query($query) or die($query);
			$tunnus = (string) mysql_insert_id();

			// haetaan laskurow viel� arrayseen
			$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
			$result   = mysql_query($query) or die($query);
			$laskurow = mysql_fetch_array($result);

			// t�t� tarvitaan jossain
			$kukarow['kesken'] = $laskurow['tunnus'];

			echo t("Otsikko perustettu")." $tunnus\n";
			break;

		case "TUOTE":

			$rivi	 = explode("\t", $loput);			// splitataan tabista
			$tuoteno = str_replace(" ", "",$rivi[0]);	// otetaan v�lily�nnit pois tuotenumerosta
			$atil    = (int) $rivi[1];					// muutetaan numeroksi
			$teksti  = trim($rivi[2]);					// kommentti
			$var     = trim($rivi[3]);					// var
			$laskuri1++;

			if($tuoteno != '' and $atil > 0) {

				$query = "	SELECT *
							FROM tuote
							WHERE tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or die($query);

				if(mysql_num_rows($result) == 1) {

					$tuoterow  = mysql_fetch_array($result);
					$toimaika  = date("Y-m-d");
					$kerayspvm = date("Y-m-d");
					
					//tarkistetaan rivi
					$eikayttajaa = "ON"; // k�ytet��n kaikkia oletuksia
					require ("tarkistarivi.inc");

					// jos riviss� oli joku virhe, lis�t��n se puutteena tilaukselle
					if (strlen($varaosavirhe) > 0) {
						
						if ($var != 'H') {
							echo "$tuoteno: ".strip_tags($varaosavirhe)."\n".t("Merkattiin")." $tuoteno ".t("puutteeksi").".\n";
						}
						
						$varaosavirhe = '';

						if ($tee == 'Y') {	//t�ss� meill� ei ole k�ytt�liittym�� ja rivi halutaan aina kuiteski lis�t� tilaukselle joten alvihomma tulee ratkasta t�ss�
							//tuotteen tiedot alv muodossa
							$trow = $tuoterow;

							list($hinta, $alv) = alv($laskurow, $trow, $hinta, '', '');
						}

						$tee='UV';
						if ($var == 'H') { // jos v�kisinhyv�ksyt��n
							$hyvaksy=$var;
							$avarattu=$atil;
						}
						else {
							$hyvaksy='P';
							$avarattu=0;
						}
					}

 					if ($var == 'J') { // jos nyt ihan v�ltt�m�tt� halutaan jt niin pistet��n sitte ..
						$hyvaksy=$var;
						$avarattu=0;
						$ajt=$atil;
					}
					
					// lis�t��n rivi...
					require ("lisaarivi.inc");
					$laskuri2++;
				}
				else {
					echo t("Tuotenumeroa")." $tuoteno ".t("ei l�ydy")."!\n";
				}
			}
			break;

		} // end switch..

	// luetaan seuraava rivi
	$rivi = fgets($handle, 4096);

} // end while

fclose($handle);

echo "$laskuri3 ".t("tilausta")." $laskuri2/$laskuri1 ".t("rivi� lis�tty")."\n";
*/
?>
