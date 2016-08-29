	<div id="container">
		<h3>Generare oferte PDF</h3>
<?php
	echo $mount;
?>

		<p>Modele de oferte PDF:</p>
<?php
	foreach($formular as $valori){
		echo $valori;
	}
?>

		<br style = "clear: left;" />
		<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
	</div>
