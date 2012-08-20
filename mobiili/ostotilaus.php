<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if(!isset($errors)) $errors = array();

# Jos haulla ei l�ytyny mit��n, ollaan palattu t�lle sivulle virheparametrilla.
if (isset($virhe)) {
	$errors['virhe'] = "Ei l�ytynyt. Hae uudestaan.";
}

$data['kesken'] = $kesken;

if (isset($submit)) {
	switch($submit) {
		case 'ok':
			# Haettu v�hint��n yhdell� kent�ll�
			if (empty($data['viivakoodi']) and empty($data['tuotenumero']) and empty($data['ostotilaus'])) {
				$errors['ostotilaus'] = "V�hint��n yksi kentt� on sy�tett�v�";
				break;
			}
			# Rakennetaan parametrit kentist�
			$url = http_build_query($data);

			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tuotteella_useita_tilauksia.php?{$url}'>"; exit();
			break;
		case 'cancel':
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>"; exit();
		   	break;
		default:
			$errors['virhe'] = "Yll�tt�v� virhe";
			break;
	}
}

### UI ###
include("kasipaate.css");

echo "
<div class='header'><h1>",t("OSTOTILAUS"),"</h1></div>";

echo "<div class='main'>
<form method='post' action=''>
<table>
	<tr>
		<th><label for='viivakoodi'>Viivakoodi</label></th>
		<td><input type='text' id='viivakoodi' name='data[viivakoodi]' /><td>
	</tr>
	<tr>
		<th><label for='tuotenumero'>Tuotenumero</label></th>
		<td><input type='text' id='tuotenumero' name='data[tuotenumero]'/><td>
	</tr>
	<tr>
		<th><label for='ostotilaus'>Ostotilaus</label></th>
		<td><input type='text' id='ostotilaus' name='data[ostotilaus]'/><td>
	</tr>
</table>
</div>";

echo "<div class='controls'>
	<button name='submit' value='ok' onclick='submit();'>",t("OK", $browkieli),"</button>
	<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</form>
</div>";

echo "<div class='error'>";
    foreach($errors as $virhe => $selite) {
        echo strtoupper($virhe).": ".$selite."<br>";
    }
echo "</div>";

if (isset($kesken)) echo "K�ytt�j�ll� saapuminen: {$kesken} kesken.";