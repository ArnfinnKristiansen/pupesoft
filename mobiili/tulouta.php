<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) == 'cancel') {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=paavalikko.php'>";
	exit;
}

$error = array(
	'tulotyyppi' => '',
);

if (isset($submit) and trim($submit) == 'submit' and isset($tulotyyppi) and trim($tulotyyppi) != '') {

	if ($tulotyyppi == 'suuntalava') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
}

if (isset($submit) and trim($submit) == 'submit') {
	if ($tulotyyppi == '') $error['tulotyyppi'] = "<font class='error'>".t("Valitse tulotyyppi")."!</font>";
}

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<body>
		<form method='post' action=''>
			<table border='0'>
				<tr>
					<td><h1>",t("Tulouta", $browkieli),"</h1>
						<table>
							<tr>
								<td>",t("Tulotyyppi", $browkieli),"</td>
							</tr>
							<tr>
								<td>
									<select name='tulotyyppi' size='4'>
										<option value='suuntalava'>ASN / Suuntalava</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<button value='wat'>Suuntalavat</button>
								</td>
							</tr>
							<tr>
								<td>
									<button name='submit' value='submit' onclick='submit();'>OK</button>
									<button name='submit' value='cancel' onclick='submit();'>Takaisin</button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>{$error['tulotyyppi']}</td>
				</tr>
			</table>
		</form>
	</body>";