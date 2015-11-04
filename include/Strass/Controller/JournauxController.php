<?php

require_once 'Strass/Journaux.php';

class JournauxController extends Strass_Controller_Action
{
    function indexAction()
    {
        $this->view->journal = $j = $this->_helper->Journal();
        $this->formats('rss', 'atom');

        $this->view->select = $s = $j->selectArticles()->where('public = 1');
        $this->view->model = new Strass_Pages_Model_Rowset($s, 7, $this->_getParam('page'));

        $this->actions->append(
            "Envoyer un PDF",
            array('action' => 'envoyer', 'journal' => $j->slug),
            array(null, $j));
        $this->actions->append(
            "Écrire un article",
            array('action' => 'ecrire', 'journal' => $j->slug),
            array(null, $j));
        $this->actions->append(
            "Éditer",
            array('action' => 'editer', 'journal' => $j->slug),
            array(null, $j));
        $this->actions->append(
            "Supprimer",
            array('action' => 'supprj', 'journal' => $j->slug),
            array(null, $j));
        $t = new Articles;
        $brouillons = $t->countRows($j->selectArticles()->where('public IS NULL OR public != 1'));
        if ($brouillons) {
            $this->actions->insert(
                0, "Brouillons",
                array('action' => 'brouillons', 'journal' => $j->slug),
                array(null, $j));
        }
    }

    function fonderAction()
    {
        $this->view->unite = $u = $this->_helper->Unite();
        $this->metas(array('DC.Title' => "Fonder le journal de ".$u->getFullName(),
        'DC.Subject' => 'journaux,journal,gazette,blog'));

        $tu = $u->findParentTypesUnite();
        if ($tu->isTerminale() && $tu->age_min && $tu->age_min < 12)
            throw new Strass_Controller_Action_Exception(
                "Impossible de créer un journal d'unité ".
                "pour ".$u->getFullName());

        $this->assert(
            null, $u, 'fonder-journal',
            "Vous n'avez pas le droit de fonder le journal de cette unité");

        $this->view->model = $m = new Wtk_Form_Model('fonder-journal');
        $i = $m->addString('nom', "Nom");
        $m->addConstraintRequired($i);
        $m->addNewSubmission('fonder', "Fonder");

        if ($m->validate()) {
            $t = new Journaux;
            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                $data = array(
                    'nom' => $m->get('nom'),
                    'slug' => $t->createSlug(wtk_strtoid($m->get('nom'))),
                    'unite' => $u->id);
                $k = $t->insert($data);
                $j = $t->findOne($k);

                $this->logger->info("Nouveau journal d'unité",
			    $this->_helper->Url('index', 'journaux', array('journal' => $j->slug)));
                $db->commit();
            }
            catch(Exception $e) { $db->rollBack(); throw $e; }
            $this->redirectSimple('index', 'journaux', null,  array('journal' => $j->slug));
        }
    }

    function editerAction()
    {
        $this->view->journal = $j = $this->_helper->Journal();
        $this->metas(array('DC.Title' => "Modifier ".$j->nom));

        $this->assert(null, $j, 'editer',
        "Vous n'avez pas le droit de modifier ce journal");

        $this->view->model = $m = new Wtk_Form_Model('journal');
        $i = $m->addString('nom', 'Nom', $j->nom);
        $m->addConstraintRequired($i);
        $m->addNewSubmission('enregistrer', 'Enregistrer');

        if ($m->validate()) {
            $t = $j->getTable();
            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                $j->nom = $m->get('nom');
                $j->slug = $t->createSlug(wtk_strtoid($j->nom), $j->slug);
                $j->save();

                $this->logger->info("Journal édité",
			    $this->_helper->Url('index', null, null, array('journal' => $j->slug)));
                $db->commit();
            }
            catch(Exception $e) { $db->rollBack(); throw $e; }

            $this->redirectSimple('index', null, null, array('journal' => $j->slug));
        }
    }

    function ecrireAction()
    {
        if ($this->_getParam('article')) {
            $a = $this->_helper->Article();
            $j = $a->findParentJournaux();
            try {
                $a->findDocument();
                $this->redirectSimple('envoyer');
            } catch (Strass_Db_Table_NotFound $e) {}
        }
        else {
            $a = null;
            $j = $this->_helper->Journal();
        }

        $this->view->unite = $u = $j->findParentUnites();

        $this->metas(array('DC.Title' => "Écrire un article"));
        $this->assert(
            null, $j, 'ecrire',
            "Vous n'avez pas le droit d'écrire un nouvel article dans ce journal");

        $publier = $this->assert(null, $j, 'publier');

        $this->view->model = $m = new Wtk_Form_Model('ecrire');
        $me = Zend_Registry::get('individu');
        if ($publier) {
            $i = $m->addEnum('auteur', "Auteur");
            /* on inclus les membres de sous-unité : le scout peuvent écrire
               dans la gazette de troupe */
            foreach($u->findInscrits(null, 1) as $individu)
                $i->addItem($individu->id, $individu->getFullname(false));

            if (!count($i))
                throw new Strass_Controller_Action_Exception_Notice(
                    "L'auteur de l'article doit être un membre, mais cette unité n'a aucun membre !");

            if ($a)
                $i->set($a->findAuteur()->id);
            else
                $i->set($me->id);
        }
        else {
            $i = $m->addInteger('auteur', "Auteur", $me->id, true);
        }

        $i = $m->addString('titre', "Titre", $a ? $a->titre : null);
        $m->addConstraintRequired($i);
        if ($publier)
            $m->addEnum(
                'public', 'Publication', $a ? $a->public : null,
                array(0 => 'Brouillon', 1 => 'Publier'));

        $m->addString('boulet', "Boulet", $a ? $a->boulet : null);
        $i = $m->addString('article', "Article", $a ? $a->article : null);
        $m->addConstraintRequired($i);
        $t = $m->addTable('images', "Images", array(
            'image' => array('File', "Image"),
            'nom' => array('String', "Renommer en"),
            'origin' => array('String')),
        false);
        if ($a)
            foreach ($a->getImages() as $image)
                $t->addRow(null, $image, $image);
        $t->addRow();
        $m->addNewSubmission('poster', "Poster");

        if ($m->validate()) {
            $t = new Articles;
            $db = $t->getAdapter();
            $db->beginTransaction();
            try {
                if ($a) {
                    $a->slug = $t->createSlug(wtk_strtoid($m->titre), $a->slug);
                    $c = $a->findParentCommentaires();
                    $message = "Article édité";
                }
                else {
                    $c = new Commentaire;
                    $a = new Article;
                    $a->slug = $t->createSlug(wtk_strtoid($m->titre));
                    $message = "Nouvel article";
                }

                $c->auteur = $m->auteur;
                $c->save();

                $a->journal = $j->id;
                $a->titre = $m->titre;
                $a->boulet = $m->boulet;
                $a->article = $m->article;
                try {
                    $a->public = (int) $m->public;
                }
                catch (Exception $e) {}
                $a->commentaires = $c->id;
                $a->save();

                $oldImages = $a->getImages();
                $newImages = array();
                $table = $m->getInstance('images');
                foreach($table as $row) {
                    if ($row->origin && $row->origin != $row->nom) {
                        $a->renameImage($row->origin, $row->nom);
                        array_push($newImages, $row->nom);
                    }
                    else {
                        $if = $row->getChild('image');
                        if ($if->isUploaded()) {
                            $nom = $row->nom ? $row->now : $if->getBasename();
                            $a->storeImage($if->getTempFilename(), $nom);
                            array_push($newImages, $nom);
                        }
                        else {
                            array_push($newImages, $row->nom);
                        }
                    }
                }
                $oldImages = array_unique($oldImages);
                $newImages = array_filter($newImages);

                /* Nettoyage des images */
                foreach ($oldImages as $image) {
                    if (!in_array($image, $newImages))
                        $a->deleteImage($image);
                }

                $this->logger->info($message, $this->_helper->url('consulter', 'journaux', null,
                array('article' => $a->slug), true));

                if (!$this->assert(null, $j, 'publier')) {
                    $mail = new Strass_Mail_Article($a);
                    $mail->send();
                }

                $db->commit();
            }
            catch(Exception $e) { $db->rollBack(); throw $e; }

            $this->redirectSimple('consulter', 'journaux', null, array('article' => $a->slug), true);
        }
    }

    function envoyerAction()
    {
        if ($this->_getParam('article')) {
            $a = $this->_helper->Article();
            $j = $a->findParentJournaux();
            $c = $a->findParentCommentaires();
            try {
                $d = $a->findDocument();
            }
            catch (Strass_Db_Table_NotFound $e) {
                $this->redirectSimple('ecrire');
            }
            $message = "Article édité";
        }
        else {
            $a = null;
            $j = $this->_helper->Journal();
            $message = "Article envoyé";
        }

        $this->metas(array('DC.Title' => "Envoyer"));
        $this->branche->append();
        if ($a)
            $this->assert(
                null, $a, null,
                "Vous n'avez pas le droit d'éditer ".$a);
        else
            $this->assert(
                null, $j, null,
                "Vous n'avez pas le droit d'envoyer un PDF dans ".$j);

        $this->view->unite = $u = $j->findParentUnites();
        $publier = $this->assert(null, $j, 'publier');
        $this->view->model = $m = new Wtk_Form_Model('envoyer');
        $me = Zend_Registry::get('individu');
        if ($publier) {
            $i = $m->addEnum('auteur', "Auteur");
            /* on inclus les membres de sous-unité : le scout peuvent écrire
               dans la gazette de troupe */
            foreach($u->findInscrits(null, 1) as $individu)
                $i->addItem($individu->id, $individu->getFullname(false));

            if (!count($i))
                throw new Strass_Controller_Action_Exception_Notice(
                    "L'auteur de l'article doit être un membre, mais cette unité n'a aucun membre !");

            if ($a)
                $i->set($a->findAuteur()->id);
            else
                $i->set($me->id);
        }
        else {
            $i = $m->addInteger('auteur', "Auteur", $me->id, true);
        }

        $i = $m->addInstance('File', 'fichier', "Fichier");
        if (!$a)
            $m->addConstraintRequired($i);
        $i = $m->addString('titre', "Titre", $a ? $a->titre : null);
        $m->addConstraintRequired($i);
        $m->addDate('date', 'Date', $a ? $c->date : null);
        if ($publier)
            $m->addEnum(
                'public', 'Publication', $a ? $a->public : null,
                array(0 => 'Brouillon', 1 => 'Publier'));

        $m->addNewSubmission('envoyer', "Envoyer");

        if ($m->validate()) {
            $td = new Documents;
            $db = $td->getAdapter();
            $db->beginTransaction();
            try {
                $da = (bool) $a;
                if (!$a) {
                    $a = new Article;
                    $c = new Commentaire;
                    $d = new Document;
                }

                $d->slug = $td->createSlug($j->slug . '-' . $m->titre, $d->slug);
                $d->titre = $m->titre;
                $d->date = $m->date;
                $i = $m->getInstance('fichier');
                if ($i->isUploaded()) {
                    $d->suffixe =strtolower(end(explode('.', $m->fichier['name'])));
                    $d->storeFile($i->getTempFilename());
                }
                $d->save();

                $c->auteur = $m->auteur;
                $c->date = $m->date;
                $c->save();

                $a->slug = $a->getTable()->createSlug($m->titre, $a->slug);
                $a->journal = $j->id;
                $a->titre = $m->titre;
                $a->article = '!document';
                try {
                    $a->public = (int) $m->public;
                }
                catch (Exception $e) {}
                $a->commentaires = $c->id;
                $a->save();

                if (!$da) {
                    $da = new DocArticle;
                    $da->article = $a->id;
                    $da->document = $d->id;
                    $da->save();
                }

                $this->logger->info(
                    $message, $this->_helper->url(
                        'consulter', 'journaux', null,
                        array('article' => $a->slug), true));

                if (!$this->assert(null, $j, 'publier')) {
                    $mail = new Strass_Mail_Article($a);
                    $mail->send();
                }

                $this->_helper->Flash->info($message);
                $db->commit();
            }
            catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }

            $this->redirectSimple(
                'consulter', 'journaux', null,
                array('article' => $a->slug), true);
        }
    }

    function brouillonsAction()
    {
        $this->view->journal = $j = $this->_helper->Journal();
        $this->metas(array('DC.Title' => "Brouillons"));
        $this->branche->append();
        $this->assert(
            null, $j, 'publier',
            "Vous n'avez pas le droit de publier des brouillons");
        $s = $j->selectArticles();
        $s->where('public = 0');
        $this->view->model = new Strass_Pages_Model_Rowset($s, 7, $this->_getParam('page'));
    }

    function consulterAction()
    {
        $this->view->article = $a = $this->_helper->Article();
        $this->view->auteur = $a->findAuteur();
        try {
            $this->view->doc = $a->findDocument();
            $editer = 'envoyer';
        }
        catch (Strass_Db_Table_NotFound $e) {
            $this->view->doc = null;
            $editer = 'ecrire';
        }

        $this->assert(null, $a, 'voir', "Cet article n'est pas public.");

        $this->actions->append(
            "Éditer",
            array('action' => $editer),
            array(null, $a));
        $this->actions->append(
            "Supprimer",
            array('action' => 'supprimer'),
            array(null, $a));
    }

    function supprjAction()
    {
        $this->view->journal = $j = $this->_helper->Journal();
        $this->view->unite = $u = $j->findParentUnites();
        $this->metas(array('DC.Title' => "Supprimer"));
        $this->branche->append();
        $this->assert(
            null, $j, 'supprimer',
            "Vous n'avez pas le droit de supprimer ce journal");

        $this->view->model = $m = new Wtk_Form_Model('supprimer');
        $m->addBool(
            'confirmer',
            "Je confirme la suppression du journal ".$j." et de tout ses articles.",
            false);
        $m->addNewSubmission('continuer', "Continuer");

        if ($m->validate()) {
            if ($m->confirmer) {
                $db = $j->getTable()->getAdapter();
                $db->beginTransaction();
                try {
                    $j->delete();
                    $this->_helper->Flash->warn("Journal supprimé");
                    $this->logger->warn("Journal supprimé",
                    $this->_helper->Url('index', 'unites', null,
                    array('unite' => $u->slug), true));
                    $db->commit();
                }
                catch(Exception $e) { $db->rollBack(); throw $e; }
                $this->redirectSimple('index', 'unites', null, array('unite' => $u->slug));
            }
            else {
                $this->redirectSimple('index', 'journaux', null, array('journal' => $j->slug), true);
            }
        }
    }

    function supprimerAction()
    {
        $this->view->article = $a = $this->_helper->Article();
        $this->assert(
            null, $a, 'supprimer',
            "Vous n'avez pas le droit de supprimer cet article");
        $this->metas(array('DC.Title' => "Supprimer ".$a->titre));

        $j = $a->findParentJournaux();
        $this->view->model = $m = new Wtk_Form_Model('supprimer');
        $m->addBool(
            'confirmer',
            "Je confirme la suppression de l'article ".$a->titre.".",
            false);
        $m->addNewSubmission('continuer', "Continuer");

        if ($m->validate()) {
            if ($m->confirmer) {
                $action = $a->public ? 'index' : 'brouillons';
                $db = $a->getTable()->getAdapter();
                $db->beginTransaction();
                try {
                    $a->delete();
                    $this->logger->info("Article supprimé",
                    $this->_helper->Url('index', 'journaux', null,
                    array('journal' => $j->slug), true));
                    $db->commit();
                }
                catch(Exception $e) { $db->rollBack(); throw $e; }
                $this->redirectSimple($action, 'journaux', null, array('journal' => $j->slug), true);
            }
            else {
                $this->redirectSimple('consulter');
            }
        }
    }
}
