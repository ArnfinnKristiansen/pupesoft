<?php

require("inc/parametrit.inc");

echo "
<font class='head'>CSS-testing:</font><hr><br>

T�ss� n�kee miten formit k�ytt�ytyy:
<form action='#'>
<input type='text'>
<input type='checkbox'	name='1'>
<input type='radio' 	name='2'>
<input type='radio' 	name='2'>
<input type='submit'>
</form> pit�isi pysy� nipussa ilman suurempia aukkoja.

<br>
<br>

<table>
<tr><th>TH</th><th>TH</th><th>TH</th></tr>
<tr><td>TD</td><td>TD</td><td>TD</td></tr>
<tr class='aktiivi'><td>TD (TR.aktiivi) (T�ss� on hover toiminto)</td><td>TD (tr aktiivi)</td><td>TD (tr aktiivi)</td></tr>
<tr><td class='back'>TD.back: Fontinv�ri sama kuin TD:ss� mutta tausta sama kuin BODY:ss�</td><td class='back'>TD.back</td><td class='back'>TD.back</td></tr>
<tr><td class='green'>TD.green: Fontti vihre�. Tausta sama kuin TD:ss�</td><td class='green'>TD.green</td><td class='green'>TD.green</td></tr>
<tr><td class='spec'>TD.spec: Tausta sama kuin TD:ss� mutta fontinv�ri sama ku TH:ssa</td><td class='spec'>TD.spec</td><td class='spec'>TD.spec</td></tr>
<tr><td class='tumma'>TD.tumma: Fontti ja tausta samanv�riset kuin TH:ssa</td><td class='tumma'>TD.tumma</td><td class='tumma'>TD.tumma</td></tr>
</table>

<br>
<br>

<a href='#'>Default linkki (T�ss� on hover toiminto)</a>
<a class='td' href='#'>TD:class linkki (T�ss� on hover toiminto)</a>
<a class='menu' href='#'>Menu:class linkki (T�ss� on hover toiminto)</a>

<br>
<br>

Default teksti�: bla bla bla bla!!!<br>
<font class='info'>INFO teksti�: bla bla bla bla!!!</font><br>
<font class='head'>HEAD teksti�: bla bla bla bla!!!</font><br>
<font class='menu'>MENU teksti�: bla bla bla bla!!!</font><br>
<font class='error'>ERROR teksti�: bla bla bla bla!!!</font><br>
<font class='message'>MESSAGE teksti�: bla bla bla bla!!!</font><br>

<br>
<br>

<pre>PRE-teksti�: bla bla bla bla!!!</pre>

<br>
<br>

<div class='popup' style='visibility:visible'>DIV:POPUP kannataa vaan kattoa, rtt� on hyv�n n�k�inen suhteessa muihin v�reihin</div>

<br>
<br>";

require("inc/footer.inc");

?>