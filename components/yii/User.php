<?php

namespace app\components\yii;

use app\components\RequestContext;
use app\models\settings\AntragsgruenApp;
use yii\web\Cookie;
use yii\web\IdentityInterface;

class User extends \yii\web\User
{
    protected function getCookieDomain(): string
    {
        $params = AntragsgruenApp::getInstance();
        if ($params->cookieDomain) {
            return $params->cookieDomain;
        } elseif ($params->domainPlain) {
            return '.' . parse_url($params->domainPlain, PHP_URL_HOST);
        } else {
            return '';
        }
    }

    /**
     * Renews the identity cookie.
     * This method will set the expiration time of the identity cookie to be the current time
     * plus the originally specified cookie duration.
     */
    protected function renewIdentityCookie(): void
    {
        $name  = $this->identityCookie['name'];
        $value = \Yii::$app->getRequest()->getCookies()->getValue($name);
        if ($value !== null) {
            $data = json_decode($value, true);
            if (is_array($data) && isset($data[2])) {
                $cookie         = new Cookie($this->identityCookie);
                $cookie->value  = $value;
                $cookie->expire = time() + (int)$data[2];
                $cookie->domain = $this->getCookieDomain();
                \Yii::$app->getResponse()->getCookies()->add($cookie);
            }
        }
    }

    /**
     * Sends an identity cookie.
     * This method is used when [[enableAutoLogin]] is true.
     * It saves [[id]], [[IdentityInterface::getAuthKey()|auth key]], and the duration of cookie-based login
     * information in the cookie.
     * @param IdentityInterface $identity
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * @see loginByCookie()
     */
    protected function sendIdentityCookie($identity, $duration): void
    {
        $cookie         = new Cookie($this->identityCookie);
        $cookie->domain = $this->getCookieDomain();
        $cookie->value  = json_encode([
            $identity->getId(),
            $identity->getAuthKey(),
            $duration,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $cookie->expire = time() + $duration;
        \Yii::$app->getResponse()->getCookies()->add($cookie);
    }

    /**
     * Switches to a new identity for the current user.
     *
     * When [[enableSession]] is true, this method may use session and/or cookie to store the user identity information,
     * according to the value of `$duration`. Please refer to [[login()]] for more details.
     *
     * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
     * when the current user needs to be associated with the corresponding identity information.
     *
     * @param IdentityInterface|null $identity the identity information to be associated with the current user.
     * If null, it means switching the current user to be a guest.
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * This parameter is used only when `$identity` is not null.
     */
    public function switchIdentity($identity, $duration = 0): void
    {
        $this->setIdentity($identity);

        if (!$this->enableSession) {
            return;
        }

        /* Ensure any existing identity cookies are removed. */
        if ($this->enableAutoLogin) {
            $cookie         = new Cookie($this->identityCookie);
            $cookie->domain = $this->getCookieDomain();
            \Yii::$app->getResponse()->getCookies()->remove($cookie);
        }

        $session = RequestContext::getSession();
        if (!YII_ENV_TEST) {
            $session->regenerateID(true);
        }
        $session->remove($this->idParam);
        $session->remove($this->authTimeoutParam);

        if ($identity) {
            $session->set($this->idParam, $identity->getId());
            if ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
            if ($this->absoluteAuthTimeout !== null) {
                $session->set($this->absoluteAuthTimeoutParam, time() + $this->absoluteAuthTimeout);
            }
            if ($duration > 0 && $this->enableAutoLogin) {
                $this->sendIdentityCookie($identity, $duration);
            }
        }
    }
}
