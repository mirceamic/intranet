<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
	}
	
	public function index($page = 'pdf')
	{
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
		
		## verifica daca e montat directorul cu poze
		$data['mount'] = $this->check_mount();
		
		## construieste formularele pentru fiecare "template" de pdf
		## matricea coloanelor
		$cols = array(
			'Art',
			'Mat',
			'Col',
			'Color',
			'RRP',
			'Price',
			'Delivery',
			'Country',
			'Image'
		);
		
		## genereaza formularul
		$data['formular'][0] = $this->make_formular($cols, 'pdf_file');
		
		## Template 2
		## matricea coloanelor
		$cols = array(
			'Art',
			'Mat',
			'Col',
			'Color',
			'Country',
			'Image'
		);
		
		## genereaza formularul
		$data['formular'][1] = $this->make_formular($cols, 'pdf_standard');
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## functia de adaugare a fisierului pentru oferta pdf
	public function add($page){
		
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## verifica daca s-a incarcat fisier
		#if($this->input->post('standard') == ''){
		#	redirect(base_url('index.php/pdf'));
		#}
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		$data['valori'] = '<div>Test</div>';
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page, $data);
		$this->load->view('footer');
	}
	
	## Functii private ##
	
	## functie care verifica daca e montat directorul cu poze
	private function check_mount(){
		
		$string = '';
		
		## verifica daca exista imaginea generica
		if(file_exists('/mnt/pdf/generic.jpg')){
			$string .= '<p>Status poze: OK</p>';
		} else {
			$string .= '<p>Status poze: eroare (ia legatura cu autorul)</p>';
		}
		
		return $string;
	}
	
	## functie pentru construirea unui formular de generare a pdf-ului
	private function make_formular($cols, $fname){
		
		$string = '<br /><div><div class = "w3-container ct">Nr</div>';
		
		## construieste un div cu valorile capului de tabel final
		foreach($cols as $col){
			$string .= '<div class = "w3-container ct">' . $col . '</div>';
		}
		
		$string .= '</div><br style = "clear: left;" />' . "\n";
		
		## genereaza form-ul care duce la generearea acestui template
		$link = 'index.php/pdf/add/' . $fname;
		$string .= form_open($link);
		#$string .= form_open($link, 'enctype = "multipart/form-data"');
		$string .= form_upload($fname);
		$string .= form_submit('submit', 'Incarca');
		$string .= form_close();
		
		return $string;
	}
	
}
