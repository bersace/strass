<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com), 2008 Étienne Bersac
 * @license    AGPL
 */

/** Zend_Controller_Request_Abstract */
require_once 'Zend/Controller/Request/Http.php';

/**
 * Strass_Controller_Request_Http
 *
 * HTTP request object for use with Zend_Controller family. avec hack
 * pour fonctionner avec renommage d'entête par mod_rewrite
 *
 * @uses Zend_Controller_Request_Abstract
 * @package Zend_Controller
 * @subpackage Request
 */
class Strass_Controller_Request_Http extends Zend_Controller_Request_Http
{
    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws Zend_Controller_Request_Exception
     */
    public function getHeader($header)
    {
        if (empty($header)) {
            require_once 'Zend/Controller/Request/Exception.php';
            throw new Zend_Controller_Request_Exception('An HTTP header name is required');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // Try to get it from the $_SERVER array first
        $temp = 'REDIRECT_HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }

        return false;
    }
}
