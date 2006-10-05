#!/usr/bin/php
<?php

	// t�ss� scp komennon host ja host dir muuttujat, muuta n�m� sopiviksi muuhun ei tarvitse koskea
	$scp_host = "root@d90.arwidson.fi";
	$scp_dir  = "/backup/mysql-backup";

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	require ("inc/connect.inc");

	echo date("d.m.Y @ G:i:s")." - Backup $dbkanta.\n";

	$filename = "$dbkanta-backup-".date("Y-m-d").".zip";

	// backupataan kaikki failit
	passthru("/usr/bin/mysqlhotcopy -q -u $dbuser --password=$dbpass $dbkanta /tmp");

	echo date("d.m.Y @ G:i:s")." - Copy done.\n";

	// siirryt��n temppidirriin
	chdir("/tmp/$dbkanta");

	// pakataan failit
	system("/usr/bin/zip -9q $filename *");

	echo date("d.m.Y @ G:i:s")." - Zip done.\n";

	// kopsataan faili
	$scpma = "scp $filename $scp_host:$scp_dir";
	system($scpma);

	echo date("d.m.Y @ G:i:s")." - Transfer done.\n";

	// dellataan pois tempit
	system("rm -rf /tmp/$dbkanta");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>