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
 * @author    Jan Matousek <jan.matousek@gmail.com>
 * @copyright 2010 Jan Matousek <jan.matousek@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Services_Facebook
 */

require_once 'Services/Facebook/Common.php';
require_once 'Services/Facebook/Exception.php';

/**
 * Facebook Stream Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Jan Matousek <jan.matousek@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_version@
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Stream extends Services_Facebook_Common
{
    
    /**
     * Stream.publish
     *
     * @return array
     * @link http://wiki.developers.facebook.com/index.php/Stream.publish
     */
    public function publish ($message)
    {
        return $this->callMethod('stream.publish', array(
            'session_key' => $this->sessionKey,
            'message' => $message,
        ));
    }

    /**
     * Stream.remove
     *
     * @return array
     * @link http://wiki.developers.facebook.com/index.php/Stream.remove
     */
    public function remove ($post_id)
    {
        return $this->callMethod('stream.remove', array(
            'session_key' => $this->sessionKey,
            'post_id' => $post_id,
        ));
    }

    /**
     * Stream.get
     *
     * @param integer $limit Limit
     *
     * @return array
     * @link http://wiki.developers.facebook.com/index.php/Stream.get
     */
    public function get ($limit = 28)
    {
        return $this->callMethod('stream.get', array(
            'session_key' => $this->sessionKey,
            'limit' => $limit,
        ));
    }

    /**
     * Stream.addComment
     *
     * @param string $post_id
     * @param string $comment
     *
     * @return string Comment id
     * @link http://wiki.developers.facebook.com/index.php/Stream.addComment
     */
    public function addComment($post_id, $comment)
    {
        return (string)$this->callMethod('stream.addComment', array(
            'session_key' => $this->sessionKey,
            'post_id' => $post_id,
            'comment' => $comment,
        ));
    }

    /**
     * Stream.removeComment
     *
     * @param string $comment_id
     *
     * @return void
     * @link http://wiki.developers.facebook.com/index.php/Stream.removeComment
     */
    public function removeComment($comment_id)
    {
        return $this->callMethod('stream.removeComment', array(
            'session_key' => $this->sessionKey,
            'comment_id' => $comment_id,
        ));
    }

    /**
     * Stream.addLike
     *
     * @param string $post_id
     *
     * @return void
     * @link http://wiki.developers.facebook.com/index.php/Stream.addLike
     */
    public function addLike($post_id)
    {
        return $this->callMethod('stream.addLike', array(
            'session_key' => $this->sessionKey,
            'post_id' => $post_id,
        ));
    }

    /**
     * Stream.removeLike
     *
     * @param string $post_id
     *
     * @return void
     * @link http://wiki.developers.facebook.com/index.php/Stream.removeLike
     */
    public function removeLike($post_id)
    {
        return $this->callMethod('stream.removeLike', array(
            'session_key' => $this->sessionKey,
            'post_id' => $post_id,
        ));
    }

}

?>
