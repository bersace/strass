<?php

require_once 'Strass/Journaux.php';
require_once 'Strass/Unites.php';

/**
 * Cette fonction return une valeur remarquable d'une série. Elle
 * répartie la série en @div intervalles consécutives et stockes les
 * bornes dans un tableau. Elle retourne la position @pos de ce
 * tableau. Si @vals est définie, on prend la valeur dans @vals à la
 * clef calculée.
 *
 * Par défaut, elle calcule simplement a médiane : on divise en deux
 * intervalle et on prend la valeur du milieu (1 est au milieu de
 * (0,1,2)).
 */
function mediane($serie, $div = 2, $pos = 1, $vals = array())
{
	$serie = array_values($serie);
	$c = count($serie);
	$i = $pos * (($c - 1) / $div);
	//   Orror::dump($div.", ".$pos." => ".$i, $serie, $serie[$i], $vals);
	if (round($i) == $i) {
		if ($vals) {
			return $vals[$serie[$i]];
		}
		else {
			return $serie[$i];
		}
	}
	else {
		$r = round($i);
		if (!is_int($serie[$r]) && $vals) {
			return ($vals[$serie[$r - 1]] + $vals[$serie[$r]]) / 2;
		}
		else {
			return ($serie[$r - 1] + $serie[$r]) / 2;
		}
	}
}

class JournauxController extends Strass_Controller_Action
{
	function indexAction()
	{
		$this->metas(array('DC.Title' => "Gazette des unités",
				   'DC.Subject' => 'journaux,journal,gazette'));
		$journaux = new Journaux();
		$journaux = $journaux->fetchAll();
		if ($journaux->count() == 1) {
			$j = $journaux->current();
			$this->redirectSimple('lire', 'journaux', null,
					      array('journal' => $j->id));
		}
		$this->view->journaux = $journaux;
		$this->branche->append('Journaux');
	}

	function fonderAction()
	{
		// tests
		$u = $this->_helper->Unite();
		$tu = $u->findParentTypesUnite();
		if ($tu->isTerminale() && $tu->age_min < 12)
			throw new Strass_Controller_Action_Exception("Impossible de créer un journal d'unité ".
								    "pour ".$u->getFullName());

		$this->assert(null, $u, 'fonder-journal',
			      "Vous n'avez pas le droit de fonder le journal de cette unité");

		// métier
		$m = new Wtk_Form_Model('fonder-journal');
		$i = $m->addString('nom', "Nom");
		$m->addConstraintRequired($i);
		$i = $m->addTable('rubriques', "Rubriques",
				  array('nom' => array('String', "Nom")));
		// rubriques proposées
		$i->addRow(array('nom' => "Éditoriaux"));      // incontournable
		// un champ vide, au cas où le client ne supporte pas la
		// javascript, qu'il puisse ajouter au moins deux rubriques :)
		$i->addRow(array('nom' => ""));
		$m->addNewSubmission('fonder', "Fonder");


		// vue
		$this->view->model = $m;
		$this->view->unite = $u;
		$this->metas(array('DC.Title'	=> "Fonder le journal de ".$u->getFullName(),
				   'DC.Subject' => 'journaux,journal,gazette'));

		
		// interprétation
		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				// fonder le journal
				$journaux = new Journaux();
				$id = wtk_strtoid($m->get('nom'));
				$data = array('nom' => $m->get('nom'),
					      'id' => $id, 'unite' => $u->id);
				$journaux->insert($data);

				// ajouter les rubriques
				$rubs = $m->get('rubriques');
				$rubriques = new Rubriques();
				foreach($rubs as $rub) {
					if ($rub['nom']) {
						$data = array('id' => wtk_strtoid($rub['nom']),
							      'nom' => $rub['nom'], 'journal' => $id);
						$rubriques->insert($data);
					}
				}

				$j = $journaux->find($id)->current();
				$this->_helper->Log("Nouveau journal d'unité", array($j, $u),
						    $this->_helper->Url('lire', 'journaux', array('journal' => $id)),
						    (string)$j);
				$db->commit();
				$this->redirectSimple('lire', 'journaux', null,
						      array('journal' => $id));

			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}
	}

	/* lire un journal = lister derniers articles et rubriques */
	function lireAction()
	{
		$articles = new Articles();

		$this->view->journal = $j = $this->_helper->Journal();
		$this->metas(array('DC.Title' => wtk_ucfirst($j->nom),
				   'DC.Subject' => 'journaux,journal,gazette'));
		$this->formats('rss', 'atom');

		$this->view->rubriques = $r = $j->findRubriques();
		$p = $this->_getParam('page');
		$p = $p ? $p : 1;
		$s = $articles->select()->where('public IS NOT NULL')->order('date DESC');
		$this->view->articles = $j->findArticles($s);
		$this->view->current = $p;

		$config = new Strass_Config_Php('strass');

		// ÉDITORIAL
		$db = $articles->getAdapter();
		$where = array('public IS NOT NULL', "date > datetime('now', '-1 year')");
		$where['rubrique = ?'] = $config->site->rubrique;
		$where['id = ?'] = $j->id;

		$s = $articles->select()->order('date DESC');
		foreach($where as $clause => $arg) {
			if (is_int($clause)) {
				$clause = $arg;
				$arg = null;
			}
			$s->where($clause, $arg);
		}
		$this->view->editorial = $e = $articles->fetchAll($s)->current();


		// RUBRIQUES
		if ($p < 2) {
			// on comptes les articles par rubriques.
			$rubsc = array();
			$t = new Articles();
			$db = $t->getAdapter();
			foreach($r as $rub) {
				$rubsc[$rub->id] =
					$t->countRows($db->quoteInto('rubrique = ?', $rub->id).
						      ' AND '.$db->quoteInto('journal = ?',
									     $j->id).
						      ' AND public IS NOT NULL');
			}

			if ($p < 2) {
				// statistiques des rubriques
				$serie = array();
				$trc = $rubsc;
				asort($trc);
				foreach($trc as $id => $c) {
					$serie = array_merge($serie, array_fill(0, $c + 1, $id));
				}
				$bornes = array();
				$range = range(0, 4);
				$classes = array('xs', 's', 'm', 'l', 'xl');
				foreach($range as $i) {
					$bornes[$classes[$i]] = mediane($serie, 5, $i, $trc);
				}

				$rubscl = array();
				foreach($r as $rub) {
					foreach(array_reverse($classes) as $c) {
						if ($rubsc[$rub->id] >= $bornes[$c]) {
							$rubscl[$rub->id] = $c;
							break;
						}
					}
				}

				$this->view->rubscl = $rubscl;
				$this->view->rubsc = $rubsc;
			}
		}


		$this->actions->append("Écrire un article",
				       array('action' => 'ecrire',
					     'journal' => $j->id),
				       array(Zend_Registry::get('user'), $j));
		$this->actions->append("Modifier",
				       array('action' => 'modifier',
					     'journal' => $j->id),
				       array(Zend_Registry::get('user'), $j));
		$brouillons = $j->findArticles('public IS NULL');
		if ($brouillons->count()) {
			$this->actions->append("Brouillons",
					       array('action' => 'brouillons',
						     'journal' => $j->id),
					       array(Zend_Registry::get('user'), $j));
			
		}
	}

	function brouillonsAction()
	{
		$this->view->journal = $j = $this->_helper->Journal();
		$this->assert(null, $j, 'publier',
			      "Vous n'avez pas le droit de publier des brouillons");
		$this->metas(array('DC.Title' => "Brouillons – ".$j->nom,
				   'DC.Subject' => 'journaux,journal,gazette,brouillons'));
		$b = $j->findArticles('public IS NULL');
		$this->view->current = $this->_getParam('page');
		$this->view->brouillons = $b;
		$this->formats('rss', 'atom');
	}

	function ecrireAction()
	{
		// init
		$j = $this->_helper->Journal();
		$this->assert(null, $j, 'ecrire',
			      "Vous n'avez pas le droit d'écrire un nouvel article dans ce journal");

		$this->metas(array('DC.Title' => "Écrire un article – ".$j->nom,
				   'DC.Subject' => 'journaux,journal,gazette'));
		$publier = $this->assert(null, $j, 'publier');
		$this->view->rubrique = $r = $this->_helper->Rubrique(false);

		// métier
		$m = new Wtk_Form_Model('ecrire');
		$rubs = $j->findRubriques();
		$enum = array();
		foreach($rubs as $rub)
			$enum[$rub->id] = $rub->nom;

		$selected = $r ? $r->id : key($enum);
		$m->addEnum('rubrique', "Rubrique", $selected, $enum);
		$i = $m->addString('titre', "Titre");
		$m->addConstraintRequired($i);
		if ($publier)
			$m->addEnum('public', 'Publication', null, array(null => 'Brouillon',
									 1 => 'Publier'));

		$m->addString('boulet', "Boulet");
		$i = $m->addString('article', "Article");
		$m->addConstraintRequired($i);
		$t = $m->addTable('images', "Images",
				  array('image' => array('File', "Image"),
					'nom' => array('String', "Renommer en")),
				  false);
		$t->addRow();
		$m->addNewSubmission('poster', "Poster");

		if ($m->validate()) {
			$db = Zend_Registry::get('db');
			$db->beginTransaction();
			try {
				$data = $m->get();
				unset($data['images']);
				$data+=array('public' => null);

				$ind = Zend_Registry::get('user');
				$data = array_merge($data,
						    array('id' => wtk_strtoid($m->get('titre')),
							  'journal' => $j->id,
							  'auteur' => $ind->id,
							  'date' => strftime('%Y-%m-%d'),
							  'heure' => strftime('%H:%M')));

				$articles = new Articles();
				$k = $articles->insert($data);
				$a = $articles->find($k['id'], $k['date'],
						     $k['journal'])->current();

				// stocker les images
				$tables = $m->getInstance('images');
				$dossier = $a->getDossier();
				if (!is_readable($dossier))
					mkdir($dossier, 0755, true);

				foreach($tables as $row) {
					$if = $row->getChild('image');
					if ($if->isUploaded()) {
						$nom = $row->get('nom');
						$fichier =
							$dossier.($nom ? $nom : $if->getBasename());
						if (!move_uploaded_file($if->getTempFilename(), $fichier)) {
							throw new Strass_Controller_Action_Exception
								("Impossible de récupérer l'image ".$if->getBasename());
						}
					}
				}

				// envoi d'un courriel aux admis si besoin.
				if (!$this->assert(null, $j, 'publier')) {
					$mail = new Strass_Mail("Nouvel article : ".$data['titre']);
					// envoi à tous les chefs
					$u = $j->findParentUnites();
					$apps = $u->getApps();
					foreach($apps as $app) {
						$ind = $app->findParentIndividus();
						if ($ind->adelec)
							$mail->addBcc($ind->adelec,
								      $ind->getFullName(false, false));
					}

					// article
					$d = $mail->getDocument();
					$d->addText("L'article suivant a été posté dans ".$j->nom.". ".
						    "Vous êtes conviés à la modérer.");
					$s = $d->addSection(null, $data['titre']);
					$s->addText($data['boulet']);
					$s->addText($data['article']);
					$l = $d->addList();
					$l->addItem()->addLink($this->_helper->Url('editer', 'journaux',
										   array('article' => $data['id'])),
							       "Éditer ou publier cet article");
					$mail->send();
				}

				$this->_helper->Log("Nouvel article", array($j),
						    $this->_helper->Url('consulter', 'journaux', null,
									array('journal' => $j->id,
									      'date' => $data['date'],
									      'article' => $data['id'])),
						    (string)$data['titre']);


				$db->commit();
				$this->redirectSimple('consulter', 'journaux', null,
						      array('journal' => $j->id,
							    'date' => $data['date'],
							    'article' => $data['id']));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		// vue
		$this->view->journal = $j;
		$this->view->model = $m;
	}

	function consulterAction()
	{
		$this->view->journal = $j = $this->_helper->Journal();
		$this->view->article = $a = $this->_helper->Article();
		$this->view->rubrique = $r = $a->findParentRubriques();
		$this->view->auteur = $a->findParentIndividus();

		$this->branche->insert(-1,
				       $r->nom,
				       array('controller'=> 'journaux',
					     'action'	=> 'lister',
					     'journal'	=> $j->id,
					     'rubrique' => $r->id),
				       array(),
				       true);

		$this->metas(array('DC.Title' => $a->titre,
				   'DC.Subject' => 'journaux,journal,gazette'));


		$this->actions->append("Éditer cet article",
				       array('action' => 'editer'),
				       array(Zend_Registry::get('user'), $a));
		$this->actions->append("Supprimer cet article",
				       array('action' => 'supprimer'),
				       array(Zend_Registry::get('user'), $a));
		$this->formats('odt');
	}


	function editerAction()
	{
		$a = $this->_helper->Article();
		$j = $this->_helper->Journal();

		$this->metas(array('DC.Title' => "Éditer ".$a->titre,
				   'DC.Subject' => 'journaux,journal,gazette'));

		// modifier l'auteur
		$super = $this->assert(null, $j, 'moderer');
		$publier = $this->assert(null, $j, 'publier');

		$this->assert(null, $a, 'editer', "Vous n'avez pas le droit d'éditer cet article");

		$m = new Wtk_Form_Model('editer');
		$i = $m->addString('titre', "Titre", $a->titre);
		$m->addConstraintRequired($i);

		if ($super) {
			// membres de l'unité
			$u = $j->findParentUnites();
			$apps = $u->getApps(null, true);
			$enum = array();
			foreach($apps as $app)
				$enum[$app->individu] = $app->findParentIndividus()->getFullname(true, false);

			// admins
			$select = Zend_Registry::get('db')->select();
			$select->distinct()
				->from('individus')
				->join('membership',
				       'membership.username = individus.username'.
				       ' AND '.
				       "membership.groupname = 'admins'",
				       array());
			$ti = new Individus();
			$is = $ti->fetchAll($select);
			foreach($is as $i)
				$enum[$i->id] = $i->getFullname(true, false);
			ksort($enum);
			$m->addEnum('auteur', 'Auteur', $a->auteur, $enum);
		}

		// rubrique
		$rubs = $j->findRubriques();
		$enum = array();
		foreach($rubs as $rub) {
			$enum[$rub->id] = $rub->nom;
		}

		$m->addEnum('rubrique', "Rubrique", $a->rubrique, $enum);

		$m->addEnum('public', 'Publication', intval($a->public),
			    array(null => 'Brouillon',
				  1 => 'Publier'));

		$m->addString('boulet', "Boulet", $a->boulet);
		$i = $m->addString('article', "Article", $a->article);

		// fichiers existant : renommer / supprimer
		$i = $m->addTable('images', "Images",
				  array('id' => array('String'),
					'nom' => array('String', 'Nom')), false);
		$fichiers = $a->getImages();

		foreach($fichiers as $fichier)
			$i->addRow(basename($fichier), basename($fichier));

		// envoyer des nouveaux fichiers.
		$t = $m->addTable('nvimgs', "Images",
				  array('image' => array('File', "Image"),
					'nom' => array('String', "Renommer en")),
				  false);
		$t->addRow();

		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$db = $a->getTable()->getAdapter();
			$db->beginTransaction();
			try {
				$ks = array('titre', 'rubrique', 'boulet', 'article', 'public');
				if ($super)
					$ks[] = 'auteur';

				foreach($ks as $k)
					$a->$k = $m->get($k);

				$a->id = wtk_strtoid($a->titre);
				$a->date = strftime('%Y-%m-%d');
				$a->heure = strftime('%H:%M:%s');
				$a->save();

				// renomer les fichiers
				$dossier = $a->getDossier().'/';
				$done = array();
				foreach($m->images as $image) {
					$done[] = $image['nom'];
					if ($image['id'] == $image['nom'])
						continue;

					if (rename($dossier.$images['id'], $dossier.$images['nom']))
						continue;

					throw new Exception("Impossible de renommer le fichier ".
							    $images['id']." en ".$images['nom']);
				}

				// supprimer
				foreach($fichiers as $fichier) {
					if (!in_array(basename($fichier), $done)) {
						if (!unlink($fichier))
							throw new Exception("Impossible de supprimer le fichier ".
									    basename($fichier));
					}
				}

				// récupérer les fichiers
				foreach($t as $i) {
					$if = $i->getChild('image');
					if ($if->isUploaded()) {
						$tmp = $if->getTempFilename();
						$nom = $i->get('nom');
						$fichier = $nom ? $nom : $if->getBasename();
						if (!move_uploaded_file($tmp, $dossier.$fichier)) {
							throw new Exception ("Impossible de récupérer le fichier ".$if->getBasename());
						}
					}
				}

				$this->_helper->Log("Article édité", array($a, $a->findParentJournaux()),
						    $this->_helper->Url('consulter', 'journaux', null,
									array('journal' => $a->journal,
									      'date' => $a->date,
									      'article' => $a->id)),
						    (string)$a);

				$db->commit();
				$this->redirectSimple('consulter', 'journaux', null,
						      array('journal' => $a->journal,
							    'date' => $a->date,
							    'article' => $a->id));

			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->journal = $j;
		$this->view->article = $a;
		$this->view->model = $m;
	}

	function supprimerAction()
	{
		$a = $this->_helper->Article();
		$this->assert(null, $a, 'supprimer');
		$this->metas(array('DC.Title' => "Supprimer ".$a->titre,
				   'DC.Subject' => 'journaux,journal,gazette'));

		$m = new Wtk_Form_Model('supprimer');
		$m->addBool('confirmer',
			    "Je confirme la suppression de l'article ".$a->titre.".",
			    false);
		$m->addNewSubmission('continuer', "Continuer");

		if ($m->validate()) {
			if ($m->confirmer) {
				$db = $a->getTable()->getAdapter();
				$db->beginTransaction();
				try {
					$label = (string)$a;
					$auteur = $a->findParentIndividus();
					$j = $a->findParentJournaux();
					$a->delete();
					$this->_helper->Log("Article supprimé",
							    array($j, 'article' => $label, 'auteur' => $auteur),
							    $this->_helper->Url('lire', 'journaux', null,
										array('journal' => $this->_getParam('journal'))),
							    (string)$j);
					$db->commit();
				}
				catch(Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
			$this->redirectSimple('lire', 'journaux', null,
					      array('journal' => $this->_getParam('journal')));
		}

		$this->view->article = $a;
		$this->view->model = $m;
	}


	// lister une rubrique
	function listerAction()
	{
		$this->view->journal = $j = $this->_helper->Journal();
		$this->view->rubrique = $r = $this->_helper->Rubrique();

		$this->metas(array('DC.Title' => wtk_ucfirst($j->nom).' : '.$r->nom,
				   'DC.Title.alternative' => $r->nom." – ".wtk_ucfirst($j->nom),
				   'DC.Subject' => 'journaux,journal,gazette'));

		$this->view->articles = $articles = $this->view->rubrique->findArticles('public IS NOT NULL');
		$this->view->current = $this->_getParam('page');

		$this->actions->append("Écrire un article",
				       array('action' => 'ecrire',
					     'journal' => $j->id,
					     'rubrique' => $r->id),
				       array(null, $j));
		$this->formats('rss', 'atom');
	}


	function modifierAction()
	{
		$j = $this->_helper->Journal();
		$this->assert(null, $j, 'modifier',
			      "Vous n'avez pas le droit de modifier ce journal");
		$this->metas(array('DC.Title' => "Modifier ".wtk_ucfirst($j->nom),
				   'DC.Subject' => 'journaux,journal,gazette'));


		$m = new Wtk_Form_Model('journal');
		$i = $m->addString('nom', 'Nom', $j->nom);
		$m->addConstraintRequired($i);
		$i = $m->addTable('rubriques', 'Rubriques existantes',
				  array('id' => array('String'),
					'nom' => array('String', 'Nom')), false);

		$rubs = $j->findRubriques();
		foreach($rubs as $rub)
			$i->addRow($rub->id, $rub->nom, false);

		$i->addRow();

		$m->addNewSubmission('enregistrer', 'Enregistrer');

		if ($m->validate()) {
			$js = $j->getTable();
			$db = $js->getAdapter();
			$db->beginTransaction();
			try {
				$j->nom = $m->get('nom');
				$j->id = wtk_strtoid($j->nom);
				$j->save();

				$tr = new Rubriques();
				// supprimer les rubriques
				$rubs = $j->findRubriques();

				$done = array();
				foreach($m->get('rubriques') as $rub) {
					// mise à jour
					if ($rub['id'] && !isset($done[$rub['id']])) {	
						$r = $tr->find($rub['id'], $j->id)->current();
						$r->nom = $rub['nom'];
						$r->id = $rub['id'] = wtk_strtoid($rub['nom']);
						$r->save();
					}
					// ajout
					else if ($rub['nom']) {
						$rub['id'] = wtk_strtoid($rub['nom']);
						$tr->insert(array('id' => $rub['id'],
								  'nom' => $rub['nom'],
								  'journal' => $j->id));
					}

					if ($rub['id'])
						$done[$rub['id']] = true;
				}

				// suppression
				$rs = $j->findRubriques();
				foreach($rs as $r)
					if (!isset($done[$r->id]))
						$r->delete();

				$db->commit();
				$this->redirectSimple('lire', 'journaux', null,
						      array('journal' => $j->id));
			}
			catch(Exception $e) {
				$db->rollBack();
				throw $e;
			}
		}

		$this->view->journal = $j;
		$this->view->model = $m;
	}
}


class Strass_Page_RendererArticle extends Wtk_Pages_Renderer
{
	protected $view;
	protected $root;

	function __construct($view, $root)
	{
		$href = $view->url(array('journal' => $view->journal->id,
					 'page' => '%i'));
		parent::__construct(urldecode($href),
				    true,
				    array('previous' => "Précédents",
					  'next' => "Suivants"));
		$this->view = $view;
		$this->root = $root;
	}

	function renderContainer()
	{
		return new Wtk_Container();;
	}

	function render($id, $article, $root)
	{
		$s = $root->addSection($article->id, $this->view->lienArticle($article));
		$s->level = $this->root->level+1; // chaîner avec la section parente.

		// n'affiche le boulet ou à défaut le début de l'article.
		$boulet = $article->boulet ? $article->boulet : wtk_first_words($article->article);
		$t = $s->addText($boulet);
		$tw = $t->getTextWiki();
		$tw->setRenderConf('Xhtml', 'image', 'base', $article->getDossier());

		$s->addParagraph(new Wtk_Inline("//in// "),
				 $this->view->lienRubrique($article->findParentRubriques()),
				 ", ",
				 $this->view->signature($article), ".")->addFlags('signature');
		$lien = $this->view->lienArticle($article, 'Lire la suite…');
		$s->addParagraph($lien)->addFlags('suite');
	}
}

