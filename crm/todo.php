<?php

require ("../inc/parametrit.inc");

echo "<font class='head'>TODO (tu-duu)</font><hr>";

$kesto_arvio = str_replace(',','.',$kesto_arvio);

if ($sort != '') {
	$sortlisa = "?sort=".$sort;
}

if ($tee == "muokkaa") {
	$query  = "select * from todo where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$rivi   = mysql_fetch_array($result);

	$prio_sel = array();
	$prioriteetti = $rivi["prioriteetti"];
	$prio_sel[$prioriteetti] = "SELECTED";

	echo "<form method='post' action='todo.php'>
	<input type='hidden' name='tee' value='paivita'>
	<input type='hidden' name='tunnus' value='$rivi[tunnus]'>
	<input type='hidden' name='vanhakuittaus' value='$rivi[kuittaus]'>

	<table>
		<tr>
			<th>kuvaus</th>
			<th>pyyt�j�</th>
			<th>aika-arvio</th>
			<th>aika-tot.</th>
			<th>deadline</th>
			<th>projekti</th>
			<th>prio</th>
			<th>kuittaus</th>
			<th></th>
		</tr>
		<tr>
			<td><textarea name='kuvaus' cols='60' rows='4'>$rivi[kuvaus]</textarea></td>
			<td><input name='pyytaja' type='text' size='15' value='$rivi[pyytaja]'></td>
			<td><input name='kesto_arvio' type='text' size='6' value='$rivi[kesto_arvio]'></td>
			<td><input name='kesto_toteutunut' type='text' size='6' value='$rivi[kesto_toteutunut]'></td>
			<td><input name='deadline' type='text' size='10' value='$rivi[deadline]'></td>
			<td><input name='projekti' type='text' size='15' value='$rivi[projekti]'></td>

			<td>
				<input name='prioriteetti' type='text' size='5' value='$prioriteetti'></td>
			</td>

			<td><input name='kuittaus' type='text' size='15' value='$rivi[kuittaus]'></td>

			<td><input value='go' type='submit'></td>

		</tr>

	</table>
	</form>";
}

if (strtoupper($kuittaus) == "POIS") {
	if ($tee == "paivita" or $tee == "valmis") {
		$query  = "delete from todo where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
		$result = mysql_query($query) or pupe_error($query);
		$tee = "";
	}
}

if ($tee == "paivita") {

	$lisa = "";

	if ($kuittaus != "" and $vanhakuittaus == "") {
		$lisa = ", kuittaus = '$kuittaus', aika = now()";
	}
	elseif ($vanhakuittaus != "") {
		$lisa = ", kuittaus = '$kuittaus'";
	}

	$query  = "	update todo set
				kuvaus           = '$kuvaus',
				prioriteetti     = '$prioriteetti',
				projekti         = '$projekti',
				pyytaja          = '$pyytaja',
				kesto_arvio      = '$kesto_arvio',
				kesto_toteutunut = '$kesto_toteutunut',
				muuttaja		 = '$kukarow[kuka]', 
				muutospvm		 = now(),
				deadline         = '$deadline'
				$lisa
				where yhtio = '$kukarow[yhtio]' and
				tunnus = '$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($tee == "uusi" and $kuvaus != "") {
	$query  = "insert into todo (yhtio, kuvaus, aika, prioriteetti, projekti, pyytaja, kesto_arvio, laatija, luontiaika, deadline) values ('$kukarow[yhtio]', '$kuvaus', now(), '$prioriteetti', '$projekti', '$pyytaja', '$kesto_arvio', '$kukarow[kuka]', now(), '$deadline')";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($tee == "uusi" and $kuvaus == "") {
	$tee = "";
}

if ($tee == "valmis") {
	$query  = "update todo set kuittaus='$kuittaus', aika=now(), muuttaja='$kukarow[kuka]', muutospvm=now() where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$tee = "";
}

if ($sort == "pyytaja")			$sort = "order by pyytaja,prioriteetti,deadline,projekti,aika";
elseif ($sort == "projekti")	$sort = "order by projekti,prioriteetti,deadline,aika";
elseif ($sort == "kesto_arvio")	$sort = "order by kesto_arvio,prioriteetti,deadline,aika";
elseif ($sort == "kuvaus")		$sort = "order by kuvaus,prioriteetti,deadline,aika";
elseif ($sort == "deadline")	$sort = "order by deadline,prioriteetti,aika";
else 							$sort = "order by prioriteetti,deadline,kesto_arvio desc,aika,projekti";

if ($tee == "") {

	$query = "select *, if(deadline='0000-00-00','9999-99-99',deadline) deadline from todo where yhtio = '$kukarow[yhtio]' and kuittaus = '' $sort";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 0) {
		echo "<font class='message'>Ei tekem�tt�mi� duuneja! Hienoa!</font><br>";
	}
	else {

		$tunnit = 0;
		$numero = 0;

		echo "<table>

		<tr>
			<th><a href='?sort=none'>#</a></th>
			<th><a href='?sort=kuvaus'>kuvaus</a></th>
			<th><a href='?sort=pyytaja'>pyyt�j�</a></th>
			<th><a href='?sort=projekti'>projekti</a></th>
			<th><a href='?sort=kesto_arvio'>aika-arvio</a></th>
			<th><a href='?sort=deadline'>deadline</a></th>
			<th><a href='?sort=prioriteetti'>prio</a></th>
			<th>kuittaus</th>
		</tr>";

		while ($rivi = mysql_fetch_array($result)) {

			$sel = array();
			$sel[$rivi["prioriteetti"]] = "SELECTED";

			$tunnit += $rivi["kesto_arvio"];
			$numero++;

			if ($rivi["prioriteetti"] == 0) {
				$rivi["prioriteetti"] = "bug";
			}

			if ($rivi["deadline"] == '9999-99-99') {
				$rivi["deadline"] = "";
			}

			echo "<tr class='aktiivi'>";

			echo "<form method='post' name='todo' action='todo.php$sortlisa' autocomplete='off'>";
			echo "<input type='hidden' name='tee' value='valmis'>";
			echo "<input type='hidden' name='tunnus' value='$rivi[tunnus]'>";

			echo "<th><a href='?tunnus=$rivi[tunnus]&tee=muokkaa'>$numero</a></th>";

			$rivi["kuvaus"] = str_replace("\n", "<br>", $rivi["kuvaus"]);

			echo "<td width='550'>$rivi[kuvaus]</td>";
			echo "<td>$rivi[pyytaja]</td>";
			echo "<td>$rivi[projekti]</td>";
	        echo "<td>$rivi[kesto_arvio]</td>";
	        echo "<td>$rivi[deadline]</td>";
	        echo "<td>$rivi[prioriteetti]</td>";
			echo "<td><input type='text' size='7' name='kuittaus'></td>";

			echo "</form>";
			echo "</tr>\n";
		}

		echo "<tr><th colspan='4'>Aika-arvio yhteens�</th><th colspan='4'>$tunnit h = ".round($tunnit/8,0)." pv</th></tr>";

		echo "</table><br>";

	}


	$query = "select * from todo where yhtio = '$kukarow[yhtio]' and kuittaus != '' order by aika desc limit 20";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) > 0) {

		echo "<font class='head'>20 VIIMEKSI TEHTY�</font><hr>";
		echo "<table>";

		echo "
		<tr>
			<th>#</th>
			<th>kuvaus</th>
			<th>pyyt�j�</th>
			<th>projekti</th>
			<th>prio</th>
			<th>aika-arvio</th>
			<th>aika-tot.</th>
			<th>kuittaus</th>
		</tr>
		";

		$numero = 0;

		while ($rivi = mysql_fetch_array($result)) {

			if ($rivi["prioriteetti"] == 0) {
				$rivi["prioriteetti"] = "bug";
			}

			$numero ++;

			echo "<tr class='aktiivi'>";
			echo "<th><a href='?tunnus=$rivi[tunnus]&tee=muokkaa'>$numero</a></th>";
			echo "<td width='550'>$rivi[kuvaus]</td>";
			echo "<td>$rivi[pyytaja]</td>";
			echo "<td>$rivi[projekti]</td>";
			echo "<td>$rivi[prioriteetti]</td>";
			echo "<td>$rivi[kesto_arvio]</td>";
			echo "<td>$rivi[kesto_toteutunut]</td>";
			echo "<td>$rivi[kuittaus]</td>";
			echo "</tr>\n";
		}
	}

	echo "</table>";

	echo "
	<br><font class='head'>LIS�� UUSI</font><hr>

	<form name='uusi' method='post' action='todo.php'>
	<input type='hidden' name='tee' value='uusi'>

	<table>
		<tr>
			<th>kuvaus</th>
			<th>pyyt�j�</th>
			<th>aika-arvio</th>
			<th>deadline</th>
			<th>projekti</th>
			<th>prio</th>
			<th></th>
		</tr>
		<tr>
			<td><textarea name='kuvaus' cols='80' rows='2'></textarea></td>
			<td><input name='pyytaja' type='text' size='15'></td>
			<td><input name='kesto_arvio' type='text' size='6'></td>
			<td><input name='deadline' type='text' size='10'</td>
			<td><input name='projekti' type='text' size='15'></td>

			<td>
				<select name='prioriteetti'>
				<option value='0'>bug</option>
				<option value='1'>1</option>
				<option value='2'>2</option>
				<option value='3'>3</option>
				<option value='4'>4</option>
				<option value='5'>5</option>
				<option value='6'>6</option>
				<option value='7'>7</option>
				<option value='8'>8</option>
				<option value='9' selected>9</option>
				</select>
			</td>

			<td><input value='go' type='submit'></td>

		</tr>

	</table>
	</form>";

}

require ("../inc/footer.inc");

?>