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
 * Facebook Admin Interface
 *
 * <code>
 * <?php
 * require_once 'Services/Facebook.php';
 * $api = new Services_Facebook();
 * echo 'Notifications that we can send per day, behalf 
 *       of a user: ' . $api->admin->getNotificationsPerDay() . '<br />';
 * echo 'Requests that we can send per day, behalf of a user: ' . 
 *       $api->admin->getRequestsPerDay(). '<br />';
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
class Services_Facebook_Admin extends Services_Facebook_Common
{
    /**
     * Default application property fields
     * 
     * @link http://wiki.developers.facebook.com/index.php/ApplicationProperties
     */
    protected $applicationFields = array(
        'application_name', 'callback_url', 'post_install_url', 'edit_url', 
        'dashboard_url', 'uninstall_url', 'ip_list', 'email', 'description', 
        'use_iframe', 'desktop', 'is_mobile', 'default_fbml', 'default_column', 
        'message_url', 'message_action', 'about_url', 'private_install',
        'installable', 'privacy_url', 'help_url', 'see_all_url', 'tos_url',
        'dev_mode', 'preload_fql'
    );
    
    /**
     * Gets property values previously set for an application.
     *
     * @param array $properties The properties to get, default is all
     *
     * @return array Array with all requested properties
     *
     * @link http://wiki.developers.facebook.com/index.php/Admin.getAppProperties
     */
    public function getAppProperties($properties = array())
    {
        if (!count($properties)) {
            $properties = $this->applicationFields;
        }
        
        //FB accepts the app properties as a json array
        $jsonProperties = json_encode($properties);
        
        $result = $this->callMethod('admin.getAppProperties', array(
                                     'properties' => $jsonProperties
                        ));
        
        //The response from Facebook is in JSON
        return json_decode((string)$result);
    }
    
    /**
     * Sets multiple properties for an application.
     *
     * @param array $properties Property / value assocative array of properties
     *
     * @return boolean True on success
     *
     * @link http://wiki.developers.facebook.com/index.php/Admin.setAppProperties
     */
    public function setAppProperties($properties = array())
    {
        $jsonArray = array();
        foreach ($properties as $property => $value) {
            if (in_array($property, $this->applicationFields)) {
                $jsonArray[$property] = $value;
            }
        }
        $jsonProperties = json_encode($jsonArray);
        
        $result = $this->callMethod('admin.setAppProperties', array(
                                   'properties' => $jsonProperties
                    ));

        return (intval((string) $result) == 1);
    }
    
    
    /**
     * Get the number of notifications your application can send on
     * behalf of a user per day.
     *
     * @return int Number of notifications
     *
     * @link http://wiki.developers.facebook.com/index.php/Admin.getAllocation
     */
    public function getNotificationsPerDay()
    {
        return (int)$this->callMethod('admin.getAllocation', array(
                                  'session_key' => $this->sessionKey,
                                  'integration_point_name' => 'notifications_per_day'
                ));
    }
    
    /**
     * Get the number of requests your application can send on behalf 
     * of a user per day.
     *
     * @return int Number of requests
     *
     * @link http://wiki.developers.facebook.com/index.php/Admin.getAllocation
     **/
    public function getRequestsPerDay()
    {
        return (int)$this->callMethod('admin.getAllocation', array(
                                  'session_key' => $this->sessionKey,
                                  'integration_point_name' => 'requests_per_day'
                ));
    }
    
}
