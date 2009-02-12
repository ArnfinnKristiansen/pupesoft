<?php

function tarkistapariteetti($jono) {

// Jaetaan luku kahden tavun lohkoihin ja sijoitetaan lohkot taulukkoon

	$k=0;

	while(strlen($jono) > 0) {
		$data[$k] = substr($jono,0,2);
		$jono = substr($jono,2);
		$k++;
		//echo "$jono\n";
	}

	$jono="";
	//echo "Jonon koko on " . sizeof($data) . "\n";

	// Tutkitaan lohko kerrallaan lohkon pariteetit
	for($j = 0; $j < 8; $j++) {
		$i = 0;
		$apu = $data[$j];
		//echo "$j Tarkistettava $apu -->";
		// Muutetaan lohko oikeaan muotoon
		$apu = hexdec($apu);
		$apu = decbin($apu);
		//echo " $apu";
		// Lasketaan ykkösten määrä
		$i = 0;
		for($m = 0; $m < 8; $m++) {
			if ($apu[$m] == '1') $i++;
		}

		// Jos ykkösiä parillinen määrä muutetaan viimeistä merkkiä
		if(!($i % 2)){
			//echo " on parillinen ja koko on ". strlen($apu);
			$vmerkki = strlen($apu) - 1;
			// Jos viimeinen on ykkönen niin muutetaan se nollaksi
			if($apu[$vmerkki] == 1) {
				$apu[$vmerkki] = 0;
			}
			// Jos viimeinen on nolla muutetaan se ykköseksi
			else {
				$apu[$vmerkki] = 1;
			}
		}

		// Muutetaan lohko oikeaan muotoon
		//echo " lopullinen on $apu";
		$apu = bindec($apu);
		$apu = dechex($apu);
		if (strlen($apu) == 1) $apu = "0" . $apu;
		$data[$j] = $apu;
		//echo " --> $apu\n";;
	}

	// Yhdistetään lohkot takaisin luvuksi
	for($i = 0; $i <= sizeof($data); $i++) {
		$jono .= $data[$i];
	}
	//echo "Lopullinen tulos $jono\n";
	//Palautetaan luku
	return $jono;
}

$pa1 = tarkistapariteetti($argv[1]);
$pa2 = tarkistapariteetti($argv[2]);

if(($pa1 != $argv[1]) or ($pa2 != $argv[2])){
	echo "\nPariteetin tarkastus ei mennyt läpi. Pariteetti asetettu väärin!";
	exit;
}

// xor on vähän vaikea?
$tulos = '';
for($i = 0; $i < 8; $i++) {

	$pala1 = hexdec(substr($pa1, 2*$i, 2));
	$pala2 = hexdec(substr($pa2, 2*$i, 2));

	$uusipala = $pala1 ^ $pala2;
	$uusipala = dechex($uusipala);
	if (strlen($uusipala) == 1) $uusipala = "0".$uusipala;
	
	//echo "\n$pala1 xor $pala2 = $uusipala";
	
	$tulos .= $uusipala;
}

$tulos = tarkistapariteetti($tulos);
//echo "\n$tulos";

$tulos = pack('H*',$tulos);
$td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), pack('H*','0000000000000000'));
mcrypt_generic_init($td, $tulos, $iv);
$tulos = mcrypt_generic($td, pack('H*','0000000000000000'));
mcrypt_generic_deinit($td);
mcrypt_module_close($td);

$tulos=unpack('H*',$tulos);

echo "\nlopullinen tulos '$tulos[1]'\n";


?>
