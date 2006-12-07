#!/usr/bin/php
<?php

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	// pit�� siirty� www roottiin
	chdir("/var/www/html/pupesoft");

	// m��ritell��n polut
    $laskut    = "/home/verkkolaskut";
    $oklaskut  = "/home/verkkolaskut/ok";
    $errlaskut = "/home/verkkolaskut/error";

	require ("inc/connect.inc"); // otetaan tietokantayhteys
    require ("inc/verkkolasku-in.inc"); // t��ll� on itse koodi

	if ($handle = opendir($laskut)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($laskut."/".$file)) {

				$nimi = $laskut."/".$file;
				$laskuvirhe = verkkolasku_in($nimi);

			    if ($laskuvirhe == "") {
			    	system("mv -f $nimi $oklaskut/$file");
			    }
			    else {
			    	system("mv -f $nimi $errlaskut/$file");
				}

			}

		}

	}

?>