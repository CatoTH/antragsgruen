<?php
/**
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2012, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/php/decoda
 */

use mjohnson\decoda\Decoda;
use mjohnson\decoda\filters\FilterAbstract;

/**
 * Provides tags for basic font styling.
 *
 * @package    mjohnson.decoda.filters
 */
class AntraegeBBCodeFilter extends FilterAbstract
{

	/**
	 * Supported tags.
	 *
	 * @access protected
	 * @var array
	 */
	protected $_tags = array(
		'B'     => array(
			'htmlTag'      => array('b', 'strong'),
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'I'     => array(
			'htmlTag'      => array('i', 'em'),
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'U'     => array(
			'htmlTag'      => 'u',
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'S'     => array(
			'htmlTag'      => 'del',
			'displayType'  => Decoda::TYPE_INLINE,
			'allowedTypes' => Decoda::TYPE_INLINE
		),
		'OLIST' => array(
			'htmlTag'           => 'ol',
			'displayType'       => Decoda::TYPE_BLOCK,
			'allowedTypes'      => Decoda::TYPE_BOTH,
			'lineBreaks'        => Decoda::NL_REMOVE,
			'childrenWhitelist' => array('LI'),
			/*
			'htmlAttributes'    => array(
				'class' => 'decoda-olist'
			)
			*/
		),
		'LIST'  => array(
			'htmlTag'           => 'ul',
			'displayType'       => Decoda::TYPE_BLOCK,
			'allowedTypes'      => Decoda::TYPE_BOTH,
			'lineBreaks'        => Decoda::NL_REMOVE,
			'childrenWhitelist' => array('LI'),
			/*
			'htmlAttributes'    => array(
				'class' => 'decoda-list'
			)
			*/
		),
		'LI'    => array(
			'htmlTag'      => 'li',
			'displayType'  => Decoda::TYPE_BLOCK,
			'allowedTypes' => Decoda::TYPE_BOTH,
			'parent'       => array('OLIST', 'LIST')
		),
		'QUOTE' => array(
			'htmlTag' => 'blockquote',
			'displayType' => Decoda::TYPE_BLOCK,
			'allowedTypes' => Decoda::TYPE_BOTH,
			'persistContent' => false,
		),
	);

}