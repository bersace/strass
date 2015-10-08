<?php

require_once 'Strass/Unites.php';

class Strass_Controller_Action_Helper_Unite extends Zend_Controller_Action_Helper_Abstract
{
    function direct($throw = true)
    {
        $slug = $this->getRequest()->getParam('unite');
        $t = new Unites;
        try {
            if ($slug)
                $unite = $t->findBySlug($slug);
            else {
                $unite = $t->findRacine();
            }
        }
        catch (Strass_Db_Table_NotFound $e) {
            if ($throw) {
                if ($slug)
                    throw new Strass_Controller_Action_Exception_NotFound("Unité ".$slug." inconnue");
                else {
                    $url = $this->_actionController->_helper->Url('fonder', 'unites', null, null, true);
                    $aide = "Vous devez [".$url." enregistrer une unité] pour commencer.";
                    throw new Strass_Controller_Action_Exception_Notice("Pas d'unité !", 404, $aide);
                }
            }
            else
                return null;
        }

        $this->liensConnexes($unite);

        $page = Zend_Registry::get('page');
        $fn = $unite->getFullname();
        if (!$page->metas->get('DC.Title'))
            $page->metas->set('DC.Title', $fn);
        $page->metas->set('DC.Creator', $fn);

        return $unite;
    }

    function setBranche($unite)
    {
        $us = array();
        $u = $unite;
        while ($u) {
            array_unshift($us, $u);
            $u = $u->findParentUnites();
        }

        foreach($us as $u) {
            $this->_actionController->branche->append(
                $u->getName(),
                array('controller' => 'unites',
                'action' => 'index',
                'unite' => $u->slug),
            array(),
            true);
        }
    }

    function liensConnexes($unite)
    {
        $this->setBranche($unite);

        // CONNEXES
        $annee = $this->getRequest()->getParam('annee');
        $connexes = $this->_actionController->connexes;
        $url = $this->_actionController->_helper->Url('index', 'unites', null, null, true);
        $connexes->titre = new Wtk_Link($url, $unite->getName());

        $connexes->append(
            "Photos",
            array('controller' => 'photos',
            'action' => 'index',
            'annee' => $annee,
            'unite' => $unite->slug),
            array(), true);

        $journal = $unite->findJournaux()->current();
        if ($journal)
            $connexes->append(
                $journal->__toString(),
                array('controller' => 'journaux',
                'action' => 'index',
                'journal' => $journal->slug),
                array(), true);

        $connexes->append(
            "Documents",
            array('controller' => 'documents',
            'action' => 'index',
            'unite' => $unite->slug),
            array(), true);

        $connexes->append(
            "Calendrier",
            array('controller' => 'activites',
            'action' => 'calendrier',
            'annee' => $annee,
            'unite' => $unite->slug),
            array(), true);

        $connexes->append(
            'Effectifs',
            array('controller' => 'unites',
            'action' => 'effectifs',
            'annee' => $annee,
            'unite' => $unite->slug),
            array(null, $unite, 'effectifs'),
            true);

        if ($unite->findFermees()->count())
            $connexes->append(
                "Archives",
                array('controller' => 'unites',
                'action' => 'archives',
                'unite' => $unite->slug),
			array(), true);
    }
}
