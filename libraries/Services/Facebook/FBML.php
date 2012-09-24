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
 * Facebook FBML Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_FBML extends Services_Facebook_Common
{
    /**
     * Refresh an image cache
     *
     * Facebook caches images from your application. If you want Facebook to
     * refresh your image's cache use this URL to tell Facebook to re-request
     * and re-cache your image.
     *
     * @param string $url URL of image to refresh
     * 
     * @return boolean
     */
    public function refreshImgSrc($url)
    {
        $result = $this->callMethod('fbml.refreshImgSrc', array(
            'session_key' => $this->sessionKey,
            'url' => $url
        ));

        $check = intval((string)$result);
        return ($check == 1);
    }

    /**
     * Fetches and re-caches the content stored at the given URL
     * 
     * @param string $url The absolute URL from which to fetch content.
     * 
     * @return      boolean
     */
    public function refreshRefUrl($url)
    {
        $result = $this->callMethod('fbml.refreshRefUrl', array(
            'session_key' => $this->sessionKey,
            'url' => $url
        ));

        $check = intval((string)$result);
        return ($check == 1);
    }

    /**
     * Associates a given "handle" with FBML markup
     * 
     * @param string $handle The handle to associate with the given FBML
     * @param string $fbml   The FBML to associate with the given handle
     * 
     * @return boolean
     * @link http://wiki.developers.facebook.com/index.php/Fbml.setRefHandle
     */
    public function setRefHandle($handle, $fbml)
    {
        $result = $this->callMethod('fbml.setRefHandle', array(
            'session_key' => $this->sessionKey,
            'handle' => $handle,
            'fbml' => $fbml
        ));

        $check = intval((string)$result);
        return ($check == 1);
    }
}

?>
