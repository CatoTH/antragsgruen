<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Tests\Support\Helper;

use Codeception\Actor;
use Tests\Support\AcceptanceTester;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * BasePage is the base class for page classes that represent Web pages to be tested.
 *
 * @property string $url The URL to this page. This property is read-only.
 *
 * @author Mark Jebri <mark.github@yandex.ru>
 * @since 2.0
 */
abstract class BasePage extends Component
{
    /**
     * @var string|array the route (controller ID and action ID, e.g. `site/about`) to this page.
     * Use array to represent a route with GET parameters. The first element of the array represents
     * the route and the rest of the name-value pairs are treated as GET parameters, e.g. `array('site/page', 'name' => 'about')`.
     */
    public string|array $route;

    /**
     * @var \Codeception\Actor the testing guy object
     */
    protected Actor $actor;


    /**
     * Constructor.
     *
     * @param \Codeception\Actor $I the testing guy object
     */
    public function __construct($I)
    {
        $this->actor = $I;
    }

    /**
     * Returns the URL to this page.
     * The URL will be returned by calling the URL manager of the application
     * with [[route]] and the provided parameters.
     * @param array $params the GET parameters for creating the URL
     * @return string|null the URL to this page
     * @throws \yii\base\InvalidConfigException if [[route]] is not set or invalid
     */
    public function getUrl(array $params = []): ?string
    {
        if (is_string($this->route)) {
            $params[0] = $this->route;
            return Yii::$app->getUrlManager()->createUrl($params);
        }

        if (is_array($this->route) && isset($this->route[0])) {
            return Yii::$app->getUrlManager()->createUrl(array_merge($this->route, $params));
        }

        throw new InvalidConfigException('The "route" property must be set.');
    }

    /**
     * Creates a page instance and sets the test guy to use [[url]].
     * @param \Codeception\Actor $I      the test guy instance
     * @param array              $params the GET parameters to be used to generate [[url]]
     * @return static the page instance
     * @throws \yii\base\InvalidConfigException
     */
    public static function openBy(Actor $I, array $params = []): BasePage
    {
        $page = new static($I);
        $I->amOnPage($page->getUrl($params));

        return $page;
    }

    /**
     * @param AcceptanceTester $I
     * @param                  $params
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getPageUrl(AcceptanceTester $I, $params): string
    {
        $page = new static($I);
        return $page->getUrl($params);
    }
}
