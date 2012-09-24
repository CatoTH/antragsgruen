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
 * Facebook Groups Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Groups extends Services_Facebook_Common
{
    /**
     * Get a list of groups
     *
     * @param mixed $uid_or_gids An array of groups or uid's
     * 
     * @return object A SimpleXmlElement with group information
     */
    public function get($uid_or_gids = null)
    {
        $args = array('session_key' => $this->sessionKey);
        if (!is_null($uid_or_gids)) {
            if (is_array($uid_or_gids)) {
                $args['gids'] = implode(',', $uid_or_gids);
            } else {
                $args['uid'] = (string) $uid_or_gids;
            }
        } 
        
        return $this->callMethod('groups.get', $args);
    }

    /**
     * Get members of a group
     *
     * @param integer $gid The group id which you want members of
     * 
     * @return object An instance of SimleXmlElement
     */
    public function getMembers($gid)
    {
        $result = $this->callMethod('groups.getMembers', array(
            'session_key' => $this->sessionKey,
            'guid' => (string)$gid
        ));

        $members = array();
        foreach ($result->members->uid as $member) {
            $members[] = (string) $member;
        }
        return $members;
    } 
}

?>
