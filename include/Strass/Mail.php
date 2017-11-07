<?php

class Strass_Mail extends Zend_Mail
{
  protected $_doc;
  static $mail_dir = 'data/mails';

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

    $this->setSubject($metas->title);

    $from = getenv('STRASS_EMETTEUR');
    $this->setFrom($from ? $from : 'none@localhost', 'Strass');

    $this->_doc = $d = new Wtk_Document($metas);
    $d->level+= 2;
    $d->addStyleComponents('mail');
    $d->setStyle(Wtk_Document_Style::factory($config->system->style));
    $d->embedStyle();
    $d->addFlags('mail');

    // :P
    $this->addHeader('X-Mailer', 'Strass');
    $this->addHeader('X-MailGenerator', 'Wtk');
  }

    function generateDevFilename()
    {
        return $_SERVER['REQUEST_TIME'] . '_' . wtk_strtoid($this->getSubject()) . '_' . mt_rand() . '.eml';
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

    $config = Zend_Registry::get('config');

    if (!$config->system->mail->enable) {
      return true;
    }

    $r = Wtk_Render::factory($this->_doc, 'Txt');
    $this->setBodyText($r->render());
    $r = Wtk_Render::factory($this->_doc, 'Html5');
    $this->setBodyHTML($r->render());

    if (getenv('STRASS_MODE') == 'devel') {
        @mkdir(self::$mail_dir);
      return parent::send(new Zend_Mail_Transport_File(array(
          'path' => self::$mail_dir,
          'callback' => array($this, 'generateDevFilename'),
      )));
    }
    else
      return parent::send(new Zend_Mail_Transport_Smtp(getenv('STRASS_SMTP')));
  }

  function replyTo($adelec, $nom = '')
  {
    $this->addHeader('Reply-To',
		     $nom ? $nom." <".$adelec.">" : $adelec);
  }

  function notifyAdmins()
  {
    $t = new Individus;
    $admins = $t->findAdmins();
    foreach($admins as $admin) {
        if ($admin->adelec && $admin->findUser()->send_mail)
            $this->addBcc($admin->adelec, $admin->getFullName(true));
    }
  }

  function notifyChefs()
  {
    $t = new Individus;
    $chefs = $t->findChefsRacines();
    foreach($chefs as $chef) {
        if ($chef->adelec && $chef->findUser()->send_mail)
            $this->addBcc($chef->adelec, $chef->getFullName(false));
    }
  }

  function notifyChefsDe($unite)
  {
      foreach($unite->findMaitrise() as $chef) {
          if ($chef->adelec && $chef->findUser()->send_mail)
              $this->addBcc($chef->adelec, $chef->getFullName(false));
      }
  }
}
