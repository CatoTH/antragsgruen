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
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2007-2008 Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: 0.2.14
 * @link      http://pear.php.net/package/Services_Facebook
 */

require_once 'Services/Facebook/Common.php';

/**
 * Facebook Application Interface
 *
 * <code>
 * <?php
 * require_once 'Services/Facebook.php';
 * $api = new Services_Facebook();
 * $app = $api->application->getPublicInfoById('123');
 * echo 'Canvas name of the app is: ' . $app->canvas_name . '<br />';
 * ?>
 * </code>
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Application extends Services_Facebook_Common
{
    /**
     * Get the public information of an application by it's ID
     *
     * @param string $id Application ID to get info of
     * 
     * @return object SimpleXMLObject of the public info
     * 
     * @link http://wiki.developers.facebook.com/index.php/Application.getPublicInfo
     **/
    public function getPublicInfoById($id)
    {
        return $this->callMethod('application.getPublicInfo', array(
                           'application_id' => $id
                ));
        
    }
    
    /**
     * Get the public information of an application by it's API key
     *
     * @param string $api_key Application API key to get the info of
     * 
     * @return object SimpleXMLObject of the public info
     * 
     * @link http://wiki.developers.facebook.com/index.php/Application.getPublicInfo
     **/
    public function getPublicInfoByAPIKey($api_key)
    {
        return $this->callMethod('application.getPublicInfo', array(
                           'application_api_key' => $api_key
                ));
    }
    
    /**
     * Get the public information of an application by it's canvas name
     *
     * @param string $canvas_name Application's canvas name
     * 
     * @return object SimpleXMLObject of the public info
     * 
     * @link http://wiki.developers.facebook.com/index.php/Application.getPublicInfo
     **/
    public function getPublicInfoByCanvasName($canvas_name)
    {
        return $this->callMethod('application.getPublicInfo', array(
                           'application_canvas_name' => $canvas_name
                ));
        
    }
    
}
