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
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Http.php 5601 2007-07-07 14:42:03Z thomas $
 */


/**
 * @see Zend_Auth_Adapter_Interface
 */
require_once 'Zend/Auth/Adapter/Interface.php';


/**
 * HTTP Authentication Adapter
 *
 * Extends Zend_Auth_Adapter_Http to allow the use of Basic auth with
 * crypted password database.
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @todo       Support auth-int
 * @todo       Track nonces, nonce-count, opaque for replay protection and stale support
 * @todo       Support Authentication-Info header
 */
class Knema_Auth_Adapter_Http extends Zend_Auth_Adapter_Http
{
    /**
     *
     * @var string
     */
    protected $_basicProcess = null;

    /**
     * Constructor
     *
     * @param  array $config Configuration settings:
     *    'accept_schemes' => 'basic'|'digest'|'basic digest'
     *    'realm' => <string>
     *    'digest_domains' => <string> Space-delimited list of URIs
     *    'nonce_timeout' => <int>
     *    'use_opaque' => <bool> Whether to send the opaque value in the header
     *    'algorithm' => <string> See $_supportedAlgos. Default: MD5
     *    'proxy_auth' => <bool> Whether to do authentication as a Proxy,
     *    'basic_process' => <string> Func to process password before comparison
     * @throws Zend_Auth_Adapter_Exception
     * @return void
     */
    public function __construct(array $config)
    {
      parent::__construct($config);
	if (isset($config['basic_process']) && is_callable($config['basic_process'])) {
	  $this->_basicProcess = $config['basic_process'];
	}
    }

    /**
     * Setter for the _basicResolver property
     *
     * @param  Zend_Auth_Adapter_Http_Resolver_Interface $resolver
     * @return Zend_Auth_Adapter_Http Provides a fluent interface
     */
    public function setBasicResolver(Zend_Auth_Adapter_Http_Resolver_Interface $resolver)
    {
        $this->_basicResolver = $resolver;

        return $this;
    }

    /**
     * Getter for the _basicResolver property
     *
     * @return Zend_Auth_Adapter_Http_Resolver_Interface
     */
    public function getBasicResolver()
    {
        return $this->_basicResolver;
    }

    /**
     * Basic Authentication
     *
     * @param  string $header Client's Authorization header
     * @throws Zend_Auth_Adapter_Exception
     * @return Zend_Auth_Result
     */
    protected function _basicAuth($header)
    {
        if (empty($header)) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('The value of the client Authorization header is required');
        }
        if (empty($this->_basicResolver)) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('A basicResolver object must be set before doing Basic '
                                                . 'authentication');
        }

        // Decode the Authorization header
        $auth = substr($header, strlen('Basic '));
        $auth = base64_decode($auth);
        if (!$auth) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Unable to base64_decode Authorization header value');
        }

        // See ZF-1253. Validate the credentials the same way the digest
        // implementation does. If invalid credentials are detected,
        // re-challenge the client.
        if (!ctype_print($auth)) {
            return $this->_challengeClient();
        }
        // Fix for ZF-1515: Now re-challenges on empty username or password
        $creds = array_filter(explode(':', $auth));
        if (count($creds) != 2) {
            return $this->_challengeClient();
        }

	if ($this->_basicProcess) {
	  $creds[1] = call_user_func($this->_basicProcess, $creds[1]);
	}

        $password = $this->_basicResolver->resolve($creds[0], $this->_realm);

        if ($password && $password == $creds[1]) {
            $identity = array('username'=>$creds[0], 'realm'=>$this->_realm);
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity);
        } else {
            return $this->_challengeClient();
        }
    }
}
