<?php

namespace app\components\yii;

class UrlManager extends \yii\web\UrlManager
{
    /**
     * URLs with a trailing slash that a rule accepts as they are (like "api-docs/<action:\w*>") are served directly.
     * Only the others are passed on to the normalizer, which redirects them to the URL without the slash.
     *
     * @param \yii\web\Request $request
     * @return array|bool
     */
    public function parseRequest($request)
    {
        if ($this->enablePrettyUrl && $this->normalizer !== false && str_ends_with($request->getPathInfo(), '/')) {
            $normalizer = $this->normalizer;
            $this->normalizer = false;
            try {
                foreach ($this->rules as $rule) {
                    $result = $rule->parseRequest($this, $request);
                    if ($result !== false) {
                        return $result;
                    }
                }
            } finally {
                $this->normalizer = $normalizer;
            }
        }

        return parent::parseRequest($request);
    }

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
