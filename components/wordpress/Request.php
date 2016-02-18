<?php

namespace app\components\wordpress;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Cookie;

class Request extends \yii\web\Request
{
    /**
     * Converts `$_COOKIE` into an array of [[Cookie]].
     * @return array the cookies obtained from request
     * @throws InvalidConfigException if [[cookieValidationKey]] is not set when [[enableCookieValidation]] is true
     */
    protected function loadCookies()
    {
        $cookies = [];
        if ($this->enableCookieValidation) {
            if ($this->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
            foreach ($_COOKIE as $name => $value) {
                if ( ! is_string($value)) {
                    continue;
                }
                $data = Yii::$app->getSecurity()->validateData(stripslashes($value), $this->cookieValidationKey);
                if ($data === false) {
                    continue;
                }
                $data = @unserialize($data);
                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = new Cookie([
                        'name'   => $name,
                        'value'  => stripslashes($data[1]),
                        'expire' => null,
                    ]);
                }
            }
        } else {
            foreach ($_COOKIE as $name => $value) {
                $cookies[$name] = new Cookie([
                    'name'   => $name,
                    'value'  => stripslashes($value),
                    'expire' => null,
                ]);
            }
        }

        return $cookies;
    }
}