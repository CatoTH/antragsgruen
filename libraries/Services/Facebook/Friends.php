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
 * Facebook Friends Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Friends extends Services_Facebook_Common
{
    /**
     * Are groupings of friends friends?
     *
     * If an array matrix of friend pairings is passed then a SimpleXmlElement
     * is passed back. If it's a single pairing of friends then a boolean is
     * passed back.
     *
     * @param array $uid1 First uid to check 
     * @param array $uid2 Second one to see if friends with $uid1
     * 
     * @return mixed Instance of SimpleXML response or boolean
     * @link http://wiki.developers.facebook.com/index.php/Friends.areFriends
     */
    public function areFriends($uid1, $uid2)
    {
        if (is_array($uid1)) {
            $uids1 = implode(',', $uid1);
        } else {
            $uids1 = $uid1;
        }

        if (is_array($uid2)) {
            $uids2 = implode(',', $uid2);
        } else {
            $uids2 = $uid2;
        }

        $res = $this->callMethod('friends.areFriends', array(
            'session_key' => $this->sessionKey,
            'uids1' => $uids1,
            'uids2' => $uids2
        )); 

        if (!is_array($uid1) && !is_array($uid2)) {
            return (intval((string)$res->friend_info->are_friends) == 1);
        }

        return $res;
    }

    /**
     * Get the current user's friends
     *
     * @param string $uid FB uid to get a friend list of
     *
     * @return array A list of uid's of current user's friends
     * @link http://wiki.developers.facebook.com/index.php/Friends.get
     */
    public function get($uid = null)
    {
        $args = array();
        if ($uid !== null) {
            $args['uid'] = (string) $uid;
        } elseif (!empty($this->sessionKey)) {
            $args['session_key'] = $this->sessionKey;
        }

        $result = $this->callMethod('friends.get', $args);

        $ret = array();
        foreach ($result->uid as $uid) {
            $ret[] = (string)$uid;
        }

        return $ret;
    }

    /**
     * Get the current user's friends by list
     * 
     * @param integer $flid The friends list id to fetch
     * 
     * @return array A list of uid's of a particular list from the current user
     * @author Jeff Hodsdon <jeffhodsdon@gmail.com>
     * @link http://wiki.developers.facebook.com/index.php/Friends.get
     */
    public function getByList($flid)
    {
        $result = $this->callMethod('friends.get', array(
            'session_key' => $this->sessionKey,
            'flid' => $flid
        ));
        
        $ret = array();
        foreach ($result->uid as $uid) {
            $ret[] = (string)$uid;
        }
        
        return $ret;
    }

    /**
     * Get a user's friends who are using your application
     *
     * @return array A list of Facebook uid's
     * @link http://wiki.developers.facebook.com/index.php/Friends.getAppUsers
     */
    public function getAppUsers()
    {
        $result = $this->callMethod('friends.getAppUsers', array(
            'session_key' => $this->sessionKey
        ));

        $ret = array();
        foreach ($result->uid as $uid) {
            $ret[] = (string)$uid;
        }

        return $ret;
    }

    /**
     * Get the current user's friend lists
     *
     * @return object SimpleXMLObject with a name and id for each list
     * @author Jeff Hodsdon <jeffhodsdon@gmail.com>
     * @link http://wiki.developers.facebook.com/index.php/Friends.getLists
     */
    public function getLists()
    {
        $result = $this->callMethod('friends.getLists', array(
            'session_key' => $this->sessionKey
        ));
        
        return $result;
    }
}

?>
