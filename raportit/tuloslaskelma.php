<?php

	if (isset($_POST["teetiedosto"])) {
		if($_POST["teetiedosto"] == 'lataa_tiedosto') $lataa_tiedosto=1;
		if($_POST["kaunisnimi"] != '') $_POST["kaunisnimi"] = str_replace("/","",$_POST["kaunisnimi"]);
	}

	if (strpos($_SERVER['SCRIPT_NAME'], "tuloslaskelma.php")  !== FALSE) {
		require ("../inc/parametrit.inc");
	}
	else {
		if ($from != "PROJEKTIKALENTERI" or (int) $mul_proj[0] == 0) {
			die("<font class='error'>�l� edes yrit�!</font>");
		}
	}

	if (isset($teetiedosto)) {
		if ($teetiedosto == "lataa_tiedosto") {
			readfile("/tmp/".$tmpfilenimi);
			exit;
		}
	}
	else {
		// Muokataan tilikartan rakennetta
		if (isset($tasomuutos)) {
			require("../tasomuutos.inc");
			require ('../inc/footer.inc');
			exit;
		}

		if ($from != "PROJEKTIKALENTERI") {
			echo "<font class='head'>".t("Tase/tuloslaskelma")."</font><hr>";
		}

		if ($tltee == "aja") {
			if ($plvv * 12 + $plvk > $alvv * 12 + $alvk) {
				echo "<font class='error'>".t("Alkukausi on p��ttymiskauden j�lkeen")."</font><br>";
				$tltee = '';
			}
		}

		//	UI vain jos sille on tarvetta
		if ($from != "PROJEKTIKALENTERI") {
			// tehd��n k�ytt�liittym�, n�ytet��n aina
			$sel = array();
			if ($tyyppi == "") $tyyppi = "4";
			$sel[$tyyppi] = "SELECTED";

			echo "<br>";
			echo "	<form action = 'tuloslaskelma.php' method='post'>
					<input type = 'hidden' name = 'tltee' value = 'aja'>
					<input type='hidden' name='toim' value='$toim'>
					<table>";

			echo "	<tr>
					<th valign='top'>".t("Tyyppi")."</th>
					<td>";

			echo "	<select name = 'tyyppi'>
					<option $sel[4] value='4'>".t("Sis�inen tuloslaskelma")."</option>
					<option $sel[3] value='3'>".t("Ulkoinen tuloslaskelma")."</option>
					<option $sel[T] value='T'>".t("Tase")."</option>
					<option $sel[1] value='1'>".t("Vastaavaa")." (".t("Varat").")</option>
					<option $sel[2] value='2'>".t("Vastattavaa")." (".t("Velat").")</option>
					</select>";

			echo "</td>
					</tr>";


			if (!isset($plvv)) {
				$query = "	SELECT *
							FROM tilikaudet
							WHERE yhtio = '$kukarow[yhtio]'
							and tilikausi_alku <= now()
							and tilikausi_loppu >= now()";
				$result = mysql_query($query) or pupe_error($query);
				$tilikausirow = mysql_fetch_assoc($result);

				$plvv = substr($tilikausirow['tilikausi_alku'], 0, 4);
				$plvk = substr($tilikausirow['tilikausi_alku'], 5, 2);
				$plvp = substr($tilikausirow['tilikausi_alku'], 8, 2);
			}

			echo "	<th valign='top'>".t("Alkukausi")."</th>
					<td><select name='plvv'>";

			$sel = array();
			$sel[$plvv] = "SELECTED";

			for ($i = date("Y"); $i >= date("Y")-4; $i--) {
				echo "<option value='$i' $sel[$i]>$i</option>";
			}

			echo "</select>";

			$sel = array();
			$sel[$plvk] = "SELECTED";

			echo "<select name='plvk'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					</select>";

			$sel = array();
			$sel[$plvp] = "SELECTED";

			echo "<select name='plvp'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					<option $sel[13] value = '13'>13</option>
					<option $sel[14] value = '14'>14</option>
					<option $sel[15] value = '15'>15</option>
					<option $sel[16] value = '16'>16</option>
					<option $sel[17] value = '17'>17</option>
					<option $sel[18] value = '18'>18</option>
					<option $sel[19] value = '19'>19</option>
					<option $sel[20] value = '20'>20</option>
					<option $sel[21] value = '21'>21</option>
					<option $sel[22] value = '22'>22</option>
					<option $sel[23] value = '23'>23</option>
					<option $sel[24] value = '24'>24</option>
					<option $sel[25] value = '25'>25</option>
					<option $sel[26] value = '26'>26</option>
					<option $sel[27] value = '27'>27</option>
					<option $sel[28] value = '28'>28</option>
					<option $sel[29] value = '29'>29</option>
					<option $sel[30] value = '30'>30</option>
					<option $sel[31] value = '31'>31</option>
					</select>
					</td></tr>";

			echo "<tr>
				<th valign='top'>".t("Loppukausi")."</th>
				<td><select name='alvv'>";

			$sel = array();
			if ($alvv == "") $alvv = date("Y");
			$sel[$alvv] = "SELECTED";

			for ($i = date("Y")+1; $i >= date("Y")-4; $i--) {
				echo "<option value='$i' $sel[$i]>$i</option>";
			}

			$sel = array();
			if ($alvk == "") $alvk = date("m");
			$sel[$alvk] = "SELECTED";

			echo "</select>";

			echo "<select name='alvk'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					</select>";

			$sel = array();
			if ($alvp == "") $alvp = date("d", mktime(0, 0, 0, (date("m")+1), 0, date("Y")));
			$sel[$alvp] = "SELECTED";

			echo "<select name='alvp'>
					<option $sel[01] value = '01'>01</option>
					<option $sel[02] value = '02'>02</option>
					<option $sel[03] value = '03'>03</option>
					<option $sel[04] value = '04'>04</option>
					<option $sel[05] value = '05'>05</option>
					<option $sel[06] value = '06'>06</option>
					<option $sel[07] value = '07'>07</option>
					<option $sel[08] value = '08'>08</option>
					<option $sel[09] value = '09'>09</option>
					<option $sel[10] value = '10'>10</option>
					<option $sel[11] value = '11'>11</option>
					<option $sel[12] value = '12'>12</option>
					<option $sel[13] value = '13'>13</option>
					<option $sel[14] value = '14'>14</option>
					<option $sel[15] value = '15'>15</option>
					<option $sel[16] value = '16'>16</option>
					<option $sel[17] value = '17'>17</option>
					<option $sel[18] value = '18'>18</option>
					<option $sel[19] value = '19'>19</option>
					<option $sel[20] value = '20'>20</option>
					<option $sel[21] value = '21'>21</option>
					<option $sel[22] value = '22'>22</option>
					<option $sel[23] value = '23'>23</option>
					<option $sel[24] value = '24'>24</option>
					<option $sel[25] value = '25'>25</option>
					<option $sel[26] value = '26'>26</option>
					<option $sel[27] value = '27'>27</option>
					<option $sel[28] value = '28'>28</option>
					<option $sel[29] value = '29'>29</option>
					<option $sel[30] value = '30'>30</option>
					<option $sel[31] value = '31'>31</option>
					</select>
					</td></tr>";

			echo "<tr><th valign='top'>".t("tai koko tilikausi")."</th>";

			$query = "	SELECT *
						FROM tilikaudet
						WHERE yhtio = '$kukarow[yhtio]'
						ORDER BY tilikausi_alku DESC";
			$vresult = mysql_query($query) or pupe_error($query);

			echo "<td><select name='tkausi'><option value='0'>".t("Ei valintaa")."";

			while ($vrow=mysql_fetch_assoc($vresult)) {
				$sel="";
				if ($trow[$i] == $vrow["tunnus"]) {
					$sel = "selected";
				}
				echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"]);
			}
			echo "</select></td>";
			echo "</tr>";

			$sel = array();
			$sel[$rtaso] = "SELECTED";

			echo "<tr><th valign='top'>".t("Raportointitaso")."</th>
					<td><select name='rtaso'>";

			$query = "SELECT max(length(taso)) taso from taso where yhtio = '$kukarow[yhtio]'";
			$vresult = mysql_query($query) or pupe_error($query);
			$vrow = mysql_fetch_assoc($vresult);

			echo "<option value='TILI'>".t("Tili taso")."</option>\n";

			for ($i=$vrow["taso"]-1; $i >= 0; $i--) {
				echo "<option ".$sel[$i+2]." value='".($i+2)."'>".t("Taso %s",'',$i+1)."</option>\n";
			}

			echo "</select></td></tr>";

			$sel = array();
			if ($tarkkuus == "") $tarkkuus = 1;
			$sel[$tarkkuus] = "SELECTED";

			echo "<tr><th valign='top'>".t("Lukujen taarkkuus")."</th>
					<td><select name='tarkkuus'>
						<option $sel[1]    value='1'>".t("�l� jaa lukuja")."</option>
						<option $sel[1000] value='1000'>".t("Jaa 1000:lla")."</option>
						<option $sel[10000] value='10000'>".t("Jaa 10 000:lla")."</option>
						<option $sel[100000] value='100000'>".t("Jaa 100 000:lla")."</option>
						<option $sel[1000000] value='1000000'>".t("Jaa 1 000 000:lla")."</option>
						</select>";

			$sel = array();
			if ($desi == "") $desi = "0";
			$sel[$desi] = "SELECTED";

			echo "<select name='desi'>
					<option $sel[0] value='0'>0 ".t("desimaalia")."</option>
					<option $sel[1] value='1'>1 ".t("desimaalia")."</option>
					<option $sel[2] value='2'>2 ".t("desimaalia")."</option>
					</select></td></tr>";

			$kauchek = $vchek = $bchek = $ychek = "";
			if ($kaikkikaudet != "") $kauchek = "SELECTED";
			if ($vertailued != "")   $vchek = "CHECKED";
			if ($vertailubu != "")   $bchek = "CHECKED";
			if ($eiyhteensa != "")   $ychek = "CHECKED";

			echo "<tr><th valign='top'>".t("N�kym�")."</th>";

			echo "<td><select name='kaikkikaudet'>
					<option value=''>".t("N�yt� vain viimeisin kausi")."</option>
					<option value='o' $kauchek>".t("N�yt� kaikki kaudet")."</option>
					</select>
					<br>&nbsp;<input type='checkbox' name='eiyhteensa' $ychek> ".t("Ei yhteens�saraketta")."
					</td></tr>";

			echo "<tr><th valign='top'>".t("Vertailu")."</th>";
			echo "<td>";
			echo "&nbsp;<input type='checkbox' name='vertailued' $vchek> ".t("Edellinen vastaava");
			echo "<br>&nbsp;<input type='checkbox' name='vertailubu' $bchek> ".t("Budjetti");
			echo "</td></tr>";

			echo "<tr><th valign='top'>".t("Konsernirajaus")."</th>";

			$konsel = array();
			$konsel[$konsernirajaus] = "SELECTED";

			echo "<td><select name='konsernirajaus'>
					<option value=''>".t("N�ytet��n kaikki tili�innit")."</option>
					<option value='AT' $konsel[AT]>".t("N�ytet��n konserniasiakkaiden ja konsernitoimittajien tili�innit")."</option>
					<option value='T'  $konsel[T]>".t("N�ytet��n konsernitoimitajien tili�innit")."</option>
					<option value='A'  $konsel[A]>".t("N�ytet��n konserniasiakkaiden tili�innit")."</option>
					</select>
					</td></tr>";

			echo "<tr><th valign='top'>".t("Sarakkeet")."</th>";

			$bchek = array();

			if (is_array($sarakebox)) {
				foreach ($sarakebox as $sara => $sarav) {
					if ($sara != "") $bchek[$sara] = "CHECKED";
				}
			}

			echo "<td>";
			echo "&nbsp;<input type='checkbox' name='sarakebox[KUSTP]' $bchek[KUSTP]> ".t("Kustannuspaikoittain");
			echo "<br>&nbsp;<input type='checkbox' name='sarakebox[KOHDE]' $bchek[KOHDE]> ".t("Kohteittain");
			echo "<br>&nbsp;<input type='checkbox' name='sarakebox[PROJEKTI]' $bchek[PROJEKTI]> ".t("Projekteittain");
			echo "<br>&nbsp;<input type='checkbox' name='sarakebox[ASOSASTO]' $bchek[ASOSASTO]> ".t("Asiakasosastoittain");
			echo "<br>&nbsp;<input type='checkbox' name='sarakebox[ASRYHMA]' $bchek[ASRYHMA]> ".t("Asiakasryhmitt�in");
			echo "</td></tr>";

			echo "</table><br>";

			$monivalintalaatikot = array("KUSTP", "KOHDE", "PROJEKTI", "ASIAKASOSASTO", "ASIAKASRYHMA");
			$noautosubmit = TRUE;

			require ("tilauskasittely/monivalintalaatikot.inc");

			echo "<br><input type = 'submit' value = '".t("N�yt�")."'></form><br><br>";

		}

		if ($tltee == "aja") {

			// Desimaalit
			$muoto = "%.". (int) $desi . "f";

			if ($plvk == '' or $plvv == '') {
				$plvv = substr($yhtiorow['tilikausi_alku'], 0, 4);
				$plvk = substr($yhtiorow['tilikausi_alku'], 5, 2);
			}

			if ($tyyppi == "T") {
				// Vastaavaa Varat
				$otsikko 	= "Tase";
				$kirjain 	= "U";
				$aputyyppi 	= "1', BINARY '2";
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = 1;
			}
			elseif ($tyyppi == "1") {
				// Vastaavaa Varat
				$otsikko 	= "Vastaavaa Varat";
				$kirjain 	= "U";
				$aputyyppi 	= 1;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = 1;
			}
			elseif ($tyyppi == "2") {
				// Vastattavaa Velat
				$otsikko 	= "Vastattavaa Velat";
				$kirjain 	= "U";
				$aputyyppi 	= 2;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = 1;
			}
			elseif ($tyyppi == "3") {
				// Ulkoinen tuloslaskelma
				$otsikko 	= "Ulkoinen tuloslaskelma";
				$kirjain 	= "U";
				$aputyyppi 	= 3;
				$tilikarttataso = "ulkoinen_taso";
				$luku_kerroin = -1;
			}
			else {
				// Sis�inen tuloslaskelma
				$otsikko 	= "Sis�inen tuloslaskelma";
				$kirjain 	= "S";
				$aputyyppi 	= 3;
				$tilikarttataso = "sisainen_taso";
				$luku_kerroin = -1;
			}

			// edellinen taso
			$taso     			= array();
			$tasonimi 			= array();
			$summattavattasot	= array();
			$summa    			= array();
			$kaudet   			= array();
			$tilisumma			= array();

			if ((int) $tkausi > 0) {
				$query = "	SELECT tilikausi_alku, tilikausi_loppu
							FROM tilikaudet
							WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tkausi'";
				$result = mysql_query($query) or pupe_error($query);
				$tkrow = mysql_fetch_assoc($result);

				$plvv = substr($tkrow['tilikausi_alku'], 0, 4);
				$plvk = substr($tkrow['tilikausi_alku'], 5, 2);
				$plvp = substr($tkrow['tilikausi_alku'], 8, 2);

				$alvv = substr($tkrow['tilikausi_loppu'], 0, 4);
				$alvk = substr($tkrow['tilikausi_loppu'], 5, 2);
				$alvp = substr($tkrow['tilikausi_loppu'], 8, 2);
			}

			// Tarkistetaan viel� p�iv�m��r�t
			if (!checkdate($plvk, $plvp, $plvv)) {
				echo "<font class='error'>".t("VIRHE: Alkup�iv�m��r� on virheellinen")."!</font><br>";
				$tltee = "";
			}

			if (!checkdate($alvk, $alvp, $alvv)) {
				echo "<font class='error'>".t("VIRHE: Loppup�iv�m��r� on virheellinen")."!</font><br>";
				$tltee = "";
			}

			$laskujoini		= "";
			$asiakasjoini	= "";
			$konsernijoini	= "";
			$tilijoini		= "";
			$konsernilisa	= "";
			$bulisa			= "";
			$groupsarake	= "";

			$asiakasosastot  = "";
			$asiakasryhmat   = "";
			$kustannuspaikat = "";
			$kohteet 		 = "";
			$projektit 		 = "";

			// Ajetaan rapotti kustannuspaikoittain
			if (isset($sarakebox["KUSTP"]) and $sarakebox["KUSTP"] != "") {
				// Kun tehd��n monta saraketta niin ei joinata budjettiin
				$vertailubu = "";

				// N�it� tarvitaan kun piirret��n headerit
				$query = "	SELECT tunnus, concat_ws(' - ', if(koodi='', NULL, koodi), nimi) nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and kaytossa != 'E'
							and tyyppi = 'K'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$kustannuspaikat[$vrow["tunnus"]] = $vrow["nimi"];
				}

				$groupsarake .= "'kustannuspaikat::',tiliointi.kustp,'#!#',";
			}

			// Ajetaan rapotti kohteittain
			if (isset($sarakebox["KOHDE"]) and $sarakebox["KOHDE"] != "") {
				// Kun tehd��n monta saraketta niin ei joinata budjettiin
				$vertailubu = "";

				// N�it� tarvitaan kun piirret��n headerit
				$query = "	SELECT tunnus, concat_ws(' - ', if(koodi='', NULL, koodi), nimi) nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and kaytossa != 'E'
							and tyyppi = 'O'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$kohteet[$vrow["tunnus"]] = $vrow["nimi"];
				}

				$groupsarake .= "'kohteet::',tiliointi.kohde,'#!#',";
			}

			// Ajetaan rapotti projekteittain
			if (isset($sarakebox["PROJEKTI"]) and $sarakebox["PROJEKTI"] != "") {
				// Kun tehd��n monta saraketta niin ei joinata budjettiin
				$vertailubu = "";

				// N�it� tarvitaan kun piirret��n headerit
				$query = "	SELECT tunnus, concat_ws(' - ', if(koodi='', NULL, koodi), nimi) nimi
							FROM kustannuspaikka
							WHERE yhtio = '$kukarow[yhtio]'
							and kaytossa != 'E'
							and tyyppi = 'P'
							ORDER BY nimi";
				$vresult = mysql_query($query) or pupe_error($query);

				while ($vrow = mysql_fetch_assoc($vresult)) {
					$projektit[$vrow["tunnus"]] = $vrow["nimi"];
				}

				$groupsarake .= "'projektit::',tiliointi.projekti,'#!#',";
			}

			// Tarvitaan lasku/asiakasjoini jos rajataan tai ajetaan raportti asiakasosatoittain tai asiakasryhmitt�in
			if ((isset($lisa) and strpos($lisa, "asiakas.") !== FALSE) or (isset($sarakebox["ASOSASTO"]) and $sarakebox["ASOSASTO"] != "") or (isset($sarakebox["ASRYHMA"]) and $sarakebox["ASRYHMA"] != "")) {
				// Kun tehd��n asiakas tai toimittajajoini niin ei vertailla budjettiin koska siin� ei olisi mit��n j�rke�
				$vertailubu = "";

				$laskujoini = " JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus ";
				$asiakasjoini = " JOIN asiakas ON lasku.yhtio = asiakas.yhtio and lasku.liitostunnus = asiakas.tunnus ";

				if (isset($sarakebox["ASOSASTO"]) and $sarakebox["ASOSASTO"] != "") {
					// N�it� tarvitaan kun piirret��n headerit
					$vresult = t_avainsana("ASIAKASOSASTO");

					while ($vrow = mysql_fetch_assoc($vresult)) {
						$asiakasosastot[$vrow["selite"]] = $vrow["selitetark"];
					}

					$groupsarake .= "'asiakasosastot::',asiakas.osasto,'#!#',";
				}

				if (isset($sarakebox["ASRYHMA"]) and $sarakebox["ASRYHMA"] != "") {
					// N�it� tarvitaan kun piirret��n headerit
					$vresult = t_avainsana("ASIAKASRYHMA");

					while ($vrow = mysql_fetch_assoc($vresult)) {
						$asiakasryhmat[$vrow["selite"]] = $vrow["selitetark"];
					}

					$groupsarake .= "'asiakasryhmat::',asiakas.ryhma,'#!#',";
				}
			}

			if ($groupsarake != "") {
				$groupsarake = "concat(".substr($groupsarake, 0, -7).")";
			}
			else {
				$groupsarake = "tiliointi.yhtio";
			}

			// Tarvitaan lasku/asiakasjoini/toimittajajoini jos rajataan tai ajetaan vain konserniasiakkaista tai konsernitoimittajista
			if (isset($konsernirajaus) and $konsernirajaus != "") {
				// Kun tehd��n asiakas tai toimittajajoini niin ei vertailla budjettiin koska siin� ei olisi mit��n j�rke�
				$vertailubu = "";

				$laskujoini = " JOIN lasku ON tiliointi.yhtio = lasku.yhtio and tiliointi.ltunnus = lasku.tunnus ";

				if ($konsernirajaus == "AT") {
					$konsernijoini  = "	LEFT JOIN asiakas ka ON lasku.yhtio = ka.yhtio and lasku.liitostunnus = ka.tunnus and ka.konserniyhtio != ''
										LEFT JOIN toimi kt ON lasku.yhtio = kt.yhtio and lasku.liitostunnus = kt.tunnus and kt.konserniyhtio != '' ";
					$konsernilisa = " and (ka.tunnus is not null or kt.tunnus is not null) ";
				}
				elseif ($konsernirajaus == "T") {
					$konsernijoini = "  LEFT JOIN toimi kt ON lasku.yhtio = kt.yhtio and lasku.liitostunnus = kt.tunnus and kt.konserniyhtio != '' ";
					$konsernilisa = " and kt.tunnus is not null ";
				}
				elseif ($konsernirajaus == "A") {
					$konsernijoini = "  LEFT JOIN asiakas ka ON lasku.yhtio = ka.yhtio and lasku.liitostunnus = ka.tunnus and ka.konserniyhtio != '' ";
					$konsernilisa = " and ka.tunnus is not null ";
				}
			}
		}

		// Budjettitauluun sopiva rajaus
		if (isset($lisa) and $lisa != "" and $vertailubu != "") {
			// Rajataan budjettia
			$bulisa = str_replace("tiliointi.","budjetti.", $lisa);
		}

		if ($tltee == "aja") {

			// Tehd��nk� linkit p�iv�kirjaan
			$query = "	SELECT yhtio
						FROM oikeu
						WHERE yhtio	= '$kukarow[yhtio]'
						and kuka	= '$kukarow[kuka]'
						and nimi	= 'raportit.php'
						and alanimi = 'paakirja'";
			$oikresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($oikresult) > 0) {
				$paakirjalink = TRUE;
			}
			else {
				$paakirjalink = FALSE;
			}

			$lopelinkki = "&lopetus=$PHP_SELF////tltee=$tltee//toim=$toim//tyyppi=$tyyppi//plvv=$plvv//plvk=$plvk//plvp=$plvp//alvv=$alvv//alvk=$alvk//alvp=$alvp//tkausi=$tkausi//rtaso=$rtaso//tarkkuus=$tarkkuus//desi=$desi//kaikkikaudet=$kaikkikaudet//eiyhteensa=$eiyhteensa//vertailued=$vertailued//vertailubu=$vertailubu".str_replace("&","//",$ulisa);

			$startmonth	= date("Ymd",   mktime(0, 0, 0, $plvk, 1, $plvv));
			$endmonth 	= date("Ymd",   mktime(0, 0, 0, $alvk, 1, $alvv));

			$annettualk = $plvv."-".$plvk."-".$plvp;
			$totalloppu = $alvv."-".$alvk."-".$alvp;

			$budjettalk = date("Ym", mktime(0, 0, 0, $plvk, 1, $plvv));
			$budjettlop = date("Ym", mktime(0, 0, 0, $alvk+1, 0, $alvv));

			if ($vertailued != "") {
				$totalalku  = ($plvv-1)."-".$plvk."-".$plvp;
				$totalloppued = ($alvv-1)."-".$alvk."-".$alvp;
			}
			else {
				$totalalku = $plvv."-".$plvk."-".$plvp;
			}

			$alkuquery1 = "";
			$alkuquery2 = "";

			for ($i = $startmonth;  $i <= $endmonth;) {

				if ($i == $startmonth) $alku = $plvv."-".$plvk."-".$plvp;
				else $alku = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)));

				if ($i == $endmonth) $loppu = $alvv."-".$alvk."-".$alvp;
				else $loppu = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2)+1, 0, substr($i,0,4)));

				$bukausi = date("Ym",    mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)));
				$headny  = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2),  substr($i,0,4)));

				if ($alkuquery1 != "") $alkuquery1 .= " ,";
				if ($alkuquery2 != "") $alkuquery2 .= " ,";

				$alkuquery1 .= "sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";
				$alkuquery2 .= "sum(if(tiliointi.tapvm >= '$alku' and tiliointi.tapvm <= '$loppu', tiliointi.summa, 0)) '$headny'\n";

				$kaudet[] = $headny;

				if ($vertailued != "") {

					if ($i == $startmonth) $alku_ed = ($plvv-1)."-".$plvk."-".$plvp;
					else $alku_ed  = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)-1));

					if ($i == $endmonth) $loppu_ed = ($alvv-1)."-".$alvk."-".$alvp;
					else $loppu_ed = date("Y-m-d", mktime(0, 0, 0, substr($i,4,2)+1, 0, substr($i,0,4)-1));

					$headed   = date("Y/m",   mktime(0, 0, 0, substr($i,4,2), substr($i,6,2), substr($i,0,4)-1));

					$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";
					$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$alku_ed' and tiliointi.tapvm <= '$loppu_ed', tiliointi.summa, 0)) '$headed'\n";

					$kaudet[] = $headed;
				}

				// sis�isess� tuloslaskelmassa voidaan joinata budjetti
				if ($vertailubu != "" and $kirjain == "S") {
					$alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi = '$bukausi' $bulisa) 'budj $headny'\n";
					$alkuquery2 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi = '$bukausi' $bulisa) 'budj $headny'\n";

					$kaudet[] = "budj $headny";
				}

				$i = date("Ymd",mktime(0, 0, 0, substr($i,4,2)+1, 1, substr($i,0,4)));
			}

			$vka = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv));
			$vkl = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv));

			$vkaed = date("Y/m", mktime(0, 0, 0, $plvk, 1, $plvv-1));
			$vkled = date("Y/m", mktime(0, 0, 0, $alvk+1, 0, $alvv-1));

			// Yhteens�otsikkomukaan
			if ($eiyhteensa == "") {
				$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
				$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$annettualk' and tiliointi.tapvm <= '$totalloppu', tiliointi.summa, 0)) '$vka - $vkl' \n";
				$kaudet[] = $vka." - ".$vkl;

				if ($vertailued != "") {

					$alkuquery1 .= " ,sum(if(tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";
					$alkuquery2 .= " ,sum(if(tiliointi.tapvm >= '$totalalku' and tiliointi.tapvm <= '$totalloppued', tiliointi.summa, 0)) '$vkaed - $vkled' \n";

					$kaudet[] = $vkaed." - ".$vkled;
				}

				if ($vertailubu != "" and $kirjain == "S") {
					$alkuquery1 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.$tilikarttataso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $bulisa) 'budj $vka - $vkl' \n";
					$alkuquery2 .= " ,(SELECT sum(budjetti.summa) FROM budjetti USE INDEX (yhtio_taso_kausi) WHERE budjetti.yhtio = tili.yhtio and BINARY budjetti.taso = BINARY tili.taso and budjetti.kausi >= '$budjettalk' and budjetti.kausi <= '$budjettlop' $bulisa) 'budj $vka - $vkl' \n";
					$kaudet[] = "budj ".$vka." - ".$vkl;
				}
			}

			if ($vertailubu != "") {
				$tilijoini = "JOIN tili ON tiliointi.yhtio=tili.yhtio and tiliointi.tilino=tili.tilino";
			}

			// Haetaan kaikki tili�innit
			$query = "	SELECT tiliointi.tilino, $groupsarake groupsarake, $alkuquery1
		 	            FROM tiliointi USE INDEX (yhtio_tilino_tapvm)
			            $laskujoini
			            $asiakasjoini
			            $konsernijoini
						$tilijoini
			            WHERE tiliointi.yhtio = '$kukarow[yhtio]'
			            and tiliointi.korjattu = ''
			            and tiliointi.tapvm >= '$totalalku'
			            and tiliointi.tapvm <= '$totalloppu'
			            $konsernilisa
			            $lisa
			            GROUP BY tiliointi.tilino, groupsarake
						ORDER BY tiliointi.tilino, groupsarake";
			$tilires = mysql_query($query) or pupe_error($query);

			$tilioinnit = array();
			$sarakkeet  = array();


			while ($tilirow = mysql_fetch_assoc($tilires)) {

				if (!isset($firstgroup)) $firstgroup = (string) $tilirow["groupsarake"];

				// Otetaan kaikki distinct sarakkeet
				$sarakkeet[(string) $tilirow["groupsarake"]] = (string) $tilirow["groupsarake"];

				$tilioinnit[(string) $tilirow["tilino"]][(string) $tilirow["groupsarake"]] = $tilirow;
			}

			//Haetaan tulos jos ajetaan taselaskelma
			if ($tyyppi == "T" or $tyyppi == "2") {

				$tulokset = array();

				// Haetaan firman tulos
				$query = "	SELECT $groupsarake groupsarake, $alkuquery1
			 	            FROM tiliointi USE INDEX (yhtio_tilino_tapvm)
				            $laskujoini
				            $asiakasjoini
				            $konsernijoini
							JOIN tili ON tiliointi.yhtio=tili.yhtio and tiliointi.tilino=tili.tilino and LEFT(tili.ulkoinen_taso, 1) = BINARY '3'
				            WHERE tiliointi.yhtio = '$kukarow[yhtio]'
				            and tiliointi.korjattu = ''
				            and tiliointi.tapvm >= '$totalalku'
				            and tiliointi.tapvm <= '$totalloppu'
				            $konsernilisa
				            $lisa
				            GROUP BY groupsarake
							ORDER BY groupsarake";
				$tulosres = mysql_query($query) or pupe_error($query);

				while ($tulosrow = mysql_fetch_assoc($tulosres)) {
					$tulokset[(string) $tulosrow["groupsarake"]] = $tulosrow;
				}
			}

			// Haetaan kaikki budjetit
			$query = "	SELECT budjetti.taso, budjetti.yhtio groupsarake, $alkuquery2
					 	FROM budjetti
						JOIN budjetti tili ON (tili.yhtio = budjetti.yhtio and tili.tunnus = budjetti.tunnus)
						LEFT JOIN tiliointi USE INDEX (PRIMARY) ON (tiliointi.tunnus = 0)
						WHERE budjetti.yhtio = '$kukarow[yhtio]'
						$bulisa
						GROUP BY budjetti.taso, groupsarake
						ORDER BY budjetti.taso, groupsarake";
			$tilires = mysql_query($query) or pupe_error($query);

			$budjetit = array();

			while ($tilirow = mysql_fetch_assoc($tilires)) {
				$budjetit[(string) $tilirow["taso"]][(string) $tilirow["groupsarake"]] = $tilirow;
			}

			// Haetaan kaikki tasot ja rakennetaan tuloslaskelma-array
			$query = "	SELECT *
						FROM taso
						WHERE yhtio = '$kukarow[yhtio]'
						and tyyppi 	= '$kirjain'
						and LEFT(taso, 1) in (BINARY '$aputyyppi')
						and taso != ''
						ORDER BY taso";
			$tasores = mysql_query($query) or pupe_error($query);

			while ($tasorow = mysql_fetch_assoc($tasores)) {

				// mill� tasolla ollaan (1,2,3,4,5,6)
				$tasoluku = strlen($tasorow["taso"]);

				// tasonimi talteen (rightp�dd�t��n �:ll�, niin saadaan oikeaan j�rjestykseen)
				$apusort = str_pad($tasorow["taso"], 20, "�");
				$tasonimi[$apusort] = $tasorow["nimi"];

				if ($toim == "TASOMUUTOS") {
					$summattavattasot[$apusort] = $tasorow["summattava_taso"];
				}

				// pilkotaan taso osiin
				$taso = array();
				for ($i = 0; $i < $tasoluku; $i++) {
					$taso[$i] = substr($tasorow["taso"], 0, $i+1);
				}

				$query = "	SELECT tilino, nimi, tunnus
						 	FROM tili
							WHERE yhtio = '$kukarow[yhtio]'
							and $tilikarttataso = BINARY '$tasorow[taso]'";
				$tilires = mysql_query($query) or pupe_error($query);

				while ($tilirow = mysql_fetch_assoc($tilires)) {

					$tilirow_summat = array();

					//Onko t�m� yhti�n tulostili?
					if (($tyyppi == "T" or $tyyppi == "2") and ($tilirow["tunnus"] == $yhtiorow["tilikauden_tulos"])) {
						$tilirow_summat = $tulokset;
					}
					elseif (isset($tilioinnit[(string) $tilirow["tilino"]])) {
						$tilirow_summat = $tilioinnit[(string) $tilirow["tilino"]];
					}
					elseif (isset($budjetit[(string) $tasorow["taso"]])) {
						$tilirow_summat = $budjetit[(string) $tasorow["taso"]];
					}
					elseif ($toim == "TASOMUUTOS") {
						$tilirow_summat = array("$firstgroup" => 0);
					}

					foreach ($tilirow_summat as $sarake => $tilirow_sum) {
						// summataan kausien saldot
						foreach ($kaudet as $kausi) {
							if (substr($kausi,0,4) == "budj") {
								$i = $tasoluku - 1;

								$summa[$kausi][$taso[$i]][(string) $sarake] = $tilirow_sum[$kausi];
							}
							else {
								// Summataan kaikkia pienempi� summaustasoja
								for ($i = $tasoluku - 1; $i >= 0; $i--) {
									// Summat per kausi/taso
									$summa[$kausi][$taso[$i]][(string) $sarake] += $tilirow_sum[$kausi];
								}

								// Summat per taso/tili/kausi
								$i = $tasoluku - 1;
								$summakey = $tilirow["tilino"]."###".$tilirow["nimi"];

								$tilisumma[$taso[$i]][$summakey][$kausi][(string) $sarake] += $tilirow_sum[$kausi];
							}
						}
					}
				}
			}

			// Haluaako k�ytt�j� n�h� kaikki kaudet
			if ($kaikkikaudet == "") {
				$alkukausi = count($kaudet)-2;

				if ($eiyhteensa == "") {
					if ($vertailued != "") $alkukausi -= 2;
					if ($vertailubu != "") $alkukausi -= 2;
				}
				else {
					if ($vertailued != "" and $vertailubu != "") $alkukausi -= 1;
					if ($vertailued == "" and $vertailubu == "") $alkukausi += 1;
				}
			}
			else {
				$alkukausi = 0;
			}

			require_once('pdflib/phppdflib.class.php');

			$pdf = new pdffile;
			$pdf->set_default('margin', 0);
			$pdf->set_default('margin-left', 5);
			$rectparam["width"] = 0.3;

			$p["height"] 	= 10;
			$p["font"]	 	= "Times-Roman";
	        $b["height"]	= 8;
			$b["font"] 		= "Times-Bold";

			if (count($kaudet) > 10 and $kaikkikaudet != "") {
				$p["height"]--;
				$b["height"]--;

				$saraklev 			= 49;
				$yhteensasaraklev 	= 66;
				$rivikork 			= 13;
			}
			else {
				$saraklev 			= 60;
				$yhteensasaraklev 	= 70;
				$rivikork 			= 15;
			}

			if ((count($kaudet) > 5 and $kaikkikaudet != "") or count($sarakkeet) > 2) {
				$vaslev = 802;
			}
			else {
				$vaslev = 545;
			}

			for ($i = $alkukausi; $i < count($kaudet); $i++) {
				foreach ($sarakkeet as $sarake) {
					$vaslev -= $saraklev;
				}
			}

			if ($vaslev > 300) {
				$vaslev = 300;
			}

			if (!function_exists("alku")) {
				function alku () {
					global $yhtiorow, $kukarow, $firstpage, $pdf, $bottom, $kaudet, $kaikkikaudet, $saraklev, $rivikork, $p, $b, $otsikko, $alkukausi, $yhteensasaraklev, $vaslev, $sarakkeet;

					if ((count($kaudet) > 5 and $kaikkikaudet != "") or count($sarakkeet) > 2) {
						$firstpage = $pdf->new_page("842x595");
						$bottom = "535";
					}
					else {
						$firstpage = $pdf->new_page("a4");
						$bottom = "782";
					}

					unset($data);

					if ((int) $yhtiorow["lasku_logo"] > 0) {
						$liite = hae_liite($yhtiorow["lasku_logo"], "Yllapito", "array");
						$data = $liite["data"];
						$isizelogo[0] = $liite["image_width"];
						$isizelogo[1] = $liite["image_height"];
						unset($liite);
					}
					elseif (file_exists($yhtiorow["lasku_logo"])) {
						$filename = $yhtiorow["lasku_logo"];

						$fh = fopen($filename, "r");
						$data = fread($fh, filesize($filename));
						fclose($fh);

						$isizelogo = getimagesize($yhtiorow["lasku_logo"]);
					}

					if ($data) {
						$image = $pdf->jfif_embed($data);

						if (!$image) {
							echo t("Logokuvavirhe");
						}
						else {
							tulosta_logo_pdf($pdf, $firstpage, "", 0, 0, 25, 120);
						}
					}
					else {
						$pdf->draw_text(10, ($bottom+30),  $yhtiorow["nimi"], $firstpage);
					}

					$pdf->draw_text(200,  ($bottom+30), $otsikko, $firstpage);

					$left = $vaslev;

					for ($i = $alkukausi; $i < count($kaudet); $i++) {
						foreach ($sarakkeet as $sarake) {
							list($muuarray, $arvo) = explode("::", $sarake);
							$sarakenimi = ${$muuarray}[$arvo];

							$oikpos1 = $pdf->strlen($kaudet[$i], $b);
							$oikpos2 = $pdf->strlen($sarakenimi, $b);

							if ($oikpos2 > $oikpos1) {
								$oikpos = $oikpos2;
							}
							else {
								$oikpos = $oikpos1;
							}

							if ($i+1 == count($kaudet) and $eiyhteensa == "") {
								$lev = $yhteensasaraklev;
							}
							else {
								$lev = $saraklev;
							}

							$pdf->draw_text($left-$oikpos+$lev,  $bottom+8, $sarakenimi, $firstpage, $b);
							$pdf->draw_text($left-$oikpos+$lev,  $bottom, $kaudet[$i], $firstpage, $b);

							$left += $saraklev;
						}
					}

					$bottom -= $rivikork;
				}
			}

			alku();

			echo "<table>";

			// printataan headerit
			echo "<tr>";

			if ($toim == "TASOMUUTOS") {

				echo "	<form method='post'>
						<input type = 'hidden' name = 'tasomuutos' value = 'TRUE'>
						<input type = 'hidden' name = 'tee' value = 'tilitaso'>
						<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
						<input type = 'hidden' name = 'taso' value = '$aputyyppi'>";

				$lopetus =  $palvelin2."raportit/tuloslaskelma.php////";

				foreach ($_REQUEST as $key => $value) {
					$lopetus .= $key."=".$value."//";
				}

				echo "<input type = 'hidden' name = 'lopetus' value = '$lopetus'>";

				echo "<td class='back' colspan='3'></td>";
			}
			else {
				echo "<td class='back' colspan='1'></td>";
			}


			for ($i = $alkukausi; $i < count($kaudet); $i++) {
				foreach ($sarakkeet as $sarake) {
					list($muuarray, $arvo) = explode("::", $sarake);
					$sarakenimi = ${$muuarray}[$arvo];

					echo "<td class='tumma' align='right' valign='bottom'>$sarakenimi<br>$kaudet[$i]</td>";
				}
			}
			echo "</tr>\n";

			// sortataan array indexin (tason) mukaan
			ksort($tasonimi);

			// loopataan tasot l�pi
			foreach ($tasonimi as $key_c => $value) {

				$key = str_replace("�", "", $key_c); // �-kirjaimet pois

				// tulostaan rivi vain jos se kuuluu rajaukseen
				if (strlen($key) <= $rtaso or $rtaso == "TILI") {

					if ($bottom < 20) {
						alku();
					}

					$class = "";

					// laitetaan ykk�s ja kakkostason rivit tummalla selkeyden vuoksi
					if (strlen($key) < 3 and $rtaso > 2) $class = "tumma";

					$rivi  = "<tr class='aktiivi'>";

					if ($toim == "TASOMUUTOS") {
						$rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&tee=muuta&lopetus=$lopetus'>$key</a></td>";
						$rivi .= "<td class='back' nowrap><a href='?tasomuutos=TRUE&taso=$key&kirjain=$kirjain&edtaso=$edkey&tee=lisaa&lopetus=$lopetus'>".t("Lis�� taso tasoon")." $key</a></td>";
					}

					$tilirivi = "";

					if ($rtaso == "TILI") {

						$class = "tumma";

						foreach ($tilisumma[$key] as $tilitiedot => $tilisumkau) {
							$tilirivi2	= "";
							$tulos 		= 0;

							for ($i = $alkukausi; $i < count($kaudet); $i++) {
								foreach ($sarakkeet as $sarake) {
									$apu = sprintf($muoto, $tilisumkau[$kaudet[$i]][(string) $sarake] * $luku_kerroin / $tarkkuus);
									if ($apu == 0) $apu = "";

									$tilirivi2 .= "<td align='right' nowrap>".number_format($apu, $desi, ',', ' ')."</td>";

									if ($tilisumkau[$kaudet[$i]][(string) $sarake] != 0) {
										$tulos++;
									}
								}
							}

							if ($tulos > 0 or $toim == "TASOMUUTOS") {

								list($tnumero, $tnimi) = explode("###", $tilitiedot);

								$tilirivi .= "<tr>";

								if ($toim == "TASOMUUTOS") {
									$tilirivi .= "<td class='back' nowrap>$key</td>";
									$tilirivi .= "<td class='back' nowrap><input type='checkbox' name='tiliarray[]' value=\"'$tnumero'\"></td>";
								}
								$tilirivi .= "<td nowrap>";

								if ($paakirjalink) {
									$tilirivi .= "<a href ='../raportit.php?toim=paakirja&tee=P&mista=tuloslaskelma&alvv=$alvv&alvk=$alvk&tili=$tnumero$ulisa$lopelinkki'>$tnumero - $tnimi</a>";
								}
								else {
									$tilirivi .= "$tnumero - $tnimi";
								}

								$tilirivi .= "</td>$tilirivi2</tr>";
							}
						}
					}

					$rivi .= "<th nowrap>$value</th>";

					$tulos = 0;

					for ($i = $alkukausi; $i < count($kaudet); $i++) {
						foreach ($sarakkeet as $sarake) {
							$query = "	SELECT summattava_taso
										FROM taso
										WHERE yhtio 		 = '$kukarow[yhtio]'
										and taso 			 = BINARY '$key'
										and summattava_taso != ''
										and tyyppi 			 = '$kirjain'";
							$summares = mysql_query($query) or pupe_error($query);

							// Budjettia ei summata
							if ($summarow = mysql_fetch_assoc($summares) and substr($kaudet[$i],0,4) != "budj") {
								foreach(explode(",", $summarow["summattava_taso"]) as $staso) {
									$summa[$kaudet[$i]][$key][(string) $sarake] = $summa[$kaudet[$i]][$key][(string) $sarake] + $summa[$kaudet[$i]][$staso][(string) $sarake];
								}
							}

							// formatoidaan luku toivottuun muotoon
							$apu = sprintf($muoto, $summa[$kaudet[$i]][$key][(string) $sarake] * $luku_kerroin / $tarkkuus);

							if ($apu == 0) {
								$apu = ""; // nollat spaseiks
							}
							else {
								$tulos++; // summaillaan t�t� jos meill� oli rivill� arvo niin osataan tulostaa
							}

							$rivi .= "<td class='$class' align='right' nowrap>".number_format($apu, $desi,  ',', ' ')."</td>";
						}
					}

					if ($toim == "TASOMUUTOS" and $summattavattasot[$key_c] != "") {
						$rivi .= "<td class='back' nowrap>".t("Summattava taso").": ".$summattavattasot[$key_c]."</td>";
					}

					$rivi .= "</tr>\n";

					// kakkostason j�lkeen aina yks tyhj� rivi.. paitsi jos otetaan vain kakkostason raportti
					if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
						$rivi .= "<tr><td class='back'>&nbsp;</td></tr>";
					}

					if (strlen($key) == 1 and ($rtaso > 1 or $rtaso == "TILI")) {
						$rivi .= "<tr><td class='back'><br><br></td></tr>";
					}

					// jos jollain kaudella oli summa != 0 niin tulostetaan rivi
					if ($tulos > 0 or $toim == "TASOMUUTOS") {

						echo $tilirivi, $rivi;

						$left = 10+(strlen($key)-1)*3;

						list($ff_string, $ff_font) = pdf_fontfit($value, $vaslev-$left, $pdf, $b);

						$pdf->draw_text($left, $bottom, $ff_string, $firstpage, $ff_font);

						$left = $vaslev;

						for ($i = $alkukausi; $i < count($kaudet); $i++) {
							foreach ($sarakkeet as $sarake) {
								$oikpos = $pdf->strlen(number_format($summa[$kaudet[$i]][$key][(string) $sarake] * $luku_kerroin / $tarkkuus, $desi, ',', ' '), $p);

								if ($i+1 == count($kaudet) and $eiyhteensa == "") {
									$lev = $yhteensasaraklev;
								}
								else {
									$lev = $saraklev;
								}

								$pdf->draw_text($left-$oikpos+$lev, $bottom, number_format($summa[$kaudet[$i]][$key][(string) $sarake] * $luku_kerroin / $tarkkuus, $desi, ',', ' '), $firstpage, $p);
								$left += $saraklev;
							}
						}

						$bottom -= $rivikork;

						if (strlen($key) == 2 and ($rtaso > 2 or $rtaso == "TILI")) {
							$bottom -= $rivikork;
						}
					}
				}

				$edkey = $key;
			}

			echo "</table>";

			//	Projektikalenterilla ei sallita PDF tulostusta
			if ($from != "PROJEKTIKALENTERI") {

				if ($toim == "TASOMUUTOS") {
					echo "<br><input type='submit' value='".t("Anna tileille taso")."'></form><br><br>";
				}

				//keksit��n uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "Tuloslaskelma-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen("/tmp/".$pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF Error $pdffilenimi");
				fclose($fh);

				echo "<br><table>";
				echo "<tr><th>".t("Tallenna pdf").":</th>";
				echo "<form method='post' action='$PHP_SELF'>";
				echo "<input type='hidden' name='toim' value='$toim'>";
				echo "<input type='hidden' name='teetiedosto' value='lataa_tiedosto'>";
				echo "<input type='hidden' name='kaunisnimi' value='".urlencode($otsikko).".pdf'>";
				echo "<input type='hidden' name='tmpfilenimi' value='$pdffilenimi'>";
				echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
				echo "</table><br>";
			}
		}

		require("../inc/footer.inc");
	}
?>