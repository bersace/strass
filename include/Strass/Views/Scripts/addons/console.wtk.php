<?php

$user = Zend_Registry::get('user');

$this->document->addStyleComponents('form');
$parent = $this->document->aside;

if ($user->username == 'nobody') {
  // login
  $section = $parent->addSection('login', "Membres");
  $l = $section->addList();
  $l->addItem($this->lien(array('controller'	=> 'membres',
				'action'	=> 'inscription'),
			  "S'enregistrer", true))->addFlags('inscription');

  $model = $this->auth_login_model;
  $form = $section->addForm($model);
  $form->addEntry('username', 34);
  $form->addPassword('password');
  $form->addForm_ButtonBox()->addForm_Submit($model->getSubmission('login'));
}
else {
  // console
  $s = $parent->addSection('console', "Mon compte");

  if (count($this->liens)) {
    $l = $s->addList();
    foreach ($this->liens as $lien) {
      $i = $l->addItem($this->lien($lien['urlOptions'],
				   new Wtk_Metas($lien['metas']),
				   true));
      $i->addFlags($lien['urlOptions']);
    }
  }

  $f = $s->addForm($this->auth_logout_model);
  $f->addForm_ButtonBox()->addForm_Submit($this->auth_logout_model->getSubmission('logout'));
}
