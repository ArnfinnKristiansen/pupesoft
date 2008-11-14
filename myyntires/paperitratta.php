<?php

	function uusi_karhukierros($yhtio) {
		$query = "SELECT tunnus FROM karhukierros where pvm=current_date and yhtio='$yhtio' and tyyppi='T'";
		$result = mysql_query($query) or pupe_error($query);
		$array = mysql_fetch_array($result);
		if (!mysql_num_rows($result)) {
			$query = "INSERT INTO karhukierros (pvm,yhtio,tyyppi) values (current_date,'$yhtio','T')";
			$result = mysql_query($query) or pupe_error($query);
			$query = "SELECT LAST_INSERT_ID() FROM karhukierros";
			$result = mysql_query($query) or pupe_error($query);
			$array = mysql_fetch_array($result);
		}
		$out = $array[0];
		return $out;

	}

	function liita_lasku($ktunnus,$ltunnus) {
		$query = "INSERT INTO karhu_lasku (ktunnus,ltunnus) values ($ktunnus,$ltunnus)";
		$result = mysql_query($query) or pupe_error($query);
	}

	function alku ($trattakierros_tunnus = '') {
		global $pdf, $asiakastiedot, $yhteyshenkilo, $yhtiorow, $kukarow, $kala, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli;

		$firstpage = $pdf->new_page("a4");
		$pdf->enable('template');
		$tid = $pdf->template->create();
		$pdf->template->size($tid, 600, 830);

		//Haetaan yhteyshenkilon tiedot
		$apuqu = "	select *
					from kuka
					where yhtio='$kukarow[yhtio]' and tunnus='$yhteyshenkilo'";
		$yres = mysql_query($apuqu) or pupe_error($apuqu);
		$yrow = mysql_fetch_array($yres);

		//Otsikko
		//$pdf->draw_rectangle(830, 20,  800, 580, $firstpage, $rectparam);
		$pdf->draw_text(30, 815,  $yhtiorow["nimi"], $firstpage);
		$pdf->draw_text(280, 815, t("TRATTA", $kieli), $firstpage);
		$pdf->draw_text(430, 815, t("Sivu", $kieli)." ".$sivu, 	$firstpage, $norm);

		//Vasen sarake
		//$pdf->draw_rectangle(737, 20,  674, 300, $firstpage, $rectparam);
		$pdf->draw_text(50, 729, t("Laskutusosoite", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(50, 717, $asiakastiedot["nimi"], 		$firstpage, $norm);
		$pdf->draw_text(50, 707, $asiakastiedot["nimitark"], 	$firstpage, $norm);
		$pdf->draw_text(50, 697, $asiakastiedot["osoite"], 		$firstpage, $norm);
		$pdf->draw_text(50, 687, $asiakastiedot["postino"]." ".$asiakastiedot["postitp"], $firstpage, $norm);
		$pdf->draw_text(50, 677, $asiakastiedot["maa"], 		$firstpage, $norm);

		$pdf->draw_rectangle(737, 300, 716, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 729, t("Ytunnus/Asiakasnumero", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 717, $asiakastiedot["ytunnus"], 			$firstpage, $norm);


		//Oikea sarake
		$pdf->draw_rectangle(800, 300, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_rectangle(800, 420, 779, 580, 				$firstpage, $rectparam);
		$pdf->draw_text(310, 792, t("P�iv�m��r�", $kieli), 		$firstpage, $pieni);

		if ($trattakierros_tunnus != "") {
			$query = "	SELECT pvm 
						FROM karhukierros 
						WHERE tunnus = '$trattakierros_tunnus'
						LIMIT 1";
			$pvm_result = mysql_query($query) or pupe_error($query);
			$pvm_row = mysql_fetch_array($pvm_result);
			$paiva = substr($pvm_row["pvm"], 8, 2);
			$kuu   = substr($pvm_row["pvm"], 5, 2);
			$year  = substr($pvm_row["pvm"], 0, 4);			
		}
		else {
			$pvm_row = array();
			$pvm_row["pvm"] = date("Y-m-d");
			$paiva = date("j");
			$kuu   = date("n");
			$year  = date("Y");
		}

		$pdf->draw_text(310, 782, tv1dateconv($pvm_row["pvm"]),	$firstpage, $norm);
		$pdf->draw_text(430, 792, t("Asiaa hoitaa", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(430, 782, $yrow["nimi"], 				$firstpage, $norm);

		$pdf->draw_rectangle(779, 300, 758, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(779, 420, 758, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 771, t("Er�p�iv�", $kieli), $firstpage, $pieni);

		$seurday   = date("j",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seurmonth = date("n",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$seuryear  = date("Y",mktime(0, 0, 0, $kuu, $paiva+7,  $year));
		$pdf->draw_text(310, 761, $seuryear."-".$seurmonth."-".$seurday, $firstpage, $norm);

		$pdf->draw_text(430, 771, t("Puhelin", $kieli), $firstpage, $pieni);
		$pdf->draw_text(430, 761, $yrow["puhno"], 		$firstpage, $norm);

		$pdf->draw_rectangle(758, 300, 737, 580, $firstpage, $rectparam);
		$pdf->draw_rectangle(758, 420, 737, 580, $firstpage, $rectparam);
		$pdf->draw_text(310, 750, t("Viiv�stykorko", $kieli), 	$firstpage, $pieni);
		$pdf->draw_text(310, 740, $yhtiorow["viivastyskorko"], 	$firstpage, $norm);
		$pdf->draw_text(430, 750, t("S�hk�posti", $kieli), 		$firstpage, $pieni);
		$pdf->draw_text(430, 740,  $yrow["eposti"],				$firstpage, $norm);

		//Rivit alkaa t�s� kohtaa
		$kala = 620;

		//Laskurivien otsikkotiedot
		//eka rivi
		$pdf->draw_text(30,  $kala, t("Laskunro", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(120, $kala, t("Laskun pvm", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(200, $kala, t("Er�p�iv�", $kieli),				$firstpage, $pieni);
		$pdf->draw_text(280, $kala, t("My�h�ss� pv", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(340, $kala, t("Viimeisin muistutuspvm", $kieli),$firstpage, $pieni);
		$pdf->draw_text(440, $kala, t("Laskun summa", $kieli),			$firstpage, $pieni);
		$pdf->draw_text(520, $kala, t("Perint�kerta", $kieli),			$firstpage, $pieni);

		$kala -= 15;

		//toka rivi
		if ($kaatosumma != 0 and $sivu == 1) {
			$pdf->draw_text(30,  $kala, t("Kohdistamattomia suorituksia", $kieli),	$firstpage, $norm);
			$pdf->draw_text(440, $kala, $kaatosumma,								$firstpage, $norm);
			$kala -= 13;
		}


		return($firstpage);
	}

	function rivi ($firstpage, $summa) {
		global $firstpage, $pdf, $row, $kala, $sivu, $lask, $rectparam, $norm, $pieni, $lask, $kieli;

		if (($lask == 29 and $sivu == 1) or ($lask == 37 and $sivu > 1)) {
			$sivu++;
			loppu($firstpage,'');
			$firstpage = alku();
			$kala = 605;
			$lask = 1;
		}

		$pdf->draw_text(30,  $kala, $row["laskunro"],	$firstpage, $norm);
		$pdf->draw_text(120, $kala, $row["tapvm"], 		$firstpage, $norm);
		$pdf->draw_text(200, $kala, $row["erpcm"], 		$firstpage, $norm);
		$pdf->draw_text(280, $kala, $row["ika"], 		$firstpage, $norm);
		$pdf->draw_text(340, $kala, $row["kpvm"],		$firstpage, $norm);
		$pdf->draw_text(440, $kala, $row["summa"], 		$firstpage, $norm);
		$pdf->draw_text(520, $kala, $row["karhuttu"]+1, $firstpage, $norm);
		$kala = $kala - 13;

		$lask++;
		$summa+=$row["summa"];
		return($summa);
	}


	function loppu ($firstpage, $summa) {

		global $pdf, $yhtiorow, $kukarow, $sivu, $rectparam, $norm, $pieni, $kaatosumma, $kieli, $maksuehtotiedot;

		//yhteens�rivi
		$pdf->draw_rectangle(110, 20, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 207, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 394, 90, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(110, 540, 90, 580,	$firstpage, $rectparam);

		$pdf->draw_text(404, 92,  t("YHTEENS�", $kieli).":",	$firstpage, $norm);
		$pdf->draw_text(464, 92,  $summa,						$firstpage, $norm);
		$pdf->draw_text(550, 92,  $yhtiorow["valkoodi"],		$firstpage, $norm);

		//Pankkiyhteystiedot
		$pdf->draw_rectangle(90, 20, 20, 580,	$firstpage, $rectparam);

		if ($maksuehtotiedot["pankkinimi1"] != "") {
			$yhtiorow["pankkinimi1"]	= $maksuehtotiedot["pankkinimi1"];
			$yhtiorow["pankkitili1"]	= $maksuehtotiedot["pankkitili1"];
			$yhtiorow["pankkiiban1"]	= $maksuehtotiedot["pankkiiban1"];
			$yhtiorow["pankkiswift1"]	= $maksuehtotiedot["pankkiswift1"];
			$yhtiorow["pankkinimi2"]	= $maksuehtotiedot["pankkinimi2"];
			$yhtiorow["pankkitili2"]	= $maksuehtotiedot["pankkitili2"];
			$yhtiorow["pankkiiban2"]	= $maksuehtotiedot["pankkiiban2"];
			$yhtiorow["pankkiswift2"]	= $maksuehtotiedot["pankkiswift2"];
			$yhtiorow["pankkinimi3"]	= $maksuehtotiedot["pankkinimi3"];
			$yhtiorow["pankkitili3"]	= $maksuehtotiedot["pankkitili3"];
			$yhtiorow["pankkiiban3"]	= $maksuehtotiedot["pankkiiban3"];
			$yhtiorow["pankkiswift3"]	= $maksuehtotiedot["pankkiswift3"];
		}

		$pdf->draw_text(30, 82,  t("Pankkiyhteys", $kieli),	$firstpage, $pieni);
		$pdf->draw_text(30, 72,  $yhtiorow["pankkinimi1"],	$firstpage, $norm);
		$pdf->draw_text(80, 72,  $yhtiorow["pankkitili1"],	$firstpage, $norm);
		$pdf->draw_text(217, 72, $yhtiorow["pankkinimi2"],	$firstpage, $norm);
		$pdf->draw_text(257, 72, $yhtiorow["pankkitili2"],	$firstpage, $norm);
		$pdf->draw_text(404, 72, $yhtiorow["pankkinimi3"],	$firstpage, $norm);
		$pdf->draw_text(444, 72, $yhtiorow["pankkitili3"],	$firstpage, $norm);


		//Alimmat kolme laatikkoa, yhti�tietoja
		$pdf->draw_rectangle(70, 20, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 207, 20, 580,	$firstpage, $rectparam);
		$pdf->draw_rectangle(70, 394, 20, 580,	$firstpage, $rectparam);

		$pdf->draw_text(30, 55, $yhtiorow["nimi"],		$firstpage, $pieni);
		$pdf->draw_text(30, 45, $yhtiorow["osoite"],	$firstpage, $pieni);
		$pdf->draw_text(30, 35, $yhtiorow["postino"]."  ".$yhtiorow["postitp"],	$firstpage, $pieni);
		$pdf->draw_text(30, 25, $yhtiorow["maa"],		$firstpage, $pieni);

		$pdf->draw_text(217, 55, t("Puhelin", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 55, $yhtiorow["puhelin"],			$firstpage, $pieni);
		$pdf->draw_text(217, 45, t("Fax", $kieli).":",			$firstpage, $pieni);
		$pdf->draw_text(247, 45, $yhtiorow["fax"],				$firstpage, $pieni);
		$pdf->draw_text(217, 35, t("Email", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(247, 35, $yhtiorow["email"],			$firstpage, $pieni);

		$pdf->draw_text(404, 55, t("Y-tunnus", $kieli).":",		$firstpage, $pieni);
		$pdf->draw_text(444, 55, $yhtiorow["ytunnus"],			$firstpage, $pieni);
		$pdf->draw_text(404, 45, t("Kotipaikka", $kieli).":",	$firstpage, $pieni);
		$pdf->draw_text(444, 45, $yhtiorow["kotipaikka"],		$firstpage, $pieni);
		$pdf->draw_text(404, 35, t("Enn.per.rek", $kieli),		$firstpage, $pieni);
		$pdf->draw_text(404, 25, t("Alv.rek", $kieli),			$firstpage, $pieni);

	}

//	require('../inc/parametrit.inc');

	require('pdflib/phppdflib.class.php');

	//echo "<font class='message'>Karhukirje tulostuu...</font>";
	flush();

	//PDF parametrit
	$pdf = new pdffile;
	$pdf->set_default('margin-top', 	0);
	$pdf->set_default('margin-bottom', 	0);
	$pdf->set_default('margin-left', 	0);
	$pdf->set_default('margin-right', 	0);
	$rectparam["width"] = 0.3;

	$norm["height"] = 10;
	$norm["font"] = "Times-Roman";

	$pieni["height"] = 8;
	$pieni["font"] = "Times-Roman";

	// defaultteja
	$lask = 1;
	$sivu = 1;

	// aloitellaan laskun teko
	$xquery='';
	for ($i=0; $i<sizeof($lasku_tunnus); $i++) {
		if($i != 0) {
			$xquery=$xquery . ",";
		}

		$xquery .= "$lasku_tunnus[$i]";
	}

	if ($nayta_pdf == 1 and $karhutunnus != '') {
		$karhutunnus = mysql_real_escape_string($karhutunnus);
		$kjoinlisa = " and kl.ktunnus = '$karhutunnus' ";
	}
	else {
		$kjoinlisa = "";
	}

	$query = "	SELECT l.tunnus, l.tapvm, l.liitostunnus,
				l.summa-l.saldo_maksettu summa, l.erpcm, l.laskunro,
				TO_DAYS(now()) - TO_DAYS(l.erpcm) as ika, max(kk.pvm) as kpvm, count(distinct kl.ktunnus) as karhuttu
				FROM lasku l
				LEFT JOIN karhu_lasku kl on (l.tunnus=kl.ltunnus $kjoinlisa)
				LEFT JOIN karhukierros kk on (kk.tunnus=kl.ktunnus)
				WHERE l.tunnus in ($xquery) and l.yhtio='$kukarow[yhtio]' and l.tila='U'
				GROUP BY 1
				ORDER BY l.erpcm";
	$result = mysql_query($query) or pupe_error($query);

	//otetaan maksuehto- ja asiakastiedot ekalta laskulta
	$laskutiedot = mysql_fetch_array($result);

	$query = "	SELECT *
				FROM maksuehto
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[maksuehto]'";
	$maksuehtoresult = mysql_query($query) or pupe_error($query);
	$maksuehtotiedot = mysql_fetch_array($maksuehtoresult);

	$query = "	SELECT *
				FROM asiakas
				WHERE yhtio='$kukarow[yhtio]' AND tunnus = '$laskutiedot[liitostunnus]'";
	$asiakasresult = mysql_query($query) or pupe_error($query);
	$asiakastiedot = mysql_fetch_array($asiakasresult);

	//Otetaan t�ss� asiakkaan kieli talteen
	$kieli = $asiakastiedot["kieli"];

	//ja kelataan akuun
	mysql_data_seek($result,0);

	$query = "	SELECT GROUP_CONCAT(distinct liitostunnus) liitokset
				FROM lasku
				WHERE lasku.yhtio = '$kukarow[yhtio]'
				and lasku.tunnus in ($xquery)";
	$lires = mysql_query($query) or pupe_error($query);
	$lirow = mysql_fetch_array($lires);

	$query = "	SELECT SUM(summa) summa
				FROM suoritus
				WHERE yhtio  = '$kukarow[yhtio]'
				and ltunnus <> 0
				and asiakas_tunnus in ($lirow[liitokset])";
	$summaresult = mysql_query($query) or pupe_error($query);
	$kaato = mysql_fetch_array($summaresult);

	$kaatosumma=$kaato["summa"] * -1;
	if (!$kaatosumma) $kaatosumma='0.00';

	if ($tee != 'tulosta_tratta') {
		$karhukierros=uusi_karhukierros($kukarow['yhtio']);
	}

	$firstpage = alku($karhutunnus);

	$summa=0.0;
	while ($row = mysql_fetch_array($result)) {
		if ($tee != 'tulosta_tratta') {
			liita_lasku($karhukierros,$row['tunnus']);
		}

		$summa=rivi($firstpage,$summa);
	}

	$loppusumma = sprintf('%.2f', $summa+$kaatosumma);

	loppu($firstpage,$loppusumma);

	//keksit��n uudelle failille joku varmasti uniikki nimi:
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$pdffilenimi = "/tmp/tratta-".md5(uniqid(mt_rand(), true)).".pdf";

	//kirjoitetaan pdf faili levylle..
	$fh = fopen($pdffilenimi, "w");
	if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus ep�onnistui $pdffilenimi");
	fclose($fh);

	if ($nayta_pdf == 1) {
		echo file_get_contents($pdffilenimi);
	}

	if ($nayta_pdf != 1 and $tee != 'tulosta_tratta') {
		// itse print komento...
		$query = "	select komento
					from kirjoittimet
					where yhtio='$kukarow[yhtio]' and tunnus = '$kukarow[kirjoitin]'";
		$kires = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($kires) == 1) {
			$kirow=mysql_fetch_array($kires);
			$line = exec("$kirow[komento] $pdffilenimi");
		}
	}
?>
