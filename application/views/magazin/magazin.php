<div id="container">
	<h3>Magazin_inception</h3>
	<br />
	<div class = "linkuri">
<?php
foreach($linkuri as $titlu => $link){
?>
	<a href = "<?php echo $link; ?>">
		<?php echo $titlu; ?>
	</a><br />
<?php
}
?>
	</div>
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
</div>
