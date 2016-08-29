<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Delegatii extends CI_Controller {
	
	public function index($page = 'delegatii')
	{
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('delegatii', $this->session->chkMenu)){
			
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
		
		## adu datele delegatiilor introduse
		$data['listadlg'] = $this->get_listaDelegatii();
		
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
	## functie pentru aducerea datelor delegatiilor existente
	private function get_listaDelegatii(){
		
		$string = '';
		
		## construieste sql-ul pentru interogarea bazei de date
		## extrage doar delegatiile care nu s-au terminat inca
		$sql = 'select dlg_delegatii.id,
			dlg_delegatii.id_ang,
			glb_angajati.nume,
			glb_angajati.prenume,
			dlg_delegatii.inceput,
			dlg_delegatii.sfarsit
		from dlg_delegatii
			left join glb_angajati
				on dlg_delegatii.id_ang = glb_angajati.id
		where dlg_delegatii.sfarsit >= now()';
		
		$qry = $this->db->query($sql);
		
		foreach($qry->result() as $row){
			$string .= '<p class = "w3-container w3-green inceput">' . $row->inceput . "</p>";
			$string .= '<p class = "w3-container w3-blue nume">' . $row->nume . ' ' . $row->prenume . '</p>';
			$string .= $this->checkAsigurare($row->id_ang, $row->sfarsit);
			$string .= '<br style = "clear:left;" />';
			
			$string .= $this->get_transport($row->id);
			#$temp = $this->get_transport($row->id);
			#var_dump($temp);
			$string .= '<p class = "w3-container w3-green sfarsit">' . $row->sfarsit . "</p>\n";
		}
		
		return $string;
	}
	
	## functie pentru verificarea valabilitatii unei asigurari
	## cu data de sfarsit a delegatiei
	private function checkAsigurare($id_ang, $sfarsit){
		
		$string = '';
		
		## construieste sql-ul pentru aducerea datelor
		$sql = "select id
			from dlg_asigurari
			where id_ang = ?
			and sfarsit >= ?";
		
		$qry = $this->db->query($sql, array($id_ang, $sfarsit));
		
		if($qry->num_rows() > 0){
			foreach($qry->result() as $row){
				$string .= '<p class = "w3-container w3-green asg">Asigurare OK ' .
					$row->id .' </p>';
			}
			
		} else {
			$string .= '<p class = "w3-container w3-red">Asigurare lipsa</p>';
		}
		
		return $string;
	}
	
	## functie pentru aducerea transporturilor si transferurilor
	## aferente unei delegatii
	private function get_transport($id_dlg){
		
		$data = array();
		
		## extrage transporturile
		$sql = 'select id, dataplecare from dlg_transportl where id_dlg = ' . $id_dlg .
			' order by dataplecare';
		
		$qry = $this->db->query($sql);
		
		foreach($qry->result() as $row){
			$data[$row->dataplecare] = array($row->id, 1);
		}
		
		## extrage transferurile
		$sql = 'select id, dataplecare from dlg_transfer where id_dlg = ' . $id_dlg .
			' order by dataplecare';
		
		$qry = $this->db->query($sql);
		
		foreach($qry->result() as $row){
			$data[$row->dataplecare] = array($row->id, 2);
		}
		
		## ordoneaza matricea in functie de date
		ksort($data);
		
		$string = '';
		foreach($data as $timp => $ids){
			$string .= '<p class = "w3-container tr' . $ids[1] . '">' .
				$timp . '</p>';
		}
		
		return $string;
	}
	
}
