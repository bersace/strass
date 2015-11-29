<?php

class Strass {
    static $install_filename = 'private/INSTALLED';

    static function getPrefix()
    {
        return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
    }

    static function isInstalled()
    {
        return file_exists(self::$install_filename);
    }

    static function setInstalled()
    {
        return file_put_contents(self::$install_filename, strftime('%Y-%m-%d %H-%M'));
    }

    static function onMaintenance()
    {
        return file_exists('maintenance.html');
    }

    static function showMaintenance()
    {
        header('HTTP/1.0 503 Service Unavailable');
        readfile('maintenance.html');
        exit(0);
    }

    static function onDevelopment()
    {
        return getenv('STRASS_MODE') == 'devel';
    }

    static function bootstrapStage1()
    {
        /* Initialise le minimum pour introspecter l'installation. */
        umask(0022);
        date_default_timezone_set('Europe/Paris');
        setlocale(LC_TIME, 'fr', 'fr_FR.utf8', 'fr_FR', 'fr_FR@euro', 'fr-FR', 'fra');

        $root = getenv('STRASS_ROOT') or null;
        if ($root) {
            if (!file_exists($root))
                mkdir($root.'data/', 0770, true);
            chdir($root);
        }
    }

    static function bootstrapStage2()
    {
        /* Initialise une installation existante (avec Wtk, styles, etc.). */
        require_once 'Wtk.php';

        Wtk::init();

        Wtk_Document_Style::$path = array(
            Strass::getPrefix() . 'static/styles/',
            'data/styles/'
        );
        Wtk_Document_Style::$basestyle = Strass::getPrefix() . 'static/styles/strass/';
    }

    static function bootstrap()
    {
        /* Initialisation complète de Strass */
        self::bootstrapStage1();
        self::bootstrapStage2();
    }

    static function main()
    {
        self::bootstrapStage1();

        /* On affiche la page de maintenance avant d'initialiser Wtk et
         * strass. Ainsi, seuls index.php et ce fichier sont requis pour
         * afficher la page de maintenance. Pas d'inclusion de Zend, Wtk,
         * etc. */
        if (self::onMaintenance())
            return self::showMaintenance();

        self::bootstrapStage2();

        if (!self::isInstalled())
            return Strass_Installer::main();

        try {
            self::run();
        }
        catch (Exception $e) {
            self::saveSession();
            try {
                try {
                    $logger = Zend_Registry::get('logger');
                }
                catch (Exception $_) {
                    $logger = new Strass_Logger;
                }
                $logger->critical($e->getMessage(), null, print_r($e, true));
            }
            catch(Exception $_) {}

            // Affichage complet des exceptions non interceptées par le
            // controlleur.
            $msg = ":(\n\n";
            $msg.= $e->getMessage()."\n\n";
            $msg.= " à ".$e->getFile().":".$e->getLine()."\n\n";
            $msg.= str_replace ('#', '<br/>#', $e->getTraceAsString())."\n";
            header('HTTP/1.1 500 Internal Server Error');
            error_log(strtok($e->getMessage(), "\n"));
            Orror::kill(strip_tags($msg));
            return;
        }

        self::saveProfile();
    }

    static function run()
    {
        Zend_Registry::set('config', new Strass_Config_Php('strass'));
        Strass_Cache::setup();
        $fc = Zend_Controller_Front::getInstance();

        $request = new Strass_Controller_Request_Http();
        $fc->setRequest($request);

        $routeur = $fc->getRouter();
        $routeur->removeDefaultRoutes();

        $p = '([[:alpha:]]+)';
        $f = '(xhtml|ics|vcf|rss|atom|pdf|tex|txt|od[ts]|csv)';
        $vars = array('controller' => array($p, 'unites'),
        'action'     => array($p, 'index'),
        'format'     => array($f, 'html'),
        'annee'      => array('([[:digit:]]{4})', null));

        $pattern = '[%controller%[/%action%][.%format%][/%annee%]*]';
        if ($prefix = @getenv('STRASS_ROUTE_PREFIX'))
            $pattern = $prefix.$pattern;
        $opattern = null;
        $route = new Strass_Controller_Router_Route_Uri($vars, $pattern, $opattern);
        $routeur->addRoute('default', $route);

        $fc->setParam('noViewRenderer', true);

        $fc->setModuleControllerDirectoryName('Controller');
        $fc->addControllerDirectory(self::getPrefix().'include/Strass/Controller', 'Strass');
        Zend_Controller_Action_HelperBroker::addPrefix('Strass_Controller_Action_Helper');
        $fc->setDefaultModule('Strass');

        // greffons
        $fc->registerPlugin(new Strass_Controller_Plugin_Error);
        $fc->registerPlugin(new Strass_Controller_Plugin_Db);
        $fc->registerPlugin(new Strass_Controller_Plugin_Auth);

        $fc->dispatch();

        self::saveSession();
    }

    static function saveProfile()
    {
        if (@strpos($_SERVER['QUERY_STRING'], 'PROFILE') === false)
            return;

        $db = Zend_Registry::get('db');
        $profiler = $db->getProfiler();
        $fd = fopen('sql-profile.csv', 'w');
        foreach ($profiler->getQueryProfiles() as $query) {
            $sql = str_replace("\n", " ", $query->getQuery());
            $time = $query->getElapsedSecs();
            fputcsv($fd, array($time, $sql));
        }
        fclose($fd);
    }

    static function saveSession()
    {
        if (class_exists('Zend_Session', false) && Zend_Session::isStarted()) {
            Zend_Session::writeClose();
        } elseif (isset($_SESSION)) {
            session_write_close();
        }
    }

    static function getSiteTitle()
    {
        $config = Zend_Registry::get('config');
        $t = new Unites;
        if (@$config->metas->title)
            return $config->metas->title;
        else {
            try {
                $racine = $t->findRacine();
                return $racine->getName();
            }
            catch (Exception $e) {
                return null;
            }
        }
    }
}
