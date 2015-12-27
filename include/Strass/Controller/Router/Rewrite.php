<?php

class Strass_Controller_Router_Rewrite extends Zend_Controller_Router_Rewrite
{
    /* Assembe l'URL avec le première route qui y arrive. $name est toujours
     * ignoré. */
    public function assemble($userParams, $name=null, $reset=false, $encode=true)
    {
        if (!is_array($userParams)) {
            require_once 'Zend/Controller/Router/Exception.php';
            throw new Zend_Controller_Router_Exception('userParams must be an array');
        }

        // Use UNION (+) in order to preserve numeric keys
        $params = $userParams + $this->_globalParams;

        /* Inject parameters from request. */
        if (!$reset)
            $params = $params + $this->_frontController->getRequest()->getParams();

        /* Essayer chaque route. Si false, alors cette URL n'est pas
         * assemblable par cette route. */
        foreach(array_reverse($this->_routes, true) as $name => $route) {
            if (($url = $route->assemble($params, $reset, $encode)) === false)
                continue;

            if (!preg_match('|^[a-z]+://|', $url))
                $url = rtrim(
                    $this->getFrontController()->getBaseUrl(),
                    self::URI_DELIMITER) . self::URI_DELIMITER . $url;

            break;
        }

        return $url;
    }
}
