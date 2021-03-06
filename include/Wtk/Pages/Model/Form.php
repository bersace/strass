<?php

/**
 *
 * Ce modèle permet de paginer un formulaire en utilisant un groupe
 * de champs par page.
 *
 * Pour connaître si le formulaire a été complété, il faut utilise
 * la méthode isValid() du modèle de la **pagination**.
 *
 * Pour la rendu, il faut étendre Wtk_Pages_Rendrer_Form et
 * implémenter les fonction renderNomdegroupe($instance-groupe,
 * $formulaire). Il est donc impératif de ne pas utiliser de tirer
 * dans l'identifiant des groupes de champs du modèle de formulaire.
 */

class Wtk_Pages_Model_Form extends Wtk_Pages_Model
{
    protected	$root;
    protected	$loop;

    function __construct(Wtk_Form_Model $model)
    {
        parent::__construct($model, 1, '$$init$$'); // un groupe par page

        $this->root = $model->getInstance();

        $model->addNewSubmission('precedent', 'Précédent');
        $model->addNewSubmission('continuer', 'Continuer');
        try { $model->addNewSubmission('terminer', 'Terminer'); } catch (Exception $e) {}
        $model->addString('$$current$$', 'Current group', null);
    }

    function getFormModel()
    {
        return $this->data;
    }

    /* Valide les pages présentées à l'utilisateur uniquement */
    function partialFormValidate()
    {
        $model = $this->data;
        $root = $model->instance;

        if (!$values = $model->getRawSubmittedValues())
            return false;

        /* Nettoyer les erreurs et leurs références. */
        foreach($model->errors as $e) {
            $i = $e->getInstance();
            $i->valid = null;
            $i->errors = array();
        }
        $model->errors = array();

        /* Récupérer la page courante */
        $root->value['$$current$$']->retrieve($values['$$current$$']);
        $current = $model->get('$$current$$');

        /* Ne récupérer depuis _POST que les valeurs des pages précédentes
           et actuelle */
        foreach ($root->value as $id => $child) {
            /* zapper les instances de méta informations */
            if (strpos($id, '$$') === false && $this->pageCmp($id, $current) > 0)
                continue;

            try {
                $root->value[$id]->retrieve(isset ($values[$id]) ? $values[$id] : NULL);
            }
            catch (Wtk_Form_Model_CompoundException $e) {
                $model->errors = array_merge($model->errors, $e->errors);
            }
        }

        $validated = $model->get('$$validated$$');
        $valid = $model->checkConstraints();

        if (!count($model->errors) && $valid && $validated)
            return $model->sent_submission;
        else
            return false;
    }

    function partialValidate()
    {
        $this->pages_count = $this->root->count() - 1; // minus $$validated$$
        $this->pages_id = array();
        foreach($this->root as $group)
            if ($group instanceof Wtk_Form_Model_Instance_Group)
                array_push($this->pages_id, $group->id);

        $model = $this->data;
        $valid = $this->partialFormValidate();
        $submission = $model->sent_submission;
        $current = $this->root->get('$$current$$');

        if (!$current)
            $current = reset($this->pages_id);
        else {
            $pks = array_flip($this->pages_id);
            if ($submission->id == 'precedent') {
                $i = $pks[$current]-1;
                if (isset($this->pages_id[$i]))
                    $current = $this->pages_id[$i];
                else
                    $current = reset($this->pages_id);
                $model->errors = array();
            }
            else { // continuer, terminer
                // suppression des erreurs ne concernant pas la page
                // courante. Implémentation gruik de la validation partielle
                // de formulaire.
                foreach($model->errors as $j => $error) {
                    $i = $error->getInstance();
                    $p = $i->path;
                    if (strpos($p, $model->id."/".$current) === false) {
                        $i->valid = null;
                        $i->errors = array();
                        unset($model->errors[$j]);
                    }
                }

                // s'il y a des erreurs dans la première
                // étape, ne pas passer à la suivante.
                if (!count($model->errors)) {
                    $i = $pks[$current]+1;
                    if (isset($this->pages_id[$i]))
                        $current = $this->pages_id[$i];
                    else
                        /* Terminé, il ne reste plus de groupes de champs */
                        $current = '$$completed$$';
                }
            }
        }

        return $current;
    }

    function validate()
    {
        $current = $this->partialValidate();

        /* Appliquer un goto explicite */
        if ($this->current != '$$init$$')
            $current = $this->current;

        $completed = $current === '$$completed$$';

        if ($completed)
            $this->gotoEnd();
        else if ($current !== false)
            $this->gotoPage($current);

        return !count($this->data->errors) && $completed;
    }

    /* Force le rendu de la page suivante */
    function gotoPage($page)
    {
        if (!in_array($page, $this->pages_id))
            throw new Exception("Can't go to inexistant form step $page");

        $this->data->getInstance('$$current$$')->set($page);
        $this->current = $page;
    }

    function gotoEnd()
    {
        $this->current = '$$completed$$';
        $this->data->getInstance('$$current$$')->set($this->current);
        return $this->current;
    }

    /* Compare si une page est postérieure à une autre */
    function pageCmp($a, $b)
    {
        /* Comparaison avec la page correspondant à l'état terminé : $$completed$$ */
        if ($a == '$$completed$$' && $b != '$$completed$$')
            return 1;
        if ($a != '$$completed$$' && $b == '$$completed$$')
            return -1;

        return array_search($a, $this->pages_id) - array_search($b, $this->pages_id);
    }

    function isValid()
    {
        return $this->current == '';
    }

    function pagesCount()
    {
        return $this->pages_count;
    }

    function getCurrentPageId()
    {
        return $this->current;
    }

    function getPrevId($ref = null)
    {
        return $this->getRelId($ref, -1);
    }

    function getNextId($ref = null)
    {
        return $this->getRelId($ref, +1);
    }

    protected function getRelId($ref, $sens)
    {
        $ref = $ref ? $ref : $this->current;
        $r = array_flip($this->pages_id);
        $i = $r[$ref]+$sens;
        return array_key_exists($i, $this->pages_id) ? $this->pages_id[$i] : null;
    }

    function fetch($id = null)
    {
        $id = $id ? $id : $this->current;
        return $this->data->getInstance($id);
    }

    /*
     * Retourne le nombre d'item par page : un groupe = une page. Si
     * aucune page à afficher, aucun groupe.
     */
    public function count()
    {
        try {
            $this->data->getInstance($this->current);
            return 1;
        }
        catch (Exception $e) {
            return 0;
        }
    }

    /*
     * réinitialise la boucle.
     */
    public function rewind()
    {
        $this->loop = true;
    }

    // retourne le groupe courant.
    public function current()
    {
        return $this->data->getInstance($this->current);
    }

    /*
     * Retourne la clef relativement à la page courante
     */
    public function key()
    {
        return $this->current;
    }

    // on arrête la boucle dès la première itération.
    public function next()
    {
        $this->loop = false;
    }

    public function valid()
    {
        return $this->loop;
    }
}
