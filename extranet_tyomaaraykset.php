<?php

if (strpos($_SERVER['SCRIPT_NAME'], "extranet_tyomaaraykset.php") !== FALSE) {
  require "inc/parametrit.inc";
}

$tyom_parametrit = array(
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'asiakkaan_tilausnumero' => isset($_REQUEST['asiakkaan_tilausnumero']) ? $_REQUEST['asiakkaan_tilausnumero'] : '', 
  'valmistaja' => isset($_REQUEST['valmistaja']) ? $_REQUEST['valmistaja'] : '', 
  'malli' => isset($_REQUEST['malli']) ? $_REQUEST['malli'] : '',
  'valmnro' => isset($_REQUEST['valmnro']) ? $_REQUEST['valmnro'] : '',
  'tuotenro' => isset($_REQUEST['tuotenro']) ? $_REQUEST['tuotenro'] : '',
  'sla' => isset($_REQUEST['sla']) ? $_REQUEST['sla'] : '',
  'komm1' => isset($_REQUEST['komm1']) ? $_REQUEST['komm1'] : '',
);

$request = array(
  'tyom_toiminto' => isset($_REQUEST['tyom_toiminto']) ? $_REQUEST['tyom_toiminto'] : '',
  'laite_tunnus' => isset($_REQUEST['laite_tunnus']) ? $_REQUEST['laite_tunnus'] : '',
  'tyom_tunnus' => isset($_REQUEST['tyom_tunnus']) ? $_REQUEST['tyom_tunnus'] : '',
  'nayta_poistetut' => isset($_REQUEST['nayta_poistetut']) ? $_REQUEST['nayta_poistetut'] : '',
  'tyom_parametrit' => $tyom_parametrit
);


#if ($kukarow['extranet'] == '') die(t("K�ytt�j�n parametrit - T�m� ominaisuus toimii vain extranetiss�"));

if (isset($avaa_tyomaarays_nappi)) {
  // Tallennetaan ty�m��r�ys j�rjestelm��n
  tallenna_tyomaarays($request);
  $tyom_toiminto = '';
  unset($request['tyom_parametrit']);
}

#if ($kukarow['multi_asiakkuus'] != '') {
  require "asiakasvalinta.inc";
#}

if ($request['tyom_toiminto'] == '') {
  piirra_kayttajan_tyomaaraykset();
}
elseif ($request['tyom_toiminto'] == 'UUSI') {
  uusi_tyomaarays_formi($laite_tunnus);
}
elseif ($request['tyom_toiminto'] == 'TALLENNA') {
  tallenna_tyomaarays();
}

function piirra_kayttajan_tyomaaraykset() {
  echo "<font class='head'>".t("Ty�m��r�ykset")."</font><hr>";
  piirra_nayta_aktiiviset_poistetut();
  $naytettavat_tyomaaraykset = hae_kayttajan_tyomaaraykset();
  if (count($naytettavat_tyomaaraykset) > 0) {
    echo "<form name ='tyomaaraysformi'>";
    echo "<table>";
    echo "<tr>";
    piirra_tyomaaraysheaderit();
    echo "</tr>";

    foreach ($naytettavat_tyomaaraykset as $tyomaarays) {    
      piirra_tyomaaraysrivi($tyomaarays);
    }

    echo "</table>";
    echo "</form>";
  }
  else {
    echo "<br><font class='error'>".t('Ty�m��r�yksi� ei l�ydy j�rjestelm�st�')."!</font><br/>";
  }

  piirra_luo_tyomaarays();
}

function hae_kayttajan_tyomaaraykset() {
  global $kukarow, $request;

  $tyomaaraykset = array();

  if ($kukarow['oletus_asiakas'] == '') {
    return $tyomaaraykset;
  }
  $alatila = " AND lasku.alatila != 'X' ";
  if (!empty($request['nayta_poistetut'])) {
    $alatila = " AND lasku.alatila = 'X' ";
  }

  $query = "SELECT
            lasku.tunnus,
            lasku.viesti,
            lasku.nimi,
            lasku.tila,
            lasku.alatila,
            lasku.tilaustyyppi,
            lasku.ytunnus,
            lasku.toimaika,
            lasku.asiakkaan_tilausnumero,
            tyomaarays.komm1,
            tyomaarays.komm2,
            tyomaarays.tyojono,
            tyomaarays.tyostatus,
            kuka.nimi myyja,
            a1.selite tyojonokoodi,
            a1.selitetark tyojono,
            a2.selitetark tyostatus,
            a2.selitetark_2 tyostatusvari,
            yhtio.nimi yhtio,
            yhtio.yhtio yhtioyhtio,
            a3.nimi suorittajanimi,
            a5.selitetark tyom_prioriteetti,
            lasku.luontiaika,
            group_concat(a4.selitetark_2) asekalsuorittajanimi,
            group_concat(concat(left(kalenteri.pvmalku,16), '##', left(kalenteri.pvmloppu,16), '##', if(a4.selitetark_2 is null or a4.selitetark_2 = '', kalenteri.kuka, a4.selitetark_2), '##', kalenteri.tunnus, '##', a4.selitetark, '##', timestampdiff(SECOND, kalenteri.pvmalku, kalenteri.pvmloppu))) asennuskalenteri,
            tyomaarays.valmnro,
            tyomaarays.mallivari,
            tyomaarays.merkki,
            tyomaarays.luvattu,
            laite.sla
            FROM lasku
            JOIN yhtio ON (lasku.yhtio=yhtio.yhtio)
            JOIN tyomaarays ON (tyomaarays.yhtio=lasku.yhtio and tyomaarays.otunnus=lasku.tunnus )
            LEFT JOIN laskun_lisatiedot ON (lasku.yhtio=laskun_lisatiedot.yhtio and lasku.tunnus=laskun_lisatiedot.otunnus)
            LEFT JOIN kuka ON (kuka.yhtio=lasku.yhtio and kuka.tunnus=lasku.myyja)
            LEFT JOIN avainsana a1 ON (a1.yhtio=tyomaarays.yhtio and a1.laji='TYOM_TYOJONO'   and a1.selite=tyomaarays.tyojono)
            LEFT JOIN avainsana a2 ON (a2.yhtio=tyomaarays.yhtio and a2.laji='TYOM_TYOSTATUS' and a2.selite=tyomaarays.tyostatus)
            LEFT JOIN kuka a3 ON (a3.yhtio=tyomaarays.yhtio and a3.kuka=tyomaarays.suorittaja)
            LEFT JOIN kalenteri ON (kalenteri.yhtio = lasku.yhtio and kalenteri.tyyppi = 'asennuskalenteri' and kalenteri.liitostunnus = lasku.tunnus)
            LEFT JOIN avainsana a4 ON (a4.yhtio=kalenteri.yhtio and a4.laji='TYOM_TYOLINJA'  and a4.selitetark=kalenteri.kuka)
            LEFT JOIN avainsana a5 ON (a5.yhtio=tyomaarays.yhtio and a5.laji='TYOM_PRIORIT' and a5.selite=tyomaarays.prioriteetti)
            LEFT JOIN laite ON (laite.yhtio = lasku.yhtio and laite.sarjanro = tyomaarays.valmnro) 
            WHERE lasku.yhtio = '{$kukarow['yhtio']}'
            AND lasku.tila     in ('A','L','N','S','C')
            {$alatila}
            AND lasku.liitostunnus = '{$kukarow['oletus_asiakas']}'
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22
            ORDER BY ifnull(a5.jarjestys, 9999), ifnull(a2.jarjestys, 9999), a2.selitetark, lasku.toimaika";
  $result = pupe_query($query);

  while ($row = mysql_fetch_assoc($result)) {
    $tyomaaraykset[] = $row;
  }

  return $tyomaaraykset;
}

function piirra_tyomaaraysheaderit() {
  $headers = array(
    t('Tilaus')."<br>".t('Asiakkaan tilausnumero'),
    t('Luontiaika'),
    t('Valmistaja')."<br>".t('Malli'),
    t('Valmnro')."<br>".t('Tuotenro'),
    t('SLA'),
    t('Luvattu'),
    t('Ty�jono'),
    t('Ty�status'),
    t('Vian kuvaus'),
    t('Ty�n toimenpiteet')
  );

  foreach ($headers as $header) {
    echo "<th>$header</th>";
  }
}

function piirra_tyomaaraysrivi($tyomaarays) {
  echo "<tr style='background-color: {$tyomaarays['tyostatusvari']};'>";
  echo "<td>{$tyomaarays['tunnus']} <br> {$tyomaarays['asiakkaan_tilausnumero']}</td>";
  echo "<td>{$tyomaarays['luontiaika']}</td>";
  echo "<td>{$tyomaarays['valmistaja']} <br> {$tyomaarays['malli']}</td>";
  echo "<td>{$tyomaarays['valmnro']} <br> {$tyomaarays['mallivari']}</td>";
  echo "<td>{$tyomaarays['sla']}</td>";
  echo "<td>".tv1dateconv($tyomaarays['luvattu'])."</td>";
  echo "<td>{$tyomaarays['tyojono']}</td>";
  echo "<td>{$tyomaarays['tyostatus']}</td>";
  echo "<td>{$tyomaarays['komm1']}</td>";
  echo "<td>{$tyomaarays['komm2']}</td>";
  echo "</tr>";
}

function piirra_luo_tyomaarays() {
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  echo "<input type='hidden' name='tyom_toiminto' value='UUSI'>";
  echo "<input type='submit' value='".t('Uusi ty�m��r�ys')."'>";
  echo "</form>";
}

function piirra_nayta_aktiiviset_poistetut() {
  global $request;
  echo "<br>";
  echo "<form name='uusi_tyomaarays_button'>";
  if (!empty($request['nayta_poistetut'])) {
    echo "<input type='hidden' name='nayta_poistetut' value=''>";
    echo "<input type='submit' value='".t('N�yt� aktiiviset')."'>";
  }
  else {
    echo "<input type='hidden' name='nayta_poistetut' value='JOO'>";
    echo "<input type='submit' value='".t('N�yt� vanhat')."'>";
  }
  echo "</form>";
  echo "<br><br>";
}

function uusi_tyomaarays_formi($laite_tunnus) {
  echo "<font class='head'>".t("Uusi ty�m��r�ys")."</font><hr>";
  // Jos ollaan tultu laiterekisterist� ja halutaan tehd� ty�m��r�ys tietylle laitteelle
  if (!empty($laite_tunnus)) {
    $request['tyom_parametrit'] = hae_laitteen_parametrit($laite_tunnus);
  }
  echo "<form name ='uusi_tyomaarays_form'>";
  echo "<table>";
  echo "<tr>";
  piirra_tyomaaraysheaderit();
  echo "</tr>";
  echo "<tr>";
  piirra_edit_tyomaaraysrivi($request);
  echo "</tr>";
  echo "</table>";
  echo "<br>";
  echo "<input type='submit' name='avaa_tyomaarays_nappi' value='".t('Avaa ty�m��r�ys')."'>";
  echo "</form>";
}

function piirra_edit_tyomaaraysrivi($request) {
  echo "<td><input type='text' name='asiakkaan_tilausnumero' value='{$request['tyom_parametrit']['asiakkaan_tilausnumero']}'></td>";
  echo "<td></td>";
  echo "<td><input type='text' name='valmistaja' value='{$request['tyom_parametrit']['valmistaja']}'>";
  echo "<br><br><input type='text' name='malli' value='{$request['tyom_parametrit']['malli']}'></td>";
  echo "<td><input type='text' name='valmnro' value='{$request['tyom_parametrit']['valmnro']}'>";
  echo "<br><br><input type='text' name='tuotenro' value='{$request['tyom_parametrit']['tuoteno']}'></td>";
  echo "<td><input type='text' name='sla' size='3' value='{$request['tyom_parametrit']['sla']}' disabled=true></td>";
  echo "<td></td>";
  echo "<td></td>";
  echo "<td></td>"; 
  echo "<td><textarea cols='40' rows='5' name='komm1'>{$request['tyom_parametrit']['komm1']}</textarea></td>";
  echo "<td></td>";
}

function tallenna_tyomaarays($request) {
  global $kukarow;

  // Haetaan oletusasiakkuus
  $query = "SELECT asiakas.*
            FROM asiakas 
            WHERE asiakas.yhtio = '{$kukarow['yhtio']}'
            AND asiakas.tunnus = '{$kukarow['oletus_asiakas']}'";
  $result = pupe_query($query);
  $asiakastiedot = mysql_fetch_assoc($result);

  // Luodaan uusi lasku
  $query  = "INSERT INTO lasku
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika = now(),
             laatija = '{$kukarow['kuka']}',
             nimi = '{$asiakastiedot['nimi']}',
             nimitark = '{$asiakastiedot['nimitark']}',
             osoite = '{$asiakastiedot['osoite']}',
             postino = '{$asiakastiedot['postino']}',
             postitp = '{$asiakastiedot['postitp']}',
             maa = '{$asiakastiedot['maa']}',
             ytunnus = '{$asiakastiedot['ytunnus']}',
             liitostunnus = '{$kukarow['oletus_asiakas']}',
             tilaustyyppi = 'A',
             tila = 'A',
             asiakkaan_tilausnumero = '{$request['tyom_parametrit']['asiakkaan_tilausnumero']}'";
  $result = pupe_query($query);
  $utunnus = mysql_insert_id($GLOBALS["masterlink"]);

  // Luodaan uusi ty�m��r�ys
  $query  = "INSERT INTO tyomaarays
             SET yhtio = '{$kukarow['yhtio']}',
             luontiaika = now(),
             otunnus = '{$utunnus}',
             laatija = '{$kukarow['kuka']}',
             tyostatus = 'O',
             prioriteetti = '3',
             hyvaksy = 'Kyll�',
             komm1 = '{$request['tyom_parametrit']['komm1']}',
             sla = '{$request['tyom_parametrit']['sla']}',
             mallivari = '{$request['tyom_parametrit']['tuotenro']}',
             valmnro = '{$request['tyom_parametrit']['valmnro']}',
             merkki = '{$request['tyom_parametrit']['merkki']}'";
  $result  = pupe_query($query); 
}

function hae_laitteen_parametrit($laite_tunnus) {
  global $kukarow;

  $laiteparametrit = array();

  $query = "SELECT
            laite.*,
            avainsana.selitetark valmistaja,
            tuote.tuotemerkki malli
            FROM laite
            LEFT JOIN tuote ON (tuote.yhtio = laite.yhtio
            AND tuote.tuoteno = laite.tuoteno)
            LEFT JOIN avainsana ON (avainsana.yhtio = tuote.yhtio
            AND avainsana.laji = 'TRY'
            AND avainsana.selite = tuote.try)
            WHERE laite.yhtio = '{$kukarow['yhtio']}'
            AND laite.tunnus = '{$laite_tunnus}'";
  $result = pupe_query($query);
  $row = mysql_fetch_assoc($result);

  $laiteparametrit['valmistaja'] = $row['valmistaja'];
  $laiteparametrit['malli'] = $row['malli'];
  $laiteparametrit['valmnro'] = $row['sarjanro'];
  $laiteparametrit['tuoteno'] = $row['tuoteno'];
  $laiteparametrit['sla'] = $row['sla'];

  return $laiteparametrit;
}
