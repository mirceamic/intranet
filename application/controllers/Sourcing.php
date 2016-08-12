<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sourcing extends CI_Controller {
	
	
	public function __construct()
	{
		parent::__construct();
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
	}
	
	public function index($page = 'sourcing')
	{
		
		## incarca libraria Excel
		#$this->load->library('excel');
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/sourcing/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## contruieste calea intreaga a paginii de incarcat
		$fpage = 'sourcing/' . $page;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
}
