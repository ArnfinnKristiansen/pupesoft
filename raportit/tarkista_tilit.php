<?php

//* T�m� skripti k�ytt�� slave-tietokantapalvelinta *//
$useslave = 2;

require "../inc/parametrit.inc";

echo "<br><font class='head'>".t("Tili�intien tilit")."</font><hr><br>";

$query = "SELECT distinct tili.tilino t, tiliointi.tilino
          FROM tiliointi
          LEFT JOIN tili ON tili.yhtio=tiliointi.yhtio and tili.tilino=tiliointi.tilino
          WHERE tiliointi.yhtio  = '$kukarow[yhtio]'
          and tiliointi.korjattu = ''
          and tiliointi.tilino   > 0
          HAVING t IS NULL";
$result = pupe_query($query);

while ($tili = mysql_fetch_array($result)) {
  $query = "SELECT tapvm viimeisin, selite
            FROM tiliointi
            WHERE yhtio  = '$kukarow[yhtio]'
            and tilino   = $tili[tilino]
            and korjattu = ''
            ORDER BY tapvm asc
            LIMIT 1";
  $res = pupe_query($query);
  $viimrow = mysql_fetch_array($res);

  echo "<br><font class='error'>Tili� $tili[tilino] ei ole en�� olemassa! Viimeisin tili�inti $viimrow[viimeisin], $viimrow[selite]</font><br>";
}

$tables  = array("asiakas", "tuote", "toimi");
$columnit = array("tilino", "tilino_eu", "tilino_ei_eu");

$query = "SHOW TABLES";
$result = pupe_query($query);

while ($table = mysql_fetch_array($result)) {

  if (in_array($table[0], $tables)) {

    echo "<br><font class='message'>Tarkastetaan taulu $table[0]</font><br>";

    $query = "  SHOW columns FROM $table[0]";
    $res = pupe_query($query);

    while ($col = mysql_fetch_array($res)) {
      if (in_array($col[0], $columnit)) {
        foreach ($columnit as $c) {
          $query = "SELECT $c
                    FROM $table[0]
                    WHERE yhtio = '$kukarow[yhtio]'
                    and $c != ''";
          $haku = pupe_query($query);

          while ($row = mysql_fetch_array($haku)) {
            $query = "SELECT tunnus
                      FROM tili
                      WHERE yhtio = '$kukarow[yhtio]' and tilino = '".$row[$c]."'";
            $tarkresr = pupe_query($query);

            if (mysql_num_rows($tarkresr) == 0) {
              echo "<font class='error'>[$c] Tili� ei l�ydy '".$row[$c]."'</font><br>";
            }
          }
        }
      }
    }
  }
}

require "../inc/footer.inc";
