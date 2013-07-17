<?php

require ("inc/parametrit.inc");
require ("validation/Validation.php");
enable_ajax();
?>
<style>
    .tr_border_top {
        border-top: 1px solid;
    }
    .text_align_right {
        text-align: right;
    }
</style>
<script>
    function tarkista() {
        ok = confirm($('#hyvaksy_hylkaa_message').val());
						
        if(ok) {
            return true;
        }
        else {
            return false;
        }
    }
</script>
<?php

if (isset($liite_popup_toiminto) and $liite_popup_toiminto == "AK") {
    liite_popup("AK", $tuotetunnus, $width, $height);
}
else {
    liite_popup("JS");
}

if (function_exists("js_popup")) {
    echo js_popup(-100);
}

if ($toim == 'EXTTARJOUS') {
    echo "<font class='head'>" . t("Ext-tarjoukset") . "</font><hr>";
}
else {
    echo "<font class='head'>" . t("Ext-ennakot") . "</font><hr>";
}

$request = array(
    "toim" => $toim,
    "kayttajaan_liitetty_asiakas" => $kayttajaan_liitetty_asiakas,
    "asiakkaan_tarjoukset" => $asiakkaan_tarjoukset,
    "action" => $action,
    "valittu_tarjous_tunnus" => $valittu_tarjous_tunnus,
    "hyvaksy" => $hyvaksy,
    "hylkaa" => $hylkaa,
);

$request['kayttajaan_liitetty_asiakas'] = hae_extranet_kayttajaan_liitetty_asiakas();

if ($tee == "LISAARIVI") {

    $query = "	SELECT *
						from tuote
						where tuoteno='$tuoteno' and yhtio='$kukarow[yhtio]'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
        //Tuote l�ytyi
        $trow = mysql_fetch_assoc($result);

 
        $kukarow["kesken"] = $otunnus;
        
        $laskurow = hae_lasku($otunnus);
        // Nollataan hinta kun kyseess� on asiakkaan ext-ennakkotilaukseen lis��m� rivi
        if ($toim == 'EXTENNAKKO') {
            $query = "  SELECT selite AS ennakko_pros_a
                        FROM tuotteen_avainsanat
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tuoteno = '{$tuoteno}'
                        AND laji = 'parametri_ennakkoale_a'
                        AND selite != ''";
            $result = pupe_query($query);
            $tuotteen_hinta = mysql_fetch_assoc($result);
            $hinta = $trow['myyntihinta'] * (1 - ($tuotteen_hinta['ennakko_pros_a'] / 100));
            $laskurow["tila"] = 'N';
        }
        else {
            $laskurow["tila"] = 'T';
        }
        $perhekielto = '';
        $perheid = 0;
        $trow = hae_tuote($tuoteno);
        $parametrit = array(
            'trow' => $trow,
            'laskurow' => $laskurow,
            'kpl' => ($kpl),
            'netto' => $netto,
            'hinta' => $hinta,
            'perhekielto' => $perhekielto,
            'perheid' => $perheid,
        );
        lisaa_rivi($parametrit);

        $lisatyt_rivit = array_merge($lisatyt_rivit1, $lisatyt_rivit2);

        if ($lisatyt_rivit[0] > 0) {
            $valmistettavat .= "," . $lisatyt_rivit[0];

            $query = "	UPDATE tilausrivi
								SET toimitettu	= '$kukarow[kuka]',
								toimitettuaika	= now(),
								keratty			= '$kukarow[kuka]',
								kerattyaika		= now()
								WHERE yhtio	= '$kukarow[yhtio]'
								and tunnus	= '$lisatyt_rivit[0]'";
            $result = pupe_query($query);
        }
    }
    else {
        echo t("Tuotetta ei l�ydy") . "!<br>";
    }

    $tee = "VALMISTA";
}

if ($request['action'] == 'nayta_tarjous') {
    nayta_tarjous($request['valittu_tarjous_tunnus'], $request['toim']);
}
elseif ($request['action'] == 'hyvaksy_tai_hylkaa') {

    if (isset($request['hyvaksy'])) {
        if ($toim == 'EXTTARJOUS') {
            $onnistuiko_toiminto = hyvaksy_tarjous($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot);
        }
        else {
            $onnistuiko_toiminto = hyvaksy_ennakko($request['valittu_tarjous_tunnus'], $syotetyt_lisatiedot, $kappalemaarat);
        }
    }
    else {
        $onnistuiko_toiminto = hylkaa_tarjous($request['valittu_tarjous_tunnus']);
    }
    if (!$onnistuiko_toiminto) {
        echo "<font class='error'>" . t("Toiminto ep�onnistui") . "</font>";
        echo "<br>";
        echo "<br>";
    }
    else {
        echo "<script>
					setTimeout(\"parent.location.href='$palvelin2'\",0);
					</script>";
        exit;
    }

    //$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus'], $request['toim']);
    $request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset(57620, $request['toim']);
    $header_values = array(
        'nimi' => t('Nimi'),
        'hinta' => t('Tarjouksen hinta'),
        'toimaika' => t('Viimeinen voimasssaolop�iv�m��r�'),
    );

    echo_rows_in_table($request['asiakkaan_tarjoukset'], $header_values, array(), 'tee_tarjouksen_nimesta_linkki');
}
else {
    //$request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset($request['kayttajaan_liitetty_asiakas']['tunnus'], $request['toim']);
    $request['asiakkaan_tarjoukset'] = hae_extranet_tarjoukset(57620, $request['toim']);
    $header_values = array(
        'nimi' => t('Nimi'),
        'hinta' => t('Tarjouksen hinta'),
        'toimaika' => t('Viimeinen voimasssaolop�iv�m��r�'),
    );

    echo_rows_in_table($request['asiakkaan_tarjoukset'], $header_values, array(), 'tee_tarjouksen_nimesta_linkki');
}

function hyvaksy_ennakko($valittu_tarjous_tunnus, $syotetyt_lisatiedot, $kappalemaarat) {
    global $kukarow, $yhtiorow;
    //SELECT '' as nro, tunnus, perheid as perheid_tunnus, tuoteno,nimitys, tilkpl as kpl,hinta as rivihinta,alv
    foreach ($kappalemaarat as &$kappalemaara_temp) {
        $kappalemaara_temp = round(str_replace(",", ".", pupesoft_cleanstring($kappalemaara_temp)), 2);
    }


    $validations = array(
        'syotetyt_lisatiedot' => 'kirjain_numero',
    );

    $validator = new FormValidator($validations);

    if ($validator->validate(array('syotetyt_lisatiedot' => $syotetyt_lisatiedot))) {

        foreach ($kappalemaarat as $kappalemaara) {
            if (!FormValidator::validateItem($kappalemaara, "2digitopt")) {
                return false;
            }
        }
        // Haetaan kannasta tilausrivit
        $muokkaamaton_ennakko = hae_tarjous($valittu_tarjous_tunnus);

        foreach ($kappalemaarat as $key => $value) {
            // Etsit��n tilausrivitunnuksen perusteella tuotteen kannassa oleva kappalem��r�
            $loytynyt_tilausrivi = search_array_key_for_value_recursive($muokkaamaton_ennakko['tilausrivit'], 'tunnus', $key);
            // Tarkistetaan l�ytyik� tilausrivi
            if (!empty($loytynyt_tilausrivi[0])) {
                // Muutetaan kappalem��r� oikeaan muotoon
                $kplmaara = round(str_replace(",", ".", pupesoft_cleanstring($loytynyt_tilausrivi[0]['kpl'])), 2);
                // Tarkistetaan onko kappalem��r� s�ilynyt muuttamattomana, jos ei muutoksia niin toimenpiteit� riville ei vaadita
                if ($kplmaara != $value) {
                    // Onko tuote tuoteperheen is�tuote tai normaali tilausrivi JA uusi sy�tetty kappalem��r� 0
                    if (($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus'] or $loytynyt_tilausrivi[0]['perheid_tunnus'] == 0) and $value == 0.00) {
                        // Tuoteperheen kappalem��r�n nollaus tilauksesta
                        if ($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus']) {
                            $andy = "   AND perheid = '{$loytynyt_tilausrivi[0]['tunnus']}'";
                        }
                        // Normaalituotteen kappalem��r�n nollaus tilauksesta  
                        else {
                            $andy = "   AND tunnus = '{$loytynyt_tilausrivi[0]['tunnus']}'";
                        }

                        $query = "  UPDATE tilausrivi
                                        SET tilkpl = 0,
                                        kpl = 0,
                                        jt = 0,
                                        varattu = 0
                                        WHERE yhtio = '{$kukarow['yhtio']}'
                                        AND otunnus = '{$valittu_tarjous_tunnus}'
                                        {$andy}
                                        ";
                        pupe_query($query);
                    }
                    // Kun kappalem��r�� muutetaan muuksi kuin nollaksi, poistetaan tilausrivi tilauksesta ja lis�t��n uudestaan uudella m��r�ll�
                    else {
                        $kukarow['kesken'] = $valittu_tarjous_tunnus;
                        // Tuoteperheen tapauksessa rivin poistoa varten
                        if ($loytynyt_tilausrivi[0]['tunnus'] == $loytynyt_tilausrivi[0]['perheid_tunnus']) {
                            $andy = "   AND perheid = '{$loytynyt_tilausrivi[0]['tunnus']}'";
                        }
                        // Normaalituotteen tapauksessa rivin poistoa varten
                        else {
                            $andy = "   AND tunnus = '{$loytynyt_tilausrivi[0]['tunnus']}'";
                        }
                        // Rivin poisto
                        $query = "  DELETE
                                    FROM tilausrivi
                                    WHERE yhtio = '{$kukarow['yhtio']}'
                                    AND otunnus = '{$valittu_tarjous_tunnus}'
                                    {$andy}
                                    ";

                        pupe_query($query);


                        // Rivin uudelleenlis�ys
                        $trow = hae_tuote($loytynyt_tilausrivi[0]['tuoteno']);
                        $laskurow = $muokkaamaton_ennakko;
                        $parametrit = array(
                            'trow' => $trow,
                            'laskurow' => $laskurow,
                            'kpl' => $value,
                            'netto' => "N",
                            'rivitunnus' => $loytynyt_tilausrivi[0]['tunnus'],
                            'tuoteno' => $loytynyt_tilausrivi[0]['tuoteno'],
                            'hinta' => 0,
                        );
                        lisaa_rivi($parametrit);
                    }
                }
            }
        }
        // P�ivitet��n k�ytt�j�n lis��m�t kommentit ja vaihdetaan tila/alatila Ennakko/Lep��m�ss�
        // $query = "UPDATE lasku set sisviesti1='{$syotetyt_lisatiedot}', tila='E', alatila='A' where yhtio='$kukarow[yhtio]' and tunnus='{$valittu_tarjous_tunnus}'";
        // $result = pupe_query($query);
        return true;
    }
    else {
        return false;
    }
}

function hyvaksy_tarjous($valittu_tarjous_tunnus, $syotetyt_lisatiedot) {
    global $kukarow, $yhtiorow;
    $validations = array(
        'syotetyt_lisatiedot' => 'kirjain_numero',
    );

    $validator = new FormValidator($validations);

    if ($validator->validate(array('syotetyt_lisatiedot' => $syotetyt_lisatiedot))) {

        $kukarow['kesken'] = $valittu_tarjous_tunnus;
        $laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus);

        ///* Reload ja back-nappulatsekki *///
        if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
            echo "<font class='error'> " . t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�") . "! </font>";
            exit;
        }

        //Luodaan valituista riveist� suoraan normaali ostotilaus
        require("tilauskasittely/tilauksesta_ostotilaus.inc");

        $tilauksesta_ostotilaus = tilauksesta_ostotilaus($kukarow["kesken"], 'T');
        $tilauksesta_ostotilaus .= tilauksesta_ostotilaus($kukarow["kesken"], 'U');

        if ($tilauksesta_ostotilaus != '')
            echo "$tilauksesta_ostotilaus<br><br>";

        // Kopsataan valitut rivit uudelle myyntitilaukselle
        require("tilauskasittely/tilauksesta_myyntitilaus.inc");

        $tilauksesta_myyntitilaus = tilauksesta_myyntitilaus($kukarow["kesken"], '', '', '', '', '', $perusta_tilaustyyppi);
        if ($tilauksesta_myyntitilaus != '')
            echo "$tilauksesta_myyntitilaus<br><br>";

        $query = "UPDATE lasku SET alatila='B' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
        $result = pupe_query($query);

        //	P�ivitet��n my�s muut tunnusnipun j�senet sympatian vuoksi hyv�ksytyiksi
        $query = "SELECT tunnusnippu from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu]";
        $result = pupe_query($query);

        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);

            $query = "UPDATE lasku SET alatila='T' where yhtio='$kukarow[yhtio]' and tunnusnippu = $row[tunnusnippu] and tunnus!='$kukarow[kesken]'";
            $result = pupe_query($query);
        }

        $query = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
        $result = pupe_query($query);

        $query = "UPDATE lasku set sisviesti1='{$syotetyt_lisatiedot}' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
        $result = pupe_query($query);

        $aika = date("d.m.y @ G:i:s", time());
        echo "<font class='message'>$otsikko $kukarow[kesken] " . t("valmis") . "!</font><br><br>";

        $tee = '';
        $tilausnumero = '';
        $laskurow = '';
        $kukarow['kesken'] = '';
        return true;
    }
    return false;
}

function hylkaa_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $kukarow['kesken'] = $valittu_tarjous_tunnus;
    $laskurow = hae_extranet_tarjous($valittu_tarjous_tunnus);

    ///* Reload ja back-nappulatsekki *///
    if ($kukarow["kesken"] == '' or $kukarow["kesken"] == '0') {
        echo "<font class='error'> " . t("Taisit painaa takaisin tai p�ivit� nappia. N�in ei saa tehd�") . "! </font>";
        exit;
    }

    $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus='$kukarow[kesken]'";
    $result = pupe_query($query);

    //Nollataan sarjanumerolinkit
    vapauta_sarjanumerot($toim, $kukarow["kesken"]);

    //	P�ivitet��n my�s muut tunnusnipun j�senet sympatian vuoksi hyl�tyiksi *** t�m�n voisi varmaan tehd� my�s kaikki kerralla? ***
    $query = "SELECT tunnus from lasku where yhtio = '$kukarow[yhtio]' and tunnusnippu > 0 and tunnusnippu = $laskurow[tunnusnippu] and tunnus != '$kukarow[kesken]'";
    $abures = pupe_query($query);

    if (mysql_num_rows($abures) > 0) {
        while ($row = mysql_fetch_assoc($abures)) {
            $query = "UPDATE lasku SET alatila='X' where yhtio='$kukarow[yhtio]' and tunnus=$row[tunnus]";
            $result = pupe_query($query);

            $query = "UPDATE tilausrivi SET tyyppi='D' where yhtio='$kukarow[yhtio]' and otunnus=$row[tunnus]";
            $result = pupe_query($query);

            //Nollataan sarjanumerolinkit
            vapauta_sarjanumerot($toim, $row["tunnus"]);
        }
    }

    $query = "UPDATE kuka set kesken='0' where yhtio='$kukarow[yhtio]' and kuka='$kukarow[kuka]'";
    $result = pupe_query($query);

    $aika = date("d.m.y @ G:i:s", time());
    echo "<font class='message'>$otsikko $kukarow[kesken] " . t("valmis") . "!</font><br><br>";

    $tee = '';
    $tilausnumero = '';
    $laskurow = '';
    $kukarow['kesken'] = '';
    return true;
}

function hae_extranet_tarjoukset($asiakasid, $toim) {
    global $kukarow, $yhtiorow;

    if ($toim == 'EXTTARJOUS') {
        $where = "  AND lasku.clearing = 'EXTTARJOUS' AND lasku.tila = 'T'";
    }
    else {
        $where = "  AND lasku.clearing = 'EXTENNAKKO' AND lasku.tila = 'N'";
    }
    
    $query = "  SELECT  concat( concat_ws('!!!', lasku.tunnus, lasku.nimi),'!!!{$toim}') AS nimi,
                lasku.hinta,
                lasku.olmapvm,
                lasku.tunnus as tunnus
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND lasku.liitostunnus = '{$asiakasid}'
                AND lasku.alatila = ''
                {$where}";
    $result = pupe_query($query);

    $haetut_tarjoukset = array();

    while ($haettu_tarjous = mysql_fetch_assoc($result)) {
        $tilrivit = hae_tarjouksen_tilausrivit($haettu_tarjous['tunnus']);
        $haettu_tarjous['hinta'] = 0;
        foreach($tilrivit as $tilrivi) {
            $haettu_tarjous['hinta'] += (float)$tilrivi['rivihinta']; 
        }
        $haetut_tarjoukset[] = $haettu_tarjous;
    }
    return $haetut_tarjoukset;
}

function hae_extranet_tarjous($tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT *
                FROM lasku
                WHERE yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$tunnus}'";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
}

function hae_extranet_kayttajaan_liitetty_asiakas() {
    global $kukarow, $yhtiorow;

    $query = "  SELECT asiakas.*
                FROM asiakas
                JOIN kuka ON ( kuka.yhtio = asiakas.yhtio AND kuka.oletus_asiakas = asiakas.tunnus AND kuka.extranet = 'X' AND kuka.kuka = '{$kukarow['kuka']}' )
                WHERE asiakas.yhtio = '{$kukarow['yhtio']}'";
    $result = pupe_query($query);

    return mysql_fetch_assoc($result);
}

function tee_tarjouksen_nimesta_linkki($header, $solu, $force_to_string) {
    global $kukarow, $yhtiorow;

    if (!stristr($header, 'tunnus')) {
        if ($header == 'nimi') {
            $tunnus_nimi_array = explode('!!!', $solu);
            echo "<td>";
            echo "<a href='$_SERVER[PHP_SELF]?action=nayta_tarjous&valittu_tarjous_tunnus={$tunnus_nimi_array[0]}&toim={$tunnus_nimi_array[2]}'>{$tunnus_nimi_array[1]}</a>";
            echo "</td>";
        }
        else if ($header == 'hinta') {
            echo "<td align='right'>";
            echo $solu;
            echo "</td>";
        }
        else {
            echo "<td>";
            echo $solu;
            echo "</td>";
        }
    }
}

function nayta_tarjous($valittu_tarjous_tunnus, $toim) {
    global $kukarow, $yhtiorow;

    $tarjous = hae_tarjous($valittu_tarjous_tunnus);

    echo_tarjouksen_otsikko($tarjous, $toim);
    echo "	<form method='post' autocomplete='off'>";
    echo "	<input type='hidden' name='tee' value='LISAARIVI'>
					<input type='hidden' name='toim'  value='$toim'>
					<input type='hidden' name='otunnus'  value='$valittu_tarjous_tunnus'>";
    echo "<font class='message'>" . t("Tuotteiden lis�ys") . "</font>";

    require('tilauskasittely/syotarivi.inc');
    echo "<br>";

    echo_tarjouksen_tilausrivit($tarjous, $toim);
}

function hae_tarjous($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT *
                FROM lasku
                WHERE lasku.yhtio = '{$kukarow['yhtio']}'
                AND tunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);

    $tarjous = mysql_fetch_assoc($result);
    $tarjous['tilausrivit'] = hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus);

    return $tarjous;
}

function hae_tarjouksen_tilausrivit($valittu_tarjous_tunnus) {
    global $kukarow, $yhtiorow;

    $query = "  SELECT '' as nro, '' as kuva, tilausrivi.tunnus, tilausrivi.perheid as perheid_tunnus, tilausrivi.tuoteno, tilausrivi.nimitys, 
                tilausrivi.tilkpl as kpl, IF(tilausrivi.alv != 0, ( (1 + ( tilausrivi.alv / 100)     ) * (tilausrivi.tilkpl * tilausrivi.hinta ) ), tilausrivi.tilkpl * tilausrivi.hinta) AS rivihinta, tilausrivi.alv, tuote.tunnus as tuote_tunnus
                FROM tilausrivi
                JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
                WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                AND tilausrivi.otunnus = '{$valittu_tarjous_tunnus}'";
    $result = pupe_query($query);

    $tilausrivit = array();

    while ($tilausrivi = mysql_fetch_assoc($result)) {
        $tilausrivit[] = $tilausrivi;
    }

    return $tilausrivit;
}

function echo_tarjouksen_otsikko($tarjous, $toim) {
    global $kukarow, $yhtiorow;

    echo "<input type='hidden' id='hyvaksy_hylkaa_message' value='" . t("Oletko varma, ett� haluat suorittaa valitun toimenpiteen") . "'/>";
    echo "<a href=$_SERVER[PHP_SELF]?toim={$toim}>" . t("Palaa takaisin") . "</a>";
    echo "<br>";
    echo "<br>";
    // TODO eri messu EXTENNAKKO / EXTTARJOUS?
    echo "<font class='message'>" . t("Tarjouksen tiedot") . "</font>";
    echo "<table>";

    echo "<tr>";
    echo "<th>" . t("Nimi") . "</th>";
    echo "<th>" . t("Viimeinen voimassaolopvm") . "</th>";
    echo "<tr>";
    echo "<td>";
    echo "{$tarjous['nimi']}";
    echo "</td>";
    echo "<td>";
    echo "{$tarjous['olmapvm']}";
    echo "</td>";

    echo "</table>";
    echo "<br>";
}

function echo_tarjouksen_tilausrivit($tarjous, $toim) {
    global $kukarow, $yhtiorow;

    echo "<font class='message'>" . t("Tilausrivit") . "</font>";

    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='action' value='hyvaksy_tai_hylkaa' />";
    echo "<input type='hidden' name='toim' value='{$toim}' />";
    echo "<input type='hidden' name='valittu_tarjous_tunnus' value='{$tarjous['tunnus']}'/ >";
    $header_values = array(
        'tuotenro' => t('Tuotenro'),
        'nimi' => t('Nimi'),
        'kpl' => t('Kpl'),
        'rivihinta' => t('Rivihinta'),
        'alv' => t('Alv'),
    );
    echo_tarjous_rows_in_table($tarjous['tilausrivit'], $header_values, array(), $toim);
    echo "<br>";
    echo "<textarea rows='4' cols='20' maxlength='1000' name='syotetyt_lisatiedot' placeholder='" . t("Lis�tietoja") . "'>";
    echo "</textarea>";
    echo "<br>";
    echo "<br>";
    echo "<input type='submit' name='hyvaksy' value='" . t("Hyv�ksy") . "' onclick='return tarkista();'/>";
    if ($toim == "EXTTARJOUS") {
        echo "<input type='submit' name='hylkaa' value='" . t("Hylk��") . "' onclick='return tarkista();'/>";
    }
    echo "</form>";
}

function echo_tarjous_rows_in_table(&$rivit, $header_values = array(), $force_to_string = array(), $toim) {
    global $kukarow, $yhtiorow;
    echo "<table>";
    if (count($rivit) > 0) {
        _echo_tarjous_table_headers($rivit[0], $header_values);
        _echo_tarjous_table_rows($rivit, $force_to_string, $toim);
    }
    else {
        echo "<tr><td>" . t("Ei tulostettavia rivej�") . "</td></tr>";
    }
    echo "</table>";
}

function _echo_tarjous_table_headers($rivi, $header_values) {
    global $kukarow, $yhtiorow;
    echo "<tr>";
    foreach ($rivi as $header_text => $value) {
        if (!stristr($header_text, 'tunnus')) {
            if (array_key_exists($header_text, $header_values)) {
                echo "<th>{$header_values[$header_text]}</th>";
            }
            else {
                //fail safe
                echo "<th>{$header_text}</th>";
            }
        }
    }
    echo "</tr>";
}

function _echo_tarjous_table_rows(&$rivit, $force_to_string = array(), $toim) {
    global $kukarow, $yhtiorow;
    $index = 0;
    foreach ($rivit as $rivi) {
        $class = "";
        if ($rivi['tunnus'] == $rivi['perheid_tunnus'] or $rivi['perheid_tunnus'] == 0) {
            $class = "tr_border_top";
            $index++;
        }
        echo "<tr>";
        foreach ($rivi as $header => &$solu) {
            _echo_tarjous_table_row_td($header, $solu, $force_to_string, $class, $index, $toim, $rivi);
        }
        echo "</tr>";
    }
}

function _echo_tarjous_table_row_td($header, $solu, $force_to_string, $class, $index, $toim, $rivi) {
    global $kukarow, $yhtiorow;
    if (!stristr($header, 'tunnus')) {

        if ($header == 'nro') {
            if ($class != '') {
                echo "<td class='{$class}'>{$index}</td>";
            }
            else {
                echo "<td class='{$class}'></td>";
            }
        }
        elseif ($header == 'kpl' and $toim == "EXTENNAKKO") {
            if ($class == 'tr_border_top') {
                if (is_numeric($solu) and !ctype_digit($solu) and !in_array($header, $force_to_string)) {
                    $class .= " text_align_right";
                }
                echo "<td class='{$class}'><input type='text' name='kappalemaarat[{$rivi['tunnus']}]' value='{$solu}' /></td>";
            }
            else {
                if (is_numeric($solu) and !ctype_digit($solu) and !in_array($header, $force_to_string)) {
                    $class .= " text_align_right";
                }
                echo "<td class='{$class}'>{$solu}</td>";
            }
        }
        elseif ($header == 'kuva') {
            // Onko liitetiedostoja

            $liitteet = liite_popup("TH", $rivi['tuote_tunnus']);

            $vari = 'spec';
            if ($liitteet != "") {
                echo "<td class='$vari' style='vertical-align: top;'>$liitteet</td>";
            }
            else {
                echo "<td class='$vari'></td>";
            }
        }
        else {
            if (is_numeric($solu) and !ctype_digit($solu) and !in_array($header, $force_to_string)) {
                $class .= " text_align_right";
                $solu = number_format($solu, 2);
            }
            echo "<td class='{$class}'>{$solu}</td>";
        }
    }
}

require ("inc/footer.inc");