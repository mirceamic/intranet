<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Liber extends CI_Controller {
	
	public function index($page = 'liber')
	{
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('liber', $this->session->chkMenu)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/liber/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## in functie de numele paginii alese, se vor rula anumite functii
		## rezultand anumite date
		switch($page){
			
			## daca s-a accesat prima pagina
			case 'liber':
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				break;
			
			## daca se acceseaza pagina de adaugare 
		}
		
		## contruieste calea intreaga a paginii de incarcat
		$fpage = 'liber/' . $page;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($fpage);
		$this->load->view('footer');
		
	}
	
	## functie pentru pagina de istoric
	public function istoric(){
		
	}
	
	## Functii private ##
	
	## functie pentru construirea tabelului de perioade libere
	private function makeTabel(){
		
		## initializeaza variabila de transport
		$string = '';
		
		## stabileste cate zile vin afisate
		$totalZile = 30;
		$detaliuZile = 13;
		
		## construieste capul de tabel
		$string .=  '<table width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<th align="center" class = "colnume">Nume</th>';
		
		## numara totalul de zile de afisat
		for($i = 0; $i <= $totalZile; $i++){
			
			## verifica daca e in primele $detaliuZile
			if($i <= $detaliuZile){
				
				## verifica daca e in timpul saptamanii
				$zi = date('N', strtotime("+$i days"));
				
				if($zi < 6){
				
					## arata ziua si luna
					$string .= '<th align="center" class = "detaliu">' .
						date('d-m', strtotime("+$i days")) .
						'</th>';
					
				} else {
					
					## arata doar ziua
					$string .= '<th align="center" class="w3-light-grey detaliuw">' .
						date('d', strtotime("+$i days")) .
						'</th>';
				}
				
			} else {
				
				## verifica daca e in timpul saptamanii
				$zi = date('N', strtotime("+$i days"));
				
				if($zi < 6){
					
					## hasureaza celula
					$clasa = ' class = "total">';
					
				} else {
					
					$clasa = ' class="w3-light-grey totalw">';
					
				}
				
				$string .= '<th align="center"' . $clasa .
					date('d', strtotime("+$i days")) . '</th>';
			}
			
		}
		
		## inchide tr-ul capului de tabel
		$string .= "\n\t</tr>\n";
		
		## construieste partea de date a tabelului
		## matricea de transport a concediilor
		## datele extrase din DB sunt tinute aici, dupa care
		## matricea asta este procesata pentru afisarea rezultatelor
		$concedii = array();
		
		## extrate id-urile angajatilor care au perioade libere
		$this->db->distinct();
		$this->db->select('liber_concedii.id_ang,
	concat(glb_angajati.nume, " ", glb_angajati.prenume) as angajat');
		$this->db->join('glb_angajati', 'liber_concedii.id_ang = glb_angajati.id');
		$this->db->where('liber_concedii.time_in >', date('Y-m-d'));
		$this->db->order_by('angajat', 'ASC');
		$qry = $this->db->get('liber_concedii');
		
		## genereaza matricea de transport
		foreach($qry->result() as $id){
			$concedii[$id->id_ang] = array(
				'nume' => $id->angajat
			);
		}
		
		## extrage datele din DB
		$this->db->select('liber_concedii.id,
	liber_concedii.id_ang,
	concat(inloc.nume, " ", inloc.prenume) as inlocuitor,
	year(liber_concedii.time_out) as an_out,
	month(liber_concedii.time_out) as luna_out,
	day(liber_concedii.time_out) as zi_out,
	(hour(liber_concedii.time_out) * 60 + minute(liber_concedii.time_out)) as minute_out, 
	year(liber_concedii.time_in) as an_in,
	month(liber_concedii.time_in) as luna_in,
	day(liber_concedii.time_in) as zi_in,
	(hour(liber_concedii.time_in) * 60 + minute(liber_concedii.time_in)) as minute_in,
	liber_concedii.obs');
		$this->db->where('liber_concedii.time_in >', date('Y-m-d'));
		$this->db->join('glb_angajati as ang', 'liber_concedii.id_ang = ang.id');
		$this->db->join('glb_angajati as inloc', 'liber_concedii.id_inloc = inloc.id');
		$this->db->order_by('zi_out', 'ASC');
		
		$qry = $this->db->get('liber_concedii');
		
		$x = array();
		
		foreach($qry->result() as $row){
			
			$x = array(
				$row->inlocuitor,
				$row->an_out,
				$row->luna_out,
				$row->zi_out,
				$row->minute_out,
				$row->an_in,
				$row->luna_in,
				$row->zi_in,
				$row->minute_in,
				$row->obs
			);
			
			$concedii[$row->id_ang][$row->id] = $x;
			
		}
		
		## proceseaza matricea de valori intr-o alta functie
		$string .= $this->makeDataTable($concedii, $totalZile, $detaliuZile);
		
		## inchide tabelul
		$string .= '</table>';
		
		return $string;
	}
	
	## functie pentru procesarea datelor perioadelor libere
	private function makeDataTable($concedii, $totalZile, $detaliuZile){
		
		## initializeaza variabila de transport
		$string = '';
		
		## proceseaza matricea cu datele extrase din DB
		foreach($concedii as $id_ang => $vals){
			
			## afiseaza numele
			$string .= '	<tr>
		<td class = "colnume">' . $vals['nume'] . '</td>';
			
			## sterge indexul 'nume'
			unset($vals['nume']);
			
			## ia fiecare zi afisata in parte si proceseaz-o
			for($i = 0; $i <= $totalZile; $i++){
				
				## verifica fiecare perioada a angajatului
				foreach($vals as $id => $perioada){
					
					## 
				}
			}
			
			## ia fiecare perioada libera a angajatului si afiseaz-o
			foreach($vals as $id => $perioada){
				
				## 
				$string .= '<td class = "colnume">' . $perioada[3] . '</td>';
			}
			
			$string .= "\t</tr>\n";
		}
		
		return $string;
	}
	
	
	## functie pentru trimiterea unui e-mail
	private function sendMail(){
		$this->load->library('email');

$this->email->from('postmaster@astormueller.ro', 'Concedii');
$this->email->to('ciprian.mic@astormueller.ro');
#$this->email->cc('another@astormueller.ro');
#$this->email->bcc('them@their-example.com');

$this->email->subject('Email Test');
$this->email->message('Testing the email class.');

$this->email->send();
	}
	
}
