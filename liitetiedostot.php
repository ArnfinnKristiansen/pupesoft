<?php

require ('inc/parametrit.inc');

echo "<font class='head'>".t("Liitetiedostot")."</font><hr>";

if (!isset($_REQUEST['liitos']) and !isset($_REQUEST['id'])) {
	echo "<form action='' method='get'>
		<table>
		<tr>
			<th>".t("Tyyppi") ."</th>
			<td>
				<select name='liitos'>
				<option value='lasku'>".t('Lasku')."</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>".t('Tunnus')."</th>
			<td><input type='text' name='id'></td>
			<td class='back'><input type='submit' name='submit' value='".t('Etsi')."'</td>
		</tr>
	</table>
	</form>";
}

// Liitet��n tiedosto?
if (isset($_POST['tee']) and $_POST['tee'] == 'file' and isset($_REQUEST['liitos']) and isset($_REQUEST['id']) and is_uploaded_file($_FILES['userfile']['tmp_name'])) {

	settype($_REQUEST['id'], 'int');
	$liiteid = mysql_real_escape_string($_REQUEST['id']);

	$retval = tarkasta_liite("userfile");
	if($retval !== true) {
		echo $retval;
	}
	else {
		tallenna_liite("userfile", mysql_real_escape_string($_REQUEST['liitos']), $liiteid, $selite, $kayttotarkoitus);
	}

}

// poistetaanko liite?
if (isset($_POST['poista']) and isset($_POST['tunnus']) and isset($_REQUEST['liitos']) and isset($_REQUEST['id'])) {

	$liitetunnus = mysql_real_escape_string($_POST['tunnus']);

	settype($_REQUEST['id'], 'int');
	$liiteid = mysql_real_escape_string($_REQUEST['id']);

	$query = "DELETE from liitetiedostot where tunnus='$liitetunnus' and yhtio='{$kukarow['yhtio']}'";
	mysql_query($query) or pupe_error($query);

	$query = "SELECT * from lasku where tunnus='$liiteid' and yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($res);

	if (in_array($laskurow['tila'], array('H','M')) and $kukarow["taso"] != 3) {
		nollaa_hyvak($liiteid);
	}
}

if (isset($_REQUEST['liitos']) and $_REQUEST['liitos'] == 'lasku' and isset($_REQUEST['id'])) {

	settype($_REQUEST['id'], 'int');
	$liiteid = mysql_real_escape_string($_REQUEST['id']);
	
	$query = "SELECT * from lasku where tunnus='$liiteid' and yhtio='{$kukarow['yhtio']}'";
	$res = mysql_query($query) or pupe_error($query);
	$laskurow = mysql_fetch_array($res);
	
	/*	Tarkastetaan k�ytt�j�oikeuksia hieman eri tavalla eri laskuilla	*/
	
	//	Oletuksena emme salli mit��n!
	$ok = false;

	//	Ostoreskontran laskut
	if(in_array($laskurow['tila'], array('H','Y','M','P','Q','X'))) {
		$query = "SELECT * from oikeu where yhtio='{$kukarow['yhtio']}' and kuka='{$kukarow['kuka']}' and nimi LIKE '%ulask.php'";
		$res = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($res) > 0) {
			$ok = true;
		}
		$litety = "<td>&nbsp;</td>";		
	}
	//	N�m� ovat varmaankin sitten itse tilauksia?
	elseif(in_array($laskurow['tila'], array("L","N","R","E","T","U","0"))) {
		$ok = true;

		//	N�ille voidaan laittaa my�s mink� lajin liite on kyseess�
		$query = "	SELECT selite, if(selitetark_2='PAKOLLINEN', concat('* ', selitetark), concat('&nbsp;&nbsp;', selitetark)) selitetark
					FROM avainsana
					WHERE yhtio = '$kukarow[yhtio]' and laji = 'TIL-LITETY'
					ORDER BY selitetark_2 DESC, jarjestys, selite";
		$ares = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($ares) > 0) {
			$litety = "<td><select name ='kayttotarkoitus'>";
			while($a = mysql_fetch_array($ares)) {
				$litety .= "<option value = '$a[selite]'>$a[selitetark]</option>";
			}
			$litety .= "</select></td>";
		}
	}
	
	if($ok === true) {
		echo "<table>
			<tr><th>".t('Nimi')."</th><td>{$laskurow['nimi']}</td></tr>
			<tr><th>".t('Nimitark')."</th><td>{$laskurow['nimitark']}</td></tr>
			<tr><th>".t('Osoite')."</th><td>{$laskurow['osoite']}</td></tr>
			<tr><th>".t('Postitp')."</th><td>{$laskurow['postitp']}</td></tr>";

		echo "</table><br>";
		
		//	Ei sallita liitetiedoston lis��mist� jos k�ytt�j�ll� ei ole 3 tason natsoja ja lasku on jo maksettu ostolasku
		// Alkuper�inen: 	if (!in_array($laskurow['tila'], array('Y','P','Q')) or $kukarow["taso"] == "3") {

		if (!in_array($laskurow['tila'], array('P','Q')) or $kukarow["taso"] == "3") {
			
			echo "<font class='message'>".t("Lis�� uusi tiedosto").":</font><br>";
			
			echo "
				<form method='post' name='kuva' enctype='multipart/form-data'>
				<input type='hidden' name='id' value='$liiteid'>
				<input type='hidden' name='tee' value='file'>
				<input type='hidden' name='liitos' value='lasku'>
				<input type='hidden' name='lopetus' value='$lopetus'>
				
				<table>
					<tr>
						<th>".t("K�ytt�tarkoitus"),"</th>
						<th>".t("Selite"),"</th>
						<th>".t("Tiedosto"),"</th>
					</tr>
					<tr>
						$litety
						<td><input type='text' name='selite' value='' size='20'></td>
						<td><input type='file' name='userfile'></td>
						<td class='back'><input type='submit' name='submit' value='".t('Liit� tiedosto')."'></td>
					</tr>
				</table>
				</form><br>";
		}

		$query = "	SELECT *, (select selitetark from avainsana a where a.yhtio = liitetiedostot.yhtio and laji = 'TIL-LITETY' and a.selite = liitetiedostot.kayttotarkoitus) kayttotarkoitus
					FROM liitetiedostot
					WHERE liitostunnus='$liiteid' AND liitos='lasku' and yhtio='{$kukarow['yhtio']}'";
		$res = mysql_query($query) or pupe_error($query);

		echo "<table>
			<tr>
				<th>".t('K�ytt�tarkoitus')."</th>
			    <th>".t('Selite')."</th>
				<th>".t('Tiedosto')."</th>
				<th>".t('Koko')."</th>
				<th>".t('Tyyppi')."</th>
				<th>".t('Lis�ttyaika')."</th>
				<th>".t('Lis��j�')."</th>
				<th colspan=2></th>
			</tr>";

		while ($liite = mysql_fetch_array($res)) {

			$filesize = $liite['filesize'];
			$type = array('b', 'kb', 'mb', 'gb');

			for ($ii=0; $filesize>1024; $ii++) {
				$filesize /= 1024;
			}

			$filesize = sprintf("%.2f",$filesize)." $type[$ii]";

			echo "<tr>
				<td>".$liite['kayttotarkoitus'] ."</td>
				<td>".$liite['selite'] ."</td>
				<td>".$liite['filename'] ."</td>
				<td>".$filesize."</td>
				<td>".$liite['filetype'] ."</td>
				<td>".tv1dateconv($liite['luontiaika'],"P")."</td>
				<td>".$liite['laatija'] ."</td>
				";

			echo "<td><a href='view.php?id={$liite['tunnus']}'>".t('N�yt� liite')."</a></td>";

			if (in_array($laskurow['tila'], array('H','M','X')) or $kukarow["taso"] == "3" or ($laskurow['h1time'] == '0000-00-00 00:00:00' and $laskurow['h2time'] == '0000-00-00 00:00:00' and $laskurow['h3time'] == '0000-00-00 00:00:00' and $laskurow['h4time'] == '0000-00-00 00:00:00' and $laskurow['h5time'] == '0000-00-00 00:00:00')) {
				echo "<td><form action='' method='post'>
					<input type='hidden' name='tunnus' value='{$liite['tunnus']}'>
					<input type='submit' name='poista' value='Poista' onclick='return confirm(\"".t('Haluatko varmasti poistaa t�m�n liitteen')."\");'>
					</form></td>";
			}

			echo "</tr>";
		}

		echo "</table>";
		
		echo "<br><br><a href='muutosite.php?tee=E&tunnus={$laskurow['tunnus']}&lopetus=$lopetus'>&laquo; ".t('Takaisin laskulle')."</a>";

		if ($lopetus != "") {						
			echo "<br><br>";
			lopetus($lopetus);
		}
	}
}

// nollaa hyvak ajat sek� asettaa viimeisimm�n hyv�ksyj�n = hyvak1
function nollaa_hyvak($id) {
	global $kukarow;

	// nollataan hyv�ksyj�t jos jokin n�ist� tiloista
	$query = "	UPDATE lasku 
				SET h1time 		= '', 
				h2time 			= '', 
				h3time 			= '', 
				h4time 			= '', 
				h5time 			= '', 
				hyvaksyja_nyt 	= hyvak1,
				tila 			= if(tila='M', 'H', tila)
				WHERE tunnus 	= $id 
				and yhtio		= '{$kukarow['yhtio']}'
				and tila in ('H','M')";
	mysql_query($query) or pupe_error($query);	
}

require ('inc/footer.inc');

?>
