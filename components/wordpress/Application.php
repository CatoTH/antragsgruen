<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\components\wordpress;

use app\controllers\Base;
use Yii;
use yii\base\InvalidRouteException;
use yii\web\Session;
use yii\web\User;

/**
 * Application is the base class for all web application classes.
 *
 * @property string $homeUrl The homepage URL.
 * @property Session $session The session component. This property is read-only.
 * @property User $user The user component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Application extends \yii\web\Application
{
    /**
     * Returns the user component.
     * @return User the user component.
     */
    public function getUser()
    {
        return $this->get('user');
    }

    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request'      => ['class' => 'app\components\wordpress\Request'],
            'response'     => ['class' => 'app\components\wordpress\Response'],
            'session'      => ['class' => 'yii\web\Session'],
            'user'         => ['class' => 'app\components\wordpress\User'],
            'errorHandler' => ['class' => 'yii\web\ErrorHandler'],
        ]);
    }

    /**
     * Runs a controller action specified by a route.
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * If the route is empty, the method will use [[defaultRoute]].
     *
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     *
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully
     */
    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Base */
            list($controller, $actionID) = $parts;
            $controller->setWordpressMode();

            $oldController        = Yii::$app->controller;
            Yii::$app->controller = $controller;
            $result               = $controller->runAction($actionID, $params);
            Yii::$app->controller = $oldController;

            $wordpressData           = WordpressLayoutData::getInstance();
            $wordpressData->content  = $result;
            $wordpressData->sidebar  = $controller->getSidebarContent();
            $wordpressData->jsFiles  = $controller->layoutParams->getJSFiles();
            $wordpressData->onLoadJs = $controller->layoutParams->onloadJs;
            $wordpressData->cssFiles = [
                $controller->layoutParams->resourceUrl('css/layout-wordpress.css'),
            ];

            return $result;
        } else {
            $id = $this->getUniqueId();
            throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
        }
    }

}
