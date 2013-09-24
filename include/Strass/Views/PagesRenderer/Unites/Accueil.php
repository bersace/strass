<?php

class Strass_Views_PagesRenderer_Unites_Accueil extends Wtk_Pages_Renderer
{
	protected $_view;

	function __construct($view)
	{
		$this->_view = $view;
		parent::__construct($view->url(array('unite' => $view->unite->id,
						     'annee' => '%i')),
				    true, null);
	}

	function render($annee, $data, $s)
	{
		$v = $this->_view;
		extract($data);

		// présentation
		$ss = $s->addSection('presentation');
		if ($image)
			$ss->addImage($image, "Image d'unité", wtk_ucfirst($unite->getName()));

		if ($texte)
			$ss->addText($texte);

		// effectifs
		$ss = $s->addSection('effectifs',
				     $v->lienUnite($unite, "Effectifs",
						   array('action' => 'contacts',
							 'annee' => $annee)))->addFlags('effectifs');
		$t = $ss->addTable($v->appsTableModel($apps),
				   false, array('role', 'progression'))
			->addFlags($unite->type);

		$t->addColumn(new Wtk_Table_Column("Poste", new Wtk_Table_CellRenderer_Text('text', 'accr')));
		if ($v->profils) {
			$t->addColumn(new Wtk_Table_Column("Nom", new Wtk_Table_CellRenderer_Link('href', 'profil',
												  'label', 'prenom-nom')));
			$t->addColumn(new Wtk_Table_Column("Téléphone", new Wtk_Table_CellRenderer_Text('text', 'telephone')));
		}
		else {
			$t->addColumn(new Wtk_Table_Column("Nom", new Wtk_Table_CellRenderer_Text('text', 'prenom-nom')));
		}

		// sous-unités
		if (count($sousunites)) {
			$ss = $s->addSection('sousunites',
					     $v->lienUnite($unite, wtk_ucfirst($soustype),
							   array('action' => 'sousunites',
								 'annee' => $annee)));
			$l = $ss->addList();
			foreach($sousunites as $sousunite)
				$l->addItem($v->lienUnite($sousunite, null,
							  array('annee' => $annee)));
		}

		// prochaines activités
		if (count($activites)) {
			$ss = $s->addSection('prochacts',
					     $v->lienUnite($unite, "Prochaines activités",
							   array('controller' => 'activites',
								 'action' => 'calendrier')));
			$l = $ss->addList();
			foreach ($activites as $activite)
				$l->addItem($v->lienActivite($activite));
		}

		// rapports
		if (count($rapports)) {
			$ss = $s->addSection('rapports',
					     $v->lienUnite($unite, "Rapports",
							   array('controller' => 'activites',
								 'action' => 'rapports',
								 'annee' => $annee)));
			$l = $ss->addList();
			foreach ($rapports as $rapport) {
				$i = $l->addItem($v->lienActivite($rapport->findParentActivites(),
								  null, 'rapport', 'activites',
								  $rapport->findParentUnites()));
			}
		}

		// photos
		if (count($photos)) {
			$ss = $s->addSection('photos',
					     $v->lien(array('controller' => 'photos',
							    'annee'	=> $annee,),
						      "Photos",
						      true));
			$l = $ss->addList();
			foreach ($photos as $photo) {
				$i = $l->addItem($v->vignettePhoto($photo));
				$i->addFlags('vignette');
			}
		}
	}
}

