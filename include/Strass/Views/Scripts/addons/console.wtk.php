<?php

$user = Zend_Registry::get('user');
$username = $user->getIdentity();

$this->document->addStyleComponents('form');

if ($username == 'nobody') {
	// login
	$section = $this->addons->addSection('login', "Membres");
	$l = $section->addChild(new Wtk_List());
	$l->addItem($this->lien(array('controller'	=> 'membres',
				      'action'	=> 'inscription'),
				"Inscription", true))->addFlags('inscription');

	$l->addItem($this->lien(array('controller'	=> 'membres',
				      'action'	=> 'index'),
				"Connection sécurisée", true));

	$model = $this->auth_login_model;
	$form = $section->addForm ($model);
	$form->addEntry('username', 34);
	$form->addPassword('password');
	$form->addForm_ButtonBox()->addForm_Submit($model->getSubmission('login'));
 }
 else {
	 // console
	 $s = $this->addons->addSection('console', "Console");

	 if (count($this->liens)) {
		 $ss = $s->addSection('actions', 'Actions');
		 $l = $ss->addList();
		 foreach ($this->liens as $lien) {
			 $i = $l->addItem($this->lien($lien['urlOptions'],
							   new Wtk_Metas($lien['metas']),
						      $lien['reset']));
			 $i->addFlags($lien['urlOptions']);
		 }
	 }

	 $ss = $s->addSection('compte', 'Votre compte');
	 $l = $ss->addList();
	 foreach($this->actions as $action) {
		 $l->addItem(new Wtk_Link($action['url'], $action['label']))
			 ->addFlags(explode('/', $action['url']));
	 }

	 $f = $s->addForm($this->auth_logout_model);
	 $f->addHidden('logout');
	 $f->addForm_ButtonBox()->addForm_Submit($this->auth_logout_model->getSubmission('logout'));
 }
