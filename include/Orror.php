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

define('E_EXCEPTION', E_ALL*2);

/**
 * Orror est un ensemble de fonction permettant de gérer plus
 * simplement les erreurs. (traitement, traduction, etc.)
 */
class Orror {
  static $html;

  /**
   * Initialise la gestion des erreurs.
   *
   * @param   int     Le niveau de report d'erreur.
   * @param   mixed   Le callback de traitement d'une erreur
   *                  (affichage ou stockage, etc...)
   */
  static function init($level = E_ALL,
		       $output_handler = array('Orror', 'output'),
		       $sink_handler = null,
		       $handle_exceptions = TRUE)
  {
    error_reporting($level);
    $_ENV['ORROR_HANDLER'] = $output_handler;
    if ($sink_handler)
      $_ENV['ORROR_SINK'] = $sink_handler;
    set_error_handler(array('Orror', 'error_handler'));
    if ($handle_exceptions)
      set_exception_handler(array('Orror', 'exception_handler'));
    Orror::$html = ini_get('html_errors');
  }

  /**
   * Clos la gestion des erreurs.
   */
  static function shutdown()
  {
    restore_error_handler();
    restore_exception_handler();
    unset($_ENV['ORROR_HANDLER']);
  }

  /**
   * Affiche un message formaté et traduit avec gettext. Donnez les
   * arguments de printf comme si vous utilisiez printf. Si des
   * argument supplémentaires sont données, le dernier est utilisé
   * comme niveau de l'erreur.
   *
   * Attention, aucune gestion des domaines gettext n'est fournit.
   *
   * @param   string  La chaine du messages d'erreur.
   * @param   mixed   ... les paramètres de printf.
   * @param   int     Le niveau d'erreur.
   * @param   int     L'id du des infos de backtrace à utiliser.
   */
  static function trigger($string)
  {
    $argv = func_get_args();
    $argc = func_num_args();
    $error = array();

    // Construction de l'appel de printf.
    $printf_argc = substr_count($string, '%');
    $printf_argv = array(gettext($string));
    //         $printf_argv[0] = str_replace('%', '%%', $printf_argv[0]);
    for($i = 1 ; $i <= $printf_argc ; $i++) {
      $printf_argv[] = $argv[$i];
    }

    $error['msg'] = call_user_func_array('sprintf', $printf_argv);

    // Selection des paramètres supplémentaires.
    $sup_argv = array_slice($argv, $printf_argc + 1);

    // Recherche du fichier et de la ligne de l'erreur.
    $dbg = debug_backtrace();
    $id = isset($sup_argv[1]) ? $sup_argv[1] : 0;
    $error['file'] = $dbg[$id]['file'];
    $error['line'] = $dbg[$id]['line'];
    $error['class'] = isset($dbg[$id+1]) ? $dbg[$id+1]['class'] : null;
    $error['function'] = isset($dbg[$id+1]) ? $dbg[$id+1]['function'] : null;

    // Selection du type d'erreur.
    $error['level'] = isset($sup_argv[0]) ? $sup_argv[0] : E_USER_NOTICE;
    $error['backtrace']	= array_slice($dbg, $id);

    Orror::callHandler($error);
  }

  /**
   * Traiteur d'erreur remplaçant de PHP.
   */
  static function error_handler()
  {
    $args = func_get_args();

    $dbg = debug_backtrace();
    $current_stack = array_pop($dbg);
    unset($dbg[0]);

    $error = array('msg'        => $args[1],
		   'file'       => $args[2],
		   'line'       => $args[3],
		   'class'      => isset($dbg[0]['class']) ? $dbg[0]['class'] : null,
		   'function'   => isset($dbg[0]['function']) ? $dbg[0]['function'] : null,
		   'level'      => $args[0],
		   'backtrace'	=> $dbg);

    Orror::callHandler($error);
  }

  static function exception_handler($e)
  {
    $error = array('msg'	=> $e->getMessage(),
		   'file'	=> $e->getFile(),
		   'line'	=> $e->getLine(),
		   'class'	=> null,
		   'function'	=> null,
		   'level'	=> E_EXCEPTION,
		   'backtrace'	=> $e->getTrace(),
		   'exception'	=> get_class($e));
    Orror::callHandler($error);
  }

  static function print_r($datas, $return = FALSE)
  {
    $msg = Orror::$html ? "<pre>" : "";
    foreach($datas as $data) {
      switch(gettype($data)) {
      case 'boolean':
      case 'null':
	$msg.= var_export($data, true);
	$msg.= "\n";
	break;
      default:
	$data = print_r($data, true);
	if (Orror::$html)
	  $data = htmlspecialchars($data);
	$msg.= $data."\n";
	break;
      }
    }
    $msg.= Orror::$html ? "</pre>\n" : "\n";

    if (!$return) {
      echo $msg;
      return;
    }
    else {
      return $msg;
    }
  }

  /**
   * Termine le programme et affiche le fichier et la ligne où le
   * programme a été arrêté. Tout les arguments sont dumpé avec
   * print_r.
   */
  static function kill()
  {
    $args = func_get_args();

    $error['msg'] = self::print_r($args, true);
    if (Orror::$html)
      $error['msg'].= "<strong>Kill !</strong>\n";
    else
      $error['msg'].= "**Kill !**\n";

    // Recherche du fichier et de la ligne de l'erreur.
    $dbg = debug_backtrace();
    $error['file'] = $dbg[0]['file'];
    $error['line'] = $dbg[0]['line'];
    $error['class'] = isset($dbg[0]['class']) ? $dbg[0]['class'] : NULL;
    $error['function'] = isset($dbg[0]) ? $dbg[0]['function'] : NULL;
    $error['level'] = E_USER_NOTICE;
    $error['backtrace'] = $dbg;

    Orror::callHandler($error);
    Orror::sink();
  }

  static function sink()
  {
    if (array_key_exists('ORROR_SINK', $_ENV)) {
      call_user_func($_ENV['ORROR_SINK']);
    }
    else {
      die();
    }
  }

  /**
   * Dump des variables. Tout les variables passés en paramètres
   * sont dumpé et une erreurs est déclenchée, de niveau
   * E_USER_NOTICE.
   */
  static function dump()
  {
    $args = func_get_args();
    $error['msg'] = self::print_r($args, TRUE);

    // Recherche du fichier et de la ligne de l'erreur.
    $dbg = debug_backtrace();

    $error['file']      = isset($dbg[0]) ? $dbg[0]['file'] : '';
    $error['line']      = isset($dbg[0]) ? $dbg[0]['line'] : '';
    $error['class']     = isset($dbg[0]['class']) ? $dbg[0]['class'] : null;
    $error['function']  = isset($dbg[0]) ? $dbg[0]['function'] : null;
    $error['level']     = E_USER_NOTICE;
    array_pop($dbg);
    $error['backtrace']	= $dbg;

    Orror::callHandler($error);
  }

  static function comment()
  {
    $args = func_get_args();
    $error['msg'] = self::print_r($args, TRUE);

    // Recherche du fichier et de la ligne de l'erreur.
    $error['file']      = null;
    $error['line']      = null;
    $error['class']     = null;
    $error['function']  = null;
    $error['level']     = null;

    Orror::callHandler($error);
  }

  /**
   * Afficheur par défaut des erreurs.
   */
  static function output($msg, $file, $line, $class, $function, $level, $backtrace, $exception = null)
  {
    // Faut-il distinguer les erreurs sys des erreurs user ?
    $errors = array(E_NOTICE        => "Notice",
		    E_WARNING       => "Warning",
		    // E_ERROR ne passe pas par ici ...
		    E_USER_NOTICE   => "Notice",
		    E_USER_WARNING  => "Warning",
		    E_USER_ERROR    => "Error",
		    E_STRICT	    => "Strict",
		    E_EXCEPTION	    => $exception." "."Exception");

    if (defined('E_RECOVERABLE_ERROR')) {
      $errors[E_RECOVERABLE_ERROR] = "Recoverable error";
    }

    if (defined('E_DEPRECATED')) {
      $errors[E_DEPRECATED] = "Obsolète";
    }

    if (ini_get('html_errors')) {
      echo '<div class="error">';
      if ($level) {
	echo
	  "<p><b>".
	  $errors[$level].
	  "</b>: ".
	  ($function || ($class && $function) ? "<span>".($class ? $class."::" : "").$function."()</span>: " : "");
	echo $msg."<br/>\n";
	echo
	  "<span>".
	  sprintf("in %s on line %s.",
		  "<b>".str_replace(dirname($_SERVER['SCRIPT_FILENAME']).'/', '', $file)."</b>",
		  "<b>".$line."</b>".
		  "</span><br/>\n");

	if (count($backtrace)) {
	  echo "<b>"."Backtrace:"."</b><br/>\n";
	  foreach ($backtrace as $i => $step) {
	    if (isset($step['file']) && isset($step['line'])) {
	      $class = isset($step['class']) ? $step['class']."." : "";
	      echo "#".$i." ".str_replace(dirname($_SERVER['SCRIPT_FILENAME']).'/', '', $step['file']).":".$step['line']." ".$class.$step['function']."()<br/>\n";
	    }
	  }
	}

	echo "<p>\n";
	echo "</div>\n";
      }
      else {
	echo "<!--\n".$msg."-->\n";
      }
    }
    else {
      echo
	$errors[$level].": ".
	($function || ($class && $function) ? ($class ? $class."::" : "").$function."(): " : "");
      echo $msg."\n";
      echo sprintf("in %s on line %s.\n",
		   str_replace(dirname($_SERVER['SCRIPT_FILENAME']).'/', '', $file),
		   $line);

      if (count($backtrace)) {
	echo "Backtrace:\n";
	foreach ($backtrace as $i => $step) {
	  if (isset($step['file']) && isset($step['line'])) {
	    $class = isset($step['class']) ? $step['class']."." : "";
	    echo "   #".$i." ".str_replace(dirname($_SERVER['SCRIPT_FILENAME']).'/', '', $step['file']).":".$step['line']." ".$class.$step['function']."()\n";
	  }
	}
      }

      echo "\n";
    }
  }

  static function callHandler($params)
  {
    if ($params['level'] & error_reporting() || $params['level'] == E_EXCEPTION) {
      $handler = isset($_ENV['ORROR_HANDLER']) ? $_ENV['ORROR_HANDLER'] : array('Orror', 'output');
      call_user_func_array($handler, $params);
    }
  }
}
