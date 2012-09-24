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
 * Facebook Users Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Users extends Services_Facebook_Common
{
    /**
     * Default user fields
     *
     * @var array $userFields List of allowed getInfo fields
     */
    public $userFields = array(
        'about_me', 'activities', 'affiliations', 'birthday', 'books',
        'current_location', 'education_history', 'first_name', 'has_added_app',
        'hometown_location', 'hs_info', 'interests', 'is_app_user', 
        'last_name', 'meeting_for', 'meeting_sex', 'movies', 'music', 'name',
        'notes_count', 'pic', 'pic_small', 'pic_square', 'political',
        'profile_update_time', 'quotes', 'relationship_status', 'religion',
        'sex', 'significant_other_id', 'status', 'timezone', 'tv',
        'wall_count', 'work_history'
    );

    /**
     * photoSizes 
     * 
     * @var array $photoSizes Supported photo sizes
     * @see self::getPhoto
     */
    protected $photoSizes = array('big', 'small', 'square');

    /**
     * Has the current user added this application?
     *
     * @return boolean
     */
    public function isAppAdded()
    {
        $result = $this->callMethod('users.isAppAdded', array(
            'session_key' => $this->sessionKey
        )); 

        return (intval((string)$result) == 1);
    }

    /**
     * Is app user
     *
     * Uses the passed in user ID or session key to determine
     * if the user is a user of the application.
     * 
     * @param float $uid Facebook user ID
     *
     * @return bool
     */
    public function isAppUser($uid = null)
    {
        $args = array();
        if ($uid !== null) {
            $args['uid'] = $uid;
        } elseif (!empty($this->sessionKey)) {
            $args['session_key'] = $this->sessionKey;
        } else {
            throw new Services_Facebook_Exception('Users.isAppUser ' .
                'requires a session key or uid, none provided');
        }

        $result = $this->callMethod('users.isAppUser', $args);
        return (intval((string)$result) == 1);
    }

    /**
     * Set a user's status message
     *
     * Set $status to true to clear the status or a string to change the 
     * actual status message.
     *
     * @param mixed $status Set to true to clear status
     * 
     * @return boolean True on success, false on failure
     * @link http://wiki.developers.facebook.com/index.php/Users.setStatus
     * @link http://wiki.developers.facebook.com/index.php/Extended_permission
     */
    public function setStatus($status)
    {
        $args = array(
            'session_key' => $this->sessionKey,
        );

        if (is_bool($status) && $status === true) {
            $args['clear'] = 'true';
        } else {
            $args['status'] = $status;
        }

        $res = $this->callMethod('users.setStatus', $args); 
        return (intval((string)$res) == 1);
    }

    /**
     * Get user info
     *
     * @param mixed $uids   A single uid or array of uids
     * @param array $fields List of fields to retrieve
     * 
     * @return object SimpleXmlElement of result
     * @link http://wiki.developers.facebook.com/index.php/Users.getInfo
     */
    public function getInfo($uids, array $fields = array())
    {
        if (is_array($uids)) {
            $uids = implode(',', $uids);
        } 

        if (!count($fields)) {
            $fields = $this->userFields;
        }

        return $this->callMethod('users.getInfo', array(
            'session_key' => $this->sessionKey,
            'uids' => $uids,
            'fields' => implode(',', $fields)
        ));
    }

    /**
     * Get the currently logged in uid
     *
     * Returns the Facebook uid of the person currently "logged in" as 
     * specified by $sessionKey.
     *
     * @return      string      The uid of the person logged in
     * @see         Services_Digg::$sessionKey
     * @link        http://wiki.developers.facebook.com/index.php/Users.getLoggedInUser
     */
    public function getLoggedInUser()
    {
        $result = $this->callMethod('users.getLoggedInUser', array(
            'session_key' => $this->sessionKey
        ));

        return (string)$result;
    }

    /**
     * Has given extended permission
     *
     * @param string  $perm Permission to check
     * @param string  $uid  User's ID, optional if session key present
     * 
     * @return boolean True if user has enabled extended permission
     * @link http://wiki.developers.facebook.com/index.php/Users.hasAppPermission
     */
    public function hasAppPermission($perm, $uid = null)
    {
        $valid = array(
            'email', 'offline_access', 'status_update', 'photo_upload',
            'create_listing', 'create_event', 'rsvp_event', 'sms'
        );

        if (!in_array($perm, $valid)) {
            throw new Services_Facebook_Exception(
                'Invalid extended permission type supplied: ' . $perm
            );
        }

        $params = array(
            'ext_perm' => $perm
        );

        if ($uid !== null) {
            $params['uid'] = $uid;
        } elseif (!empty($this->sessionKey)) {
            $params['session_key'] = $this->sessionKey;
        } else {
            throw new Services_Facebook_Exception('A UID or session key must be ' .
                'given for hadAppPermission.');
        }

        $result = $this->callMethod('users.hasAppPermission', $params);

        return (intval((string)$result) == 1);
    }

    /**
     * Get photo 
     *
     * Get a photo given an user id. Allow different sizes.
     * 
     * @param string $uid  Id of the user you want to get a photo of
     * @param string $size Size of the photo {@link self::photoSizes}
     *
     * @return mixed Photo data
     */
    public function getPhoto($uid, $size = '')
    {
        $field = 'pic';
        if ($size !== '') {
            if (!in_array($size, $this->photoSizes)) {
                throw new Services_Facebook_Exception('Photo size "' .
                    $size . '" is not supported.');
            }

            $field .= '_' . $size;
        }

        $url = (string) $this->getInfo($uid, array($field))->user->$field;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Services_Facebook::$timeout);
        $photo = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Services_Facebook_Exception(
                curl_error($ch),
                curl_errno($ch)
            );
        }
        curl_close($ch);

        return $photo;
    }
}

?>
