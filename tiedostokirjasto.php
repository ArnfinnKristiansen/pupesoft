<?php

(@include "inc/parametrit.inc") || (@include "parametrit.inc") || exit;

require_once("tiedostofunkkarit.inc");

$toim           = isset($toim) ? strtoupper($toim) : "";
$aihealue       = isset($aihealue) ? $aihealue : "";
$tiedostotyyppi = isset($tiedostotyyppi) ? $tiedostotyyppi : "";
$toimittaja     = isset($toimittaja) ? $toimittaja : "";

if ($toim == "LAATU") {
  $otsikko     = "Laatu-asiakirjat";
  $ylaotsikko  = "Aihealueet";
  $aihealueet  = hae_aihealueet();
  $toimittajat = "";
}
else {
  $otsikko     = "Tiedostokirjasto";
  $ylaotsikko  = "Toimittajat";
  $toimittajat = hae_toimittajat_selectiin();
  $aihealueet  = "";
}

echo "<font class='head'>" . t($otsikko) . "</font>";
echo "<hr>";

$tee = empty($tee) ? '' : $tee;

$params = array(
  "aihealue"               => $aihealue,
  "tiedoston_tyyppi"       => $tiedostotyyppi,
  "valittu_toimittaja"     => $toimittaja,
  "ylaotsikko"             => $ylaotsikko,
  "toimittajat"            => $toimittajat,
  "aihealueet"             => $aihealueet,
  "valittu_aihealue"       => $aihealue,
  "valittu_tiedostotyyppi" => $tiedostotyyppi
);

if ($tee == 'hae_tiedostot' and !empty($tiedostotyyppi)) {
  $tiedostot = hae_tiedostot($params);

  piirra_formi($params);
  piirra_tiedostolista($tiedostot);
}
else {
  $tee = "";
}

if ($tee == "") {
  if ($toim == "LAATU" and empty($aihealueet)) {
    echo "<font class='error'>" . t("Aihealueita ei ole viel� lis�tty") . "</font>";
  }
  else {
    piirra_formi($params);
  }
}

function piirra_formi($params) {
  global $toim;

  $ylaotsikko             = isset($params["ylaotsikko"]) ? $params["ylaotsikko"] : "";
  $toimittajat            = isset($params["toimittajat"]) ? $params["toimittajat"] : "";
  $aihealueet             = isset($params["aihealueet"]) ? $params["aihealueet"] : "";
  $valittu_aihealue       = isset($params["valittu_aihealue"]) ? $params["valittu_aihealue"] : "";
  $valittu_tiedostotyyppi =
    isset($params["valittu_tiedostotyyppi"]) ? $params["valittu_tiedostotyyppi"] : "";
  $valittu_toimittaja     =
    isset($params["valittu_toimittaja"]) ? $params["valittu_toimittaja"] : "";

  echo "<form action='tiedostokirjasto.php' method='post'>";
  echo "<input type='hidden' name='tee' value='hae_tiedostot'/>";
  echo "<input type='hidden' name='toim' value='{$toim}'>";
  echo "<table>";
  echo "<tbody>";

  echo "<tr>";
  echo "<td><label for='toimittaja_id'>" . t($ylaotsikko) . "</label></td>";
  echo "<td>";

  if (!empty($toimittajat)) {
    echo '<select id="toimittaja" name="toimittaja" onchange="submit()">';
    foreach ($toimittajat as $toimittaja) {
      $valittu = $valittu_toimittaja == $toimittaja['tunnus'] ? "selected" : "";
      echo "<option {$valittu} value='{$toimittaja['tunnus']}'>{$toimittaja['nimi']}</option>";
    }
  }
  elseif ($aihealueet) {
    echo '<select id="aihealue" name="aihealue" onchange="submit()">';
    foreach ($aihealueet as $aihealue) {
      $valittu = $valittu_aihealue == $aihealue['selite'] ? "selected" : "";
      echo "<option {$valittu} value='{$aihealue['selite']}'>{$aihealue['selite']}</option>";
    }
  }

  echo '</select>';
  echo "</td>";
  echo "</tr>";

  if ($valittu_aihealue and $tiedostotyypit = tiedostotyypit($valittu_aihealue)) {
    echo "<tr>";
    echo "<td><label for='tyyppi_id'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi_id' name='tiedostotyyppi' onchange='submit()'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $valittu = $valittu_tiedostotyyppi == $tiedostotyyppi['selitetark'] ? "selected" : "";
      echo "<option value='{$tiedostotyyppi["selitetark"]}'
                    {$valittu}>{$tiedostotyyppi["selitetark"]}
            </option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }
  elseif ($tiedostotyypit = tiedostotyypit()) {
    echo "<tr>";
    echo "<td><label for='tiedostotyyppi'>" . t("Tiedoston tyyppi") . "</label></td>";
    echo "<td>";
    echo "<select id='tiedostotyyppi' name='tiedostotyyppi' onchange='submit()'>";

    foreach ($tiedostotyypit as $tiedostotyyppi) {
      $tiedostotyyppinimi =
        t_avainsana("LITETY", '', "and selite = '{$tiedostotyyppi}'", '', '', "selitetark");
      $valittu            = $valittu_tiedostotyyppi == $tiedostotyyppi ? "selected" : "";
      echo "<option value='{$tiedostotyyppi}'
                    {$valittu}>{$tiedostotyyppinimi}
            </option>";
    }

    echo "</select>";
    echo "</td>";
    echo "</tr>";
  }
  elseif ($valittu_aihealue) {
    echo
      "<tr>
         <td colspan='2'>
           <font class='error'>" .
      t("T�lle aihealueelle ei ole viel� lis�tty tiedostotyyppej�") .
      "</font>
         </td>
       </tr>";
  }

  echo "<tr>";
  echo "<td class='back'>";
  echo "<input type='submit' value='" . t("Hae") . "'/>";
  echo "</td>";
  echo "</tr>";

  echo "</tbody>";
  echo "</table>";
  echo "</form>";
}
