<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<?php
		## adauga fisierul principal CSS
		echo link_tag('css/main.css');
	?>
	<?php
		## adauga fisierul special pentru controlerul cerut
		echo link_tag('css/' . $this->router->fetch_class() . '.css');
	?>
</head>
<body>
	<div id="top">
		<h3 id="logo">
			<a href="<?php echo base_url(); ?>">
				Intranet<br />Oradea
			</a>
		</h3>
		<h3 id="nume">
			Utilizator: <?php echo $this->session->username . "\n"; ?>
		</h3>
		<ul>
<?php echo $this->session->meniuri; ?>
		</ul>
	</div>
