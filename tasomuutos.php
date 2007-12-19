<?php

require("inc/parametrit.inc");

echo "<font class='head'>".t("Rakenteen muutos")."</font><hr>";

if ($tee == "lisaalisaa") {
	
	if ($tyyppi == "") {
		echo "<font class='error'>Tyyppi puuttuu!</font><br>";
		
		$tee = "lisaa";
	}
	if ($laji == "") {
		echo "<font class='error'>Laji puuttuu!</font><br>";
		
		$tee = "lisaa";
	}
	if ($uusitaso == "") {
		echo "<font class='error'>Taso puuttuu!</font><br>";
		
		$tee = "lisaa";
	}
	if ($nimi == "") {
		echo "<font class='error'>Nimi puuttuu!</font><br>";
		
		$tee = "lisaa";
	}
	
	if (substr($uusitaso, 0, strlen($taso)) != $taso) {
		echo "<font class='error'>Uuden tason ".strlen($taso)." ensimm�ist� merkki� on oltava: \"$taso\"!</font><br>";
		
		$tee = "lisaa";
	}
	
	if (strlen($uusitaso) != strlen($taso)+1) {
		echo "<font class='error'>Uuden tason pituus on oltava: ".(strlen($taso)+1)."!</font><br>";
		
		$tee = "lisaa";
	}
	
	if ($kirjain != $tyyppi) {
		echo "<font class='error'>Uuden tason tyyppi on oltava: $kirjain!</font><br>";
		
		$tee = "lisaa";
	}
	
	if (substr($taso,0,1) != substr($uusitaso,0,1)) {

		echo "<font class='error'>Uuden tason ensimm�inen merkki on oltava: ".substr($taso,0,1)."!</font><br>";
		
		$tee = "lisaa";
	}
	
	$query = "	SELECT *
				FROM taso
				WHERE yhtio = '$kukarow[yhtio]' 
				and taso = '$uusitaso'";
	$vresult = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($vresult) > 0) {
		echo "<font class='error'>Taso $uusitaso l�ytyy jo j�rjestelm�st�!</font><br>";
		
		$tee = "lisaa";	
	}
	
	if ($tee == "lisaalisaa") {
		if ($sisennys == "K") {
			$query = "	SELECT *
						FROM taso
						WHERE yhtio = '$kukarow[yhtio]' 
						and taso like '$taso%' 
						and tyyppi = '$kirjain'
						ORDER BY CHAR_LENGTH(taso) desc";
			$vresult = mysql_query($query) or pupe_error($query);
	
			while ($vrow = mysql_fetch_array($vresult)) {
				$paivtaso = $taso.substr($uusitaso,-1).substr($vrow["taso"], strlen($taso));
			
				echo "Tasokoodi muuttuu: $vrow[taso] --> $paivtaso<br>";
			
				$query = "	UPDATE taso
							SET taso = '$paivtaso'
							WHERE yhtio = '$kukarow[yhtio]'
							and taso 	= '$vrow[taso]'
							and taso   != '$taso'
							and tyyppi 	= '$kirjain'";
				$res = mysql_query($query) or pupe_error($query);
		
				if ($kirjain == "S") {
					$query = "	UPDATE tili
								SET sisainen_taso = '$paivtaso'
								WHERE yhtio = '$kukarow[yhtio]' 
								and sisainen_taso = '$vrow[taso]'";
					$res = mysql_query($query) or pupe_error($query);
			
					$query = "	UPDATE budjetti
								SET taso = '$paivtaso'
								WHERE yhtio = '$kukarow[yhtio]' 
								and taso = '$vrow[taso]'";
					$res = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "	UPDATE tili
								SET ulkoinen_taso = '$paivtaso'
								WHERE yhtio = '$kukarow[yhtio]' 
								and ulkoinen_taso = '$vrow[taso]'";
					$res = mysql_query($query) or pupe_error($query);	
				}
			}
		}
		
		$query = "	INSERT into taso
					SET taso 		= '$uusitaso',
					tyyppi	 		= '$tyyppi',
					laji	 		= '$laji',
					summattava_taso = '$summattava_taso',
					nimi	 		= '$nimi',
					laatija			= '$kukarow[kuka]',
					luontiaika		= now(),
					yhtio 			= '$kukarow[yhtio]'";
		$res = mysql_query($query) or pupe_error($query);
		
		echo "Taso $uusitaso lis�tty tilikartan rakenteeseen!<br>";
	
		$tee = "";
	}
}

if ($tee == "lisaa") {
	
	echo "<br><br>";
	echo "Lis�� taso v�liin: $taso - $edtaso<br>";
	
	echo "<br>";
	echo "	<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value = 'lisaalisaa'>
			<input type = 'hidden' name = 'taso' value = '$taso'>
			<input type = 'hidden' name = 'edtaso' value = '$edtaso'>
			<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<table>";

	$valittuu="";
	if ($tyyppi='U') $valittuu="selected";
	
	if(!isset($uusitaso)) {
		$uusitaso = $taso;
	}
	echo "
		<tr>
			<th align='left'>Sisennet��nk� ylemm�t tasot</th>
			<td>	
				<select name='sisennys'>
					<option value = 'E' >Ei</option>
					<option value = 'K' >Kyll�</option>
				</select>
			</td>
		</tr>
	
		<tr>
			<th align='left'>Tyyppi</th>
			<td>	
				<select name='tyyppi'>
					<option value = 'S' >Sis�inen taso</option>
					<option value = 'U' $valittuu>Ulkoinen taso</option>
				</select>
			</td>
		</tr>
	
		<tr><th align='left'>Laji</th>
		<td><select name='laji'>
		<option value = 'O' >Normaali</option>
		<option value = 'N' >Tasoa ei nollata</option>
		</select></td></tr>
	
		<tr><th align='left'>Summattava_taso</th><td><input type = 'text' name = 'summattava_taso' value = '$summattava_taso' size='10'></td></tr>
		<tr><th align='left'>Taso</th><td><input type = 'text' name = 'uusitaso' value = '$uusitaso' size='10'></td></tr>
		<tr><th align='left'>nimi</th><td><input type = 'text' name = 'nimi' value = '$nimi' size='35'></td></tr><tr>";

	echo "</table><br>
	      <input type = 'submit' value = '".t("Lis��")."'></form>";
	
}

if ($tee == "muutamuuta") {
	
	if (strlen($uusitaso) < strlen($taso)) {
		echo "<font class='error'>Uuden tason pituus on oltava v�hint��n: ".strlen($taso)." merkki� pitk�!</font><br>";
		
		$tee = "muuta";
	}
	
	if (substr($taso,0,1) != substr($uusitaso,0,1)) {

		echo "<font class='error'>Uuden tason ensimm�inen merkki on oltava: ".substr($taso,0,1)."!</font><br>";
		
		$tee = "muuta";
	}
	
	$query = "	SELECT *
				FROM taso
				WHERE yhtio = '$kukarow[yhtio]' 
				and taso = '$uusitaso'
				and tyyppi = '$kirjain'";
	$vresult = mysql_query($query) or pupe_error($query);
	
	if (mysql_num_rows($vresult) > 0) {
		echo "<font class='error'>Taso $uusitaso l�ytyy jo j�rjestelm�st�!</font><br>";
		
		$tee = "muuta";	
	}
	
	if ($tee == "muutamuuta") {
		
		echo "Muutetaan tasot<br><br>";
		$query = "	SELECT *
					FROM taso
					WHERE yhtio = '$kukarow[yhtio]' 
					and taso like '$taso%' 
					and tyyppi = '$kirjain'
					ORDER BY CHAR_LENGTH(taso) desc";
		$vresult = mysql_query($query) or pupe_error($query);
			
		while ($vrow = mysql_fetch_array($vresult)) {
			
			$uusitilitaso = $uusitaso.substr($vrow["taso"], strlen($uusitaso)-(strlen($uusitaso)-strlen($taso)));
			
			echo "$vrow[taso] --> $uusitilitaso<br>";
		
			$query = "	UPDATE taso
						SET taso = '$uusitilitaso'
						WHERE yhtio = '$kukarow[yhtio]'
						and taso 	= '$vrow[taso]'
						and tyyppi 	= '$kirjain'";
			$res = mysql_query($query) or pupe_error($query);
			
			if ($kirjain == "S") {
				$query = "	UPDATE tili
							SET sisainen_taso = '$uusitilitaso'
							WHERE yhtio = '$kukarow[yhtio]' 
							and sisainen_taso = '$vrow[taso]'";
				$res = mysql_query($query) or pupe_error($query);
			
				$query = "	UPDATE budjetti
							SET taso = '$uusitilitaso'
							WHERE yhtio = '$kukarow[yhtio]' 
							and taso = '$vrow[taso]'";
				$res = mysql_query($query) or pupe_error($query);
			}
			else {
				$query = "	UPDATE tili
							SET ulkoinen_taso = '$uusitilitaso'
							WHERE yhtio = '$kukarow[yhtio]' 
							and ulkoinen_taso = '$vrow[taso]'";
				$res = mysql_query($query) or pupe_error($query);	
			}
		}
		
		//	N�yt�t��n t�m� ruutu hetkisenaikaa..
		echo "VALMIS!!<br>Palataan takaisin sinne mist� tultiinkin<br>";
		flush();
	}
	
	$tee = "";
}

if ($tee == "muuta") {
	
	echo "<br><br>";
	echo "Muuta tason: $taso koodia<br>";
	
	echo "<br>";
	echo "	<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value = 'muutamuuta'>
			<input type = 'hidden' name = 'taso' value = '$taso'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
			<table>";

	echo "
		<tr><th align='left'>Uusi tasokoodi</th><td><input type = 'text' name = 'uusitaso' value = '$uusitaso' size='10'></td></tr>";

	echo "</table><br>
	      <input type = 'submit' value = '".t("Muuta")."'></form>";
	
}

if ($tee == "tilitasotilitaso") {

	if ($kirjain == "S") {
		$query = "	UPDATE tili
					SET sisainen_taso = '$uusitaso'
					WHERE yhtio = '$kukarow[yhtio]' 
					and tilino in ($tiliarray)";
		$res = mysql_query($query) or pupe_error($query);
	}
	else {
		$query = "	UPDATE tili
					SET ulkoinen_taso = '$uusitaso'
					WHERE yhtio = '$kukarow[yhtio]' 
					and tilino in ($tiliarray)";
		$res = mysql_query($query) or pupe_error($query);	
	}
	
	$tee = "";
}

if ($tee == "tilitaso") {
	
	echo "<br><br>";
	echo "Anna tileille: ";
	echo implode(",", $tiliarray);
	echo " taso!<br>";
	
	echo "<br>";
	echo "	<form action = '$PHP_SELF' method='post'>
			<input type = 'hidden' name = 'tee' value = 'tilitasotilitaso'>
			<input type = 'hidden' name = 'kirjain' value = '$kirjain'>
			<input type = 'hidden' name = 'lopetus' value = '$lopetus'>
			<input type = 'hidden' name = 'tiliarray' value = '".implode(",", $tiliarray)."'>
			<table>";

	echo "<tr><th align='left'>Uusi tasokoodi</th>";		
		
	$query = "select * from taso where yhtio='$kukarow[yhtio]' and tyyppi='$kirjain' and left(taso,1)='$taso' 
	order by taso";
	$tasoresu = mysql_query($query) or pupe_error($query);

	echo "<td><select name='uusitaso'>\n";

	while ($tasorow = mysql_fetch_array($tasoresu)) {
		echo "<option value = '$tasorow[taso]'>$tasorow[taso] - $tasorow[nimi]</option>\n";
	}

	echo "</select></td></tr>";

	echo "</table><br>
	      <input type = 'submit' value = '".t("Muuta")."'></form>";
	
}

if ($tee == "") {
	if ($lopetus != '') {
		// Jotta urlin parametrissa voisi p��ss�t� toisen urlin parametreineen
		$lopetus = str_replace('////','?', $lopetus);
		$lopetus = str_replace('//','&',  $lopetus);
		
		echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$lopetus'>";
		exit;
	}
}

require("inc/footer.inc");

?>
