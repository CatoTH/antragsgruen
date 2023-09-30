<?php

namespace app\components\yii;

class UrlManager extends \yii\web\UrlManager
{
    /**
     * @param array|string $params
     * @return string
     */
    public function createUrl($params)
    {
        $url = parent::createUrl($params);
        if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) {
            $currHost = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
            if (str_starts_with($url, $currHost)) {
                $url = substr($url, strlen($currHost));
            }
        }
        if ($url === '') {
            $url = '/';
        }
        return $url;
    }
}
