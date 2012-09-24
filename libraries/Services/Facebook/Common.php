<?php

/**
 * PHP5 interface for Facebook's REST API
 *
 * PHP version 5.1.0+
 *
 * LICENSE: This source file is subject to the New BSD license that is 
 * available through the world-wide-web at the following URI:
 * http://www.opensource.org/licenses/bsd-license.php. If you did not receive  
 * a copy of the New BSD License and are unable to obtain it through the web, 
 * please send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Services
 * @package   Services_Facebook
 * @author    Joe Stump <joe@joestump.net> 
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2007-2008 Joe Stump <joe@joestump.net>  
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: 0.2.14
 * @link      http://pear.php.net/package/Services_Facebook
 */

require_once 'Services/Facebook.php';
require_once 'Services/Facebook/Exception.php';
require_once 'Validate.php';

/**
 * Common class for all Facebook interfaces
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @author   Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
abstract class Services_Facebook_Common
{
    /**
     * URI of Facebook's REST API
     *
     * @var         string      $api
     */
    protected $api = '';

    /**
     * Version of the API to use
     *
     * @var         string      $version
     */
    protected $version = '1.0';

    /**
     * Currently logged in user
     * 
     * @var         string      $sessionKey
     */
    public $sessionKey = '';

    /**
     * Use secret as session secret or not
     *
     * @var bool $useSessionSecret
     */
    public $useSessionSecret = false;

    /**
     * Construct 
     * 
     * Construct.  Set API URL, something that may change per driver.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setAPI(Services_Facebook::$apiURL);
    }

    /**
     * Call method 
     * 
     * Used by all of the interface classes to send a request to the Facebook
     * API. It builds the standard argument list, munges that with the 
     * arguments passed to it, signs the request and sends it along to the
     * API. 
     *
     * Once the request has taken place the cURL response is checked to make
     * sure no low level HTTP errors occurred, the XML is parsed using 
     * SimpleXml and then checked for Facebook errors. 
     * 
     * Any formal error encountered is thrown as an exception.
     * 
     * @param mixed $method Method to call
     * @param array $args   Arguments to send
     *
     * @return mixed Result
     */
    public function callMethod($method, array $args = array())
    {
        $this->updateArgs($args, $method);

        $response = $this->sendRequest($args);
        $result   = $this->parseResponse($response);

        return $result;
    }

    /**
     * Send a request to the API
     *
     * @param array $args API arguments passed as GET args
     *
     * @return object Response as an instance of SimleXmlElement
     * @throws Services_Facebook_Exception
     */
    protected function sendRequest(array $args)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, Services_Facebook::$timeout);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, Services_Facebook::$useDnsCache);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Services_Facebook_Exception(
                curl_error($ch), curl_errno($ch), $args['method'], $this->api
            );
        }

        curl_close($ch);

        return $response;
    }

    /**
     * parseResponse 
     * 
     * Parses the raw response from Facebook
     *
     * @param mixed $response Response xml
     *
     * @return string Parsed response
     */
    protected function parseResponse($response)
    {
        $xml = simplexml_load_string($response);
        if (!$xml instanceof SimpleXmlElement) {
            throw new Services_Facebook_Exception(
                'Could not parse XML response', 0, $this->api
            );
        }

        $error = $this->checkRequest($xml);
        if (is_array($error) && count($error)) {
            throw new Services_Facebook_Exception($error['message'],
                                                  $error['code'], $this->api);
        }

        return $xml;
    }

    /**
     * Update arguments
     * 
     * Updates the arguments with api_key, version, etc. Then
     * signs it.
     *
     * @param array &$args  Arguments being sent
     * @param mixed $method Method being called
     *
     * @return void
     */
    protected function updateArgs(array &$args, $method)
    {
        $args['api_key'] = Services_Facebook::$apiKey;
        $args['v']       = $this->version;
        $args['format']  = 'XML';
        $args['method']  = $method;
        $args['call_id'] = microtime(true);
        $args            = $this->signRequest($args);
    }

    /**
     * Sign the request
     *
     * @param array $args Arguments for the request to be signed
     * 
     * @return array Arguments with the appropriate sig added
     * @see Services_Facebook::$secret
     */
    protected function signRequest(array $args) 
    {
        if (isset($args['sig'])) {
            unset($args['sig']);
        }

        if ($this->useSessionSecret) {
            $args['ss'] = 1;
        }

        ksort($args);

        $sig = '';
        foreach ($args as $k => $v) {
            $sig .= $k .'=' . $v;
        }

        $sig        .= Services_Facebook::$secret;
        $args['sig'] = md5($sig);
        return $args;
    }

    /**
     * Check if request resulted in an error
     *
     * @param object $xml Instance of SimpleXmlElement
     * 
     * @return Array with code/message or false if no error is present
     */
    protected function checkRequest($xml)
    {
        $message = null;
        $code    = 0;
        switch ($this->version) {
        case '1.0':
            if (isset($xml->error_code)) {
                $code = (int)$xml->error_code;
            }

            if (isset($xml->error_msg)) {
                $message = $xml->error_msg;
            }
            break;
        default:
            if (isset($xml->fb_error->code)) {
                $code = (int)$xml->fb_error->code;
            }

            if (isset($xml->fb_error->msg)) {
                $message = $xml->fb_error->msg;
            }
            break;
        }

        if ($code > 0 || !is_null($message)) {
            return array('code' => $code, 'message' => $message);
        }

        return false;
    }

    /**
     * Get API
     *
     * @return string API url
     */
    public function getAPI()
    {
        return $this->api;
    }

    /**
     * Set API
     * 
     * @param mixed $api Api url to set
     *
     * @return void
     */
    public function setAPI($api)
    {
        if (!Validate::uri($api)) {
            throw new Services_Facebook_Exception('Invalid API: ' . $api);
        }

        $this->api = $api;
    }
}

?>
