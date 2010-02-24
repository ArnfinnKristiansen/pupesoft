#!/usr/bin/php
<?php

	// t�ss� muuttujat, muuta n�m� sopiviksi muuhun ei tarvitse koskea
	$cp_dir  = "/backup/pupesoft-backup";
	$dbkanta = "pupesoft";
	$dbuser  = "pupesoft";
	$dbpass  = "pupesoft1";

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	echo date("d.m.Y @ G:i:s")." - Backup $dbkanta.\n";

	$filename = "/tmp/$dbkanta-backup-".date("Y-m-d").".sql";

	// tehd��n mysqldump
	system("mysqldump -u $dbuser --password=$dbpass $dbkanta > $filename");

	echo date("d.m.Y @ G:i:s")." - MySQL dump done.\n";

	// pakataan failit
	system("/usr/bin/pbzip2 $filename");

	echo date("d.m.Y @ G:i:s")." - Bzip2 done.\n";

	// siirret��n faili
	system("mv $filename.bz2 $cp_dir");

	// Siivotaan yli 30pv vanhat backupit pois
	chdir($cp_dir);
	system("find $cp_dir -mtime +30 -exec rm -f {} \;");

	echo date("d.m.Y @ G:i:s")." - All done.\n";

?>