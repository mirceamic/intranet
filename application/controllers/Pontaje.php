<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pontaje extends CI_Controller {
	
	## defineste o matrice cu denumirea lunilor
	private static $luniText = array(
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
	
	public function index($page = 'pontaje')
	{
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('pontaje', $this->session->chkMenu)){
			
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
		
		## afla anul
		## adu anul si luna de afisat
		switch($this->input->post('an')){
			
			case NULL:
				
				$an = date('Y');
				$luna = date('n');
				$azi = intval(date('j'));
				
			break;
			
			default:
				
				$an = $this->input->post('an');
				$luna = $this->input->post('luna');
				
				## verifica daca luna aleasa este identica cu luna curenta
				if($luna == date('n')){
					
					## ultima zi se ia ziua curenta
					$azi = intval(date('j'));
					
				## daca nu este identica
				} else {
					
					## construieste data aleasa
					$tmpstr = $an . '-' . $luna . '-1';
					$tmp = strtotime($tmpstr);
					## se ia ultima zi din luna
					$azi = intval(date('t', $tmp));
					
				}
				
			break;
			
		}
		#$luna = 10;
		#$an = 2016;
		
		## construieste selectorul de perioada
		$data['selector'] = $this->faSelector($luna, $an);
		
		## construieste tabelul cu valorile initiale
		$liberInit = $this->aduConcediiInit($an, $luna);
		$data['tabelInit'] = $liberInit['string'];
		
		## adu zilele libere oficiale
		$zLiber = $this->getZileLibere($an);
		
		## adu concediile angajatului
		$concedii = $this->aduConcedii($an, $luna);
		
		## adu datele de pontare ale angajatului curent
		$pontari = $this->aduPontari($luna, $an);
		
		## initializeaza tabelul de date
		$data['pontaje'] = '';
		
		## liber initial
		$liberInit = $liberInit['total'];
		
		## ia fiecare zi a lunii
		for($i = 1; $i <= $azi; $i++){
			
			## verifica ziua procesata
			$verZi = $this->verificaZiua($i, $luna, $an, $zLiber);
			
			## initializeaza diferenta zilnica - timpul care se scade din concediu
			$diffZilnic = 0;
			
			## da nu trebuie procesata
			if($verZi == 1){
				
				## scrie un rand gol
				$data['pontaje'] .= '<tr><td class = "liber">' . $i .
					'</td><td colspan = "9" class = "liber">&nbsp;</td></tr>';
				
			## daca trebuie procesata
			} elseif($verZi == 0){
				
				## transforma ziua in timestamp
				$zu = mktime(0, 0, 0, $luna, $i, $an);
				
				## adu valoarea "Y-m-d" a zilei procesate
				$zp = date('Y-n-d', $zu);
				$zptmp = date('Y-m-d', $zu);
				$zp0 = $zptmp . ' 00:00:00';
				
				## verifica daca este concediu
				if(array_key_exists($zp, $concedii)){
					
					## verifica daca exista valori ale concediului
					if($concedii[$zp]['inceput'] != 0){
						
						## in functie de tipul perioadei, afiseaza valori diferite
						switch($concedii[$zp]['tip']){
							## concediu de odihna
							case 1:
								
								$tip = 'CO';
								$diffZilnic = $diffZilnic + 480;
								$liberInit = $liberInit - 480;
								
								break;
							
							## concediu medical
							case 2:
								
								$tip = 'CM';
								
								break;
								
							## delegatie
							case 3:
								
								$tip = 'D';
								
								break;
								
							## eveniment deosebit
							case 4:
								
								$tip = 'CED';
								
								break;								
						}
						
						## calculeaza restul de concediu
						$diferenta = $this->faTimp($liberInit, 'zhm');
						
						## afiseaza concediul
						$data['pontaje'] .= '<tr><td>' . $i . '</td>' .
							'<td>' . $tip . '</td>' .
							'<td>&nbsp;</td>' .
							'<td>&nbsp;</td>' .
							'<td>' . $this->extractData($concedii[$zp]['inceput'], 'hma') . '</td>' .
							'<td>' . $this->extractData($concedii[$zp]['sfarsit'], 'hma') . '</td>' .
							'<td>0</td>' .
							'<td class = "totalc">' . $diferenta['zile'] . '</td>' .
							'<td class = "totalc">' . $diferenta['ore'] . '</td>' .
							'<td class = "totalc">' . $diferenta['minute'] . '</td>' .
							'</tr>';
						
					## in acest caz exista concediu incomplet,
					## verifica pontajul
					} else {
						
						$tmp = $this->verificaPontaje($i, $zp0, $pontari, $liberInit);
						$data['pontaje'] .= $tmp['string'];
						
						## calculeaza timpul ramas
						$liberInit = $liberInit - $tmp['diff'];
						
					}
					
				## daca nu este concediu, verifica pontarile
				} else {
					
					$tmp = $this->verificaPontaje($i, $zp0, $pontari, $liberInit);
					$data['pontaje'] .= $tmp['string'];
					
					## calculeaza timpul ramas
					$liberInit = $liberInit - $tmp['diff'];
					
				}
			}
		}
		
		
		
		
		
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	
	## functie pentru generarea selectorului de perioada
	private function faSelector($luna, $an){
		
		## variabila de transport
		$string = form_open('index.php/pontaje/index/pontaje');
		
		## matricea de transport
		$x = array(
			'ani' => array(),
			'luni' => array()
			);
		
		## adu anii
		$this->db->distinct();
		$this->db->select('year(data) as an');
		$this->db->where('id_ang', $this->session->pontajid);
		$this->db->order_by('data', 'ASC');
		$qry = $this->db->get('pnt_pontari2016');
		
		## extrage rezultatele intr-o matrice de transport
		foreach($qry->result() as $vals){
			$x['ani'][$vals->an] = $vals->an;
		}
		
		## selectorul de ani
		$string .= form_dropdown('an', $x['ani'],$an);
		
		## adu lunile
		$this->db->distinct();
		$this->db->select('month(data) as luna');
		$this->db->where('id_ang', $this->session->pontajid);
		$this->db->order_by('data', 'ASC');
		$qry = $this->db->get('pnt_pontari2016');
		
		## extrage rezultatele intr-o matrice de transport
		foreach($qry->result() as $vals){
			$x['luni'][$vals->luna] = self::$luniText[$vals->luna];
		}
		
		## selectorul de luni
		$string .= form_dropdown('luna', $x['luni'], $luna);
		
		## restul form-ului
		$string .= form_submit('submit','Arata');
		$string .= form_close();
		
		return $string;
	}
	
	## functie pentru aducerea perioadelor initiale
	private function aduConcediiInit($an, $luna){
		
		## variabile initiale
		$minute = 0;
		$ore = 0;
		$totalLiber = 0;
		
		## string-ul si matricea de transport
		$string = '';
		$x = array();
		
		$perioada = $an . $luna;
		$this->db->select('zile, minute, obs');
		$this->db->where('cod_pontaj', $this->session->userid);
		$this->db->where('perioada', $perioada);
		$qry = $this->db->get('pnt_concediu');
		foreach($qry->result() as $vals){
			
			## calculeaza minutele ramase
			$minute = $vals->minute % 60;
			$ore = ($vals->minute - $minute) / 60;
			
			$string .= '<tr><td>' . $an . '</td>' .
				'<td>' . $luna . '</td>' .
				'<td>' . $vals->zile . '</td>' .
				'<td>' . $ore . '</td>' .
				'<td>' . $minute . '</td>' .
				'<td>' . $vals->obs . '</td>' .
				'</tr>';
			
			## adauga la valoarea totalului liber
			$totalLiber = $totalLiber + intval($vals->zile) * 480 + intval($vals->minute);
			
		}
		
		$x['string'] = $string;
		$x['total'] = $totalLiber;
		
		return $x;
		
	}
	
	## functie pentru aducerea perioadelor libere luate
	## aduce doar zilele libere complete iar la medical si delegatii
	## se considera toata ziua libera
	private function aduConcedii($an, $luna){
		
		## zile in luna verificata
		$zData = strtotime($an . '-' . $luna . '-01');
		$zile = date('t', $zData);
		
		## matricea de transport
		$x = array();
		
		## genereaza interogarea
		#$qry = $this->db->select('id_ang, time_out, time_in, tip, tara, obs')->from('liber_perioade')
		$qry = $this->db->select('id_ang, time_out, time_in, tip')->from('liber_perioade')
			->where('id_ang', $this->session->userid)
			->group_start()
				->group_start()
					->where('year(time_out)', $an)
					->where('month(time_out)', $luna)
				->group_end()
				->or_group_start()
					->where('year(time_in)', $an)
					->where('month(time_in)', $luna)
				->group_end()
			->group_end()
			->order_by('time_in','ASC')
			->order_by('tip','ASC')
		->get();
		
		## verifica daca sunt rezultate pentru interogare
		if($qry->num_rows() != 0){
			
			## daca exista rezultate, ia fiecare rezultat pentru procesare
			foreach($qry->result() as $vals){
				#var_dump($vals);
				$timeO = strtotime($vals->time_out);
				$lunaTO = date('m', $timeO);
				$timeI = strtotime($vals->time_in);
				$lunaTI = date('m', $timeI);
				
				## daca luna perioadelor coincid cu luna procesata
				if($luna == $lunaTO && $luna == $lunaTI){
					
					## ziua si ora de inceput
					$ziO = date('j', $timeO);
					$oraO = date('G', $timeO);
					
					## ziua si ora de sfarsit
					$ziI = date('j', $timeI);
					$oraI = date('G', $timeI);
					
				## daca perioada incepe in luna anterioara
				} elseif($luna > $lunaTO && $luna == $lunaTI){
					
					## ziua si ora de inceput
					$ziO = 1;
					$oraO = 8;
					
					## ziua si ora de sfarsit
					$ziI = date('j', $timeI);
					$oraI = date('G', $timeI);
					
				## daca perioada se termina in luna urmatoare
				} elseif($luna == $lunaTO && $luna < $lunaTI){
					
					## ziua si ora de inceput
					$ziO = date('j', $timeO);
					$oraO = date('G', $timeO);
					
					## ziua si ora de sfarsit
					$ziI = $zile;
					$oraI = 17;
					
				## daca perioada incepe si se termina in alte luni
				} else {
					
					## ziua si ora de inceput
					$ziO = 1;
					$oraO = 8;
					
					## ziua si ora de sfarsit
					$ziI = $zile;
					$oraI = 17;
					
				}
				
				## pentru fiecare zi intre datele de inceput si sfarsit, populeaza matricea de transport
				for($i = $ziO; $i <= $ziI; $i++){
					
					## construieste ziua procesata => cheie in matricea de transport
					$ii = $this->corecteazaInt($i);
					$zi = $an . '-' . $luna . '-' . $ii;
					
					## initializeaza sub-matricile de transport
					if(!array_key_exists($zi,$x)){
						$x[$zi] = array();
					}
					
					$j = $i;
					## in functie de tipul perioadei, trebuiesc notate anumite date
					## perioada libera
					if($vals->tip == '1'){
						
						## daca una din perioade nu e 8 respectiv 5
						if($oraO != 8 || $oraI != 17){
							
							## daca zilele coincid
							if($ziO == $ziI){
								
								$x[$zi]['inceput'] = 0;
								$x[$zi]['sfarsit'] = 0;
								$x[$zi]['tip'] = 0;
								
							## daca zilele nu coincid
							} else {
								
								## daca intrarea nu e 8
								if($oraO != 8 && $oraI == 17){
									
									$z = $ziO + 1;
									$z = $this->corecteazaInt($z);
									
									$ziInceput = substr($vals->time_out,0,7) . '-' . $z . ' 08:00:00';
									$ziSfarsit = $vals->time_in;
									
									$j = $i + 1;
									
								## daca iesirea nu e 17
								} elseif($oraO == 8 && $oraI != 17){
									
									$z = $ziI - 1;
									$z = $this->corecteazaInt($z);
									
									$ziInceput = $vals->time_out;
									$ziSfarsit = substr($vals->time_in,0,7) . '-' . $z . ' 17:00:00';
									
									$j = $i - 1;
									
								## pentru restul cazurilor
								## daca nici una dintre ore nu e 8 sau 17
								} else {
									
									$z = $ziO + 1;
									$z = $this->corecteazaInt($z);
									
									$ziInceput = substr($vals->time_out,0,7) . '-' . $z . ' 08:00:00';
									
									$z = $ziI - 1;
									$z = $this->corecteazaInt($z);
									
									$ziSfarsit = substr($vals->time_in,0,7) . '-' . $z . ' 17:00:00';
									
									## ????
									$ji = $i + 1;
									$jo = $i - 1;
									
								}
								
								/*$x[$vals->id_ang][$j]['inceput'] = $ziInceput;
								$x[$vals->id_ang][$j]['sfarsit'] = $ziSfarsit;
								$x[$vals->id_ang][$j]['tip'] = $vals->tip;*/
								$x[$zi]['inceput'] = $ziInceput;
								$x[$zi]['sfarsit'] = $ziSfarsit;
								$x[$zi]['tip'] = $vals->tip;
								
							}
							
							continue;
							
						## ramane "daca ambele ore sunt 8 si 17"
						} else {
							
							$x[$zi]['inceput'] = $vals->time_out;
							$x[$zi]['sfarsit'] = $vals->time_in;
							$x[$zi]['tip'] = $vals->tip;
							
						}
						
					## concediu medical
					} elseif($vals->tip == '2'){
						
						$x[$zi]['inceput'] = $vals->time_out;
						$x[$zi]['sfarsit'] = $vals->time_in;
						$x[$zi]['tip'] = $vals->tip;
						
					## delegatie
					} elseif($vals->tip == '3'){
						
						$x[$zi]['inceput'] = $vals->time_out;
						$x[$zi]['sfarsit'] = $vals->time_in;
						$x[$zi]['tip'] = $vals->tip;
						
					## evenimente deosebite
					} elseif($vals->tip == '4'){
						
						$x[$zi]['inceput'] = $vals->time_out;
						$x[$zi]['sfarsit'] = $vals->time_in;
						$x[$zi]['tip'] = $vals->tip;
						
					}
				}
			}
		}
		
		return $x;
	}
	
	
	## functie pentru extragerea pontarilor pentru angajatul vizitator
	private function aduPontari($luna, $an){
		
		## matricea de transport
		$x = array();
		$i = 0;
		
		## construieste interogarea
		$this->db->select('id_ang, denumire, marca, data, datain, dataout, orar');
		$this->db->where('id_ang', $this->session->pontajid);
		$this->db->where('YEAR(data)', $an);
		$this->db->where('MONTH(data)', $luna);
		$this->db->order_by('data', 'ASC');
		$qry = $this->db->get('pnt_pontari2016');
		
		## extrage rezultatele intr-o matrice de transport
		foreach($qry->result() as $vals){
			
			$x[$vals->data][$i] = array(
				$vals->datain,
				$vals->dataout,
				$vals->orar
			);
			
			$i++;
		}
		
		return $x;
	}
	
	## functie pentru aducerea zilelor libere oficiale
	private function getZileLibere($an){
		
		$x = array();
		
		$this->db->where('YEAR(zi)',$an);
		$qry = $this->db->get('pnt_libere');
		
		foreach($qry->result() as $vals){
			$x[$vals->zi] = array($vals->descriere);
		}
		
		return $x;
	}
	
	
	## functie pentru verificarea zilei
	## daca e weekend, liber sau zi din viitor
	private function verificaZiua($zi, $luna, $an, $zLiber){
		
		## contruieste variabilele de timp
		## ziua in format unix time
		## dupa ora 12 ar trebui sa fie importate pontajele de ziua anterioara
		## astfel incat se poate lua in considerare si ziua anterioara
		$zu = mktime(11, 20, 0, $luna, $zi, $an);
		## pt ziua din saptamana
		$zs = date('w', $zu);
		
		## pentru afisarea cat mai corecta, timpul de referinta pentru "zi din viitor"
		## va fi considerat cu 12 de ore in urma
		$viitor = time() - (12 * 60 * 60);
		
		## adauga un 0 pentru zile "mici"
		if(strlen($zi) == 1){
			$zi = '0' . $zi;
		}
		
		## adauga un 0 pentru luni "mici"
		if(strlen($luna) == 1){
			$luna = '0' . $luna;
		}
		
		## pt zi libera
		$zl = $an . '-' . $luna . '-' . $zi;
		
		## verifica daca data este in weekend sau in viitor
		if($zs == 6 || $zs == 0 || $zu > $viitor){
			$x = 1;
			
		## daca e sarbatoare
		} elseif (array_key_exists($zl, $zLiber)){
			$x = 2;
			
		## daca e zi de procesat
		} else {
			$x = 0;
		}
		
		return $x;
	}
	
	## functie pentru verificarea si afisarea pontajelor
	private function verificaPontaje($zi, $zp0, $pontari, $liber){
		
		## matricea de transport
		$tmp = array(
			'string' => ''
		);
		$prz = 0;
		$diff = 0;
		
		## matricile pentru intrari/iesiri
		$in = array();
		$out = array();
		
		## verifica daca exista pontaj
		if(array_key_exists($zp0, $pontari)){
			
			$x = count($pontari[$zp0]);
			$y = round($x / 2, 0, PHP_ROUND_HALF_UP) + 1;
			
			$tmp['string'] .= '<tr><td class = "liber" rowspan = "' . $y . '">' . $zi . '</td>';
			$tmp['string'] .= '<td rowspan = "' . $y . '">P</td>';
			
			## extrage intrarile si iesirile
			foreach($pontari[$zp0] as $val){
				
				## tipul orarului (aici se ia ultima valoare din ziua procesata)
				$orar = $val[2];
				
				## daca e intrare
				if($val[0] != NULL){
					array_push($in, $val[0]);
				
				## daca e iesire
				} elseif($val[1] != NULL){
					array_push($out, $val[1]);
				}
			}
			
			## ordoneaza valorile din matrici
			sort($in, SORT_STRING);
			sort($out, SORT_STRING);
			
			## extrage prima intrare a zilei
			$firstIn = $this->verTIn($in[0]);
			
			
			## pentru fiecare valoare din matricea de intrare, scrie un rand de valori
			foreach($in as $key => $vals){
				
				## intrarea din pontaj
				$mInAf = $this->extractData($vals, 'hma');
				
				## calculeaza intrarea
				$mIn = $this->extractData($vals, 'mc');
				$timeInCalculat = $this->verificaTIn($mIn);
				
				## verifica daca exista cheie pentru iesire
				if(array_key_exists($key, $out)){
					
					## iesirea din pontaj
					$mOutAf = $this->extractData($out[$key], 'hma');
					
					## iesirea calculata
					$mOut = $this->extractData($out[$key], 'mc');
					$timeOutCalculat = $this->verificaTOut($mOut, $firstIn, $orar);
					
					
				## daca nu exista iesire, afiseaza timpul de intrare => prezenta = 0
				} else {
					
					## iesire = intrare
					$mOutAf = $mInAf;
					$timeOutCalculat['val'] = $timeInCalculat;
				}
				
				## calculeaza iesirea (in functie de orar si intrarea initiala)
				
				
				$prezenta = $timeOutCalculat['val'] - $timeInCalculat;
				
				## verifica daca din timpul de prezenta trebuie scazuta pauza
				if($prezenta > 270){
					$prezenta = $prezenta - 60;
				}
				
				## verifica daca timpul de prezenta total este mai mare de 8 ore
				if($prezenta > 480){
					$prezenta = 480;
				}
				
				
				## calculeaza prezenta totala
				$prz = $prz + $prezenta;
				
				$tmp['string'] .= '<td>' . $mInAf . '</td>';
				$tmp['string'] .= '<td>' . $mOutAf . '</td>';
				$tmp['string'] .= '<td>' . $this->faTimp($timeInCalculat, 'hm') . '</td>';
				$tmp['string'] .= '<td>' . $this->faTimp($timeOutCalculat['val'], 'hm') . '</td>';
				$tmp['string'] .= '<td>' . $this->faTimp($prezenta, 'hm')  . '</td>';
				## de sters
				$tmp['string'] .= '<td colspan = "3">&nbsp;</td></tr>';
				
			}
			
			## calculeaza diferenta zilnica
			$diff = 480 - $prz;
			
			$liber = $liber - $diff;
			
			## extrage valorile de afisat ale concediului ramas
			$liberRamas = $this->faTimp($liber, 'zhm');
			
			## scrie totalul zilei
			$tmp['string'] .= '<td colspan = "4" class = "total">Total:</td>';
			$tmp['string'] .= '<td class = "totalc">' . $this->faTimp($prz, 'hm') . '</td>';
			$tmp['string'] .= '<td class = "totalc">' . $liberRamas['zile'] . '</td>';
			$tmp['string'] .= '<td class = "totalc">' . $liberRamas['ore'] . '</td>';
			$tmp['string'] .= '<td class = "totalc">' . $liberRamas['minute'] . '</td>';
			
			
		## daca nu exista pontari => nemotivat
		} else {
			$tmp['string'] .= '<tr><td class = "liber">' . $zi .
				'</td><td colspan = "9" class = "nemotivat">N (nu exista pontari sau perioade libere)</td></tr>';
		}
		
		$tmp['diff'] = $diff;
		
		return $tmp;
	}
	
	## functie pentru verificarea timpilor de intrare
	private function verificaTIn($tin){
		
		## intrare inainte de ora 8
		if($tin <= 480){
			
			$x = 480;
			
		## intrare intre 8 si 12 (fara 15) SAU intrare dupa ora 13
		## dupa 11:45 se poate considera intrare la ora 13
		} elseif(($tin > 480 && $tin <= 705) || $tin > 780) {
			
			$x = intval((ceil($tin / 15)) * 15);
			
		## intrare intre 11:46 si 13 -> se considera 13
		} elseif($tin > 705 && $tin <= 780) {
			
			$x = 780;
			
		## in cazuri neprevazute
		} else {
			$x = 0;
		}
		
		return $x;
	}
	
	## functie pentru verificarea timpilor de intrare
	private function verificaTOut($tout, $tin, $orar){
		
		## matricea de transport
		$z = array();
		
		## infunctie de tipul orarului si valoarea intrarii, se stabileste valoarea limita a iesirii
		switch($orar){
			case '1':
				## pentru orar normal
				$limita = 1020;
				
			break;
			
			case '2':
				
				## pentru orar special, verifica valoarea primei intrari
				switch(true){
					
					## daca s-a intrat inainte de 8
					case $tin <= 480:
						
						$limita = 1020;
						
					break;
					
					## daca s-a intrat pana la 8:15
					case $tin <= 495:
						
						$limita = 1035;
						
					break;
					
					## daca s-a intrat pana la 8:30
					case $tin <= 510:
						
						## daca iesirea e inainte de 17:30
						## (nu a recuparat jumatatea de ora)
						if($tout >= 1020 && $tout < 1050){
							
							$limita = 1020;
							
						} else {
							
							$limita = 1050;
							
						}
						
					break;
					
					## daca s-a intrat de la 8:30 la 8:45 => exista posibilitatea de recuperare a primelor 30 minute
					case ($tin > 510 && $tin <= 525):
						
						## daca iesirea e dupa 17:30
						if($tout >= 1050){
							
							$limita = 1050;
							
						## daca iesirea e inainte de 17:30
						} else {
							
							$limita = 1020;
							
						}
						
					break;
						
					## daca nu se indeplineste nici una din conditii, limita este normala
					#default:
					case $tin > 525:
						
						$limita = 1020;
						
					break;
					
				}
				
			break;
			
			default:
				
				$limita = 1020;
				
			break;
		}
		
		
		## iesire inainte de ora 12 SAU iesire dupa 13:15, pana la 17:30
		if($tout < 720 || ($tout >= 795 && $tout <= $limita)){
			
			$x = intval((floor($tout / 15)) * 15);
			
		## iesire intre 12 si 13:15
		} elseif($tout >= 720 && $tout < 795) {
			
			$x = 720;
			
		## iesire dupa 17:30
		} elseif($tout > $limita) {
			
			$x = $limita;
			
		## in cazuri neprevazute
		} else {
			$x = 1;
		}
		
		$z['limita'] = $limita;
		$z['val'] = $x;
		
		return $z;
	}
	
	
	## functie pentru transformarea unui numar de 1 caracter intr-un string cu 2 caractere (cu 0 in fata)
	private function corecteazaInt($valoare){
		
		$x = '';
		
		if(strlen($valoare) == 1){
			$x = '0' . $valoare;
		}else{
			$x = $valoare;
		}
		
		return $x;
		
	}
	
	## extrage componentele datei dintr-un string,
	## rezultatul este conform tipul de date ales
	private function extractData($string, $tip){
		
		## matricea de transport
		$x = array();
		
		## transforma string-ul in timestamp
		$tmp = strtotime($string);
		
		## in functie de tipul ales vor rezulta anumite valori
		switch($tip){
			## zi
			case 'z':
				$x = intval(date('j', $tmp));
				break;
				
			## ora, minut
			case 'hm':
				$x['ora'] = intval(date('G', $tmp));
				$x['minut'] = intval(date('i', $tmp));
				break;
				
			## ora si minutul pentru afisare
			case 'hma':
				$x = date('H:i', $tmp);
				break;
				
			## ziua din saptamana (1 - Luni; 7 - Duminica)
			case 'zs':
				$x = intval(date('N', $tmp));
				break;
				
			## numarul de zile din luna
			case 'zl':
				$x = intval(date('t', $tmp));
				break;
				
			## zi, luna, an - pentru afisare
			case 'zla':
				$x = date('Y-m-d', $tmp);
				break;
			
			## total minute pentru calcul
			case 'mc':
				$ora = intval(date('G', $tmp));
				$minut = intval(date('i', $tmp));
				
				$x = $ora * 60 + $minut;
				
				break;
			
		}
		
		return $x;
		
	}
	
	## functie pentru transformarea unui int in string, pentru afisare
	private function faTimp($pont, $tip){
		
		## variabila de transport
		$x = '';
		
		switch($tip){
			## ora si minutele
			case 'hm':
				## afla minutele ramase
				$min = $pont % 60;
				
				## transforma minutele in 2 cifre
				$minute = $this->corecteazaInt($min);
				
				## afla orele intregi
				$ore = ($pont - $min) / 60;
				
				$x = $ore . ':' . $minute;
				
				break;
			
			## zile, ore , minute
			case 'zhm':
				
				$x = array();
				
				## minutele ramase de la zile
				$minuteZile = $pont % 480;
				
				## zilele intregi
				$zile = ($pont - $minuteZile) / 480;
				
				## minute ramase de la ore
				$minute = $minuteZile % 60;
				
				## ore intregi
				$ore = ($minuteZile - $minute) / 60;
				
				$x = array(
					'zile' => $zile,
					'ore' => $ore,
					'minute' => $minute
				);
				
				break;
		}
		
		return $x;
	}
	
	## functie pentru verificarea primului timp de intrare
	private function verTIn($in){
		
		## extrage orele si minutele
		$ora = intval(substr($in,11,2));
		$minut = intval(substr($in,14,2));
		
		## construieste valorile pentru minute
		$time = $ora * 60 + $minut;
		
		return $time;
		
	}
	
}
