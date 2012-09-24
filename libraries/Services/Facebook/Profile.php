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
require_once 'Services/Facebook/Exception.php';

/**
 * Facebook Profile Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Profile extends Services_Facebook_Common
{
    /**
     * Set FBML in a user's profile
     *
     * You are not required to use a session key that belongs to the user whose
     * profile you are changing.
     *
     * @param mixed   $markup Profile markup or array of markup  
     * @param integer $uid    Facebook uid to set FBML for
     * 
     * @return boolean True on success, false on unknown error
     * @link http://wiki.developers.facebook.com/index.php/Profile.setFBML
     */
    public function setFBML($markup, $uid = 0)
    {
        $args = array();

        if (is_array($markup)) {
            static $options = array('profile', 'profile_action', 'mobile_profile');
            foreach ($options as $opt) {
                if (isset($markup[$opt]) && strlen($markup[$opt])) {
                    $args[$opt] = $markup[$opt];
                }
            }
        } elseif (strlen($markup)) {
            $args['profile'] = $markup;
        } else {
            throw new Services_Facebook_Exception(
                'You must provide valid FBML markup'
            );
        }

        if ($uid > 0) {
            $args['uid'] = $uid;
        }

        $result = $this->callMethod('profile.setFBML', $args);
        $check  = intval((string)$result);
        return ($check == 1);
    }

    /**
     * Get the current profile FBML
     *
     * @param integer $uid Facebook uid to fetch FBML for
     * 
     * @return object Instance of SimpleXmlElement
     * @link http://wiki.developers.facebook.com/index.php/Profile.getFBML
     */
    public function getFBML($uid = 0)
    {
        $args = array('session_key' => $this->sessionKey);
        if ($uid > 0) {
            $args['uid'] = $uid;
        }

        $result = $this->callMethod('profile.getFBML', $args);
        return (string) $result;
    }
}

?>
