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
 * Facebook Feed Interface
 *
 * @category Services
 * @package  Services_Facebook
 * @author   Joe Stump <joe@joestump.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 0.2.14
 * @link     http://wiki.developers.facebook.com
 */
class Services_Facebook_Feed extends Services_Facebook_Common
{
    /**
     * Publish a story to a user's feed
     *
     * The $images array should be a numerically indexed array of arrays, where
     * each image has two keys: src and href. The src is the full URI of the
     * image and the href is the link of that image.
     *
     * <code>
     * <?php
     * $images = array(
     *     array('src'  => 'http://example.com/images1.jpg',
     *           'href' => 'http://example.com/images.php?image=1'),
     *     array('src'  => 'http://example.com/images2.jpg',
     *           'href' => 'http://example.com/images.php?image=2'),
     *     array('src'  => 'http://example.com/images3.jpg',
     *           'href' => 'http://example.com/images.php?image=3')
     * );
     * ?>
     * </code>
     *
     * @param string $title  FBML to post as story title
     * @param string $body   FBML to post as story body
     * @param array  $images Images to post to story entry
     *
     * @return boolean
     *
     * @link http://wiki.developers.facebook.com/index.php/Feed.publishStoryToUser
     * @link http://wiki.developers.facebook.com/index.php/PublishActionOfUser_vs._PublishStoryToUser
     */
    public function publishStoryToUser($title,
                                       $body = '',
                                       array $images = array())
    {
        $args = array(
            'title' => $title,
            'session_key' => $this->sessionKey
        );

        if (strlen($body)) {
            $args['body'] = $body;
        }

        if (count($images)) {
            // Facebook only allows four images so don't send more than that.
            $cnt = count($images);
            if ($cnt > 4) {
                $cnt = 4;
            }

            for ($i = 0 ; $i < $cnt ; $i++) {
                $n = ($i + 1);
                $args['image_' . $n] = $images[$i]['src'];
                if (isset($images[$i]['href'])) {
                    $args['image_' . $n . '_link'] = $images[$i]['href'];
                } else {
                    $args['image_' . $n . '_link'] = $images[$i]['src'];
                }
            }
        } 

        $result = $this->callMethod('feed.publishStoryToUser', $args);
        $check  = intval((string)$result->feed_publishStoryToUser_response_elt);
        return ($check == 1);
    }

    /**
     * Publish an action to a user's feed
     *
     * An action differs from a story in that a user's action is sent to all
     * of that user's friends as well.
     *
     * The $images array should be a numerically indexed array of arrays, where
     * each image has two keys: src and href. The src is the full URI of the
     * image and the href is the link of that image.
     *
     * <code>
     * <?php
     * $images = array(
     *     array('src'  => 'http://example.com/images1.jpg',
     *           'href' => 'http://example.com/images.php?image=1'),
     *     array('src'  => 'http://example.com/images2.jpg',
     *           'href' => 'http://example.com/images.php?image=2'),
     *     array('src'  => 'http://example.com/images3.jpg',
     *           'href' => 'http://example.com/images.php?image=3')
     * );
     * ?>
     * </code>
     *
     * @param string $title  FBML to post as story title
     * @param string $body   FBML to post as story body
     * @param array  $images Images to post to story entry
     * 
     * @return boolean
     *
     * @link http://wiki.developers.facebook.com/index.php/Feed.publishActionOfUser
     * @link http://wiki.developers.facebook.com/index.php/PublishActionOfUser_vs._PublishStoryToUser
     */
    public function publishActionOfUser($title, 
                                        $body = '', 
                                        array $images = array())
    {
        $args = array(
            'title' => $title,
            'session_key' => $this->sessionKey
        );

        if (strlen($body)) {
            $args['body'] = $body;
        }

        if (count($images)) {
            // Facebook only allows four images so don't send more than that.
            $cnt = count($images);
            if ($cnt > 4) {
                $cnt = 4;
            }

            for ($i = 0 ; $i < $cnt ; $i++) {
                $n = ($i + 1);
                $args['image_' . $n] = $images[$i]['src'];
                if (isset($images[$i]['href'])) {
                    $args['image_' . $n . '_link'] = $images[$i]['href'];
                } else {
                    $args['image_' . $n . '_link'] = $images[$i]['src'];
                }
            }
        } 

        $result = $this->callMethod('feed.publishActionOfUser', $args);
        $check  = intval((string)$result->feed_publishActionOfUser_response_elt);
        return ($check == 1);
    }
    
    /**
     * Publish a templatized action to a user's feed
     *
     * An action differs from a story in that a user's action is sent to all
     * of that user's friends as well.
     * 
     * An templatized story publishes News Feed stories to the friends of that user.
     * These stories or more likely to appear to the friends of that user depending
     * upon a variety of factors, such as the closeness of the relationship between
     * the users, the interaction data facebook has about that particular story type,
     * and the quality of the content in the story/on the linked page.
     * http://wiki.developers.facebook.com/index.php/FeedRankingFAQ
     *
     * The $images array should be a numerically indexed array of arrays, where
     * each image has two keys: src and href. The src is the full URI of the
     * image and the href is the link of that image.
     *
     * <code>
     * <?php
     * $images = array(
     *     array('src'  => 'http://example.com/images1.jpg',
     *           'href' => 'http://example.com/images.php?image=1'),
     *     array('src'  => 'http://example.com/images2.jpg',
     *           'href' => 'http://example.com/images.php?image=2'),
     *     array('src'  => 'http://example.com/images3.jpg',
     *           'href' => 'http://example.com/images.php?image=3')
     * );
     * ?>
     * </code>
     *
     * @param string $titleTemplate FBML to post as the title, must contain {actor}
     * @param array  $feedData      Array containing optional Feed template, data, and/or actor id
     * @param array  $images        Images to post to story entry
     *
     * @return boolean
     *
     * @author Jeff Hodsdon <jeffhodsdon@gmail.com>
     * @link   http://wiki.developers.facebook.com/index.php/Feed.publishTemplatizedAction
     */
    public function publishTemplatizedAction($titleTemplate,
                                             array $feedData = array(),
                                             array $images = array())
    {
        $args = array(
            'title_template' => $titleTemplate,
            'session_key' => $this->sessionKey
        );

        static $options = array('title_data', 'body_template', 'body_data',
                                'body_general', 'page_actor_id');
    
        foreach ($options as $opt) {
            if (isset($feedData[$opt]) && strlen($feedData[$opt])) {
                $args[$opt] = $feedData[$opt];
            }
        }

        if (count($images)) {
            // Facebook only allows four images so don't send more than that.
            $cnt = count($images);
            if ($cnt > 4) {
                $cnt = 4;
            }

            for ($i = 0 ; $i < $cnt ; $i++) {
                $n = ($i + 1);
                $args['image_' . $n] = $images[$i]['src'];
                if (isset($images[$i]['href'])) {
                    $args['image_' . $n . '_link'] = $images[$i]['href'];
                } else {
                    $args['image_' . $n . '_link'] = $images[$i]['src'];
                }
            }
        }

        $result = $this->callMethod('feed.publishTemplatizedAction', $args);
        return (intval((string)$result) == 1);
    }

    /**
     * Builds a template bundle around the specified templates, registers them
     * on Facebook, and responds with a template bundle ID that can be used
     * to identify your template bundle to other Feed-related API calls.
     * 
     * A template bundle consists of:
     *  - an array of one line story templates
     *  - an array of short story templates
     *  - a single full story template
     * 
     * Each array consists of one or more templates, and each template consists
     * of one or more tokens (for the story actor, friends, items, and so
     * forth), some static text, and some FBML. Tokens must be wrapped in curly
     * braces and asterisks, as in {*actor*}. The {*actor*} token must appear
     * at the beginning of all one line templates and at the beginning of short
     * and full template story titles.
     * 
     * The order of templates in an array is very important. In general, the
     * most flexible template should be first in the array. The most flexible
     * template has the most tokens in it. The first template will always be used
     * for feed stories. The last one-line template in the array must be the
     * least flexible of all the template in the bundle. Thus, it should include
     * only tokens that are a strict subset of all other tokens.
     * 
     * When considering these templates, the first template makes for the best
     * story, but the last template has the highest aggregation potential. When
     * you publish a story using feed.publishUserAction, you're posting the
     * first version of the story to a user's Mini-Feed, and you're posting one
     * of three different stories to that users friends' News Feeds.
     * 
     * Short story each consist of two parts, a template title and a template
     * body. Short stories should be passed as an array of short stories,
     * with each element being an array containing the keys 'template_title'
     * and 'template_body'
     * 
     * Full story templates should be passed as an array containing keys
     * 'template_title' and 'template_body'
     *
     * Action links @see http://wiki.developers.facebook.com/index.php/Action_Links
     * 
     * @access  public
     * @param   array   $oneLineStoryTpls   array of one-line story templates
     * @param   array   $shortStoryTpls     optional array of short story templates
     * @param   array   $fullStoryTemplate  optional full story template
     * @param   array   $actionLinks        optional array of actoin link records
     * @return  string  template bundle ID of newly registered bundle
     * @link    http://wiki.developers.facebook.com/index.php/Feed.registerTemplateBundle
     * @author  Matthew Fonda <matthewfonda@gmail.com>
     */
    public function registerTemplateBundle(array $oneLineStoryTpls,
                                           array $shortStoryTpls = array(),
                                           array $fullStoryTpl = array(),
                                           array $actionLinks = array())
    {
        $args = array();
        if (count($oneLineStoryTpls)) {
            $args['one_line_story_templates'] = json_encode($oneLineStoryTpls);
        } else {
            throw new Services_Facebook_Exception(
                    'Feed.registerTemplateBundle requires at least one one-line story template'
                );
        }
        
        if (count($shortStoryTpls)) {
            $args['short_story_templates'] = json_encode($shortStoryTpls);
        }

        if (isset($fullStoryTpl['template_title'], $fullStoryTpl['template_body'])) {
            $args['full_story_template'] = json_encode($fullStoryTpl);
        }

        if (count($actionLinks)) {
            $args['action_links'] = json_encode($actionLinks);
        }
        
        $result = $this->callMethod('feed.registerTemplateBundle', $args);
        return (float) (string)$result;
    }

    /**
     * Retrieves the full list of all template bundles registered by the
     * requesting application. This does not include any template bundles
     * previously deactivated via calls to feed.deactivateTemplateBundle
     * 
     * @access  public
     * @return  SimpleXMLElement    SimpleXMLElement containing templates
     * @link    http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundles
     * @author  Matthew Fonda <matthewfonda@gmail.com>
     */
    public function getRegisteredTemplateBundles()
    {
        return $this->callMethod('feed.getRegisteredTemplateBundles');
    }

    /**
     * Returns information about a specified template bundle previously
     * registered by the requesting application. The result is returned
     * as a SimpleXMLElement.
     * 
     * @access  public
     * @param   int                 $id     ID of template bundle
     * @return  SimpleXMLElement    SimpleXMLElement representing the bundle
     * @link    http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundleByID
     * @author  Matthew Fonda <matthewfonda@gmail.com>
     */
    public function getRegisteredTemplateBundleByID($id)
    {
        $args = array('template_bundle_id' => $id);
        return $this->callMethod('feed.getRegisteredTemplateBundleByID', $args);
    }

    /**
     * Deactivates a previously registered template bundle. Once a template
     * bundle has been deactivated, it can no longer be used to publish stories
     * via feed.publishUserAction. Stories published agaisnt the template
     * bundle prior to its deactivation are still valid and will show up in the
     * Mini-Feed and News Feed. The response is true if and only if the template
     * bundle, identified by $id, is an active template bundle owned by the
     * requesting application, and is false otherwise.
     * 
     * @access  public
     * @param   int     $id     ID of template bundle to deactivate
     * @return  boolean
     * @link    http://wiki.developers.facebook.com/index.php/Feed.deactivateTemplateBundleByID
     * @author  Matthew Fonda <matthewfonda@gmail.com>
     */
    public function deactivateTemplateBundleByID($id)
    {
        $args = array('template_bundle_id' => $id);
        $result = $this->callMethod('feed.deactivateTemplateBundleByID', $args);
        return (intval($result) == 1);
    }

    /**
     * Publishes a story on behald of the user owning the session, using the
     * specified template bundle. This method requires an active session key
     * in order to be called. This method returns true if all succeeds, and
     * false of the user never authorized the application to publish to his or
     * her Mini-Feed.
     * 
     * This method should be passed a template bundle ID to use, and an array
     * of template data whose keys are the tokens to replace, and values are
     * the desired replacement. 'actor' and 'target' are special tokens and
     * should not be included in this array. If one or more of the templates
     * include tokens other than 'actor' and 'targets', then this array is
     * required. This array can also include exactly one of the following keys:
     * 'images', 'flash', 'mp3', or 'video'.
     * 
     * If 'images' is passed, it should map to an array of up to four images,
     * and each array should contain a key 'src', and optionally 'href'
     * 
     * If 'flash' is passed, it should map to an array containing two required
     * keys: 'swfsrc', which is the URL of the flash object to be rendered, and
     * 'imgsrc', which is the URL of an image to be displayed until the users
     * clicks the flash object. Optionally, the 'flash' array can contain 'width'
     * and 'height'. The height must be an integer between 30 and 100 (inclusive),
     * and the width must be either 100, 110, or 130.
     * 
     * If 'mp3' is passed, it must contain a single required field, 'src', and
     * can optionally contain 'title', 'artist', and 'album'
     * 
     * If 'video' is passed, it must contain two required fields: 'video_src'
     * and 'preview_img'. The video array can also contain the following
     * optional fields: 'video_title', 'video_link', and 'video_type'.
     * 
     * If the template in questions contains a 'target' token, the userIDs
     * of the target should be passed as an array, $targetIDs.
     * 
     * @access  public
     * @param   int     $templateBundleID   ID of template bundle to use
     * @param   array   $templateData       array of template data
     * @param   array   $targetIDs          array of target IDs
     * @param   string  $bodyGeneral        additional markup that extends the
     *                                      body of a short story
     * @return  boolean
     * @link    http://wiki.developers.facebook.com/index.php/Feed.publishUserAction
     * @author  Matthew Fonda <matthewfonda@gmail.com>
     */
    public function publishUserAction($templateBundleID, 
                                      array $templateData = array(),
                                      array $targetIDs = array(),
                                      $bodyGeneral = ''
                                     )
    {
        $args = array('session_key'        => $this->sessionKey,
                      'template_bundle_id' => $templateBundleID
                     );
        if (count($templateData)) {
            $args['template_data'] = json_encode($templateData);
        }
        
        if (count($targetIDs)) {
            $args['target_ids'] = implode(',', $targetIDs);
        }
         
        if (strlen($bodyGeneral)) {
            $args['body_general'] = $bodyGeneral;
        }
        
        $result = $this->callMethod('feed.publishUserAction', $args);
        return (intval($result->feed_publishUserAction_response_elt) == 1);
    }

}

?>
