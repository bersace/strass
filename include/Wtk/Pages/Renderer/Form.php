<?php

class Wtk_Pages_Renderer_Form extends Wtk_Pages_Renderer
{
    protected	$form;

    function __construct()
    {
        parent::__construct(null);
    }

    function renderContainer($page_model)
    {
        $form_model = $page_model->data;
        $this->form = $f = new Wtk_Form($form_model);
        // render all other fields in Hidden control up to current page.
        $current = $form_model->get('$$current$$');
        $root = $form_model->getInstance();
        foreach($root as $group) {
            if ($group->id != $current) {
                $this->renderInstance($group, $f);
            }
        }

        return $f;
    }

    function renderInstance($instance, $f)
    {
        if ($instance instanceof Wtk_Form_Model_Instance_Group) {
            foreach($instance as $child) {
                $this->renderInstance($child, $f);
            }
        }
        else if ($instance->id != '$$validated$$') {
            $f->addHidden($instance);
        }
    }

    function render($id, $data, $container)
    {
        $method = 'render'.ucfirst($id);
        $container->addFlags($id);
        if (!method_exists($this, $method))
            throw new Exception("Missing render method $method");
        call_user_func(array($this, $method), $data, $container);
    }

    function renderLinks($pages, $page_model)
    {
        $pids = $page_model->getPagesIds();
        $cid =$page_model->getCurrentPageId();
        $submit = $cid == end($pids) ? 'terminer' : 'continuer';
        $b = $this->form->addForm_ButtonBox();
        $b->addForm_Submit($page_model->data->getSubmission($submit))
          ->addFlags('primary');
        if ($cid != $pids[0])
            $b->addForm_Submit($page_model->data->getSubmission('precedent'))
              ->addFlags('secondary');
    }
}
