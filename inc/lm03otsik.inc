<?php

//print "<pre>";

$luonti = getdate();

$ulos = "LM03";                              // 01 Aineistotunnus
$ulos .= "0";                              // 02 Tietuetunnus (Er�tietue)
$ulos .= "0";                              // 03 Tapahtumalaji
$ulos .= sprintf('%-14.14s', $yritystilino);              // 04 Maksajan tilinumero
$ulos .= sprintf('%09d', $yrityytunnus);                // 05 Maksajan tunnus
$ulos .= sprintf('%02d', substr($luonti['year'], 2, 2));        // 06 Tiedoston luontip�iv� vv
$ulos .= sprintf('%02d', $luonti['mon']);                // 06 Tiedoston luontip�iv� kk
$ulos .= sprintf('%02d', $luonti['mday']);                // 06 Tiedoston luontip�iv� pp
$ulos .= sprintf('%02d', $luonti['hours']);                // 07 Tiedoston luotiaika hh
$ulos .= sprintf('%02d', $luonti['minutes']);              // 07 Tiedoston luotiaika mm
$ulos .= sprintf('%2.2s', $rahalaitos);                  // 08 Vastaanottava rahalaitos
$ulos .= sprintf('%02d', substr($maksupvm, 2, 2));             // 09 Er�p�iv� vv
$ulos .= sprintf('%02d', substr($maksupvm, 5, 2));            // 09 Er�p�iv� kk
$ulos .= sprintf('%02d', substr($maksupvm, 8, 2));            // 09 Er�p�iv� pp
$ulos .= sprintf('%-35.35s', $yritysnimi);                // 10 Maksajan nimitarkenne
$ulos .= sprintf('%-35.35s', $luonti['hours'].$luonti['minutes']);    // 11 Er�n yksil�intitieto
$ulos .= sprintf('%17.17s', '');                    // 12 OVT-tunnus (ei k�yt�ss�)
$ulos .= "1";                              // 13 Rahayksik�n koodi (1 = EUR)
$ulos .= sprintf('%88.88s', '');                    // 14 Varalla
$ulos .= " ";                              // 15 Nordealla pakollinen Aineiston luonne (0 = maksut) / Sampolla "tyhj��"
$ulos .= sprintf('%10.10s', '');                    // 16 Nordealla Palvelutunnus / Sampolla "tyhj��"
$ulos .= sprintf('%66.66s', '');                    // 17 Varalla

//print strlen($ulos) . "/" . $ulos;
//print "</pre><br>";

fputs($toot, $ulos."\r\n");
