<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'edit' and isset($selected_row) and trim($selected_row) != '' and isset($alusta_tunnus) and trim($alusta_tunnus) != '' and isset($liitostunnus) and trim($liitostunnus) != '') {

		$data = array(
			'selected_row' => (int) $selected_row,
			'alusta_tunnus' => (int) $alusta_tunnus,
			'liitostunnus' => (int) $liitostunnus
		);

		$url = http_build_query($data);

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=muokkaa_suuntalavan_rivia.php?{$url}'>";
		exit;
	}

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
}

$sort_by_direction_tuoteno 		= (!isset($sort_by_direction_tuoteno) or $sort_by_direction_tuoteno == 'asc') ? 'desc' : 'asc';
$sort_by_direction_maara 		= (!isset($sort_by_direction_maara) or $sort_by_direction_maara == 'asc') ? 'desc' : 'asc';
$sort_by_direction_yksikko 		= (!isset($sort_by_direction_yksikko) or $sort_by_direction_yksikko == 'asc') ? 'desc' : 'asc';
$sort_by_direction_tuotepaikka 	= (!isset($sort_by_direction_tuotepaikka) or $sort_by_direction_tuotepaikka == 'asc') ? 'desc' : 'asc';

$tuotteet = array();

if (isset($alusta_tunnus)) {

	if (isset($sort_by) and $sort_by == "tuoteno") {
		$orderby = "tuoteno";
		$ascdesc = $sort_by_direction_tuoteno;
	}
	elseif (isset($sort_by) and $sort_by == "maara") {
		$orderby = "maara";
		$ascdesc = $sort_by_direction_maara;
	}
	elseif (isset($sort_by) and $sort_by == "yksikko") {
		$orderby = "yksikko";
		$ascdesc = $sort_by_direction_yksikko;
	}
	else {
		$orderby = "tuotepaikka";
		$ascdesc = $sort_by_direction_tuotepaikka;
	}

	$tuoteno = (isset($viivakoodi) and trim($viivakoodi))  ? trim($viivakoodi) : "";

	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, $orderby, $ascdesc, $tuoteno);

	$i = 0;

	while ($row = mysql_fetch_assoc($res)) {
		$tuotteet[$i]['tilriv_tunnus'] = $row['tunnus'];
		$tuotteet[$i]['tuoteno'] = $row['tuoteno'];
		$tuotteet[$i]['maara'] = $row['varattu'];
		$tuotteet[$i]['yks'] = $row['yksikko'];
		$tuotteet[$i]['osoite'] = "{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}";
		$tuotteet[$i]['tuotekerroin'] = $row['tuotekerroin'] != '' ? (float) $row['tuotekerroin'] : 1;

		$i++;
	}
}

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

  	<script type='text/javascript'>

		function setFocus() {
			if (document.getElementById('viivakoodi')) document.getElementById('viivakoodi').focus();
		}

		// window.document.hakuformi.viivakoodi.focus();

		// $.expr[':'].containsi = function(a,i,m) {
		//     return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
		// };

  // 		$(function() {

		// 	var highlight_row = function() {
		// 		$('td.tumma').removeClass('tumma');

		// 		var parent = $(this).parent();
		// 		parent.children().toggleClass('tumma');

		// 		$('#selected_row').val(parent.attr('id'));
		// 	}

		// 	var highlight_rows = function() {
		// 		$(this).parent().children('td.selectable').addClass('tumma').show();
		// 	}

		// 	$('td.selectable').on('click', highlight_row);

		// 	$('td.selectable').bind('rows', highlight_rows);

		// 	$('#viivakoodi').on('keyup', function(e) {
		// 		if ((e.keyCode > 47 && e.keyCode < 58) ||�(e.keyCode > 64 && e.keyCode < 91) ||�(e.keyCode > 96 && e.keyCode < 123) || e.keyCode == 8) {

		// 			var viivakoodi = $(this).val();

		// 			if (viivakoodi == '') {
		// 				$('td.selectable').removeClass('tumma').show();
		// 				$('#selected_row').val('');
		// 			}
		// 			else {
		// 				$('td.tumma').removeClass('tumma');

		// 				$('td.selectable').hide();

		// 				$('td.selectable:containsi(\"'+viivakoodi+'\")').trigger('rows');

		// 				if ($('td.selectable:containsi(\"'+viivakoodi+'\")').parent().length > 1) {
		// 					$('#selected_row').val('');
		// 				}
		// 			}
		// 		}
		// 	});
  // 		});
	</script>

	<table border='0' onload='setFocus();'>
		<tr>
			<td colspan='4'><h1>",t("Suuntalavan tuotteet", $browkieli),"</h1>
				<form name='viivakoodiformi' method='post' action=''>
					<table>
						<tr>
							<td colspan='5'>",t("Viivakoodi", $browkieli),":&nbsp;<input type='text' id='viivakoodi' name='viivakoodi' value='' />&nbsp;
								<button name='submit' value='submit' onclick='submit();'>",t("Etsi", $browkieli),"</button>
							</td>
						</tr>
						<tr>
							<td colspan='5'>&nbsp;</td>
						</tr>
					</table>
				</form>
				<form name='hakuformi' method='post' action=''>
				<table>
					<tr>
						<th>&nbsp;</th>
						<th nowrap>
							<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=tuoteno&sort_by_direction_tuoteno={$sort_by_direction_tuoteno}'>",t("Tuotenro", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_tuoteno == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "							</th>
						<th nowrap>
							<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=maara&sort_by_direction_maara={$sort_by_direction_maara}'>",t("M��r�", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_maara == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "							</th>
						<th nowrap>
							<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=yksikko&sort_by_direction_yksikko={$sort_by_direction_yksikko}'>",t("Yks", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_yksikko == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "							</th>
						<th nowrap>
							<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=tuotepaikka&sort_by_direction_tuotepaikka={$sort_by_direction_tuotepaikka}'>",t("Osoite", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_tuotepaikka == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "							</th>
					</tr>";

					$chk = count($tuotteet) == 1 ? " checked" : "";

					foreach ($tuotteet as $tuote) {
						echo "<tr id='{$tuote['tilriv_tunnus']}'>";
						echo "<td><input type='radio' name='selected_row' value='{$tuote['tilriv_tunnus']}'{$chk} /></td>";
						echo "<td class='selectable' nowrap>{$tuote['tuoteno']}</td>";
						echo "<td class='selectable' nowrap>{$tuote['maara']}";

						if ($tuote['tuotekerroin'] != 1) echo "&nbsp;(",$tuote['maara'] * $tuote['tuotekerroin'],")";

						$onko_suoratoimitus_res = onko_suoratoimitus($tuote['tilriv_tunnus']);

						if ($row = mysql_fetch_assoc($onko_suoratoimitus_res)) {
							if ($row["suoraan_laskutukseen"] == "") echo "&nbsp;",t("JT");
						}

						echo "</td>";
						echo "<td class='selectable' nowrap>{$tuote['yks']}</td>";
						echo "<td class='selectable' nowrap>{$tuote['osoite']}</td>";
						echo "</tr>";
					}

echo "						<tr>
						<td colspan='5'>&nbsp;</td>
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("Valitse", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='edit' onclick='submit();'>",t("Muokkaa", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='varalle' onclick='submit();'>",t("Varalle", $browkieli),"</button>
						</td>
						<td>
							&nbsp;
						</td>
					</tr>
				</table>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				</form>
			</td>
		</tr>
		<tr>
			<td colspan='4' class='back'>{$error['alusta']}</td>
		</tr>
	</table>";

require('inc/footer.inc');