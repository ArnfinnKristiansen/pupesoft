<?php
	require ("siirtoa.php");
	require ("ekakaytto.php");
	require ("../inc/parametrit.inc");
	
	echo "<font class='head'>".t("Patutestaus")."<hr></font>";
	
	if ($tunnus != '' and $ekapala != '') {
		echo "<font class='error'>".ekasiirtoavain($ekapala,$tokapala,$tarkiste,$tunnus)."</font><br><br>";
		$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and tunnus = '$tunnus'";
		$vresult = mysql_query($query) or pupe_error($query);
		if ($vrow=mysql_fetch_array($vresult)) {
			echo "<font class='error'>".ekakayttoavain($tunnus)."</font><br><br>";
		}
	}
	
	if ($tunnus != '' and $aineisto != '') {
		$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and tunnus = '$tunnus' and kayttoavain != ''";
		$vresult = mysql_query($query) or pupe_error($query);
		if ($vrow=mysql_fetch_array($vresult)) {
			echo "<font class='error'>Tilill� ei ole k�ytt�avainta</font><br><br>";
		}
		else {
			siirto($yritirow, $aineisto, $pvm);
		}
	}
	
	echo "<form action = '' method='post'><table><tr><th>".t("Pankkitili");
	$query = "	SELECT * 
				FROM yriti 
				WHERE yhtio  = '$kukarow[yhtio]'
				and kaytossa = '' ";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "</td><td><select name='tunnus'>";

	while ($vrow=mysql_fetch_array($vresult)) {
		echo "<option value = '$vrow[tunnus]'>$vrow[nimi]";
		if ($vrow['kayttoavain'] != '') echo " patu ok";
	}
	echo "</select></td></tr>";
	echo "<tr><th>".t("Avain1")."</th>";
	echo "<td><input type='text' name = 'ekapala'></td></tr>";
	echo "<tr><th>".t("Avain2")."</th>";
	echo "<td><input type='text' name = 'tokapala'></td></tr>";
	echo "<tr><th>".t("Tarkiste")."</th>";
	echo "<td><input type='text' name = 'tarkiste'></td></tr>";
	echo "<tr><th>".t("Tiliote")."</th>";
	echo "<td><input type='radio' name = 'aineisto' value = 'TITO'></td></tr>";
	echo "<tr><th>".t("Valuuttakurssit")."</th>";
	echo "<td><input type='radio' name = 'aineisto' value = 'VKEUR'></td></tr>";
	echo "<tr><th>".t("Tuotantotilanneraportti")."</th>";
	echo "<td><input type='radio' name = 'aineisto' value = 'INFO'></td></tr>";
	echo "<tr><th>".t("Aineistojen tilakysely")."</th>";
	echo "<td><input type='radio' name = 'aineisto' value = 'STATUS'></td></tr>";
	echo "<tr><th></th><td><input type='submit' name = 'P�ivit�'></td></tr></table></form>";
	
	require ("../inc/footer.inc");
?>
