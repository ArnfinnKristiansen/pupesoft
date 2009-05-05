<?php

	// jos ollaan saatu komentorivilt� parametri
	// komentorivilt� pit�� tulla parametrin� yhtio
	if (trim($argv[1]) != '') {

		if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

		// otetaan tietokanta connect
		require ("inc/connect.inc");
		require ("inc/functions.inc");

		// hmm.. j�nn��
		$kukarow['yhtio'] = $argv[1];
		$kukarow['kuka'] = "crond";

		$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
		$yhtiores = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($yhtiores) == 1) {
			$yhtiorow = mysql_fetch_array($yhtiores);
			$aja = 'run';

			$query = "	SELECT *
						FROM yhtion_parametrit
						WHERE yhtio='$yhtiorow[yhtio]'";
			$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

			if (mysql_num_rows($result) == 1) {
				$yhtion_parametritrow = mysql_fetch_array($result);

				// lis�t��n kaikki yhtiorow arrayseen
				foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
					$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
				}
			}

		}
		else {
			die ("Yhti� $kukarow[yhtio] ei l�ydy!");
		}
	}
	else {
		require ("inc/parametrit.inc");

		echo "<font class='head'>Iltasiivo</font><hr>";

		if ($aja != "run") {
			echo "<br><form action='$PHP_SELF' method='post'>";
			echo "<input type='hidden' name='aja' value='run'>";
			echo "<input type='submit' value='Aja iltasiivo!'>";
			echo "</form>";
		}

		echo "<pre>";
	}

	if ($aja == "run") {

		$iltasiivo = "";
		$laskuri   = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden toimittaja on poistettu
		$query = "	SELECT toimi.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join toimi on toimi.yhtio = tuotteen_toimittajat.yhtio and toimi.tunnus = tuotteen_toimittajat.liitostunnus
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having toimi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri poistetun toimittajan tuoteliitosta.\n";
		$laskuri = 0;

		// poistetaan kaikki tuotteen_toimittajat liitokset joiden tuote on poistettu
		$query = "	SELECT tuote.tunnus, tuotteen_toimittajat.tunnus toimtunnus
					from tuotteen_toimittajat
					left join tuote on tuote.yhtio = tuotteen_toimittajat.yhtio and tuote.tuoteno = tuotteen_toimittajat.tuoteno
					where tuotteen_toimittajat.yhtio = '$kukarow[yhtio]'
					having tuote.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "DELETE from tuotteen_toimittajat where tunnus = '$row[toimtunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri poistetun tuotteen tuoteliitosta.\n";
		
		$laskuri = 0;
		$laskuri2 = 0;
		
		// poistetaan kaikki JT-otsikot jolla ei ole en�� rivej� ja extranet tilaukset joilla ei ole rivej� ja tietenkin my�s ennakkootsikot joilla ei ole rivej�.
		$query = "	SELECT tilausrivi.tunnus, lasku.tunnus laskutunnus, lasku.tila, lasku.tunnusnippu
					from lasku
					left join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					where lasku.yhtio = '$kukarow[yhtio]' and
					lasku.tila in ('N','E')
					having tilausrivi.tunnus is null";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php (1)")."<br>";

			//	Jos kyseess� on tunnusnippupaketti, halutaan s�ilytt�� linkki t�st� tehtyihin tilauksiin, tilaus merkataan vain toimitetuksi
			if($row["tunnusnippu"] > 0) {
				$query = "UPDATE lasku set tila = 'L', alatila='X' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri2 ++;
			}
			else {
				$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
				$deler = mysql_query($query) or die($query);
				$laskuri ++;
			}

			//poistetaan TIETENKIN kukarow[kesken] ettei voi sy�tt�� extranetiss� rivej� t�lle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);

		}

		if ($laskuri > 0) $iltasiivo .= "Poistettiin $laskuri rivit�nt� tilausta.\n";
		if ($laskuri2 > 0) $iltasiivo .= "Merkattiin toimitetuksi $laskuri2 rivit�nt� tilausta.\n";

		$laskuri = 0;
		
		// Merkit��n laskut mit�t�idyksi joilla on pelk�st��n mit�t�ityj� rivej�.
		$query = "	SELECT lasku.tunnus laskutunnus, lasku.tila, count(*) kaikki, sum(if(tilausrivi.tyyppi='D',1,0)) dellatut
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus
					where lasku.yhtio = '$kukarow[yhtio]' 
					and lasku.tila in ('N','E','L')
					and lasku.alatila != 'X'
					group by 1,2
					having dellatut > 0 and kaikki = dellatut";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php (1.5)")."<br>";

			$query = "UPDATE lasku set alatila='$row[tila]', tila='D',  comments = '$komm' where yhtio = '$kukarow[yhtio]' and tunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;

			//poistetaan TIETENKIN kukarow[kesken] ettei voi sy�tt�� extranetiss� rivej� t�lle
			$query = "UPDATE kuka set kesken = '' where yhtio = '$kukarow[yhtio]' and kesken = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);

		}

		if ($laskuri > 0) $iltasiivo .= "Mit�t�itiin $laskuri tilausta joilla oli pelkki� mit�t�ityj� rivej�.\n";


		$laskuri = 0;
		
		// Merkit��n rivit mit�t�idyksi joiden otsikot on mit�t�ity
		$query = "	SELECT lasku.tunnus laskutunnus
					from lasku
					join tilausrivi on tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus and tilausrivi.tyyppi!='D'
					where lasku.yhtio = '$kukarow[yhtio]' 
					and lasku.tila = 'D'
					GROUP BY 1";
		$result = mysql_query($query) or die($query);

		while ($row = mysql_fetch_array($result)) {
			$komm = "(" . $kukarow['kuka'] . "@" . date('Y-m-d') .") ".t("Mit�t�i ohjelmassa iltasiivo.php (1.5)")."<br>";

			$query = "UPDATE tilausrivi set tyyppi='D'  where yhtio = '$kukarow[yhtio]' and otunnus = '$row[laskutunnus]'";
			$deler = mysql_query($query) or die($query);
			$laskuri ++;
		}

		if ($laskuri > 0) $iltasiivo .= "Mit�t�itiin $laskuri mit�t�idyn tilauksen rivit. (Rivit jostain syyst� ei dellattuja)\n";


		// t�ss� tehd��n isitt�mist� perheist� ei-perheit� ja my�s perheist� joissa ei ole lapsia eli nollataan perheid
		$lask = 0;
		$lask2 = 0;

		$query = "	SELECT perheid, count(*) koko
					FROM tilausrivi
					WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid != '0'
					GROUP BY perheid";
		$result = mysql_query($query) or pupe_error($query);

		while ($row = mysql_fetch_array($result)) {
			$query = "	SELECT perheid
						FROM tilausrivi
						WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and tunnus = '$row[perheid]'";
			$result2 = mysql_query($query) or pupe_error($query);
			
			if (mysql_num_rows($result2) == 0) {
				$lask++;
				$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
				$upresult = mysql_query($query) or pupe_error($query);
			}
			else {
				if ($row['koko'] == 1) {
					$lask2++;
					$query = "UPDATE tilausrivi SET perheid = 0 WHERE yhtio = '$kukarow[yhtio]' and tyyppi = 'L' and laskutettuaika = '0000-00-00' and perheid = '$row[perheid]' order by tunnus";
					$upresult = mysql_query($query) or pupe_error($query);
				}
			}
		}

		if ($lask+$lask2 > 0) {
			$iltasiivo .= "Tuhotiin $lask orpoa perhett�, ja $lask2 lapsetonta is�� (eli perheid nollattiin)\n";
		}

		$lasktuote = 0;
		$laskpois = 0;
		$poistetaankpl = 0;

		$query = "	SELECT tuoteno, liitostunnus, count(tunnus) countti
					FROM tuotteen_toimittajat
					WHERE yhtio = '$kukarow[yhtio]'
					GROUP BY 1,2
					HAVING countti > 1";
		$result = mysql_query($query) or pupe_error($query);
		
		while ($row = mysql_fetch_array($result)) {
			$lasktuote++;
			$poistetaankpl = $row['countti']-1;

			$poisquery = "	DELETE FROM tuotteen_toimittajat
							WHERE yhtio = '$kukarow[yhtio]'
							AND tuoteno = '$row[tuoteno]'
							AND liitostunnus = '$row[liitostunnus]'
							ORDER BY tunnus DESC
						 	LIMIT $poistetaankpl";
			$poisresult = mysql_query($poisquery) or pupe_error($poisquery);
			$laskpois += mysql_affected_rows();
		}

		if ($lasktuote > 0) {
			$iltasiivo .= "Poistettiin $lasktuote tuotteelta yhteens� $laskpois duplikaatti tuotteen_toimittajaa\n";
		}

		
		if ($iltasiivo != "") {

			echo "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]\n\n";
			echo $iltasiivo;
			echo "\n";

			if($iltasiivo_email == 1) {
				$header 	= "From: <$yhtiorow[postittaja_email]>\n";
				$header 	.= "MIME-Version: 1.0\n" ;
				$subject 	= "Iltasiivo ".date("d.m.Y")." - $yhtiorow[nimi]";

				mail($yhtiorow["admin_email"], "Iltasiivo yhtiolle '{$yhtiorow["yhtio"]}'", $iltasiivo, $header, " -f $yhtiorow[postittaja_email]");
			}
		}
	}

	if (trim($argv[1]) == '') {
		echo "</pre>";
		require('inc/footer.inc');
	}

?>