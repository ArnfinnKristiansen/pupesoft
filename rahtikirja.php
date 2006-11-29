<?php
	require ("inc/parametrit.inc");	
	
	if ($id=='') $id=0;


	//lis�t��n sy�tetty kama rahtikirja-tauluun
	if ($tee=='add') {
		$apu=0; //apumuuttuja
		$tutkimus = 0; // t�nne tulee luku

		// katotaan ollaanko sy�tetty jotain
		for ($i = 0; $i < count($pakkaus); $i++) {
			if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {
				$tutkimus++;
			}
		}

		// jos ollaan muokkaamassa rivej� poistetaan eka vanhat rahtikirjattiedot..
		if ($tutkimus > 0) {

			if ($muutos == 'yes') {
				$query = "delete from rahtikirjat where yhtio='$kukarow[yhtio]' and otsikkonro='$otsikkonro' and rahtikirjanro='$rakirno'";
				$result = mysql_query($query) or pupe_error($query);

				// merkataan tilaus takaisin ker�tyksi, paitsi jos se on vientitilaus jolle vientitiedot on sy�tetty
				$query = "update lasku set alatila='C' where yhtio='$kukarow[yhtio]' and tunnus='$otsikkonro' and alatila!='E'";
				$result = mysql_query($query) or pupe_error($query);

				//Voi k�yd� niin, ett� rahtikirja on jo tulostunut. Poistetaan mahdolliset tulostusflagit
				$query = "	update tilausrivi set toimitettu = '', toimitettuaika=''
							where otunnus = '$otsikkonro' and yhtio = '$kukarow[yhtio]' and var not in ('P','J') and tyyppi='L'";
				$result  = mysql_query($query) or pupe_error($query);
			}

			// saadaanko n�ille tilauksille sy�tt�� rahtikirjoja
			$query = "	SELECT
						lasku.yhtio,
						rahtikirjat.otsikkonro,
						toimitustapa.nouto,
						lasku.vienti
						FROM lasku use index (tila_index)
						JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != ''
						JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
						LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
						LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
						WHERE lasku.yhtio = '$kukarow[yhtio]'
						and lasku.tila = 'L'
						and lasku.alatila in ('C','E')
						and lasku. tunnus in ($tunnukset)
						HAVING rahtikirjat.otsikkonro is null and ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')";
			$tilre = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($tilre) == 0) {
				echo "<br><br><font class='error'> ".t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�")."! </font><br>";
				exit;
			}

			echo "<font class='head'>".t("Lis�ttiin rahtikirjaan")."</font><hr>";
			echo "<table>";

			for ($i=0; $i<count($pakkaus); $i++) {

				// katotaan ett� ollaan sy�tetty jotain
				if ($tutkimus > 0) {

					// ja insertataan vaan jos se on erisuurta ku nolla (n�in voidaan nollalla tai spacella tyhjent�� kentti�)
					if (($kilot[$i] != '' or $kollit[$i] != '' or $kuutiot[$i] != '' or $lavametri[$i] != '') and $subnappi != '') {

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
										(rahtikirjanro,kilot,kollit,kuutiot,lavametri,merahti,otsikkonro,pakkaus,rahtisopimus,toimitustapa,tulostuspaikka,pakkauskuvaus,pakkauskuvaustark,yhtio) values
										('$rakirno','$kilot[$i]','$kollit[$i]','$kuutiot[$i]','$lavametri[$i]','$merahti','$otsikkonro','$pakkaus[$i]','$rahtisopimus','$toimitustapa','$tulostuspaikka','$pakkauskuvaus[$i]','$pakkauskuvaustark[$i]','$kukarow[yhtio]')";
							$result = mysql_query($query) or pupe_error($query);

							if ($kollit[$i]=='') 	$kollit[$i]		= 0;
							if ($kilot[$i]=='') 	$kilot[$i]		= 0;
							if ($lavametri[$i]=='') $lavametri[$i]	= 0;
							if ($kuutiot[$i]=='')	$kuutiot[$i]	= 0;

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
				if ($row['nouto']=='' and ($row['hetiera']=='H' or $row['hetiera']=='K')) {
					// rahtikirjojen tulostus vaatii seuraavat muuttujat:

					// $toimitustapa	toimitustavan selite
					// $varasto 		varastopaikan tunnus
					// $tee				t�ss� pit�� olla teksti tulosta

					$varasto	= $tulostuspaikka;
					$tee		= "tulosta";

					require ("rahtikirja-tulostus.php");

				} // end if tulostetaanko heti
			} // end if l�ytyk� toimitustapa
			
			
			if ($yhtiorow['karayksesta_rahtikirjasyottoon'] != '' and $mista == 'keraa.php') {
				$query = "SELECT sum(kollit) kolleroiset FROM rahtikirjat WHERE yhtio = '$kukarow[yhtio]' and otsikkonro in ($tunnukset)";
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
			
			//katotaan haluttiinko osoitelappuja
			$oslappkpl = (int) $oslappkpl;
			if ($oslappkpl > 0 ) {
				$query = "	select *
							from varastopaikat
							where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[varasto]'
							order by alkuhyllyalue,alkuhyllynro";
				$prires= mysql_query($query) or pupe_error($query);
				if (mysql_num_rows($prires)>0) {
					$prirow= mysql_fetch_array($prires);
					//haetaan osoitelapun tulostuskomento
					$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$prirow[printteri3]'";
					$kirres= mysql_query($query) or pupe_error($query);
					$kirrow= mysql_fetch_array($kirres);
					$oslapp=$kirrow['komento'];


					//jos osoitelappuprintteri l�ytyy tulostetaan osoitelappu..
					if ($oslapp != '') {
						if ($oslappkpl > 0) {
							$oslapp .= " -#$oslappkpl ";
						}
						$tunnus = $laskurow["tunnus"];
						$juuresta = 'joo';
						require ("tilauskasittely/osoitelappu_pdf.inc");
					} //end if voidaan tulostaa
				} //end if varastopaikat
			} // end if oslappkpl

		} // end if apu>0
	}

	// meill� ei ole valittua tilausta
	if ($toim == 'lisaa' and $id == 0) {
		echo "<font class='head'>".t("Rahtikirjojen sy�tt�")."</font><hr>";

		$formi  = "find";
		$kentta = "etsi";

		// tehd��n etsi valinta
		echo "<br><form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").":
				<input type='hidden' name='toim' value='$toim'>
				<input type='text' name='etsi'>
				<input type='Submit' value='".t("Etsi")."'></form>";

		$haku = '';
		if (is_string($etsi))  $haku = "and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku = "and lasku.tunnus='$etsi'";

		// Haetaan sopivia tilauksia
		$query = "	SELECT
					min(lasku.tunnus) tunnus,
					GROUP_CONCAT(distinct lasku.tunnus order by lasku.tunnus SEPARATOR ',') tunnukset,
					GROUP_CONCAT(distinct concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) order by concat_ws(' ', lasku.toim_nimi, lasku.toim_nimitark) SEPARATOR '<br>') nimi,
					GROUP_CONCAT(distinct lasku.laatija order by lasku.laatija SEPARATOR '<br>') laatija,
					lasku.toimitustapa toimitustapa,
					toimitustapa.nouto nouto,
					if(maksuehto.jv='', 'OK', lasku.tunnus) jvgrouppi,
					if(toimitustapa.hetiera='K', toimitustapa.tunnus, lasku.tunnus) kimppakyyti,
					lasku.vienti,
					date_format(lasku.luontiaika, '%Y-%m-%d') laadittux,
					rahtikirjat.otsikkonro
					FROM lasku use index (tila_index)
					JOIN tilausrivi use index (yhtio_otunnus) ON tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.toimitettu = '' and tilausrivi.keratty != ''
					JOIN maksuehto ON lasku.yhtio = maksuehto.yhtio and lasku.maksuehto = maksuehto.tunnus
					LEFT JOIN toimitustapa use index (selite_index) ON toimitustapa.yhtio = lasku.yhtio and toimitustapa.selite = lasku.toimitustapa
					LEFT JOIN rahtikirjat use index (otsikko_index) ON rahtikirjat.otsikkonro=lasku.tunnus and rahtikirjat.yhtio=lasku.yhtio
					WHERE lasku.yhtio = '$kukarow[yhtio]'
					and lasku.tila = 'L'
					and lasku.alatila = 'C'
					$haku
					GROUP BY lasku.toimitustapa, toimitustapa.nouto, jvgrouppi, kimppakyyti, lasku.vienti, laadittux, rahtikirjat.otsikkonro
					HAVING rahtikirjat.otsikkonro is null and ((toimitustapa.nouto is null or toimitustapa.nouto='') or lasku.vienti!='')
					ORDER BY laadittu";
		$tilre = mysql_query($query) or pupe_error($query);

		//piirret��n taulukko...
		if (mysql_num_rows($tilre) != 0) {

			echo "<table>";

			echo "<tr>";
			echo "<th>".t("Tilaus")."</th>";
			echo "<th>".t("Asiakas")."</th>";
			echo "<th>".t("Toimitustapa")."</th>";
			echo "<th>".t("Laatija")."</th>";
			echo "<th>".t("Laadittu")."</th>";
			echo "</tr>";

			while ($row = mysql_fetch_array($tilre)) {
				echo "<tr>";
				echo "<td valign='top'>".str_replace(',', '<br>', $row["tunnukset"])."</td>";
				echo "<td valign='top'>$row[nimi]</td>";
				echo "<td valign='top'>$row[toimitustapa]</td>";
				echo "<td valign='top'>$row[laatija]</td>";
				echo "<td valign='top'>$row[laadittux]</td>";

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

		$formi  = "find";
		$kentta = "etsi";

		// tehd��n etsi valinta
		echo "<br><form action='$PHP_SELF' name='find' method='post'>".t("Etsi tilausta").":
				<input type='hidden' name='toim' value='$toim'>
				<input type='text' name='etsi'>
				<input type='Submit' value='".t("Etsi")."'></form>";

		$haku='';
		if (is_string($etsi))  $haku = "and lasku.nimi LIKE '%$etsi%'";
		if (is_numeric($etsi)) $haku = "and lasku.tunnus='$etsi'";

		// pvm 30 pv taaksep�in
		$dd = date("d",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$mm = date("m",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
		$yy = date("Y",mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));

		// n�ytet��n tilauksia jota voisi muokata, tila L alatila B tai E tai sitetn alatila D jos toimitustapa on HETI
		$query = "	select lasku.tunnus 'tilaus', lasku.nimi asiakas, concat_ws(' ', lasku.toimitustapa, vienti, ' ', varastopaikat.nimitys) toimitustapa, date_format(luontiaika, '%Y-%m-%d') laadittu, laatija, rahtikirjat.rahtikirjanro rakirno, sum(kilot) kilot, sum(kollit) kollit, sum(kuutiot) kuutiot, sum(lavametri) lavametri
					from lasku use index (tila_index),
					toimitustapa use index (selite_index),
					rahtikirjat use index (otsikko_index),
					varastopaikat use index (PRIMARY)
					where lasku.yhtio='$kukarow[yhtio]'
					and	tila='L'
					and (alatila in ('B','E') or (alatila='D' and hetiera='H'))
					and toimitustapa.yhtio=lasku.yhtio
					and toimitustapa.selite=lasku.toimitustapa
					and rahtikirjat.yhtio=lasku.yhtio
					and rahtikirjat.otsikkonro=lasku.tunnus
					and varastopaikat.yhtio=lasku.yhtio
					and	varastopaikat.tunnus=rahtikirjat.tulostuspaikka
					$haku
					group by 1,2,3,4,5,6
					order by toimitustapa, luontiaika desc";
		$tilre = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($tilre) != 0) {
			echo "<table>";

			echo "<tr>";
			for ($i=0; $i<mysql_num_fields($tilre)-5; $i++)
				echo "<th align='left'>".t(mysql_field_name($tilre,$i))."</th>";
			echo "<th>".t("Tiedot yhteens�")."</th></tr>";

			while ($row = mysql_fetch_array($tilre)) {
				echo "<tr>";

				for ($i=0; $i<mysql_num_fields($tilre)-5; $i++)
					echo "<td>$row[$i]</td>";

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

		echo "<table>";
		echo "<form name='rahtikirjainfoa' action='$PHP_SELF' method='post' autocomplete='off'>";
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
				$hetiera 		= $row['hetiera'];
				$select 		= 'selected';
				$toimitustapa 	= $row['selite'];
			}
			elseif ($toimitustapa == $row['selite']) {
				$hetiera 		= $row['hetiera'];
				$select 		= 'selected';
				$toimitustapa 	= $row['selite'];
			}
			else $select = '';

			echo "<option $select value='$row[selite]'>$row[selite]</option>\n";
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
			$rarow=mysql_fetch_array($rares);
			$rahtisopimus=$rarow['rahtisopimus'];
		}

		if ($otsik['rahtisopimus']!='') $rahtisopimus=$otsik['rahtisopimus'];

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

		if (strtoupper($hetiera) == 'H' or strtoupper($hetiera) == 'K') {
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

		echo "</tr>";

		// jos meill� on hetitulostettava j�lkivaatimus-tilaus niin (annetaan mahdollisuus tulostaa) TULOSTETAAN lasku heti
		if ((strtoupper($hetiera) == 'H' or strtoupper($hetiera) == 'K') and $marow["jv"] != "") {

			echo "<tr><td class='back'><br></td></tr>";
			echo "<tr>";
			echo "<th colspan='3'><font class='message'>".t("Valitse j�lkivaatimuslaskujen tulostuspaikka")."</font></th>";
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
		
		if ($yhtiorow['karayksesta_rahtikirjasyottoon'] == '' or $mista != 'keraa.php') {
			echo "<tr>";
			echo "<th colspan='3'>".t("Osoitelappum��r�")."</th>";
			echo "<td><input type='text' size='4' name='oslappkpl' value='$oslappkpl'>";
			echo "</td></tr>";
		}


		echo "</table>";

		//sitten tehd��n pakkaustietojen sy�tt�...
		echo "<br><font class='message'>".t("Sy�t� tilauksen pakkaustiedot")."</font><hr>";

		echo "<table>";

		$query  = "	SELECT *
					FROM avainsana
					WHERE yhtio	= '$kukarow[yhtio]'
					and laji	= 'pakkaus'
					order by jarjestys";
		$result = mysql_query($query) or pupe_error($query);

		$query  = "	SELECT tuotemassa, varattu
					FROM tilausrivi, tuote
					WHERE tilausrivi.yhtio = tuote.yhtio and tilausrivi.tuoteno = tuote.tuoteno
					and tilausrivi.yhtio = '$kukarow[yhtio]' and otunnus = '$otsik[tunnus]' and ei_saldoa = ''";
		$painoresult = mysql_query($query) or pupe_error($query);

		$puntari	= 0;
		$ylikpl		= 0;
		$nollakpl	= 0;

		if (mysql_num_rows($painoresult) > 0) {
			while ($painorow = mysql_fetch_array($painoresult)) {
				if ($painorow['tuotemassa'] > 0) {
					$massa = $painorow['tuotemassa'] * $painorow['varattu'];
					$puntari += $massa;
					$ylikpl += $painorow['varattu'];
				}
				else {
					$nollakpl += $painorow['varattu'];
				}
			}

			$yhtpaikpl = $ylikpl + $nollakpl;

			if ($ylikpl > 0 and $yhtpaikpl > 0) {
				$osumapros = round(($ylikpl / $yhtpaikpl) * 100,0);
			}
			else {
				$osumapros = 'N/A';
			}

			$puntari = round($puntari,2);

			echo "<font class='message'>".sprintf(t("Tilauksen paino tuoterekisterin tietojen mukaan on: %s KG, %s %%:lle kappaleista on annettu paino."),$puntari,$osumapros)."</font><br>";
		}

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
			
			echo "</tr>";

			$i++;
		}

		echo "</table>";

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
