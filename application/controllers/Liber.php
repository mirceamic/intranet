<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Liber extends CI_Controller {
	
	private static $tipLiber = array(
		'1' => array(
			'class' => ' class = "zliber"',
			'tin' => 480,
			'tout' => 1020,
			'pauza' => 4
		),
		'2' => array(
			'class' => ' class = "zmedical"',
			'tin' => 480,
			'tout' => 1020,
			'pauza' => 8
		),
		'3' => array(
			'class' => ' class = "zdelegatie"',
			'tin' => 0,
			'tout' => 1440,
			'pauza' => 24
		)
	);
	
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
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## in functie de numele paginii alese, se vor rula anumite functii
		## rezultand anumite date
		switch($page){
			
			## daca s-a accesat prima pagina
			case 'liber':
				
				## creaza link-urile de adaugari
				$data['linkuri'] = "\n\t<br />\n\t" . '<div class = "linkuri">
	<a href = "' . base_url('index.php/liber/index/perioada') . '">Perioada noua</a><br />
	<a href = "' . base_url('index.php/liber/index/delegatie') . '">Delegatie noua</a><br />
</div>';
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				break;
			
			## daca se introduce o perioada noua
			case 'perioada':
				
				$data['linkuri'] = '';
				
				## construieste formularul pentru introducerea unei perioade noi
				$data['tabel'] = $this->makeForm('liber');
				
				$page = 'liber';
				
				break;
			
			## daca se introduce o perioada noua
			case 'delegatie':
				
				$data['linkuri'] = '';
				
				## construieste formularul pentru introducerea unei perioade noi
				$data['tabel'] = $this->makeForm('delegatie');
				
				$page = 'liber';
				
				break;
			
			## daca s-a introdus o perioada noua
			case 'add':
				
				## introdu datele in DB
				
				
				## trimite mail-ul de confirmare
				
				
				## afiseaza prima pagina
				## creaza link-urile de adaugari
				$data['linkuri'] = "\n\t<br />\n\t" . '<div class = "linkuri">
	<a href = "' . base_url('index.php/liber/index/perioada') . '">Perioada noua</a><br />
	<a href = "' . base_url('index.php/liber/index/delegatie') . '">Delegatie noua</a><br />
</div>';
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				$page = 'liber';
				
				break;
			
		}
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/liber/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
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
		$string .=  '<table cellspacing="0" cellpadding="0">
	<tr>
		<th align="center" class = "colnume">Nume</th>';
		
		## variabila de transport a zilelor de weekend
		$wend = array();
		
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
					
					## incarca matricea de zile libere
					$wend[] = $i;
					
				}
				
			} else {
				
				## verifica daca e in timpul saptamanii
				$zi = date('N', strtotime("+$i days"));
				
				if($zi < 6){
					
					## hasureaza celula
					$clasa = ' class = "total">';
					
				} else {
					
					$clasa = ' class="w3-light-grey totalw">';
					
					## incarca matricea de zile libere
					$wend[] = $i;
					
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
		$this->db->select('liber_perioade.id_ang,
	concat(glb_angajati.nume, " ", glb_angajati.prenume) as angajat');
		$this->db->join('glb_angajati', 'liber_perioade.id_ang = glb_angajati.id');
		$this->db->where('liber_perioade.time_in >', date('Y-m-d'));
		$this->db->order_by('angajat', 'ASC');
		$qry = $this->db->get('liber_perioade');
		
		## genereaza matricea de transport
		foreach($qry->result() as $id){
			$concedii[$id->id_ang] = array(
				'nume' => $id->angajat
			);
		}
		
		## extrage datele din DB
		$this->db->select('liber_perioade.id,
	liber_perioade.id_ang,
	concat(inloc.nume, " ", inloc.prenume) as inlocuitor,
	year(liber_perioade.time_out) as an_out,
	month(liber_perioade.time_out) as luna_out,
	day(liber_perioade.time_out) as zi_out,
	(hour(liber_perioade.time_out) * 60 + minute(liber_perioade.time_out)) as minute_out, 
	year(liber_perioade.time_in) as an_in,
	month(liber_perioade.time_in) as luna_in,
	day(liber_perioade.time_in) as zi_in,
	(hour(liber_perioade.time_in) * 60 + minute(liber_perioade.time_in)) as minute_in,
	liber_perioade.tip,
	liber_perioade.obs');
		$this->db->where('liber_perioade.time_in >', date('Y-m-d'));
		$this->db->join('glb_angajati as ang', 'liber_perioade.id_ang = ang.id');
		$this->db->join('glb_angajati as inloc', 'liber_perioade.id_inloc = inloc.id');
		$this->db->order_by('zi_out', 'ASC');
		
		$qry = $this->db->get('liber_perioade');
		
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
				$row->tip,
				$row->obs
			);
			
			$concedii[$row->id_ang][$row->id] = $x;
			
		}
		
		## proceseaza matricea de valori intr-o alta functie
		$string .= $this->makeDataTable($concedii, $totalZile, $detaliuZile, $wend);
		
		## inchide tabelul
		$string .= '</table>';
		
		return $string;
	}
	
	## functie pentru procesarea datelor perioadelor libere
	private function makeDataTable($concedii, $totalZile, $detaliuZile, $wend){
		
		## matricea pentru clasele tipurilor de concedii
		$clasa = array(
			'1' => ' class = "zliber"',
			'2' => ' class = "zmedical"',
			'3' => ' class = "zdelegatie"'
		);
		
		
		## initializeaza variabila de transport
		$string = '';
		
		## afla ziua de azi
		$azi = strtotime(date('Y-m-d'));
		
		## proceseaza matricea cu datele extrase din DB
		foreach($concedii as $id_ang => $vals){
			## afiseaza numele
			$string .= '	<tr>
		<td class = "colnume">' . $vals['nume'] . "</td>\n";
			
			## sterge indexul 'nume'
			unset($vals['nume']);
			
			## ia fiecare zi afisata in parte si proceseaz-o
			for($i = 0; $i <= $totalZile; $i++){
				
				## ziua procesata
				$zi = $azi + $i * 86400;
				
				## valori initiale
				## daca e weekend
				if(in_array($i, $wend)){
					$cls = ' class="w3-light-grey"';
				} else {
					$cls = '';
				}
				
				$val = '';
				$v = '';
				
				## ia fiecare perioada a angajatului
				## verifica fiecare perioada a angajatului
				foreach($vals as $id => $perioada){
					
					## construieste prima zi
					$str = $perioada[1] . '-' . $perioada[2] . '-' . $perioada[3];
					#$datain = date_create($str);
					$datain = strtotime($str);
					## construieste ultima zi
					$str = $perioada[5] . '-' . $perioada[6] . '-' . $perioada[7];
					#$dataout = date_create($str);
					$dataout = strtotime($str);
					
					## construieste titlul celulei
					$titlu = ' title = "';
					
					## adauga datele de inceput
					$titlu .= $perioada[3] . '-' . $perioada[2] . ' ' . $perioada[4]/60 . ' - ';
					## adauga datele de sfarsit
					$titlu .= $perioada[7] . '-' . $perioada[6] . ' ' . $perioada[8]/60 . '"';
					
					## verifica daca trebuie notata ziua
					## daca inceputul e mai mic decat ziua prelucrata
					## SI daca sfarsitul e mai mare decat ziua prelucrata
					if($datain <= $zi && $dataout >= $zi){
						
						## valoarea care va fi afisata
						$valori = $this->checkValoare($perioada, $zi);
						#$val += intval($perioada[8])/60 - intval($perioada[4])/60;
						$val += $valori;
						
						## in functie de tipul perioadei, prelucreaza datele
						switch($perioada[9]){
							
							## perioada libera normala
							case '1':
							## concediu medical
							case '2':
								
								## verifica daca e weekend
								if(in_array($i, $wend)){
									
									$v = '&nbsp;';
									$cls .= ' class="w3-light-grey"';
									
								} else {
									
									$v = $val;
									$cls = $clasa[$perioada[9]];
								}
								
								break;
							
							## delegatie
							case '3':
								
								$v = $val;
								$cls = $clasa[$perioada[9]];
								
								break;
						}
					}
				}
				
				$string .= "\t\t<td" . $cls . $titlu . '>' . $v . "</td>\n";
				
			}
			
			$string .= "\t</tr>\n";
		}
		
		return $string;
	}
	
	## functie pentru calcularea valorii afisate in tabel
	private function checkValoare($datePerioada, $ziua){
		$x = 0;
		$inceput = 0;
		$sfarsit = 0;
		
		## construieste zilele de inceput si de sfarsit
		$str = $datePerioada[1] . '-' . $datePerioada[2] . '-' . $datePerioada[3];
		$datain = strtotime($str);
		$str = $datePerioada[5] . '-' . $datePerioada[6] . '-' . $datePerioada[7];
		$dataout = strtotime($str);
		
		## ziua datei procesate
		$zi = date('j', $ziua);
		
		## verifica daca datele perioadei libere coincid sau nu cu ziua procesata
		if($datePerioada[3] == $zi && $datePerioada[7] == $zi){
			
			$inceput = $datePerioada[4];
			$sfarsit = $datePerioada[8];
			
		} elseif($datePerioada[3] == $zi && $datePerioada[7] != $zi){
			
			$inceput = $datePerioada[4];
			$sfarsit = self::$tipLiber[$datePerioada[9]]['tout'];
			
		} elseif($datePerioada[3] != $zi && $datePerioada[7] == $zi){
			
			$inceput = self::$tipLiber[$datePerioada[9]]['tin'];
			$sfarsit = $datePerioada[8];
			
		} elseif($datePerioada[3] != $zi && $datePerioada[7] != $zi){
			
			$inceput = self::$tipLiber[$datePerioada[9]]['tin'];
			$sfarsit = self::$tipLiber[$datePerioada[9]]['tout'];
			
		}
		
		$x = ($sfarsit - $inceput)/60;
		
		if($x > self::$tipLiber[$datePerioada[9]]['pauza']){
			$x--;
		}
		
		return $x;
	}
	
	## functie pentru generarea formularului pentru perioade libere
	private function makeForm($tip){
		
		## defineste constantele generale
		$selectMinut = array(
			'00' => '00',
			'15' => '15',
			'30' => '30',
			'45' => '45'
		);
		
		$str = '';
		
		## defineste constantele pentru fiecare tip de perioada
		if($tip == 'liber'){
			
			$selectOra = array(
				'08' => '08',
				'09' => '09',
				'10' => '10',
				'11' => '11',
				'12' => '12',
				'13' => '13',
				'14' => '14',
				'15' => '15',
				'16' => '16',
				'17' => '17'
			);
			
			## construieste selectorul tipului de perioada libera
			$str .= "\n\t<h4>Tipul perioadei:</h4>\n\t";
			
			$selectTip = array(
				1 => 'Concediu de odihna',
				2 => 'Concediu medical'
			);
			
			$str .= form_dropdown('tip', $selectTip);
			
			## construieste selectorul pentru inlocuitor
			$str .= "\t<h4>Inlocuitor:</h4>\n\t";
			
			$this->db->select('id, concat(nume, ", ", prenume) as ang');
			$this->db->where('inlocuitor > ', 0);
			$this->db->order_by('inlocuitor');
			$qry = $this->db->get('glb_angajati');
			
			## populeaza matricea pentru selector
			$selectInloc = array();
			
			foreach($qry->result() as $row){
				$selectInloc[$row->id] = $row->ang;
			}
			
			$str .= form_dropdown('loct', $selectInloc,'999');
			
			
		} elseif($tip == 'delegatie'){
			
			$selectOra = array(
				'00' => '00',
				'01' => '01',
				'02' => '02',
				'03' => '03',
				'04' => '04',
				'05' => '05',
				'06' => '06',
				'07' => '07',
				'08' => '08',
				'09' => '09',
				'10' => '10',
				'11' => '11',
				'12' => '12',
				'13' => '13',
				'14' => '14',
				'15' => '15',
				'16' => '16',
				'17' => '17',
				'18' => '18',
				'19' => '19',
				'20' => '20',
				'21' => '21',
				'22' => '22',
				'23' => '23'
			);
		}
		
		
		## construieste script-ul pentru alegerea datelor
		$formular = '<script src="' .
			base_url('application/third_party/jquery-ui-1.12/external/jquery/jquery.js') .
			'"></script>' . "\n";
		$formular .= '<script src="' .
			base_url('application/third_party/jquery-ui-1.12') .
			'/jquery-ui.min.js"></script>' . "\n";
		$formular .= '<script>
$(function() {
	$( "#from" ).datepicker({
		numberOfMonths: 1,
		onClose: function( selectedDate ) {
			$( "#to" ).datepicker( "option", "minDate", selectedDate );
		}
	});
	$( "#to" ).datepicker({
		numberOfMonths: 1,
		onClose: function( selectedDate ) {
			$( "#from" ).datepicker( "option", "maxDate", selectedDate );
		}
	});
});
</script>
<h3>Adauga perioada</h3>' . "\n";
		
		$formular .= form_open('index.php/liber/index/add');
		
		## construieste data de inceput
		$formular .= '<div class = "datele">' . "\n\t<h4>Inceput</h4>\n\t";
		
		$formData = array(
			'name' => 'from',
			'id' => 'from'
		);
		$formular .= form_input($formData);
		$formular .= form_dropdown('orafrom', $selectOra);
		$formular .= form_dropdown('mfrom', $selectMinut);
		
		## construieste data de sfarsit
		$formular .= "\t<h4>Sfarsit</h4>\n\t";
		
		$formData = array(
			'name' => 'to',
			'id' => 'to'
		);
		$formular .= form_input($formData);
		$formular .= form_dropdown('orato', $selectOra);
		$formular .= form_dropdown('mto', $selectMinut);
		
		## construieste partea diferita a formularului
		$formular .= "</div>\n" . '<div class = "selectori">';
		
		$formular .= $str;
		
		## campul de observatii
		$formular .= "</div><br /><br />\n\t" . '<div class = "observatii">' . "\n\t\t";
		$formular .= "\t<h4>Observatii</h4>\n\t";
		
		$formObs = array(
			'name' => 'obs',
			'rows' => 3,
			'cols' => 40
		);
		
		$formular .= form_textarea($formObs);
		
		$formular .= "\t</div>\n\t";
		
		## genereaza butonul final
		$formular .= form_submit('submit', 'Adauga');
		
		## inchide formularul
		$formular .= form_close();
		
		return $formular;
		
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
