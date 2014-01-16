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
      $m = new Wtk_Table_Model(array('unite_slug' => null,
				     'unite_type' => null,
				     'unite_nom' => null,
				     'unite_lien' => null,
				     'prenom-nom' => 'Nom',
				     'role'		=> 'Rôle',
				     'accr'		=> null,
				     'progression'	=> 'Progression',
				     'fiche'		=> null,
				     // contact
				     'adelec'		=> 'Adélec',
				     'fixe'		=> 'Fixe',
				     'portable'	=> 'Portaaieauble',
				     'telephone' => 'Téléphone',
				     'adresse'	=> 'Adresse',
				     // infos perso
				     'naissance'	=> 'Naissance',
				     'age'		=> 'Âge',
				     // progression,
				     'totem'		=> 'Totem',
				     'etape'		=> 'Étape',
				     'numero'		=> 'N°'));
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
      $unite = $app->findParentUnites();
      $prog = $individu->getProgression($this->view->annee);
      if ($prog)
	$etape = $prog->findParentEtape();

      if ($acl->isAllowed($ind, $individu, 'fiche'))
	$url_fiche = $this->view->urlIndividu($individu, 'fiche', 'individus', true);
      else {
	$url_fiche = null;
      }

      $url_unite =$this->view->url(array('controller' => 'unites',
					 'action' => 'index',
					 'unite' => $unite->slug), true);

      // insertion du tuple
      $m->append($unite->slug,
		 $unite->findParentTypesUnite()->slug,
		 wtk_ucfirst($unite->getName()),
		 $url_unite,
		 $individu->getFullname(true, false),
		 $role->acl_role,
		 $role->getAccronyme(),
		 $prog ? $prog->etape : null,
		 $url_fiche,
		 // contact
		 $individu->adelec,
		 wtk_nbsp($individu->fixe),
		 wtk_nbsp($individu->portable),
		 wtk_nbsp($individu->portable ? $individu->portable : $individu->fixe),
		 wtk_nbsp(preg_replace("`\r?\n`", " – ", trim($individu->adresse))),
		 // infos
		 strftime('%d-%m-%Y', strtotime($individu->naissance)),
		 $individu->getAge(),
		 $individu->totem,
		 isset($etape) ? $etape->titre : '',
		 $individu->numero ? $individu->numero : null
		 );
    }
  }
}