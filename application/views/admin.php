<div id="container">
	<h3>Administrare</h3>
	<br />
	<div>
		<p>Alege utilizatorul</p>
		<?php
			echo form_open('index.php/admin');
			echo form_dropdown('users',
				$utilizatori['utilizatori']
			);
			echo form_submit('select', 'Select');
			echo form_close();
		?>
	</div>
	<br />
	<div>
		<?php
			echo $infouser;
		?>
	</div>
	
	
	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds.</p>
</div>
