<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="ro">
<head>
	<meta charset="utf-8">
	<title>Access denied</title>
	<?php
		## adauga fisierul principal CSS
		echo link_tag('css/main.css');
	?>
</head>
<body>
<div id="container">
	<h3>Access denied</h3>
	<br />
	<p>Nu ai acces la aceasta aplicatie.<br />Pentru a primi acces, transmite la IT urmatorul sir de caractere:
<?php
	echo $this->session->mac;
?>
	</p>
	<br />
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
</div>
</body>
</html>
