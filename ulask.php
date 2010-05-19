<?php

if (strpos($_SERVER['SCRIPT_NAME'], "ulask.php")  !== FALSE) {
	require "inc/parametrit.inc";
}

enable_ajax();

if ($livesearch_tee == "TILIHAKU") {
	livesearch_tilihaku();
	exit;
}

require "inc/alvpopup.inc";

echo "<font class='head'>".t("Uuden laskun perustus")."</font><hr>";

if ($tee == 'VIIVA') {

	// t�ll�st� on laskun viivakoodi:
	// 2
	// 15753000000064
	// 00004600
	// 00000007702554380108
	// 041215
	// 00008

	if (substr($nimi,0,1) != '2' or strlen($nimi) != 54) {
		echo "<font class='error'>".t("Emme osaa kuin viivakoodi-versio kakkosta! Sy�tt�m�si tieto ei ole sit�!")."</font><br><br>";
		$tee = "";
	}
	else {
		$tee2	= "";
		$tilino = substr($nimi,1,14);
		$summa  = substr($nimi,15,8) / 100;
		$viite  = ltrim(substr($nimi,23,20),"0"); // etunollat pois
		$erv    = substr($nimi,43,2);
		$erk    = substr($nimi,45,2);
		$erp    = substr($nimi,47,2);

		//Toistaiseksi osataan vaan tarkistaa suomalaisten pankkitilien oikeellisuutta
		if (strtoupper($yhtiorow['maa']) == 'FI') {
			$pankkitili = $tilino;
			require("inc/pankkitilinoikeellisuus.php");
			$tilino = $pankkitili;
		}

		$query = "select tunnus from toimi where yhtio='$kukarow[yhtio]' and tilinumero='$tilino'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Toimittajaa")." $tilino ".t("ei l�ytynytk��n")."!</font><br><br>";
			$tee = "";
		}
		else {
			$trow		 	= mysql_fetch_array($result);
			$toimittajaid 	= $trow["tunnus"];
			$tee 			= "P";
			$tee2 			= "V"; // Meill� on eroja virheentarkastuksissa, jos tiedot tuli viivakoodista
		}
	}
}

// Tarkistetetaan sy�tteet perustusta varten
if ($tee == 'I') {

	$errormsg = "";
	// Talletetaan k�ytt�j�n nimell� tositteen/liitteen kuva, jos sellainen tuli
	// koska, jos tulee virheit� tiedosto katoaa. Kun kaikki on ok, annetaan sille oikea nimi
	if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$kuva = false;

		// otetaan file extensio
		$path_parts = pathinfo($_FILES['userfile']['name']);
		$ext = $path_parts['extension'];
		if (strtoupper($ext) == "JPEG") $ext = "jpg";

		// extensio pit�� olla oikein
		if (strtoupper($ext) != "JPG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
			$errormsg .= "<font class='error'>".t("Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja")."!</font>";
			$tee = "E";
			$fnimi = "";
		}
		// ja file jonkun kokonen
		elseif ($_FILES['userfile']['size'] == 0) {
			$errormsg .= "<font class='error'>".t("Tiedosto on tyhj�")."!</font>";
			$tee = "E";
			$fnimi = "";
		}

		$query = "SHOW variables like 'max_allowed_packet'";
		$result = mysql_query($query) or pupe_error($query);
		$varirow = mysql_fetch_array($result);

		if ($filesize > $varirow[1]) {
			$errormsg .= "<font class='error'>".t("Liitetiedosto on liian suuri")."! (mysql: $varirow[1]) </font>";
			$tee = "E";
		}

		// jos ei virheit�..
		if ($tee == "I") {
			$kuva = tallenna_liite("userfile", "lasku", 0, "", "", 0, 0, "");
		}
	}
	elseif (isset($_FILES['userfile']['error']) and $_FILES['userfile']['error'] != 4) {
		// nelonen tarkoittaa, ettei mit��n file� uploadattu.. eli jos on joku muu errori niin ei p��stet� eteenp�in
		if ($_FILES['userfile']['error'] == 1) {
			$errormsg .=  "<font class='error'>".t("Liitetiedosto on liian suuri")."! (php: (".ini_get("upload_max_filesize")."))</font><br>";
		}
		else {
			$errormsg .=  "<font class='error'>".t("Laskun kuvan l�hetys ep�onnistui")."! (Error: ".$_FILES['userfile']['error'].")</font><br>";
		}
		$tee = "E";
	}

	if (isset($toitilinumero)) {
		$query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Toimittajaa")." $ytunnus ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$trow = mysql_fetch_array ($result);

		if (isset($toitilinumero) and (strtoupper($trow['maa'])) == 'FI') {
			$pankkitili = $toitilinumero;
			require("inc/pankkitilinoikeellisuus.php");
			$tilino = $pankkitili; //Jos t�m� ei siis onnistu puuttuu tilinumero ja se huomataan my�hemmin
			if ($tilino == '') unset($toitilinumero); //Ei p�ivitet�
		}

		if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
			$query = "UPDATE toimi set tilinumero='$toitilinumero' where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
			$result = mysql_query($query) or pupe_error($query);
		}
		else {
			$query = "UPDATE toimi set ultilno='$toitilinumero', swift='$toiswift' where yhtio='$kukarow[yhtio]' and tunnus='$toimittajaid'";
			$trow['ultilno']=$toitilinumero;
			$trow['swift']=$toiswift;
			$result = mysql_query($query) or pupe_error($query);
		}
	}
	// Hoidetaan pilkut pisteiksi....
	$kassaale = str_replace (",", ".", trim($kassaale));
	$summa    = str_replace (",", ".", trim($summa));

	for ($i=1; $i<$maara; $i++) {
		$isumma[$i] = str_replace (",", ".", trim($isumma[$i]));
	}

	if ($summa != "" and !is_numeric($summa)) {
		$errormsg .= "<font class='error'>".t("Summa ei ole numeerinen")."!</font><br>";
		$tee = 'E';
	}

	if ($kassaale != "" and !is_numeric($kassaale)) {
		$errormsg .= "<font class='error'>".t("Kassa-ale ei ole numeerinen")."!</font><br>";
		$tee = 'E';
	}

	for ($i=1; $i<$maara; $i++) {
		if ($isumma[$i] != "" and !is_numeric($isumma[$i])) {
			$errormsg .= "<font class='error'>".t("Jokin tili�inneist� ei ole numeerinen")."!</font><br>";
			$tee = 'E';
			break;
		}
	}

	// muutetaan numeroiksi
	$tpk += 0;
	$tpp += 0;
	$tpv += 0;
	if ($tpv < 1000) $tpv += 2000;

	if ((int) $kopioi > 12) {
		$errormsg .= "<font class='error'>".t("Laskun voi kopioida korkeintaan 12 kertaa")."</font><br>";
		$tee = 'E';
	}

	if (!checkdate($tpk, $tpp, $tpv)) {
		$errormsg .= "<font class='error'>".t("Virheellinen tapahtumapvm")."</font><br>";
		$tee = 'E';
	}
	else {
		if ($err > 0) {
			if ($erp > 0) {
				$errormsg .= "<font class='error'>".t("Kaksi er�pvm��")."</font><br>";
				$tee = 'E';
			}
			else {
				$erp = date("d", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
				$erk = date("m", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
				$erv = date("Y", mktime(0, 0, 0, $tpk, $tpp+$err, $tpv));
				$err = 0;
			}
		}

		if ($kar > 0) {
			if ($kap > 0) {
				$errormsg .= "<font class='error'>".t("Kaksi kassa-alepvm��")."</font><br>";
				$tee = 'E';
			}
			else {
				$kap = date("d", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
				$kak = date("m", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
				$kav = date("Y", mktime(0, 0, 0, $tpk, $tpp+$kar, $tpv));
				$kar = 0;
			}
		}
	}

	// muutetaan numeroiksi
	$erk += 0;
	$erp += 0;
	$erv += 0;
	if ($erv < 1000) $erv += 2000;

	if (!checkdate($erk, $erp, $erv)) {
		$errormsg .= "<font class='error'>".t("Virheellinen er�pvm")."</font><br>";
		$tee = 'E';
	}

	if ($kapro != 0) {
		if ($kassaale > 0) {
			$errormsg .= "<font class='error'>".t("Kaksi kassa-alesummaa")."</font><br>";
			$tee = 'E';
		}
		else {
			$kassaale = $summa * $kapro / 100;
			$kapro = 0;
		}
	}
	$kassaale = round($kassaale,2);

	if ($kak > 0) {
		$kak += 0;
		$kap += 0;
		$kav += 0;
		if ($kav < 1000) $kav += 2000;

		if (!checkdate($kak, $kap, $kav)) {
			$errormsg .= "<font class='error'>".t("Virheellinen kassaer�pvm")."</font><br>";
			$tee = 'E';
		}
		else {
			if ($kassaale == 0) {
				$errormsg .= "<font class='error'>".t("Kassapvm on, mutta kassa-ale puuttu")."</font><br>";
				$tee = 'E';
			}
		}
	}

	if (trim($hyvak[1]) == "") {
		$errormsg .= "<font class='error'>".t("Laskulla on pakko olla ensimm�inen hyv�ksyj�")."!</font><br>";
		$tee = 'E';
	}

	// poistetaan spacet ja tehd��n uniikki
	$apu_hyvak = array();

	foreach (array_unique($hyvak) as $apu_hyvakrivi) {
		if ($apu_hyvakrivi != " ") {
			$apu_hyvak[] = $apu_hyvakrivi;
		}
	}

	if (count($apu_hyvak) == 1 and in_array($kukarow["kuka"], $apu_hyvak)) {
		$errormsg .= "<font class='error'>".t("Laskun sy�tt�j� ei saa olla ainoa hyv�ksyj�")."!</font><br>";
		$tee = 'E';
	}

	if ($luouusikeikka == "LUO" and $vienti != "C" and $vienti != "J" and $vienti != "F" and $vienti != "K" and $vienti != "I" and $vienti != "L") {
		$errormsg .= "<font class='error'>".t("Keikkaa ei voi perustaa kululaskulle")."</font><br>";
		$tee = 'E';
	}

	if (strlen($viite) == 0 and strlen($viesti) == 0) {
		$errormsg .= "<font class='error'>".t("Anna viite tai viesti")."</font><br>";
		$tee = 'E';
	}

	if (strlen($viite) > 0) {
		require "inc/tarkistaviite.inc";
		if ($ok == 0) {
			$errormsg .= "<font class='error'>".t("Viite on v��rin")."</font><br>";
			$tee = 'E';
		}
	}

	if (strlen($viite) > 0 and strlen($viesti) > 0) {
		$errormsg .= "<font class='error'>".t("Viitett� ja viesti� ei voi antaa yhtaikaa")."</font><br>";
		$tee = 'E';
	}


	// T�ll�in ei tarvitse erikseen sy�tt�� summaa
	if ($maara == 2 and strlen($isumma[1]) == 0) {
		$isumma[1] = $summa;
	}

	if ($maara > 1) {
		if ($syottotyyppi=='prosentti') {
			$viimeinensumma=0;
			for ($i=1; $i<$maara; $i++) {
				$viimeinensumma += $isumma[$i];
			}
			if ($viimeinensumma != 100) {
				$errormsg .= "<font class='error'>".t("Prosenttien yhteisumma ei ole 100")." $viimeinensumma</font><br>";
				$tee = 'E';
			}
			else {
				for ($i=1; $i<$maara; $i++) {
					if ($isumma[$i] != 0) {
						$isumma[$i] = round((float) $summa * (float) $isumma[$i] / 100,2);
						$summatotaali += $isumma[$i];
						$viimeinensumma = $i;
					}
				}
				if (abs($summatotaali - $summa) >= 0.01) {
					$isumma[$viimeinensumma] += $summatotaali - $summa;
				}
				$syottotyyppi='saldo';
			}
		}
	}

	if ((is_array($trow) and strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi == strtoupper($yhtiorow['maa']))) {
		$ohjeitapankille='';
	}
	else {
		if (strlen($ohjeitapankille) > 350) {
			$errormsg .= "<font class='error'>".t("Ohjeita pankille-kent�n pituus 350 ylittyi")."</font><br>";
			$tee = 'E';
		}
	}

 	// K�yd��n tili�innit l�pi
	for ($i=1; $i<$maara; $i++) {
 		// K�sitell��nk� rivi??
		if (strlen($itili[$i]) > 0) {
			$turvasumma 	= $summa;
			$virhe 			= '';
			$tili 			= $itili[$i];
			$summa 			= $isumma[$i];
			$selausnimi		= 'itili[' . $i .']'; // Minka niminen mahdollinen popup on?
			$mistatullaan	= 'ulask.php'; // koska nyky��n on sallittua sy�tt�� nollalasku, eli t�ss� tapauksessa ei sallita ett� kaadutaan tilioinnin summan puuttumiseen
			$ulos			=''; // Mahdollinen popup tyhjennetaan

			require "inc/tarkistatiliointi.inc";

 			// Sielt� kenties tuli p�ivitys tilinumeroon
			if ($ok == 0) {
				// Annetaan k�ytt�j�n p��tt�� onko ok
				if ($itili[$i] != $tili) {
					$itili[$i] = $tili;
					$gok = 1; // Tositetta ei kirjoiteta kantaan viel�
				}
			}
			else {
				$gok = $ok; // Nostetaan virhe ylemm�lle tasolle
			}

			$ivirhe[$i]  = $virhe;
			$iulos[$i] 	 = $ulos;
			$yleissumma += $isumma[$i];
			$summa 		 = $turvasumma;
		}
	}

	// Jossain tapahtui virhe
	if ($gok == 1) {
		$errormsg .= "<font class='error'>".t("Jossain tili�inniss� oli virheit� tai muutoksia")."!</font><br>";
		$tee = 'E';
	}

	if (abs($yleissumma - $summa) >= 0.01 ) {
		$errormsg .= "<font class='error'>".t("Tili�inti heitt��")." $summa != $yleissumma</font><br>";
		$tee = 'E';
	}

	// Jos toimittaja l�ytyy se haetaan, muuten tiedot tulee formista
	if ($toimittajaid > 0) {
		$query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Toimittajaa")." $ytunnus ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$trow = mysql_fetch_array ($result);
	}

	if (strlen($trow['ytunnus']) == 0) {
		$errormsg .= "<font class='error'>".t("Ytunnus puuttuu")."</font><br>";
		$tee = 'E';
	}

	if (strlen($trow['nimi']) == 0) {
		$errormsg .= "<font class='error'>".t("Toimittajan nimi puuttuu")."</font><br>";
		$tee = 'E';
	}

	// Kotimainen toimittaja
	if (strtoupper($tyyppi) == strtoupper($yhtiorow['maa']) or strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {

		if (strlen($trow['tilinumero']) < 6) {
			$errormsg .= "<font class='error'>".t("Pankkitili puuttuu tai liian lyhyt")."</font><br>";
			$tee = 'E';
		}
		else {
			$pankkitili = $trow['tilinumero'];

			if (strtoupper($yhtiorow['maa']) == 'FI') {
				require "inc/pankkitilinoikeellisuus.php";
			}

			if ($pankkitili == "") {
				$errormsg .= "<font class='error'>".t("Pankkitili on virheellinen")."</font><br>";
				$tee = 'E';
			}
			else {
				$trow['tilinumero'] = $pankkitili;
			}
		}
	}
	else {
		// Ulkomainen toimittaja
		if (strlen($trow['ultilno']) == 0) {
			$errormsg .= "<font class='error'>".t("Ulkomainenpankkitili puuttuu")."</font><br>";
			$tee = 'E';
		}
	}
}


if ($tee == 'Y') {

	require ("inc/kevyt_toimittajahaku.inc");

	// Toimittaja l�ytyi
	if ($toimittajaid != 0) {
		$tee 	= "P";
		$trow 	= $toimittajarow;
	}
}

// Annetaan k�ytt�j�lle esit�ytetty formi, jos toimittaja on tai sitten t�ytett�v�t kent�t
if ($tee == 'P' or $tee == 'E') {

	//p�iv�m��r�n tarkistus
	$tilalk = split("-", $yhtiorow["ostoreskontrakausi_alku"]);
	$tillop = split("-", $yhtiorow["ostoreskontrakausi_loppu"]);

	$tilalkpp = $tilalk[2];
	$tilalkkk = $tilalk[1]-1;
	$tilalkvv = $tilalk[0];

	$tilloppp = $tillop[2];
	$tillopkk = $tillop[1]-1;
	$tillopvv = $tillop[0];

	echo "	<SCRIPT LANGUAGE=JAVASCRIPT>

				function verify(){
					var pp = document.lasku.tpp;
					var kk = document.lasku.tpk;
					var vv = document.lasku.tpv;

					pp = Number(pp.value);
					kk = Number(kk.value)-1;
					vv = Number(vv.value);

					if (vv < 1000) {
						vv = vv+2000;
					}

					var dateSyotetty = new Date(vv,kk,pp);
					var dateTallaHet = new Date();
					var ero = (dateTallaHet.getTime() - dateSyotetty.getTime()) / 86400000;

					var tilalkpp = $tilalkpp;
					var tilalkkk = $tilalkkk;
					var tilalkvv = $tilalkvv;
					var dateTiliAlku = new Date(tilalkvv,tilalkkk,tilalkpp);
					dateTiliAlku = dateTiliAlku.getTime();


					var tilloppp = $tilloppp;
					var tillopkk = $tillopkk;
					var tillopvv = $tillopvv;
					var dateTiliLoppu = new Date(tillopvv,tillopkk,tilloppp);
					dateTiliLoppu = dateTiliLoppu.getTime();

					dateSyotetty = dateSyotetty.getTime();

					if (dateSyotetty < dateTiliAlku || dateSyotetty > dateTiliLoppu) {
						var msg = '".t("VIRHE: Sy�tetty p�iv�m��r� ei sis�lly kuluvaan tilikauteen!")."';

						if (alert(msg)) {
							return false;
						}
						else {
							return false;
						}
					}

					if (ero >= 30) {
						var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun yli 30pv menneisyyteen?")."';
						return confirm(msg);
					}
					if (ero <= -14) {
						var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun yli 14pv tulevaisuuteen?")."';
						return confirm(msg);
					}

					if (vv < dateTallaHet.getFullYear()) {
						if (5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}
					else if (vv == dateTallaHet.getFullYear()) {
						if (kk < dateTallaHet.getMonth() && 5 < dateTallaHet.getDate()) {
							var msg = '".t("Oletko varma, ett� haluat p�iv�t� laskun menneisyyteen")."?';
							return confirm(msg);
						}
					}
				}
			</SCRIPT>";


	if ($toimittajaid > 0) {

		$query = "SELECT * FROM toimi WHERE tunnus = '$toimittajaid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo t("Toimittajaa")." $ytunnus ".t("ei l�ytynytk��n")."!";
			exit;
		}

		$trow = mysql_fetch_array($result);

		// Oletusarvot toimittajalta, jos ekaaa kertaa t��ll�
		if ($tee == 'P') {
			$valkoodi 			= $trow['oletus_valkoodi'];
			$kar      			= $trow['oletus_kapvm'];
			$kapro    			= $trow['oletus_kapro'];
			if ($tee2 != 'V') 	$err = $trow['oletus_erapvm']; // Viivakoodilla on aina erapvm ja sit� k�ytet��n
			$hyvak[1]   		= $trow['oletus_hyvak1'];
			$hyvak[2]   		= $trow['oletus_hyvak2'];
			$hyvak[3]   		= $trow['oletus_hyvak3'];
			$hyvak[4]   		= $trow['oletus_hyvak4'];
			$hyvak[5]   		= $trow['oletus_hyvak5'];
			$oltil      		= $trow['tilino'];
			$olkustp    		= $trow['kustannuspaikka'];
			$olkohde    		= $trow['kohde'];
			$olprojekti 		= $trow['projekti'];
			$osuoraveloitus		= $trow['oletus_suoraveloitus'];
			$ohyvaksynnanmuutos	= $trow['oletus_hyvaksynnanmuutos'];
			$vienti				= $trow['oletus_vienti'];
			$ohjeitapankille    = $trow['ohjeitapankille'];
		}

		// Tehd��n konversio checkboxseja varten
		if ($osuoraveloitus != '') $osuoraveloitus = 'checked';
		if ($ohyvaksynnanmuutos != '') $ohyvaksynnanmuutos = 'checked';

		$fakta = "";
		if (trim($trow["fakta"]) != "") {
			$fakta = "<br><br><font class='message'>$trow[fakta]</font>";
		}

		echo "<table><tr><td valign='top' style='padding: 0px;'>";


		echo "<table>";
		echo "<tr><th colspan='2'>".t("Toimittaja")."</th></tr>";
		echo "<tr><td colspan='2'>$trow[nimi] $trow[nimitark] ($trow[ytunnus])</td></tr>";
		echo "<tr><td colspan='2'>$trow[osoite] $trow[osoitetark], $trow[maa]-$trow[postino] $trow[postitp], $trow[maa] $fakta</td></tr>";
		echo "<tr><td><form action='yllapito.php?toim=toimi&tunnus=$toimittajaid&lopetus=ulask.php////tee=$tee//toimittajaid=$toimittajaid//maara=$maara' method='post'>";
		echo "<input type = 'submit' value = '".t("Muuta toimittajan tietoja")."'></form>";
		echo "</td></tr></table>";
		echo "</td>";

		// eri tilitiedot riippuen onko suomalainen vai ei
		echo "<td valign='top' style='padding: 0px;'>";
		echo "<table>";
		echo "<tr><th colspan='2'>".t("Tilitiedot")."</th></tr>";

		echo "<form name = 'lasku' action = '$PHP_SELF?tee=I&toimittajaid=$toimittajaid' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";

		if (strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) {

			$pankki = $trow['pankki1'];

			if ($trow['pankki2']!='') $pankki .= "<br>$trow[pankki2]";
			if ($trow['pankki3']!='') $pankki .= "<br>$trow[pankki3]";
			if ($trow['pankki4']!='') $pankki .= "<br>$trow[pankki4]";

			if ($trow['ultilno']=='') { //Toimittajan tilinumero puuttuu. Annetaan sen sy�tt�
				echo "<tr><td>".t("Ultilino")."</td><td><input type='text' name='toitilinumero' size=10 value='$toitilinumero'></td></tr>";
				echo "<tr><td>".t("SWIFT")."</td><td><input type='text' name='toiswift' size=10 value='$toiswift'></td></tr>";
			}
			else {
				echo "<tr><td>".t("Ultilino")."</td><td>$trow[ultilno]</td></tr>";
				echo "<tr><td>".t("SWIFT")."</td><td>$trow[swift]</td></tr>";
				echo "<tr><td>".t("Pankki")."</td><td>$pankki</td></tr>";
			}
		}
		else {
			if ($trow['tilinumero']=='') { //Toimittajan tilinumero puuttuu. Annetaan sen sy�tt�
				echo "<tr><td>".t("Tilinumero")."</td><td><input type='text' name='toitilinumero' size=10 value='$toitilinumero'></td></tr>";
			}
			else {
				echo "<tr><td>".t("Tilinumero")."</td><td>$trow[tilinumero]</td></tr>";
			}
		}
		echo "</table>";
		echo "</td>";

		// tulostetaan mahdolliset errorimessaget
		if ($errormsg != '') {
			echo "<td class='back' valign='top'>$errormsg</td>";
		}
		else {
			// N�ytet��n nelj� viimeisint� laskua, jotta v�hennet��n duplikaattien tallennusta
			$query = "	SELECT tapvm, summa
						FROM lasku
						WHERE yhtio='$kukarow[yhtio]' and
						tila in ('H', 'M') and
						ytunnus='$trow[ytunnus]' and
						tapvm > date_sub(now(), interval 30 day)
						ORDER BY tapvm desc
						LIMIT 4";
			$vikatlaskutres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vikatlaskutres) > 0) {
				echo "<td valign='top' style='padding: 0px;'>";
				echo "<table><tr>";

				for ($i = 0; $i < mysql_num_fields($vikatlaskutres); $i++) {
					echo "<th>" . t(mysql_field_name($vikatlaskutres,$i))."</th>";
				}

				echo "</tr>";

				while ($vikatlaskutrow = mysql_fetch_array($vikatlaskutres)) {
					echo "<tr>";
					for ($i=0; $i<mysql_num_fields($vikatlaskutres); $i++) {
						if (mysql_field_name($vikatlaskutres,$i) == 'tapvm') {
							echo "<td>".tv1dateconv($vikatlaskutrow[$i])."</td>";
						}
						else {
							echo "<td>$vikatlaskutrow[$i]</td>";
						}
					}
					echo "</tr>";
				}

				echo "</table>";
				echo "</td>";
			}
		}

		echo "</tr></table>";
	}
	else {

 		// jaaha, ei ollut toimittajaa, joten pyydet��n sy�tt�m��n tiedot
		echo "<form name = 'lasku' action = '$PHP_SELF?tee=I&toimittajaid=$toimittajaid' method='post' enctype='multipart/form-data' onSubmit = 'return verify()'>";
		echo "<input type='hidden' name='lopetus' value='$lopetus'>";
		echo "<input type='hidden' name='oma' value='1'>";
		echo "<input type='hidden' name='tyyppi' value='$tyyppi'>";

		if ($errormsg != '') echo "$errormsg<br>";

		if ($tyyppi != strtoupper($yhtiorow['maa'])) {
			echo "
				<font class='message'>".t("Ulkomaalaisen toimittajan tiedot")."</font>
				<table><tr><td class='back' valign='top'>

				<table>
				<tr><th>".t("ytunnus")."</th>	<td><input type='text' name='trow[ytunnus]'    maxlength='16'  size=18 value='$trow[ytunnus]'></td></tr>
				<tr><th>".t("nimi")."</th>		<td><input type='text' name='trow[nimi]'       maxlength='45' size=45 value='$trow[nimi]'></td></tr>
				<tr><th>".t("nimitark")."</th>	<td><input type='text' name='trow[nimitark]'   maxlength='45' size=45 value='$trow[nimitark]'></td></tr>
				<tr><th>".t("osoite")."</th>		<td><input type='text' name='trow[osoite]'     maxlength='45' size=45 value='$trow[osoite]'></td></tr>
				<tr><th>".t("osoitetark")."</th>	<td><input type='text' name='trow[osoitetark]' maxlength='45' size=45 value='$trow[osoitetark]'></td></tr>
				<tr><th>".t("postino")."</th>	<td><input type='text' name='trow[postino]'    maxlength='15' size=10 value='$trow[postino]'></td></tr>
				<tr><th>".t("postitp")."</th>	<td><input type='text' name='trow[postitp]'    maxlength='45' size=45 value='$trow[postitp]'></td></tr>
				</table>

				</td><td class='back'>

				<table>
				<tr><th>".t("maa")."</th>	<td><input type='text' name='trow[maa]' maxlength='2'  size=4  value='$trow[maa]'></td></tr>
				<tr><th>".t("Ultilino")."</th>	<td><input type='text' name='trow[ultilno]'  maxlength='35' size=45 value='$trow[ultilno]'></td></tr>
				<tr><th>".t("SWIFT")."</th>		<td><input type='text' name='trow[swift]'    maxlength='11' size=45 value='$trow[swifth]'></td></tr>
				<tr><th>".t("pankki1")."</th>	<td><input type='text' name='trow[pankki1]'  maxlength='35' size=45 value='$trow[pankki1]'></td></tr>
				<tr><th>".t("pankki2")."</th>	<td><input type='text' name='trow[pankki2]'  maxlength='35' size=45 value='$trow[pankki2]'></td></tr>
				<tr><th>".t("pankki3")."</th>	<td><input type='text' name='trow[pankki3]'  maxlength='35' size=45 value='$trow[pankki3]'></td></tr>
				<tr><th>".t("pankki4")."</th>	<td><input type='text' name='trow[pankki4]'  maxlength='35' size=45 value='$trow[pankki4]'></td></tr>
				</table>

				</td></tr></table>";
		}
		else {
			echo "
				<font class='message'>".t("Kotimaisen toimittajan tiedot")."</font>
				<input type='hidden' name = 'trow[maa]' value = ".strtoupper($yhtiorow['maa']).">

				<table>
				<tr><th>".t("ytunnus")."</th>	<td><input type='text' name='trow[ytunnus]'    maxlength='8'  size=10 value='$trow[ytunnus]'></td></tr>
				<tr><th>".t("nimi")."</th>		<td><input type='text' name='trow[nimi]'       maxlength='45' size=45 value='$trow[nimi]'></td></tr>
				<tr><th>".t("nimitark")."</th>	<td><input type='text' name='trow[nimitark]'   maxlength='45' size=45 value='$trow[nimitark]'></td></tr>
				<tr><th>".t("osoite")."</th>		<td><input type='text' name='trow[osoite]'     maxlength='45' size=45 value='$trow[osoite]'></td></tr>
				<tr><th>".t("osoitetark")."</th>	<td><input type='text' name='trow[osoitetark]' maxlength='45' size=45 value='$trow[osoitetark]'></td></tr>
				<tr><th>".t("postino")."</th>	<td><input type='text' name='trow[postino]'    maxlength='5'  size=10 value='$trow[postino]'></td></tr>
				<tr><th>".t("postitp")."</th>	<td><input type='text' name='trow[postitp]'    maxlength='45' size=45 value='$trow[postitp]'></td></tr>
				<tr><th>".t("Tilinumero")."</th>	<td><input type='text' name='trow[tilinumero]' maxlength='45' size=45 value='$trow[tilinumero]'></td></tr>
				</table>
				";
		}

		echo "<br>";
	}

	// Kursorin oletuspaikka
	$formi = 'lasku';
	$kentta = 'tpp';

	echo "	<table cellpadding='3' cellspacing='0' border='0'>
			<tr>
			<td>".t("Laskun p�iv�ys")."</td>
			<td><input type='text' name='tpp' maxlength='2' size=2 value='$tpp'>
			<input type='text' name='tpk' maxlength='2' size=2 value='$tpk'>
			<input type='text' name='tpv' maxlength='4' size=4 value='$tpv'> ".t("ppkkvvvv")."</td>
			</tr>";

	echo "<tr>
			<td>".t("Er�pvm")."</td><td><input type='text' name='erp' maxlength='2' size=2 value='$erp'>
			<input type='text' name='erk' maxlength='2' size=2 value='$erk'>
			<input type='text' name='erv' maxlength='4' size=4 value='$erv'> ".t("ppkkvvvv tai")."
			<input type='text' name='err' maxlength='3' size=2 value='$err'> ".t("p�iv�� tai suoraveloitus")."
			<input type='checkbox' name='osuoraveloitus' $osuoraveloitus>
			</td>
		  </tr>";

	echo "<tr><td>".t("Laskun summa")."</td>";
	echo "<td><input type='text' name='summa' value='$summa'>";

	//Tehd��n valuuttapopup, jos ulkomainen toimittaja muuten kirjoitetaan vain $yhtiorow[valkoodi]
	if ((is_array($trow) and strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi != strtoupper($yhtiorow['maa']))) {

		$query = "	SELECT nimi
					FROM valuu
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY jarjestys";
		$vresult = mysql_query($query) or pupe_error($query);

		echo "<select name='valkoodi'>";

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($valkoodi == $vrow['nimi']) {
				$sel = "selected";
			}
			echo "<option value ='$vrow[nimi]' $sel>$vrow[nimi]</option>";
		}
		echo "</select>";
	}
	else {
		echo "<input type='hidden' name='valkoodi' value='$yhtiorow[valkoodi]'> $yhtiorow[valkoodi]";
	}

	echo "</td></tr>";

	echo "<tr>
			<td>".t("Viite")."</td><td><input type='text'  maxlength='20' size='25' name='viite' value='$viite'>
		</tr>
		<tr>
			<td>".t("Kassaer�pvm")."</td><td>
			<input type='text' name='kap' maxlength='2' size=2 value='$kap'>
			<input type='text' name='kak' maxlength='2' size=2 value='$kak'>
			<input type='text' name='kav' maxlength='4' size=4 value='$kav'> ".t("ppkkvvvv tai")."
			<input type='text' name='kar' maxlength='3' size=2 value='$kar'> ".t("p�iv��")."
			</td>
		</tr>
		<tr>
			<td>".t("Kassa-alennus")."</td><td><input type='text' name='kassaale' value='$kassaale'>
			<input type='text' name='kapro' maxlength='6' size=6 value='$kapro'>%</td>
		</tr>
		<tr>
			<td>".t("Viesti")."</td><td><input type='text' maxlength='70' size='60' name='viesti' value='$viesti'></td>
		</tr>

		<tr>
			<td>".t("Kommentti")."</td><td><input type='text' name='komm' size='60' value='$komm'></td>
		</tr>
		<tr>
			<td>".t("Laskunumero")."</td><td><input type='text' name='toimittajan_laskunumero' value='$toimittajan_laskunumero' size='60'></td>
		</tr>";

		if ((is_array($trow) and strtoupper($trow['maa']) != strtoupper($yhtiorow['maa'])) or (!is_array($trow) and $tyyppi != strtoupper($yhtiorow['maa']))) {

			echo "
			<tr>
				<td>".t("Ohjeita pankille")."</td><td><textarea name='ohjeitapankille' rows='2' cols='58'>$ohjeitapankille</textarea></td>
			</tr>";

		}

/*
		echo "<tr>
			<td>".t("Sis�inen tieto1")."</td><td><input type='text' maxlength='20' name='sis1' value='$sis1'></td>
		</tr>
		<tr>
			<td>".t("Sis�inen tieto2")."</td><td><input type='text' maxlength='20' name='sis2' value='$sis2'></td>
		</tr>";
*/

	if ($vienti == 'A') $vientia = 'selected';
	if ($vienti == 'B') $vientib = 'selected';
	if ($vienti == 'C') $vientic = 'selected';
	if ($vienti == 'D') $vientid = 'selected';
	if ($vienti == 'E') $vientie = 'selected';
	if ($vienti == 'F') $vientif = 'selected';
	if ($vienti == 'G') $vientig = 'selected';
	if ($vienti == 'H') $vientih = 'selected';
	if ($vienti == 'I') $vientii = 'selected';
	if ($vienti == 'J') $vientij = 'selected';
	if ($vienti == 'K') $vientik = 'selected';
	if ($vienti == 'L') $vientil = 'selected';

	echo "
		<tr>
			<td>".t("Laskun tyyppi")."</td><td>
				<select name='vienti'>
					<option value='A' $vientia>".t("Kotimaa")."</option>
					<option value='B' $vientib>".t("Kotimaa huolinta/rahti")."</option>
					<option value='C' $vientic>".t("Kotimaa vaihto-omaisuus")."</option>
					<option value='J' $vientij>".t("Kotimaa raaka-aine")."</option>

					<option value='D' $vientid>".t("EU")."</option>
					<option value='E' $vientie>".t("EU huolinta/rahti")."</option>
					<option value='F' $vientif>".t("EU vaihto-omaisuus")."</option>
					<option value='K' $vientik>".t("EU raaka-aine")."</option>

					<option value='G' $vientig>".t("ei-EU")."</option>
					<option value='H' $vientih>".t("ei-EU huolinta/rahti")."</option>
					<option value='I' $vientii>".t("ei-EU vaihto-omaisuus")."</option>
					<option value='L' $vientil>".t("ei-EU raaka-aine")."</option>
				</select>
			</td>
		</tr>";

	// tutkitaan ollaanko jossain toimipaikassa alv-rekister�ity
	$query = "	SELECT *
				FROM yhtion_toimipaikat
				WHERE yhtio = '$kukarow[yhtio]'
				and maa != ''
				and vat_numero != ''
				and toim_alv != ''";
	$alhire = mysql_query($query) or pupe_error($query);

	// ollaan alv-rekister�ity
	if (mysql_num_rows($alhire) >= 1) {

		if ($tilino_alv == "") {
			$tilino_alv = $trow["tilino_alv"];
		}

		echo "<tr>";
		echo "<td>".t("Alv tili")."</td><td>";
		echo "<select name='tilino_alv'>";
		echo "<option value='$yhtiorow[alv]'>$yhtiorow[alv] - $yhtiorow[nimi], $yhtiorow[kotipaikka], $yhtiorow[maa]</option>";

		while ($vrow = mysql_fetch_array($alhire)) {
			$sel = "";
			if ($tilino_alv == $vrow['toim_alv']) {
				$sel = "selected";
			}
			echo "<option value='$vrow[toim_alv]' $sel>$vrow[toim_alv] - $vrow[nimi], $vrow[kotipaikka], $vrow[maa]</option>";
		}

		echo "</select>";
		echo "</td>";
		echo "</tr>";
	}
	else {
		$tilino_alv = $yhtiorow["alv"];
		echo "<input type='hidden' name='tilino_alv' value='$tilino_alv'>";
	}

	echo "<tr>";
	echo "<td>".t("Laskun kuva")."</td>";

	if ($kuva) {
		echo "<td>".t("Kuva jo tallessa")."!<input name='kuva' type='hidden' value = '$kuva'></td>";
	}
	else {
		echo "<input type='hidden' name='MAX_FILE_SIZE' value='50000000'>";
		echo "<td><input name='userfile' type='file'></td>";
	}

	echo "</tr>";

	echo "<tr><td colspan='2'><hr></td></tr>";

	echo "<tr><td valign='top'>".t("Hyv�ksyj�t")."</td><td>";

	$query = "SELECT kuka, nimi
			  FROM kuka
			  WHERE yhtio = '$kukarow[yhtio]' and hyvaksyja='o'
			  ORDER BY nimi";
	$vresult = mysql_query($query) or pupe_error($query);

	$ulos = '';
	// T�ytet��n 5 hyv�ksynt�kentt��
	for ($i=1; $i<6; $i++) {

		while ($vrow = mysql_fetch_array($vresult)) {
			$sel = "";
			if ($hyvak[$i] == $vrow['kuka']) {
				$sel = "selected";
			}
			$ulos .= "<option value ='$vrow[kuka]' $sel>$vrow[nimi]";
		}

 		// K�yd��n sama data l�pi uudestaan
		if (!mysql_data_seek ($vresult, 0)) {
			echo "mysql_data_seek failed!";
			exit;
		}
		echo "<select name='hyvak[$i]'>
			  <option value = ' '>".t("Ei kukaan")."
			  $ulos
			  </select>";
		$ulos="";

		// Tehd��n checkbox, jolla annetaan lupa muuttaa hyv�ksynt�listaa my�hemmin
		if ($i == 1) {
			echo " ".t("Listaa saa muuttaa")." <input type='checkbox' name='ohyvaksynnanmuutos' $ohyvaksynnanmuutos>";
		}
		echo "<br>";
	}

	echo "</td></tr>";

	echo "<tr><td colspan='2'>";

	$uusiselke = "";

	if ($luouusikeikka != '') {
		$uusiselke = "CHECKED";
	}

	echo "<hr><table>";
	echo "<tr><td>".t("Luo uusi keikka laskulle").":</td><td><input type='checkbox' name='luouusikeikka' value='LUO' $uusiselke></td>";
	echo "<td>".t("Kopio lasku")."</td><td><input type='input' name='kopioi' value='$kopioi' size='3' maxlength='2'></td><td>".t("kertaa")."</td></tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";

	echo "<tr><td colspan='2'>";

	// Hoidetaan oletukset!

	for ($i=1; $i<$maara; $i++) {
		if ($i == 1 and strlen($itili[$i]) == 0) {
			$itili[$i] = $oltil;
		}
		if ($i == 1 and strlen($ikohde[$i]) == 0) {
			$ikohde[$i] = $olkohde;
		}
		if ($i == 1 and strlen($iprojekti[$i]) == 0) {
			$iprojekti[$i] = $olprojekti;
		}
		if ($i == 1 and strlen($ikustp[$i]) == 0) {
			$ikustp[$i] = $olkustp;
		}
		if (strlen($ivero[$i]) == 0) {
			if (strtoupper($trow['maa']) == strtoupper($yhtiorow['maa'])) {
				$ivero[$i] = alv_oletus();
			}
			else {
				$ivero[$i] = 0;
			}
		}
	}


	// ykk�stasolla ei saa tehd� tili�intej�, laitetaan oletukset
	if ($kukarow['taso'] < '2') {

		// Jos toimittajalla ei ollut oletustili�, haetaan se yritykselt�
		if ($itili[1] == '' or $itili[1] == 0) $itili[1] = $yhtiorow['muutkulut'];

		echo "<input type='hidden' value='$itili[1]'		name='itili[1]'>
				<input type='hidden' value='$ikohde[1]'		name='ikohde[1]'>
				<input type='hidden' value='$iprojekti[1]'	name='iprojekti[1]'>
				<input type='hidden' value='$ikustp[1]'		name='ikustp[1]'>
				<input type='hidden' value='$ivero[1]'		name='ivero[1]'>";
	}
	else {

		// Tehd��n haluttu m��r� tili�intirivej�
		$syottotyyppisaldo='checked';
		$syottotyyppiprosentti='';
		if (isset($syottotyyppi)) {
			if ($syottotyyppi=='prosentti') $syottotyyppiprosentti = 'checked';
		}

		echo "<hr>
			<table>
				<tr>
					<th>".t("Tili")."</th>
					<th>".t("Kustannuspaikka")."</th>
					<th><input type='radio' name='syottotyyppi' value='summa' $syottotyyppisaldo>".t("Summa")." <input type='radio' name='syottotyyppi' value='prosentti' $syottotyyppiprosentti>".t("Prosentti")."</th>
					<th style='text-align:right;'>".t("Vero")."</th>
				</tr>";

		for ($i=1; $i<$maara; $i++) {

			echo "<tr><td valign='top'>";

 			// Tehaan kentta tai naytetaan popup
			if ($iulos[$i] == '') {
				echo livesearch_kentta("lasku", "TILIHAKU", "itili[$i]", 170, $itili[$i], "EISUBMIT");
			}
			else {
				echo "$iulos[$i]";
			}

			// Etsit��n selv�kielinen tilinnimi, jos sellainen on
			if (strlen($itili[$i]) != 0) {
				$query = "SELECT nimi FROM tili	WHERE yhtio = '$kukarow[yhtio]' and tilino = '$itili[$i]'";
				$vresult = mysql_query($query) or pupe_error($query);

				if (mysql_num_rows($vresult) != 0) {
					$vrow = mysql_fetch_array($vresult);
					echo "<br>$vrow[nimi]";
				}
			}

			echo "</td>";

			// Tehd��n kustannuspaikkapopup
			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'K'
						and kaytossa != 'E'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<td valign='top'>";

			if (mysql_num_rows($vresult) > 0) {
				echo "<select name='ikustp[$i]'>";
				echo "<option value =' '>".t("Ei kustannuspaikkaa")."";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($ikustp[$i] == $vrow['tunnus']) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
				}

				echo "</select><br>";
			}

			// Tehd��n kohdepopup
			$query = "	SELECT tunnus, nimi, koodi
						FROM kustannuspaikka
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi = 'O'
						and kaytossa != 'E'
						ORDER BY nimi";
			$vresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($vresult) > 0) {
				echo "<select name='ikohde[$i]'>";
				echo "<option value =' '>".t("Ei kohdetta")."";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($ikohde[$i] == $vrow['tunnus']) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
				}
				echo "</select><br>";
			}

			// Tehd��n projektipopup

			if (mysql_num_rows($vresult) > 0) {
				$query = "	SELECT tunnus, nimi, koodi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and tyyppi = 'P'
							and kaytossa != 'E'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				echo "<select name='iprojekti[$i]'>";
				echo "<option value =' '>".t("Ei projektia")."";

				while ($vrow = mysql_fetch_array($vresult)) {
					$sel = "";
					if ($iprojekti[$i] == $vrow[0]) {
						$sel = "selected";
					}
					echo "<option value ='$vrow[tunnus]' $sel>$vrow[koodi] $vrow[nimi]</option>";
				}
				echo "</select>";
			}

			echo "</td>";

			echo "<td valign='top'><input type='text' name='isumma[$i]' value='$isumma[$i]'></td>";
			echo "<td valign='top'>" . alv_popup('ivero['.$i.']', $ivero[$i]);
			echo "$ivirhe[$i]";
			echo "</td></tr>";

			if ($maara > 1 and $i+1 != $maara) {
				echo "<tr><td colspan='4'><hr></td></tr>";
			}
		}

		echo "</table>";

	} // end taso < 2

	echo "</td></tr>
		</table>
		<br>
		<input type = 'hidden' name = 'toimittajaid' value = '$toimittajaid'>
		<input type = 'hidden' name = 'maara' value = '$maara'>
		<input type = 'submit' value = '".t("Perusta")."'></form>";

} // end if tee = 'P'

if ($tee == 'I') {

	$query = "SELECT kurssi FROM valuu WHERE nimi = '$valkoodi' and yhtio = '$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1) {
		echo t("Valuuttaa")." $valkoodi ".t("ei l�ytynytk��n")."!";
		exit;
	}

	$vrow = mysql_fetch_array($result);

	$tila = "M";
	$hyvak[5] = trim($hyvak[5]);
	$hyvak[4] = trim($hyvak[4]);
	$hyvak[3] = trim($hyvak[3]);
	$hyvak[2] = trim($hyvak[2]);
	$hyvak[1] = trim($hyvak[1]);

	if (strlen($hyvak[5]) > 0) {
		$hyvaksyja_nyt=$hyvak[5];
		$tila = "H";
	}
	if (strlen($hyvak[4]) > 0) {
		$hyvaksyja_nyt=$hyvak[4];
		$tila = "H";
	}
	if (strlen($hyvak[3]) > 0) {
		$hyvaksyja_nyt=$hyvak[3];
		$tila = "H";
	}
	if (strlen($hyvak[2]) > 0) {
		$hyvaksyja_nyt=$hyvak[2];
		$tila = "H";
	}
	if (strlen($hyvak[1]) > 0) {
		$hyvaksyja_nyt=$hyvak[1];
		$tila = "H";
	}

	$olmapvm = $erv . "-" . $erk . "-" . $erp;

	if (strlen(trim($kap)) > 0) {
		$olmapvm = $kav . "-" . $kak . "-" . $kap;
	}

	// Jotkut maat (kaikki paitsi Suomi) vaativat toistaiseksi toistenroita :( Taklataan sit� t�ss�
	$tositenro=0;

	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
		$query = "LOCK TABLE tiliointi WRITE, lasku WRITE, sanakirja WRITE, liitetiedostot WRITE";
		$result = mysql_query($query) or pupe_error($query);

		$alaraja = 41000000;
		$ylaraja = 42000000;

		$query  = "	SELECT max(tosite) + 1 nro FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and tosite > $alaraja and tosite < $ylaraja";
		$tresult = mysql_query($query) or pupe_error($query);
		$tositenrorow = mysql_fetch_array($tresult);

		if ($tositenrorow['nro'] < $alaraja) $tositenrorow['nro'] = 41000001;
		$tositenro=$tositenrorow['nro'];
	}

	if ($komm != "") {
		$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") " . trim($komm);
	}

	if ($kuva) {
		$ebid = '';
	}

	// Kirjoitetaan lasku
	$query = "INSERT into lasku set
			yhtio = '$kukarow[yhtio]',
			summa = '$summa',
			kasumma = '$kassaale',
			erpcm = '$erv-$erk-$erp',
			kapvm = '$kav-$kak-$kap',
			olmapvm = '$olmapvm',
			valkoodi = '$valkoodi',
			hyvak1 = '$hyvak[1]',
			hyvak2 = '$hyvak[2]',
			hyvak3 = '$hyvak[3]',
			hyvak4 = '$hyvak[4]',
			hyvak5 = '$hyvak[5]',
			hyvaksyja_nyt = '$hyvaksyja_nyt',
			ytunnus = '$trow[ytunnus]',
			tilinumero = '$trow[tilinumero]',
			nimi = '$trow[nimi]',
			nimitark = '$trow[nimitark]',
			osoite = '$trow[osoite]',
			osoitetark = '$trow[osoitetark]',
			postino = '$trow[postino]',
			postitp = '$trow[postitp]',
			maa =  '$trow[maa]',
			viite = '$viite',
			viesti = '$viesti',
			vienti = '$vienti',
			tapvm = '$tpv-$tpk-$tpp',
			ebid = '$ebid',
			tila = '$tila',
			ultilno = '$trow[ultilno]',
			pankki_haltija = '$trow[pankki_haltija]',
			swift = '$trow[swift]',
			pankki1 = '$trow[pankki1]',
			pankki2 = '$trow[pankki2]',
			pankki3 = '$trow[pankki3]',
			pankki4 = '$trow[pankki4]',
			vienti_kurssi = '$vrow[kurssi]',
			laatija = '$kukarow[kuka]',
			liitostunnus = '$toimittajaid',
			hyvaksynnanmuutos = '$ohyvaksynnanmuutos',
			suoraveloitus = '$osuoraveloitus',
			luontiaika = now(),
			comments = '$komm',
			asiakkaan_tilausnumero = '$toimittajan_laskunumero',
			sisviesti1 = '$ohjeitapankille',
			alv_tili = '$tilino_alv'";

// Poistin n�m� toistaiseksi insertist�
//			sisviesti1 = '$sis1',
//			sisviesti2 = '$sis2',

	$result = mysql_query($query) or pupe_error($query);
	$tunnus = mysql_insert_id ($link);

	if ($kuva) {
		// p�ivitet��n kuvalle viel� linkki toiseensuuntaa
		$query = "UPDATE liitetiedostot set liitostunnus='$tunnus', selite='$trow[nimi] $summa $valkoodi' where tunnus='$kuva'";
		$result = mysql_query($query) or pupe_error($query);
	}

	$omasumma = round($summa * $vrow['kurssi'],2);
	$omasumma_valuutassa = $summa;

	$vassumma = -1 * $omasumma;
	$vassumma_valuutassa = -1 * $omasumma_valuutassa;

	// Tehd��n oletustili�innit

	//Tutkitaan otsovelkatili�
	if ($trow["konserniyhtio"] != '') {
		$ostovelat = $yhtiorow["konserniostovelat"];
	}
	else {
		$ostovelat = $yhtiorow["ostovelat"];
	}

	// Ostovelka
	$query = "INSERT INTO tiliointi SET
				yhtio				= '$kukarow[yhtio]',
				ltunnus				= '$tunnus',
				tilino				= '$ostovelat',
				kustp				= 0,
				tapvm				= '$tpv-$tpk-$tpp',
				summa				= '$vassumma',
				summa_valuutassa	= '$vassumma_valuutassa',
				valkoodi			= '$valkoodi',
				vero				= 0,
				lukko				= '1',
				tosite				= '$tositenro',
				laatija 			= '$kukarow[kuka]',
				laadittu 			= now()";
	$result = mysql_query($query) or pupe_error($query);


	// Oletuskulutili�inti
	// Nyt on saatava py�ristykset ok
	$veroton = 0;
	$veroton_valuutassa = 0;

	$muusumma = 0;
	$muusumma_valuutassa = 0;

	for ($i=1; $i<$maara; $i++) {
		$isumma_valuutassa[$i] = $isumma[$i];
		$isumma[$i] = round($isumma[$i] * $vrow['kurssi'], 2);

 		// Netotetaan alvi
		if ($ivero[$i] != 0) {
			$ialv[$i] = round($isumma[$i] - $isumma[$i] / (1 + ($ivero[$i] / 100)),2);
			$ialv_valuutassa[$i] = round($isumma_valuutassa[$i] - $isumma_valuutassa[$i] / (1 + ($ivero[$i] / 100)),2);

			$isumma[$i] -= $ialv[$i];
			$isumma_valuutassa[$i] -= $ialv_valuutassa[$i];

			$muusumma += $isumma[$i] + $ialv[$i];
			$muusumma_valuutassa += $isumma_valuutassa[$i] + $ialv_valuutassa[$i];
		}
		else {
			$muusumma += $isumma[$i];
			$muusumma_valuutassa += $isumma_valuutassa[$i];
		}

		$veroton += $isumma[$i];
		$veroton_valuutassa += $isumma_valuutassa[$i];
	}

	if ($muusumma != $omasumma) {
		echo "<font class='message'>".t("Valuuttapy�ristyst�")." " . round($muusumma-$omasumma,2) . "</font><br>";
		for ($i=1; $i<$maara; $i++) {
			if ($isumma[$i] != 0) {
				$isumma[$i] += $omasumma-$muusumma;
				$isumma_valuutassa[$i] += $omasumma_valuutassa-$muusumma_valuutassa;
				$i=$maara;
			}
		}
	}

	$muusumma = 0;
	$muusumma_valuutassa = 0;

	for ($i=1; $i<$maara; $i++) {
		$muusumma += $isumma[$i] + $ialv[$i];
		$muusumma_valuutassa += $isumma_valuutassa[$i] + $ialv_valuutassa[$i];
	}

	if (round($muusumma,2) != round($omasumma,2)) {
		echo t("Valuuttapy�ristyksen j�lkeenkin heitt��")." $omasumma <> $muusumma<br>";
		echo t("T�st� ei selvitty! Kulutili�inti� ei tehty")."<br>";
		exit;
	}

	for ($i=1; $i<$maara; $i++) {

		if (strlen($itili[$i]) > 0) {

			$query = "	INSERT INTO tiliointi SET
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$itili[$i]',
						kustp 				= '$ikustp[$i]',
						kohde 				= '$ikohde[$i]',
						projekti 			= '$iprojekti[$i]',
						tapvm 				= '$tpv-$tpk-$tpp',
						summa 				= '$isumma[$i]',
						summa_valuutassa	= '$isumma_valuutassa[$i]',
						valkoodi 			= '$valkoodi',
						vero 				= '$ivero[$i]',
						selite 				= '$iselite[$i]',
						lukko 				= '',
						tosite 				= '$tositenro',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$result = mysql_query($query) or pupe_error($query);

 			// Tili�id��n alv
			if ($ivero[$i] != 0) {
				$isa = mysql_insert_id ($link); // N�in l�yd�mme t�h�n liittyv�t alvit....

				$query = "	INSERT INTO tiliointi SET
							yhtio 				= '$kukarow[yhtio]',
							ltunnus 			= '$tunnus',
							tilino 				= '$tilino_alv',
							kustp 				= 0,
							kohde 				= 0,
							projekti 			= 0,
							tapvm 				= '$tpv-$tpk-$tpp',
							summa 				= '$ialv[$i]',
							summa_valuutassa 	= '$ialv_valuutassa[$i]',
							valkoodi 			= '$valkoodi',
							vero 				= 0,
							selite 				= '$iselite[$i]',
							lukko 				= '1',
							tosite 				= '$tositenro',
							laatija 			= '$kukarow[kuka]',
							laadittu 			= now(),
							aputunnus 			= '$isa'";
				$result = mysql_query($query) or pupe_error($query);
			}

			// Varastonmuutos tulee tuloslaskelmaan, joten kopioidaan sinne kustannuspaikat jne...
			if ($vienti != 'A' and $vienti != 'D' and $vienti != 'G' and $vienti != '') {

				if ($vienti == 'J' or $vienti == 'K' or $vienti == 'L') {
					$varastonmuutostili = $yhtiorow["raaka_ainevarastonmuutos"];
				}
				else {
					$varastonmuutostili = $yhtiorow["varastonmuutos"];
				}

				$query = "	INSERT INTO tiliointi SET
							yhtio 				= '$kukarow[yhtio]',
							ltunnus 			= '$tunnus',
							tilino 				= '$varastonmuutostili',
							kustp 				= '$ikustp[$i]',
							kohde 				= '$ikohde[$i]',
							projekti 			= '$iprojekti[$i]',
							tapvm 				= '$tpv-$tpk-$tpp',
							summa 				= $isumma[$i] * -1,
							summa_valuutassa	= $isumma_valuutassa[$i] * -1,
							valkoodi 			= '$valkoodi',
							vero 				= 0,
							lukko 				= '',
							tosite 				= '$tositenro',
							laatija 			= '$kukarow[kuka]',
							laadittu			= now()";
				$result = mysql_query($query) or pupe_error($query);
			}
		}
	}

	// Kirjataan tarvittava taseen varastovienti
	if ($vienti != 'A' and $vienti != 'D' and $vienti != 'G' and $vienti != '') {

		if ($vienti == 'C' or $vienti == 'F' or $vienti == 'I' or $vienti == 'J' or $vienti == 'K' or $vienti == 'L') {
			$varastotili = $yhtiorow['matkalla_olevat'];
		}
		else {
			$varastotili = $yhtiorow['varasto'];
		}

		$query = "	INSERT INTO tiliointi SET
					yhtio 				= '$kukarow[yhtio]',
					ltunnus 			= '$tunnus',
					tilino 				= '$varastotili',
					kustp				= 0,
					tapvm 				= '$tpv-$tpk-$tpp',
					summa 				= $veroton,
					summa_valuutassa	= $veroton_valuutassa,
					valkoodi			= '$valkoodi',
					vero 				= 0,
					lukko 				= '',
					tosite 				= '$tositenro',
					laatija 			= '$kukarow[kuka]',
					laadittu 			= now()";
		$result = mysql_query($query) or pupe_error($query);
	}

	// Jos meill� on suoraveloitus
	if ($osuoraveloitus != '') {

		echo "<font class='message'>".t('Suoraveloitus');

		 //Toimittajalla on pankkitili, teemme er�p�iv�lle suorituksen valmiiksi
		if ($trow['oletus_suoravel_pankki'] > 0) {

			echo " ".t('oletuspankkitilille').".</font><br>";

			// Oletustili�innit
			// Ostovelat
			$query = "	INSERT INTO tiliointi SET
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$ostovelat',
						tapvm 				= '$erv-$erk-$erp',
						summa 				= '$omasumma',
						summa_valuutassa	= '$omasumma_valuutassa',
						valkoodi 			= '$valkoodi',
						vero 				= 0,
						lukko 				= '',
						tosite				= '$tositenro',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$xresult = mysql_query($query) or pupe_error($query);

			// Rahatili
			$query = "	INSERT INTO tiliointi SET
						yhtio 				= '$kukarow[yhtio]',
						ltunnus 			= '$tunnus',
						tilino 				= '$yhtiorow[selvittelytili]',
						tapvm 				= '$erv-$erk-$erp',
						summa 				= '$vassumma',
						summa_valuutassa	= '$vassumma_valuutassa',
						valkoodi 			= '$valkoodi',
						vero 				= 0,
						lukko 				= '',
						tosite 				= '$tositenro',
						laatija 			= '$kukarow[kuka]',
						laadittu 			= now()";
			$xresult = mysql_query($query) or pupe_error($query);

			if ($tila == 'M') {
				$query = "	UPDATE lasku set
							tila = 'Y',
							mapvm = '$erv-$erk-$erp',
							maksu_kurssi = 1
							WHERE tunnus = '$tunnus'";
				$xresult = mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t('Lasku merkittiin suoraan maksetuksi')."</font><br>";
			}
		}
		else {
		 	// T�m� on vain suoraveloitus
			if ($tila == 'M') {
				echo " ".t('ilman oletuspankkitili�').".</font><br>";
				$query = "UPDATE lasku set tila = 'Q' WHERE tunnus = '$tunnus'";
				$xresult = mysql_query($query) or pupe_error($query);
				echo "<font class='message'>".t('Lasku merkittiin odottamaan suoritusta')."</font><br>";
			}
		}
	}

	// Kopioidaan laskua tarvittaessa ollaan todella tyhmi� ja kopioidaan vaan surutta
	$kopioi = (int) $kopioi;

	if ($kopioi > 0) {
		//Etsit��n tehty lasku
		$query = "SELECT * FROM lasku WHERE tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 1) {
			echo "Kopioitava lasku katosi! Laskua ei kopioitu!<br>";
		}
		else {
			$laskurow = mysql_fetch_array ($result);

			$query = "SELECT * FROM tiliointi WHERE yhtio = '$kukarow[yhtio]' and ltunnus = '$tunnus' order by tunnus";
			$tilresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tilresult) == 0) {
				echo "Kopioitavat tili�innit katosi! Tili�intej� ei kopioitu!<br>";
			}
			for ($kopio=1; $kopio<=$kopioi; $kopio++) {
				$kopiotpv = $tpv;
				$kopiotpk = $tpk;
				$kopiotpp = $tpp;
				$kopioerv = $erv;
				$kopioerk = $erk;
				$kopioerp = $erp;
				$kopiokav = $kav;
				$kopiokak = $kak;
				$kopiokap = $kap;
				$query = "INSERT into lasku set ";

				for ($i=0; $i<mysql_num_fields($result); $i++) {

					if (mysql_field_name($result, $i) != 'tunnus') {

						if (mysql_field_name($result, $i) == 'tapvm') {
							$kopiotpk = $tpk + $kopio;

							if ($kopiotpk > 12) {
								$kopiotpk=1;
								$kopiotpv++;
							}

							if (!checkdate($kopiotpk, $kopiotpp, $kopiotpv)) {
								$kopiotpp = date("d",mktime(0, 0, 0, $kopiotpk + 1, 0, $kopiotpv));
							}
							$query .= "tapvm ='$kopiotpv-$kopiotpk-$kopiotpp',";
						}
						elseif (mysql_field_name($result, $i) == 'erpcm') {

							$kopioerk = $erk + $kopio;

							if ($kopioerk > 12) {
								$kopioerk=1;
								$kopioerv++;
							}

							if (!checkdate($kopioerk, $kopioerp, $kopioerv)) {
								$kopioerp = date("d",mktime(0, 0, 0, $kopioerk + 1, 0, $kopioerv));
							}

							$query .= "erpcm ='$kopioerv-$kopioerk-$kopioerp',";

							if ($laskurow['kapvm'] == '0000-00-00') {
								$query .= "olmapvm ='$kopioerv-$kopioerk-$kopioerp',";
							}
						}
						elseif (mysql_field_name($result, $i) == 'kapvm') {

							if ($laskurow['kapvm'] != '0000-00-00') {

								$kopiokak = $kak + $kopio;

								if ($kopiokak > 12) {
									$kopiokak=1;
									$kopiokav++;
								}

								if (!checkdate($kopiokak, $kopiokap, $kopiokav)) {
									$kopiokap = date("d",mktime(0, 0, 0, $kopiokak + 1, 0, $kopiokav));
								}

								$query .= "kapvm ='$kopiokav-$kopiokak-$kopiokap',";
								$query .= "olmapvm ='$kopiokav-$kopiokak-$kopiokap',";
							}
						}
						elseif (mysql_field_name($result, $i) == 'olmapvm') {
						}
						else {
							$query .= mysql_field_name($result,$i) . "='" . $laskurow[$i] . "',";
						}
					}
				}

				$query       = substr($query,0,-1);
				$insresult   = mysql_query($query) or pupe_error($query);
				$kopiotunnus = mysql_insert_id($link);

				//Kopioidaan tili�innit
				mysql_data_seek ($tilresult,0);

				while ($tiliointirow = mysql_fetch_array ($tilresult)) {

					$query = "INSERT into tiliointi set ";

					for ($i=0; $i<mysql_num_fields($tilresult); $i++) {

						if (mysql_field_name($tilresult, $i) != 'tunnus') {

							if (mysql_field_name($tilresult, $i) == 'tapvm') {
								$query .= "tapvm ='$kopiotpv-$kopiotpk-$kopiotpp',";
							}
							elseif (mysql_field_name($tilresult, $i) == 'ltunnus') {
								$query .= "ltunnus ='$kopiotunnus',";
							}
							elseif (mysql_field_name($tilresult, $i) == 'aputunnus') {
								if ($tiliointirow['aputunnus'] != 0) {
									$query .= "aputunnus ='$kopiotiltunnus',";
								}
							}
							else {
								$query .= mysql_field_name($tilresult,$i) . "='" . $tiliointirow[$i] . "',";
							}
						}
					}

					$query         = substr($query,0,-1);
					$insresult     = mysql_query($query) or pupe_error($query);
					$kopiotiltunnus= mysql_insert_id($link);
				}
				echo "<font class='message'>".t("Tehtiin kopio p�iv�lle")." $kopiotpv-$kopiotpk-$kopiotpp</font><br>";
			}
		}
	}

	$tee    = "";
	$selite = "";

	if ($kpexport == 1 or strtoupper($yhtiorow['maa']) != 'FI') {
		$query = "UNLOCK TABLES";
		$result = mysql_query($query) or pupe_error($query);
		echo "<font class='message'>".t("Lasku perustettiin asiakkalle")." $trow[nimi]<br>";
		echo t("Summa")." $yleissumma $valkoodi = $omasumma $yhtiorow[valkoodi]".t('Tositenro on')." $tositenro<br></font><hr>";
	}
	else {
		echo "<font class='message'>".t("Lasku perustettiin asiakkalle")." $trow[nimi]<br>";
		echo t("Summa")." $yleissumma $valkoodi = $omasumma $yhtiorow[valkoodi]<br></font><hr>";
	}

	// N�ytett��n k�ytt�liittym�
	$tee = '';

	//Luodaan uusi keikka jos k�ytt�j� ruksasi keikkaruksin
	if ($luouusikeikka == "LUO") {

		//Luodaan uusi keikka
		$aladellaa = "En haluu dellata!";

		require("inc/verkkolasku-in-luo-keikkafile.inc");

		echo "$laskuvirhe<br><br>";

		if ($autokohdistus == "AUTO") {
			//Tehd��n keikka ja varastoonvienti automaattisesti
			$query = "	UPDATE tilausrivi SET
						hinta = hinta * (1 - ale / 100) * (1 - $laskurow[erikoisale] / 100),
						uusiotunnus = '$keikantunnus',
						tyyppi = 'O',
						varattu = varattu * -1,
						tilkpl = tilkpl * -1
						WHERE yhtio = '$kukarow[yhtio]'
						and tunnus in ($ostorow[tunnukset])";
			$liittos = mysql_query($query) or pupe_error($query);

			// t�m�n keikan voi vied� saldoille...
			$otunnus = $keikantunnus;

			$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tunnus = '$otunnus'";
			$result = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			// vied��n varastoon...
			require ("tilauskasittely/varastoon.inc");

			$tee = "KALA";
		}
		else {
			//Sitten menn��n suoraan keikalle
			$otunnus = $keikantunnus;
			$tila = "";
			$tee = "";
			$PHP_SELF = "tilauskasittely/keikka.php";
			$alku = "";
			$toimittajaid = $trow["tunnus"];

			require("tilauskasittely/ostotilausten_rivien_kohdistus.inc");
			exit;
		}
	}
}

if (strlen($tee) == 0) {

	$formi  = 'viivat';
	$kentta = 'nimi';

	echo "<table>";

	echo "<tr><td><form name = 'viivat' action = '$PHP_SELF?tee=VIIVA' method='post'>".t("Perusta lasku viivakoodilukijalla")."</td>
		<input type='hidden' name='lopetus' value='$lopetus'>
		<td><input type = 'text' name = 'nimi' size='8'></td>
		<td>".t("tili�intirivej�").":</td>
		<td><select name='maara'><option value ='2'>1
		<option value ='4'>3
		<option value ='8'>7
		<option value ='16'>15
		<option value ='31'>30
		</select></td>
		<td><input type = 'submit' value = '".t("Perusta")."'></td></tr></form>";

	echo "<tr><td><form action = '$PHP_SELF?tee=Y' method='post'>".t("Perusta lasku toimittajan Y-tunnuksen/nimen perusteella")."</td>
		<input type='hidden' name='lopetus' value='$lopetus'>
		<td><input type = 'text' name = 'ytunnus' size='8' maxlength='15'></td>
		<td>".t("tili�intirivej�").":</td>
		<td><select name='maara'><option value ='2'>1
		<option value ='4'>3
		<option value ='8'>7
		<option value ='16'>15
		<option value ='31'>30
		</select></td>
		<td><input type = 'submit' value = '".t("Perusta")."'></td></tr></form>";

	echo "<td><form action = '$PHP_SELF?tee=P' method='post'>".t("Perusta lasku ilman toimittajatietoja")."</td>
		<input type='hidden' name='lopetus' value='$lopetus'>
		<td>
		<select name='tyyppi'>
		<option value =".strtoupper($yhtiorow['maa']).">".t("Kotimaa")."
		<option value ='nonfi'>".t("Ulkomaa")."
		</select></td>
		<td>".t("tili�intirivej�").":</td>
		<td><select name='maara'><option value ='2'>1
		<option value ='4'>3
		<option value ='8'>7
		<option value ='16'>15
		<option value ='31'>30
		</select></td>
		<td><input type = 'submit' value = '".t("Perusta")."'></td></tr></form>";

	if ($toimittajaid > 0) {
		$query = "	SELECT nimi
					FROM toimi
					WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$toimittajaid'";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {

			$row = mysql_fetch_array($result);

			echo "<td><form action = '$PHP_SELF?tee=Y' method='post'>".t("Perusta lasku toimittajalle")." $row[nimi]</td>
			<input type='hidden' name='lopetus' value='$lopetus'>
			<td><input type='hidden'  name='toimittajaid' value='$toimittajaid'></td>
			<td>".t("tili�intirivej�").":</td>
			<td><select name='maara'><option value ='2'>1
			<option value ='4'>3
			<option value ='8'>7
			<option value ='16'>15
			<option value ='31'>30
			</select></td>
			<td><input type = 'submit' value = '".t("Perusta")."'></td></tr></table></form>";
		}
	}
	else {
		echo "</table>";
	}
}

if (strpos($_SERVER['SCRIPT_NAME'], "ulask.php")  !== FALSE) {
	require ("inc/footer.inc");
}

?>
