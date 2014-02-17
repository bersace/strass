<?php

class Strass_Mail extends Zend_Mail
{
  protected $_doc;

  // metas = Wtk_Metas ou string (=titre)
  function __construct($metas)
  {
    parent::__construct('utf-8');

    $config = Zend_Registry::get('config');

    if (is_string($metas)) {
      $title = $metas;
      $metas = $config->metas->toArray();
      $metas['title'] = $title;
      $metas = new Wtk_Metas($metas);
    }

    $title = "[".$config->system->id."] ".$metas->title;
    $this->setSubject($title);

    $this->_doc = $d = new Wtk_Document($metas);
    $d->addStyleComponents('mail');
    $d->setStyle(new Wtk_Document_Style($config->system->style));
    $d->embedStyle();
    $d->addFlags('mail');

    // :P
    $this->addHeader('X-Mailer', 'Strass');
    $this->addHeader('X-MailGenerator', 'Wtk');
  }

  function getDocument()
  {
    return $this->_doc;
  }

  function url($urlOptions)
  {
    $fc = Zend_Controller_Front::getInstance();
    $router = $fc->getRouter();
    $request = $fc->getRequest();
    $url = $router->assemble($urlOptions);
    return "http://".$request->getServer('HTTP_HOST').$url;
  }

  function render() {
  }

  function send()
  {
    $this->render();

    $local = strpos($_SERVER['HTTP_HOST'], '.local') !== false;

    if ($local) {
      $this->_recipients = array();
      $this->_to = array();
      $this->_headers['To'] = array();
      $this->_headers['Bcc'] = array();
    }

    $config = Zend_Registry::get('config');

    if (!$config->system->mail->enable) {
      return true;
    }

    $r = Wtk_Render::factory($this->_doc, 'Txt');
    $this->setBodyText($r->render());
    $r = Wtk_Render::factory($this->_doc, 'Xhtml');
    $this->setBodyHTML($r->render());

    // assure que le courriel est bien envoyé à l'admin,
    // pour archivage.
    if (!isset($this->_recipients[$config->system->admin])) {
      if (empty($this->_to))
	$this->addTo($config->system->admin, $config->system->short_title);
      else
	$this->addBcc($config->system->admin, $config->system->short_title);
    }

    // assure l'existence d'un expéditeur, par défaut le config.
    if (!isset($this->_headers['From']))
      $this->setFrom($config->system->admin, $config->system->short_title);


    $smtp = $local ? 'smtp.free.fr' : $config->system->mail->smtp;

    if ($smtp)
      parent::send(new Zend_Mail_Transport_Smtp($smtp));
    else
      parent::send(new Zend_Mail_Transport_Sendmail());
  }

  function replyTo($adelec, $nom = '')
  {
    $this->addHeader('Reply-To',
		     $nom ? $nom." <".$adelec.">" : $adelec);
  }

  function notifyAdmins()
  {
    $ti = new Individus();
    $db = $ti->getAdapter();
    $select = $db->select()
      ->distinct()
      ->from('individus')
      ->join('membership',
	     'individus.username = membership.username'.
	     ' AND '.
	     "membership.groupname = 'admins'",
	     array());

    $admins = $ti->fetchAll($select);
    $tos = array();
    foreach($admins as $admin)
      $this->addBcc($admin->adelec, $admin->getFullName(true));
  }

  function notifyChefs()
  {
    $tu = new Unites;
    $ti = new Individus;
    $s = $ti->select()
      ->from('individus')
      ->join('unites',
	     'unites.parent IS NULL',
	     array())
      ->join('appartient',
	     'appartient.unite = unites.id'.
	     ' AND '.
	     "appartient.role = 'chef'".
	     ' AND '.
	     'appartient.fin IS NULL',
	     array())
      ->where('individus.id = appartient.individu');
    $chefs = $ti->fetchAll($s);

    $tos = array();
    foreach($chefs as $chef)
      $this->addBcc($chef->adelec, $chef->getFullName(false));
  }
}