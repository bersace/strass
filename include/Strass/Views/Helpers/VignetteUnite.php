<?php

class Strass_View_Helper_VignetteUnite
{
    protected	$view;

    public function setView($view)
    {
        $this->view = $view;
    }

    public function vignetteUnite($unite, $annee = null, $label = null, $urlOptions = array())
    {
        if (!$unite)
            return;

        $label = $label ? $label : $unite->getName();

        if ($src = $unite->getCheminImage())
            $image = new Wtk_Image($src, "Photo d'unité", $label);
        else {
            $photo = $unite->findPhotoAleatoire($annee);
            if ($photo)
                $image = new Wtk_Image($photo->getURLVignette(),
                $photo->titre, $unite->getFullname());
            else {
                $image = new Wtk_Paragraph("Pas d'image !");
                $image->addFlags('empty', 'image');
            }
        }

        $urlOptions = array_merge(
            array(
                'controller' => 'unites',
                'action' => 'index',
                'unite' => $unite->slug),
            $urlOptions);
        $type = $unite->findParentTypesUnite();
        $w = new Wtk_Section;
        $w->addFlags('wrapper')->addChild($image);
        $plabel = new Wtk_Paragraph($label);
        $plabel->addFlags('label');

        $link = new Wtk_Link(
            $this->view->url($urlOptions), $label,
            new Wtk_Container($w, $plabel));
        $link->addFlags('vignette', $type->slug);
        $link->addFlags('unite');
        if (!$src)
            $link->addFlags('photo');

        return $link;
    }
}
