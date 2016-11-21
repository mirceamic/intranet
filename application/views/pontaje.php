<div id="container">
	<h3>Situatie pontaje</h3>
	<br />

<?php echo $selector; ?>

	<br />
<!-- Tabelul cu valorile initiale -->
<table class = "tabel init">
	<tr>
		<th rowspan = "2" class = "small">An</th>
		<th rowspan = "2" class = "small">Luna</th>
		<th colspan = "3">Concediu la inceputul lunii</th>
		<th rowspan = "2" class = "big">Observatii</th>
	</tr><tr>
		<th class = "small">zile</th>
		<th class = "small">ore</th>
		<th class = "small">min</th>
	</tr>
<?php echo $tabelInit; ?>
</table>
	<br />
<!-- Capul de tabel -->
<table class = "tabel date">
	<tr>
		<th rowspan = "2" class = "zitip">Ziua</th>
		<th rowspan = "2" class = "zitip">Tip</th>
		<th colspan = "2">Timp real</th>
		<th colspan = "2">Timp calculat</th>
		<th rowspan = "2" class = "small">Prezenta</th>
		<th colspan = "3">Concediu ramas</th>
	</tr><tr>
		<th class = "small">intrare</th>
		<th class = "small">iesire</th>
		<th class = "small">intrare</th>
		<th class = "small">iesire</th>
		<th class = "small">zile</th>
		<th class = "small">ore</th>
		<th class = "small">min</th>
	</tr>
	
<?php echo $pontaje; ?>
</table>
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
</div>
