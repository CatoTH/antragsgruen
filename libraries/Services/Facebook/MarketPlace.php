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
require_once 'Services/Facebook/MarketPlace/Listing.php';

/**
 * Facebook Marketplace Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_MarketPlace extends Services_Facebook_Common
{
    /**
     * Create (or Update) a marketplace listing
     *
     * @param object $l Instance of listing object
     * 
     * @return object Instance of listing object (with ID)
     * @throws Services_Facebook_Exception
     */
    public function createListing(Services_Facebook_MarketPlace_Listing $l)
    {
        $l->validate();
        $result = $this->callMethod('marketplace.createListing', array(
            'session_key' => $this->sessionKey,
            'listing_id' => $l->id,
            'show_on_profile' => (($l->showInProfile) ? '1' : '0'),
            'listing_attrs' => json_encode($l->data)
        ));

        $id    = (float) (string) $result;
        $l->id = $id;
        return $l;
    }

    /**
     * Get marketplace categories
     *
     * @return      object
     * @throws      Services_Facebook_Exception
     */
    public function getCategories()
    {
        $result = $this->callMethod('marketplace.getCategories', array(
            'session_key' => $this->sessionKey
        ));

        $categories = array();
        foreach ($result as $category) {
            $categories[] = (string) $category;
        }

        return $categories;
    }

    /**
     * Get marketplace subcategories
     *
     * @param string $category Category to fetch subcategories for
     * 
     * @return object
     * @throws Services_Facebook_Exception
     */
    public function getSubCategories($category)
    {
        return $this->callMethod('marketplace.getSubCategories', array(
            'session_key' => $this->sessionKey,
            'category' => $category
        ));
    }

    /**
     * Get marketplace listings, filter by listing ids and user ids
     *
     * @param mixed $listingIds Array or string of List id(s)
     * @param mixed $uids       Array or string of User id(s)
     * 
     * @return mixed SimpleXML object of the listings 
     * @link http://wiki.developers.facebook.com/index.php/Marketplace.getListings
     */
    public function getListings($listingIds = null, $uids = null)
    {
        if ((!$listingIds) && (!$uids)) {
            throw new Services_Facebook_Exception(
                'Must specifiy at least 1 user or listing id'
            );
        }
        
        if (is_array($listingIds)) {
            $listingIds = implode(',', $listingIds);
        }

        if (is_array($uids)) {
            $uids = implode(',', $listingIds);
        }

        return $this->callMethod('marketplace.getListings', array(
            'session_key' => $this->sessionKey,
            'listing_ids' => $listingIds,
            'uids' => $uids
        ));
    }

    /**
     * Remove marketplace listing
     *
     * Remove a listing by id and setting the status to either 'SUCCESS', 
     * 'DEFAULT', 'NOT_SUCCESS'
     *
     * @param string $listingId Listing id
     * @param string $status    Status
     * 
     * @return bool Success or Failure 
     * @author Jeff Hodsdon <jeffhodsdon@gmail.com>
     * @link http://wiki.developers.facebook.com/index.php/Marketplace.removeListing
     */
    public function removeListing($listingId, $status = 'DEFAULT')
    {
        $result = $this->callMethod('marketplace.removeListing', array(
            'session_key' => $this->sessionKey,
            'listing_id' => $listingId,
            'status' => $status
        ));
        return ((string)$result == 1);
    }

    /**
     * Search marketplace listings
     *
     * @param string $query       The query term to search for
     * @param string $category    Category to search within
     * @param string $subCategory Subcategory to search within
     * 
     * @return object
     * @throws Services_Facebook_Exception
     */
    public function search($query, $category = '', $subCategory = '')
    {
        if (strlen($subCategory) && !strlen($category)) {
            throw new Services_Facebook_Exception(
                'You must specify a category when searching by subCategory'
            );
        }

        $args = array('session_key' => $this->sessionKey);
        if (strlen($category)) {
            $args['category'] = $category;
        }

        if (strlen($subCategory)) {
            $args['subcategory'] = $subCategory;
        }

        return $this->callMethod('marketplace.search', $args);
    }
}

?>
