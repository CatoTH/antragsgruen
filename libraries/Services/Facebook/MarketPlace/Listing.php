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

require_once 'Services/Facebook/Exception.php';

/**
 * Facebook Marketplace listing
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_MarketPlace_Listing
{
    /**
     * Listing ID (listing_id)
     *
     * @var         integer     $id
     */
    public $id = 0;

    /**
     * Show listing in profile?
     *
     * @var         boolean     $showInProfile
     */
    public $showInProfile = true;

    /**
     * Listing parameters
     *
     * @param array $data
     * 
     * @link http://wiki.developers.facebook.com/index.php/Marketplace_Listing_Attributes
     */
    public $data = array();

    /**
     * Set listing data
     *
     * @param string $var (e.g. price, condition, etc.)
     * @param string $val Value of attribute
     * 
     * @return void
     */
    public function __set($var, $val)
    {
        $this->data[$var] = $val;
    }

    /**
     * Get listing data
     *
     * @param string $var (e.g. price, condition, etc.)
     * 
     * @return mixed Null if value is not set
     */
    public function __get($var)
    {
        if (isset($this->data[$var])) {
            return $this->data[$var];
        }

        return null;
    }

    /**
     * Validate listing before posting it
     *
     * @return      void
     * @throws      Services_Facebook_Exception
     */
    public function validate()
    {
        static $required = array(
            'category', 'subcategory', 'title', 'description'
        );

        foreach ($required as $field) {
            if (!isset($this->data[$field]) || !strlen($this->data[$field])) {
                throw new Services_Facebook_Exception(
                    'Marketplace listing requires ' . $field . ' be set'
                );
            }
        }

        foreach ($this->data as $field => $val) {
            $func = 'validate' . ucfirst($field);
            if (method_exists($this, $func)) {
                $this->$func($val);
            }
        }
    }

    /**
     * Validate condition value
     *
     * @param string $val The condition to validate against
     * 
     * @return void
     * @throws Services_Facebook_Exception
     */
    public function validateCondition($val)
    {
        if (!in_array($val, array('ANY', 'NEW', 'USED'))) {
            throw new Services_Facebook_Exception(
                'Condition must be ANY, NEW or USED'
            );
        }
    }
}

?>
