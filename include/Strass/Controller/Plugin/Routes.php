<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Plugin_Routes extends Zend_Controller_Plugin_Abstract
{

    function routeStartup()
    {
        $fc = Zend_Controller_Front::getInstance();
        $routeur = $fc->getRouter();
        $routeur->removeDefaultRoutes();

        /* Routeur générique */
        $p = '[[:alpha:]]+';
        $f = 'atom|csv|ics|rss|txt|vcf';
        $vars = array(
            'controller' => array($p, 'unites'),
            'action'     => array($p, 'index'),
            'format'     => array($f, 'html'),
            'annee'      => array('[[:digit:]]{4}', null));

        $pattern = '[%controller%[/%action%][.%format%][/%annee%]*]';
        $route = new Strass_Controller_Router_Route_Uri($vars, $pattern, null);
        $routeur->addRoute('default', $route);

        /* Raccourcis */
        $t = new Unites;
        $slugs = array();
        foreach ($t->fetchAll() as $u)
            array_push($slugs, $u->slug);
        $unite_pattern = join('|', $slugs);

        $routeur->addRoute(
            'alias',
            $route = new Strass_Controller_Router_Route_Alias(
                array(
                    'annee' => array('[12][0-9]]{3}', null),
                    'action' => array(null, 'index'),
                    'controller' => array(null, 'index'),
                    'format' => array($f, 'html'),
                    'unite' => array($unite_pattern, null),
                ),
                '%unite%[/%__alias__%[.%format%][/%annee%]]*',
                array(
                    'default' => array('unites', 'index'),
                    'archives' => array('unites', 'archives'),
                    'calendrier' => array('activites', 'calendrier'),
                    'editer' => array('unites', 'editer'),
                    'effectifs' => array('unites', 'effectifs'),
                    'fermer' => array('unites', 'fermer'),
                    'fonder' => array('unites', 'fonder'),
                    'inscrire' => array('unites', 'inscrire'),
                    'parametres' => array('unites', 'parametres'),
                    'photos' => array('photos', 'index'),
                    'documents' => array('documents', 'index'),
                ))
        );

        /* Orror::dump($route); */
        /* $request = $fc->getRequest(); */
        /* Orror::kill($routeur->route($request)); */
    }
}
