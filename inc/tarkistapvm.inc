<?php
$query = "SELECT TO_DAYS('$pvm')"; // huh huh mik� h�kki!!!
$result = pupe_query($query);
$pvmrow=mysql_fetch_array($result);
$pvm=$pvmrow[0] + 0;
$query = "SELECT FROM_DAYS($pvm)"; // huh huh mik� h�kki!!!
$result = pupe_query($query);
$pvmrow=mysql_fetch_array($result);
$pvm=$pvmrow[0];
