<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Liber extends CI_Controller {
	
	private static $tipLiber = array(
		'1' => array(
			'class' => ' class = "zliber"',
			'tin' => 480,
			'tout' => 1020,
			'pauza' => 240
		),
		'2' => array(
			'class' => ' class = "zmedical"',
			'tin' => 480,
			'tout' => 1020,
			'pauza' => 480
		),
		'3' => array(
			'class' => ' class = "zdelegatie"',
			'tin' => 0,
			'tout' => 1440,
			'pauza' => 1440
		)
	);
	
	private static $legenda = '	<div class = "legenda">
		<h5>Legenda</h5>
		<div class = "tip1"><a>perioada libera</a></div>
		<br class = "clar" />
		<div class = "tip2"><a>concediu medical</a></div>
		<br class = "clar" />
		<div class = "tip3"><a>delegatie</a></div>
		<br class = "clar" />
	</div>';
	
	public function index($page = 'liber')
	{
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('liber', $this->session->chkMenu)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## string-ul care genereaza link-urile de actiuni
		$linkuri = "\n\t<br />\n\t" . '<div class = "linkuri">
	<a href = "' . base_url('index.php/liber/index/perioada') . '">Perioada noua</a><br />
	<a href = "' . base_url('index.php/liber/index/delegatie') . '">Delegatie noua</a><br />
	<a href = "' . base_url('index.php/liber/index/istoric') . '">Istoric</a><br />
</div>';
		
		
		## in functie de numele paginii alese, se vor rula anumite functii
		## rezultand anumite date
		switch($page){
			
			## daca s-a accesat prima pagina
			case 'liber':
				
				## creaza link-urile de adaugari
				$data['linkuri'] = $linkuri;
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				## afiseaza legenda
				$data['legenda'] = self::$legenda;
				
				break;
			
			## daca se introduce o perioada noua
			case 'perioada':
				
				$data['linkuri'] = '';
				
				## construieste formularul pentru introducerea unei perioade noi
				$data['tabel'] = $this->modForm('liber');
				
				## legenda nu trebuie aratata
				$data['legenda'] = '';
				
				$page = 'liber';
				
				break;
			
			## daca se introduce o perioada noua
			case 'delegatie':
				
				$data['linkuri'] = '';
				
				## construieste formularul pentru introducerea unei perioade noi
				$data['tabel'] = $this->modForm('delegatie');
				
				## legenda nu trebuie aratata
				$data['legenda'] = '';
				
				$page = 'liber';
				
				break;
			
			## daca se introduce o perioada noua
			case 'mod':
				
				$data['linkuri'] = '';
				
				## extrage tipul perioadei de modificat
				$tip = 'mod' . $this->input->post('tip');
				
				## construieste formularul pentru modificarea unei perioade
				$data['tabel'] = $this->modForm($tip);
				
				## legenda nu trebuie aratata
				$data['legenda'] = '';
				
				$page = 'liber';
				
				break;
			
			## daca s-a introdus o perioada noua
			case 'add':
				
				## construieste variabilele comune
				## de la
				$dataOut = $this->input->post('from') .
					' ' .  $this->input->post('orafrom') .
					':' .  $this->input->post('mfrom') . ':00';
				
				## pana la
				$dataIn = $this->input->post('to') .
					' ' .  $this->input->post('orato') .
					':' .  $this->input->post('mto') . ':00';
				
				## introdu datele in DB
				switch($this->input->post('tip')){
					case 1:
					case 2:
						
						## stabileste loctiitorul
						$loct = $this->input->post('loct');
						
						## tara este goala pentru acest caz
						$tara = '';
						
						break;
					
					case 3:
						
						## stabileste loctiitorul
						$loct = '999';
						
						## stabileste tara de destinatie
						$tara = '';
						
						foreach($this->input->post('tari') as $id => $val){
							$tara .= $val . ',';
						}
						
						$tara = rtrim($tara, ',');
						
						break;
				}
				
				## matricea de date care trebuiesc introduse in DB
				$insertArr = array(
					'id_ang' => $this->session->userid,
					'id_inloc' => $loct,
					'time_out' => $dataOut,
					'time_in' => $dataIn,
					'tip' => $this->input->post('tip'),
					'tara' => $tara,
					'obs' => $this->input->post('obs')
				);
				
				## introdu datele in DB
				$this->db->insert('liber_perioade', $insertArr);
				
				## trimite mail-ul de confirmare
				$this->sendMail($this->session->userid,  $this->input->post('tip'), 'add', $insertArr);
				
				## afiseaza prima pagina
				## creaza link-urile de adaugari
				$data['linkuri'] = $linkuri;
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				## afiseaza legenda
				$data['legenda'] = self::$legenda;
				
				$page = 'liber';
				
				break;
			
			## daca s-a modificat o perioada
			case 'modd':
				
				## construieste variabilele comune
				## de la
				$dataOut = $this->input->post('from') .
					' ' .  $this->input->post('orafrom') .
					':' .  $this->input->post('mfrom') . ':00';
				
				## pana la
				$dataIn = $this->input->post('to') .
					' ' .  $this->input->post('orato') .
					':' .  $this->input->post('mto') . ':00';
				
				## introdu datele in DB
				switch($this->input->post('tip')){
					case 1:
					case 2:
						
						## stabileste loctiitorul
						$loct = $this->input->post('loct');
						
						## tara este goala pentru acest caz
						$tara = '';
						
						break;
					
					case 3:
						
						## stabileste loctiitorul
						$loct = '999';
						
						## stabileste tara de destinatie
						$tara = '';
						
						foreach($this->input->post('tari') as $id => $val){
							$tara .= $val . ',';
						}
						
						$tara = rtrim($tara, ',');
						
						break;
				}
				
				## matricea de date care trebuiesc introduse in DB
				$updateArr = array(
					'id_ang' => $this->session->userid,
					'id_inloc' => $loct,
					'time_out' => $dataOut,
					'time_in' => $dataIn,
					'tip' => $this->input->post('tip'),
					'tara' => $tara,
					'obs' => $this->input->post('obs')
				);
				
				## introdu datele in DB
				$this->db->where('id',$this->input->post('id'));
				$this->db->update('liber_perioade', $updateArr);
				
				## trimite mail-ul de confirmare
				$this->sendMail($this->session->userid, $this->input->post('tip'), 'mod', $updateArr);
				
				## afiseaza prima pagina
				## creaza link-urile de adaugari
				$data['linkuri'] = $linkuri;
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				## afiseaza legenda
				$data['legenda'] = self::$legenda;
				
				$page = 'liber';
				
				break;
			
			## daca se sterge o perioada
			case 'del':
				
				## extrage datele perioadei sterse
				## de la
				$dataOut = $this->input->post('from') .
					' ' .  $this->input->post('orafrom') .
					':' .  $this->input->post('mfrom') . ':00';
				
				## pana la
				$dataIn = $this->input->post('to') .
					' ' .  $this->input->post('orato') .
					':' .  $this->input->post('mto') . ':00';
				
				$datePerioada = array(
					'id_ang' => $this->session->userid,
					'id_inloc' => $this->input->post('id_inloc'),
					'time_out' => $dataOut,
					'time_in' => $dataIn,
					'tip' => $this->input->post('tip'),
					'tara' => $this->input->post('tara'),
					'obs' => $this->input->post('obs')
				);
				
				## trimite mail-ul de instiintare
				$this->sendMail($this->session->userid, $this->input->post('tip'), 'del', $datePerioada);
				
				## sterge perioada
				$this->db->where('id', $this->input->post('id'));
				$this->db->delete('liber_perioade');
				
				## afiseaza prima pagina
				## creaza link-urile de adaugari
				$data['linkuri'] = $linkuri;
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $this->makeTabel();
				
				## afiseaza legenda
				$data['legenda'] = self::$legenda;
				
				$page = 'liber';
				
				break;
			
			case 'istoric':
				
				## variabila care transporta formularul
				$formStr = '';
				## variabila care transporta tabelul
				$tabelStr = '';
				
				## matricea pentru denumirile lunilor (in RO)
				$luniRO = array(
					1 => 'Ianuarie',
					2 => 'Februarie',
					3 => 'Martie',
					4 => 'Aprilie',
					5 => 'Mai',
					6 => 'Iunie',
					7 => 'Iulie',
					8 => 'August',
					9 => 'Septembrie',
					10 => 'Octombrie',
					11 => 'Noiembrie',
					12 => 'Decembrie'
				);
				
				## adu anul si luna de afisat
				switch($this->input->post('istY')){
					
					case NULL:
						
						$an = date('Y');
						$luna = date('n');
						
						break;
					
					default:
						
						$an = $this->input->post('istY');
						$luna = $this->input->post('istM');
						
						break;
					
				}
				
				## construieste formularul pentru selectarea perioadei
				$arrAni = array();
				$this->db->select('YEAR(time_out) as an');
				$this->db->distinct();
				$qry = $this->db->get('liber_perioade');
				foreach($qry->result() as $row){
					$arrAni[$row->an] = $row->an;
				}
				
				$formStr .= form_open('index.php/liber/index/istoric');
				$formStr .= form_dropdown('istY', $arrAni, $an);
				$formStr .= form_dropdown('istM', $luniRO, $luna);
				$formStr .= form_submit('submit','Arata');
				$formStr .= form_close();
				
				## construieste capul de tabel
				$tabelStr .= $this->makeTabelIstoric($an, $luna);
				
				## extrage datele din DB, pentru perioada specificata
				$this->db->select();
				
				## afiseaza prima pagina
				## creaza link-urile de adaugari
				$data['linkuri'] = $formStr;
				
				## afiseaza tabelul cu perioadele libere viitoare
				$data['tabel'] = $tabelStr;
				
				## afiseaza legenda
				$data['legenda'] = self::$legenda;
				
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
					$string .= '<th align="center" class="w3-grey detaliuw">' .
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
					
					$clasa = ' class="w3-grey totalw">';
					
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
	liber_perioade.tara,
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
				$row->tara,
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
					$cls = ' class="w3-grey"';
				} else {
					$cls = '';
				}
				
				$val = '';
				$v = '';
				
				## construieste titlul celulei
				$titlu = '';
				
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
					
					## verifica daca trebuie notata ziua
					## daca inceputul e mai mic decat ziua prelucrata
					## SI daca sfarsitul e mai mare decat ziua prelucrata
					if($datain <= $zi && $dataout >= $zi){
						
						$titlu .= ' title = "';
						
						## valoarea care va fi afisata
						$valori = $this->checkValoare($perioada, $zi);
						
						## adauga datele de inceput
						$titlu .= 'Inceput: ' . $perioada[3] . ' ' . $valori['lunai'] . ' ' .
							$valori['orai'] . ':' . $valori['minuti'];
						## adauga datele de sfarsit
						$titlu .= "\nSfarsit: " . $perioada[7] . ' ' . $valori['lunao'] . ' ' .
							$valori['orao'] . ':' . $valori['minuto'];
						
						## in functie de tipul perioadei, prelucreaza datele
						switch($perioada[9]){
							
							## perioada libera normala
							case '1':
							## concediu medical
							case '2':
								
								## verifica daca e weekend
								if(in_array($i, $wend)){
									
									$v = '&nbsp;';
									$cls .= ' class="w3-grey"';
									
								} else {
									
									## pentru zilele de detaliu, arata ora si minut
									if($i <= $detaliuZile){
										
										## daca e vorba de zi cu detaliu
										$vi = $valori['ora'] . ':' . $valori['minut'] . ' h';
										$v = $this->makeEditable($id_ang, $id, $perioada[9], $vi);
										
									} else {
										
										## daca e zi de total, arata doar orele
										#$v = $valori['ora'];
										$v = $this->makeEditable($id_ang, $id, $perioada[9], $valori['ora']);
									}
									
									$cls = $clasa[$perioada[9]];
								}
								
								break;
							
							## delegatie
							case '3':
								
								#$v = $valori['ora'];
								$v = $this->makeEditable($id_ang, $id, $perioada[9], $valori['ora']);
								$cls = $clasa[$perioada[9]];
								
								## adauga si destinatia
								$titlu .= "\nDest.: " . $perioada[10];
								
								break;
						}
						
						$titlu .= '"';
						
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
		
		$x = array();
		$x['minut'] = 0;
		$minuti = $datePerioada[4] % 60;
		$minuto = $datePerioada[8] % 60;
		$x['ora'] = 0;
		$orai = ($datePerioada[4] - $minuti) / 60;
		$orao = ($datePerioada[8] - $minuto) / 60;
		$x['lunai'] = '';
		$x['lunao'] = '';
		$inceput = 0;
		$sfarsit = 0;
		
		$x['minuti'] = $this->corectareNumar($minuti);
		$x['minuto'] = $this->corectareNumar($minuto);
		$x['orai'] = $this->corectareNumar($orai);
		$x['orao'] = $this->corectareNumar($orao);
		
		## construieste zilele de inceput si de sfarsit
		$str = $datePerioada[1] . '-' . $datePerioada[2] . '-' . $datePerioada[3];
		$datain = strtotime($str);
		$str = $datePerioada[5] . '-' . $datePerioada[6] . '-' . $datePerioada[7];
		$dataout = strtotime($str);
		
		## ziua datei procesate
		$zi = date('j', $ziua);
		
		## luna datei de inceput
		$x['lunai'] = date('M', $datain);
		## luna datei de sfarsit
		$x['lunao'] = date('M', $dataout);
		
		## verifica daca datele perioadei libere coincid sau nu cu ziua procesata
		## daca perioada incepe SI se termina in ziua procesata
		if($datePerioada[3] == $zi && $datePerioada[7] == $zi){
			
			$inceput = $datePerioada[4];
			$sfarsit = $datePerioada[8];
			
		## daca perioada DOAR incepe in ziua procesata
		} elseif($datePerioada[3] == $zi && $datePerioada[7] != $zi){
			
			$inceput = $datePerioada[4];
			$sfarsit = self::$tipLiber[$datePerioada[9]]['tout'];
			
		## daca perioada DOAR se termina in ziua procesata
		} elseif($datePerioada[3] != $zi && $datePerioada[7] == $zi){
			
			$inceput = self::$tipLiber[$datePerioada[9]]['tin'];
			$sfarsit = $datePerioada[8];
			
		## daca perioada CUPRINDE ziua procesata
		} elseif($datePerioada[3] != $zi && $datePerioada[7] != $zi){
			
			$inceput = self::$tipLiber[$datePerioada[9]]['tin'];
			$sfarsit = self::$tipLiber[$datePerioada[9]]['tout'];
			
		}
		
		## verifica daca perioada libera cuprinde pauza (720 - 780)
		## daca perioada libera incepe inainte si se termina dupa pauza (conditia standard)
		if($this->intre($inceput,480,720) && $this->intre($sfarsit,780,1020)){
			$y = $sfarsit - $inceput - 60;
			
		## daca inceputul e IN timpul pauzei
		} elseif($this->intre($inceput,720,780) && $this->intre($sfarsit,780,1020)){
			$y = $sfarsit - 780;
			
		## daca sfarsitul e IN timpul pauzei
		} elseif($this->intre($inceput,480,720) && $this->intre($sfarsit,720,780)){
			$y = 720 - $inceput;
			
		## in restul cazurilor, calculeaza doar diferenta dintre sfarsit si inceput
		} else {
			$y = $sfarsit - $inceput;
		}
		
		$x['minut'] = $y % 60;
		$x['ora'] = ($y - $x['minut']) / 60;
		$x['test'] = $y;
		
		return $x;
	}
	
	## functie pentru generarea formularului pentru perioade libere
	private function modForm($tip){
		
		## defineste constantele generale
		$selectMinut = array(
			'00' => '00',
			'15' => '15',
			'30' => '30',
			'45' => '45'
		);
		
		## variabila care va tine datele despre tranzactia care trebuie modificata
		$valmod = array();
		
		## daca e vorba de o modificare, adu din DB, valorile tranzactiei de modificat
		if($this->input->post('id') > 0){
			
			$this->db->select('id, id_inloc,
				DATE_FORMAT(time_out,"%Y-%m-%d") as d_out,
				DATE_FORMAT(time_out,"%H") as h_out,
				DATE_FORMAT(time_out,"%i") as m_out,
				DATE_FORMAT(time_in,"%Y-%m-%d") as d_in,
				DATE_FORMAT(time_in,"%H") as h_in,
				DATE_FORMAT(time_in,"%i") as m_in,
				tip, tara, obs');
			$this->db->where('id', $this->input->post('id'));
			$qry = $this->db->get('liber_perioade');
			$row = $qry->row();
			
			## populeaza matricea de transport
			$valmod = array(
				'id' => $row->id,
				'inloc' => $row->id_inloc,
				'dout' => $row->d_out,
				'hout' => $row->h_out,
				'mout' => $row->m_out,
				'din' => $row->d_in,
				'hin' => $row->h_in,
				'min' => $row->m_in,
				'tip' => $row->tip,
				'tara' => $row->tara,
				'obs' => $row->obs,
				'submit' => 'Modifica',
				'sterge' => 'Sterge'
			);
			
			$tara = explode(',',$row->tara);
			
		} else {
			$valmod = array(
				'id' => '',
				'inloc' => '999',
				'dout' => '',
				'hout' => '08',
				'mout' => '00',
				'din' => '',
				'hin' => '17',
				'min' => '00',
				'tip' => '',
				'tara' => '',
				'obs' => '',
				'submit' => 'Adauga',
				'sterge' => ''
			);
			
			$tara = array();
		}
		
		$str = '';
		
		## defineste constantele pentru fiecare tip de perioada
		if($tip == 'liber' || $tip == 'mod1' || $tip == 'mod2'){
			
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
			
			$str .= form_dropdown('tip', $selectTip, $valmod['tip']);
			
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
			
			$str .= form_dropdown('loct', $selectInloc,$valmod['inloc']);
			
			
		} elseif($tip == 'delegatie' || $tip == 'mod3'){
			
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
			
			## stabileste tipul perioadei
			$str .= "\n\t" . form_hidden('tip', 3) . "\n";
			
			## construieste form-ul pentru tari
			## matricea tarilor de selectat
			$tariArr = array(
				'Romania',
				'Germania',
				'India',
				'Ungaria',
				'Italia',
				'China',
				'Portugalia',
				'Franta',
				'Elvetia',
				'Rusia'
			);
			
			$str .= "\t<h4>Destinatie</h4>\n";
			
			foreach($tariArr as $tval){
				
				$str .= "\t" . '<div id="atara">' . "\n\t\t";
				
				## verifica daca trebuiesc selectate
				if(in_array($tval, $tara)){
					$str .= form_checkbox('tari[]',$tval,TRUE);
				} else {
					$str .= form_checkbox('tari[]',$tval);
				}
				
				$str .= $tval . "</div>\n";
			}
			
			$str .= "\t" . '<div id="atara">' . "\n\t\t";
			$str .= form_checkbox('tari[]','Ungaria-Aeroport',FALSE,'style = "width: 200px"');
			$str .= "Ungaria-Aeroport</div>\n";
			
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
		
		$formDel = '';
		
		## daca e modificare, formularul duce la modificare
		switch($valmod['submit']){
			case 'Adauga':
				
				$formular .= form_open('index.php/liber/index/add');
				$formular .= form_hidden('id', $valmod['id']);
				
				$formDel .= '';
				
				break;
			
			case 'Modifica':
				
				$formular .= form_open('index.php/liber/index/modd');
				$formular .= form_hidden('id', $valmod['id']);
				
				## form-ul cu informatiile despre perioada care se va sterge
				$formDel .= form_open('index.php/liber/index/del');
				$formDel .= form_hidden('id', $valmod['id']);
				$formDel .= form_hidden('id_inloc', $valmod['inloc']);
				$formDel .= form_hidden('tip', $valmod['tip']);
				$formDel .= form_hidden('from', $valmod['dout']);
				$formDel .= form_hidden('orafrom', $valmod['hout']);
				$formDel .= form_hidden('mfrom', $valmod['mout']);
				$formDel .= form_hidden('to', $valmod['din']);
				$formDel .= form_hidden('orato', $valmod['hin']);
				$formDel .= form_hidden('mto', $valmod['min']);
				$formDel .= form_hidden('tara', $valmod['tara']);
				$formDel .= form_hidden('obs', $valmod['obs']);
				$formDel .= form_submit('sterge', 'Sterge');
				$formDel .= form_close();
				
				break;
			
		}
		
		## construieste data de inceput
		$formular .= '<div class = "datele">' . "\n\t<h4>Inceput</h4>\n\t";
		
		$formData = array(
			'name' => 'from',
			'id' => 'from'
		);
		$formular .= form_input($formData,$valmod['dout'],'required');
		$formular .= form_dropdown('orafrom', $selectOra, $valmod['hout']);
		$formular .= form_dropdown('mfrom', $selectMinut, $valmod['mout']);
		
		## construieste data de sfarsit
		$formular .= "\t<h4>Sfarsit</h4>\n\t";
		
		$formData = array(
			'name' => 'to',
			'id' => 'to'
		);
		$formular .= form_input($formData,$valmod['din'],'required');
		$formular .= form_dropdown('orato', $selectOra, $valmod['hin']);
		$formular .= form_dropdown('mto', $selectMinut, $valmod['min']);
		
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
		
		$formular .= form_textarea($formObs, $valmod['obs']);
		
		$formular .= "\t</div>\n\t";
		
		## genereaza butonul final
		$formular .= form_submit('submit', $valmod['submit']);
		
		## inchide formularul
		$formular .= form_close();
		
		## genereaza butonul de stergere, daca trebuie
		$formular .= $formDel;
		
		return $formular;
		
	}
	
	## functie pentru trimiterea unui e-mail
	private function sendMail($id, $tip, $mod, $valori){
		
		## construieste mail-ul in functie de modul operatiei
		switch($mod){
			
			case 'add':
				$descr = ' a introdus';
				
				break;
			
			case 'mod':
				$descr = ' a modificat';
				
				break;
			
			case 'del':
				$descr = ' a sters';
				
				break;
			
		}
		
		## afla descrierea mail-ului
		switch($tip){
			case 1:
			case 2:
				
				$titlu = 'Concediu';
				$descr .= ' un concediu';
				$col = 'mailcc';
				$observatii = ".\nObservatii: " . $valori['obs'];
				
				break;
				
			case 3:
				
				$titlu = 'Delegatie';
				$descr .= ' o delegatie';
				$col = 'dlgcc';
				$observatii = ".\nDestinatie: " . $valori['tara']
					. ".\nObservatii: " . $valori['obs'];
				
				break;
			
		}
		
		$from = 'postmaster@astormueller.ro';
		
		## incarca libraria pentru trimiterea mail-urilor
		$this->load->library('email');
		
		## de la cine se trimite mail-ul
		$this->email->from($from, $titlu);
		
		## afla datele necesare despre angajat
		## afla id-urile la care "mai" trebuie trimis mail-ul
		$this->db->select('user, ' . $col);
		$this->db->where('id', $id);
		$qry = $this->db->get('glb_angajati');
		$row = $qry->row();
		
		## cui i se trimite mail-ul
		$this->email->to($row->user . '@astormueller.ro');
		
		## afla persoanele care trebuiesc sa fie in CC
		$strx = $row->{$col};
		$cc = explode(',', $strx);
		$this->db->select('user');
		$this->db->where_in('id', $cc);
		$qry = $this->db->get('glb_angajati');
		
		## afla cine este inlocuitorul si daca trebuie trimis mail
		switch($valori['id_inloc']){
			## daca inlocuitorul e nedeclarat
			case '999':
				
				$ccAddr = '';
				
				break;
			
			## daca exista inlocuitor
			default:
				
				$this->db->select('user');
				$this->db->where('id', $valori['id_inloc']);
				$qryInloc = $this->db->get('glb_angajati');
				$rowI = $qryInloc->row();
				
				$ccAddr = $rowI->user . '@astormueller.ro, ';
				
				break;
		}
		
		## verifica daca sunt rezultate
		if($qry->num_rows() > 0){
			## construieste adresele de CC
			
			foreach($qry->result() as $addr){
				$ccAddr .= $addr->user . '@astormueller.ro, ';
			}
			
			## sterge ultima virgula
			$ccAddr = rtrim($ccAddr, ', ');
		
			$this->email->cc($ccAddr);
		}
		
		
		## adresele din BCC
		$this->email->bcc('cimi@astormueller.ro');
		
		## subiectul mail-ului
		$this->email->subject($this->session->username . $descr);
		
		## continutul mail-ului
		$emailMsg = $this->session->username . $descr .
			" cu urmatoarele date:\nData de inceput: " .
			$valori['time_out'] .
			"\nData de sfarsit: " .
			$valori['time_in'] .
			$observatii
		;
		
		$this->email->message($emailMsg);
		
		$this->email->send();
	}
	
	## functie pentru adaugarea unui 0 la numere
	private function corectareNumar($numar){
		
		$x = '0';
		
		if(strlen((string) $numar) == 1){
			$x .= $numar;
		} else {
			$x = $numar;
		}
		
		return $x;
	}
	
	## functie pentru a verifica daca o valoare este intr-un interval
	private function intre($int,$min,$max){
		
		return ($min<=$int && $int<=$max);
		
	}
	
	## functie pentru determinarea apartenentei unei perioade la un angajat
	private function makeEditable($id, $idtr, $tip, $valoare){
		
		$str = '';
		
		switch($id){
			
			## daca apartine celui conectat
			case $this->session->userid:
				
				## construieste form-ul
				$str .= form_open('index.php/liber/index/mod');
				$str .= form_hidden('tip', $tip);
				$str .= form_hidden('id', $idtr);
				$str .= form_submit('submit', $valoare, 'class = "mod"');
				$str .= form_close();
				
				break;
			
			## daca nu apartine celui conectat
			default:
				
				$str .= $valoare;
				
				break;
		}
		
		return $str;
	}
	
	## functie pentru construirea tabelului de perioade libere
	private function makeTabelIstoric($an, $luna){
		
		## initializeaza variabila de transport
		$string = '';
		
		## afla zilele din luna selectata
		
		## stabileste cate zile vin afisate
		$totalZile = date('t', strtotime('01-'. $luna . '-' . $an));
		$primaZi = strtotime('01-'. $luna . '-' . $an);
		$ultimaZi = strtotime($totalZile . '-'. $luna . '-' . $an);
		
		## construieste capul de tabel
		$string .=  '<table cellspacing="0" cellpadding="0">
	<tr>
		<th align="center" class = "colnume">Nume</th>';
		
		## variabila de transport a zilelor de weekend
		$wend = array();
		
		## numara totalul de zile de afisat
		for($i = 0; $i < $totalZile; $i++){
			
			## verifica daca e in timpul saptamanii
			$zi = date('N', strtotime("+$i days", $primaZi));
			
			if($zi < 6){
			
				## arata ziua si luna
				$string .= '<th align="center" class = "detaliu">' .
					date('d-m', strtotime("+$i days", $primaZi)) .
					'</th>';
				
			} else {
				
				## arata doar ziua
				$string .= '<th align="center" class="w3-grey detaliuw">' .
					date('d', strtotime("+$i days", $primaZi)) .
					'</th>';
				
				## incarca matricea de zile libere
				$wend[] = $i;
				
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
		$this->db->where('liber_perioade.time_in >', date('Y-m-d', $primaZi));
		$this->db->where('liber_perioade.time_out <', date('Y-m-d', $ultimaZi));
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
	liber_perioade.tara,
	liber_perioade.obs');
		$this->db->where('liber_perioade.time_in >', date('Y-m-d', $primaZi));
		$this->db->where('liber_perioade.time_out <', date('Y-m-d', $ultimaZi));
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
				$row->tara,
				$row->obs
			);
			
			$concedii[$row->id_ang][$row->id] = $x;
			
		}
		
		## proceseaza matricea de valori intr-o alta functie
		$string .= $this->makeDataTableIstoric($concedii, $totalZile, $wend, $primaZi);
		
		## inchide tabelul
		$string .= '</table>';
		
		return $string;
	}
	
	## functie pentru procesarea datelor perioadelor libere
	private function makeDataTableIstoric($concedii, $totalZile, $wend, $primaZi){
		
		## matricea pentru clasele tipurilor de concedii
		$clasa = array(
			'1' => ' class = "zliber"',
			'2' => ' class = "zmedical"',
			'3' => ' class = "zdelegatie"'
		);
		
		## initializeaza variabila de transport
		$string = '';
		
		## afla ziua de azi
		#$azi = strtotime(date('Y-m-d'));
		
		## proceseaza matricea cu datele extrase din DB
		foreach($concedii as $id_ang => $vals){
			## afiseaza numele
			$string .= '	<tr>
		<td class = "colnume">' . $vals['nume'] . "</td>\n";
			
			## sterge indexul 'nume'
			unset($vals['nume']);
			
			## ia fiecare zi afisata in parte si proceseaz-o
			for($i = 0; $i < $totalZile; $i++){
				
				## ziua procesata
				$zi = $primaZi + $i * 86400;
				
				## valori initiale
				## daca e weekend
				if(in_array($i, $wend)){
					$cls = ' class="w3-grey"';
				} else {
					$cls = '';
				}
				
				$val = '';
				$v = '';
				
				## construieste titlul celulei
				$titlu = '';
				
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
					
					## verifica daca trebuie notata ziua
					## daca inceputul e mai mic decat ziua prelucrata
					## SI daca sfarsitul e mai mare decat ziua prelucrata
					if($datain <= $zi && $dataout >= $zi){
						
						$titlu .= ' title = "';
						
						## valoarea care va fi afisata
						$valori = $this->checkValoare($perioada, $zi);
						
						## adauga datele de inceput
						$titlu .= 'Inceput: ' . $perioada[3] . ' ' . $valori['lunai'] . ' ' .
							$valori['orai'] . ':' . $valori['minuti'];
						## adauga datele de sfarsit
						$titlu .= "\nSfarsit: " . $perioada[7] . ' ' . $valori['lunao'] . ' ' .
							$valori['orao'] . ':' . $valori['minuto'];
						
						## in functie de tipul perioadei, prelucreaza datele
						switch($perioada[9]){
							
							## perioada libera normala
							case '1':
							## concediu medical
							case '2':
								
								## verifica daca e weekend
								if(in_array($i, $wend)){
									
									$v = '&nbsp;';
									$cls .= ' class="w3-grey"';
									
								} else {
									
									$v = $valori['ora'] . ':' . $valori['minut'] . ' h';
									$cls = $clasa[$perioada[9]];
								}
								
								## adauga si observatii
								$titlu .= "\nObs.: " . $perioada[11];
								
								break;
							
							## delegatie
							case '3':
								
								## daca e in weekend, scrie mai mic
								if(in_array($i, $wend)){
									$cls = $clasa[$perioada[9]];
								} else {
									$cls = $clasa[$perioada[9]];
								}
								
								$v = $valori['ora'];
								
								## adauga si destinatia
								$titlu .= "\nDest.: " . $perioada[10];
								
								break;
						}
						
						$titlu .= '"';
						
					}
				}
				
				$string .= "\t\t<td" . $cls . $titlu . '>' . $v . "</td>\n";
				
			}
			
			$string .= "\t</tr>\n";
		}
		
		return $string;
	}
	
}
