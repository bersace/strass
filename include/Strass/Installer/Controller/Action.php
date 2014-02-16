<?php

class Strass_Installer_Controller_Action extends Zend_Controller_Action
{
  /*
   * Configure une vue en fournissant deux variables par défaut :
   * document (widget racine) et page (modèle de la page courante).
   */
  function initView ()
  {
    $view = new Zend_View;
    $view->setEncoding('UTF-8');
    $fc = Zend_Controller_Front::getInstance();
    $cs = $fc->getControllerDirectory();
    $prefixes = array_keys($cs);
    foreach ($prefixes as $prefix) {
      $viewdir = dirname(dirname(__FILE__)).'/Views/';
      $view->addScriptPath($viewdir.'Scripts');
      $view->addHelperPath($viewdir.'Helpers', $prefix.'_View_Helper_');
    }

    $view->page = new Strass_Page(new Wtk_Metas(array('DC.Title'	=> 'Installation',
						      'DC.Language'	=> 'fr',
						      'site' => 'Strass')));

    return $view;
  }

  /*
   * Travestissement de Zend_Controller_Action::getViewScript
   * pour utilise $controller comme controlleur si défini.
   */
  function getViewScript($action, $controller = false)
  {
    $script = parent::getViewScript($action, is_string($controller) ? true : $controller);

    if (is_string($controller))
      $script = wtk_strtoid($controller).DIRECTORY_SEPARATOR.$script;

    return $script;
  }

  public function preDispatch ()
  {
    $this->view = $this->initView();
  }

  /*
   * Version allégée de render qui n'ajoute rien à la requête (cf
   * postDispatch()).
   */
  public function render($action = null, $controller = false)
  {
    $script = $this->getViewScript($action, $controller);
    $this->view->document->addStyleComponents('install');
    return $this->view->render($script);
  }

  /*
   * Génère effectivement le rendu.
   */
  function postDispatch ()
  {
    $page = $this->view->page;
    $page->addFormat(Strass_Format::factory('Xhtml'));
    $page->selectFormat('xhtml');
    $this->viewSuffix = $page->format->viewSuffix.'.php';

    $response = $this->getResponse();
    // rendu effectif du document.
    $output = $page->format->render($this);
    $response->setHeader('Content-Type', $page->format->mimeType.'; charset=utf-8');
    $response->appendBody($output);
  }
}
