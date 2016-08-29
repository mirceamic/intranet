<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('admin', $this->session->chkMenu)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
	}
	
	public function index($page = 'admin')
	{
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## adu lista cu utilizatorii
		$data['utilizatori'] = $this->get_UserList();
		
		## verifica daca s-a ales vre-un utilizator
		if(!is_null($this->input->post('users'))){
			$data['infouser'] = $this->get_User($this->input->post('users'));
		} else {
			$data['infouser'] = '';
		}
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## functie pentru modificarea datelor unui utilizator
	public function modUser(){
		
		/*
		 * Fa modificarile
		 */
		
		## construieste valoarea pentru meniuri
		$strMeniuri = '';
		
		foreach($this->input->post('meniuri') as $val){
			$strMeniuri .= $val . ",";
		}
		
		## sterge ultima virgula
		$strMeniuri = rtrim($strMeniuri, ',');
		
		## verifica valoarea campului "inactiv"
		if(array_key_exists('inactiv', $this->input->post())){
			$inactiv = 1;
		} else {
			$inactiv = 0;
		}
		
		
		## construieste matricea de campuri care trebuiesc modificate
		$modificari = array(
			'marca' => $this->input->post('marca'),
			'id_pontaj' => $this->input->post('idpontaj'),
			'cod_pontator' => $this->input->post('codpontaj'),
			'nume' => $this->input->post('nume'),
			'prenume' => $this->input->post('prenume'),
			'user' => $this->input->post('user'),
			'mac' => $this->input->post('mac'),
			'inceput' => $this->input->post('inceput'),
			'sfarsit' => $this->input->post('sfarsit'),
			'inactiv' => $inactiv,
			'meniuri' => $strMeniuri
		);
		
		$this->db->where('id', $this->input->post('id'));
		$this->db->update('glb_angajati', $modificari);
		
		/*
		 * Afiseaza pagina cu modificarile
		 */
		
		## stabileste titlul paginii afisate
		$data['title'] = 'Utilizator modificat'; // Capitalize the first letter
		
		## adu lista cu utilizatorii
		$data['utilizatori'] = $this->get_UserList();
		
		## verifica daca s-a ales vre-un utilizator
		$data['infouser'] = $this->get_User($this->input->post('users'));
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view('admin');
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
	## functie pentru aducerea unei liste de angajati
	private function get_UserList(){
		
		## matricea de transport a valorilor aduse din DB
		$data['utilizatori'] = array();
		$data['inactivi'] = array();
		
		## sql-ul pentru interogarea bazei de date
		$sql = 'SELECT id,
			nume,
			prenume,
			inactiv
		FROM glb_angajati
		ORDER BY inactiv,
			nume,
			prenume';
		
		## ruleaza interogarea
		$rez = $this->db->query($sql);
		
		$x = 0;
		
		## extrage si prelucreaza datele
		foreach($rez->result() as $row){
			
			## verifica trecerea de la activi la inactivi
			if($x == $row->inactiv){
				$data['utilizatori'][$row->id] = $row->nume . " " . $row->prenume;
				$data['inactivi'][$row->id] = $row->inactiv;
				
				$x = $row->inactiv;
			} else {
				$data['utilizatori'][0] = " ";
				$data['inactivi'][0] = 0;
				
				$x = $row->inactiv;
			}
		}
		
		return $data;
	}
	
	## functie pentru aducerea informatiilor despre utilizator
	private function get_User($id){
		
		$string = form_open('index.php/admin/modUser');
		
		## sql-ul pentru aducerea datelor din DB
		$sql = 'SELECT * FROM glb_angajati WHERE id = ?';
		
		## interogheaza baza de date
		$qry = $this->db->query($sql, $id);
		
		## prelucreaza datele
		$row = $qry->result();
		
		## construieste campurile form-ului
		## matricea de proprietati
		$props = array(
			#'class' => 'w3-input w3-border w3-hover-teal'
			'class' => 'w3-input w3-border'
		);
		$labels = array(
			'class' => 'w3-label w3-text-teal'
		);
		
		## creaza un div general pentru a putea aranja
		## campul "meniuri" in dreapta acestui div;
		## sa nu strice aranjarea campurilor unidimensionale
		$string .= '<div class = "form">';
		## id (hidden)
		$string .= form_hidden('id', $row[0]->id);
		## users (hidden), pentru a reveni la aceeasi fereastra
		$string .= form_hidden('users', $row[0]->id);
		## marca
		## creaza un div pentru aranjarea fiecarui element
		$string .= '<div class = "formular">' . "\n";
		$props['style'] = 'width:50px';
		$string .= form_label('Marca', 'marca', $labels) . "\n";
		$string .= form_input('marca', $row[0]->marca, $props);
		$string .= "</div>\n";
		
		## id_pontaj (id-ul din baza de date a pontatorului)
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('ID pontaj', 'idpontaj', $labels) . "\n";
		$string .= form_input('idpontaj', $row[0]->id_pontaj, $props);
		$string .= "</div>\n";
		
		## cod_pontator (codul din pontator)
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Cod pontator', 'codpontaj', $labels) . "\n";
		$string .= form_input('codpontaj', $row[0]->cod_pontator, $props);
		$string .= '</div><br style = "clear:left;"/><br />' . "\n";
		
		## nume
		$props['style'] = 'width:170px';
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Nume', 'nume', $labels) . "\n";
		$string .= form_input('nume', $row[0]->nume, $props);
		$string .= "</div>\n";
		
		## prenume
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Prenume', 'prenume', $labels) . "\n";
		$string .= form_input('prenume', $row[0]->prenume, $props);
		$string .= "</div>\n";
		
		## utilizator
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Nume de utilizator (e-mail)', 'user', $labels) . "\n";
		$string .= form_input('user', $row[0]->user, $props);
		$string .= '</div><br style = "clear:left;"/><br />' . "\n";
		
		## adresa mac
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Adresa MAC', 'mac', $labels) . "\n";
		$string .= form_input('mac', $row[0]->mac, $props);
		$string .= "</div>\n";
		
		## data inceput
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Data angajare', 'inceput', $labels) . "\n";
		$string .= form_input('inceput', $row[0]->inceput, $props);
		$string .= "</div>\n";
		
		## data sfarsit
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Data incheiere CIM', 'sfarsit', $labels) . "\n";
		$string .= form_input('sfarsit', $row[0]->sfarsit, $props);
		$string .= '</div><br style = "clear:left;"/><br />' . "\n";
		
		## butoanele checkbox pentru drepturi
		## checkbox-ul pentru activ/inactiv
		$string .= '<div class = "formchk">' . "\n";
		## contruieste label-ul
		$valoare = $row[0]->inactiv;
		$string .= form_label('Inactiv', 'inactiv', $labels) . "\n";
		$string .= form_checkbox('inactiv', 1, $valoare);
		$string .= "</div>\n";
		
		
		## matricea cu numele checkbox-urilor necesare
/*		$chbox = array(
			'inactiv',
			'financiar',
			'pontaj',
			'mk',
			'l121',
			'rapoarte',
			'aprobator',
			'pdf',
			'admin'
		);
		
		## construieste un loop pentru fiecare checkbox
		foreach($chbox as $val){
			$string .= '<div class = "formchk">' . "\n";
			## contruieste label-ul
			$lbl = ucfirst($val);
			$valoare = $row[0]->{$val};
			$string .= form_label($lbl, $val, $labels) . "\n";
			$string .= form_checkbox($val, 1, $valoare);
			$string .= "</div>\n";
		}
*/		
		## inchide div-ul "form"
		$string .= "</div>\n";
		## meniuri
		## defineste numarul de optiuni vizibile (ideal = toate)
		$props['size'] = 8;
		$meniuri = $this->get_multiMeniu($row[0]->meniuri);
		#$meniuri = array($row[0]->meniuri);
		$string .= '<div class = "formular">' . "\n";
		$string .= form_label('Meniuri', 'meniuri', $labels) . "\n";
		$string .= form_multiselect('meniuri[]', $meniuri['opt'], $meniuri['select'], $props);
		$string .= '</div><br style = "clear:left;"/><br />' . "\n";
		
		## sterge proprietatea "size"
		unset($props['size']);
		
		## construieste butonul de submit
		$string .= form_submit('submit', 'Modifica');
		## inchide form-ul
		$string .= form_close();
		
		return $string;

	}
	
	## functie pentru aducerea a doua matrici cu care se va contrui
	## multiselect-ul pentru meniuri
	private function get_multiMeniu($sir){
		## sql-ul pentru aducerea tuturor meniurilor
		$sql = 'select id, denumire from glb_meniu order by pos';
		
		## ruleaza interogarea
		$rez = $this->db->query($sql);
		
		## introdu rezultatele intr-o matrice
		$opt = array();
		foreach ($rez->result() as $val){
			#echo $val['id'];
			#$x = $val->denumire;
			$opt[$val->id] = $val->denumire;
			
		}
		#var_dump($x);
		## transforma sirul de optiuni selectate intr-o matrice
		$select = explode(',', $sir);
		
		## genereaza matricea pentru return
		$data = array();
		$data['opt'] = $opt;
		#$data['opt'] = '';
		$data['select'] = $select;
		
		return $data;
	}
	
}
