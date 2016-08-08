<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rapoarte extends CI_Controller {
	
	public function index($page = 'rapoarte')
	{
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
}
