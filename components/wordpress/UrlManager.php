<?php

namespace app\components\wordpress;

class UrlManager extends \yii\web\UrlManager
{
    /**
     * Initializes UrlManager.
     */
    public function init()
    {
        parent::init();
        if (is_admin()) {
            $this->enablePrettyUrl = false;
        }
    }

    /** @param string|array $params use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @return string the created URL
     */
    public function createUrl($params)
    {
        // @TODO Special handling if "page" parameter is set from the app
        $get = \Yii::$app->request->get();
        if (isset($get['page'])) {
            $params['page'] = $get['page'];
        }
        return parent::createUrl($params);
    }
}
