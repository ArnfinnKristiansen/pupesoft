<?php
	require "inc/parametrit.inc";

	js_popup();	
	
	if($kukarow["resoluutio"] == "I") {
		$tiliointiDivWidth = "650px";
	}
	else {
		$tiliointiDivWidth = "520px";
	}
	
	echo "<font class='head'>".t("Tili�intien muutos/selailu")."</font><hr>";

	if ((($tee == 'U') or ($tee == 'P') or ($tee == 'M') or ($tee == 'J')) and ($oikeurow['paivitys'] != 1)) {
		echo "<b>".t("Yritit p�ivitt�� vaikka simulla ei ole siihen oikeuksia")."</b>";
		exit;
	}
	
	if ($tunnus != 0) {
		$query = "SELECT ebid, nimi, concat_ws(' ', tapvm, mapvm) laskunpvm FROM lasku WHERE yhtio = '$kukarow[yhtio]' and tunnus = '$tunnus'";
		$result = mysql_query ($query) or die ("Kysely ei onnistu $query");
		if (mysql_num_rows($result) > 0) {
			$laskurow=mysql_fetch_array ($result);
			$laskunpvm = $laskurow['laskunpvm'];
		}
		else {
			echo "".t("Lasku katosi")." $tunnus";
			exit;
		}	
	}
	
	if ($laji=='') $laji='O';
	if($laji=='M') $selm='SELECTED';
	if($laji=='O') $selo='SELECTED';
	if($laji=='MM') $selmm='SELECTED';
	if($laji=='OM') $selom='SELECTED';
	if($laji=='X') $selx='SELECTED';
	
	if($laji=='M') $lajiv="tila = 'U'";
	if($laji=='O') $lajiv="tila in ('H', 'Y', 'M', 'P', 'Q')";

	$pvm='tapvm';
	if($laji=='OM') {
		$lajiv="tila = 'Y'";
		$pvm='mapvm';
	}
	if ($laji == 'MM') {
		$lajiv="tila = 'U'";
		$pvm='mapvm';
	}	
	if($laji=='X') $lajiv="tila = 'X'";

	// mik� kuu/vuosi nyt on
	$year = date("Y");
	$kuu  = date("n");
	// poimitaan erikseen edellisen kuun viimeisen p�iv�n vv,kk,pp raportin oletusp�iv�m��r�ksi
	if($vv=='') $vv = date("Y",mktime(0,0,0,$kuu,0,$year));
	if($kk=='') $kk = date("n",mktime(0,0,0,$kuu,0,$year));
	if(strlen($kk)==1) $kk = "0" . $kk; 


//Yl�s hakukriteerit
	if ($viivatut == 'on') $viivacheck='checked';
	echo "<div id='ylos' style=''>
			<form name = 'valinta' action = '$PHP_SELF' method='post'>
			<table>
			<tr><th>".t("Anna kausi, muodossa kk-vvvv").":</th>
			<td><input type = 'text' name = 'kk' value='$kk' size=2></td>
			<td><input type = 'text' name = 'vv' value='$vv' size=4></td>
			<th>".t("Mitk� tositteet listataan").":</th>
			<td><select name='laji'>
			<option value='M' $selm>".t("myyntilaskut")."
			<option value='O' $selo>".t("ostolaskut")."
			<option value='MM' $selmm>".t("myyntilaskut maksettu")."
			<option value='OM' $selom>".t("ostolaskut maksettu")."
			<option value='X' $selx>".t("muut")."
			</select></td>
			<td><input type='checkbox' name='viivatut' $viivacheck> ".t("Korjatut")."</td>
			<td class='back'><input type = 'submit' value = '".t("Valitse")."'></td>
			</tr>
			</table>
			</form></div>";
	$formi = 'valinta';
	$kentta = 'kk';


// Vasemmalle laskuluettelo
	if ($vv < 2000) $vv += 2000;
	$lvv=$vv;
	$lkk=$kk;
	$lkk++;
	if ($lkk > 12) {
		$lkk='01';
		$lvv++;
	}
	
	
	$query = "	SELECT tunnus, nimi, $pvm, summa, comments
				FROM lasku
				WHERE yhtio = '$kukarow[yhtio]' and $pvm >= '$vv-$kk-01' and $pvm < '$lvv-$lkk-01' and $lajiv
				ORDER BY tapvm desc, summa desc";

	$result = mysql_query($query) or pupe_error($query);
	$loppudiv ='';
	if (mysql_num_rows($result) == 0) {
		echo "<font class='error'>".t("Haulla ei l�ytynyt yht��n laskua")."</font>";

	}
	else {
		$seutunnus = 0;
		echo "<div id='vasen' style='position: absolute; top: 100px; height: 200px; width:$tiliointiDivWidth; overflow: scroll;'><table><tr>";
		echo "<th></th>";
		for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
			echo "<th>" . t(mysql_field_name($result,$i))."</th>";
		}
		echo "</tr>";
		echo "<tr>";
		while ($trow=mysql_fetch_array ($result)) {
			if ($seuraava == 1) { // T�ss� on seuraavan laskun tunnus. Siirret��n se seuraava nappulaan (, joka tehd��n inc/tiliointirivit.inc:ss�)
				$seutunnus = $trow['tunnus'];
				$seuraava = 0;
			}

			
			$tyylia='<td>';
			$tyylil='</td>';
			if ($trow['tunnus']==$tunnus) {
				$tyylia='<th>';
				$tyylil='</th>';
				$seuraava = 1;
			}
			
			if ($trow['comments'] != '') {
				$loppudiv .= "<div id='".$trow['tunnus']."' class='popup' style='width:250px'>";
				$loppudiv .= $trow["comments"]."<br></div>";
				echo "<td valign='top'><a class='menu' onmouseout=\"popUp(event,'".$trow['tunnus']."')\" onmouseover=\"popUp(event,'".$trow['tunnus']."')\"><img src='pics/lullacons/alert.png'></a></td>";
			}
			else 
				echo "<td></td>";
				
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				if ($i==1) {
					if (strlen($trow[$i])==0) $trow[$i]="(tyhj�)";
					echo "$tyylia<a href name='$trow[tunnus]'><a href='$PHP_SELF?tee=E&tunnus=$trow[tunnus]&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$trow[tunnus]'>$trow[$i]</a></a>$tyylil";
				}
				else {
						
					echo "$tyylia$trow[$i]$tyylil";
				}
			}
						
			echo "</tr>";
		}
	}
	echo "</tr></table></div>";

//Oikealla laskun kuva
	echo "<div id='oikea' style='position: absolute; top: 100px; left: 525px; height: 700px; width:$tiliointiDivWidth; overflow: scroll;'>";
	if ($tunnus != '') {
		// tehd��n lasku linkki
		echo "<td>".ebid($tunnus) ."</td>";
	}
	else {
		echo "<font class='message'> ".t("Laskua ei ole valittu")."</font>";
	}
	echo "</div>";

// Alas tili�innit
	echo "<div id='alas' style='position: absolute; top: 300px; height: 500px; width:$tiliointiDivWidth; overflow: scroll;''>";
	 
	if ($tee == 'P') { // Olemassaolevaa tili�inti� muutetaan, joten yliviivataan rivi ja annetaan perustettavaksi
		$query = "SELECT tilino, kustp, kohde, projekti, summa, vero, selite, tapvm
					FROM tiliointi
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
		
		if (mysql_num_rows($result) == 0) {
			echo "".t("Tili�inti� ei l�ydy")."! $query";
			exit;
		}
		$tiliointirow=mysql_fetch_array($result);
		$tili = $tiliointirow[0];
		$kustp = $tiliointirow[1];
		$kohde = $tiliointirow[2];
		$projekti = $tiliointirow[3];
		$summa = $tiliointirow[4];
		$vero = $tiliointirow[5];
		$selite = $tiliointirow[6];
		$tiliointipvm = $tiliointirow['tapvm'];
		$ok = 1;

// Etsit��n kaikki tili�intirivit, jotka kuuluvat t�h�n tili�intiin ja lasketaan niiden summa

		$query = "SELECT sum(summa) FROM tiliointi
					WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu='' GROUP BY aputunnus";
		$result = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($result) != 0) {
			$summarow=mysql_fetch_array($result);
			$summa += $summarow[0];
			$query = "UPDATE tiliointi SET korjattu = '$kukarow[kuka]', korjausaika = now()
						WHERE aputunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]' and tiliointi.korjattu=''";
			$result = mysql_query($query) or pupe_error($query);
		}

		$query = "UPDATE tiliointi
					SET korjattu = '$kukarow[kuka]', korjausaika = now()
					WHERE tunnus = '$ptunnus' and yhtio = '$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);

		$tee = "E"; // N�ytet��n milt� tosite nyt n�ytt��
	}

	if ($tee == 'U') { // Lis�t��n tili�intirivi
		$summa = str_replace ( ",", ".", $summa);
		$selausnimi = 'tili'; // Minka niminen mahdollinen popup on?
		require "inc/tarkistatiliointi.inc";
		$tiliulos=$ulos;
		$ulos='';


		$tee = 'E';
		if ($ok != 1) {
			require "inc/teetiliointi.inc";
			if ($jaksota == 'on') {
				$tee = 'U';
				require "inc/jaksota.inc"; // Jos jotain jaksotetaan on $tee J
			}
		}
	}
	if (($tee == 'E') or ($tee=='F')) { // Tositeen n�ytt� muokkausta varten
		if ($tee == 'F') {
// Laskun tilausrivit
			require "inc/tilausrivit.inc";
		}
		else {
// Tositteen tili�intirivit...
			require "inc/tiliointirivit.inc";
		}
// Tehd��n nappula, jolla voidaan vaihtaa n�kym�ksi tilausrivit/tili�intirivit
		if ($tee == 'F') {
			$ftee = 'E';
			$fnappula = 'tili�innit';
		}
		else {
			$ftee = 'F';
			$fnappula = 'tilausrivit';
		}
		
		echo "<a href = '$PHP_SELF?tee=$ftee&tunnus=$tunnus&laji=$laji&vv=$vv&kk=$kk&viivatut=$viivatut#$tunnus'>$fnappula</a>";
	}
	echo "</div>";
	
	echo $loppudiv;
	
	echo "<div id='footer' style='position: absolute; top:780px;'>";
	require "inc/footer.inc";
	echo "</div>";
?>