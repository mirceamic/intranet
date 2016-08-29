<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sourcing extends CI_Controller {
	
	
	public function __construct()
	{
		parent::__construct();
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('sourcing', $this->session->chkMenu)){
			
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
		
		## pune o limita mai mare de rulare a script-ului
		set_time_limit(600);
		
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
		
		#$string = '';
		## matricea pentru verificarea tipului de WS
		$numaratoare = array(
			0 => array(),
			30 => 0,
			36 => 0,
			39 => 0,
			43 => 0
		);
		
		## reseteaza fisierele care tin datele
		$this->resetFiles($numaratoare);
		
		foreach($fisiere as $fname){
			
			## matricea implicita a coloanelor
			$cols = array(
				array('A', 'C'),
				array('B', 'D'));
			
			## matricea implicita pentru upper
			$upper = array('B', 'D', 'E');
			## matrice implicita cu randurile pentru upper
			$udata = array(9,10,11,12);
				
			## construieste calea catre fisier
			$fcale = $cale . $fname;
			
			## incarca fisierul intr-un obiect PHPExcel
			$objPHPExcel = PHPExcel_IOFactory::load($fcale);
			
			## adu valorile celulelor intr-o matrice
			$cells = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
			
			## verifica unde e valoarea "Hang Tag"
			if($cells[30]['A'] == 'HANG TAG'){
				
				## matrice cu randuri peste care trebuie sarit (randuri goale)
				$gol = array(8, 16, 20, 24);
				
				## proceseaza fisierul
				$this->extractExcel($cells, $cols, $upper, $udata, $gol, 30);
				
				$numaratoare[30]++;
				
			} elseif($cells[36]['A'] == 'HANG TAG'){
				$numaratoare[36]++;
				
			} elseif($cells[39]['A'] == 'HANG TAG'){
				
				## matrice cu randuri peste care trebuie sarit (randuri goale)
				$gol = array(8, 18, 24, 28, 34);
				
				## proceseaza fisierul
				$this->extractExcel($cells, $cols, $upper, $udata, $gol, 39);
				
				$numaratoare[39]++;
				
			} elseif($cells[43]['A'] == 'HANG TAG'){
				
				## matricea pentru upper
				$upper = array('C', 'F', 'G');
				## matricea de coloane
				$cols = array(
					array('A', 'D'),
					array('C', 'F'));
				
				## matrice cu randuri peste care trebuie sarit (randuri goale)
				$gol = array(8, 18, 24, 28);
				
				## proceseaza fisierul
				$this->extractExcel($cells, $cols, $upper, $udata, $gol, 43);
				
				$numaratoare[43]++;
				
			} else {
				array_push($numaratoare[0], $fname);
			}
		}
		
		$string = $this->createView($numaratoare);
		
		return $string;
	}
	
	## functie pentru resetarea (golirea) fisierelor care tin datele
	private function resetFiles($valori){
		
		## scoate cheia 0 din matricea de valori
		unset($valori[0]);
		
		## pentru fiecare cheie din matrice, reseteaza fisierul echivalent
		foreach($valori as $key => $val){
			
			$file = 'tmp/output' . $key . '.csv';
			
			write_file($file, '');
		}
	}
	
	## functie de extras datele dintr-un fisier excel
	private function extractExcel($celule, $cols, $upper, $udata, $gol, $nr){
		
		## initializeaza string-urile (1 - titlu; 2 - date)
		$string1 = '';
		$string2 = '';
		
		## contor pentru upper
		$idnr = 0;
		
		## despartitorul csv-ului
		$desp = ';';
		
		## incepe procesarea datelor
		foreach($celule as $rand => $coloana){
			
			## treci peste anumite randuri
			if(in_array($rand, $gol)){
				
				continue;
				
			## proceseaza randurile de upper
			} elseif(in_array($rand, $udata)){
				
				## contruieste titlul
				$idnr++ ;
				$mat = 'material' . $idnr;
				$col = 'color' . $idnr;
				$sup = 'supplier' . $idnr;
				
				$string1 .= $mat . $desp . $col . $desp . $sup . $desp;
				
				## contruieste datele
				foreach($upper as $val){
					
					## contruieste valorile conform coloanelor specificate
					$string2 .= $coloana[$val] . $desp;
					
				}
			} else {
				
				## construieste titlul
				$string1 .= str_replace("\n",'',$coloana[$cols[0][0]]) . $desp .
					str_replace("\n",'',$coloana[$cols[0][1]]) . $desp;
				
				## construieste datele
				$string2 .= str_replace("\n",'',$coloana[$cols[1][0]]) . $desp .
					str_replace("\n",'',$coloana[$cols[1][1]]) . $desp;
				
			}
		}
		
		$string1 .= "\n";
		$string2 .= "\n";
		
		$file = 'tmp/output' . $nr . '.csv';
		write_file($file, $string1, 'a');
		write_file($file, $string2, 'a');
	}
	
	## functie pentru afisarea rezultatelor exportului din excel
	private function createView($nr){
		
		$string = '';
		$fisiere = "<p>Fisierele neprocesate:</p>\n<ul>";
		
		## extrage fisierele care nu au fost procesate
		foreach($nr[0] as $val){
			
			$fisiere .= '<li>' . $val . "</li>\n";
		}
		
		$fisiere .= "</ul>\n";
		
		## proceseaza restul matricii
		## sterge cheia 0
		unset($nr[0]);
		
		## itereaza matricea
		foreach($nr as $key => $val){
			
			$file = 'tmp/output' . $key . '.csv';
			
			## verifica daca sunt valori
			if($val == 0){
				continue;
			} else {
				$string .= '<a href = "' . base_url($file) .
					'">WS cu ' . $key . " randuri</a><br />\n";
			}
		}
		
		return $string . $fisiere;
	}
	
}
