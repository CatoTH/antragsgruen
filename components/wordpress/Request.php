<?php

namespace app\components\wordpress;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;

class Request extends \yii\web\Request
{
    protected $adminDefaultRoute = '';

    /**
     * @param string $route
     */
    public function setAdminDefaultRoute($route) {
        $this->adminDefaultRoute = $route;
    }

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

    private $_bodyParams;

    /**
     * Returns the request parameters given in the request body.
     *
     * Request parameters are determined using the parsers configured in [[parsers]] property.
     * If no parsers are configured for the current [[contentType]] it uses the PHP function `mb_parse_str()`
     * to parse the [[rawBody|request body]].
     * @return array the request parameters given in the request body.
     * @throws \yii\base\InvalidConfigException if a registered parser does not implement the [[RequestParserInterface]].
     * @see getMethod()
     * @see getBodyParam()
     * @see setBodyParams()
     */
    public function getBodyParams()
    {
        if ($this->_bodyParams === null) {
            $bodyParams        = parent::getBodyParams();
            $this->_bodyParams = \wp_unslash($bodyParams);
        }

        return $this->_bodyParams;
    }

    /**
     * Sets the request body parameters.
     *
     * @param array $values the request body parameters (name-value pairs)
     *
     * @see getBodyParam()
     * @see getBodyParams()
     */
    public function setBodyParams($values)
    {
        $this->_bodyParams = $values;
    }


    private $_queryParams;

    /**
     * Returns the request parameters given in the [[queryString]].
     *
     * This method will return the contents of `$_GET` if params where not explicitly set.
     * @return array the request GET parameter values.
     * @see setQueryParams()
     */
    public function getQueryParams()
    {
        if ($this->_queryParams === null) {
            return \wp_unslash($_GET);
        }

        return $this->_queryParams;
    }

    /**
     * Sets the request [[queryString]] parameters.
     *
     * @param array $values the request query parameters (name-value pairs)
     *
     * @see getQueryParam()
     * @see getQueryParams()
     */
    public function setQueryParams($values)
    {
        $this->_queryParams = $values;
    }

    /**
     * Resolves the current request into a route and the associated parameters.
     * @return array the first element is the route, and the second is the associated parameters.
     * @throws NotFoundHttpException if the request cannot be resolved.
     */
    public function resolve()
    {
        if (is_admin()) {
            if (isset($_GET['route'])) {
                return [stripslashes($_GET['route']), $this->getQueryParams()];
            } else {
                return [$this->adminDefaultRoute, $this->getQueryParams()];
            }
        } else {
            $result = Yii::$app->getUrlManager()->parseRequest($this);
            if ($result !== false) {
                list ($route, $params) = $result;
                if ($this->_queryParams === null) {
                    $_GET = $params + $_GET; // preserve numeric keys
                } else {
                    $this->_queryParams = $params + $this->_queryParams;
                }

                return [$route, $this->getQueryParams()];
            } else {
                throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
            }
        }
    }
}