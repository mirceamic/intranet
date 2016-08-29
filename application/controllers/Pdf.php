<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf extends CI_Controller {
	
	## defineste dimensiunile pentru tabelul pdf
	private static $dimensiuni = array(
		'pdf_tmpl1' => array(
			'nr' => 10,
			'art' => 30,
			'mat' => 15,
			'col' => 15,
			'color' => 45,
			'rrp' => 24,
			'price' => 18,
			'delivery' => 24,
			'country' => 20,
			'img' => 80
		),
		'pdf_tmpl2' => array(
			'nr' => 10,
			'art' => 30,
			'mat' => 15,
			'col' => 15,
			'color' => 45,
			'rrp' => 24,
			'price' => 18,
			'delivery' => 24,
			'country' => 20,
			'img' => 80
		),
		'pdf_tmpl3' => array(
			'nr' => 10,
			'art' => 30,
			'mat' => 20,
			'col' => 20,
			'color' => 50,
			'country' => 30,
			'img' => 80
		),
		'pdf_tmpl4' => array(
			'nr' => 10,
			'art' => 30,
			'mat' => 15,
			'col' => 15,
			'assortment' => 39,
			'rrp' => 24,
			'price' => 18,
			'delivery' => 24,
			'pairs' => 20,
			'img' => 80
		),
		'pdf_tmpl5' => array(
			'nr' => 10,
			'art' => 28,
			'mat' => 15,
			'col' => 15,
			'grupa' => 40,
			'rrp' => 20,
			'price' => 20,
			'delivery' => 24,
			'country' => 30,
			'img' => 80
		)
	);
	
	## defineste pozitia de start a imaginii
	private static $imgStart = array(
		'pdf_tmpl1' => array(
			'start' => 225
		),
		'pdf_tmpl2' => array(
			'start' => 225
		),
		'pdf_tmpl3' => array(
			'start' => 185
		),
		'pdf_tmpl4' => array(
			'start' => 220
		),
		'pdf_tmpl5' => array(
			'start' => 227
		)
	);
	
	public function __construct(){
		parent::__construct();
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('pdf', $this->session->chkMenu)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
	}
	
	public function index($page = 'pdf'){
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/pdf/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## verifica daca e montat directorul cu poze
		$data['mount'] = $this->check_mount('generic.jpg');
		
		## construieste formularele pentru fiecare "template" de pdf
		
		/*
		 * Template 1
		 */
			
			## dimensiunile coloanelor
			$cols = array(
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Col',
				'color' => 'Color',
				'rrp' => 'RRP',
				'price' => 'Price',
				'delivery' => 'Delivery',
				'country' => 'Country'
			);
			
			## genereaza formularul
			$data['formular'][0] = $this->make_formular($cols, 'pdf_tmpl1');
		
		/* T1 */
		
		/*
		 * Template 1 in germana
		 */
			
			## dimensiunile coloanelor
			$cols = array(
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Frb',
				'color' => 'Farbe',
				'rrp' => 'RRP',
				'price' => 'Price',
				'delivery' => 'Delivery',
				'country' => 'Land'
			);
			
			## genereaza formularul
			$data['formular'][1] = $this->make_formular($cols, 'pdf_tmpl2');
		
		/* T1 */
		
		/*
		 * Template 2
		 */
		
			## matricea coloanelor
			$cols = array(
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Col',
				'color' => 'Color',
				'country' => 'Country'
			);
		
			## genereaza formularul
			$data['formular'][2] = $this->make_formular($cols, 'pdf_tmpl3');
			
		/* T2 */
		
		/*
		 * Template 3
		 */
		
			## matricea coloanelor
			$cols = array(
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Col',
				'assortment' => 'Assortment',
				'rrp' => 'RRP',
				'price' => 'Price',
				'delivery' => 'Delivery',
				'pairs' => 'Pairs'
			);
		
			## genereaza formularul
			$data['formular'][3] = $this->make_formular($cols, 'pdf_tmpl4');
			
		/* T3 */
		
		/*
		 * Template 4
		 */
		
			## matricea coloanelor
			$cols = array(
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Col',
				'grupa' => 'Grupa',
				'rrp' => 'RRP',
				'price' => 'Price',
				'delivery' => 'Delivery',
				'country' => 'Country'
			);
		
			## genereaza formularul
			$data['formular'][4] = $this->make_formular($cols, 'pdf_tmpl5');
			
		/* T4 */
		
		## contruieste calea intreaga a paginii de incarcat
		$fpage = 'pdf/' . $page;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage);
		$this->load->view('footer');
		
	}
	
	## functia de adaugare a fisierului pentru oferta pdf
	public function add($page){
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/pdf/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## extrage datele trimise si afiseaza-le
		$tmp = $this->makeTabel($this->input->post(), $_FILES, $page);
		$data['valori'] = $tmp['string'];
		
		## verifica daca trebuie generat fisierul pdf
		if($tmp['check'] == 0){
		
			## genereaza fisierul PDF
			$funic = $this->makePDF($page);
			
			## afiseaza link-ul catre fisierul generat
			$data['link'] =  '<a href = "' . base_url($funic) . '">Fisierul PDF generat</a>';
			
		} elseif($tmp['check'] == 1){
			
			$data['link'] = '';
		}
		
		## contruieste calea intreaga a paginii de incarcat
		$fpage = 'pdf/' . $page;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage, $data);
		$this->load->view('footer');
	}
	
	## Functii private ##
	
	## functie pentru generarea pdf-ului
	private function makePDF($page){
		
		/*
		 * Extrage dimensiunile celulelor din PDF
		 * 
		 */
		
		$dims = self::$dimensiuni[$page];
		
		## constante pentru dimensionarea elementelor in PDF
		## distanta fata de marginea stanga, de unde incepe imaginea
		$x = self::$imgStart[$page]['start'];
		## distanta fata de marginea de sus, de unde incep elementele
		$y = -24;
		## inaltimea elementelor
		$z = 44;
		## inaltimea x lungimea imaginii
		$wh = 52;
		
		## numarul de ordine
		$no = 1;
		
		## calea catre imagine
		$imgPath = '/mnt/pdf/';
		
		/*
		 * Incarca libraria FPDF
		 * 
		 */
		
		## definestea calea catre font-urile fpdf
		define('FPDF_FONTPATH',$this->config->item('fonts_path'));
		
		## incarca libraria fpdf
		#$this->load->library(array('fpdf','fpdf_rotate','pdf'));
		$this->load->library(array('fpdf'));
		
		/*
		 * Construieste datele pentru SELECT
		 * 
		 * string-ul de coloane care trebuiesc aduse din DB
		 * 
		 */
		
		## daca e template-ul lui Vasile
		if($page == 'pdf_tmpl5'){
			$template = array(
				'grupa' => 'Grupa',
				'art' => 'Art',
				'mat' => 'Mat',
				'col' => 'Col',
				'rrp' => 'RRP',
				'price' => 'Price',
				'delivery' => 'Delivery',
				'country' => 'Country'
			);
		
		## daca e template normal
		} else {
			$template = $this->input->post('coloane');
		}
		
		$strSelect = '';
		
		foreach($template as $key => $val){
			
			$strSelect .= $key . ',';
			
		}
		
		## adauga imaginea
		$strSelect .= 'img';
		
		/*
		 * end strSelect
		 */
		
		## numarul de coloane din template
		$nrcols = max(array_keys($template));
		
		/*
		 * extrage datele din DB
		 * in timpul extragerii datelor, construieste PDF-ul
		 * 
		 */
		
		$pdf = new FPDF('L');
		$pdf->AliasNbPages();
		$pdf->SetFont('Arial','B',10);
		## test
		$pdf->SetAutoPageBreak(false);
		
		## construieste select-ul
		$this->db->select($strSelect);
		
		## ruleaza interogarea
		$qry = $this->db->get($page);
		
		## porneste contorul pentru elementele de pe o pagina
		$nrElemente = 1;
		
		## initializeaza matricea de transport a setului unei pagini
		$set = array();
		
		## extrage datele
		foreach($qry->result() as $row){
			
			## la primul element creaza o pagina noua si scrie header-ul
			if($nrElemente == 1){
				
				## adauga pagina noua
				$pdf->AddPage();
				
				## construieste capul de tabel
				## incepe cu numarul de ordine
				$pdf->Cell($dims['nr'],7,'No.',1,0,'C');
				
				## pentru fiecare element din template, "deseneaza" capul de tabel
				foreach($template as $key => $val){
					$pdf->Cell($dims[$key],7,$val,1,0,'C');
				}
				
				## "deseneaza" chenarul pentru imagine
				$pdf->Cell($dims['img'],7,'Image',1,0,'C');
				
				## treci la linia urmatoare
				$pdf->Ln();
				
				## initializeaza contoarele paginii
				## distanta fata de marginea de sus, de unde incep elementele
				$y = -24;
				
			}
			
		## \/ scrie pdf-ul
			
			## ia fiecare element al liniei si "deseneaza-l"
			## stabileste punctul de referinta pentru imaginea urmatoare
			$y = $y + $z;
			
			## scrie prima coloana
			$pdf->Cell($dims['nr'],$z,$no,1,0,'C');
			
			## scrie restul de coloane
			foreach($template as $key => $val){
				$pdf->Cell($dims[$key],$z,$row->$key,1,0,'C');
			}
			
			## scrie chenarul imaginii
			$pdf->Cell($dims['img'],$z,'',1);
			
			## construieste calea catre imagine
			$imgPath .= $row->img;
			
			## scrie imaginea in chenarul creat
			$pdf->Image($imgPath,$x,$y,$wh);
			
			## treci la o linie noua
			$pdf->Ln();
			
		## /\ scrie pdf-ul
			
			## cand ajunge la ultimul element din pagina (4), reseteaza contorul
			if($nrElemente == 4){
				$nrElemente = 0;
			}
			
			## incrementeaza numarul liniei
			$no++;
			## incrementeaza contorul
			$nrElemente++;
			## reseteaza calea imaginii
			$imgPath = '/mnt/pdf/';
			
		}
		
		## genereaza un nume de fisier unic 'doc' . luna . data . minut . secunda
		$unic = date('j') . date('H') . date('i') . date('s');
		
		## calea fisierului
		$funic = 'tmp/document' . $unic . '.pdf';
		
		## scrie si afiseaza fisierul
		$pdf->Output($funic, 'F');
		
		return $funic;
		
	}
	
	## functie care verifica daca e montat directorul cu poze
	private function check_mount($fname){
		
		## construieste toata calea
		$cale = '/mnt/pdf/' . $fname;
		$string = '';
		
		## verifica daca exista imaginea generica
		if(file_exists($cale)){
			if($fname == 'generic.jpg'){
				$string .= '		<p>Status poze: OK</p>';
			} else {
				$string .= 'OK';
			}
		} else {
			if($fname == 'generic.jpg'){
				$string .= '		<p>Status poze: eroare (ia legatura cu autorul)</p>';
			} else {
				$string .= $fname;
			}
		}
		
		return $string;
	}
	
	## functie pentru construirea unui formular de generare a pdf-ului
	private function make_formular($cols, $fname){
		
		$string = '		<br />
		<div>
			<div class = "w3-container w3-pale-red ctnr">Nr</div>' . "\n";
			#<div class = "w3-container w3-light-grey ctnr">Nr</div>' . "\n";
		
		## construieste un div cu valorile capului de tabel final
		foreach($cols as $col => $val){
			$string .= "\t\t\t" . '<div class = "w3-container ct' . $col .
				'">' . $val . "</div>\n";
		}
		
		$string .= "\t\t\t" . '<div class = "w3-container w3-pale-red ctimage">Image</div>' . "\n";
		
		$string .= "\t\t" . '</div>
		<br style = "clear: left;" /><br />' . "\n";
		
		## genereaza form-ul care duce la generearea acestui template
		$link = 'index.php/pdf/add/' . $fname;
		$string .= "\t\t" . form_open_multipart($link);
		$string .= form_upload($fname);
		$string .= form_hidden('coloane', $cols);
		$string .= form_submit('submit', 'Incarca');
		$string .= "\t\t" . form_close();
		#$string .= "\n\t\t<hr>\n";
		
		return $string;
	}
	
	## functie pentru procesarea datelor din fisierul importat
	private function makeTabel($post, $file, $page){
		
		/**
		 ** Constante
		 **/
		
		## initializeaza matricea de transport
		$x = array();
		## initial, variabila de verificare e 0
		$x['check'] = 0;
		
		## initializeaza matricea de valori
		$matrice = array();
		
		## initializeaza contorul pentru numarul de linii
		$nrline = 0;
		
		## atribuie o variabila pentru numarul de coloane din template
		$nrcols = count(array_keys($post['coloane']));
		
		## string-ul de transport
		$string = "\t<p>Verifica datele introduse</p>\n\t" .
		'<div class = "cthead">
		<div class = "w3-container ctnr">Nr</div>' . "\n";
		
		/**
		 ** Procesari
		 **/
		
		## extrage coloanele si construieste capul de tabel
		foreach($post['coloane'] as $key => $nume){
			
			$string .= "\t\t" . '<div class = "w3-container ct' . $key .
				'">' . $nume . "</div>\n";
		}
		
		## coloana de imagine
			$string .= "\t\t" . '<div class = "w3-container ctimage">Image' . "</div>\n";
		
		## sfarsitul capului de tabel
		$string .= "\t</div>\n";
		
		## afla despartitorul CSV
		$file = fopen($_FILES[$page]["tmp_name"], "r");
		$linie = fgets($file);
		$vg = substr_count($linie,',');
		$pv = substr_count($linie,';');
		if($vg > $pv){
			$despartitor = ',';
		} else {
			$despartitor = ';';
		}
		
		## incepe div-ul pentru datele tabelului
		$string .= "\t" . '<div class = "ctdata">' . "\n";
		
		## citeste fisierul incarcat
		$file = fopen($_FILES[$page]["tmp_name"], "r");
		
		## citeste fiecare linie
		while($line = fgetcsv($file, 1024, $despartitor)){
			
			## verifica daca linia contine toate coloanele necesare
			if($nrcols > max(array_keys($line))){
				
				## daca numarul de coloane este mai mare decat numarul de date introduse (din csv)
				## coloane template > coloane csv
				$string .= "\t\t" . '<div class = "w3-container ctgol">date incomplete</div>
		<br style = "clear: left;" />' . "\n";
				
				## coloane template <= coloane csv
			} else {
				
				## itereaza numarul liniei
				$nrline++;
				
				## scrie prima coloana - numarul curent
				$string .= "\t\t" . '<div class = "w3-container ctnr">' . $nrline . "</div>\n";
				
				## initializeaza matricea liniei
				$matrice[$nrline] = array();
				
				## id-ul liniei
				$id = 0;
				
				## treci prin matricea $linie doar cate coloane are template-ul
				foreach($post['coloane'] as $key => $val){
					
					$string .= "\t\t" . '<div class = "w3-container ct' . $key .
						'">&nbsp;' . $line[$id] . "</div>\n";
					
					## impinge valorile coloanelor in matricea valorilor
					$matrice[$nrline][$key] = $line[$id];
					
					$id++;
				}
				
				## construieste numele fisierului de imagine
				$fname = $line[0] . $line[1] . '-' . $line[2] . '.jpg';
				
				## verifica daca fisierul exista
				$fileCheck = $this->check_mount($fname);
				
				## construieste coloana de imagine
				$string .= "\t\t" . '<div class = "w3-container ctimage">' . $fileCheck . "</div>\n";
				
				## daca nu exista fisier pentru imagine
				## pune imaginea alba
				if($fileCheck === 'OK'){
					$imgFile = $fname;
				} else {
					$imgFile = 'generic.jpg';
				}
				
				## impinge valoarea imaginii in matricea valorilor
				#array_push($matrice[$nrline], $imgFile);
				$matrice[$nrline]['img'] = $imgFile;
				
				## treci la randul urmator
				$string .= "\t\t" . '<br style = "clear: left;" />' . "\n";
				
			}
		}
		
		## inchide div-ul de date
		$string .= "\t</div>";
		
		$x['string'] = $string;
		
		## goleste tabela
		$this->db->truncate($page);
		
		## daca toate valorile din csv nu corespund cu template-ul ales
		## nr coloane csv < nr coloane template
		## => $matrice fara valori => eroare la introducerea datelor in DB
		if(empty($matrice)){
			
			## scrie un mesaj cum ca fisierul csv nu e compatibil cu template-ul ales
			$string = '<p>Fisierul incarcat nu contine destule coloane, confrom modelului ales</p>';
			
			## modifica variabila de verificare
			$x['check'] = 1;
			
		} else {
			
			## scrie valorile din fisier, in baza de date
			$this->db->insert_batch($page, $matrice);
			
		}
		
		$x['string'] = $string;
		
		return $x;
	}
	
	
}
