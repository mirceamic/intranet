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
		#$this->output->enable_profiler(TRUE);
	}
	
	public function index($page = 'magazin')
	{
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## initializeaza variabila pentru link-uri
		$data['generare'] = '';
		
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
				$data['tabel'] = $this->makeTabelScan('');
				
				break;
			
			## daca se adauga ean-uri
			case 'addean':
				
				## operatiunea de introducere a datelor in DB
				$eans = $this->input->post();
				## imparte string-ul intr-o matrice de valori
				$coduri = explode("\n", $eans['ean']);
				## scoate ultima valoare
				array_pop($coduri);
				## scoate caracterele nesemnificative "\n"
				$coduri = array_map('trim',$coduri);
				## desparte matricea
				## adauga un nivel in plus, pentru insert
				$coduri = array_chunk($coduri,1);
				
				## schimba cheia fiecarui element din matrice
				## pentru a rula "insert_batch" cu succes
				foreach($coduri as $key => $val){
					$coduri[$key]['ean'] = $coduri[$key][0];
					unset($coduri[$key][0]);
				}
				
				$this->db->insert_batch('mag_scan', $coduri);
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				## genereaza tabelul cu articolele scanate
				$data['tabel'] = $this->makeTabelScan('');
				
				break;
			
			## daca s-a resetat partea de scanare
			case 'reset':
				
				## resetarea tabelei de scanare
				$this->db->truncate('mag_scan');
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				## genereaza tabelul cu articolele scanate
				$data['tabel'] = $this->makeTabelScan('');
				
				break;
			
			## daca s-au generat fisierele de import
			case 'generare':
				
				$this->load->helper('file');
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				## genereaza fisierele de import impreuna cu tabelul cu articolele scanate
				## functia este asemanatoare cu makeTabelScan pentru ca aduce aceleasi date
				$data['tabel'] = $this->makeTabelScan('generare');
				
				## genereaza link-uri pentru fisierele generate
				$data['generare'] = '		<br />
		<div class = "linkuri">
			<a href = "' . base_url('tmp/nir.csv') . '">NIR</a><br />
			<a href = "' . base_url('tmp/etichete.txt') . '">Etichete</a>
		</div>';
				
				break;
			
			## daca se modifica preturile
			case 'modifica':
				
				## operatiunea de modificare a preturilor in DB
				
				## creaza matricea de date
				$mod = array(
					'pa' => $this->input->post('pa'),
					'pv' => $this->input->post('pv')
				);
				
				## stabileste conditia
				$where = 'cod like "' . $this->input->post('art') . '%"';
				
				## genereaza interogarea
				$sql = $this->db->update_string('mag_articole', $mod, $where);
				
				## ruleaza update-ul
				$this->db->query($sql);
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				## genereaza tabelul cu articolele scanate
				$data['tabel'] = $this->makeTabelScan('');
				
				break;
			
			## daca trebuie stearsa o linie
			case 'sterge':
				
				## operatiunea de stergere a articolului scanat
				$this->db->where('ean', $this->input->post('ean'));
				$this->db->delete('mag_scan');
				
				## revino la pagina de introducere a unui nou articol
				$page = 'scan';
				
				## genereaza formularul de introducere a EAN-urilor
				$data['string'] = $this->makeFormScan();
				## genereaza tabelul cu articolele scanate
				$data['tabel'] = $this->makeTabelScan('');
				
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
		#$sql = 'INSERT INTO intranet.mag_articole
		#	(ean, cod, c1_id, c2_id, c3_id, c6, c7, c8, pa, pv) VALUES';
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
			/*$sql .= '(' . $cod . ',
			"' . $codart . $nr . '",
			' . $data['departament'] . ',
			' . $data['grupa'] . ',
			' . $data['culoare'] . ',
			"' . $data['grupa'] . $data['grupa2'] . '",
			"' . $data['material'] . '",
			' . $data['culoare'] . ',
			' . $data['pa'] . ',
			' . $data['pv'] . '),';*/
			
			array_push($sqldata, array(
				'ean' => trim($cod),
				'cod' => strtoupper($codart) . strtoupper($nr),
				'c1_id' => $data['departament'],
				'c2_id' => $data['grupa'],
				'c3_id' => $data['culoare'],
				'c6' => strtoupper($data['grupa']) . strtoupper($data['grupa2']),
				'c7' => strtoupper($data['material']),
				'c8' => $data['culoare'],
				'pa' => $data['pa'],
				'pv' => $data['pv']
			));
			
			$nr++;
		}
		
		$this->db->insert_batch('mag_articole', $sqldata);
		
		#echo $this->db->last_query();
		#var_dump($sqldata);
		
		$strinit = '<p>S-a introdus articolul: ' . $codart . '</p>';
		
		return $strinit;
	}
	
	## functie pentru generearea form-ului de la scanare
	private function makeFormScan(){
		
		## div-ul general care va tine form-urile de sus
		$string = '	<div class = "forms">';
		
		## generarea form-ului pentru ean-uri
		$valori = array(
			'name'	=>	'ean',
			'rows'	=>	10,
			'cols'	=>	13,
			'id'	=>	'ean'
		);
		
		## codul pentru form-ul de scanare ean-uri
		$string .= '
		<div class = "eanform">';
		$string .= '
			' . form_open('index.php/magazin/index/addean');
		$string .= '				' . form_label('Scaneaza codurile EAN','ean');
		$string .= '
				' . form_textarea($valori);
		$string .= '				' . form_submit('scan','Incarca');
		$string .= '			' . form_close();
		
		## inchide div-ul ean si deschide div-ul pentru reset si generare
		$string .= '
		</div>
		<div class = "generare">';
		
		## genereaza form-ul pentru reset
		$string .= '
			' . form_open('index.php/magazin/index/reset');
		$string .= '				' . form_submit('reset','Reset');
		$string .= '			' . form_close();
		$string .= '<br />';
		
		## generarea form-ului pentru generarea fisierelor de import
		$string .= '
			' . form_open('index.php/magazin/index/generare');
		$string .= '				' . form_input('serie', 'AMFCT');
		$string .= '				' . form_input('nr');
		$string .= '				' . form_input('data', date("d.m.Y"));
		$string .= '				' . form_submit('generare','Genereaza');
		$string .= '			' . form_close();
		
		## nu se inchide div-ul, deoarece mai trebuiesc introduse link-urile
		## catre fisierele de import
		## div-ul se inchide in view (scan.php)
		
		return $string;
	}
	
	## functie pentru generarea tabelului cu articole scanate
	private function makeTabelScan($tip){
		
		## valorile initiale pentru variabilele de transport
		$header = '';
		$strdata = '';
		$patotal = 0;
		
		## daca se genereaza fisierele de import
		## initializeaza variabilele
		if($tip == 'generare'){
			$nir = './tmp/nir.csv';
			$strnir = '';
			$etichete = './tmp/etichete.txt';
			$stretichete = '';
			## despartitorul pentru csv
			$desp = ';';
		}
		
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
		
		## interogheaza DB-ul
		$qry = $this->db->query($sql);
		
		## verifica daca exista valori
		if($qry->num_rows() != 0){
			
			## variabila care transporta datele tabelului
			$strdata = '
	<div class = "tabelData">';
			
			## extrage datele si construieste tabelul
			foreach($qry->result() as $row){
				
				$strdata .= "\n" . '		<div class = "ean">' . $row->ean . '</div>
		<div class = "cod">' . $row->cod . '</div>
		<div class = "dep">' . $row->dept . '</div>
		<div class = "grp">&nbsp;' . $row->grupa . '&nbsp;</div>
		<div class = "cols">&nbsp;' . $row->culoare . '&nbsp;</div>
		<div class = "art">&nbsp;' . $row->c6 . '&nbsp;</div>
		<div class = "mat">&nbsp;' . $row->c7 . '&nbsp;</div>
		<div class = "col">&nbsp;' . $row->c8 . '&nbsp;</div>
		';
				
				## construieste cele 2 form-uri pentru schimbatul preturilor
				$strdata .= form_open('index.php/magazin/index/modifica');
				$strdata .= '			<div class = "pa">
				' . form_input('pa', $row->pa) . '			</div>
			<div class = "pv">
				' . form_input('pv', $row->pv) . '			</div>
			<div class = "nr">' . $row->nr . "</div>";
				$strdata .= '
			' . form_hidden('art', substr($row->cod,0,-4));
				$strdata .= '			' . form_submit('submit','Modifica', 'class = "buton"');
				$strdata .= '		' . form_close();
				## adauga un buton de stergere a liniei
				$strdata .= '
		' . form_open('index.php/magazin/index/sterge');
				$strdata .= '			' . form_hidden('ean', $row->ean);
				$strdata .= '			' . form_submit('submit','Sterge', 'class = "buton"');
				$strdata .= '		' . form_close();
				
				$strdata .= '
		<br class = "break" />';
				
				## afla suma de achizitie
				#$patotal = $patotal + intval($row->pa) * intval($row->nr);
				$patotal = $patotal + $row->pa * $row->nr;
			
				if($tip == 'generare'){
					## construieste string-urile pentru fisiere
					$strnir .= $this->input->post('serie') . $desp .
						$this->input->post('nr') . $desp .
						$this->input->post('data') . $desp .
						$row->cod . $desp . $desp .
						substr($row->cod,0,-4) . $desp . $desp .
						$row->ean . $desp .
						$row->dept . $desp .
						$row->grupa . $desp .
						$row->culoare . $desp .
						substr($row->cod,-2,2) . $desp .
						substr($row->cod,-3,1) . $desp .
						$row->c6 . $desp .
						$row->c7 . $desp .
						$row->c8 . $desp . $desp. $desp .
						$row->nr . $desp .
						$row->pa . $desp .
						$row->pv . $desp . $desp . "PER\n";
					
					## scrie eticheta pentru fiecare pereche de pantofi
					for($i = 1; $i <= $row->nr; $i++){
						$stretichete .= '^XA^FO20,20^ARN,52,20^FD' .
							substr($row->cod,0,-4) .
							"^FS\n^FO20,60^ARN,52,20^FD" .
							substr($row->cod,-2,2) .
							"^FS\n^FO70,60^ARN,36,20^FD" .
							$row->c8 .
							"^FS\n^FO140,60^ARN,36,20^FD" .
							$row->culoare .
							"^FS\n^FO250,110^ARN,88,35^FD" .
							$row->pv .
							" RON^FS\n^FO30,110^BY2^BEN,50,Y,N^FD" .
							$row->ean . "^BY^FS^XZ\n\n";
					}
				}
			}
			
			$strdata .= "\n	</div>\n";
			
			## afla numarul total de articole
			$sql = 'select count(id) as nr from mag_scan';
			$qry = $this->db->query($sql);
			$count = $qry->first_row();
			
			## capul de tabel
			$header = '
	<div class = "tabelHead">
		<div class = "ean">EAN</div>
		<div class = "cod">Cod</div>
		<div class = "dep">Dep</div>
		<div class = "grp">Grupa</div>
		<div class = "cols">Culoare</div>
		<div class = "art">Art</div>
		<div class = "mat">Mat</div>
		<div class = "col">Col</div>
		<div class = "pa">PA (' . number_format($patotal,2,",",".") . ')</div>
		<div class = "pv">PV</div>
		<div class = "nr">Nr (' . $count->nr . ')</div>
	</div>' . "\n	<br class = \"break\" />";
			
			if($tip == 'generare'){
				## scrie fisierele
				write_file($nir, $strnir);
				write_file($etichete, $stretichete);
				
			}
		}
		
		return $header . $strdata;
	}
	
	
}
