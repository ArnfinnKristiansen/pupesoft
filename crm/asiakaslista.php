<?php
	
	// k�sitt�m�t�n juttu, mutta ei voi muuta
	if ($_POST["voipcall"] != "") $_GET["voipcall"]  = "";

	require ("../inc/parametrit.inc");

	if ($voipcall == "call" and $o != "" and $d != "") {
		ob_start();
		$retval = @readfile($VOIPURL."&o=$o&d=$d");
		$retval = ob_get_contents();
		ob_end_clean();
		if ($retval != "OK") echo "<font class='error'>Soitto $o -&gt; $d ep�onnistui!</font><br><br>";
	}
	
	$otsikko   = 'Asiakaslista';
	if ($yhtiorow['viikkosuunnitelma'] == '') {
		$kentat    = "tunnus::nimi::asiakasnro::ytunnus::if(toim_postitp!='',toim_postitp,postitp)::postino::yhtio::myyjanro::email";
	}
	else {
		$kentat    = "tunnus::nimi::myyjanro::ytunnus::asiakasnro::if(toim_postitp!='',toim_postitp,postitp)::yhtio";
	}
	
	$jarjestys = 'selaus, nimi';

	echo "<font class='head'>".t("$otsikko")."</font><hr>";
	
	$array = split("::", $kentat);
	$count = count($array);
	for ($i=0; $i<=$count; $i++) {
		if (strlen($haku[$i]) > 0) {
			$lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
			$ulisa .= "&haku[" . $i . "]=" . $haku[$i];
		}
	}
	if (strlen($ojarj) > 0) {
		$jarjestys = $ojarj;
	}
	
	if ($asos != '') {
		$lisa .= " and osasto='$asos' ";
	}
	
	if ($aspiiri != '') {
		$lisa .= " and piiri='$aspiiri' ";
	}
	
	if ($asryhma != '') {
		$lisa .= " and ryhma='$asryhma' ";
	}
	if ($astila != '') {
		$lisa .= " and tila='$astila' ";
	}
	
	if ($asmyyja != '') {
		$lisa .= " and myyjanro='$asmyyja' ";
	}

	$lisa .= " and laji != 'P' ";
	
	if (trim($konserni) != '') {
		$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
		$result = mysql_query($query) or pupe_error($query);
		$konsernit = "";
		
		while ($row = mysql_fetch_array($result)) {	
			$konsernit .= " '".$row["yhtio"]."' ,";
		}		
		$konsernit = " yhtio in (".substr($konsernit, 0, -1).") ";			
	}
	else {
		$konsernit = " yhtio = '$kukarow[yhtio]' ";
	}
	
	if ($yhtiorow['viikkosuunnitelma'] == '') {
		if ($tee == "lahetalista") {		
			$query = "	SELECT tunnus, nimi, postitp, ytunnus, yhtio, asiakasnro, nimitark, osoite, postino, postitp, maa, toim_nimi, toim_nimitark, toim_osoite, toim_postino, toim_postitp, toim_maa,
						puhelin, fax, myyjanro, email, osasto, piiri, ryhma, fakta, toimitustapa, yhtio
						FROM asiakas 
						WHERE $konsernit 
						$lisa";
			$tiednimi = "asiakaslista.xls";
		}
		else {
			$query = "	SELECT tunnus, nimi, asiakasnro, ytunnus,  if(toim_postitp!='',toim_postitp,postitp) postitp, if(toim_postino!=00000,toim_postino,postino) postino, yhtio, myyjanro, email, puhelin
						FROM asiakas 
						WHERE $konsernit 
						$lisa";
			$tiednimi = "viikkosuunnitelma.xls";
		}
	}
	else {
		$query = "	SELECT tunnus, nimi, (SELECT concat_ws(' ',myyja,nimi) from kuka where yhtio='$kukarow[yhtio]' and myyja=myyjanro and myyja > 0 limit 1) myyja, ytunnus, asiakasnro, if(toim_postitp!='',toim_postitp,postitp) postitp, yhtio, puhelin
					FROM asiakas 
					WHERE $konsernit 
					$lisa";
	}
	

				
	if ($lisa == "" and ($tee != 'laheta' or $tee != 'lahetalista')) {
		$limit = " LIMIT 200 ";
	} 
	else {
		$limit = " ";
	}
				
	$query .= "$ryhma ORDER BY $jarjestys $limit";
	$result = mysql_query($query) or pupe_error($query);

	if ($oper == t("Vaihda listan kaikkien asiakkaiden tila")) {
		// K�yd��n lista l�pi kertaalleen
		while ($trow = mysql_fetch_array ($result)) {
			$query_update = "	UPDATE asiakas 
								SET tila = '$astila_vaihto' 
								WHERE tunnus = '$trow[tunnus]' 
								AND yhtio = '$yhtiorow[yhtio]'";
			$result_update = mysql_query($query_update) or pupe_error($query_update);
		}
		$result = mysql_query($query) or pupe_error($query);
	}
	
	if ($tee == 'laheta' or $tee == 'lahetalista') {
		
		if ($tee == "lahetalista") {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if(isset($workbook)) {
					$excelsarake = 0;

					for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
						if(isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;					
						}
					}
					
					
					$excelsarake = 0;
					$excelrivi++;					
					
				}
			}
		}
		else {
			if (@include('Spreadsheet/Excel/Writer.php')) {
				//keksit��n failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$excelnimi = md5(uniqid(mt_rand(), true)).".xls";

				$workbook = new Spreadsheet_Excel_Writer('/tmp/'.$excelnimi);
				$workbook->setVersion(8);
				$worksheet =& $workbook->addWorksheet('Sheet 1');

				$format_bold =& $workbook->addFormat();
				$format_bold->setBold();

				$excelrivi = 0;

				if(isset($workbook)) {
					$excelsarake = 0;
					
					for ($i=1; $i<mysql_num_fields($result); $i++) {
						//$liite .= $trow[$i]."\t";
						if(isset($workbook)) {
							$worksheet->write($excelrivi, $excelsarake, t(mysql_field_name($result,$i)) , $format_bold);
							$excelsarake++;					
						}
					}					
					
					$worksheet->write($excelrivi, $excelsarake, t("pvm") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kampanjat") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvm k�yty") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("km") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("l�ht�") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("paluu") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("pvraha") , $format_bold);
					$excelsarake++;
					$worksheet->write($excelrivi, $excelsarake, t("kommentti") , $format_bold);
					
					
					$excelsarake = 0;
					$excelrivi++;					
					
				}
			}
		}
		while ($trow=mysql_fetch_array ($result)) {
			$excelsarake = 0;
			for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
				if(isset($workbook)) {
					$worksheet->writeString($excelrivi, $excelsarake, $trow[$i], $format_bold);
					$excelsarake++;					
				}
			}
			$excelrivi++;
		}
		
		if(isset($workbook)) {
			// We need to explicitly close the workbook
			$workbook->close();
		}
		
		$liite = "/tmp/".$excelnimi;
		
		$bound = uniqid(time()."_") ;

		$header  = "From: <$yhtiorow[postittaja_email]>\n";
		$header .= "MIME-Version: 1.0\n" ;
		$header .= "Content-Type: multipart/mixed; boundary=\"$bound\"\n" ;
		
		$content = "--$bound\n" ;

		$content .= "Content-Type: application/excel; name=\"".basename($liite)."\"\n" ;
		$content .= "Content-Transfer-Encoding: base64\n" ;
		$content .= "Content-Disposition: inline; filename=\"".basename($tiednimi)."\"\n\n";

		$handle  = fopen($liite, "r");
		$sisalto = fread($handle, filesize($liite));
		fclose($handle);

		$content .= chunk_split(base64_encode($sisalto));
		$content .= "\n" ;
		
				
		
		if ($tee == "lahetalista") {
			mail($kukarow['eposti'], "Asiakkaiden tiedot", $content, $header, "-f $yhtiorow[postittaja_email]");			
			echo "<br><br><font class='message'>".t("Asiakkaiden tiedot s�hk�postiisi")."!</font><br><br><br>";
		}
		else {
			mail($kukarow['eposti'], "Viikkosunnitelmapohja", $content, $header, "-f $yhtiorow[postittaja_email]");
			echo "<br><br><font class='message'>".t("Suunnitelmapohja l�hetetty s�hk�postiisi")."!</font><br><br><br>";
		}
		
		
		mysql_data_seek($result,0);
		
	}
	
	if ($yhtiorow['viikkosuunnitelma'] == '') {
		echo "<li><a href='$PHP_SELF?tee=laheta&asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja".$ulisa."'>".t("L�het� viikkosuunnitelmapohja s�hk�postiisi")."</a><br>";
		echo "<li><a href='$PHP_SELF?tee=lahetalista&asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja".$ulisa."'>".t("L�het� asiakaslista s�hk�postiisi")."</a><br>";
	}
	
	echo "<br><table>
			<form action='$PHP_SELF' method='post'>
			<input type='hidden' name='voipcall' value='kala'>";
	
	$asosresult = t_avainsana("ASIAKASOSASTO");
	
	echo "<tr><th>".t("Valitse asiakkaan osasto").":</th><td><select name='asos' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki osastot")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asos == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td>";
	
	
	$chk = "";
	if (trim($konserni) != '') {
		$chk = "CHECKED";
	}
	
	echo "<th>".t("N�yt� konsernin kaikki asiakkaat").":</th><td><input type='checkbox' name='konserni' $chk onclick='submit();'></td>";
	echo "</tr>\n\n";
	
	$asosresult = t_avainsana("PIIRI");
	
	echo "<tr><th>".t("Valitse asiakkaan piiri").":</th><td><select name='aspiiri' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki piirit")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($aspiiri == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	echo "</select></td>";
	

	$asosresult = t_avainsana("ASIAKASRYHMA");
	
	echo "<th>".t("Valitse asiakkaan ryhm�").":</th><td><select name='asryhma' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki ryhm�t")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asryhma == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>\n\n";
					
	$query = "	SELECT distinct asiakas.myyjanro, kuka.nimi
				FROM asiakas
				LEFT JOIN kuka ON kuka.yhtio = asiakas.yhtio and kuka.myyja=asiakas.myyjanro
				WHERE asiakas.yhtio='$kukarow[yhtio]' and asiakas.myyjanro!=0  order by myyjanro";
	$asosresult = mysql_query($query) or pupe_error($query);
	
	echo "<tr>";
	echo "<th>".t("Valitse asiakkaan myyj�").":</th><td><select name='asmyyja' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki myyj�t")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($asmyyja == $asosrow["myyjanro"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[myyjanro]' $sel2>$asosrow[myyjanro] - $asosrow[nimi]</option>";
	}
	
	echo "</select></td>\n\n";				

	$asosresult = t_avainsana("ASIAKASTILA");

	echo "<th>".t("Valitse asiakkaan tila").":</th><td><select name='astila' onchange='submit();'>";
	echo "<option value=''>".t("Kaikki tilat")."</option>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($astila == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>\n\n";			
	echo "</table><br><table>";

	echo "<tr>";
		
	for ($i = 1; $i < mysql_num_fields($result)-1; $i++) {
		echo "<th><a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&asmyyja=$asmyyja&ojarj=".mysql_field_name($result,$i).$ulisa."'>" . t(mysql_field_name($result,$i)) . "</a>";

		if 	(mysql_field_len($result,$i)>10) $size='20';
		elseif	(mysql_field_len($result,$i)<5)  $size='5';
		else	$size='10';

		echo "<br><input type='text' name='haku[$i]' value='$haku[$i]' size='$size' maxlength='" . mysql_field_len($result,$i) ."'>";
		echo "</th>";
	}

	echo "<td class='back'>&nbsp;&nbsp;<input type='Submit' value='".t("Etsi")."'></td></tr>\n\n";
	
	while ($trow=mysql_fetch_array ($result)) {
		echo "<tr class='aktiivi'>";
		for ($i=1; $i<mysql_num_fields($result)-1; $i++) {
			if ($i == 1) {
				if (trim($trow[1]) == '') $trow[1] = "".t("*tyhj�*")."";
				echo "<td><a href='asiakasmemo.php?ytunnus=$trow[ytunnus]&asiakasid=$trow[tunnus]'>$trow[1]</a></td>";
			}
			elseif(mysql_field_name($result,$i) == 'ytunnus') {
				echo "<td><a href='../yllapito.php?toim=asiakas&tunnus=$trow[tunnus]&lopetus=crm/asiakaslista.php'>$trow[$i]</a></td>";
			}
			else {
				echo "<td>$trow[$i]</td>";
			}
		}
		
		echo "<td class='back'>";		

		if ($trow["puhelin"] != "" and $kukarow["puhno"] != "" and isset($VOIPURL)) {
			$d = ereg_replace("[^0-9]", "", $trow["puhelin"]);  // dest
			$o = ereg_replace("[^0-9]", "", $kukarow["puhno"]); // orig
			echo "<a href='$PHP_SELF?asos=$asos&asryhma=$asryhma&astila=$astila&aspiiri=$aspiiri&konserni=$konserni&ojarj=$ulisa&o=$o&d=$d&voipcall=call'>Soita $o -&gt; $d</a>";
		}

		echo "</td>";		
		echo "</tr>\n\n";
	}
	echo "</table>";

	echo "<br/>";

	$asosresult = t_avainsana("ASIAKASTILA");

	echo t("Vaihda asiakkaiden tila").": <select name='astila_vaihto'>";
	
	while ($asosrow = mysql_fetch_array($asosresult)) {
		$sel2 = '';
		if ($astila == $asosrow["selite"]) {
			$sel2 = "selected";
		}
		echo "<option value='$asosrow[selite]' $sel2>$asosrow[selite] - $asosrow[selitetark]</option>";
	}
	
	echo "</select></td></tr>\n\n";

	echo "<input type=\"submit\" name=\"oper\" value=\"".t("Vaihda listan kaikkien asiakkaiden tila")."\">";
	echo "</form>";

	require ("../inc/footer.inc");

?>