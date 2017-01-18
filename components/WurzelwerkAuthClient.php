<?php

namespace app\components;

use app\models\db\User;
use Yii;
use yii\authclient\OpenId;

class WurzelwerkAuthClient extends OpenId
{
    public $authUrl = 'https://service.gruene.de/openid/';

    public $requiredAttributes = [
        'namePerson/friendly',
    ];

    public $optionalAttributes = [
        'namePerson/first',
        'namePerson/last',
        'contact/email',
    ];

    /**
     * @return string
     */
    protected function defaultName()
    {
        return 'wurzelwerk';
    }

    /**
     * @return string
     */
    protected function defaultTitle()
    {
        return 'Wurzelwerk-Login';
    }

    /**
     * @return array
     */
    protected function defaultViewOptions()
    {
        return [
            'popupWidth'  => 800,
            'popupHeight' => 500,
        ];
    }

    /**
     * @param string $user_id
     */
    public function setClaimedId($user_id)
    {
        parent::setClaimedId($user_id);
        $this->authUrl = 'https://service.gruene.de/openid/?user=' . $user_id;
    }


    /**
     * Performs OpenID verification with the OP.
     * @param bool $validateRequiredAttributes
     * @return bool whether the verification was successful.
     * @throws \yii\base\Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate($validateRequiredAttributes = true)
    {
        $claimedId = $this->getClaimedId();
        if (empty($claimedId) || !isset($this->data['openid_assoc_handle'])) {
            return false;
        }
        $params = [
            'openid.assoc_handle' => $this->data['openid_assoc_handle'],
            'openid.signed'       => $this->data['openid_signed'],
            'openid.sig'          => $this->data['openid_sig'],
        ];

        if (isset($this->data['openid_ns'])) {
            /* We're dealing with an OpenID 2.0 server, so let's set an ns
            Even though we should know location of the endpoint,
            we still need to verify it by discovery, so $server is not set here*/
            $params['openid.ns'] = 'http://specs.openid.net/auth/2.0';
        } elseif (isset($this->data['openid_claimed_id']) &&
            $this->data['openid_claimed_id'] != $this->data['openid_identity']
        ) {
            // If it's an OpenID 1 provider, and we've got claimed_id,
            // we have to append it to the returnUrl, like authUrlV1 does.
            $this->returnUrl .= (strpos($this->returnUrl, '?') ? '&' : '?') . 'openid.claimed_id=' . $claimedId;
        }

        if (!$this->compareUrl($this->data['openid_return_to'], $this->returnUrl)) {
            // The return_to url must match the url of current request.
            return false;
        }

        foreach (explode(',', $this->data['openid_signed']) as $item) {
            $value                     = $this->data['openid_' . str_replace('.', '_', $item)];
            $params['openid.' . $item] = $value;
        }

        $params['openid.mode'] = 'check_authentication';

        $response = $this->sendRequest("https://service.gruene.de/openid/?user=" . $claimedId, 'POST', $params);

        if (!preg_match('/is_valid\s*:\s*true/i', $response)) {
            return false;
        }
        return $this->validateRequiredAttributes();
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

        $attributes = $this->getUserAttributes();
        if (!isset($attributes['id'])) {
            throw new \Exception('Incomplete Login data');
        }

        return $attributes;
    }

    /**
     * Generates default [[returnUrl]] value.
     * @return string default authentication return URL.
     */
    protected function defaultReturnUrl()
    {
        $params = \Yii::$app->request->get();
        $keys   = array_keys($params);
        foreach ($keys as $name) {
            if (strncmp('openid', $name, 6) === 0) {
                unset($params[$name]);
            }
        }
        $params[0] = Yii::$app->requestedRoute;
        $url       = Yii::$app->getUrlManager()->createUrl($params);

        if (strpos($url, $this->getTrustRoot()) !== 0) {
            $url = $this->getTrustRoot() . $url;
        }

        return $url;
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
        if (isset($attributes['contact/email']) && filter_var($attributes['contact/email'], FILTER_VALIDATE_EMAIL)) {
            $user->email          = $attributes['contact/email'];
            $user->emailConfirmed = 1;
        }
        $user->auth            = $auth;
        $user->status          = User::STATUS_CONFIRMED;
        
        if (!$user->save()) {
            throw new \Exception('Could not create user: ' . $user->getErrors());
        }

        return $user;
    }
}
