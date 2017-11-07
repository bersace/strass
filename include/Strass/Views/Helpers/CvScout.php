<?php

class Strass_View_Helper_CvScout
{
    protected	$view;

    public function setView($view)
    {
        $this->view = $view;
    }

    public function cvScout($apps, $admin=false)
    {
        $m = new Wtk_Table_Model('unite_slug', 'unite_type', 'unite_nom', 'unite_lien',
        'role', 'accr', 'acl', 'debut', 'fin',
        'url-editer', 'url-supprimer');

        $individu = null;
        foreach($apps as $app) {
            if (!$individu)
                $individu = $app->findParentIndividus();

            $role = $app->findParentRoles();
            $unite = $app->findParentUnites();
            $url_unite = $this->view->url(
                array(
                    'controller' => 'unites', 'action' => 'effectifs',
                    'unite' => $unite->slug, 'annee' => $app->getAnnee()),
                true);
            $url_editer = $this->view->url(array(
                'controller' => 'individus', 'action' => 'reinscrire',
                'inscription' => $app->id), true);
            $url_suppr = $this->view->url(array(
                'controller' => 'individus', 'action' => 'desinscrire',
                'inscription' => $app->id), true);
            $fin = $app->fin ? strftime('au %x', strtotime($app->fin)) : "à aujourd'hui";
            $m->append(
                $unite->slug,
                $unite->findParentTypesUnite()->slug,
                $unite->getFullName(),
                $url_unite,
                array($role->slug, wtk_strtoid($app->titre)),
                $app->getAccronyme(),
                $role->acl_role,
                strftime('du %x', strtotime($app->debut)), $fin,
                $url_editer, $url_suppr
            );
        }

        $t = new Wtk_Table($m, true, array('acl', 'role'));
        $config = Zend_Registry::get('config');
        $t->addFlags('effectifs', $config->system->association, 'appartenances');
        $t->addNewColumn('Poste', new Wtk_Table_CellRenderer_Text('text', 'accr'));
        $t->addNewColumn(
            'Unité', new Wtk_Table_CellRenderer_Link('href', 'unite_lien',
            'label', 'unite_nom'), 'unite');
        $t->addNewColumn('Début', new Wtk_Table_CellRenderer_Text('text', 'debut'));
        $t->addNewColumn('Fin', new Wtk_Table_CellRenderer_Text('text', 'fin'));

        if ($admin && $this->view->assert(null, $individu, 'inscrire')) {
            $t->addNewColumn(
                null, new Wtk_Table_CellRenderer_Link('href', 'url-editer',
                'label', 'Éditer',
                'flags', array('adminlink', 'editer')));
            $t->addNewColumn(
                null, new Wtk_Table_CellRenderer_Link('href', 'url-supprimer',
                'label', 'Supprimer',
                'flags', array('adminlink', 'critical',
                'supprimer')));
        }

        return $t;
    }
}
