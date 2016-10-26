<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pontaj extends CI_Controller {
	
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
	
	public function index($page = 'pontaj')
	{
		## optiuni de debugging
		$this->output->enable_profiler(TRUE);
		
		##verifica daca exista deja o sesiune pentru utilizator
		if(!isset($this->session->username) ||
			!in_array('pontaj', $this->session->chkMenu)){
			
			## adu datele despre utilizator
			redirect(base_url());
			
		}
		
		## daca nu exista pagina de afisat
		if ( ! file_exists(APPPATH.'views/'.$page.'.php'))
		{
			// Whoops, we don't have a page for that!
			show_404();
		}
		
		/**
		 *  Chemarea functiilor individuale pentru afisarea tabelului de pontaje
		 *  aferent functiei getTabelPontari
		 * 
		 **/
		
		$an = 2016;
		$luna = 10;
		
		## afla numarul de zile din luna selectata
		$dataI = $an . '-' . $luna . '-01';
		$nrZile = date('t', strtotime($dataI));
		$dataSF = $an . '-' . $luna . '-' . $nrZile;
		
		## adu zilele libere oficiale
		$zLiber = $this->getZileLibere($an);
		
		## incepe constructia tabelului
		$data['pontaj'] = '';
		
		## construieste capul de tabel
		$data['pontaj'] .= $this->makeCapTabel($nrZile, $luna, $an, $zLiber);
		
		## adu lista cu angajatii care au pontari in luna selectata
		$ids = $this->getIDs($an, $luna);
		## adu id-urile din program, pentru extragerea perioadelor libere
		$idCI = $this->getIdCI($ids);
		
		## adu concediile initiale
		$concedii = $this->aduConcediiInit($an, $luna);
		
		## adu zilele luate liber
		$zConcedii = $this->aduConcedii($idCI, $an, $luna);
		
		## adu pontarile pentru perioada selectata
		$pontari = $this->aduPontari($ids, $an, $luna);
		
		## adu angajatii cu pontari din nomenclatorul de angajati
		$this->db->select('id, id_pontaj, cod_pontator, nume, prenume');
		$this->db->where_in('id_pontaj', $ids);
		$this->db->order_by('nume', 'ASC');
		$qry = $this->db->get('glb_angajati');
		
		## ia fiecare angajat si extrage datele specifice
		foreach($qry->result() as $valAng){
			
			## pentru fiecare angajat, extrage datele necesare pentru calcul
			## verifica daca exista pontari pentru angajatul procesat
			if(array_key_exists($valAng->id_pontaj, $pontari)){
				## extrage pontarile angajatului procesat
				$pontajAng = $pontari[$valAng->id_pontaj];
			} else {
				$pontajAng = 0;
			}
			
			## verifica daca exista perioade libere pentru angajatul procesat
			if(array_key_exists($valAng->id, $zConcedii)){
				## extrage datele libere ale angajatului procesat
				$liberAng = $zConcedii[$valAng->id];
			} else {
				$liberAng = 0;
			}
			
			## verifica daca exista concediu initial pentru angajatul procesat
			if(array_key_exists($valAng->cod_pontator, $concedii)){
				$zile = $concedii[$valAng->cod_pontator][0];
				$ore = floor($concedii[$valAng->cod_pontator][1] / 60);
				$minute = $concedii[$valAng->cod_pontator][1] - $ore * 60;
				
				$cellTitle = $ore . ':' . $minute;
				
				## valoarea, in minute, a perioadei libere a angajatului
				$concFinal = $zile * 480 + $minute;
				
			## daca nu exista inregistrari, stabileste valorile implicite
			} else {
				$zile = '&nbsp';
				$ore = '&nbsp';
				$minute = '&nbsp';
				$cellTitle = 'nu exista concediu initial';
				$concFinal = 0;
			}
			
			## initializeaza diferenta de pontaj, pentru calcularea zilelor libere
			$diffCalcul = 0;
			
			## scrie coloana de nume si concediu initial
			$data['pontaj'] .= '	<tr>
		<td class = "angajat">' .
			$valAng->id . ' ' .
			$valAng->id_pontaj . ' ' .
			#$valAng->cod_pontator .
			$valAng->nume . ' ' . $valAng->prenume . '</td>
		<td class = "initial" title = "' . $cellTitle . '">' . $zile . "</td>\n";
			
			## proceseaza fiecare zi pentru angajatul actual
			for($i = 1; $i <= $nrZile; $i++){
				
				## verifica daca trebuie procesata ziua sau nu
				$zp = $this->verificaZiua($i, $luna, $an, $zLiber);
				
				## noteaza in paralel si o variabila care sa tina valorile pontajelor ca si text
				$titlu = '';
				
				## initializeaza o variabila pentru diferenta de scazut din concediul total
				$diffZi = 0;
				
				## daca ziua nu trebuie procesata
				if($zp == 1){
					
					$clasa = ' class = "liber"';
					$valoare = '&nbsp;';
					
				## daca ziua trebuie procesata
				} elseif($zp == 0){
					
					## intai trebuie verificat daca exista perioade libere luate
					$valAfisare = $this->validareLibere($liberAng, $i);
					
					switch($valAfisare['validare']){
						## daca exista perioada libera
						case 1:
							$clasa = ' class = "pontat' . $valAfisare['tip'] . ' w3-pale-green"';
							$valoare = $valAfisare['valoare'];
							
							$diffZi = $diffZi + $valAfisare['diff']; /* ulterior */
							
							break;
					
						## daca nu exista perioada libera, continua procesarea pontarilor
						case 0:
							
							$valPontajAf = $this->validarePontaj($pontajAng, $i, $diffCalcul);
							
							$diffCalcul = $valPontajAf['diffCalc'];
							
							switch($valPontajAf['validare']){
								
								## daca nu exista pontari
								case 0:
									$clasa = ' class = "nepontat"';
									$valoare = 'fp';
									
									break;
									
								## daca exista pontari
								case 1:
									
									$clasa = $valPontajAf['clasa'];
									$titlu .= $valPontajAf['titlu'];
									$valoare = $valPontajAf['valoare'];
									
									break;
							}
							
							$diffZi = $diffZi + $valPontajAf['diff'];
							
							break;
					}
					
				}
				
				#$diffCalcul = $diffCalcul + $diffZi;
				
				$concFinal = $concFinal - $diffZi;
				$titlu .= "\ndifZi " . $diffZi;
				$titlu .= "\ndifCalc " . $diffCalcul;
				$titlu .= "\ndifTotal " . $concFinal . '"';
				#var_dump($titlu);
				
				## scrie celula conform datelor calculate
				$data['pontaj'] .= '<td'. $clasa . $titlu . '>' . $valoare . "</td>\n";
				
			}
			
			## transforma concediul final in zile, ore, minute
			$fMinute = $concFinal % 60;
			$fOreTotal = ($concFinal - $fMinute) / 60;
			$fOre = $fOreTotal % 8;
			$fZile = ($fOreTotal - $fOre) / 8;
			
			## scrie ultimele 3 coloane
			$data['pontaj'] .= "<td class = \"diff\">" . $fZile . "</td>\n";
			$data['pontaj'] .= "<td class = \"diff\">" . $fOre . "</td>\n";
			$data['pontaj'] .= "<td class = \"diff\">" . $fMinute . "</td>\n";
			
		}
		
		## inchide tabela
		$data['pontaj'] .= "</table>\n";
		
		/**
		 *  Sfarsitul calculelor aferente pontajului
		 * 
		 */
		
		## stabileste titlul paginii afisate
		$data['title'] = ucfirst($page); // Capitalize the first letter
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## Functii private ##
	
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
	
	## functie pentru construirea capului de tabel
	private function makeCapTabel($nrZile, $luna, $an, $zLiber){
		
		## incepe constructia tabelului
		$string = '<table cellpadding = 0 cellspacing = 0 border = "black solid 1px">
	<tr>
		<th rowspan = "2" class = "angajat">Nume</th>
		<th rowspan = "2" class = "initial">Ci</th>
		<th colspan = "' . $nrZile . '">Zilele lunii</th>
		<th colspan= "3">Concediu ramas</th>
	</tr>
	<tr>
';
		
		## construieste capul de tabel cu numarul de zile din luna selectata
		for($y = 1; $y <= $nrZile; $y++){
			
			## verifica daca ziua e valida
			## daca e weekend, sarbatoare sau in viitor sa poata fi formatata diferit
			$checkZi = $this->verificaZiua($y, $luna, $an, $zLiber);
			
			## daca e zi libera
			if($checkZi == 1){
				$stil = 'class = "liber"';
				
			## daca e zi de procesat
			} elseif ($checkZi == 0){
				$stil = 'class = "pontat"';
			}
			
			$string .= "		<th $stil>$y</th>\n";
		}
		
		$string .= '		<th>zile</th>
		<th>ore</th>
		<th>min</th>
	</tr>';
		
		return $string;
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
		
		## verifica daca data este in weekend sau zi libera
		if($zs == 6 || $zs == 0 || in_array($zl, $zLiber) || $zu > $viitor){
			$x = 1;
		} else {
			$x = 0;
		}
		
		return $x;
	}
	
	## functie pentru aducerea ID-urilor angajatilor care au pontari pe luna procesata
	## id_pontaj
	private function getIDs($an, $luna){
		
		## id-uri care nu trebuiesc luate in considerare
		$idNU = array(0,51,52,57,59);
		
		$this->db->select('distinct(id_ang) as ids');
		$this->db->where('YEAR(data)', $an);
		$this->db->where('MONTH(data)', $luna);
		#$this->db->where('id_ang', 17);
		$this->db->where_not_in('id_ang', $idNU);
		$qry = $this->db->get('pnt_pontari2016');
		
		## genereaza matricea de id-uri
		$ids = array();
		foreach($qry->result() as $vals){
			array_push($ids, $vals->ids);
		}
		
		return $ids;
	}
	
	## functie pentru aducerea ID-urilor principale, in functie de id-urile de pontare
	private function getIdCI($ids){
		
		$x = array();
		
		$this->db->select('id, id_pontaj');
		$this->db->where_in('id_pontaj', $ids);
		$qry = $this->db->get('glb_angajati');
		
		## genereaza matricea de id-uri
		foreach($qry->result() as $vals){
			array_push($x, $vals->id);
		}
		
		return $x;
	}
	
	## functie pentru aducerea concediilor initiale
	private function aduConcediiInit($an, $luna){
		
		## matricea de transport
		$x = array();
		
		$perioada = $an . $luna;
		$this->db->select('cod_pontaj, sum(minute) as m, sum(zile) as z');
		$this->db->where('perioada', $perioada);
		$this->db->group_by('cod_pontaj');
		$qry = $this->db->get('pnt_concediu');
		foreach($qry->result() as $vals){
			
			$x[$vals->cod_pontaj] = array(
				$vals->z,
				$vals->m
			);
		}
		
		return $x;
		
	}
	
	## functie pentru aducerea perioadelor libere luate
	## aduce doar zilele libere complete iar la medical si delegatii
	## se considera toata ziua libera
	private function aduConcedii($ids, $an, $luna){
		
		## matricea de transport
		$x = array();
		
		## genereaza interogarea
		#$qry = $this->db->select('id_ang, time_out, time_in, tip, tara, obs')->from('liber_perioade')
		$qry = $this->db->select('id_ang, time_out, time_in, tip')->from('liber_perioade')
			->where_in('id_ang', $ids)
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
				
				## extrage zilele de inceput si sfarsit ale perioadei
				$ziO = intval(substr($vals->time_out,8,2));
				$oraO = intval(substr($vals->time_out,11,2));
				$ziI = intval(substr($vals->time_in,8,2));
				$oraI = intval(substr($vals->time_in,11,2));
				
				## pentru fiecare zi intre datele de inceput si sfarsit, populeaza matricea de transport
				for($i = $ziO; $i <= $ziI; $i++){
					
					## initializeaza sub-matricile de transport
					if(!array_key_exists($vals->id_ang,$x)){
						$x[$vals->id_ang][$i] = array();
					}
					
					## in functie de tipul perioadei, trebuiesc notate anumite date
					## perioada libera
					if($vals->tip == '1'){
						
						## daca una din perioade nu e 8 respectiv 5
						if($oraO != 8 || $oraI != 17){
							
							## daca zilele coincid
							if($ziO == $ziI){
								
								$x[$vals->id_ang][$i]['inceput'] = 0;
								$x[$vals->id_ang][$i]['sfarsit'] = 0;
								$x[$vals->id_ang][$i]['tip'] = 0;
								
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
									
									$ziSfarsit = substr($vals->time_in,0,7) . '-' . $zi . ' 17:00:00';
									
									$ji = $i + 1;
									$jo = $i - 1;
									
								}
								
								$x[$vals->id_ang][$j]['inceput'] = $ziInceput;
								$x[$vals->id_ang][$j]['sfarsit'] = $ziSfarsit;
								$x[$vals->id_ang][$j]['tip'] = $vals->tip;
								
							}
							
							continue;
							
						## ramane "daca ambele ore sunt 8 si 17"
						} else {
							
							$x[$vals->id_ang][$i]['inceput'] = $vals->time_out;
							$x[$vals->id_ang][$i]['sfarsit'] = $vals->time_in;
							$x[$vals->id_ang][$i]['tip'] = $vals->tip;
							
						}
						
					## concediu medical
					} elseif($vals->tip == '2'){
						
						$x[$vals->id_ang][$i]['inceput'] = $vals->time_out;
						$x[$vals->id_ang][$i]['sfarsit'] = $vals->time_in;
						$x[$vals->id_ang][$i]['tip'] = $vals->tip;
						
					## delegatie
					} elseif($vals->tip == '3'){
						
						$x[$vals->id_ang][$i]['inceput'] = $vals->time_out;
						$x[$vals->id_ang][$i]['sfarsit'] = $vals->time_in;
						$x[$vals->id_ang][$i]['tip'] = $vals->tip;
						
					## evenimente deosebite
					} elseif($vals->tip == '4'){
						
						$x[$vals->id_ang][$i]['inceput'] = $vals->time_out;
						$x[$vals->id_ang][$i]['sfarsit'] = $vals->time_in;
						$x[$vals->id_ang][$i]['tip'] = $vals->tip;
						
					}
				}
			}
		}
		
		return $x;
	}
	
	## functie pentru aducerea pontarilor
	private function aduPontari($ids, $an, $luna){
		
		## matricea de transport
		$x = array();
		
		## contorul pentru matrici
		$i = 1;
		
		$this->db->select('id_ang, denumire, marca, data, datain, dataout, orar');
		$this->db->where_in('id_ang', $ids);
		$this->db->where('YEAR(data)', $an);
		$this->db->where('MONTH(data)', $luna);
		$this->db->order_by('id_ang', 'ASC');
		$this->db->order_by('data', 'ASC');
		$qry = $this->db->get('pnt_pontari2016');
		foreach($qry->result() as $calc){
			
			$x[$calc->id_ang][$i] = array(
				$calc->data,
				$calc->datain,
				$calc->dataout,
				$calc->orar
			);
			
			$i++;
		}
		
		$x[$calc->id_ang][0] = array(
			$calc->denumire,
			$calc->marca
		);
		
		return $x;
		
	}
	
	## functie pentru validarea perioadelor libere fata de ziua procesata
	private function validareLibere($liber, $zi){
		#var_dump($liber);
		## matricea de transport
		$x = array();
		## verifica daca $liber = 0 -> nu s-a generat matrice
		if($liber == 0){
			
			$x['validare'] = 0;
			$x['diff'] = 0;
			
		}elseif(array_key_exists($zi, $liber)){
			
			$x['validare'] = 1;
			$x['tip'] = $liber[$zi]['tip'];
			
			## in functie de tipul perioadei libere, se va afisa un cod corespunzator
			switch($liber[$zi]['tip']){
				## concediu normal
				case '1':
					$x['valoare'] = 'CO';
					$x['diff'] = 480;
					
					break;
					
				## concediu medical
				case '2':
					$x['valoare'] = 'CM';
					$x['diff'] = 0;
					
					break;
					
				## delegatie
				case '3':
					$x['valoare'] = 'D';
					$x['diff'] = 0;
				
					break;
					
				## concediu special
				case '4':
					$x['valoare'] = 'CED';
					$x['diff'] = 0;
				
					break;
					
				## valoare goala
				case '0':
					$x['validare'] = 0;
					$x['diff'] = 0;
					
					break;
			}
			
		## daca nu exista cheia in matrice, trece valori nule (0)
		} else {
			
			$x['validare'] = 0;
			$x['diff'] = 0;
			
		}
		
		return $x;
		
	}
	
	## functie pentru validarea pontajelor fata de ziua procesata
	private function validarePontaj($pontaj, $zi, $diffCalcul){
		
		## matricea de transport
		$x = array();
		## matricea de transport a pontajelor
		$in = array();
		$out = array();
		$orar = array();
		
		$x['titlu'] = ' title = ';
		$x['clasa'] = ' class = ';
		$titlu = '';
		$ttl = '';
		$ttl2 = '';
		$pont = 0;
		
		## scoate numele angajatului din matricea de pontari
		unset($pontaj[0]);
		
		## verifica intai daca $liber are valoarea 0 (nu exista perioade libere)
		if($pontaj == 0){
			
			$x['validare'] = 0;
			
		## daca exista pontaje, prelucreaza-le
		} else {
			
			$x['validare'] = 1;
			
			## ia fiecare pontare si extrage pontarile pentru ziua procesata
			foreach($pontaj as $val){
				
				## extrage ziua pontarii
				$j = intval(substr($val[0],8,2));
				
				## daca ziua pontarii coincide cu ziua procesata
				if($j == $zi){
					
					## populeaza matricile pontajelor
					$x['orar'] = $val[3];
					
					## daca e intrare
					if($val[1] != NULL){
						array_push($in, $val[1]);
						$titlu .= "->" . $val[1] . "\n";
						
					## daca e iesire
					}else{
						array_push($out, $val[2]);
						$titlu .= "<-" . $val[2] . "\n";
						
					}
				}
			}
			
			## sorteaza matricile
			sort($in, SORT_STRING);
			sort($out, SORT_STRING);
			
			## extrage numarul cheilor din matricile valorilor
			$keyIn = count($in);
			$keyOut = count($out);
			
			## verifica daca numarul cheilor este identic
			if($keyIn !== $keyOut){
				
				## daca nu este identic, noteaza celula corespunzator
				$x['clasa'] .= '"incomplet w3-sand"';
				$x['titlu'] .= '"' . $titlu . '"';
				$x['valoare'] = 'I'; ## pontaj incomplet (intrari != iesiri)
				$x['diff'] = 480;
				$diffCalcul = $diffCalcul + 480;
				
			} elseif($keyIn == 0 || $keyOut == 0){
				
				## daca este 0, noteaza celula corespunzator
				$x['clasa'] .= '"conm w3-orange"';
				$x['titlu'] .= '"nu sunt pontari"';
				$x['valoare'] = 'CN'; ## pontaj incomplet (nemotivat)
				$x['diff'] = 480;
				$diffCalcul = $diffCalcul + 480;
				#var_dump($diffCalcul);
			} elseif($keyIn == $keyOut) {
				
				## scoate prima intrare a zilei
				$tIntrare = $this->verTIn($in[0]);
				
				## initializeaza valoarea de afisat
				$tPontat = 0;
				
				## calculeaza pontajul
				for($k = 0; $k < $keyIn; $k++){
					
					## extrage orele si minutele
					$oraI = intval(substr($in[$k],11,2));
					$minutI = intval(substr($in[$k],14,2));
					$oraO = intval(substr($out[$k],11,2));
					$minutO = intval(substr($out[$k],14,2));
					## construieste valorile pentru minute
					$timeIn = $oraI * 60 + $minutI;
					$timeOut = $oraO * 60 + $minutO;
					
					## verifica daca intrarea e inainte de iesire
					if($timeOut < $timeIn){
						
						$x['clasa'] .= '"incomplet"';
						$ttl = '"error1"';
						$x['valoare'] = 'err1'; ## pontaj neordonat
						
					## daca e corect cronologic, continua prelucrarea
					} else {
						
						## variabile de transport
						$cls = '';
						$tin = 0;
						$tout = 0;
						
						## verifica si corecteaza timpul de intrare
						$tin = $this->verificaTIn($timeIn);
						
						## verifica si corecteaza timpul de iesire
						$tout = $this->verificaTOut($timeOut, $tIntrare, $x['orar']);
						
						## calculeaza pontajul
						## daca sunt 2 pontari de la 8 la 17
						if($tin == 480 && $tout['val'] >= $tout['limita']){
							$pont = 480;
							
						## daca sunt 2 pontari in prima jumatate a zilei
						## si 2 pontari in a doua jumatate a zilei
						## se ia in considerare doar a doua parte a zilei
						} elseif($tin == 780 && $tout['val'] >= $tout['limita']){
							
							$pont = $pont + ($tout['limita'] - $tin);
							
						## in cazuri atipice
						## si in cazul in care sunt mai multe pontari,
						## aici se calculeaza pontarile din prima parte a zilei
						} else {
							
							$ttl2 .= $tin . "\n";
							## daca diferenta e mai mare de 4:30 ore, scade o ora
							if(($tout['val'] - $tin) > 270){
								$pont = $pont + ($tout['val'] - $tin) - 60;
							} else {
								$pont = $pont + ($tout['val'] - $tin);
							}
						}
					}
				}
				
				$x['diff'] = 480 - $pont;
				$diffCalcul = $diffCalcul + $x['diff'];
				
				## in functie de valoarea $pont, afiseaza ceea ce trebuie
				## pontaj standard
				if($pont == 480){
					
					$valAfisare = '8';
					$x['clasa'] .= '"pontat w3-pale-green"';
					
				## pontaj mai mic de 8 ore
				} elseif($pont < 480){
					
					## verifica daca s-a adunat o zi din diferente
					if($diffCalcul >= 480){
						
						## zi libera calculata
						$valAfisare = 'CO';
						$x['clasa'] .= '"pontat22 w3-dark-grey"';
						
						## reseteaza valoarea diferentei de calcul
						$diffCalcul = $diffCalcul - 480;
						
					} else {
						
						## zi libera calculata
						$valAfisare = '8';
						$x['clasa'] .= '"pontat11"';
						
					}
					
				## alte cazuri
				} else {
					
					$valAfisare = '?';
					$x['clasa'] .= '"pontat33"';
					
				}
				
				#$valAfisare = $this->faTimp($pont);
				$ttl2 = $this->faTimp($pont);
				#$ttl = '"' . $titlu . '"';
				$ttl = '"' . $titlu . $ttl2;
				
				$x['valoare'] = $valAfisare;
				
			}
			
			$x['titlu'] .= $ttl;
				
		}
		
		$x['diffCalc'] = $diffCalcul;
		
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
	
	## functie pentru verificarea timpilor de intrare
	private function verificaTIn($tin){
		
		## intrare inainte de ora 8
		if($tin <= 480){
			
			$x = 480;
			
		## intrare intre 8 si 12 (fara 15) SAU intrare dupa ora 13
		## dupa 11:45 se poate considera intrare la ora 13
		} elseif(($tin > 480 && $tin <= 705) || $tin > 780) {
			
			$x = intval((ceil($tin / 15)) * 15);
			
		/*## intrare dupa ora 13
		} elseif($time > 780) {
			
			$x = (ceil($time / 15)) * 15;*/
			
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
					
					/*## daca s-a intrat pana la 8:30
					case $tin <= 510:
						
						$limita = 1050;
						
					break;*/
					
					## daca nu se indeplineste nici una din conditii, limita este normala
					default:
						
						$limita = 1050;
						
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
	
	## functie pentru transformarea unui int in string, pentru afisare
	private function faTimp($pont){
		
		## afla minutele ramase
		$min = $pont % 60;
		
		## transforma minutele in 2 cifre
		$minute = $this->corecteazaInt($min);
		/*if(strlen($min) == 1){
			$minute = '0' . $min;
		}else{
			$minute = $min;
		}*/
		
		## afla orele intregi
		$ore = ($pont - $min) / 60;
		
		$x = $ore . ':' . $minute;
		
		return $x;
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
	
	
}
