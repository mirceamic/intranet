<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	
	public function index($page = 'home')
	{
		$this->output->enable_profiler(TRUE);
		
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username)){
			## adu datele despre utilizator
			#$this->getUser();
			
			## acasa
			$this->session->mac = '12345';
			$this->session->chkuser = 1;
			$this->session->username = 'Ciprian Mic';
			$this->session->delegatii = 3;
		}
		
		## daca "utilizatorul" are drepturi de acces la aplicatie
		if($this->session->chkuser == 1){
			$this->load->view('header', $data);
			$this->load->view($page);
			$this->load->view('footer');
			
		## daca nu are drepturi -> pagina de atentionare
		} elseif($this->session->chkuser == 0){
			$this->load->view('denyuser');
		}
	}
	
	## Functii private ##
	
	## functie pentru determinarea utilizatorului
	private function getUser(){
		
		## afla ip-ul care cere pagina
		$ip = $_SERVER['REMOTE_ADDR'];

		## verifica daca e vorba de magazin
		$mag = explode('.',$ip);

		## daca ip-ul e din clasa 147
		if($mag[2] == 147){
			$mac = '1234';

		## daca nu e din clasa 147 (in mod normal e din 140)
		} else {

			$cmd = "arp -a -n | grep " . $ip;
			$x = shell_exec($cmd);
			$y = explode(" ", $x);
			$mac = strtolower($y[3]);
			## data-timp cand a fost facuta cererea
			#$this->reg->set('time', $_SERVER['REQUEST_TIME']);
		}
		
		## afla utilizatorul in functie de MAC
		## interogarea pentru aducerea datelor din baza de date
		$sql = 'select id,
				concat(prenume, ", ", nume) as nume,
				delegatii
			from glb_angajati
			where inactiv = 0
				and mac = "' . $mac . '"';

		## extrage datele si prelucreaza-le
		$qry = $this->db->query($sql);
		
		$row = $qry->row();
		
		## trimite mac-ul in sesiune
		## in cazul utilizatorului care trebuie autorizat
		$this->session->mac = $mac;
		
		## daca sunt rezultate
		if (isset($row)){
			$this->session->chkuser = 1;
			$this->session->username = $row->nume;
			$this->session->delegatii = $row->delegatii;
			
		## daca nu sunt rezultate
		} else {
			$this->session->chkuser = 0;
		}
	}
}
