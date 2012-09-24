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

/**
 * Services_Facebook_Share
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Share
{
    /**
     * Parse the share meta data from body
     *
     * @param string $body HTML code to parse info from
     * 
     * @return array Meta information on videos, etc.
     */
    public function parse($body)
    {
        $metas = $links = array();
        preg_match_all('/<meta ([^>]+)>/i', $body, $metas);
        preg_match_all('/<link ([^>]+)>/i', $body, $links);

        $ret = array();
        foreach ($links[0] as $l) {
            $m = array();
            if (preg_match('/rel="(image_src|audio_src|video_src)"/i', $l, $m)) {
                $type = strtolower(preg_replace('/_src$/i', '', $m[1]));
                if (!isset($ret[$type])) {
                    $ret[$type] = array();
                }

                if (preg_match('/href="([^"]+)"/i', $l, $m)) {
                    $ret[$type]['src'] = $m[1];
                }
            }
        }

        foreach ($metas[0] as $meta) {
            $m = array();
            if (preg_match('/name="(title|description)"/i', $meta, $m)) {
                $type = strtolower($m[1]);
                if (preg_match('/content="([^"]+)"/i', $meta, $m)) {
                    $ret[$type] = $m[1];
                }
            } elseif (preg_match('/name="medium"/i', $meta, $m)) {
                if (preg_match('/content="(audio|image|video|news|blog|mult)"/i', $meta, $m)) {
                    $ret['medium'] = $m[1];
                } 
            } elseif (preg_match('/name="(video|image)_(height|width)"/i', $meta, $m)) {
                $type = strtolower($m[1]);
                $val  = strtolower($m[2]);
                if (preg_match('/content="([0-9]+)"/i', $meta, $m)) {
                    $ret[$type][$val] = $m[1];
                }
            } elseif (preg_match('/name="(video|audio|image)_type"/i', $meta, $m)) {
                $type = strtolower($m[1]);
                if (preg_match('/content="([^"]+)"/i', $meta, $m)) {
                    $ret[$type]['type'] = $m[1];
                }
            } elseif (preg_match('/name="audio_(title|artist|album)"/i', $meta, $m)) {
                $val = strtolower($m[1]);
                if (preg_match('/content="([^"]+)"/i', $meta, $m)) {
                    $ret['audio'][$val] = $m[1];
                }
            }
        }

        return $ret;
    }
}

?>
