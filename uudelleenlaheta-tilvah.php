<?php

print "<font class='head'>Uudelleenl�het� tilausvahvistus</font><hr>";

require ("inc/parametrit.inc");

if ($tee == "laheta" and $tunnukset != "") {

	$query = "SELECT * FROM lasku WHERE yhtio = '$kukarow[yhtio]' AND tila in ('N','L') AND tunnus in ($tunnukset)";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		while ($laskurow = mysql_fetch_array($result)) {

			echo "Uudelleenl�hetet��n tilausvahvistus ($laskurow[tilausvahvistus]): $laskurow[nimi]<br>";

			chdir("tilauskasittely");

			if (strpos(" ".$laskurow['tilausvahvistus'],'E'))
				require("tilausvahvistus-edi.inc");

			if (strpos(" ".$laskurow['tilausvahvistus'],'S'))
				require("tilausvahvistus-email.inc");

			if (strpos(" ".$laskurow['tilausvahvistus'],'F'))
				require("tilausvahvistus-fax.inc");

			if (strpos(" ".$laskurow['tilausvahvistus'],'O'))
				require("tilausvahvistus-email.inc");

			if (strpos(" ".$laskurow['tilausvahvistus'],'U'))
				require("tilausvahvistus-futursoft.inc");

		}

	}
	else {
		print "<font class='error'>Tilauksia ei l�ytynyt: $tunnukset!</font><br>";
	}
}
else {
	echo "<font class='message'>Anna tilausnumerot pilkulla eroteltuna</font><br>";
	echo "<form method='post'>";
	echo "<input type='hidden' name='tee' value='laheta'>";
	echo "<input name='tunnukset' type='text' size='60'>";
	echo "<input type='submit' value='L�het� tilausvahvistukset'>";
	echo "</form>";
}

?>