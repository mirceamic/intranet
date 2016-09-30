<div id="container">
	<h3>Intranet</h3>
	<br />
	
	<p>Perioade libere personale</p>
	<table cellpadding = 0 cellspacing = 0 width = "600px">
		<tr>
			<th width = "8%">ID</th>
			<th width = "6%">tip</th>
			<th width = "22%">Inceput</th>
			<th width = "22%">Sfarsit</th>
			<th width = "17%">Inlocuitor</th>
			<th width = "25%">Observatii</th>
		</tr>
	<?php echo $liber; ?>
	</table>
	<br />
	<p>Perioade libere ca si inlocuitor</p>
	<table cellpadding = 0 cellspacing = 0 width = "600px">
		<tr>
			<th width = "17%">Angajat</th>
			<th width = "22%">Inceput</th>
			<th width = "22%">Sfarsit</th>
			<th width = "25%">Observatii</th>
		</tr>
	<?php echo $liberi; ?>
	</table>
	<br />
	<div class = "legenda">
		<h5>Legenda</h5>
		<div class = "tip1"><a>perioada libera</a></div>
		<br class = "clar" />
		<div class = "tip2"><a>concediu medical</a></div>
		<br class = "clar" />
		<div class = "tip3"><a>delegatie</a></div>
		<br class = "clar" />
	</div>
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
</div>
