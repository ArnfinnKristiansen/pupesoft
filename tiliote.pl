#!/usr/bin/perl

use FileHandle;

print "\npupesoft tiliote.pl v0.1a\n-------------------------\n\n";

$dirri  = "/tiliotteet/uudet/";				# dirri mist� etsit��n tiliotefaileja
$done   = "/tiliotteet/valmiit/";			# dirri minne k�sitellyt filet siirret��n
$php    = "/usr/bin/php";				# php executable
$script = "/home/pupesoft/public_html/tiliote.php";	# polku mist� tiliote.php l�ytyy

opendir($hakemisto, $dirri);

while ($file = readdir($hakemisto))
{
	$nimi = $dirri.$file;
	
	if (-f $nimi)
	{
		system("$php $script perl $nimi");
		system("mv -f $nimi $done");
	}

}
