<?php

namespace app\components\wordpress;

use app\models\exceptions\Internal;
use yii\web\IdentityInterface;
use \app\models\db\User as DBUser;

class User extends \yii\web\User
{
    private $_access = [];

    /** @var \WP_User */
    private $_wordpressUser;

    /** @var DBUser */
    private $_identity;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        $this->_wordpressUser = wp_get_current_user();
        $this->_identity = $this->getOrCreateUser();
    }

    /**
     * @return User
     * @throws \Exception
     */
    public function getOrCreateUser()
    {
        $auth = DBUser::wordpressId2Auth($this->_wordpressUser->ID);

        $user = DBUser::findOne(['auth' => $auth]);
        if ($user) {
            return $user;
        }

        $user         = new DBUser();
        $user->name   = '';
        $user->auth   = $auth;
        $user->status = DBUser::STATUS_CONFIRMED;
        if ( ! $user->save()) {
            throw new \Exception('Could not create user: ' . $user->getErrors());
        }

        return $user;
    }

    /**
     * @param IdentityInterface $identity
     * @param int $duration
     *
     * @return bool|void
     * @throws Internal
     */
    public function login(IdentityInterface $identity, $duration = 0)
    {
        throw new Internal('Not possible for wordpress-users: login');
    }

    /**
     * @param string $token the access token
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     *
     * @return null|IdentityInterface the identity associated with the given access token. Null is returned if
     * the access token is invalid or [[login()]] is unsuccessful.
     * @throws Internal
     */
    public function loginByAccessToken($token, $type = null)
    {
        throw new Internal('Not possible for wordpress-users: loginByAccessToken');
    }

    /**
     */
    protected function loginByCookie()
    {
        throw new Internal('Not possible for wordpress-users: loginByCookie');

    }

    /**
     * @param boolean $destroySession whether to destroy the whole session. Defaults to true.
     * This parameter is ignored if [[enableSession]] is false.
     *
     * @return bool whether the user is logged out
     * @throws Internal
     */
    public function logout($destroySession = true)
    {
        throw new Internal('Not possible for wordpress-users: logout');
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return boolean whether the current user is a guest.
     * @see getIdentity()
     */
    public function getIsGuest()
    {
        return ($this->_wordpressUser->ID == 0);
    }

    /**
     * Returns a value that uniquely represents the user.
     * @return string|integer the unique identifier for the user. If null, it means the user is a guest.
     * @see getIdentity()
     */
    public function getId()
    {
        return $this->_wordpressUser->ID;
    }

    /**
     * @param boolean $autoRenew whether to automatically renew authentication status if it has not been done so before.
     * This is only useful when [[enableSession]] is true.
     *
     * @return IdentityInterface|null the identity object associated with the currently logged-in user.
     * `null` is returned if the user is not logged in (not authenticated).
     */
    public function getIdentity($autoRenew = true)
    {
        return $this->_identity;
    }

    /**
     * @param null $defaultUrl
     *
     * @return string the URL that the user should be redirected to after login.
     * @throws Internal
     */
    public function getReturnUrl($defaultUrl = null)
    {
        throw new Internal('Not possible for wordpress-users: getReturnUrl');
    }

    /**
     * @param array|string $url
     *
     * @throws Internal
     */
    public function setReturnUrl($url)
    {
        throw new Internal('Not possible for wordpress-users: setReturnUrl');
    }

    /**
     * @param bool $checkAjax
     *
     * @return void|\yii\web\Response
     * @throws Internal
     */
    public function loginRequired($checkAjax = true)
    {
        return false;

    }

    /**
     * Renews the identity cookie.
     * This method will set the expiration time of the identity cookie to be the current time
     * plus the originally specified cookie duration.
     */
    protected function renewIdentityCookie()
    {
        throw new Internal('Not possible for wordpress-users: renewIdentityCookie');
    }

    /**
     * @param IdentityInterface $identity
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     *
     * @throws Internal
     * @see loginByCookie()
     */
    protected function sendIdentityCookie($identity, $duration)
    {
        throw new Internal('Not possible for wordpress-users: sendIdentityCookie');
    }

    /**
     * @param IdentityInterface|null $identity the identity information to be associated with the current user.
     * If null, it means switching the current user to be a guest.
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * This parameter is used only when `$identity` is not null.
     *
     * @throws Internal
     */
    public function switchIdentity($identity, $duration = 0)
    {
        throw new Internal('Not possible for wordpress-users: switchIdentity');
    }

    /**
     */
    protected function renewAuthStatus()
    {
    }
}