<?php

require "../inc/parametrit.inc";
require_once '../inc/pupeExcel.inc';
require '../inc/ProgressBar.class.php';

if($tee == 'lataa_tiedosto') {
	$filepath = "/tmp/".$tmpfilenimi;
	if(file_exists($filepath)) {
		readfile($filepath);
		unlink($filepath);
	}
	exit;
}

?>
<script>
	$(document).ready(function() {
		tuotteittain_group_by_bind();
	});

	function tuotteittain_group_by_bind() {
		$('#tuotteittain_group').on('click', function() {
			$('#nayta_tuotteiden_nimitykset').prop('checked', $('#tuotteittain_group').prop('checked'));
		});
	}

	function tarkista() {
		if($('#ppa').val() == '' || $('#kka').val() == '' || $('#vva').val() == '' || $('#ppl').val() == '' || $('#kkl').val() == '' || $('#vvl').val() == '') {
			alert($('#paivamaara_vaarin').html());
			return false;
		}

		return true;
	}
</script>
<?php

echo "<font class='head'>".t('Matkalaskuraportti')."</font><hr>";

echo "<div id='paivamaara_vaarin' style='display:none;'>".t("Antamasi p�iv�m��r� on virheellinen")."</div>";

$request_params = array(
	"ajotapa" => $ajotapa,
	"tuotetyypit" => $tuotetyypit,
	"jarjestys" => $jarjestys,
	"mul_kustp" => $mul_kustp,
	"kenelta_kustp" => $kenelta_kustp,
	"ruksit" => $ruksit,
	"tuotenro" => $tuotenro,
	"toimittajanro" => $toimittajanro,
	"matkalaskunro" => $matkalaskunro,
	"tuotteet_lista" => $tuotteet_lista,
	"piilota_kappaleet" => $piilota_kappaleet,
	"nimitykset" => $nimitykset,
	"tilrivikomm" => $tilrivikomm,
	"laskunro" => $laskunro,
	"maksutieto" => $maksutieto,
	"tapahtumapaiva" => $tapahtumapaiva,
	"ppa" => $ppa,
	"kka" => $kka,
	"vva" => $vva,
	"ppl" => $ppl,
	"kkl" => $kkl,
	"vvl" => $vvl,
	"tmpfilenimi" => $tmpfilenimi,
	"kaunisnimi" => $kaunisnimi,
	"debug" => 1,
);

if($request_params['debug'] == 1) {
	echo "<pre>";
	var_dump($_REQUEST);
	echo "</pre>";
}

if($tee == 'aja_raportti') {
	$rivit = generoi_matkalaskuraportti_rivit($request_params);

	//p��tet��n mit� tehd��n datalle
	//s�ilytet��n mahdollisuus printata my�s pdf:lle tiedot
	$request_params['tiedosto_muoto'] = "xls";
	if($request_params['tiedosto_muoto'] == "xls") {
		$tiedosto = generoi_excel_tiedosto($rivit, $request_params);
	}

	echo_matkalaskuraportti_form($request_params);
	echo "<br/>";
	echo "<font class='message'>".t("Raportti on ajettu")."</font>";
	echo "<br/>";
	echo "<br/>";
	echo_tallennus_formi($tiedosto);
}
else {
	echo_matkalaskuraportti_form($request_params);
}

function generoi_matkalaskuraportti_rivit($request_params) {
	global $kukarow;

	$where = generoi_where_ehdot($request_params);
	$select = generoi_select($request_params);
	$group = generoi_group_by($request_params);
	$tuote_join = generoi_tuote_join($request_params);
	$toimi_join = generoi_toimi_join($request_params);
	$kustannuspaikka_join = generoi_kustannuspaikka_join($request_params);

	$query = "	SELECT
				{$select}
				FROM lasku
				JOIN tilausrivi
				ON ( tilausrivi.yhtio = lasku.yhtio AND tilausrivi.otunnus = lasku.tunnus AND tilausrivi.tyyppi = 'M')
				JOIN tilausrivin_lisatiedot
				ON ( tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio and tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus )
				JOIN tuote
				{$tuote_join}
				JOIN toimi
				{$toimi_join}
				JOIN kustannuspaikka
				{$kustannuspaikka_join}
				{$where}
				{$group}";

	if($request_params['debug'] == 1) {
		echo "<pre>";
		var_dump($query);
		echo "</pre>";
	}

	$result = pupe_query($query);

	$rivit = array();
	while($rivi = mysql_fetch_assoc($result)) {
		$rivit[] = $rivi;
	}

	return $rivit;
}

function generoi_where_ehdot($request_params) {
	global $kukarow;

	$where = 'WHERE ';

	if(!empty($request_params['ppa']) and !empty($request_params['kka']) and !empty($request_params['vva']) and !empty($request_params['ppl']) and !empty($request_params['kkl']) and !empty($request_params['vvl'])) {
		$where .= "lasku.yhtio = '{$kukarow['yhtio']}'
		AND (lasku.tapvm >= '{$request_params['vva']}-{$request_params['kka']}-{$request_params['ppa']}'
			AND lasku.tapvm < '{$request_params['vvl']}-{$request_params['kkl']}-{$request_params['ppl']}') ";
	}

	if(!empty($request_params['ajotapa'])) {
		$where .= ajotapa_where($request_params);
	}

	if(!empty($request_params['matkalaskunro'])) {
		$where .= matkalaskunro_where($request_params);
	}

	return $where;
}

function ajotapa_where($request_params) {
	$where = "";
	switch ($request_params['ajotapa']) {
		case 'keskeneraiset':
			$where .= "AND lasku.tila = 'H' AND lasku.alatila = 'M'";
			break;
		case 'maksamattomat':
			$where .= "AND lasku.tila = 'H' AND lasku.alatila = ''";
			break;
		case 'maksetut':
			$where .= "AND lasku.tila = 'Y' AND lasku.alatila = ''";
			break;
		case 'keskeneraiset_maksamattomat':
			$where .= "AND (lasku.tila = 'H' AND lasku.alatila = 'M') OR (lasku.tila = 'H' AND lasku.alatila = '')";
			break;
		case 'maksamattomat_maksetut':
			$where .= "AND (lasku.tila = 'H' AND lasku.alatila = '') OR (lasku.tila = 'Y' AND lasku.alatila = '')";
			break;
	}

	return $where;
}

function matkalaskunro_where($request_params) {
	//TODO MATKALASKU RAJAUS annetaanko tunnuksia pilkulla eroteltuna vai H�?
	return "AND lasku.tunnus IN ({$request_params['matkalaskunro']})";
}

function generoi_tuote_join($request_params) {
	$tuote_join = "ON ( tuote.yhtio = lasku.yhtio and tuote.tuoteno=tilausrivi.tuoteno ";
	if(!empty($request_params['tuotetyypit'])) {
		$tuotetyypit = implode("','", $request_params['tuotetyypit']);
		$tuote_join .= " AND tuote.tuotetyyppi IN ('{$tuotetyypit}')";
	}

	if(!empty($request_params['tuotteet_lista'])) {
		//TODO t�h�n pit�� varmaan laittaa jotain validaatiota sun muuta???????? pilkulla eroteltunako tulee
		$tuote_join .= " AND tuote.tuoteno IN ({$request_params['tuotteet_lista']})";
	}

	if($request_params['kenelta_kustp'] == "tuotteilta") {
		if(!empty($request_params['mul_kustp'])) {
			$tuote_join .= " AND tuote.kustp IN (".implode(',', $request_params['mul_kustp']).")";
		}
	}

	$tuote_join .= " )";
	return $tuote_join;
}

function generoi_kustannuspaikka_join($request_params) {
	$kustannuspaikka_join = "ON ( kustannuspaikka.yhtio = lasku.yhtio ";

	switch($request_params['kenelta_kustp']) {
		case 'toimittajilta':
			$kustannuspaikka_join .= "AND kustannuspaikka.tunnus = toimi.kustannuspaikka";
			break;
		case 'tuotteilta':
			$kustannuspaikka_join .= " AND kustannuspaikka.tunnus = tuote.kustp";
			break;
	}

	$kustannuspaikka_join .= " )";

	return $kustannuspaikka_join;
}

function generoi_toimi_join($request_params) {
	$toimi_join = "ON ( toimi.yhtio = lasku.yhtio AND toimi.tunnus = lasku.liitostunnus";
	if($request_params['kenelta_kustp'] == "toimittajilta") {
		if(!empty($request_params['mul_kustp'])) {
			$toimi_join .= " AND toimi.kustannuspaikka IN (".implode(',', $request_params['mul_kustp']).")";
		}
	}

	$toimi_join .= " )";

	return $toimi_join;
}

function generoi_select($request_params) {
	$mukaan_tulevat = jarjesta_prioriteetit($request_params);
	$select = "";

	//generoidaan selectit annettujen grouppauksien prioriteetin mukaan, jotta kent�t ovat printtaus vaiheessa oikeassa j�rjestyksess�.
	foreach($mukaan_tulevat as $group => $value) {
		$select .= select_group_byn_mukaan($request_params, $group);
	}

	//group: kaikki
	if(empty($request_params['piilota_kappaleet'])) {
		//jos on mik� tahansa grouppi niin tilausrivi.kpl pit�� summata
		if($request_params['ruksit']['tuotteittain']
				or $request_params['ruksit']['toimittajittain']
				or $request_params['ruksit']['matkalaskuittain']
				or $request_params['ruksit']['tuotetyypeittain']
				or $request_params['ruksit']['kustp']) {
			//TODO kun halutaan piilottaa kappaleet niin piilotetaanko pelk�st��n kappaleet ja n�ytet��n ilmaiset lounaat sun muut?
			$select .= "sum(tilausrivi.kpl) as kpl, sum(tilausrivi.erikoisale) as ilmaiset_lounaat, sum(tilausrivi.hinta) as hinta, sum(tilausrivi.rivihinta) as rivihinta, ";
		}
		else {
			$select .= "tilausrivi.kpl, tilausrivi.erikoisale as ilmaiset_lounaat, ";
		}
	}

	if(!empty($request_params['tilrivikomm'])) {
		$select .= "tilausrivi.kommentti, ";
	}

	if(!empty($request_params['maksutieto'])) {
		//TODO mik� on maksutieto?
		$select .= "";
	}

	$select = substr($select, 0, -2);

	return $select;
}

function select_group_byn_mukaan($request_params, $group) {
	$select = "";
	switch($group) {
		case 'kustp':
			$select .= "kustannuspaikka.tunnus as kustp_tunnus, kustannuspaikka.nimi as kustp_nimi, ";
			break;
		case 'toimittajittain':
			$select .= "toimi.tunnus as toimi_tunnus, toimi.nimi as toimi_nimi, ";
			break;
		case 'tuotteittain':
			//tuotteiden nimityksen n�ytet��n kun: nimitykset checked ja grouptaan tuotteittain
			if(!empty($request_params['nimitykset'])) {
				$select .= "tilausrivi.nimitys, ";
			}
			//matkalasku rivin muita tietoja, kuin kpl halutaan n�ytt�� vain jos grouptaan tuotteittain
			$select .= "tilausrivi.tuoteno, tilausrivi.keratty, tilausrivi.toimitettu, ";
			break;
		case 'tuotetyypeittain':
			$select .= "tuote.tuotetyyppi, ";
			break;
		case 'matkalaskuittain':
			$select .= "lasku.tunnus as lasku_tunnus, ";
			//laskunumero n�ytet��n kun: Piilota laskunumero not checked ja groupataan laskun mukaan
			if(!empty($request_params['laskunro'])) {
				$select .= "lasku.laskunro, ";
			}
			//n�ytet��n: kun tapahtuma p�iv� checked ja groupataan matkalaskuittain
			if(!empty($request_params['tapahtumapaiva'])) {
				$select .= "lasku.tapvm, ";
			}
			$select .= "lasku.summa, ";
			break;
	}

	return $select;
}

function generoi_group_by($request_params) {
	//selectoidaan vain valitut grouppaukset mukaan
	$group_by = "";

	$mukaan_tulevat = jarjesta_prioriteetit($request_params);

	if(!empty($mukaan_tulevat)) {
		$group_by = "GROUP BY ";
		foreach($mukaan_tulevat as $index => $value) {
			switch($index) {
				case 'kustp':
					$group_by .= "kustannuspaikka.tunnus, ";
					break;
				case 'toimittajittain':
					$group_by .= "toimi.tunnus, ";
					break;
				case 'tuotteittain':
					//tuotteittain groupattaessa pit�� k�ytt�� nimityst�, koska ilmaiset lounaat toiminnallisuutta implementoitaessa osap�iv�raha otettiin pois tuoteelta ja liitettiin kokop�iv�rahan kanssa samaan
					//my�skin var kent�n toiminnallisuus muutettiin, mink� seurauksena emme voi groupata sen perusteella, koska se ei ole taaksep�in yhteensopiva
					$group_by .= "tilausrivi.nimitys, ";
					break;
				case 'tuotetyypeittain':
					$group_by .= "tuote.tuotetyyppi, ";
					break;
				case 'matkalaskuittain':
					$group_by .= "lasku.tunnus, ";
					break;
			}
		}
		//poistetaan viimeiset 2 merkki� ", " group by:n lopusta
		$group_by = substr($group_by, 0, -2);
	}

	return $group_by;
}

function jarjesta_prioriteetit($request_params) {
	$mukaan_tulevat = array();
	foreach ($request_params['ruksit'] as $index => $value) {
		if ($value != '') {
			$mukaan_tulevat[$index] = $request_params['jarjestys'][$index];
		}
	}
	//t�ss� meill� on mukaan tulevat grouppaukset, nyt array pit�� sortata niin, ett� pienin prioriteetti tulee ensimm�iseksi ja tyhj�t pohjalle
	asort($mukaan_tulevat);
	//t�ll� saadaan tyhj�t valuet arrayn pohjalle
	$mukaan_tulevat = array_diff($mukaan_tulevat, array('')) + array_intersect($mukaan_tulevat, array(''));
	/* php > $arr = array(0 => '1', 1 => '3', 2 => '2', 3 => '', 4 => '', 5 => '6'); asort($arr); $re = array_diff($arr, array('')) + array_intersect($arr, array('')); echo print_r($re);
	  Array
	  (
	  [0] => 1
	  [2] => 2
	  [1] => 3
	  [5] => 6
	  [3] =>
	  [4] =>
	  )
	 */

	return $mukaan_tulevat;
}

function echo_matkalaskuraportti_form($request_params) {
	global $kukarow;

	$now = date('d-m-Y');
	$last_month = date('d-m-Y', strtotime($now . '-1 month'));
	$now = explode('-', $now);
	$last_month = explode('-', $last_month);
	
	if ($request_params['ruksit']['tuotetyypeittain'] != '')   	$ruk_tuotetyypeittain_chk	= "CHECKED";
	if ($request_params['ruksit']['tuotteittain'] != '')   		$ruk_tuotteittain_chk   	= "CHECKED";
	if ($request_params['ruksit']['toimittajittain'] != '')   	$ruk_toimittajittain_chk   	= "CHECKED";
	if ($request_params['ruksit']['matkalaskuittain'] != '')   	$ruk_matkalaskuittain_chk	= "CHECKED";
	if ($request_params['piilota_kappaleet'] != '')				$piilota_kappaleet_chk		= "CHECKED";
	if ($request_params['nimitykset'] != '')					$nimchk						= "CHECKED";
	if ($request_params['tilrivikomm'] != '')					$tilrivikommchk				= "CHECKED";
	if ($request_params['laskunro'] != '')						$laskunrochk   				= "CHECKED";
	if ($request_params['maksutieto'] != '')					$maksutietochk				= "CHECKED";
	if ($request_params['tapahtumapaiva'] != '')				$tapahtumapaivachk			= "CHECKED";
	
	if($request_params['ppl'] == '')							$request_params['ppl']		= $now[0];
	if($request_params['kkl'] == '')							$request_params['kkl']		= $now[1];
	if($request_params['vvl'] == '')							$request_params['vvl']		= $now[2];

	if($request_params['ppa'] == '')							$request_params['ppa']		= $last_month[0];
	if($request_params['kka'] == '')							$request_params['kka']		= $last_month[1];
	if($request_params['vva'] == '')							$request_params['vva']		= $last_month[2];

	$jarjestys['kustp'] = $request_params['jarjestys']['kustp'];
	$ruksit["kustp"] = $request_params['ruksit']['kustp'];
	//asetetaan toimittajilta default valueksi
	$kenelta_kustp = ($request_params['kenelta_kustp'] == ''? 'toimittajilta' : $request_params['kenelta_kustp']);
	
	$ajotavat = array(
		"keskeneraiset" => t("Keskener�iset"),
		"maksamattomat" => t("Maksamattomat"),
		"maksetut" => t("Maksetut"),
		"keskeneraiset_maksamattomat" => t("Keskener�iset ja maksamattomat"),
		"maksamattomat_maksetut" => t("Maksamattomat ja maksetut"),
	);
	$tuotetyypit = array(
		"A" => t("P�iv�raha"),
		"B" => t("Muu kulu"),
	);

	echo "<form name='matkalaskuraportti' method='POST'>";
	echo "<input type='hidden' name='tee' value='aja_raportti' />";
	echo "<table id='ajotavat'>";

	echo "<tr>";
	echo "<th>".t("Valitse Ajotapa")."</th>";
	echo "<td>";
	echo "<select name='ajotapa'>";
	$sel = "";
	foreach($ajotavat as $ajotapa_key => $ajotapa_value) {
		if($ajotapa_key == $request_params['ajotapa']) {
			$sel = "SELECTED";
		}
		echo "<option value='{$ajotapa_key}' $sel>{$ajotapa_value}</option>";
		$sel = "";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<br/>";

	echo "<table id='tuotetyypit'>";
	echo "<tr>";
	echo "<th>".t("Valitse tuotetyypit")."</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "<select id='tuotetyypit' multiple='multiple' name='tuotetyypit[]'>";
	$sel = "";
	foreach($tuotetyypit as $tuotetyyppi_key => $tuotetyyppi_value) {
		if(is_array($request_params['tuotetyypit']) and in_array($tuotetyyppi_key, $request_params['tuotetyypit'])) {
			$sel = "SELECTED";
		}
		echo "<option value='$tuotetyyppi_key' $sel>$tuotetyyppi_value</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>";
	echo t("Prio").": <input type='text' name='jarjestys[tuotetyypeittain]' size='2' value='{$request_params['jarjestys']['tuotetyypeittain']}'> ";
	echo t("Tuotetyypeitt�in")." <input type='checkbox' name='ruksit[tuotetyypeittain]' value='tuotetyypeittain' $ruk_tuotetyypeittain_chk>";
	echo "</th>";
	echo "</tr>";
	echo "</table>";

	$noautosubmit = TRUE;
	$monivalintalaatikot = array("<br>KUSTP");
	$monivalintalaatikot_normaali = array();

	require ("../tilauskasittely/monivalintalaatikot.inc");

	echo "<br/><br/>";

	echo "<table id='lisarajaus'>";
	echo "<tr>
			<th>".t("Lis�rajaus")."</th>
			<th>".t("Prio")."</th>
			<th> x</th>
			<th>".t("Rajaus")."</th>
		</tr>";
	echo "<tr></tr>";
	echo "<tr>
			<th>".t("Listaa tuotteittain")."</th>
			<td><input type='text' name='jarjestys[tuotteittain]' size='2' value='{$request_params['jarjestys']['tuotteittain']}'></td>
			<td><input id='tuotteittain_group' type='checkbox' name='ruksit[tuotteittain]' value='tuotteittain' {$ruk_tuotteittain_chk}></td>
			<td><input type='text' name='tuotenro' value='{$request_params['tuotenro']}'></td>
		</tr>";
	echo "<tr>
			<th>".t("Listaa toimittajittain")."</th>
			<td><input type='text' name='jarjestys[toimittajittain]' size='2' value='{$request_params['jarjestys']['toimittajittain']}'></td>
			<td><input type='checkbox' name='ruksit[toimittajittain]' value='toimittajittain' {$ruk_toimittajittain_chk}></td>
			<td><input type='text' name='toimittajanro' value='{$request_params['toimittajanro']}'></td>
		</tr>";
	echo "<tr>
			<th>".t("Listaa matkalaskuittain")."</th>
			<td><input type='text' name='jarjestys[matkalaskuittain]' size='2' value='{$request_params['jarjestys']['matkalaskuittain']}'></td>
			<td><input type='checkbox' name='ruksit[matkalaskuittain]' value='matkalaskuittain' {$ruk_matkalaskuittain_chk}></td>
			<td><input type='text' name='matkalaskunro' value='{$request_params['matkalaskunro']}'></td>
		</tr>";
	echo "</table>";

	echo "<br/><br/>";

	echo "<table id='tuotelista'>";
	echo "<tr>
			<th valign='top'>".t("Tuotelista")."<br>(".t("Rajaa n�ill� tuotteilla").")</th>
			<td colspan='3'><textarea name='tuotteet_lista' rows='5' cols='35'>{$request_params['tuotteet_lista']}</textarea></td>
		</tr>";
	echo "</table>";

	echo "<br/><br/>";

	echo "<table id='naytto'>";
	echo "<tr>
			<th>".t("Piilota kappaleet")."</th>
			<td colspan='3'><input type='checkbox' name='piilota_kappaleet' {$piilota_kappaleet_chk}></td>
		</tr>";
	echo "<tr>
			<th>".t("N�yt� tuotteiden nimitykset")."</th>
			<td colspan='3'><input id='nayta_tuotteiden_nimitykset'type='checkbox' name='nimitykset' {$nimchk}></td>
			<td class='back'>".t("(Kun listaat tuotteittain valitse t�m�!)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("N�yt� tilausrivin kommentti")."</th>
			<td colspan='3'><input type='checkbox' name='tilrivikomm' {$tilrivikommchk}></td>
			<td class='back'>".t("(Listataan kaikki rivit)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("N�yt� my�s laskunumero")."</th>
			<td colspan='3'><input type='checkbox' name='laskunro' {$laskunrochk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("N�yt� my�s maksuetieto")."</th>
			<td colspan='3'><input type='checkbox' name='maksutieto' {$maksutietochk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "<tr>
			<th>".t("N�yt� my�s tapahtumap�iv�")."</th>
			<td colspan='3'><input type='checkbox' name='tapahtumapaiva' {$tapahtumapaivachk}></td>
			<td class='back'>".t("(Toimii vain jos listaat matkalaskuittain)")."</td>
		</tr>";
	echo "</table>";

	echo "<br/>";

	echo "<table>";
	echo "<tr>
			<th>".t("Sy�t� alkup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input id='ppa' type='text' name='ppa' value='{$request_params['ppa']}' size='3'></td>
			<td><input id='kka' type='text' name='kka' value='{$request_params['kka']}' size='3'></td>
			<td><input id='vva' type='text' name='vva' value='{$request_params['vva']}' size='5'></td>
			</tr>
			<br/>
			<tr><th>".t("Sy�t� loppup�iv�m��r� (pp-kk-vvvv)")."</th>
			<td><input id='ppl' type='text' name='ppl' value='{$request_params['ppl']}' size='3'></td>
			<td><input id='kkl' type='text' name='kkl' value='{$request_params['kkl']}' size='3'></td>
			<td><input id='vvl' type='text' name='vvl' value='{$request_params['vvl']}' size='5'></td>
		</tr>
		<br/>";
	echo "</table>";
	echo "<br/>";

	echo nayta_kyselyt("myyntiseuranta");

	echo "<br/>";
	echo "<input type='submit' name='aja_raportti' value='".t("Aja raportti")."' onclick='return tarkista();' />";
	echo "</form>";

	echo "<br/><br/>";
}

function echo_tallennus_formi($xls_filename) {
	echo "<table>";
	echo "<tr><th>".t("Tallenna tulos").":</th>";
	echo "<form method='post' class='multisubmit'>";
	echo "<input type='hidden' name='tee' value='lataa_tiedosto'>";
	echo "<input type='hidden' name='lataa_tiedosto' value='1'>";
	echo "<input type='hidden' name='kaunisnimi' value='".t('Matkalaskuraportti').".xlsx'>";
	echo "<input type='hidden' name='tmpfilenimi' value='{$xls_filename}'>";
	echo "<td class='back'><input type='submit' value='".t("Tallenna")."'></td></tr></form>";
	echo "</table><br/>";
}

function generoi_excel_tiedosto($rivit, $request_params) {
	$xls = new pupeExcel();
	$rivi = 0;
	$sarake = 0;

	xls_headerit($xls, $rivit, $rivi, $sarake);

	xls_rivit($xls, $rivit, $rivi, $sarake);

	$xls_tiedosto = $xls->close();

	return $xls_tiedosto;
}

function xls_headerit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake) {
	foreach($rivit[0] as $header_text => $value) {
		kirjoita_solu($xls, $header_text, $rivi, $sarake);
	}
	$rivi++;
	$sarake = 0;
}

function xls_rivit(pupeExcel &$xls, &$rivit, &$rivi, &$sarake) {
	foreach($rivit as $matkalasku_rivi) {
		foreach($matkalasku_rivi as $solu) {
			kirjoita_solu($xls, $solu, $rivi, $sarake);
		}
		$rivi++;
		$sarake = 0;
	}
}

function kirjoita_solu(&$xls, $string, &$rivi, &$sarake) {
	if(is_numeric($string)) {
		$xls->writeNumber($rivi, $sarake, $string);
	}
	else if(valid_date($string) != 0 and valid_date($string) !== false) {
		$xls->writeDate($rivi, $sarake, $string);
	}
	else {
		$xls->write($rivi, $sarake, $string);
	}
	$sarake++;
}

function valid_date($date) {
	//preg_match() returns 1 if the pattern matches given subject, 0 if it does not, or FALSE if an error occurred. 
    return (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date));
}

?>
