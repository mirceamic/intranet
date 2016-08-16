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
		
		## incarca helper-ul de fisiere
		$this->load->helper('file');
		
		## incarca libraria Excel
		$this->load->library('excel');
		
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
	}
	
	public function index($page = 'sourcing')
	{
		
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
		
		## functie de test
		#$data['export'] = $this->test();
		
		## functie de verificare a tipului de WS
		$data['export'] = $this->getTip();
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
	## functie de test
	private function test(){
		
		## matricea de transport
		$valori = array();
		## numarul fisierelor
		$fnr = 0;
		
		## calea catre WS-uri
		$cale = '/mnt/ws/';
		
		## afla numele fiecarui fisier din director
		$fisiere = get_filenames($cale);
		
		$string = '';
		
		foreach($fisiere as $fname){
			
			$fcale = $cale . $fname;
			
			$objPHPExcel = PHPExcel_IOFactory::load($fcale);
			
			## adu valorile celulelor intr-o matrice
			$cells = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
			
			## proceseaza valorile dorite
			foreach($cells as $nr => $row){
				
				## treci peste randurile de upper
				if($nr == 8){
					continue;
				} elseif ($nr >= 9 && $nr <= 12){
					$idnr = $nr - 8;
					$mat = 'material' . $idnr;
					$col = 'color' . $idnr;
					$sup = 'supplier' . $idnr;
					
#					$valori[$fnr][$mat] = $row['B'];
#					$valori[$fnr][$col] = $row['D'];
#					$valori[$fnr][$sup] = $row['E'];
					
					## scrie string-ul
					$string .= $mat . ' - ' . $row['B'] . ", " .
						$col . ' - ' . $row['D'] . ", " .
						$sup . ' - ' . $row['E'] . ", ";
					
				} else {
				
					## adauga valorile in matricea de transport
#					$valori[$fnr][str_replace("\n",'',($row['A']))] = str_replace("\n",'',($row['B']));
#					$valori[$fnr][str_replace("\n",'',($row['C']))] = str_replace("\n",'',($row['D']));
					
					## scrie string-ul
					$string .= str_replace("\n",'',($row['A'])) . ' - ' .
						str_replace("\n",'',($row['B'])) . ", " .
						str_replace("\n",'',($row['C'])) . ' - ' .
						str_replace("\n",'',($row['D'])) . ", ";
					
				}
				
			}
			
			$string .= "\n\n";
			
			
		}
		
		
		#return $valori;
		return $string;
	}
	
	## functie de verificare a tipului de WS
	private function getTip(){
		
		## matricea de transport
		$valori = array();
		## numarul fisierelor
		$fnr = 0;
		
		## calea catre WS-uri
		$cale = '/mnt/ws/';
		
		## afla numele fiecarui fisier din director
		$fisiere = get_filenames($cale);
		
		$string = '';
		
		foreach($fisiere as $fname){
			
			$fcale = $cale . $fname;
			
			$objPHPExcel = PHPExcel_IOFactory::load($fcale);
			
			## adu valorile celulelor intr-o matrice
			$cells = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
			
			## verifica unde e valoarea "Hang Tag"
			
		}
	}
			
}
