<?php
	// estet��n sivun lataus suoraan
	if (!empty($HTTP_GET_VARS["oikeus"]) ||
	    !empty($HTTP_POST_VARS["oikeus"]) ||
	    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
	    !isset($oikeus)) {

	  echo "<p>".t("Kielletty toiminto")."!</p>";
	  exit;
	}

	$laskutunnukset = "";
	$laskutunnuksetkale = "";
	
	// $lasku_tunnukset[]
	if (is_array($lasku_tunnukset)){	
		for ($i=0;$i<sizeof($lasku_tunnukset);$i++) {
			if($i!=0) $laskutunnukset=$laskutunnukset . ",";
			$laskutunnukset=$laskutunnukset . "$lasku_tunnukset[$i]";
		}
	}
	else {
		$laskutunnukset = 0;
	}
	
	// $lasku_tunnukset_kale[]
	if (is_array($lasku_tunnukset_kale)) {
		for ($i=0;$i<sizeof($lasku_tunnukset_kale);$i++) {
			if($i!=0) $laskutunnuksetkale=$laskutunnuksetkale . ",";
			$laskutunnuksetkale=$laskutunnuksetkale . "$lasku_tunnukset_kale[$i]";
		}
	}
	else {
		$laskutunnuksetkale = 0;
	}

	// Tarkistetaan muutama asia
	if ($laskutunnukset == 0 and $laskutunnuksetkale == 0) {
		echo "<font class='error'>".t("Olet kohdistamassa, mutta et ole valinnut mit��n kohdistettavaa")."!</font>";
		exit;
	}	

	if ($osasuoritus == 1) {
		if (sizeof($lasku_tunnukset) != 1) {
			echo "<font class='error'>".t("Osasuoritukseen ei ole valittu yht� laskua")."</font>";
			exit;
		}
		if (sizeof($lasku_tunnukset_kale) > 0) {
			echo "<font class='error'>".t("Osasuoritukseen ei voi valita k�teisalennusta")."</font>";
			exit;
		}
	}

	$query = "LOCK TABLES yriti READ, yhtio READ, tili READ, lasku WRITE, suoritus WRITE, tiliointi WRITE, sanakirja WRITE";
	$result = mysql_query($query) or pupe_error($query);

	// haetaan suorituksen tiedot
	$query = "	SELECT suoritus.tunnus tunnus, 
				suoritus.asiakas_tunnus asiakas_tunnus, 
				suoritus.tilino tilino,
				suoritus.summa summa, 
				suoritus.valkoodi valkoodi,
				suoritus.kurssi kurssi,
				suoritus.asiakas_tunnus asiakastunnus,
				suoritus.kirjpvm maksupvm,
				suoritus.ltunnus ltunnus,
				suoritus.nimi_maksaja nimi_maksaja,
				
				yriti.oletus_rahatili kassatilino, 
				tiliointi.tilino myyntisaamiset_tilino,
				yhtio.myynninkassaale kassa_ale_tilino,
				yhtio.pyoristys pyoristys_tilino,
				yhtio.myynninvaluuttaero myynninvaluuttaero_tilino
				
				
				FROM suoritus, yriti, yhtio, tiliointi
				WHERE suoritus.ltunnus!=0 AND 
				suoritus.tunnus='$suoritus_tunnus' AND
				yriti.yhtio='$kukarow[yhtio]' AND
				yhtio.yhtio=yriti.yhtio AND
				yriti.tilino=suoritus.tilino AND
				suoritus.kohdpvm='0000-00-00' AND
				tiliointi.yhtio='$kukarow[yhtio]' AND
				tiliointi.tunnus=suoritus.ltunnus AND
				tiliointi.korjattu=''";
	$result = mysql_query($query) or pupe_error($query);
	$suoritus = mysql_fetch_array ($result) or pupe_error('<br>Joku suoritukseen liittyv� tieto on kateissa (t�m� on paha ongelma)'. $query);

	// otetaan talteen, jos suorituksen kassatilill� on kustannuspaikka.. tarvitaan jos suoritukselle j�� saldoa
	$query = "select * from tiliointi WHERE aputunnus='$suoritus[ltunnus]' AND yhtio='$kukarow[yhtio]' and tilino='$suoritus[kassatilino]' and korjattu=''";
	$result = mysql_query($query) or pupe_error($query);
	$apurow = mysql_fetch_array($result);
	$apukustp = $apurow["kustp"];

	// haetaan laskujen tiedot
	$laskujen_summa=0;

	if ($osasuoritus == 1) {
		//*** T�ss� yritet��n hoitaa osasuoritus mahdollisimman elegantisti ***

		//Haetaan osasuoritettava lasku
		$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 0 AS alennus, tunnus, vienti_kurssi 
					FROM lasku 
					WHERE tunnus = '$laskutunnukset' 
					and  mapvm='0000-00-00'";
		$result = mysql_query($query) or pupe_error($query);
	
		if (mysql_num_rows($result) != 1) {
			echo "<font class='error'>".t("Osasuoritettava lasku katosi! (joku maksoi sen sinua ennen?)")."</font><br>";
			exit;
		}
		$lasku = mysql_fetch_array($result);
	
		$ltunnus			= $lasku["tunnus"];
		$maksupvm			= $suoritus["maksupvm"];
		$suoritussumma		= $suoritus["summa"];
		$suoritussummaval	= $suoritus["summa"];

		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

		require ("manuaalinen_suoritusten_kohdistus_tee_korkolasku.php");

		// Aloitetaan kirjanpidon kirjaukset
		// Kassatili
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritussumma, '$ltunnus','Manuaalisesti kohdistettu suoritus (osasuoritus)','$apukustp')";
		$result = mysql_query($query) or pupe_error($query);
        
		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $lasku["vienti_kurssi"],2);
	
		// Myyntisaamiset
		$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
	            	VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myyntisaamiset_tilino]', -1 * $suoritussumma,'Manuaalisesti kohdistettu suoritus (osasuoritus)')";
		$result = mysql_query($query) or pupe_error($query);	
	
		// Suoritetaan valuuttalaskua
		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$valuuttaero = round($suoritussummaval * $lasku["vienti_kurssi"],2) - round($suoritussummaval * $suoritus["kurssi"],2);
			
			// Tuliko valuuttaeroa?
			if (abs($valuuttaero) >= 0.01) {
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
		            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$ltunnus', '$suoritus[myynninvaluuttaero_tilino]', $valuuttaero,'Manuaalisesti kohdistettu suoritus (osasuoritus)')";
				$result = mysql_query($query) or pupe_error($query);				
			}
			
			$query = "	UPDATE lasku 
						SET saldo_maksettu_valuutassa=saldo_maksettu_valuutassa+$suoritussummaval 
						WHERE tunnus=$ltunnus 
						AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
		
			// Jos t�m�n suorituksen j�lkeen ei en�� j�� maksettavaa valuutassa
			if ($lasku["summa_valuutassa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}
		else {
			//jos t�m�n suorituksen j�lkeen ei en�� j�� maksettavaa niin merkataan lasku maksetuksi
			if ($lasku["summa"] == $suoritus["summa"]) {
				 $lisa = ", mapvm=now()";
			}
		}
		
		$query = "	UPDATE lasku 
					SET viikorkoeur = '$korkosumma', saldo_maksettu=saldo_maksettu+$suoritussumma $lisa 
					WHERE tunnus	= $ltunnus 
					AND yhtio		= '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		//Merkataan suoritus k�ytetyksi ja yliviivataan sen tili�innit
		$query = "UPDATE suoritus SET kohdpvm=now(), summa=0 WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)	
		$query = "SELECT aputunnus, ltunnus FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
				
		if (mysql_num_rows($result) != 1) {
			die ("Tili�inti1 kateissa " . $suoritus["tunnus"]);
		}
		$tiliointi = mysql_fetch_array ($result);
	
		$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		//echo "<font class='message'>$query</font><br>";
	
		if (mysql_num_rows($result) != 1) {
			echo "Tili�inti2 kateissa " . $suoritus["tunnus"];
			exit;
		}
	
		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
			
		$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	}
	
	else {
		//*** T�ss� k�sitell��n tavallinen (ja paljon monimutkaisempi) suoritus ***
		$laskujen_summa = 0;

		if($laskutunnukset != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, 0 AS alennus, 0 AS alennus_valuutassa, tunnus, vienti_kurssi, tapvm 
						FROM lasku WHERE tunnus IN ($laskutunnukset) 
						and yhtio = '$kukarow[yhtio]' 
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
				    
			if (mysql_num_rows($result) != sizeof($lasku_tunnukset)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset)."'</font><br>";
				exit;
			}
		
			while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+=$lasku["summa"];
				$laskujen_summa_valuutassa	+=$lasku["summa_valuutassa"];
			}
		}
	
		// Alennukset
		if($laskutunnuksetkale != 0) {
			$query = "	SELECT summa - saldo_maksettu AS summa, summa_valuutassa - saldo_maksettu_valuutassa AS summa_valuutassa, kasumma AS alennus, kasumma_valuutassa AS alennus_valuutassa, tunnus, vienti_kurssi, tapvm  
						FROM lasku WHERE tunnus IN ($laskutunnuksetkale) 
						AND yhtio = '$kukarow[yhtio]' 
						and mapvm = '0000-00-00'";
			$result = mysql_query($query) or pupe_error($query);
				    
			if (mysql_num_rows($result) != sizeof($lasku_tunnukset_kale)) {
				echo "<font class='error'>".t("Joku laskuista katosi (joku maksoi sen sinua ennen?)")." '".mysql_num_rows($result)."' '".sizeof($lasku_tunnukset_kale)."'</font><br>";
				exit;
			}

		    while($lasku = mysql_fetch_array($result)){
				$laskut[] = $lasku;
				$laskujen_summa				+= $lasku["summa"] - $lasku["alennus"];
				$laskujen_summa_valuutassa	+= $lasku["summa_valuutassa"] - $lasku["alennus_valuutassa"];
			}
		}
		
		if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa_valuutassa,2);
			
			echo "<font class='message'>".t("Tilitapahtumalle j�� py�ristyksen j�lkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}
		else {
			$kaatosumma = round($suoritus["summa"] - $laskujen_summa,2);
			
			echo "<font class='message'>".t("Tilitapahtumalle j�� py�ristyksen j�lkeen")." $kaatosumma $suoritus[valkoodi]</font><br>";
		}
		
		//Jos heittoa ja kirjataan kassa-alennuksiin etsit��n joku sopiva lasku (=iso summa)
		if($kaatosumma != 0 and $pyoristys_virhe_ok == 1) {
			echo "<font class='message'>".t("Kirjataan kassa-aleen")."</font> ";
			
			$query = "	SELECT tunnus, laskunro 
						FROM lasku 
						WHERE tunnus IN ($laskutunnukset,$laskutunnuksetkale) 
						AND yhtio = '$kukarow[yhtio]' 
						ORDER BY summa desc 
						LIMIT 1";
			$result = mysql_query($query) or pupe_error($query);
					
			if (mysql_num_rows($result) == 0) {
				echo "<font class='error'>".t("Kaikki laskut katosivat")." (sys err)</font<br>";
				exit;
			}
			else {
				$kohdistuslasku = mysql_fetch_array($result);
			}	    
		}

		// Tili�id��n myyntisaamiset
		if (is_array($laskut)) {
			
			$kassaan = 0;
			
			foreach ($laskut as $lasku) {
			
				// lasketaan korko
				$ltunnus			= $lasku["tunnus"];
				$maksupvm			= $suoritus["maksupvm"];
				$suoritussumma		= $suoritus["summa"];
				$suoritussummaval	= $suoritus["summa"];

				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) $suoritussumma = round($suoritussummaval * $suoritus["kurssi"],2);

				require ("manuaalinen_suoritusten_kohdistus_tee_korkolasku.php");
		      	
				//Kohdistammeko py�ristykset ym:t t�h�n?
			 	if($kaatosumma != 0 and $pyoristys_virhe_ok == 1 and $lasku["tunnus"] == $kohdistuslasku["tunnus"]) {
			 		echo "<font class='message'>".t("Sijoitin lis�kassa-alen laskulle").": $kohdistuslasku[laskunro]</font> ";
			
					if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
						$lasku["alennus_valuutassa"] = round($lasku["alennus_valuutassa"] - $kaatosumma,2);
						
						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus_valuutassa] $suoritus[valkoodi]</font> ";
					}
					else {
						$lasku["alennus"] = round($lasku["alennus"] - $kaatosumma,2);
						
						echo "<font class='message'>".t("Uusi kassa-ale").": $lasku[alennus] $suoritus[valkoodi]</font> ";
					}
										
					$kaatosumma = 0;
			 	}
								
				// Tehd��n valuuttakonversio kassa-alennukselle 
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$lasku["alennus"] = round($lasku["alennus_valuutassa"] * $suoritus["kurssi"],2);
				}
								
				// Tili�id��n kassa-alennukset
				// Kassa-alessa on huomioitava alv, joka voi olla useita vientej�
				if($lasku["alennus"] != 0) {

					$totkasumma = 0;
					$query = "	SELECT * from tiliointi 
								WHERE ltunnus='$lasku[tunnus]' 
								and yhtio 	= '$kukarow[yhtio]'
								and tapvm 	= '$lasku[tapvm]'  
								and tilino	<> $yhtiorow[myyntisaamiset] 
								and tilino	<> $yhtiorow[konsernimyyntisaamiset] 
								and tilino	<> $yhtiorow[alv] 
								and tilino	<> $yhtiorow[varasto] 
								and tilino	<> $yhtiorow[varastonmuutos] 
								and tilino	<> $yhtiorow[pyoristys] 
								and tilino	<> $yhtiorow[factoringsaamiset]
								and korjattu = ''";
					$yresult = mysql_query($query) or pupe_error($query);
					//echo "<font class='message'>Kassa-ale alv etsint�: $query</font><br>";
	
					if (mysql_num_rows($yresult) == 0) { // Jotain meni pahasti pieleen
						echo "<font class='error'>".t("En l�yt�nyt laskun myynnin vientej�! Alv varmaankin heitt��")."</font> ";
						
						$query="INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero)
								VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', '$lasku[alennus]', 'Manuaalisesti kohdistettu suoritus (alv ongelma)', '0')";
						$result = mysql_query($query) or pupe_error($query);						
					}
					else {
						while ($tiliointirow=mysql_fetch_array ($yresult)) {
							// Kuinka paljon on t�m�n viennin osuus
							$summa = round($tiliointirow['summa'] * (1+$tiliointirow['vero']/100) * -1 / $lasku["summa"] * $lasku["alennus"],2);
	
							if ($tiliointirow['vero'] != 0) { // Netotetaan alvi
								//$alv:ssa on alennuksen alv:n maara
								$alv = round($summa - $summa / (1 + ($tiliointirow['vero'] / 100)),2);
								//$summa on alviton alennus
								$summa -= $alv;
							}
							// Kuinka paljon olemme kumulatiivisesti tili�ineet
							$totkasumma += $summa + $alv;
							
							$query="INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite, vero)
									VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[kassa_ale_tilino]', $summa, 'Manuaalisesti kohdistettu suoritus', '$tiliointirow[vero]')";
							$result = mysql_query($query) or pupe_error($query);
							
							//echo "<font class='message'>Kassa-alet: $query</font><br>";
	
							$isa = mysql_insert_id ($link); // N�in l�yd�mme t�h�n liittyv�t alvit....
	
							if ($tiliointirow['vero'] != 0) {
	
								$query = "	INSERT into tiliointi set
											yhtio ='$kukarow[yhtio]',
											ltunnus = '$lasku[tunnus]',
											tilino = '$yhtiorow[alv]',
											tapvm = '$suoritus[maksupvm]',
											summa = $alv,
											vero = '',
											selite = '$selite',
											lukko = '1',
											laatija = '$kukarow[kuka]',
											laadittu = now(),
											aputunnus = $isa";
								$xresult = mysql_query($query) or pupe_error($query);
								
								//echo "<font class='message'>Kassa-alen alv: $query</font><br>";
							}
						}
					
						//Hoidetaan mahdolliset py�ristykset
						$heitto = $totkasumma - $lasku["alennus"];
						if (abs($heitto) >= 0.01) {
							echo "<font class='message'>".t("Kassa-alvpy�ristys")." $heitto</font> ";
							
							$query = "	UPDATE tiliointi SET summa = summa - $totkasumma + $lasku[alennus]
										WHERE tunnus = '$isa' and yhtio='$kukarow[yhtio]'";
							$xresult = mysql_query($query) or pupe_error($query);
							
							$isa=0; //V�h�n turvaa
						}
					}
				}
				
				// Tehd��n valuuttakonversio kassasuoritukselle
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {
					$suoritettu_kassaan = round(($lasku["summa_valuutassa"] * $suoritus["kurssi"])-$lasku["alennus"], 2);
				}
				else {
					$suoritettu_kassaan = $lasku["summa"] - $lasku["alennus"];
				}
									        	
				// Kassatili
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, tilino, summa, ltunnus, selite, kustp)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]','$suoritus[kassatilino]', $suoritettu_kassaan, $lasku[tunnus], 'Manuaalisesti kohdistettu suoritus','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);
				
				// Lasketaan summasummarum paljonko ollaan tili�ity kassaan
				$kassaan += $suoritettu_kassaan;
				
				// Myyntisaamiset
				$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
							VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myyntisaamiset_tilino]', -1*$lasku[summa], 'Manuaalisesti kohdistettu suoritus')";
				$result = mysql_query($query) or pupe_error($query);
								
				// Tuliko valuuttaeroa?
				if (strtoupper($suoritus["valkoodi"]) != strtoupper($yhtiorow['valkoodi'])) {

					$valero = round($lasku["summa"] - $suoritettu_kassaan - $lasku["alennus"], 2);

					if (abs($valero) >= 0.01) {
						$query = "	INSERT INTO tiliointi(yhtio, laatija, laadittu, tapvm, ltunnus, tilino, summa, selite)
				            		VALUES ('$kukarow[yhtio]','$kukarow[kuka]',now(), '$suoritus[maksupvm]', '$lasku[tunnus]', '$suoritus[myynninvaluuttaero_tilino]', $valero, 'Manuaalisesti kohdistettu suoritus')";
						$result = mysql_query($query) or pupe_error($query);
					}
				}
				
				$query = "	UPDATE lasku 
							SET mapvm='$suoritus[maksupvm]',  viikorkoeur='$korkosumma', saldo_maksettu=0, saldo_maksettu_valuutassa=0 
							WHERE tunnus = $lasku[tunnus] 
							AND yhtio	 = '$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);
			}

			$query = "UPDATE suoritus SET kohdpvm=now(), summa=$kaatosumma WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
			
			// Luetaan ketjussa olevat tapahtumat ja poistetaan ne (=merkataan korjatuksi)
			$query = "SELECT aputunnus, ltunnus, summa FROM tiliointi WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($result) != 1) {
				die ("Tili�inti1 kateissa " . $suoritus["tunnus"]);
			}
			$tiliointi = mysql_fetch_array ($result);
			
			$query = "SELECT tunnus FROM tiliointi WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
						
			if (mysql_num_rows($result) != 1) {
				die ("Tili�inti2 kateissa " . $suoritus["tunnus"]);
			}

			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE tunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
						
			$query = "UPDATE tiliointi SET korjausaika=now(), korjattu='$kukarow[kuka]' WHERE aputunnus=$suoritus[ltunnus] AND yhtio='$kukarow[yhtio]'";
			$result = mysql_query($query) or pupe_error($query);
							
			// J��k� suoritukselle viel� saldoa
			$erotus = round($tiliointi["summa"] + $kassaan, 2);
						
			if ($erotus != 0) {
				//Myyntisaamiset
				$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[myyntisaamiset_tilino]', $erotus,'K�sin sy�tetty suoritus')";		
				$result = mysql_query($query) or pupe_error($query);
				$ttunnus = mysql_insert_id($link);
							
				//Kassatili
				$query="INSERT INTO tiliointi(yhtio,laatija,laadittu,tapvm,ltunnus,tilino,summa,selite,aputunnus,lukko,kustp) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$suoritus[maksupvm]','$tiliointi[ltunnus]','$suoritus[kassatilino]',$erotus*-1,'K�sin sy�tetty suoritus',$ttunnus,'1','$apukustp')";
				$result = mysql_query($query) or pupe_error($query);
				
				// P�ivitet��n osoitin
				$query = "UPDATE suoritus SET ltunnus = '$ttunnus', kohdpvm = '0000-00-00' WHERE tunnus=$suoritus[tunnus] AND yhtio='$kukarow[yhtio]'";
				$result = mysql_query($query) or pupe_error($query);				
			}
		}
	}

	echo "<br><font class='message'>".t("Kohdistus onnistui").".</font><br>";
	
	$query = "UNLOCK TABLES";
	$result = mysql_query($query) or pupe_error($query);
	
	$tila			= "suorituksenvalinta";
	$asiakas_tunnus = $suoritus["asiakas_tunnus"];
?>
