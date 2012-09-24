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

require_once 'Services/Facebook/Common.php';

/**
 * Facebook FQL (Facebook Query Language) Interface
 *
 * Facebook allows you to generically query data from their API in an SQL-like
 * interface that they call FQL. This class allows you to send a raw query to
 * the API and get a raw XML response back in the form of a SimpleXmlElement.
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_FQL extends Services_Facebook_Common
{
    /**
     * Send a FQL query
     *
     * Senda a query to the Facebook API and returns the response in the form
     * for a SimpleXmlElement.
     *
     * <code>
     * <?php
     * require_once 'Services/Facebook.php';
     * $api = Services_Facebook::factory('FQL');
     * $fql = 'SELECT *
     *         FROM user
     *         WHERE uid IN (123, 345, 567)';
     * $result = $api->query($fql);
     * foreach ($result->user as $user) {
     *     echo (string)$user->first_name;
     * }
     * ?>
     * </code>
     *
     * @param string $query FQL query string
     *
     * @return object Instance of SimpleXMLElement
     */
    public function query($query)
    {
        return $this->callMethod('fql.query', array(
            'session_key' => $this->sessionKey,
            'query' => $query
        ));
    }
}

?>
