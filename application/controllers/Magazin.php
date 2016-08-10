<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Magazin extends CI_Controller {
	
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
	
	public function index($page = 'magazin')
	{
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## in functie de numele paginii alese, se vor rula anumite functii
		## rezultand anumite date
		switch($page){
			
			## daca s-a accesat prima pagina
			case 'magazin':
				## genereaza link-urile
				$data['linkuri'] = array(
					'Adauga articole noi' => base_url('index.php/magazin/index/adauga'),
					'Scaneaza marfa' => base_url('index.php/magazin/index/scan')
				);
				break;
			
			## daca se acceseaza pagina de adaugare articole noi
			case 'adauga':
				
				## genereaza formularul de introducere a noului articol
				$data['string'] = $this->makeFormAdd('');
				break;
			
			## daca s-a introdus articol nou
			case 'add':
				
				## proceseaza si introdu datele in DB
				$x = $this->insertArticolNou($this->input->post());
				
				## revino la pagina de introducere a unui nou articol
				$page = 'adauga';
				
				## genereaza formularul de introducere a noului articol
				$data['string'] = $this->makeFormAdd($x);
				
				break;
			
			## daca se acceseaza pagina de scanare marfa
			case 'scan':
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				
				## genereaza tabelul cu articolele scanate
				$data['tabel'] = $this->makeTabelScan();
				
				break;
			
			## daca se adauga ean-uri
			case 'addean':
				
				## operatiunea de introducere a datelor in DB
				
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				
				break;
			
			## daca s-a resetat partea de scanare
			case 'reset':
				
				## resetarea tabelei de scanare
				
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				
				break;
			
			## daca s-au generat fisierele de import
			case 'generare':
				
				## generarea fisierului de import
				
				## generarea fisierului pentru etichete
				
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				
				break;
			
			
		}
		
		## verifica aici daca exista pagina, deoarece unele metode depind de alte pagini
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/magazin/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste numele paginii care trebuie incarcata
		## toate paginile acestei clase sunt intr-un director separat
		$fpage = 'magazin/' . $page;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
	## functie de generare a form-ului pentru adaugarea de articole noi
	private function makeFormAdd($init){
		
		$string = '<div class = "addform">';
		
		## introdu textul initial
		$string .= $init;
		
		## incepe form-ul
		$string .= form_open('index.php/magazin/index/add');
		
		## matricea de valori pentru elementele form-ului
		$valori = array(
			'name'	=>	'ean',
			'rows'	=>	10,
			'cols'	=>	13,
			'id'	=>	'ean'
		);
		
		## primul div -> codurile ean
		$string .= '<div>';
		
		## adauga textarea pentru codurile EAN
		$string .= form_label('Scaneaza codurile EAN in ordine','ean');
		$string .= form_textarea($valori);
		$string .= form_label('Prima marime','marime');
		$string .= form_input('marime','','id = "marime"');
		
		## al doilea div pentru celelalte campuri ale articolului
		$string .= '</div><div>';
		
		## adu lista cu departamente si creaza un dropdown
		$x = array();
		$sql = 'select id, denumire from mag_departamente';
		$qry = $this->db->query($sql);
		foreach($qry->result() as $row){
			$x[$row->id] = $row->denumire;
		}
		
		$string .= form_dropdown('departament', $x);
		
		## adauga elementele articolului
		$string .= form_label('Grupa','grupa');
		$string .= form_input('grupa','','id = "grupa"');
		$string .= form_label('Stil','grupa2');
		$string .= form_input('grupa2','','id = "grupa2"');
		$string .= form_label('Material','material');
		$string .= form_input('material','','id = "material"');
		$string .= form_label('Cod culoare','culoare');
		$string .= form_input('culoare','','id = "culoare"');
		$string .= form_label('Pret achizitie','pa');
		$string .= form_input('pa','','id = "pa"');
		$string .= form_label('Pret Vanzare','pv');
		$string .= form_input('pv','','id = "pv"');
		
		## inchide div-ul elementelor articolului, si form-ul
		$string .= '</div>';
		$string .= '</div><br style = "clear: left;" />';
		$string .= form_submit('insert','Adauga');
		$string .= form_close();
		$string .= '</div><br style = "clear: left;" />';
		
		return $string;
	}
	
	## functie pentru introducerea unui articol nou in DB
	private function insertArticolNou($data){
		
		## transforma string-ul de EAN-uri in matrice
		$ean = explode("\n", $data['ean']);
		array_pop($ean);
		
		## construieste inceputul sql-ulului de introdus datele in DB
		$sql = 'INSERT INTO intranet.mag_articole
			(ean, cod, c1_id, c2_id, c3_id, c6, c7, c8, pa, pv) VALUES';
		$sqldata = array();
		
		## afla daca e H sau D
		$h = array(1,4,6,7);
		$d = array(2,5,8,10);
		
		if(in_array(intval($data['departament']),$h)){
			$tip = 'H';
		}elseif(in_array(intval($data['departament']),$d)){
			$tip = 'D';
		}else{
			$tip = '';
		}
		
		## construieste denumirea articolului
		$codart = $data['grupa'] .
			$data['grupa2'] . '-' .
			$data['material'] . '-' .
			$data['culoare'] . '-' .
			$tip;
		
		## afla prima marime a articolului
		$nr = intval($data['marime']);
		
		## construieste sql-ul pentru fiecare ean
		foreach($ean as $cod){
			$sql .= '(' . $cod . ',
			"' . $codart . $nr . '",
			' . $data['departament'] . ',
			' . $data['grupa'] . ',
			' . $data['culoare'] . ',
			"' . $data['grupa'] . $data['grupa2'] . '",
			"' . $data['material'] . '",
			' . $data['culoare'] . ',
			' . $data['pa'] . ',
			' . $data['pv'] . '),';
			
			array_push($sqldata, array(
				'ean' => trim($cod),
				'cod' => $codart . $nr,
				'c1_id' => $data['departament'],
				'c2_id' => $data['grupa'],
				'c3_id' => $data['culoare'],
				'c6' => $data['grupa'] . $data['grupa2'],
				'c7' => $data['material'],
				'c8' => $data['culoare'],
				'pa' => $data['pa'],
				'pv' => $data['pv']
			));
			
			$nr++;
		}
		
		#$this->db->set($sqldata);
		$this->db->insert_batch('mag_art', $sqldata);
		#var_dump($sqldata);
		
		$strinit = '<p>S-a introdus articolul: ' . $codart . '</p>';
		
		return $strinit;
	}
	
	## functie pentru generearea form-ului de la scanare
	private function makeFormScan(){
		
		## div-ul general care va tine form-urile de sus
		$string = '<div class = "forms">';
		
		## generarea form-ului pentru ean-uri
		$valori = array(
			'name'	=>	'ean',
			'rows'	=>	10,
			'cols'	=>	13,
			'id'	=>	'ean'
		);
		
		$string .= '<div class = "eanform">';
		$string .= form_open('index.php/magazin/index/addean');
		$string .= form_label('Scaneaza codurile EAN','ean');
		$string .= form_textarea($valori);
		$string .= form_submit('scan','Incarca');
		$string .= form_close();
		
		## inchide div-ul ean si deschide div-ul pentru reset si generare
		$string .= '</div><div class = "generare">';
		
		## genereaza form-ul pentru reset
		$string .= form_open('index.php/magazin/index/reset');
		$string .= form_submit('reset','Reset');
		$string .= form_close();
		$string .= '<br />';
		
		## generarea form-ului pentru generarea fisierelor de import
		$string .= form_open('index.php/magazin/index/generare');
		$string .= form_input('serie', 'AMFCT');
		$string .= form_input('nr');
		$string .= form_input('data', date("d.m.Y"));
		$string .= form_submit('generare','Genereaza');
		$string .= form_close();
		
		
		return $string;
	}
	
	## functie pentru generarea tabelului cu articole scanate
	private function makeTabelScan(){
		
		## construieste sql-ul de interogat
		$sql = 'select mag_scan.ean,
			mag_articole.cod,
			mag_articole.c1_id,
			mag_departamente.denumire as dept,
			mag_articole.c2_id,
			mag_grupe.denumire as grupa,
			mag_articole.c3_id,
			mag_culori.nume_ro as culoare,
			mag_articole.c6,
			mag_articole.c7,
			mag_articole.c8,
			mag_articole.pa,
			mag_articole.pv,
			count(mag_scan.id) as nr
		from mag_articole
		right join mag_scan
			on mag_articole.ean = mag_scan.ean
		left join mag_departamente
			on mag_articole.c1_id = mag_departamente.id
		left join mag_grupe
			on mag_articole.c2_id = mag_grupe.cod
		left join mag_culori
			on mag_articole.c3_id = mag_culori.id
		group by mag_scan.ean
		order by mag_articole.cod';
		
		$string = '<div class = "tabelHead">
			<div class = "ean">EAN</div>
			<div class = "cod">Cod</div>
			<div class = "dep">Dep</div>
			<div class = "grp">Grupa</div>
			<div class = "cols">Culoare</div>
			<div class = "art">Art</div>
			<div class = "mat">Mat</div>
			<div class = "col">Col</div>
			<div class = "pa">PA</div>
			<div class = "pv">PV</div>
			<div class = "nr">Nr</div>
		</div>';
		
		## interogheaza DB-ul
		$qry = $this->db->query($sql);
		
		foreach($qry->result() as $row){
			
		}
	}
	
}
