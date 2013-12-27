<?php

class Strass_View_Helper_TableEffectifs
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
		$this->view->document->addStyleComponents('effectifs');
	}

	function tableEffectifs($model, $fiches = true, $type = 'contacts', $colonnes = array(), $supplementaires = array())
	{
		$t = new Wtk_Table($model, true, array('role', 'progression'));

		if ($fiches) {
			$acl = Zend_Registry::get('acl');
			$moi = Zend_Registry::get('user');

			if ($type == 'participants')
				$colonnes['numero'] = "N°";
			else
				$colonnes = array_merge(array('accr' => '',
							      'prenom-nom'=> 'Nom'),
							$colonnes);

			if ($type == 'contacts') {
				$colonnes['adelec'] = 'Adélec';
				$colonnes['fixe'] = 'Fixe';
				$colonnes['portable'] = 'Portable';
				$colonnes['adresse'] = 'Adresse';
			}

			if ($type == 'participants') {
				$colonnes['adresse'] = 'Adresse';
				$colonnes['naissance'] = 'Naissance';
				$colonnes['accr'] = 'Fonction';
			}
			if ($type == 'maitrise') {
				$colonnes['numero'] = 'N°';
				$colonnes['naissance'] = 'Naissance';
				$colonnes['adresse'] = 'Adresse';
				$colonnes['telephone'] = 'Téléphone';
				$colonnes['adelec'] = 'Adélec';
				$colonnes['cep1'] = 'CEP1';
				$colonnes['cep2'] = 'CEP2';
				$colonnes['formation'] = 'Formation';
			}
		}
		else {
			$colonnes = array('accr'=> '',
					  'prenom-nom' => 'Nom');
		}

		// rendu des colonnes
		foreach($colonnes as $id => $titre) {
			switch($id) {
			case 'adelec':
				$l = new Wtk_Table_CellRenderer_Link('href', 'adelec',
								     'label', 'adelec');
				$l->setUrlFormat('mailto:%s');
				$t->addColumn(new Wtk_Table_Column($titre, $l));
				break;
			case 'prenom-nom':
				if ($fiches) {
					$t->addColumn(new Wtk_Table_Column($titre,
									   new Wtk_Table_CellRenderer_Link('href', 'fiche',
														     'label', 'prenom-nom')));
					break;
				}
			default:
				$t->addColumn(new Wtk_Table_Column($titre, new Wtk_Table_CellRenderer_Text('text', $id)));
				break;
			}
		}

		// colonnes supplémentaires, vides.
		if ($type == 'liste') {
			foreach ($supplementaires as $supplementaire) {
				$t->addColumn(new Wtk_Table_Column($supplementaire['nom'], new Wtk_Table_CellRenderer_Text()));
			}
		}

		return $t;
	}
}