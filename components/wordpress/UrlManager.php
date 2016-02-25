<?php

namespace app\components\wordpress;

class UrlManager extends \yii\web\UrlManager
{

    /** @param string|array $params use a string to represent a route (e.g. `site/index`),
     * or an array to represent a route with query parameters (e.g. `['site/index', 'param1' => 'value1']`).
     * @return string the created URL
     */
    public function createUrl($params)
    {
        $this->showScriptName = false;

        $targetRoute   = $params[0];
        $isAdminTarget = false;
        $origPretty    = $this->enablePrettyUrl;
        foreach (WordpressCompatibility::$SETTING_PAGE_ROUTES as $route => $page) {
            if (strpos($targetRoute, $route) === 0) {
                $isAdminTarget = $page;
            }
        }

        if ($isAdminTarget) {
            $params['page']        = $isAdminTarget;
            $this->enablePrettyUrl = false;
        }

        $url                   = parent::createUrl($params);
        $this->enablePrettyUrl = $origPretty;

        if ( ! $isAdminTarget) {
            $url = get_site_url() . $url;
        }

        return $url;
    }
}
