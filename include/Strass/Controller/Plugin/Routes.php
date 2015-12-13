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
        $p = '([[:alpha:]]+)';
        $f = '(xhtml|ics|vcf|rss|atom|pdf|tex|txt|od[ts]|csv)';
        $vars = array(
            'controller' => array($p, 'unites'),
            'action'     => array($p, 'index'),
            'format'     => array($f, 'html'),
            'annee'      => array('([[:digit:]]{4})', null));

        $pattern = '[%controller%[/%action%][.%format%][/%annee%]*]';
        $route = new Strass_Controller_Router_Route_Uri($vars, $pattern, null);
        $routeur->addRoute('default', $route);

        /* Raccourcis */
        $t = new Unites;
        $slugs = array();
        foreach ($t->fetchAll() as $u)
            array_push($slugs, $u->slug);
        $unite_pattern = '(' . join('|', $slugs) . ')';

        $routeur->addRoute(
            'unites',
            new Strass_Controller_Router_Route_Uri(
                array(
                    'action' => array($p, 'index'),
                    'annee' => array('([[:digit:]]{4})', null),
                    'controller' => array($p, 'unites'),
                    'format' => array($f, 'html'),
                    'unite' => array($unite_pattern, null),
                ),
                '[%unite%[/%controller%[/%action%[.%format%]]][/%annee%]]',
                null)
        );

        /* $request = new Strass_Controller_Request_Http(); */
        /* Orror::kill($routeur->route($request)); */
        /* Orror::kill($request); */
    }
}
