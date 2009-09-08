<?
function siirto ($yritirow, $aineisto, $pvm) {
	global $testaus;

	$nro = $file.=sprintf ('%03d', $yritirow['nro']);

	$pankki = substr($yritirow['tilino'],0,1);
	if ($pankki == '1') $pankki='2';

	// TIEDOSTONIMET
	$osoite="/var/www/html/pupesoft/dataout";
	$etiedosto="ESI.A";
	$sptiedosto="SIIRTOPYYNTO.$nro";
	// Alustetaan ja tehd��n Siirtopyynt�-tiedosto
	// Siirtopyynt��n lis�t��n tilinumero, jolta tiliote haetaan
	// $tili="195030-10";

	echo "Tehd��n siirtopyynt� $osoite/$sptiedosto<br>";

	if ($pvm == '') $pvm = '000000';
	if ($pankki == '') $pankki = '2';

	$siirtopyynto = siirtopyynto($pankki, $yritirow['tilino'], $aineisto, "DEMO", $pvm);
	echo strlen($siirtopyynto). "-->" . $siirtopyynto."<br>";

	echo "Alustetaan ESI-tiedoston teko<br>";
	$esi = sanoma($yritirow, "ESI");
	echo strlen($esi). "-->" . $esi."<br>";

	if ($testaus != '') {
		$esi =">>ESI161120 0000KERMIT      3.01SMH003701234567             99910000011111111        00941015073000001                                          ";
		echo strlen($esi). "-->" . $esi."<br>";
		if (strtoupper($yritirow['kayttoavain']) != "AEBAE983D6406D07") echo "K�ytt�avain on virheellinen $yritirow[kayttoavain]<br>";
		else echo "K�ytt�avain on oikein<br>";
	}

	echo "Suojataan ESI-tiedosto '$yritirow[kayttoavain]'<br>";
	$esi = salaa($esi, "ESIa", $yritirow['kayttoavain']);
	echo strlen($esi). "-->" . $esi."<br>";

	if ($testaus != '') {
		if (substr($esi,-16) != '4B69B6DD4F72C75B') echo "Suojaus ei onnistu ".substr($esi,-16)."<br>";
			else echo "Suojaus ok<br>";
	}

	// Jos ei haluta avainvaihtoa, lis�t��n sanoman per��n nolla	
	$esi .= "0";
	echo "Ei avainvaihtoa<br>";
	echo strlen($esi). "-->" . $esi."<br>";

	// Tiedostojen nimet
	$omaesia= "$osoite/$etiedosto";
	$omaesip= "$osoite/ESI.P";
	$omasiirto= "$osoite/$sptiedosto";
	$omaaineisto = "$osoite/$aineisto.$nro";
	$omakuittaus = "KUITTAUS".$nro;

	//Nordea
	if ($pankki == '2') {
		$pankinesia = "ESI.A";
		$pankinesip= "ESI.P";
		$pankinsiirto = "SIIRTO";
		$pankinaineisto = "$aineisto.$nro";
		$pankinkuittaus = "KUITTAUS.$nro";
	}
	//Sampopankki
	if ($pankki == '8') {
		$pankinesia = "asiakas";
		$pankinesip= "kuittaus";
		$pankinsiirto = "sovellus";
		$pankinaineisto = "aineisto";
		$pankinkuittaus = "kuittaus";
		$lopetus = "//SIFNOFF\n";
		$yhteydenlopetus = $osoite ."/lopetustiedosto"; //Sampopankki
		file_put_contents($yhteydenlopetus,$lopetus);
	}

	file_put_contents($esia, $omaesi);
	file_put_contents($siirto,$omasiirto);

	// FTP-yhteydenottomuuttujat
	if ($pankki == '2') {
		$host="solo.nordea.fi";
		$log="anonymous";
		$pass="SITE PASSIVE";
	}
	if ($pankki == '8') {
		$host="192.49.51.8";
		$log="anonymous";
		$pass="pupesofttestaus";
	}

	echo "Avataan FTP-yhteys $host<br>";
	exit;
	$ftp = ftp_connect($host);
	if($ftp) {
	// Jos jokin asia ep�onnistuu, katkaistaan heti yhteys
	echo "Yhteys muodostettu: $host<br>";
	$login_ok = ftp_login($ftp,$log,$pass);
	ftp_pasv($ftp, true);
	echo "L�hetet��n Esi-sanoma: $esia<br>";
	if(ftp_put($ftp, $pankinesia, $omaesia, FTP_ASCII)) {
			echo "Esi-sanoman l�hetys onnistui. Haetaan vastaus: $esip<br>";
			if(ftp_get($ftp, $omaesip, $pankinesip, FTP_ASCII)) {
					echo  "Esi-sanoman vastaus saatiin.L�hetet��n siirtopyynt�: $siirto<br>";
					if(ftp_put($ftp, $pankinsiirto, $omasiirto, FTP_ASCII)) {
							echo "Siirtopyynt� l�hetettiin. Haetaan aineisto: $aineisto<br>";
							if(ftp_get($ftp, $omaaineisto, $pankinaineisto, FTP_ASCII)) {
									echo "Aineiston haku onnistui. Haetaan kuittaus: $kuittaus<br>";
									if(ftp_get($ftp, $omakuittaus, $pankinkuittaus, FTP_ASCII)) {
										echo "Kuittaus haettu<br>";
										// Sampo haluaa loputustiedoston
										if ($pankki=='8') {
											if(ftp_put($ftp, $lopetus, "lopetus", FTP_ASCII)) {
												echo "Pyydettiin yhteydenlopetus.<br>";
											}
										}
									}
									else {
										echo "Kuittausta ei saatu<br>";
									}
							}
							else {
									echo "Aineiston haku ei onnistunut.<br>";
									echo "Haetaan kuittaus: $pankinkuittaus<br>";
						  			if(ftp_get($ftp, $omakuittaus, $pankinkuittaus, FTP_ASCII)) {
										echo "Kuittaus saatiin<br>";
									}
									else {
										echo "Kuittausta ei saatu<br>";
									}
							}
					}
					else {
						echo "Siirtopyynt� ev�ttiin<br>";
					}
			}
			else {
				echo "Vastausta esi-sanomaan ei saatu<br>";
			}
	  }
	  else {
			echo "Esi-sanoman l�hetys ei onnistunut<br>";
	  }
	  echo "<br>Lopetetaan yhteys";
	  ftp_quit($ftp);
	}
	else {
			echo "Ei yhteytt�!<br>";
	}
	return "";
}
?>
