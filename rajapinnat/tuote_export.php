<?php
	
	require ('../inc/parametrit.inc');
	
	// Haetaan pupesta tuotteen tiedot
	$query = "	SELECT tuote.*,
				try_fi.selitetark try_fi_nimi,
				try_se.selitetark try_se_nimi,
				try_ee.selitetark try_ee_nimi,
				try_en.selitetark try_en_nimi,
				osasto_fi.selitetark osasto_fi_nimi,
				osasto_se.selitetark osasto_se_nimi,
				osasto_ee.selitetark osasto_ee_nimi,
				osasto_en.selitetark osasto_en_nimi,
				ta_nimitys_se.selite nimi_swe, 
				ta_nimitys_en.selite nimi_eng,
				if(t1.jarjestys = 0 or t1.jarjestys is null, 999999, t1.jarjestys) t1j1,
				if(t2.jarjestys = 0 or t2.jarjestys is null, 999999, t2.jarjestys) t2j2,
				if(t3.jarjestys = 0 or t3.jarjestys is null, 999999, t3.jarjestys) t3j3,
				if(tuote.tuotemassa <= 0, 0.01, tuote.tuotemassa) tuotemassa
				FROM tuote
				LEFT JOIN tuotteen_avainsanat t1 on tuote.yhtio = t1.yhtio and tuote.tuoteno = t1.tuoteno and t1.laji = 'parametri_variaatio' and t1.kieli = 'fi'
				LEFT JOIN tuotteen_avainsanat t2 on tuote.yhtio = t2.yhtio and tuote.tuoteno = t2.tuoteno and t2.laji = 'parametri_vari' and t2.kieli = 'fi'
				LEFT JOIN tuotteen_avainsanat t3 on tuote.yhtio = t3.yhtio and tuote.tuoteno = t3.tuoteno and t3.laji = 'parametri_koko' and t3.kieli = 'fi'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_se on tuote.yhtio = ta_kuvaus_se.yhtio and tuote.tuoteno = ta_kuvaus_se.tuoteno and ta_kuvaus_se.laji = 'kuvaus' and ta_kuvaus_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_se on tuote.yhtio = ta_lyhyt_se.yhtio and tuote.tuoteno = ta_lyhyt_se.tuoteno and ta_lyhyt_se.laji = 'lyhytkuvaus' and ta_lyhyt_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_se on tuote.yhtio = ta_mainos_se.yhtio and tuote.tuoteno = ta_mainos_se.tuoteno and ta_mainos_se.laji = 'mainosteksti' and ta_mainos_se.kieli = 'se'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_ru on tuote.yhtio = ta_nimitys_ru.yhtio and tuote.tuoteno = ta_nimitys_ru.tuoteno and ta_nimitys_ru.laji = 'nimitys' and ta_nimitys_ru.kieli = 'ru'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_ru on tuote.yhtio = ta_kuvaus_ru.yhtio and tuote.tuoteno = ta_kuvaus_ru.tuoteno and ta_kuvaus_ru.laji = 'kuvaus' and ta_kuvaus_ru.kieli = 'ru'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_ru on tuote.yhtio = ta_lyhyt_ru.yhtio and tuote.tuoteno = ta_lyhyt_ru.tuoteno and ta_lyhyt_ru.laji = 'lyhytkuvaus' and ta_lyhyt_ru.kieli = 'ru'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_ru on tuote.yhtio = ta_mainos_ru.yhtio and tuote.tuoteno = ta_mainos_ru.tuoteno and ta_mainos_ru.laji = 'mainosteksti' and ta_mainos_ru.kieli = 'ru'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_ee on tuote.yhtio = ta_nimitys_ee.yhtio and tuote.tuoteno = ta_nimitys_ee.tuoteno and ta_nimitys_ee.laji = 'nimitys' and ta_nimitys_ee.kieli = 'ee'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_ee on tuote.yhtio = ta_kuvaus_ee.yhtio and tuote.tuoteno = ta_kuvaus_ee.tuoteno and ta_kuvaus_ee.laji = 'kuvaus' and ta_kuvaus_ee.kieli = 'ee'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_ee on tuote.yhtio = ta_lyhyt_ee.yhtio and tuote.tuoteno = ta_lyhyt_ee.tuoteno and ta_lyhyt_ee.laji = 'lyhytkuvaus' and ta_lyhyt_ee.kieli = 'ee'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_ee on tuote.yhtio = ta_mainos_ee.yhtio and tuote.tuoteno = ta_mainos_ee.tuoteno and ta_mainos_ee.laji = 'mainosteksti' and ta_mainos_ee.kieli = 'ee'
				LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_kuvaus_en on tuote.yhtio = ta_kuvaus_en.yhtio and tuote.tuoteno = ta_kuvaus_en.tuoteno and ta_kuvaus_en.laji = 'kuvaus' and ta_kuvaus_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_lyhyt_en on tuote.yhtio = ta_lyhyt_en.yhtio and tuote.tuoteno = ta_lyhyt_en.tuoteno and ta_lyhyt_en.laji = 'lyhytkuvaus' and ta_lyhyt_en.kieli = 'en'
				LEFT JOIN tuotteen_avainsanat as ta_mainos_en on tuote.yhtio = ta_mainos_en.yhtio and tuote.tuoteno = ta_mainos_en.tuoteno and ta_mainos_en.laji = 'mainosteksti' and ta_mainos_en.kieli = 'en'
				LEFT JOIN avainsana as try_fi ON (try_fi.yhtio = tuote.yhtio and try_fi.selite = tuote.try and try_fi.laji = 'try' and try_fi.kieli = 'fi')
				LEFT JOIN avainsana as try_se ON (try_se.yhtio = tuote.yhtio and try_se.selite = tuote.try and try_se.laji = 'try' and try_se.kieli = 'se')
				LEFT JOIN avainsana as try_ee ON (try_ee.yhtio = tuote.yhtio and try_ee.selite = tuote.try and try_ee.laji = 'try' and try_ee.kieli = 'ee')
				LEFT JOIN avainsana as try_en ON (try_en.yhtio = tuote.yhtio and try_en.selite = tuote.try and try_en.laji = 'try' and try_en.kieli = 'en')	
				LEFT JOIN avainsana as osasto_fi ON (osasto_fi.yhtio = tuote.yhtio and osasto_fi.selite = tuote.osasto and osasto_fi.laji = 'osasto' and osasto_fi.kieli = 'fi')
				LEFT JOIN avainsana as osasto_se ON (osasto_se.yhtio = tuote.yhtio and osasto_se.selite = tuote.osasto and osasto_se.laji = 'osasto' and osasto_se.kieli = 'se')
				LEFT JOIN avainsana as osasto_ee ON (osasto_ee.yhtio = tuote.yhtio and osasto_ee.selite = tuote.osasto and osasto_ee.laji = 'osasto' and osasto_ee.kieli = 'ee')
				LEFT JOIN avainsana as osasto_en ON (osasto_en.yhtio = tuote.yhtio and osasto_en.selite = tuote.osasto and osasto_en.laji = 'osasto' and osasto_en.kieli = 'en')		
				WHERE tuote.yhtio = '{$kukarow["yhtio"]}'
			#	AND tuote.nakyvyys != ''
				AND tuote.status != 'P'
				AND tuote.tuotetyyppi not in ('A','B')
				AND tuote.tuoteno != ''
	 			ORDER BY t1j1, t2j2, t3j3, tuote.tuoteno
				";

	$res = pupe_query($query);
query_dump($query);


	$dnstuote = $dnsryhma = $dnstock = $dnsasiakas = $dnshinnasto = array();

	// loopataan tuotteet
	while ($row = mysql_fetch_array($res)) {
		
		list(,,$myytavissa) = saldo_myytavissa($row["tuoteno"]);
		
		$dnstuote[] = array('tuoteno' => $row["tuoteno"], 'nimi' => $row["nimitys"], 
											'yksikko' => $row["yksikko"], 'myyntihinta' => $row["myyntihinta"],
											'ean' => $row["ean"], 'myytavissa' => $myytavissa,
											'osasto' => $row["osasto"], 'try' => $row["try"],
											'nimi_swe' => $row["nimi_swe"],'nimi_eng' => $row["nimi_eng"],);
										
		$dnstock[] = array('tuoteno' => $row["tuoteno"], 'ean' => $row["ean"], 'myytavissa' => $myytavissa,);

		$dnsryhma[$row["osasto"]][$row["try"]] = array('osasto' => $row["osasto"], 'try' => $row["try"],
											'osasto_fi' => $row["osasto_fi_nimi"], 'try_fi' => $row["try_fi_nimi"],
											'osasto_se' => $row["osasto_se_nimi"], 'try_se' => $row["try_se_nimi"],
											'osasto_en' => $row["osasto_en_nimi"], 'try_en' => $row["try_en_nimi"],
											);
		
		// $dnslajitelma[] = array();
	}
	
	// Haetaan kaikki asiakkaat 
	$query = "	SELECT asiakas.* 
				FROM asiakas 
				WHERE asiakas.yhtio = '{$kukarow["yhtio"]}'
				AND asiakas.laji !='P'";
	$res = pupe_query($query);
	
	// loopataan asiakkaat
	while ($row = mysql_fetch_array($res)) {
		$dnsasiakas[] =   array('nimi' => $row["nimi"], 
								'osoite' => $row["osoite"], 'postino' => $row["postino"],
								'postitp' => $row["postitp"], 'email' => $row["email"],);
	}
	
	// Haetaan kaikki hinnastot
	$query = "	SELECT hinnasto.* 
				FROM hinnasto 
				WHERE hinnasto.yhtio = '{$kukarow["yhtio"]}'
				AND (hinnasto.minkpl = 0 and hinnasto.maxkpl = 0)
				AND hinnasto.laji !='O' 
				AND hinnasto.maa in ('FI','')
				AND hinnasto.valkoodi in ('EUR','')
				";
	$res = pupe_query($query);
	
	// loopataan hinnastot
	while ($row = mysql_fetch_array($res)) {
		$dnshinnasto[] =   array('tuoteno' => $row["tuoteno"], 'selite' => $row["selite"], 'alkupvm' => $row["alkupvm"], 'loppupvm' => $row["loppupvm"], 'hinta' => $row["hinta"],);
	}
	
/*
	echo "TUOTTEET:<pre>",var_dump($dnstuote),"</pre>";
	echo "VARASTO:<pre>",var_dump($dnstock),"</pre>";
	echo "RYHM�:<pre>",var_dump($dnsryhma),"</pre>";
*/	
	// mik� vkauppa
	// --> mit� kutsutaan
	
	echo "Kutsun nyt tuotetta<br>";
	require ("tuotexml.inc");
	echo "Kutsun nyt varastoa<br>";
	require ("varastoxml.inc");
	echo "Kutsun nyt ryhm��<br>";
	require ("ryhmaxml.inc");
	echo "Kutsun nyt asiakasta<br>";
	require ("asiakasxml.inc");
	echo "Kutsun nyt hinnastoa<br>";
	require ("hinnastoxml.inc");
	require ("inc/footer.inc");
?>