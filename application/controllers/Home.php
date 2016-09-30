<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	
	public function index($page = 'home')
	{
		#$this->output->enable_profiler(TRUE);
		
		## in functie de pagina aleasa, proceseaza
		switch($page) {
			
			## daca e vorba de prima pagina
			case 'home':
				
				##verifica daca exista deja o sesiune pentru utilizator
				if(!isset($this->session->username)){
					## adu datele despre utilizator
					$this->getUser();
					
					## acasa
					#$this->session->mac = '12345';
					#$this->session->chkuser = 1;
					#$this->session->username = 'Ciprian Mic';
					#$this->session->delegatii = 3;
				}
				
				break;
			
			## daca se vrea resetarea sesiunii
			case 'reset':
				
				## distruge sesiunea
				session_destroy();
				
				## redirectioneaza utilizatorul la pagina principala
				redirect(base_url());
				
				break;
			
			## daca se modifica o perioada
			case 'mod':
				
				break;
		}
		
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		$data['liber'] = $this->getLiber();
		$data['liberi'] = $this->getLiberI();
		
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
				delegatii,
				id_dep,
				meniuri
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
			$this->session->userid = $row->id;
			$this->session->username = $row->nume;
			#$this->session->delegatii = $row->delegatii;
			
			## adu meniurile utilizatorului
			$this->getMeniu($row->meniuri);
			
		## daca nu sunt rezultate
		} else {
			$this->session->chkuser = 0;
		}
	}
	
	## functie pentru determinarea meniurilor utilizatorului
	private function getMeniu($menuid){
		
		## initiaza matricea care va tine meniurile
		$meniuri = array();
		
		## construieste interogarea pentru meniuri
		$sql = 'select pos, class, denumire from glb_meniu where id in ('
			. $menuid . ') order by pos';
		
		## extrage datele din DB
		$qry = $this->db->query($sql);
		
		## afla numarul de randuri aduse din DB
		## este numarul de meniuri asociate utilizatorului
		$x = $qry->num_rows();
		
		## afla procentul asociat fiecarui element din meniu
		## se aduna cu 2 pentru cele 2 elemente goale de la margine
		## scade .5 din rezultatul final, pentru siguranta
		$y = number_format(100 / ($x + 2),2) - .5;
		
		## string-ul care tine lista de meniuri
		$str = '<li style="width:' . $y . '%" class="w3-hide-small">&nbsp;</li>' . "\n";
		
		foreach($qry->result() as $row){
			
			$str .= '<li style="width:' . $y . '%"><a class="' .
				$row->class . '" href="' .
				base_url() .
				'index.php/' .
				$row->class . '">' .
				$row->denumire . "</a></li>\n";
			
			$x++;
			
			## populeaza matricea cu meniurile asociate utilizatorului
			array_push($meniuri, $row->class);
		}
		
		$str .= '<li style="width:' . $y . '%" class="w3-hide-small">&nbsp;</li>' . "\n";
		
		## incarca string-ul in sesiune
		$this->session->meniuri = $str;
		
		## incarca matricea de meniuri in sesiune
		$this->session->chkMenu = $meniuri;
		
	}
	
	## functie pentru aducerea perioadelor libere
	private function getLiber(){
		
		$string = '';
		$tip = '';
		
		##adu datele din DB
		$this->db->select('liber_perioade.id,
			liber_perioade.id_ang,
			liber_perioade.id_inloc,
			concat(glb_angajati.nume, " ", glb_angajati.prenume) as inloc,
			liber_perioade.time_out,
			liber_perioade.time_in,
			liber_perioade.tip,
			liber_perioade.tara,
			liber_perioade.obs');
		$this->db->where('id_ang', $this->session->userid);
		$this->db->where('year(time_in) >= ', date('Y'));
		$this->db->where('month(time_in) >= ', date('n'));
		$this->db->where('day(time_in) >= ', date('j'));
		$this->db->order_by('liber_perioade.time_out', 'ASC');
		$this->db->join('glb_angajati', 'glb_angajati.id = liber_perioade.id_inloc', 'left');
		$qry = $this->db->get('liber_perioade');
		
		## verifica daca exista date
		if($qry->num_rows() == 0){
			
			$string = '	<tr><td colspan = "6">nu ti-ai planificat nici o perioada libera</td></tr>';
			
		} else {
			
			foreach($qry->result() as $row){
				
				## adu valoarea tip-ului
				switch($row->tip){
					case 1:
						$tip = 'CO';
						break;
					case 2:
						$tip = 'BO';
						break;
					case 3:
						$tip = 'D';
						break;
				}
				
				$string .= '	<tr class = "tip' . $row->tip . '">
			<td>' . $row->id . '</td>
			<td>' . $tip . '</td>
			<td>' . $row->time_out . '</td>
			<td>' . $row->time_in . '</td>
			<td>' . $row->inloc . '</td>
			<td>' . $row->obs . "</td>
		</tr>\n";
			}
		}
		
		return $string;
	}
	
	## functie pentru aducerea perioadelor libere
	private function getLiberI(){
		
		$string = '';
		$tip = '';
		
		##adu datele din DB
		$this->db->select('liber_perioade.id,
			liber_perioade.id_ang,
			liber_perioade.id_inloc,
			concat(glb_angajati.nume, " ", glb_angajati.prenume) as ang,
			liber_perioade.time_out,
			liber_perioade.time_in,
			liber_perioade.tip,
			liber_perioade.tara,
			liber_perioade.obs');
		$this->db->where('id_inloc', $this->session->userid);
		$this->db->where('year(time_in) >= ', date('Y'));
		$this->db->where('month(time_in) >= ', date('n'));
		$this->db->where('day(time_in) >= ', date('j'));
		$this->db->order_by('liber_perioade.time_out', 'ASC');
		$this->db->join('glb_angajati', 'glb_angajati.id = liber_perioade.id_ang', 'left');
		$qry = $this->db->get('liber_perioade');
		
		## verifica daca exista date
		if($qry->num_rows() == 0){
			
			$string = '	<tr><td colspan = "6">nu exista nici o perioada ca si inlocuitor</td></tr>';
			
		} else {
			
			foreach($qry->result() as $row){
				
				## adu valoarea tip-ului
				switch($row->tip){
					case 1:
						$tip = 'CO';
						break;
					case 2:
						$tip = 'BO';
						break;
					case 3:
						$tip = 'D';
						break;
				}
				
				$string .= '	<tr class = "tip' . $row->tip . '">
			<td>' . $row->ang . '</td>
			<td>' . $row->time_out . '</td>
			<td>' . $row->time_in . '</td>
			<td>' . $row->obs . "</td>
		</tr>\n";
			}
		}
		
		return $string;
	}
	
}
