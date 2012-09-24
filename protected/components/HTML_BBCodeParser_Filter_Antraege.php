<?php

require_once('HTML/BBCodeParser/Filter.php');


class HTML_BBCodeParser_Filter_Antraege extends HTML_BBCodeParser_Filter
{


	var $_definedTags = array('list'  => array('htmlopen'  => 'ul',
											   'htmlclose' => 'ul',
											   'allowed'   => 'all',
											   'child'     => 'none^li',
											   'attributes'=> array('list'  => 'style=%2$slist-style-type:%1$s;%2$s')
	),
							  'ulist' => array('htmlopen'  => 'ol',
											   'htmlclose' => 'ol',
											   'allowed'   => 'all',
											   'child'     => 'none^li',
											   'attributes'=> array('list'  => 'style=%2$slist-style-type:%1$s;%2$s')
							  ),
							  'li'    => array('htmlopen'  => 'li',
											   'htmlclose' => 'li',
											   'allowed'   => 'all',
											   'parent'    => 'none^ulist,list',
											   'attributes'=> array()
							  ),
							  'b'     => array('htmlopen' => 'strong', 'htmlclose' => 'strong', 'allowed' => 'all', 'attributes' => array()),
							  'i'     => array('htmlopen' => 'em', 'htmlclose' => 'em', 'allowed' => 'all', 'attributes' => array()),
							  'u'     => array('htmlopen' => 'u', 'htmlclose' => 'u', 'allowed' => 'all', 'attributes' => array()),
							  's'     => array('htmlopen' => 's', 'htmlclose' => 's', 'allowed' => 'all', 'attributes' => array()),
							  'a'     => array('htmlopen' => 'a', 'htmlclose' => 'a', 'allowed' => 'none^img', 'attributes' => array('url' => 'target=\'_blank\' rel=\'nofollow\' href=%2$s%1$s%2$s')),
							  'quote' => array('htmlopen' => 'blockquote', 'htmlclose' => 'blockquote', 'allowed' => 'all', 'attributes' => array('quote' => '')),

	);


	function _preparse()
	{
		$options = PEAR::getStaticProperty('HTML_BBCodeParser', '_options');
		$o       = $options['open'];
		$c       = $options['close'];
		$oe      = $options['open_esc'];
		$ce      = $options['close_esc'];

		$pattern = array("!" . $oe . "\*" . $ce . "!",
			"!" . $oe . "(u?)list=(?-i:A)(\s*[^" . $ce . "]*)" . $ce . "!i",
			"!" . $oe . "(u?)list=(?-i:a)(\s*[^" . $ce . "]*)" . $ce . "!i",
			"!" . $oe . "(u?)list=(?-i:I)(\s*[^" . $ce . "]*)" . $ce . "!i",
			"!" . $oe . "(u?)list=(?-i:i)(\s*[^" . $ce . "]*)" . $ce . "!i",
			"!" . $oe . "(u?)list=(?-i:1)(\s*[^" . $ce . "]*)" . $ce . "!i",
			"!" . $oe . "(u?)list([^" . $ce . "]*)" . $ce . "!i");

		$replace = array($o . "li" . $c,
			$o . "\$1list=upper-alpha\$2" . $c,
			$o . "\$1list=lower-alpha\$2" . $c,
			$o . "\$1list=upper-roman\$2" . $c,
			$o . "\$1list=lower-roman\$2" . $c,
			$o . "\$1list=decimal\$2" . $c,
			$o . "\$1list\$2" . $c);

		$this->_preparsed = preg_replace($pattern, $replace, $this->_text);
	}


}

