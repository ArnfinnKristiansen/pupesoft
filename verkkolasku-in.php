#!/usr/bin/php
<?php

	if ($argc == 0) die ("T�t� scripti� voi ajaa vain komentorivilt�!");

	// pit�� siirty� www roottiin
	chdir("/Users/juppe/Sites/Devlab/pupesoft");

	// m��ritell��n polut
    $laskut     = "/Users/juppe/Desktop/verkkolaskut";
    $oklaskut   = "/Users/juppe/Desktop/verkkolaskut/ok";
    $origlaskut = "/Users/juppe/Desktop/verkkolaskut/orig";
    $errlaskut  = "/Users/juppe/Desktop/verkkolaskut/error";

	require ("inc/connect.inc"); // otetaan tietokantayhteys
    require ("inc/verkkolasku-in.inc"); // t��ll� on itse koodi
    require ("inc/verkkolasku-in-erittele-laskut.inc"); // t��ll� pilkotaan Finvoiceaineiston laskut omiksi tiedostoikseen

	// K�sitell��n ensin kaikki Finvoicet
	if ($handle = opendir($laskut)) {

		while (($file = readdir($handle)) !== FALSE) {

			if (is_file($laskut."/".$file)) {

				$nimi = $laskut."/".$file;
				$luotiinlaskuja = erittele_laskut($nimi);

				// Jos tiedostosta luotiin laskuja siirret��n se tielt� pois
				if ($luotiinlaskuja > 0) {
					system("mv ".$laskut."/".$file." ".$origlaskut."/".$file);
				}
			}
		}
	}
	
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
