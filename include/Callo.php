<?php

  /**
   * Brief description of the file.
   *
   * PHP version 4 and 5.
   *
   * LICENSE: This program is free software; you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation; either version 2 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program; if not, write to the Free Software
   * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
   *
   * @author      Étienne Bersac <bersace03@free.fr>
   * @copyright	© 2005 Étienne Bersac
   * @license	http://www.gnu.org/licenses/gpl.txt GPL
   */

  /* Callo est une surcouche à Zend_Translate pour utiliser Gettext */

require_once 'Zend/Translate.php';

$_ENV['TRANSLATE'] = array();

function __($string)
{
  $translate = end($_ENV['TRANSLATE']);
  return $translate instanceof Zend_Translate ? $translate->_($string) : $string;
}

// printf + _
function P_($string)
{
  $argv = func_get_args ();
  echo call_user_func_array ('S_', $argv);
}

// sprintf + _
function S_($string)
{
  $argv = func_get_args();
  $argv[0] = __($string);

  return call_user_func_array('sprintf', $argv);
}

/**
 * Classe de confort pour la gestion de la traduction avec Gettext.
 */
class Callo
{

  /**
   * Défini un domain de traduction gettext sans perdre le
   * précédent.
   */
  static function start($domain, $locale = null, $dir = 'data/locale')
  {
    // définition de la locale
    $locale = $locale === null || !Zend_Locale::isLocale($locale) ? new Zend_Locale() : $locale;
    // définition du fichier .mo
    $filename = $dir.'/'.$locale.'/LC_MESSAGES/'.$domain.'.mo';

    if (is_readable($filename)) {
      $translate = new Zend_Translate(Zend_Translate::AN_GETTEXT,
				      $filename,
				      $locale->toString());
    }
    else {
      $translate = null;
    }

    // Ajoute le traducteur dans la pile.
    array_push($_ENV['TRANSLATE'], $translate);
  }

  /**
   * Retourne le domaine courant.
   */
  static function current()
  {
    return end($_ENV['TRANSLATE']);
  }

  /**
   * Clos l'utilisation d'un domaine et remet le précédent en
   * utilisation.
   */
  static function end()
  { 
    // Supprimer le domaine courant.
    array_pop($_ENV['TRANSLATE']);
  }
}
?>
