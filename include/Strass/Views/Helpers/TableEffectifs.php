<?php

class Strass_View_Helper_TableEffectifs
{
  protected	$view;
  public static $headers = array('accr' => '',
				 'prenom-nom' => 'Nom',
				 'adelec' => 'Adélec',
				 'fixe' => 'Fixe',
				 'portable' => 'Portable',
				 'adresse' => 'Adresse',
				 'naissance' => 'Naissance',
				 );

  public function setView($view)
  {
    $this->view = $view;
    $this->view->document->addStyleComponents('effectifs');
  }

  function tableEffectifs($unite, $model, $fiches=true, $colonnes=array())
  {
    $t = new Wtk_Table($model, true, array('acl', 'role', 'etape'));

    $type = $unite->findParentTypesUnite();
    $t->addFlags('contacts', $type->slug);
    if ($type->virtuelle)
      $t->addFlags('virtuelle');
    if ($type->isTerminale())
      $t->addFlags('terminale');
    else
      $t->addFlags('parente');

    $colonnes = array_merge(array('accr', 'prenom-nom'), $colonnes);
    $headers = array();
    foreach($colonnes as $colonne) {
      $headers[$colonne] = $this::$headers[$colonne];
    }

    // rendu des colonnes
    foreach($headers as $id => $titre) {
      switch($id) {
      case 'adelec':
	$l = new Wtk_Table_CellRenderer_Link('href', 'adelec',
					     'label', 'adelec');
	$l->setUrlFormat('mailto:%s');
	$t->addColumn(new Wtk_Table_Column($titre, $l));
	break;
      case 'fixe':
      case 'portable':
	if ($fiches) {
	  $renderer = new Wtk_Table_CellRenderer_Link('href', $id, 'label', $id);
	  $renderer->setUrlFormat('tel:%s');
	  $t->addColumn(new Wtk_Table_Column($titre, $renderer));
	}
	break;
      case 'prenom-nom':
	if ($fiches) {
	  $t->addColumn(new Wtk_Table_Column($titre,
					     new Wtk_Table_CellRenderer_Link('href', 'fiche',
									     'label', 'prenom-nom',
									     'flags', array('role', 'etape'))));
	}
	break;
      default:
	$t->addColumn(new Wtk_Table_Column($titre, new Wtk_Table_CellRenderer_Text('text', $id)));
	break;
      }
    }

    if (!$unite->isTerminale())
      $t->setCategoryColumn('unite_nom',
			    new Wtk_Table_CellRenderer_Link('href', 'unite_lien',
							    'label', 'unite_nom'),
			    array('unite_slug', 'unite_type'));

    return $t;
  }
}