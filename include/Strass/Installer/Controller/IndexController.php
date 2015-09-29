<?php

class IndexController extends Strass_Installer_Controller_Action
{
    static $mouvements = array(
        'suf' => 'Scouts unitaires de France',
        'fse' => "Association guides et scouts d'Europe",
    );

    function indexAction()
    {
        $m = new Wtk_Form_Model('installation');

        $g = $m->addGroup('site', "Le site" );
        $i = $g->addEnum('mouvement', "Mouvement", null, self::$mouvements);
        $m->addConstraintRequired($i);

        $g = $m->addGroup('admin', "Votre compte" );
        $i = $g->addString('prenom', "Votre prénom");
        $m->addConstraintRequired($i);

        $i = $g->addString('nom', "Votre nom");
        $m->addConstraintRequired($i);

        $i = $g->addEnum('sexe', "Sexe", null, array('h' => 'Masculin', 'f' => 'Féminin'));
        $m->addConstraintRequired($i);

        $i = $g->addDate('naissance', "Date de naissance", 0);
        $m->addConstraintRequired($i);

        $i = $g->addString('adelec', "Adélec");
        $m->addConstraintRequired($i);

        $i = $i0 = $g->addString('motdepasse', "Mot de passe");
        $m->addConstraintRequired($i);

        $i = $i1 = $g->addString('confirmation', "Confirmation");
        $m->addConstraintEqual($i1, $i0);

        $this->view->model = $pm = new Wtk_Pages_Model_Form($m);

        if ($pm->validate()) {
            $installer = new Strass_Installer($m->get());
            $installer->run();

            /* Autologin. Écrire dans la session l'identité de l'admin */
            $t = new Users;
            $admin = $t->findByUsername($m->get('admin/adelec'));
            $auth = Zend_Auth::getInstance();
            $auth->getStorage()->write($admin->getIdentity());

            $this->_redirect('/', array('prependBase' => false,
            'exit' => true));
        }
    }
}
