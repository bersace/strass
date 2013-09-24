<?php

class Wtk_Form_Error extends Wtk_Element
{
  protected	$message;

  function __construct (Exception $e)
  {
    $i = $e->getInstance ();

    $this->message = new Wtk_Paragraph ();

    if ($i instanceof Wtk_Form_Model_Instance) {
      // remplacer tout les %s par des liens vers l'instance.
      $inlines = explode ("%s", $e->getFormat ());
      $href = $_SERVER['REQUEST_URI']."#".wtk_strtoid($i->path);
      $label = $e->getInstance ()->label;

      $ils = array();
      foreach($inlines as $inline) {
	$ils[] = new Wtk_Inline($inline);
      }

      $pre = null;
      foreach($ils as $il) {
	if ($pre) {
	  $this->message->addChild(new Wtk_Link ($href, $label));
	}

	$this->message->addChild($il);
	$pre = $il;
      }
    }
    else {
      $this->message->addChild(new Wtk_Inline($e->getFormat()));
    }
  }

  function template ()
  {
    $tpl = $this->elementTemplate ();
    $tpl->addChild ('message', $this->message->template ());
    return $tpl;
  }
}

?>