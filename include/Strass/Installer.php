<?php

require_once 'Strass/Individus.php';

class Strass_Installer
{
  const VERSION = 15;

  function __construct($data, $dbname = null)
  {
    $this->sql_dir = dirname(__FILE__) . '/Installer/sql/';
    $this->data = $data;
    $this->dbname = $dbname;
  }

  static function main()
  {
    Zend_Registry::set('config', new Strass_Config_Php('strass', array()));
    Zend_Registry::set('acl', new Strass_Installer_FakeAcl);
    Strass_Cache::setup();

    try {
      $fc = Zend_Controller_Front::getInstance();

      $fc->setRequest(new Strass_Controller_Request_Http);
      $fc->setParam('useDefaultControllerAlways', true);
      $fc->setParam('noViewRenderer', true);
      $fc->setModuleControllerDirectoryName('Installer');
      $fc->addControllerDirectory('include/Strass/Installer/Controller', 'Strass');
      $fc->setDefaultModule('Strass');
      $fc->registerPlugin(new Strass_Controller_Plugin_Error);

      $fc->dispatch();

      Zend_Session::writeClose();
    }
    catch (Exception $e) {
      // affichage complet des exceptions non intercepté par le controlleur. À améliorer.
      $msg = ":(\n\n";
      $msg.= $e->getMessage()."\n\n";
      $msg.= " à ".$e->getFile().":".$e->getLine()."\n\n";
      $msg.= str_replace ('#', '<br/>#', $e->getTraceAsString())."\n";
      Orror::kill(strip_tags($msg));
    }
  }

  function generateRealm()
  {
    if ($_SERVER['SERVER_NAME'] != 'localhost') {
      return 'strass-'.$_SERVER['SERVER_NAME'];
    }
    else {
      return 'strass-'.mt_rand(100, 999);
    }
  }

  function writeConfig()
  {
    $config = array ('metas' => array ('title' => null,
				       'author' => null,
				       'creation' => strftime('%Y'),
				       'subject' => 'scout,' . $this->data['site']['mouvement'],
				       ),
		     'system' => array ('short_title' => null,
					'mouvement' => $this->data['site']['mouvement'],
					'realm' => $this->generateRealm(),
					'duree_connexion' => 2678400,
					'admin' => $this->data['admin']['adelec'],
					'mail' => array('enable' => true,
							'smtp' => '',
							),
					'style' => 'strass',
					),
		     );
    $config = new Strass_Config_Php('strass', $config);
    $config->write();
    Zend_Registry::set('config', $config);
  }

  function initDb()
  {
    /* optimisation car la création du schéma peut prendre pas mal de
       temps, et nous somme online */
    $db = Strass_Db::setup($this->dbname, true);

    $dump = $this->sql_dir . '/dump-' .$this->data['site']['mouvement']. '.sql';
    if (!file_exists($dump))
      throw new Exception("Pas de données pour ce mouvement !");
    $sql = file_get_contents($dump);
    $snippets = array_filter(explode(";\n", $sql));
    foreach($snippets as $snippet)
      $db->exec($snippet);

    Strass_Version::save(self::VERSION);
    Strass_Migrate::run($db);
  }

  function initAdmin()
  {
    extract($this->data['admin']);

    $i = new Individu;
    $i->prenom = $prenom;
    $i->nom = $nom;
    $i->sexe = $sexe;
    $i->adelec = $adelec;
    $i->naissance = $naissance;
    $i->slug = $i->getTable()->createSlug($i->getFullname());
    $i->save();

    $u = new User;
    $u->individu = $i->id;
    $u->username = $adelec;
    $u->password = Users::hashPassword($adelec, $motdepasse);
    $u->admin = true;
    $u->save();

    Zend_Registry::set('user', $u);
  }

  function run()
  {
    $this->writeConfig();
    $this->initDb();
    $this->initAdmin();

    $logger = new Strass_Logger('installeur');
    $logger->info("Installation terminée");

    Strass::setInstalled();
  }
}