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
require_once 'Validate.php';

/**
 * Facebook Notifications Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Notifications extends Services_Facebook_Common
{
    /**
     * App to user notification type
     *
     * @see self::send()
     */
    const TYPE_APP_TO_USER = 'app_to_user';

    /**
     * User to user notification type 
     *
     * @see self::send()
     */
    const TYPE_USER_TO_USER = 'user_to_user';

    /**
     * Get notifications for current user
     *
     * Returns all of the outstanding notifications for the given user, which
     * include messages, pokes, shares, friend requests, group invites, and  
     * event invites.
     *
     * @return      object      Instance of SimpleXmlElement 
     * @link        http://wiki.developers.facebook.com/index.php/Notifications.get
     */
    public function get()
    {
        $args = array(
            'session_key' => $this->sessionKey
        );

        return $this->callMethod('notifications.get', $args);
    }

    /**
     * Send a notification
     *
     * When you send a notification you can send it to an array of Facebook
     * uids. The notification should be valid FBML. Optionally, you can pass
     *
     * Optionally, you can pass a type parameter. 'general' (default)
     * notifications require an active user session, while 'announcement'
     * does not.
     *
     * The result value can either be true or a string. The string is a valid
     * URI that you should redirect the user to for confirmation.
     *
     * @param array  $to           Facebook uids to send note to
     * @param string $notification FBML of notification
     * @param string $type         Type of notification
     * 
     * @return mixed Confirmation URI or true 
     * @see self::TYPE_GENERAL, self::TYPE_ANNOUNCEMENT
     * @link http://wiki.developers.facebook.com/index.php/Notifications.send
     */
    public function send(array $to, $notification, $type = self::TYPE_USER_TO_USER)
    {
        $args = array(
            'to_ids' => implode(',', $to),
            'notification' => $notification
        ); 

        if ($type == self::TYPE_USER_TO_USER) {
            $args['session_key'] = $this->sessionKey;
            $args['type']        = $type;
        } elseif ($type == self::TYPE_APP_TO_USER) {
            $args['type'] = $type;
        } elseif ($type == 'announcement' || $type == 'general') {
            // Backwards compatiblity
            if ($type == 'general') {
                $args['session_key'] = $this->sessionKey;
            }
            $args['type'] = $type;
        } else {
            // Backwards compatiblity
            $args['email'] = $type;
        }

        $result = $this->callMethod('notifications.send', $args);
        $check  = (string)$result;
        if (strlen($check) && Validate::uri($check)) {
            return $check;
        }

        return true;
    }

    /**
     * Send an email out to application users
     *
     * @param array  $recipients An array of Facebook uids to send too
     * @param string $subject    Subject of the email
     * @param mixed  $text       Text or FBML and text for the body of the email
     * 
     * @return array An array of success uids the email went out too
     * @author Jeff Hodsdon <jeffhodsdon@gmail.com>
     * @link http://wiki.developers.facebook.com/index.php/Notifications.sendEmail
     */     
    public function sendEmail(array $recipients, $subject, $text = null)
    {
        $args = array(
            'recipients' => implode(',', $recipients),
            'subject' => $subject,
            );
            
        if (preg_match('/<fbml/i', $text)) {
            $args['fbml'] = $text;
        } else {
            $args['text'] = $text;
        }
        
        $result = $this->callMethod('notifications.sendEmail', $args);
        return explode(',', (string)$result);
    }
}

?>
