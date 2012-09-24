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
 * Facebook Events Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Events extends Services_Facebook_Common
{
    /**
     * Get events based on multiple query terms
     *
     * @param array $params Various terms to query by
     * 
     * @return object SimpleXmlElement
     */
    public function get(array $params)
    {
        static $rsvp = array(
            'attending', 'unsure', 'declined', 'not_replied'
        );

        $args = array('session_key' => $this->sessionKey);

        if (isset($params['uid'])) {
            $args['uid'] = intval($params['uid']);
        }
        
        if (isset($params['eids'])) {
            if (is_array($params['eids'])) {
                if (count($params['eids'])) {
                    $eids = array();
                    foreach ($params['eids'] as $eid) {
                        $eids[] = intval($eid);
                    }

                    $params['eids'] = implode(',', $eids);
                }
            } else {
                $args['eids'] = intval($params['eids']);
            }
        }

        if (isset($params['start_time'])) {
            $args['start_time'] = intval($params['start_time']);
        }

        if (isset($params['end_time'])) {
            $args['end_time'] = intval($params['end_time']);
        }

        if (isset($params['rsvp_status']) && 
            in_array($params['rsvp_status'], $rsvp)) {
            $args['rsvp_status'] = $params['rsvp_status'];
        }

        return $this->callMethod('events.get', $args);
    }

    /**
     * Get events by event ID
     *
     * @param mixed $eid Array of eid's or single eid
     * 
     * @return object SimpleXmlElement
     */
    public function getEvents($eid)
    {
        if (is_array($eid)) {
            $eid = implode(',', $eid);
        } 

        return $this->callMethod('events.get', array(
            'session_key' => $this->sessionKey,
            'eid' => $eid
        ));
    }

    /**
     * Get events for a given user
     *
     * @param int $uid User ID to fetch events for
     * 
     * @return SimpleXmlElement
     */
    public function getEventsByUser($uid)
    {
        return $this->callMethod('events.get', array(
            'session_key' => $this->sessionKey,
            'uid' => $uid
        ));
    }

    /**
     * Get events by date range
     *
     * Use UNIX timestamps to fetch events within a given date/time range. A
     * value of 0 indicates no upper or lower boundary. 
     *
     * @param int $start UNIX timestamp of start time
     * @param int $end   UNIX timestamp of end time
     * 
     * @return object SimpleXmlElement
     */
    public function getEventsByDate($start, $end)
    {
        return $this->callMethod('events.get', array(
            'session_key' => $this->sessionKey,
            'start' => $start,
            'end' => $end
        ));
    }

    /**
     * Get members of event
     *
     * @param int $eid Event ID to fetch members of
     * 
     * @return object SimpleXmlElement of users attending, etc.
     * @link http://wiki.developers.facebook.com/index.php/Events.getMembers
     */
    public function getMembers($eid)
    {
        return $this->callMethod('events.getMembers', array(
            'session_key' => $this->sessionKey,
            'eid' => $eid
        ));
    }
}

?>
