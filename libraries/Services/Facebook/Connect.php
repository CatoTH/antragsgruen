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
 * @author    Jeff Hodsdon <jeff@digg.com>
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2007-2008 Jeff Hodsdon <jeff@digg.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: 0.2.14
 * @link      http://pear.php.net/package/Services_Facebook
 */

require_once 'Services/Facebook/Common.php';
require_once 'Services/Facebook/Exception.php';
require_once 'Validate.php';

/**
 * Facebook Application Interface
 *
 * <code>
 * <?php
 * require_once 'Services/Facebook.php';
 * $api = new Services_Facebook();
 * $app = $api->connect->('');
 * ?>
 * </code>
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Jeff Hodsdon <jeff@digg.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Connect extends Services_Facebook_Common
{

    /**
     * Construct
     *
     * Various tasks that should be ran before non-static methods
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        if (!function_exists('json_encode')) {
            throw new Services_Facebook_Exception('PHP Function ' .
                'json_encode() is required for Services_Facebook_Connect ' .
                ' methods.');
        }
    }

    /**
     * Unconnected Friends Count 
     * 
     * Get the amount of users friends that have not connected their account
     * to your site.  NOTE: These are users that you have sent connect.registerUser
     * information.
     *
     * @access public
     * @return int Amount of users
     */
    public function getUnconnectedFriendsCount()
    {
        $result = $this->callMethod('connect.getUnconnectedFriendsCount', array(
            'session_key' => $this->sessionKey
        ));

        return (int) $result;
    }

    /**
     * Register Users 
     * 
     * The accounts array may hold up to 1,000 accounts. Each account should hold
     * these array keys: (account_id and account_url are optional)
     *
     * <code>
     * 
     * // Hash the emails
     * $hash1 = Services_Facebook_Connect::hashEmail('joe@example.com');
     * $hash2 = Services_Facebook_Connect::hashEmail('jeff@example.com');
     * 
     * $accounts = array();
     * 
     * $accounts[] = array(
     *     'email_hash'  => $hash1,
     *     'account_id'  => 12345678,
     *     'account_url' => 'http://example.com/users?id=12345678'
     * )
     * 
     * $accounts[] = array(
     *     'email_hash'  => $hash2,
     * )
     * 
     * $connect = Services_Facebook::factory('Connect');
     * $result  = $connect->registerUsers($accounts);
     * </code>
     *
     * @param array $accounts Information about accounts
     *
     * @access public
     * @throws Services_Facebook_Exception If emash_hash is missing or
     *         another field was passed in that is not supported. 
     * @return object SimpleXML object from callMethod()
     */
    public function registerUsers(array $accounts)
    {
        $fields = array(
            'email_hash',
            'account_id',
            'account_url'
        );

        foreach ($accounts as $account) {
            if (empty($account['email_hash'])) {
                throw new Services_Facebook_Exception('email_hash is ' .
                    'required in each account map passed to ' .
                    'Services_Facebook_Connect::registerUsers()');
            }

            $keys = array_keys($account);
            foreach ($keys as $key) {
                if (!in_array($key, $fields)) {
                    throw new Services_Facebook_Exception('Field ' . $key .
                        ' is not supported.');
                }
            }
        }

        $result = $this->callMethod('connect.registerUsers', array(
            'accounts' => json_encode($accounts)
        ));

        $hashes = array();
        foreach ($result->connect_registerUsers_response_elt as $hash) {
            $hashes[] = (string) $hash;
        }

        return $hashes;
    }

    /**
     * unregisterUsers 
     * 
     * This method allows a site to unregister a connected account. You should 
     * call this method if the user deletes his account on your site.
     * 
     *
     * <code>
     * $hashes = array();
     * $hashes[] = Services_Facebook_Connect::hashEmail('joe@example.com');
     * $hashes[] = Services_Facebook_Connect::hashEmail('jeff@example.com');
     * 
     * $connect = new Services_Facebook::factory('Connect');
     * $result  = $connect->unregisterUsers($hashes);
     * </code>
     *
     * @param array $emailHashes An array of email_hashes to unregister
     *
     * @access public
     * @throws Services_Facebook_Exception if json_decode() is not available
     * @return object SimpleXML object from callMethod()
     */
    public function unregisterUsers(array $emailHashes)
    {
        $result = $this->callMethod('connect.unregisterUsers', array(
            'email_hashes' => json_encode($emailHashes)
        ));

        return (intval((string) $result) == 1);
    }

    /**
     * hashEmail 
     * 
     * @param string $email Email to hash
     *
     * @static
     * @access public
     * @return string Hashed email address
     * @throws Services_Facebook_Exception
     * @see    http://www.php.net/crc32
     * @see    http://www.php.net/md5
     */
    static public function hashEmail($email)
    {
        if (!Validate::email($email)) {
            throw new Services_Facebook_Exception('Invalid email address passed to'
                . ' Services_Facebook_Connect::hashEmail()');
        }

        $email = strtolower(trim($email));
        $crc32 = sprintf("%u", crc32($email));
        $md5   = md5($email);

        return $crc32 . '_' . $md5;
    }

    /**
     * hashEmails 
     * 
     * @param array $emails Emails to hash
     *
     * @static
     * @access public
     * @return array  Hashed emails
     */
    static public function hashEmails(array $emails)
    {
        foreach ($emails as &$email) {
            $email = self::hashEmail($email);
        }

        return $emails;
    }
}

?>
