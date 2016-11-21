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
		#$this->output->enable_profiler(TRUE);
		
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
		
		## adu anul si luna de afisat
		switch($this->input->post('an')){
			
			case NULL:
				
				$an = date('Y');
				$luna = date('n');
				
			break;
			
			default:
				
				$an = $this->input->post('an');
				$luna = $this->input->post('luna');
				
			break;
			
		}
		
		## genereaza selectorul pentru istoric
		$data['selector'] = $this->faSelector($luna, $an, 'index.php/pontaj/index/pontaj');
		
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
		
		$data['export'] = '<!--';
		
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
			
			## string-ul pentru link-ul angajatului
			$linkAng = array(
				'index.php',
				'pontaj',
				'individual',
				$valAng->id,
				$valAng->id_pontaj
			);
				#$valAng->cod_pontator
			
			## scrie coloana de nume si concediu initial
			$data['pontaj'] .= '	<tr>
		<td class = "angajat">
			<a href = "' . site_url($linkAng) . '">' .
			$valAng->nume . ' ' . $valAng->prenume . '</a></td>
		<td class = "initial" title = "' . $cellTitle . '">' . $zile . "</td>\n";
			
			## proceseaza fiecare zi pentru angajatul actual
			for($i = 1; $i <= $nrZile; $i++){
				
				## verifica daca trebuie procesata ziua sau nu
				$zp = $this->verificaZiua($i, $luna, $an, $zLiber);
				
				## noteaza in paralel si o variabila care sa tina valorile pontajelor ca si text
				$titlu = '';
				
				## initializeaza o variabila pentru diferenta de scazut din concediul total
				$diffZi = 0;
				
				## daca ziua nu trebuie procesata (weekend)
				if($zp == 1){
					
					$clasa = ' class = "liber"';
					$valoare = '&nbsp;';
					
				## daca ziua nu trebuie procesata (sarbatoare)
				} elseif($zp == 2){
					
					$clasa = ' class = "w3-sand"';
					$valoare = '&nbsp;';
					
				## daca ziua trebuie procesata
				} elseif($zp == 0){
					
					## intai trebuie verificat daca exista perioade libere luate
					$valAfisare = $this->validareLibere($liberAng, $i);
					
					switch($valAfisare['validare']){
						## daca exista perioada libera
						case 1:
							$clasa = ' class = "pontat' . $valAfisare['tip'] . $valAfisare['clasa'];
							$valoare = $valAfisare['valoare'];
							
							$diffZi = $diffZi + $valAfisare['diff']; /* ulterior */
							
							break;
					
						## daca nu exista perioada libera, continua procesarea pontarilor
						case 0:
							
							$valPontajAf = $this->validarePontaj($pontajAng, $i, $diffCalcul, $valAng->id);
							
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
							
							$data['export'] .= $valPontajAf['export'];
							
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
			
			$data['export'] .= "\n";
			
		}
		
		## inchide export-ul de date
		$data['export'] .= "\n-->";
		
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
	
	## functie care arata pontarile individuale ale unui angajat
	public function individual($idAng, $idPontaj){
		
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
		
		$page = 'individual';
		
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
		
		## string-ul pentru selector
		$stringSelect = 'index.php/pontaj/individual/' . $idAng . '/' . $idPontaj;
		## genereaza selectorul pentru istoric
		$data['selector'] = $this->faSelector($luna, $an, $stringSelect);
		
		## construieste link-ul de adaugare perioade initiale
		## matricea cu id-uri acceptate
		$idsAdaugare = array(17, 31, 50, 52);
		$linkAdaugare = '';
		
		if(in_array($this->session->userid, $idsAdaugare)){
			
			## construieste an.luna pentru care se va adauga perioada
			$perioada = $an . $luna;
			
			## elementele link-ului
			$elemente = array(
				'index.php',
				'pontaj',
				'add',
				$idAng,
				$idPontaj,
				$perioada
			);
			
			$linkAdaugare .= "\n\t<br />\n\t" .
				'<a href = "' .
				site_url($elemente) .
				'">Adauga perioada initiala</a>' .
				"\n<br />";
			
			
			
		} else {
			$linkAdaugare = '';
		}
		
		$data['selector'] .= $linkAdaugare;
		
		
		## construieste tabelul cu valorile initiale
		$liberInit = $this->aduConcediiInitAng($an, $luna, $idAng);
		$data['tabelInit'] = $liberInit['string'];
		
		## adu zilele libere oficiale
		$zLiber = $this->getZileLibere($an);
		
		## adu concediile angajatului
		$concedii = $this->aduConcediiAng($an, $luna, $idAng);
		
		## adu datele de pontare ale angajatului curent
		$pontari = $this->aduPontariAng($luna, $an, $idPontaj);
		
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
					'</td><td colspan = "9" class = "liber">&nbsp;</td></tr>' . "\n";
				
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
						$diferenta = $this->faTimpAng($liberInit, 'zhm');
						
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
							"</tr>\n";
						
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
		
		
		
		
		
		## stabileste titlul paginii afisate
		$data['title'] = $pontari[0];
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
		
	}
	
	## functie pentru adaugarea unei perioade initiale
	public function add($idAng, $idPontaj, $perioada){
		
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
		
		$page = 'individualAdd';
		
		## adu numele angajatului
		$this->db->select('nume, prenume');
		$this->db->where('id', $idAng);
		$qry = $this->db->get('glb_angajati');
		$row = $qry->row();
		$angajat = $row->nume . ' ' . $row->prenume;
		
		## construieste formularul de introdus perioada
		$formLink = 'index.php/pontaj/done';
		$data['formular'] = form_open($formLink);
		
		## valorile ascunse
		$data['formular'] .= form_hidden('id', $idAng);
		$data['formular'] .= form_hidden('id_pontaj', $idPontaj);
		$data['formular'] .= form_hidden('perioada', $perioada);
		
		## afiseaza denumirile campurilor
		$data['formular'] .= '<div class = "labelAdd">' . form_label('Zile', 'zile') .
			"</div>\n" . '<div class = "labelAdd">' . form_label('Ore', 'ore') .
			"</div>\n" . '<div class = "labelAdd">' . form_label('Minute', 'minute') .
			"</div>\n" . '<br class = "clear" />';
		
		## datele pentru campuri
		$camp = array(
			'name'	=> 'zile',
			'id'	=> 'zile',
			'size'	=> '5'
		);
		
		## datele de introdus
		$data['formular'] .= form_input($camp);
		
		$camp['name'] = 'ore';
		$camp['id'] = 'ore';
		$data['formular'] .= form_input($camp);
		
		$camp['name'] = 'minute';
		$camp['id'] = 'minute';
		$data['formular'] .= form_input($camp);
		$data['formular'] .= "<br /><br />\n";
		
		$camp = array(
			'name'	=> 'obs',
			'id'	=> 'obs',
			'rows'	=> '3',
			'cols'	=> '40'
		);
		
		$data['formular'] .= form_label('Observatii', 'obs', 'class = "labelAddTxt"');
		$data['formular'] .= '<br class = "clear" />';
		$data['formular'] .= form_textarea($camp);
		$data['formular'] .= "<br /><br />\n";
		
		## restul form-ului
		$data['formular'] .= form_submit('submit','Adauga');
		$data['formular'] .= form_close();
		
		## stabileste titlul paginii afisate
		$data['title'] = 'Adauga perioada pentru ' . $angajat;
		
		## incarca pagina de afisat
		$this->load->view('header', $data);
		$this->load->view($page);
		$this->load->view('footer');
	}
	
	public function done(){
		
		## optiuni de debugging
		#$this->output->enable_profiler(TRUE);
		
		## incarca ajutorul de matrici
		$this->load->helper('array');
		
		## matricea pentru link-ul de redirectionare
		$link = array(
			'index.php',
			'pontaj',
			'individual',
			$this->input->post('id'),
			$this->input->post('id_pontaj')
		);
		
		## daca cel putin un camp are o valoare diferita de NULL
		if($this->input->post('zile') != '' ||
		$this->input->post('ore') != '' ||
		$this->input->post('minute') != ''){
			
			## extrage datele din POST
			$y = elements(array('zile', 'ore', 'minute'), $_POST, 'deff');
			
			$minute = intval($y['ore']) * 60 + intval($y['minute']);
			
			## populeaza matricea pentru insert
			$x = array(
				'perioada'		=> $this->input->post('perioada'),
				'cod_pontaj'	=> $this->input->post('id'),
				'zile'			=> intval($y['zile']),
				'minute'		=> $minute,
				'obs'			=> $this->input->post('obs'),
				'operator'		=> $this->session->username,
				'opmac'			=> $this->session->mac,
				'opdata'		=> date("Y-m-d H:i")
			);
			
			## introdu datele in DB
			$this->db->insert('pnt_concediu', $x);
			
		}
		
		## trimite utilizatorul la pagina individuala de pontari
		redirect(site_url($link));
		
	}
	
	## Functii private ##
	
	## functie pentru generarea selectorului de perioada
	private function faSelector($luna, $an, $string){
		
		## variabila de transport
		#$string = form_open('index.php/pontaj/index/pontaj');
		$string = form_open($string);
		
		## matricea de transport
		$x = array(
			'ani' => array(),
			'luni' => array()
			);
		
		## adu anii
		$this->db->distinct();
		$this->db->select('year(data) as an');
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
	
	## functie pentru aducerea ID-urilor angajatilor care au pontari pe luna procesata
	## id_pontaj
	private function getIDs($an, $luna){
		
		## id-uri care nu trebuiesc luate in considerare
		$idNU = array(0,51,52,57,76);
		
		$this->db->select('distinct(id_ang) as ids');
		$this->db->where('YEAR(data)', $an);
		$this->db->where('MONTH(data)', $luna);
		#$this->db->where('id_ang', 9);
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
		
		## zile in luna verificata
		$zData = strtotime($an . '-' . $luna . '-01');
		$zile = date('t', $zData);
		
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
				
				## extrage zilele de inceput si sfarsit ale perioadei
				/*$ziO = intval(substr($vals->time_out,8,2));
				$oraO = intval(substr($vals->time_out,11,2));
				$ziI = intval(substr($vals->time_in,8,2));
				$oraI = intval(substr($vals->time_in,11,2));*/
				
				## pentru fiecare zi intre datele de inceput si sfarsit, populeaza matricea de transport
				for($i = $ziO; $i <= $ziI; $i++){
					
					## initializeaza sub-matricile de transport
					if(!array_key_exists($vals->id_ang,$x)){
						$x[$vals->id_ang][$i] = array();
					}
					
					$j = $i;
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
									
									$ziSfarsit = substr($vals->time_in,0,7) . '-' . $z . ' 17:00:00';
									
									## ????
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
					$x['clasa'] = ' w3-pale-green"';
					
					break;
					
				## concediu medical
				case '2':
					$x['valoare'] = 'CM';
					$x['diff'] = 0;
					$x['clasa'] = ' w3-pale-green"';
					
					break;
					
				## delegatie
				case '3':
					$x['valoare'] = 'D';
					$x['diff'] = 0;
					$x['clasa'] = ' w3-light-blue"';
				
					break;
					
				## concediu special
				case '4':
					$x['valoare'] = 'CED';
					$x['diff'] = 0;
					$x['clasa'] = ' w3-pale-green"';
				
					break;
					
				## valoare goala
				case '0':
					$x['validare'] = 0;
					$x['diff'] = 0;
					$x['clasa'] = ' w3-pale-green"';
					
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
	private function validarePontaj($pontaj, $zi, $diffCalcul, $idAng){
		
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
			
			$x['export'] = '';
			## exporta intrarile si iesirile (viitor export excel)
			foreach($in as $cheie => $valIn){
				
				## verifica daca exista si iesire pentru intrarea procesata
				if(array_key_exists($cheie,$out)){
					$valOut = $out[$cheie];
				} else {
					$valOut = 0;
				}
				
				$x['export'] .= $idAng . ',' . $valIn . ',' . $valOut . "\n";
			}
			
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
			
		## intrare intre 11:46 si 13 -> se considera 13
		} elseif($tin > 705 && $tin <= 780) {
			
			$x = 780;
			
		## in cazuri neprevazute
		} else {
			$x = 0;
		}
		
		return $x;
	}
	
	## functie pentru verificarea timpilor de iesire
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
	
	
	/* Individual */
	
	## functie pentru aducerea concediilor unui angajat (selectat)
	private function aduConcediiInitAng($an, $luna, $idPontaj){
		## variabile initiale
		$minute = 0;
		$ore = 0;
		$totalLiber = 0;
		
		## string-ul si matricea de transport
		$string = '';
		$x = array();
		
		$perioada = $an . $luna;
		$this->db->select('zile, minute, obs');
		$this->db->where('cod_pontaj', $idPontaj);
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
	private function aduConcediiAng($an, $luna, $idPontaj){
		
		## zile in luna verificata
		$zData = strtotime($an . '-' . $luna . '-01');
		$zile = date('t', $zData);
		
		## matricea de transport
		$x = array();
		
		## genereaza interogarea
		#$qry = $this->db->select('id_ang, time_out, time_in, tip, tara, obs')->from('liber_perioade')
		$qry = $this->db->select('id_ang, time_out, time_in, tip')->from('liber_perioade')
			->where('id_ang', $idPontaj)
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
	private function aduPontariAng($luna, $an, $idPontaj){
		
		## matricea de transport
		$x = array();
		$i = 0;
		$nume = '';
		
		## construieste interogarea
		$this->db->select('id_ang, denumire, marca, data, datain, dataout, orar');
		$this->db->where('id_ang', $idPontaj);
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
			
			$nume = $vals->denumire;
		}
		
		## extrage denumirea angajatului
		array_push($x, $nume);
		
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
			
			$tmp['string'] .= "\n" . '<tr>' . "\n\t" . '<td rowspan = "' . $y . '">' . $zi . "</td>\n\t";
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
			#$firstIn = $in[0];
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
				
				$tmp['string'] .= "\n\t<td>" . $mInAf . '</td>';
				$tmp['string'] .= "\n\t<td>" . $mOutAf . '</td>';
				$tmp['string'] .= "\n\t<td>" . $this->faTimpAng($timeInCalculat, 'hm') . '</td>';
				$tmp['string'] .= "\n\t<td>" . $this->faTimpAng($timeOutCalculat['val'], 'hm') . '</td>';
				$tmp['string'] .= "\n\t<td>" . $this->faTimpAng($prezenta, 'hm')  . "</td>\n\t";
				## de sters
				$tmp['string'] .= '<td colspan = "3">&nbsp;</td>' . "\n" . '</tr>' . "\n";
				
			}
			
			## calculeaza diferenta zilnica
			$diff = 480 - $prz;
			
			$liber = $liber - $diff;
			
			## extrage valorile de afisat ale concediului ramas
			$liberRamas = $this->faTimpAng($liber, 'zhm');
			
			## scrie totalul zilei
			$tmp['string'] .= "<tr>\n\t" . '<td colspan = "4" class = "total">Total:</td>';
			$tmp['string'] .= "\n\t" . '<td class = "totalc">' . $this->faTimpAng($prz, 'hm') . '</td>';
			$tmp['string'] .= "\n\t" . '<td class = "totalc">' . $liberRamas['zile'] . '</td>';
			$tmp['string'] .= "\n\t" . '<td class = "totalc">' . $liberRamas['ore'] . '</td>';
			$tmp['string'] .= "\n\t" . '<td class = "totalc">' . $liberRamas['minute'] . "</td>\n</tr>";
			
			
		## daca nu exista pontari => nemotivat
		} else {
			$tmp['string'] .= '<tr><td class = "liber">' . $zi .
				'</td><td colspan = "9" class = "nemotivat">N (nu exista pontari sau perioade libere)</td></tr>';
		}
		
		$tmp['diff'] = $diff;
		
		return $tmp;
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
	private function faTimpAng($pont, $tip){
		
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
	
	/* Adaugare init */
	
	
	
}
