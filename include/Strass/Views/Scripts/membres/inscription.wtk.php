<?php

class Scout_Pages_RendererInscription extends Wtk_Pages_Renderer_Form
{
	protected $view;

	function __construct($view, $model)
	{
		parent::__construct($model);
		$this->view = $view;
	}

	function renderFiche($group, $f)
	{
		$f->addParagraph()
			->addFlags('warn')
			->addInline("Un compte utilisateur vous permettra d'accéder aux contacts, ".
				    "au calendrier de votre unité, ".
				    "d'envoyer des photos, de gérer votre unité, etc. ".
				    "**Seuls les membres et anciens membres du groupe sont acceptés.** ".
				    "Les inscriptions sont soumise à une validation par les administrateur.");
		$g = $f->addForm_Fieldset("Informations personnelles");
		$g->addParagraph()
			->addFlags('info')
			->addInline("Décrivez précisément votre état civil, ".
				    "**pas de pseudonyme ni d'abbréviations !**");

		$g->addEntry('fiche/etat-civil/prenom', 32);
		$g->addEntry('fiche/etat-civil/nom', 32);
		$i = $f->getModel()->getInstance('fiche/etat-civil/sexe');
		if ($i->count() > 1)
			$g->addSelect('fiche/etat-civil/sexe', false);
		else
			$g->addHidden('fiche/etat-civil/sexe');
		$g->addDate('fiche/etat-civil/naissance', '%e-%m-%Y');
		$g->addEntry('fiche/etat-civil/situation', 48);

		$g = $f->addForm_Fieldset("Contact");
		$g->addEntry('fiche/contact/adresse', 32, 2);
		$g->addEntry('fiche/contact/fixe', 12);
		$g->addEntry('fiche/contact/portable', 12);
		$g->addEntry('fiche/contact/adelec', 32);

		$g = $f->addForm_Fieldset("Progression");
		$g->addEntry('fiche/progression/origine', 48);

		// éviter les badges, etc.
		$i = $this->view->model->getFormModel()->getInstance('fiche/progression/progression');
		if ($i->count() < 5)
			$g->addSelect('fiche/progression/progression', false);

		$g->addSelect('fiche/progression/formation', false);
		$g->addEntry('fiche/progression/perespi', 24);

		$f->addSection('cotisation', "Côtisation")->addText($this->view->cotisation);
		$f->addSection('envoi', "Envoi")->addText($this->view->envoi);
	}

	function renderCompte($group, $f)
	{
		$g = $f->addChild(new Wtk_Form_Fieldset("Identification"));
		$g->addParagraph()
			->addFlags('info')
			->addInline("Choisissez l'identifiant ou pseudonyme et le code qui vous convient.");
		$g->addEntry('compte/identifiant', 24);
		$g->addPassword('compte/code', 24);
	}

	function renderModeration($group, $f)
	{
		$g = $f->addChild(new Wtk_Form_Fieldset("Message à l'administrateur"));
		$g->addParagraph()
			->addFlags('info')
			->addInline("Présentez-vous afin de faciliter la modération de votre inscription.");
		$i = $g->addEntry('moderation/message', 64, 8);
		$i->useLabel(false);

		try {
			$g = $f->addForm_Fieldset("Votre scoutisme dans notre groupe");
			$g->addParagraph()
				->addFlags('info')
				->addInline("Pour aider à compléter l'historique du groupe, ".
					    "vous pouvez décrire les étapes de votre scoutisme **dans ce groupe** : ".
					    "équipe, patrouille, poste, etc.");
			$g->addTable('moderation/participations',
				     array('unite'	=> array('Entry', 24),
					   'poste'	=> array('Entry', 16),
					   'debut'	=> array('Date', '%m/%Y'),
					   'fin'	=> array('Date', '%m/%Y')));
		}
		catch(Exception $e) {
			$f->removeChild($g);
		}
	}
}

$this->document->addStyleComponents('circulaire');
$s = $this->document->addSection('inscription');
$s->addPages(null, $this->model,
	     new Scout_Pages_RendererInscription($this, $this->model->getFormModel()));
