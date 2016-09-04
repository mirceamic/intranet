<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Necesar extends CI_Controller {
	
	public function index($page = 'necesar')
	{
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('necesar', $this->session->chkMenu)){
			
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
		
		#$this->aduLuna();
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
	## functie pentru aducerea lunii active
	private function aduLuna(){
		
		## construieste sql-ul
		$sql = 'select id, denumire from aprv_perioade where status = 1';
		
		## extrage datele din DB
		$qry = $this->db->query($sql);
		
		## verifica daca exista date extrase
		if($qry->num_rows() == 0){
			
		}
	}
	
}
