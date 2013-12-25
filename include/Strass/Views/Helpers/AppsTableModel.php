<?php

class Strass_View_Helper_AppsTableModel
{
	protected	$view;

	public function setView($view)
	{
		$this->view = $view;
	}

	function appsTableModel($apps, $m = null)
	{
		if (!$m) {
			$m = new Wtk_Table_Model(array('prenom-nom'	=> 'Nom',
						       'role'		=> 'Rôle',
						       'accr'		=> null,
						       'progression'	=> 'Progression',
						       'profil'		=> null,
						       // contact
						       'adelec'		=> 'Adélec',
						       'fixe'		=> 'Fixe',
						       'portable'	=> 'Portable',
						       'telephone'	=> 'Téléphone',
						       'adresse'	=> 'Adresse',
						       // infos perso
						       'naissance'	=> 'Naissance',
						       'age'		=> 'Âge',
						       'situation'	=> 'Situation',
						       'origine'	=> 'Origine',
						       // progression,
						       'perespi'	=> 'Père spi',
						       'parrain'	=> 'Parrain',
						       'totem'		=> 'Totem',
						       'etape'		=> 'Étape',
						       'numero'		=> 'N°',
						       // formation
						       'cep1'		=> 'CEP1', // branche ????
						       'cep2'		=> 'CEP2',
						       'formation'	=> 'Formation'));
		}
		$this->append($m, $apps);
		return $m;
	}

	function append($model, $apps)
	{
		$m = $model;
		$acl = Zend_Registry::get('acl');
		$ind = Zend_Registry::get('user');
		foreach($apps as $app) {
			$individu = $app->findParentIndividus();
			$vn = $individu->voirNom();
			$role = $app->findParentRoles();
			$prog = $individu->getProgression($this->view->annee);
			if ($prog)
				$etape = $prog->findParentEtape();

			// collecte des formations
			$fs = $individu->findFormation();
			$formations = array('cep1' => "",
					    'cep2' => "",
					    'autre' => array());
			foreach ($fs as $f) {
				$d = $f->findParentDiplomes();

				// recp, reap ?
				if (strpos($d->id, 'cep') !== false) {
					// ajouter la branche ?
					$formations[$d->id] = wtk_fdate($f->date);
				}
				else {
					array_push($formations['autre'], $d->accr);
				}
			}
			if (count($formations['autre']) > 1) {
				$dernière =  array_pop($formations['autre']);
				$formations['autre'] = implode(', ', $formations['autre']);
				if ($dernière) {
					$formations['autre'] = implode(' et ', array($formations['autre'], $dernière));
				}
			}
			else {
				$formations['autre'] = implode('',$formations['autre']);
			}


			// insertion du tuple
			$m->append($individu->getFullname(true, false),
				   $role->id,
				   $role->getAccronyme(),
				   $prog ? $prog->etape : null,
				   $acl->isAllowed($ind, $individu, 'voir') ? $this->view->urlIndividu($individu) : null,
				   // contact
				   $individu->adelec,
				   wtk_nbsp($individu->fixe),
				   wtk_nbsp($individu->portable),
				   wtk_nbsp($individu->portable ? $individu->portable : $individu->fixe),
				   wtk_nbsp(preg_replace("`\r?\n`", " – ", trim($individu->adresse))),
				   // infos
				   strftime('%d-%m-%Y', strtotime($individu->naissance)),
				   $individu->getAge(),
				   wtk_nbsp($individu->situation),
				   wtk_nbsp($individu->origine),
				   // progression,
				   wtk_nbsp($individu->perespi),
				   wtk_nbsp($individu->parrain),
				   $individu->totem,
				   isset($etape) ? $etape->titre : '',
				   $individu->numero ? $individu->numero : null,
				   // formation,
				   $formations['cep1'],
				   $formations['cep2'],
				   $formations['autre']
				   );
		}
	}
}