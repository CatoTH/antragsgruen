<?php
/**
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2012, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

use \Decoda\Decoda;
use \Decoda\Filter\AbstractFilter;

/**
 * Provides tags for basic font styling.
 *
 * @package    mjohnson.decoda.filters
 */
class AntraegeBBCodeFilter extends AbstractFilter
{

	/**
	 * Supported tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array(
		'b'     => array(
			'htmlTag'      => array('b', 'strong'),
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'i'     => array(
			'htmlTag'      => array('i', 'em'),
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'u'     => array(
			'htmlTag'      => 'u',
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		's'     => array(
			'htmlTag'      => 'del',
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'quote' => array(
			'htmlTag'        => 'blockquote',
			'displayType'    => Decoda::TYPE_BLOCK,
			'allowedTypes'   => Decoda::TYPE_BOTH,
			'persistContent' => false,
		),


		'olist' => array(
			'htmlTag'           => 'ol',
			'displayType'       => Decoda::TYPE_BLOCK,
			'allowedTypes'      => Decoda::TYPE_BOTH,
			'lineBreaks'        => Decoda::NL_REMOVE,
			'childrenWhitelist' => array('li', '*'),
			'onlyTags'          => true,
		),
		'list'  => array(
			'htmlTag'           => 'ul',
			'displayType'       => Decoda::TYPE_BLOCK,
			'allowedTypes'      => Decoda::TYPE_BOTH,
			'lineBreaks'        => Decoda::NL_REMOVE,
			'childrenWhitelist' => array('li', '*'),
			'onlyTags'          => true,
		),
		'li'    => array(
			'htmlTag'      => 'li',
			'displayType'  => Decoda::TYPE_BLOCK,
			'allowedTypes' => Decoda::TYPE_BOTH,
			'parent'       => array('olist', 'list')
		),
		'*'     => array(
			'htmlTag'           => 'li',
			'displayType'       => Decoda::TYPE_BLOCK,
			'allowedTypes'      => Decoda::TYPE_BOTH,
			'childrenBlacklist' => array('olist', 'list', 'li'),
			'parent'            => array('olist', 'list')
		)

	);

}