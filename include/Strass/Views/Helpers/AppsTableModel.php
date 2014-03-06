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
      $m = new Wtk_Table_Model('unite_slug',
			       'unite_type',
			       'unite_nom',
			       'unite_lien',
			       'prenom-nom',
			       'role',
			       'accr',
			       'acl',
			       'etape',
			       'fiche',
			       'adelec',
			       'fixe',
			       'portable',
			       'telephone',
			       'adresse',
			       'naissance',
			       'age',
			       'totem',
			       'numero');
    }
    $this->append($m, $apps);
    return $m;
  }

  function append($model, $apps)
  {
    $m = $model;
    $acl = Zend_Registry::get('acl');

    foreach($apps as $app) {
      $individu = $app->findParentIndividus();
      $role = $app->findParentRoles();
      $unite = $app->findParentUnites();

      if ($acl->isAllowed(null, $individu, 'fiche'))
	$url_fiche = $this->view->urlIndividu($individu, 'fiche', 'individus', true);
      else {
	$url_fiche = null;
      }

      $url_unite =$this->view->url(array('controller' => 'unites',
					 'action' => 'index',
					 'unite' => $unite->slug), true);
      $etape = $individu->findParentEtapes();

      // insertion du tuple
      $m->append($unite->slug,
		 $unite->findParentTypesUnite()->slug,
		 $unite->getName(),
		 $url_unite,
		 $individu->getFullname(true, false),
		 $role->slug,
		 $app->getAccronyme(),
		 $role->acl_role,
		 $etape ? $etape->slug : null,
		 $url_fiche,
		 $individu->adelec,
		 wtk_nbsp($individu->fixe),
		 wtk_nbsp($individu->portable),
		 wtk_nbsp($individu->portable ? $individu->portable : $individu->fixe),
		 wtk_nbsp(preg_replace("`\r?\n`", " – ", trim($individu->adresse))),
		 strftime('%d-%m-%Y', strtotime($individu->naissance)),
		 $individu->getAge(),
		 $individu->totem,
		 $individu->numero ? $individu->numero : null
		 );
    }
  }
}