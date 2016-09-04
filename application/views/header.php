<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<?php
		## adauga fisierul W3 CSS
		echo link_tag('css/w3.css');
		## adauga fisierul principal CSS
		echo link_tag('css/main.css');
	
		## adauga fisierul special pentru controlerul cerut
		echo link_tag('css/' . $this->router->fetch_class() . '.css');
	?>
	<style>
		html, body {
			font-family: "Comic Sans MS", cursive, sans-serif;
		}
	</style>
</head>
<body>
	<header class="w3-container w3-teal">
		<h3 id = "logo" class="w3-left">
			<a href="<?php echo base_url(); ?>">
				Intranet Oradea
			</a>
		</h3>
		<h3 id="nume" class="w3-right w3-medium">
			Utilizator: <a href = "<?php
echo base_url('index.php/home/index/reset');
?>" style = "text-decoration: none;"><?php echo $this->session->username . "\n"; ?></a>

		</h3>
		<div style = "clear:both;" class = "w3-large">
			<ul class = "w3-navbar w3-teal w3-center">
<?php echo $this->session->meniuri; ?>
			</ul>
		</div>
	</header>
