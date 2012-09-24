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
 * @copyright 2009 Jeff Hodsdon <jeffhodsdon@gmail.com>  
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: 0.2.14
 * @link      http://pear.php.net/package/Services_Facebook
 */

require_once 'Services/Facebook/Common.php';

/**
 * Facebook Batch Interface
 *
 * <code>
 * $batch = new Services_Facebook_Batch;
 * $batch->sessionKey = 'fooo';
 * $batch->addCall('restriction', 'admin.getRestrictionInfo');
 * $batch->addCall('friends', 'friends.get', array('uid' => 683226814));
 * $batch->addCall('areFriends', 'friends.areFriends', array('uid1' => 617370918, 'uid2' => 683226814));
 * $batch->run();
 *
 * var_dump($batch['friends']);
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
class Services_Facebook_Batch
extends Services_Facebook_Common
implements ArrayAccess
{

    /**
     * Calls 
     * 
     * @var array $calls Calls that will be batched
     */
    protected $calls = array();

    /**
     * Results 
     * 
     * @var array $results Name value pair of results
     */
    protected $results = array();

    /**
     * Add call 
     * 
     * Adds a call to be batched.
     *
     * @param mixed $name     Name of the result
     * @param mixed $endpoint Facebook API endpoint. e.g. friends.get
     * @param array $args     Arguments for the API call
     *
     * @return void
     * @see    Services_Facebook_Batch::run()
     */
    public function addCall($name, $endpoint, $args = array())
    {
        $this->calls[] = array(
            'name'     => $name,
            'endpoint' => $endpoint,
            'args'     => $args
        );
    }

    /**
     * Run 
     * 
     * Run the calls that were added via Services_Facebook_Batch::addCall()
     * in a batch.  If successful the results are stored.
     *
     * @param mixed $serial If true, calls will be ran in order on
     *                      on Facebook's side.
     *
     * @return void
     * @see    Services_Facebook_Batch::addCall()
     * @link   http://wiki.developers.facebook.com/index.php/Batch.run
     */
    public function run($serial = false)
    {
        if (empty($this->calls)) {
            return;
        }

        $methodFeed = array();
        foreach ($this->calls as $call) {
            $this->updateArgs($call['args'], $call['endpoint']);
            $methodFeed[] = http_build_query($call['args']);
        }

        $args = array(
            'method_feed' => json_encode($methodFeed),
            'serial_only' => ($serial) ? 'true' : 'false',
            'session_key' => $this->sessionKey
        );

        $result = $this->callMethod('batch.run', $args);

        $exception = false;
        for ($i = 0; $i < count($this->calls); $i++) {
            $name     = $this->calls[$i]['name'];
            $response = $result->batch_run_response_elt[$i];
            $response = simplexml_load_string((string) $response);
            $this->results[$name] = $response;
        }
    }

    /**
     * Get results 
     * 
     * @return array Results from the batch
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Offset exists 
     * 
     * @param string $offset Offset to check
     *
     * @return bool If element exists
     */
    public function offsetExists($offset)
    {
        return isset($this->results[$offset]);
    }

    /**
     * Offset get 
     * 
     * @param mixed $offset Offset to get
     *
     * @return SimpleXMLElement Result
     */
    public function offsetGet($offset)
    {
        return $this->results[$offset];
    }

    /**
     * Offset set 
     * 
     * We do not allow the batch results to be modified.
     *
     * @param mixed $offset Offset to set
     * @param mixed $value  Value to set
     *
     * @return void
     * @throws Services_Facebook_Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new Services_Facebook_Exception('You can not change the ' .
            'batch results!');
    }

    /**
     * Offset unset 
     * 
     * @param mixed $offset Offset to unset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

}

?>
