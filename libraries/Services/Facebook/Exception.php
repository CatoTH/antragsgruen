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
 * @copyright 2007-2008 Joe Stump <joe@joestump.net>  
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: 0.2.14
 * @link      http://pear.php.net/package/Services_Facebook
 */

/**
 * Services_Facebook_Exception
 *
 * All calls to the API can result in a few different errors; an HTTP error,
 * an API error or some other random cURL error. In all cases the package will
 * throw a Services_Facebook_Exception.
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Exception extends Exception
{
    /**
     * Last API call
     *
     * This is the URI of the API call that generated the error. The error
     * message and code from the error is passed as well.
     *
     * @var         string      $lastCall       URI of last API call
     */
    protected $lastCall = '';

    /**
     * Constructor
     *
     * @param string $message  The exception's message/info
     * @param int    $code     The error code for the exception
     * @param string $lastCall URI of last API call
     */
    public function __construct($message, $code = 0, $lastCall = '')
    {
        parent::__construct($message, $code);
        $this->lastCall = $lastCall;
    }

    /**
     * Returns last API call
     *
     * @return      string      
     * @see         Services_Facebook_Exception::$lastCall
     */
    public function getLastCall()
    {
        return $this->lastCall;
    }
}

?>
