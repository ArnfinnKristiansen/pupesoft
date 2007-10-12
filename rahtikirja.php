<?php
	require ("inc/parametrit.inc");

	if (strpos($toim,'_') !== false) {
		$toim = substr($toim,0,strpos($toim,'_'));
		$tila = 'G';
	}
	else {
		$tila = 'L';
	}

	if ($toimtila != '') {
		$tila = $toimtila;
	}

	if ((int) $otsikkonro > 0 or (int) $id > 0) {
		if ((int) $otsikkonro > 0) {
			$hakutunnus	= $otsikkonro;
		}
		else {
			$hakutunnus	= $id;
		}

		$query = "SELECT tila FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus='$hakutunnus' LIMIT 1";
		$result = mysql_query($query) or pupe_error($query);
		$row = mysql_fetch_array($result);

		$tila = $row["tila"];
	}

	if ($id == '') $id=0;

	// jos ollaan rahtikirjan esisy�t�ss� niin tehd��n lis�ys v�h�n helpommin
	if ($rahtikirjan_esisyotto != "" and $tee == "add" and $yhtiorow["rahtikirjojen_esisyotto"] == "M") {

		// esisy�tt� sallittu vain N tilassa oleville tilauksille
		$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro' and tila='N'";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) == 0) {
			echo "<br><br><font class='error'>".t("Esisy�tt� sallittu vain kesken oleville myyntitilauksille")."! </font><br>";
			exit;
		}

		$tutkimus = 0;

		// dellataan kaikki rahtikirjat t�ll� otsikolla
		$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro'";
		$result = mysql_query($query) or pupe_error($query);

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$kilot[$i]		= str_replace(',', '.', $kilot[$i]);
				$kollit[$i]	 	= str_replace(',', '.', $kollit[$i]);
				$kuutiot[$i]	= str_replace(',', '.', $kuutiot[$i]);
				$lavametri[$i]	= str_replace(',', '.', $lavametri[$i]);

				// lis�t��n rahtikirjatiedot (laitetaan poikkeava kentt��n -9 niin tiedet��n ett� esisy�tetty)
				$query  = "insert into rahtikirjat
							(poikkeava,rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,viesti,yhtio) values
							('-9','$otsikkonro','$kilot[$i]','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','$merahti','$otsikkonro','$pakkaus[$i]','$rahtisopimus','$toimitustapa','$tulostuspaikka','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','$viesti','$kukarow[yhtio]')";
				$result = mysql_query($query) or pupe_error($query);
				$tutkimus++;
			}
		}

		if ($tutkimus > 0) {
			// rullataan l�pi ja menn��n myyntiin
			$tee  = "";
			$toim = "";
			$id   = 0;
			// karsee h�kki mutta pit�� sanoa, ett� from on laskutatilaus niin p��st��n takasin muokkaukseen
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tilauskasittely/tilaus_myynti.php?toim=$rahtikirjan_esisyotto&aktivoinnista=true&from=LASKUTATILAUS'>";
		}
	}

	//lis�t��n sy�tetty kama rahtikirja-tauluun
	if ($tee == 'add') {
		$apu=0; //apumuuttuja
		$tutkimus = 0; // t�nne tulee luku

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$tutkimus++;
			}
		}

		// jos ollaan muokkaamassa rivej� poistetaan eka vanhat rahtikirjatiedot..
		if ($tutkimus > 0) {

			if ($muutos == 'yes') {
				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro' and rahtikirjanro='$rakirno'";
				$result = mysql_query($query) or pupe_error($query);

				// merkataan tilaus takaisin ker�tyksi, paitsi jos se on vientitilaus jolle vientitiedot on sy�tetty
				$query = "update lasku set alatila='C' where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro' and alatila!='E'";
				$result = mysql_query($query) or pupe_error($query);

				//Voi k�yd� niin, ett� rahtikirja on jo tulostunut. Poistetaan mahdolliset tulostusflagit
				$query = "	update tilausrivi set toimitettu = '', toimitettuaika=''
							where otunnus = '$otsikkonro' and yhtio = '$kukarow[yhtio]' and var not in ('P','J') and tyyppi='$tila'";
				$result  = mysql_query($query) or pupe_error($query);
				
				//	Poistetaan kaikki lavaeloitukset
				$query = "	select group_concat(distinct(concat('\'', selitetark_2, '\''))) veloitukset
							from avainsana
							join tuote on tuote.yhtio=avainsana.yhtio and tuote.tuoteno=avainsana.selitetark_2
							WHERE avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji='pakkaus'";
				$pakres = mysql_query($query) or pupe_error($query);
				$pakrow = mysql_fetch_array($pakres);
				if($pakrow["veloitukset"]!="") {
					$query = "delete from tilausrivi where yhtio='{$kukarow["yhtio"]}' and otunnus='$otsikkonro' and tuoteno IN ({$pakrow["veloitukset"]})";
					$delres = mysql_query($query) or pupe_error($query);
				}
			}

			if ($tila == 'L') {
				$alatilassa = " and lasku.alatila in ('C','E') ";
				$joinmaksuehto = " JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus ";
			}
			else {
				$alatilassa = " and lasku.alatila = 'C' ";
			}
			
			//	Jostain vuotaa muuttuja ja joudutaan ikuiseen looppiin. T�m� n�ytt�� toimivan
			if(!function_exists("lisaarivi")) {
				function lisaarivi ($otunnus, $tuoteno, $kpl, $hinta = "") {
					global $kukarow, $yhtiorow;

					$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otunnus'";
					$rhire = mysql_query($query) or pupe_error($query);
					$laskurow = mysql_fetch_array($rhire);

					$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tuoteno'";
					$rhire = mysql_query($query) or pupe_error($query);
					$trow  = mysql_fetch_array($rhire);

					$varataan_saldoa 	= "EI";
					$kukarow["kesken"]	= $otunnus;
					$korvaavakielto 	= "ON";
					$toimaika			= $laskurow["toimaika"];
					$kerayspvm			= $laskurow["kerayspvm"];
					
					require("tilauskasittely/lisaarivi.inc");
					
					//	Merkataan t�m� rivi ker�tyksi ja toimitetuksi..
					$query = "	update tilausrivi set 
								kerattyaika	= now(),
								keratty		= '{$kukarow["kuka"]}'
								where yhtio = '$kukarow[yhtio]' and tunnus='{$lisatyt_rivit1[0]}'";
					$updres = mysql_query($query) or pupe_error($query);										
				}
			}

			// saadaanko n�ille tilauksille sy�tt�� rahtikirjoja
			$query = "	SELECT
						lasku.yhtio,
						rahtikirjat.otsikkonro,
						rahtikirjat.poikkeava,
						toimitustapa.nouto,
						lasku.vienti
						FROM lasku use index (tila_index)
						JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != ''
						$joinmaksuehto
						LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
						LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = '$tila'
						$alatilassa
						and lasku. tunnus in ($tunnukset)
						HAVING (rahtikirjat.otsikkonro is null or rahtikirjat.poikkeava = -9) and ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')";
			$tilre = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tilre) == 0) {
				echo "<br><br><font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font><br>";
				exit;
			}

			echo "<font class='head'>".t("Lis�ttiin rahtikirjaan")."</font><hr>";
			echo "<table>";
			
			if ($yhtiorow['rahti_ja_kasittelykulut_kasin'] != '') {
				$k_rahtikulut = str_replace(',', '.', $k_rahtikulut);
				$k_kasitkulut = str_replace(',', '.', $k_kasitkulut);
				
				if ($k_rahtikulut > 0) {
					$query = "	UPDATE tilausrivi 
								SET tyyppi='D',
								kommentti = concat(kommentti, ' $kukarow[kuka] muutti rahtikuluja rahtikirjan sy�t�ss�.')
								WHERE yhtio='$kukarow[yhtio]' 
								and otunnus='$otsikkonro' 
								and tuoteno='$yhtiorow[rahti_tuotenumero]'
								and uusiotunnus=0
								and tyyppi != 'D'";
					$result = mysql_query($query) or pupe_error($query);
					
					lisaarivi($otsikkonro, $yhtiorow["rahti_tuotenumero"], 1, $k_rahtikulut);
				}
				
				if ($k_kasitkulut > 0) {
					$query = "	UPDATE tilausrivi 
								SET tyyppi='D', 
								kommentti = concat(kommentti, ' $kukarow[kuka] muutti k�sittelykuluja rahtikirjan sy�t�ss�.')
								WHERE yhtio='$kukarow[yhtio]' 
								and otunnus='$otsikkonro' 
								and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'
								and uusiotunnus=0
								and tyyppi != 'D'";
					$result = mysql_query($query) or pupe_error($query);
					
					lisaarivi($otsikkonro, $yhtiorow["kasittelykulu_tuotenumero"], 1, $k_kasitkulut);
				}
			}

			for ($i=0; $i<count($pakkaus); $i++) {

				// katotaan ett� ollaan sy�tetty jotain
				if ($tutkimus > 0) {

					// ja insertataan vaan jos se on erisuurta ku nolla (n�in voidaan nollalla tai spacella tyhjent�� kentti�)
					if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {

						$kilot[$i]		= str_replace(',', '.', $kilot[$i]);
						$kollit[$i]	 	= str_replace(',', '.', $kollit[$i]);
						$kuutiot[$i]	= str_replace(',', '.', $kuutiot[$i]);
						$lavametri[$i]	= str_replace(',', '.', $lavametri[$i]);

						if ($rakirno == '') {
							$query = "select max(rahtikirjanro) rakirno from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro'";
							$result = mysql_query($query) or pupe_error($query);
							$rakirow = mysql_fetch_array($result);
							$rakirno = $rakirow["rakirno"]+1;
						}

						//T�ss� otetaan kaikkien tilausten tunnukset joille sy�tet��n rahtikirjan tiedot
						$tilaukset = explode(',', $tunnukset);

						foreach ($tilaukset as $otsikkonro) {
							$query  = "insert into rahtikirjat
										(poikkeava,rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,viesti,yhtio) values
										('','$rakirno','$kilot[$i]','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','$merahti','$otsikkonro','$pakkaus[$i]','$rahtisopimus','$toimitustapa','$tulostuspaikka','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','$viesti','$kukarow[yhtio]')";
							$result = mysql_query($query) or pupe_error($query);

							if ($kollit[$i]=='') 	$kollit[$i]		= 0;
							if ($kilot[$i]=='') 	$kilot[$i]		= 0;
							if ($lavametri[$i]=='') $lavametri[$i]	= 0;
							if ($kuutiot[$i]=='')	$kuutiot[$i]	= 0;
							
							//	Lis�t��n my�s pakkauksen veloitus, mik�li sellainen on annettu
							$query = "	SELECT avainsana.* 
										FROM avainsana
										JOIN tuote ON tuote.yhtio=avainsana.yhtio and tuote.tuoteno=avainsana.selitetark_2
										WHERE avainsana.yhtio='$kukarow[yhtio]'
										and avainsana.laji='pakkaus' 
										and avainsana.selite='$pakkaus[$i]'
										and selitetark='$pakkauskuvaus[$i]'
										and tuoteno != ''";
							$pakres = mysql_query($query) or pupe_error($query);
							
							if(mysql_num_rows($pakres) == 1) {
								$pakrow = mysql_fetch_array($pakres);
								
								lisaarivi($otsikkonro, $pakrow["selitetark_2"], $kollit[$i]);
							}
							
							if ($kilot[$i]!=0 or $kollit[$i]!=0 or $kuutiot[$i]!=0 or $lavametri[$i]!=0) {
								echo "<tr><td>$pakkauskuvaus[$i]</td><td>$pakkaus[$i]</td><td>$pakkauskuvaustark[$i]</td><td align='right'>$kollit[$i] kll</td><td align='right'>$kilot[$i] kg</td><td align='right'>$kuutiot[$i] m&sup3;</td><td align='right'>$lavametri[$i] m</td></tr>";
							}

							// Vain ekalle tilaukselle lis�t��n kilot
							$kollit[$i]		= 0;
							$kilot[$i] 		= 0;
							$lavametri[$i] 	= 0;
							$kuutiot[$i] 	= 0;

							$apu++;
						}

					}

					//menn��n valitsemaan seuraavaa
					$id=0;
				}
			}

			echo "</table><br>";
		}

		// jos lis�ttiin jotain, merkataan rahtikirjatiedot sy�tetyksi..
		if ($apu > 0) {

			echo "<br>";

			// Haetaan laskun kaikki tiedot ja katsotaan onko kyseess� j�kivaatimus
			$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro'";
			$result   = mysql_query($query) or pupe_error($query);
			$laskurow = mysql_fetch_array($result);

			//Vientilaskuille alatilaa ei saa aina p�ivitt��
			if ($laskurow['alatila'] == 'E') {
				$alatila = "E";
			}
			else {
				$alatila = "B";
			}

			// P�ivitet��n laskuille sy�tetyt tiedot
			$query = "	update lasku
						set alatila='$alatila', kohdistettu='$merahti', rahtisopimus='$rahtisopimus', toimitustapa='$toimitustapa'
						where yhtio = '$kukarow[yhtio]' and tunnus in ($tunnukset)";
			$result = mysql_query($query) or pupe_error($query);

			// Katsotaan pit�isik� t�m� rahtikirja tulostaa heti...
			$query = "select * from toimitustapa where yhtio='$kukarow[yhtio]' and selite='$toimitustapa'";
			$result = mysql_query($query) or pupe_error($query);

			if ((mysql_num_rows($result)==1)) {
				$row = mysql_fetch_array($result);

				// t�m� toimitustapa pit�isi tulostaa nyt..
				if ($row['nouto']=='' and ($row['tulostustapa']=='H' or $row['tulostustapa']=='K')) {
					// rahtikirjojen tulostus vaatii seuraavat muuttujat:

					// $toimitustapa_varasto	toimitustavan selite!!!!varastopaikan tunnus
					// $tee						t�ss� pit�� olla teksti tulosta
					
					$toimitustapa_varasto = $toimitustapa."!!!!".$tulostuspaikka;
					$tee				  = "tulosta";

					require ("rahtikirja-tulostus.php");

				} // end if tulostetaanko heti
			} // end if l�ytyk� toimitustapa


			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php') {
				$query = "	SELECT sum(kollit) kolleroiset 
							FROM rahtikirjat 
							WHERE yhtio = '$kukarow[yhtio]' and otsikkonro in ($tunnukset)";
				$result = mysql_query($query) or pupe_error($query);
				$oslaprow = mysql_fetch_array($result);

				if ($oslaprow['kolleroiset'] > 0) {
					$oslappkpl = $oslaprow['kolleroiset'];
				}
				else {
					$oslappkpl = 0;
				}
				$keraaseen = 'mennaan';
			}

			// Katotaan haluttiinko osoitelappuja tai l�hetteit�
			$oslappkpl = (int) $oslappkpl;
			$lahetekpl = (int) $lahetekpl;
			
			//tulostetaan faili ja valitaan sopivat printterit
			if ($laskurow["varasto"] == '') {
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]'
							order by alkuhyllyalue,alkuhyllynro
							limit 1";
			}
			else {
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
			}
			$prires = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($prires) > 0) {
				
				$prirow= mysql_fetch_array($prires);

				// k�teinen muuttuja viritet��n tilaus-valmis.inc:iss� jos maksuehto on k�teinen
				// ja silloin pit�� kaikki l�hetteet tulostaa aina printteri5:lle (lasku printteri)
				if ($kateinen == 'X') {
					$apuprintteri = $prirow['printteri5']; // laskuprintteri
				}
				else {
					if ($valittu_tulostin == "oletukselle") {
						$apuprintteri = $prirow['printteri1']; // l�heteprintteri
					}
					else {
						$apuprintteri = $valittu_tulostin;
					}
				}

				//haetaan l�hetteen tulostuskomento
				$query   = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
				$kirres  = mysql_query($query) or pupe_error($query);
				$kirrow  = mysql_fetch_array($kirres);
				$komento = $kirrow['komento'];
				
										
				if ($valittu_oslapp_tulostin == "oletukselle") {
					$apuprintteri = $prirow['printteri3']; // osoitelappuprintteri
				}
				else {
					$apuprintteri = $valittu_oslapp_tulostin;
				}						

				//haetaan osoitelapun tulostuskomento
				$query  = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$apuprintteri'";
				$kirres = mysql_query($query) or pupe_error($query);
				$kirrow = mysql_fetch_array($kirres);
				$oslapp = $kirrow['komento'];
			}
				
			if ($valittu_tulostin != '' and $komento != "" and $lahetekpl > 0) {

				$otunnus = $laskurow["tunnus"];

				//hatetaan asiakkaan l�hetetyyppi
				$query = "  SELECT lahetetyyppi, luokka, puhelin, if(asiakasnro!='', asiakasnro, ytunnus) asiakasnro
							FROM asiakas
							WHERE tunnus='$laskurow[liitostunnus]' and yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
				$asrow = mysql_fetch_array($result);
				
				$lahetetyyppi = "";

				if ($asrow["lahetetyyppi"] != '') {
					$lahetetyyppi = $asrow["lahetetyyppi"];
				}
				else {
					//Haetaan yhti�n oletusl�hetetyyppi
					$query = "  SELECT selite
								FROM avainsana
								WHERE yhtio = '$kukarow[yhtio]' and laji = 'LAHETETYYPPI'
								ORDER BY jarjestys, selite
								LIMIT 1";
					$vres = mysql_query($query) or pupe_error($query);
					$vrow = mysql_fetch_array($vres);

					if ($vrow["selite"] != '' and file_exists($vrow["selite"])) {
						$lahetetyyppi = $vrow["selite"];
					}
				}
				
				if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
					require_once ("tilauskasittely/tulosta_lahete_alalasku.inc");
				}	
				elseif (strpos($lahetetyyppi,'simppeli') !== FALSE) {
					require_once ("tilauskasittely/$lahetetyyppi");
				}
				else {
					require_once ("tilauskasittely/tulosta_lahete.inc");
				}
							
				//	Jos meill� on funktio tulosta_lahete meill� on suora funktio joka hoitaa koko tulostuksen
				if(function_exists("tulosta_lahete")) {
					if($vrow["selite"] != '') {
						$tulostusversio = $vrow["selite"];
					}
					else {
						$tulostusversio = $asrow["lahetetyyppi"];						
					}
					
					tulosta_lahete($otunnus, $komento["L�hete"], $kieli = "", $toim, $tee, $tulostusversio);
				}
				else {
					// katotaan miten halutaan sortattavan
					$sorttauskentta = generoi_sorttauskentta($yhtiorow["lahetteen_jarjestys"]);

					if($laskurow["tila"] == "L" or $laskurow["tila"] == "N") {
						$tyyppilisa = " and tilausrivi.tyyppi in ('L') ";
					}
					else {
						$tyyppilisa = " and tilausrivi.tyyppi in ('L','G','W') ";
					} 

					//generoidaan l�hetteelle ja ker�yslistalle rivinumerot
					$query = "  SELECT tilausrivi.*,							
								round(if(tuote.myymalahinta != 0, tuote.myymalahinta, tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1)),2) ovhhinta,
								round(tilausrivi.hinta * (tilausrivi.varattu+tilausrivi.jt+tilausrivi.kpl) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100)),2) rivihinta,
								$sorttauskentta,
								if(tilausrivi.var='J', 1, 0) jtsort
								FROM tilausrivi
								JOIN tuote ON tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
								JOIN lasku ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
								WHERE tilausrivi.otunnus = '$otunnus'
								and tilausrivi.yhtio = '$kukarow[yhtio]'
								$tyyppilisa
								ORDER BY jtsort, sorttauskentta $yhtiorow[lahetteen_jarjestys_suunta], tilausrivi.tunnus";
					$riresult = mysql_query($query) or pupe_error($query);

					//generoidaan rivinumerot
					$rivinumerot = array();

					while ($row = mysql_fetch_array($riresult)) {
						$rivinumerot[$row["tunnus"]] = $row["tunnus"];
					}

					sort($rivinumerot);

					$kal = 1;

					foreach($rivinumerot as $rivino) {
						$rivinumerot[$rivino] = $kal;
						$kal++;
					}

					mysql_data_seek($riresult,0);

					
					unset($pdf);
					unset($page);
					
					$sivu  = 1;
					$total = 0;
					
					// Aloitellaan l�hetteen teko
					$page[$sivu] = alku();

					while ($row = mysql_fetch_array($riresult)) {
						rivi($page[$sivu]);
						$total+= $row["rivihinta"];
					}
					
					//Vikan rivin loppuviiva
					$x[0] = 20;
					$x[1] = 580;
					$y[0] = $y[1] = $kala + $rivinkorkeus - 4;
					$pdf->draw_line($x, $y, $page[$sivu], $rectparam);

					loppu($page[$sivu], 1);
					
					if ($lahetetyyppi == "tulosta_lahete_alalasku.inc") {
						alvierittely($page[$sivu]);
					}
					
					//tulostetaan sivu
					if ($lahetekpl > 0) {
						$komento .= " -#$lahetekpl ";
					}

					print_pdf($komento);					
				}
			}
			
			// Tulostetaan osoitelappu
			if ($valittu_oslapp_tulostin != "" and $oslapp != '' and $oslappkpl > 0) {
				$tunnus = $laskurow["tunnus"];
				
				if ($oslappkpl > 0) {
					$oslapp .= " -#$oslappkpl ";
				}
										
				require ("tilauskasittely/osoitelappu_pdf.inc");
			}
			
			echo "<br><br>";
		} // end if apu>0
	}

	// meill� ei ole valittua tilausta
	if ($toim == 'lisaa' and $id == 0) {
		echo "<font class='head'>".t("Rahtikirjojen sy�tt�")."</font><hr>";

		$formi  = "find";
		$kentta = "etsi";


		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='toimtila' value='$tila'>";
		echo "<input type='hidden' name='text' value='etsi'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY nimitys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_array($result)){
			$sel = '';
			if (($row[0] == $tuvarasto) or ($kukarow['varasto'] == $row[0] and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row[0];
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != '' and yhtio = '$kukarow[yhtio]'
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)){
				$sel = '';
				if ($row[0] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row[0];
				}
				echo "<option value='$row[0]' $sel>$row[0]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$sela = $selb = $selc = "";

		if ($tutyyppi == "NORMAA") {
			$sela = "SELECTED";
		}
		if ($tutyyppi == "ENNAKK") {
			$selb = "SELECTED";
		}
		if ($tutyyppi == "JTTILA") {
			$selc = "SELECTED";
		}
		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("N�yt� normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("N�yt� ennakkotilausket")."</option>";
		echo "<option value='JTTILA' $selc>".t("N�yt� jt-tilausket")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row[0];
			}
			echo "<option value='$row[0]' $sel>".asana('TOIMITUSTAPA_',$row[0])."</option>";
		}

		echo "</select></td>";

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.tunnus='$etsi'";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and yhtio = '$kukarow[yhtio]' and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}

		//jos myyntitilaus niin halutaan maksuehto mukaan
		if ($tila == 'L') {
			$joinmaksuehto = "JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus";
			$selectmaksuehto = "if(maksuehto.jv='', 'OK', lasku.tunnus) jvgrouppi,";
			$groupmaksuehto = "jvgrouppi,";
		}
		else {
			$wherelasku = " and lasku.toim_nimi != '' ";
		}

		// Haetaan sopivia tilauksia
		$query = "	SELECT
					min(lasku.tunnus) tunnus,
					GROUP_CONCAT(distinct lasku.tunnus order by lasku.tunnus SEPARATOR ',') tunnukset,
					if(lasku.tila='L',GROUP_CONCAT(distinct concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) order by concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) SEPARATOR '<br>'),nimi) nimi,
					GROUP_CONCAT(distinct lasku.laatija order by lasku.laatija SEPARATOR '<br>') laatija,
					lasku.toimitustapa toimitustapa,
					toimitustapa.nouto nouto,
					$selectmaksuehto
					if(toimitustapa.tulostustapa='K', toimitustapa.tunnus, lasku.tunnus) kimppakyyti,
					lasku.vienti,
					date_format(lasku.luontiaika, '%Y-%m-%d') laadittux,
					date_format(lasku.toimaika, '%Y-%m-%d') toimaika,
					rahtikirjat.otsikkonro,
					rahtikirjat.poikkeava
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != ''
					$joinmaksuehto
					LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
					LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = '$tila'
					and lasku.alatila = 'C'
					$wherelasku
					$haku
					$tilaustyyppi
					GROUP BY lasku.toimitustapa, toimitustapa.nouto, $groupmaksuehto kimppakyyti, lasku.vienti, laadittux, toimaika, rahtikirjat.otsikkonro
					HAVING (rahtikirjat.otsikkonro is null or rahtikirjat.poikkeava = -9) and ((toimitustapa.nouto is null or toimitustapa.nouto = '') or lasku.vienti != '')
					ORDER BY laadittu";
		$tilre = mysql_query($query) or pupe_error($query);

		//piirret��n taulukko...
		if (mysql_num_rows($tilre) != 0) {

			echo "<br><table>";

			echo "<tr>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Toimitustapa")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<th>".t("Laadittu")."</th>";
			
			if ($kukarow['resoluutio'] == 'I') {
				echo "<th>".t("Toimaika")."</th>";
			}
			
			echo "</tr>";

			while ($row = mysql_fetch_array($tilre)) {
				echo "<tr class='aktiivi'>";
				echo "<td valign='top'>".str_replace(',', '<br>', $row["tunnukset"])."</td>";
				echo "<td valign='top'>$row[nimi]</td>";
				echo "<td valign='top'>$row[toimitustapa]</td>";
				echo "<td valign='top'>$row[laatija]</td>";
				echo "<td valign='top'>".tv1dateconv($row["laadittux"])."</td>";
				
				if ($kukarow['resoluutio'] == 'I') {
					echo "<td valign='top'>".tv1dateconv($row["toimaika"])."</td>";
				}

				echo "	<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='id' value='$row[tunnus]'>
						<input type='hidden' name='tunnukset' value='$row[tunnukset]'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='rakirno' value='$row[tunnus]'>
						<td class='back' valign='top'><input type='submit' name='tila' value='".t("Sy�t�")."'></td>
						</form>";
				echo "</tr>";
			}

			echo "</table>";
		}
		else {
			echo "<font class='message'>".t("Sopivia tilauksia ei l�ytynyt")."...</font><br><br>";
		}
	}

	if ($toim == 'muokkaa' and $id == 0) {

		echo "<font class='head'>".t("Muokkaa rahtikirjatietoja")."</font><hr>";

		echo "<table>";
		echo "<form action='$PHP_SELF' name='find' method='post'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='toimtila' value='$tila'>";
		echo "<input type='hidden' name='text' value='etsi'>";
		echo "<tr><td>".t("Valitse varasto:")."</td><td><select name='tuvarasto' onchange='submit()'>";

		$query = "	SELECT tunnus, nimitys
					FROM varastopaikat
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY nimitys";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while ($row = mysql_fetch_array($result)){
			$sel = '';
			if (($row[0] == $tuvarasto) or ($kukarow['varasto'] == $row[0] and $tuvarasto=='')) {
				$sel = 'selected';
				$tuvarasto = $row[0];
			}
			echo "<option value='$row[0]' $sel>$row[1]</option>";
		}
		echo "</select>";

		$query = "	SELECT distinct maa
					FROM varastopaikat
					WHERE maa != '' and yhtio = '$kukarow[yhtio]'
					ORDER BY maa";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) > 1) {
			echo "<select name='tumaa' onchange='submit()'>";
			echo "<option value=''>".t("Kaikki")."</option>";

			while ($row = mysql_fetch_array($result)){
				$sel = '';
				if ($row[0] == $tumaa) {
					$sel = 'selected';
					$tumaa = $row[0];
				}
				echo "<option value='$row[0]' $sel>$row[0]</option>";
			}
			echo "</select>";
		}

		echo "</td>";

		echo "<td>".t("Valitse tilaustyyppi:")."</td><td><select name='tutyyppi' onchange='submit()'>";

		$sela = $selb = $selc = "";

		if ($tutyyppi == "NORMAA") {
			$sela = "SELECTED";
		}
		if ($tutyyppi == "ENNAKK") {
			$selb = "SELECTED";
		}
		if ($tutyyppi == "JTTILA") {
			$selc = "SELECTED";
		}
		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";
		echo "<option value='NORMAA' $sela>".t("N�yt� normaalitilaukset")."</option>";
		echo "<option value='ENNAKK' $selb>".t("N�yt� ennakkotilausket")."</option>";
		echo "<option value='JTTILA' $selc>".t("N�yt� jt-tilausket")."</option>";

		echo "</select></td></tr>";

		echo "<tr><td>".t("Valitse toimitustapa:")."</td><td><select name='tutoimtapa' onchange='submit()'>";

		$query = "	SELECT selite
					FROM toimitustapa
					WHERE yhtio = '$kukarow[yhtio]'
					ORDER BY selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<option value='KAIKKI'>".t("N�yt� kaikki")."</option>";

		while($row = mysql_fetch_array($result)){
			$sel = '';
			if($row[0] == $tutoimtapa) {
				$sel = 'selected';
				$tutoimtapa = $row[0];
			}
			echo "<option value='$row[0]' $sel>".asana('TOIMITUSTAPA_',$row[0])."</option>";
		}

		echo "</select></td>";

		echo "<td>".t("Etsi tilausta").":</td><td><input type='text' name='etsi'>";
		echo "<input type='Submit' value='".t("Etsi")."'></form></td></tr>";

		echo "</table>";

		$haku = '';

		if (!is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.nimi LIKE '%$etsi%'";
		}

		if (is_numeric($etsi) and $etsi != '') {
			$haku .= "and lasku.tunnus='$etsi'";
		}

		if ($tuvarasto != '' and $tuvarasto != 'KAIKKI') {
			$haku .= " and lasku.varasto='$tuvarasto' ";
		}

		if ($tumaa != '') {
			$query = "	SELECT group_concat(tunnus) tunnukset
						FROM varastopaikat
						WHERE maa != '' and yhtio = '$kukarow[yhtio]' and maa = '$tumaa'";
			$maare = mysql_query($query) or pupe_error($query);
			$maarow = mysql_fetch_array($maare);
			$haku .= " and lasku.varasto in ($maarow[tunnukset]) ";
		}

		if ($tutoimtapa != '' and $tutoimtapa != 'KAIKKI') {
			$haku .= " and lasku.toimitustapa='$tutoimtapa' ";
		}

		if ($tutyyppi != '' and $tutyyppi != 'KAIKKI') {
			if ($tutyyppi == "NORMAA") {
				$haku .= " and lasku.clearing='' ";
			}
			elseif($tutyyppi == "ENNAKK") {
				$haku .= " and lasku.clearing='ENNAKKOTILAUS' ";
			}
			elseif($tutyyppi == "JTTILA") {
				$haku .= " and lasku.clearing='JT-TILAUS' ";
			}
		}
		// pvm 30 pv taaksep�in
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		// n�ytet��n tilauksia jota voisi muokata, tila L alatila B tai E tai sitetn alatila D jos toimitustapa on HETI
		$query = "	select lasku.tunnus 'tilaus', lasku.nimi asiakas, concat_ws(' ', lasku.toimitustapa, vienti, ' ', varastopaikat.nimitys) toimitustapa, date_format(lasku.luontiaika, '%Y-%m-%d') laadittu, lasku.laatija, rahtikirjat.rahtikirjanro rakirno, sum(kilot) kilot, sum(kollit) kollit, sum(kuutiot) kuutiot, sum(lavametri) lavametri
					from lasku use index (tila_index),
					toimitustapa use index (selite_index),
					rahtikirjat use index (otsikko_index),
					varastopaikat use index (PRIMARY)
					where lasku.yhtio='$kukarow[yhtio]'
					and	tila='$tila'
					and (lasku.alatila in ('B','E') or (lasku.alatila='D' and toimitustapa.tulostustapa='H'))
					and toimitustapa.yhtio=lasku.yhtio
					and toimitustapa.selite=lasku.toimitustapa
					and rahtikirjat.yhtio=lasku.yhtio
					and rahtikirjat.otsikkonro=lasku.tunnus
					and varastopaikat.yhtio=lasku.yhtio
					and	varastopaikat.tunnus=rahtikirjat.tulostuspaikka
					$haku
					$tilaustyyppi
					group by 1,2,3,4,5,6
					order by toimitustapa, lasku.luontiaika desc";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) != 0) {
			echo "<br><table>";

			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($tilre)-5; $i++)
				echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";
			echo "<th>".t("Tiedot yhteens�")."</th></tr>";

			while ($row = mysql_fetch_array($tilre)) {
				echo "<tr class='aktiivi'>";

				for ($i=0; $i<mysql_num_fields($tilre)-5; $i++)
					if (mysql_field_name($tilre,$i) == 'laadittu') {
						echo "<td>".tv1dateconv($row[$i])."</td>";
					}
					else {
						echo "<td>$row[$i]</td>";
					}

				$tiedot="";
				if ($row['kollit']>0)		$tiedot .= "$row[kollit] kll ";
				if ($row['kilot']>0)		$tiedot .= "$row[kilot] kg ";
				if ($row['kuutiot']>0)		$tiedot .= "$row[kuutiot] m&sup3; ";
				if ($row['lavametri']>0)	$tiedot .= "$row[lavametri] m";

				echo "<td>$tiedot</td>";

				echo "<form method='post' action='$PHP_SELF'>
						<input type='hidden' name='toim' value='$toim'>
						<input type='hidden' name='tee' value='change'>
						<input type='hidden' name='rakirno' value='$row[rakirno]'>
						<input type='hidden' name='id' value='$row[tilaus]'>
						<input type='hidden' name='tunnukset' value='$row[tilaus]'>
						<td class='back'><input type='submit' value='".t("Muokkaa rahtikirjaa")."'></td>
						</form>";

				if ($row["tilaus"] != $edotsikkonro) {
					echo "<form method='post' action='$PHP_SELF'>
							<input type='hidden' name='toim' value='$toim'>
							<input type='hidden' name='id' value='$row[tilaus]'>
							<input type='hidden' name='tunnukset' value='$row[tilaus]'>
							<td class='back'><input type='submit' value='".t("Lis�� rahtikirja tilaukselle")."'></td>
							</form>";
				}
				else {
					echo "<td class='back'></td>";
				}
				echo "</tr>";

				$edotsikkonro = $row["tilaus"];
			}
			echo "</table>";
		}
	}

	if ($id != 0) {

		echo "<font class='head'>".t("Sy�t� rahtikirjan tiedot")."</font><hr>";

		$query = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$id'";
		$resul = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($resul) == 0) {
			die ("<font class='error'>".t("VIRHE Tilausta").": $id ".t("ei l�ydy")."!</font>");
		}

		$otsik = mysql_fetch_array($resul);

		if ($tila == 'L') {
			$query = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$otsik[maksuehto]'";
			$resul = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($resul) == 0) {
				$marow = array();
			 	if ($otsik["erpcm"] == "0000-00-00") {
					echo ("<font class='error'>".t("VIRHE: Maksuehtoa ei l�ydy")."! $otsik[maksuehto]!</font>");
				}
			}
			else {
				$marow = mysql_fetch_array($resul);
			}
		}

		echo "<table>";
		echo "<form name='rahtikirjainfoa' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='rahtikirjan_esisyotto' value='$rahtikirjan_esisyotto'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='rakirno' value='$rakirno'>";
		echo "<input type='hidden' name='tee' value='add'>";
		echo "<input type='hidden' name='otsikkonro' value='$otsik[tunnus]'>";
		echo "<input type='hidden' name='tunnukset' value='$tunnukset'>";
		echo "<input type='hidden' name='mista' value='$mista'>";

		echo "<tr><th align='left'>".t("Tilaus")."</th><td>$otsik[tunnus]</td>";
		echo "<th align='left'>".t("Ytunnus")."</th><td>$otsik[ytunnus]</td></tr>";

		echo "<tr><th align='left'>".t("Asiakas")."</th><td>$otsik[nimi] $otsik[nimitark]<br>$otsik[osoite]<br>$otsik[postino] $otsik[postitp]</td>";
		echo "<th align='left'>".t("Toimitusosoite")."</th><td>$otsik[toim_nimi] $otsik[toim_nimitark]<br>$otsik[toim_osoite]<br>$otsik[toim_postino] $otsik[toim_postitp]</td></tr>";

		echo "<tr><th align='left'>".t("Ker�tty")."</th><td>$otsik[kerayspvm]</td>";
		echo "<th align='left'>".t("Maksuehto")."</th><td>$marow[teksti]</td></tr>";

		if ($otsik["vienti"] == 'K')		$vientit = t("Vienti� EU:n ulkopuolelle");
		elseif ($otsik["vienti"] == 'E')	$vientit = t("EU Vienti�");
		else								$vientit = t("Kotimaan myynti�");

		echo "<tr><th align='left'>".t("Vienti")."</th><td>$vientit</td>";

		// haetaan kaikki toimitustavat
		$query  = "SELECT * FROM toimitustapa WHERE yhtio='$kukarow[yhtio]' order by jarjestys,selite";
		$result = mysql_query($query) or pupe_error($query);

		echo "<th align='left'>".t("Toimitustapa")."</th><td>\n";

		echo "<select name='toimitustapa' onchange='submit()'>\n";

		while ($row = mysql_fetch_array($result)) {
			if ($otsik['toimitustapa'] == $row['selite'] and $toimitustapa=='') {
				$tulostustapa 	= $row['tulostustapa'];
				$select 		= 'selected';
				$toimitustapa 	= $row['selite'];
			}
			elseif ($toimitustapa == $row['selite']) {
				$tulostustapa 	= $row['tulostustapa'];
				$select 		= 'selected';
				$toimitustapa 	= $row['selite'];
			}
			else $select = '';

			echo "<option $select value='$row[selite]'>".asana('TOIMITUSTAPA_',$row['selite'])."</option>\n";
		}

		echo "</select></td></tr>\n";


		// jos ei olla submitattu t�t� ruutua, otetaan merahti otsikolta
		if (!isset($merahti)) $merahti  = $otsik['kohdistettu'];

		//tehd��n rahtipopup..
		if ($merahti=="K") {
			$rahtihaku = $yhtiorow['ytunnus'];
			$mesel = "SELECTED";
			$nesel = "";
		}
		else {
			$rahtihaku = $otsik['ytunnus'];
			$nesel = "SELECTED";
			$mesel = "";
		}

		echo "<tr><th align='left'>".t("Rahti")."</th><td>";
		echo "<select name='merahti' onchange='submit()'>";
		echo "<option value=''  $nesel>".t("Vastaanottaja")."</option>";
		echo "<option value='K' $mesel>".t("L�hett�j�")."</option>";
		echo "</select></td>";


		//etsit��n l�ytyyk� rahtisopimusta
		$rahtisopimus='';
		$query = "select * from rahtisopimukset where toimitustapa='$toimitustapa' and ytunnus='$rahtihaku' and yhtio='$kukarow[yhtio]'";
		$rares = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($rares) == 1) {
			$rarow = mysql_fetch_array($rares);
			$rahtisopimus = $rarow['rahtisopimus'];
		}

		if ($otsik['rahtisopimus'] != '') $rahtisopimus = $otsik['rahtisopimus'];

		//tehd��n rahtisopimuksen sy�tt�
		echo "<th align='left'>".t("Rahtisopimus")."</th><td><input value='$rahtisopimus' type='text' name='rahtisopimus' size='20'></td></tr>";

		// haetaan kaikki varastot
		$query  = "SELECT tunnus, nimitys FROM varastopaikat WHERE yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		// jos l�ytyy enemm�n kuin yksi, tehd��n varasto popup..
		if (mysql_num_rows($result) > 1) {
			echo "<tr><th align='left'>".t("Varasto")."</th><td>";
			echo "<select name='tulostuspaikka'>";

			$query = "select tulostuspaikka from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$id' limit 1";
			$rarrr = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($rarrr)==1) {
				$roror          = mysql_fetch_array($rarrr);
				$tulostuspaikka = $roror['tulostuspaikka'];
			}

			if ($kukarow["varasto"]!=0) $tulostuspaikka=$kukarow['varasto'];

			if ($tulostuspaikka=='') $tulostuspaikka=$otsik['varasto'];

			while ($row = mysql_fetch_array($result)) {
				if ($tulostuspaikka==$row['tunnus'])	$select='selected';
				else									$select='';

				echo "<option $select value='$row[tunnus]'>$row[nimitys]</option>";
			}
			echo "</select></td>";
		}
		else {
			$row = mysql_fetch_array($result);

			$tulostuspaikka = $row[0];

			echo "<input type='hidden' name='tulostuspaikka' value='$row[0]'>";
		}

		if (strtoupper($tulostustapa) == 'H' or strtoupper($tulostustapa) == 'K') {
			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			echo "<th>".t("Rahtikirjatulostin")."</th><td><select name='komento'>";
			echo "<option value=''>".t("Oletus")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}
			echo "</select></td>";
		}
		else {
			echo "<th></th><td></td>";
		}
		
		$query = "	SELECT GROUP_CONCAT(distinct if(viesti!='',viesti,NULL) separator '. ') viesti
					from rahtikirjat use index (otsikko_index)
					where yhtio			= '$kukarow[yhtio]'
					and otsikkonro		= '$id'
					and rahtikirjanro	= '$rakirno'";
		$viestirar = mysql_query($query) or pupe_error($query);
		
		$viestirarrow = mysql_fetch_array($viestirar);
		
		echo "<th>Kuljetusohje</th><td><textarea name='viesti'>$viestirarrow[viesti]</textarea></td>";
		
		echo "</tr>";

		// jos meill� on hetitulostettava j�lkivaatimus-tilaus niin (annetaan mahdollisuus tulostaa) TULOSTETAAN lasku heti
		if ((strtoupper($tulostustapa) == 'H' or strtoupper($tulostustapa) == 'K') and $marow["jv"] != "") {

			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr>";
			echo "<th colspan='3'><font class='error'>".t("Valitse j�lkivaatimuslaskujen tulostuspaikka")."</font></th>";
			echo "<td><select name='laskutulostin'>";
			echo "<option value=''>".t("Ei tulosteta laskua")."</option>";

			//Haetaan varaston JV-kuittitulostin
			$query = "SELECT printteri7 FROM varastopaikat WHERE yhtio='$kukarow[yhtio]' and tunnus='$tulostuspaikka'";
			$jvres = mysql_query($query) or pupe_error($query);
			$jvrow = mysql_fetch_array($jvres);

			$query = "	select *
						from kirjoittimet
						where yhtio='$kukarow[yhtio]'
						ORDER BY kirjoitin";
			$kires = mysql_query($query) or pupe_error($query);

			while ($kirow=mysql_fetch_array($kires)) {
				if ($kirow["tunnus"] == $jvrow["printteri7"]) {
					$sel = "SELECTED";
				}
				else {
					$sel = "";
				}

				echo "<option value='$kirow[tunnus]' $sel>$kirow[kirjoitin]</option>";
			}

			echo "</select></td></tr>";

		}

		echo "</table>";

		//sitten tehd��n pakkaustietojen sy�tt�...
		echo "<br><font class='message'>".t("Sy�t� tilauksen pakkaustiedot")."</font><hr>";

		echo "<table>";

		$query  = "	SELECT avainsana.selite, ".avain('select')."
					FROM avainsana
					".avain('join','PAKKAUS_')."
					WHERE avainsana.yhtio	= '$kukarow[yhtio]'
					and avainsana.laji	= 'pakkaus'
					order by avainsana.jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$query  = "	SELECT sum(tuotemassa*(varattu+kpl)) massa, sum(varattu+kpl) kpl, sum(if(tuotemassa!=0, varattu+kpl, 0)) kplok
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus = '$otsik[tunnus]'";
		$painoresult = mysql_query($query) or pupe_error($query);
		$painorow = mysql_fetch_array($painoresult);

		if ($painorow["kpl"] > 0) {
			$osumapros = round($painorow["kplok"] / $painorow["kpl"] * 100, 2);
		}
		else {
			$osumapros = "N/A";
		}

		echo "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s kg, %s %%:lle kappaleista on annettu paino."),$painorow["massa"],$osumapros)."</font><br>";
		
		//Tuotekannassa voi olla tuotteen mitat kahdella eri tavalla
		// leveys x korkeus x syvyys
		// leveys x korkeus x pituus
		$query = "	SHOW columns
					FROM tuote
					LIKE 'tuotepituus'";
		$spres = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($spres) == 1) {
			$splisa = "tuotepituus";
		}
		else {
			$splisa = "tuotesyvyys";
		}
		
		$query  = "	SELECT round(sum(tuotekorkeus*tuoteleveys*$splisa*(varattu+kpl)),10) tilavuus, sum(varattu+kpl) kpl, sum(if(tuotekorkeus!=0 and tuoteleveys!=0 and $splisa!=0, varattu+kpl, 0)) kplok
					FROM tilausrivi
					JOIN tuote ON (tuote.yhtio=tilausrivi.yhtio and tuote.tuoteno=tilausrivi.tuoteno and tuote.ei_saldoa = '')
					WHERE tilausrivi.yhtio = '$kukarow[yhtio]' and tilausrivi.otunnus = '$otsik[tunnus]'";
		$tilavuusresult = mysql_query($query) or pupe_error($query);
		$tilavuusrow = mysql_fetch_array($tilavuusresult);

		if ($tilavuusrow["kpl"] > 0) {
			$osumapros = round($tilavuusrow["kplok"] / $tilavuusrow["kpl"] * 100, 2);
		}
		else {
			$osumapros = "N/A";
		}
		
		$tilavuusrow["tilavuus"] = round($tilavuusrow["tilavuus"],3);

		echo "<font class='message'>".t("Tilauksen tilavuus tuoterekisterin tietojen mukaan on").": $tilavuusrow[tilavuus] m&sup3; , $osumapros ".t("%:lle kappaleista on annettu koko.")."</font><br>";
		
		echo "<table>";

		echo "<tr><th>".t("Kollia")."</th><th>".t("Kg")."</th><th>m&sup3;</th><th>m</th><th align='left' colspan='3'>".t("Pakkaus")."</th></tr>";

		$i = 0;

		while ($row = mysql_fetch_array($result)) {
			$query = "	select sum(kollit) kollit, sum(kilot) kilot, sum(kuutiot) kuutiot, sum(lavametri) lavametri, min(pakkauskuvaustark) pakkauskuvaustark
						from rahtikirjat use index (otsikko_index)
						where yhtio			= '$kukarow[yhtio]'
						and otsikkonro		= '$id'
						and rahtikirjanro	= '$rakirno'
						and pakkaus			= '$row[selite]'
						and pakkauskuvaus	= '$row[selitetark]'";
			$rarrr = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($rarrr)==1) {
				$roror = mysql_fetch_array($rarrr);
				if ($roror['kollit']>0)					$kollit[$i]				= $roror['kollit'];
				if ($roror['kilot']>0)					$kilot[$i]				= $roror['kilot'];
				if ($roror['kuutiot']>0)				$kuutiot[$i]			= $roror['kuutiot'];
				if ($roror['lavametri']>0)				$lavametri[$i]			= $roror['lavametri'];
				if ($roror['pakkauskuvaustark']!='')	$pakkauskuvaustark[$i]	= $roror['pakkauskuvaustark'];
			}

			echo "<tr>
			<td><input type='hidden' name='pakkaus[$i]' value='$row[selite]'>
				<input type='hidden' name='pakkauskuvaus[$i]' value='$row[selitetark]'>
			    <input type='text' size='3' value='$kollit[$i]' name='kollit[$i]'></td>
			<td><input type='text' size='3' value='$kilot[$i]' name='kilot[$i]'></td>
			<td><input type='text' size='3' value='$kuutiot[$i]' name='kuutiot[$i]'></td>
			<td><input type='text' size='3' value='$lavametri[$i]' name='lavametri[$i]'></td>
			<td>$row[selite]</td>
			<td>$row[selitetark]</td>";

			/*
			$query = "	SELECT distinct selite, selitetark
						FROM avainsana
						WHERE yhtio='$kukarow[yhtio]' and laji='PAKKAUSKUVAUS'
						ORDER BY selite+0";
			$pksresult = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($pksresult) > 0) {
				echo "<td><select name='pakkauskuvaustark[$i]'>";
				echo "<option value=''>".t("Ei tarkennetta")."</option>";

				while ($pksrow = mysql_fetch_array($pksresult)) {
					$sel = '';
					if ($pakkauskuvaustark[$i] == $pksrow[0]) {
						$sel = "selected";
					}
					echo "<option value='$pksrow[0]' $sel>$pksrow[0]</option>";
				}
				echo "</select></td>";
			}
			*/
			echo "<td><input type='text' size='10' name='pakkauskuvaustark[$i]' value='$pakkauskuvaustark[$i]'></td>";

			echo "</tr>";

			$i++;
		}
		
		echo "</table>";
		
		if ($yhtiorow['rahti_ja_kasittelykulut_kasin'] != '') {

			echo "<br><table>";
			
			$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[rahti_tuotenumero]'";
			$rhire = mysql_query($query) or pupe_error($query);
			
			
			
			if (mysql_num_rows($rhire) == 1) {
				$trow  = mysql_fetch_array($rhire);
				
				$query = "	SELECT 
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa
				 			FROM tilausrivi 
							JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]' 
							and tilausrivi.otunnus = '$otsik[tunnus]' 
							and tilausrivi.tuoteno = '$yhtiorow[rahti_tuotenumero]'
							and tilausrivi.tyyppi != 'D'";
				$rhire = mysql_query($query) or pupe_error($query);
				$rrow  = mysql_fetch_array($rhire);
				
				if ($yhtiorow["alv_kasittely"] == '') {
					$k_rahtikulut = $rrow["summa"];
				}
				else {
					$k_rahtikulut = $rrow["arvo"];
				}
				
				echo "<tr><th>".t("Rahti").":</th><td><input type='text' size='6' name='k_rahtikulut' value='$k_rahtikulut'></td><td>$yhtiorow[valkoodi]</td></tr>";
			}
			
			$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$yhtiorow[kasittelykulu_tuotenumero]'";
			$rhire = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($rhire) == 1) {
				$trow  = mysql_fetch_array($rhire);
				
				$query = "	SELECT 
							round(sum(tilausrivi.hinta / if('$yhtiorow[alv_kasittely]'  = '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) arvo,
							round(sum(tilausrivi.hinta * if('$yhtiorow[alv_kasittely]' != '' and tilausrivi.alv < 500, (1+tilausrivi.alv/100), 1) * (tilausrivi.varattu+tilausrivi.jt) * if(tilausrivi.netto='N', (1-tilausrivi.ale/100), (1-(tilausrivi.ale+lasku.erikoisale-(tilausrivi.ale*lasku.erikoisale/100))/100))),2) summa
				 			FROM tilausrivi 
							JOIN lasku ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
							WHERE tilausrivi.yhtio = '$kukarow[yhtio]' 
							and tilausrivi.otunnus = '$otsik[tunnus]' 
							and tilausrivi.tuoteno = '$yhtiorow[kasittelykulu_tuotenumero]'
							and tilausrivi.tyyppi != 'D'";
				$rhire = mysql_query($query) or pupe_error($query);
				$rrow  = mysql_fetch_array($rhire);
				
				if ($yhtiorow["alv_kasittely"] == '') {
					$k_kasitkulut = $rrow["summa"];
				}
				else {
					$k_kasitkulut = $rrow["arvo"];
				}
				
				echo "<tr><th>".t("K�sittelykulut").":</th><td><input type='text' size='6' name='k_kasitkulut' value='$k_kasitkulut'></td><td>$yhtiorow[valkoodi]</td></tr>";
			}

			echo "</table>";
		}
		
		if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == '' or $mista != 'keraa.php') {
			
			$sel 		= "SELECTED";
			$oslappkpl 	= 0;
			$lahetekpl  = 0;
			
			echo "<br><table>";
			echo "<tr><th>".t("L�hete").":</th><th>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE
						yhtio='$kukarow[yhtio]'
						ORDER by kirjoitin";
			$kirre = mysql_query($query) or pupe_error($query);

			echo "<select name='valittu_tulostin'>";

			echo "<option value=''>".t("Ei tulosteta")."</option>";
			echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select> ".t("Kpl").": <input type='text' size='4' name='lahetekpl' value='$lahetekpl'></th>";
			
			
			echo "</tr>";
			
			echo "<tr>";
			
			echo "<th>".t("Osoitelappu").":</th>";
			
			echo "<th>";
			
			mysql_data_seek($kirre, 0);

			echo "<select name='valittu_oslapp_tulostin'>";		
			echo "<option value=''>".t("Ei tulosteta")."</option>";
			echo "<option value='oletukselle' $sel>".t("Oletustulostimelle")."</option>";

			while ($kirrow = mysql_fetch_array($kirre)) {
				echo "<option value='$kirrow[tunnus]'>$kirrow[kirjoitin]</option>";
			}

			echo "</select> ".t("Kpl").": <input type='text' size='4' name='oslappkpl' value='$oslappkpl'></th>";
		
			echo "</table>";
		}


		if ($tee=='change' or $tee=='add') {
			echo "<input type='hidden' name='muutos' value='yes'>";
		}

		echo "<br>
		<input type='hidden' name='id' value='$id'>
		<input name='subnappi' type='submit' value='".t("Valmis")."'>";
		echo "</form>";

		if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php') {
			echo "<font class='message'>".t("Siirryt automaattisesti takaisin ker�� ohjelmaan")."!</font>";
		}
	}

	if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php' and $keraaseen == 'mennaan') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tilauskasittely/keraa.php'>";
		exit;
	}

	require ("inc/footer.inc");
?>
