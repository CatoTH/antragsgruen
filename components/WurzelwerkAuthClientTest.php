<?php

namespace app\components;

use app\models\db\User;
use app\models\exceptions\Internal;
use Yii;
use yii\authclient\OpenId;
use yii\base\Security;
use yii\db\Expression;

class WurzelwerkAuthClientTest extends WurzelwerkAuthClient
{
    private static $TEST_USERS = [
        'DoeJane' => [
            'id'                  => 'DoeJane',
            'contact/email'       => 'jane@example.org',
            'namePerson/friendly' => 'DoeJane',
        ],
        'DoeJohn' => [
            'id'                  => 'DoeJohn',
            'contact/email'       => 'john@example.org',
            'namePerson/friendly' => 'DoeJohn',
        ],
    ];

    /**
     * @param bool $validateRequiredAttributes
     * @return bool whether the verification was successful.
     * @throws Internal
     * @throws \yii\base\Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate($validateRequiredAttributes = true)
    {
        if (YII_ENV !== 'test') {
            throw new Internal('This function can only be called in test mode');
        }
        $claimedId = $this->getClaimedId();
        return isset(static::$TEST_USERS[$claimedId]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAuthenticatedAttributes()
    {
        if (!$this->validate()) {
            throw new \Exception('Login-Daten nicht überprüfbar');
        }
        return static::$TEST_USERS[$this->getClaimedId()];
    }

    /**
     * @return User
     * @throws \Exception
     */
    public function getOrCreateUser()
    {
        $attributes = $this->getAuthenticatedAttributes();
        $auth       = User::wurzelwerkId2Auth($attributes['id']);

        $user = User::findOne(['auth' => $auth]);
        if ($user) {
            return $user;
        }

        $user = new User();
        //$user->name            = $attributes['namePerson/friendly'];
        $user->name            = ''; // https://github.com/CatoTH/antragsgruen/issues/77
        $user->email           = $attributes['contact/email'];
        $user->emailConfirmed  = 1;
        $user->auth            = $auth;
        $user->status          = User::STATUS_CONFIRMED;
        $user->siteNamespaceId = null;
        // @TODO E-Mail
        if (!$user->save()) {
            throw new \Exception('Could not create user: ' . $user->getErrors());
        }

        return $user;
    }

    /**
     * @param string $backUrl
     * @return string
     */
    public function getFakeRedirectUrl($backUrl)
    {
        return UrlHelper::createUrl([
            'user/loginwurzelwerk',
            'backUrl'             => $backUrl,
            'openid.claimed_id'   => $this->getClaimedId(),
            'openid.mode'         => 'id_res',
            'openid.identity'     => 'https://service.gruene.de/openid/' . strtolower($this->getClaimedId()),
            'openid.assoc_handle' => '123',
            'openid.return_to'    => '',
        ]);
    }
}
