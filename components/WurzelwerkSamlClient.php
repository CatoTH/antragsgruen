<?php

namespace app\components;

use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use SimpleSAML_Auth_Simple;
use yii\authclient\ClientInterface;
use app\models\db\User;

class WurzelwerkSamlClient implements ClientInterface
{
    const PARAM_EMAIL        = 'gmnMail';
    const PARAM_USERNAME     = 'urn:oid:0.9.2342.19200300.100.1.1';
    const PARAM_GIVEN_NAME   = 'urn:oid:2.5.4.42';
    const PARAM_FAMILY_NAME  = 'urn:oid:2.5.4.4';
    const PARAM_ORGANIZATION = 'membershipOrganizationKey';

    /** @var SimpleSAML_Auth_Simple */
    private $auth;

    private $params;

    /**
     * WurzelwerkSimplesamlClient constructor.
     */
    public function __construct()
    {

        $this->auth   = new SimpleSAML_Auth_Simple('default-sp');
        $this->params = $this->auth->getAttributes();
    }

    /**
     * @throws \Exception
     */
    public function requireAuth()
    {
        $this->auth->requireAuth([]);
        if (!$this->auth->isAuthenticated()) {
            throw new \Exception('SimpleSaml: Something went wrong on requireAuth');
        }
        $this->params = $this->auth->getAttributes();
    }

    /**
     * @return User
     * @throws \Exception
     */
    public function getOrCreateUser()
    {
        $email         = $this->params[static::PARAM_EMAIL][0];
        $givenname     = $this->params[static::PARAM_GIVEN_NAME][0];
        $familyname    = $this->params[static::PARAM_FAMILY_NAME][0];
        $organizations = ($this->params[static::PARAM_ORGANIZATION] ? $this->params[static::PARAM_ORGANIZATION] : []);
        $username      = $this->params[static::PARAM_USERNAME][0];
        $auth          = User::wurzelwerkId2Auth($username);

        /** @var User $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $user = new User();
        }

        $user->name           = $givenname . ' ' . $familyname;
        $user->nameGiven      = $givenname;
        $user->nameFamily     = $familyname;
        $user->email          = $email;
        $user->emailConfirmed = 1;
        $user->fixedData      = 1;
        $user->auth           = $auth;
        $user->status         = User::STATUS_CONFIRMED;

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->samlOrgaFile && file_exists($params->samlOrgaFile)) {
            $orgas = json_decode(file_get_contents($params->samlOrgaFile), true);
            $user->organization = '';
            foreach ($organizations as $organization) {
                $orgaKv = substr($organization, 0, 6);
                if (isset($orgas[$orgaKv])) {
                    $user->organization = $orgas[$orgaKv]['kurzname'];
                }
            }
        }

        if (!$user->save()) {
            throw new \Exception('Could not create user: ' . $user->getErrors());
        }

        return $user;
    }

    /**
     *
     */
    public function logout()
    {
        if ($this->auth->isAuthenticated()) {
            $this->auth->logout();
        }
        \Yii::$app->user->logout();
    }

    /**
     * @param string $id service id.
     * @throws Internal
     */
    public function setId($id)
    {
        throw new Internal('Not implemented yet: SAML / setId');
    }

    /**
     * @return string service id
     * @throws Internal
     */
    public function getId()
    {
        throw new Internal('Not implemented yet: SAML / getId');
    }

    /**
     * @return string service name.
     * @throws Internal
     */
    public function getName()
    {
        throw new Internal('Not implemented yet: SAML / getName');
    }

    /**
     * @param string $name service name.
     * @throws Internal
     */
    public function setName($name)
    {
        throw new Internal('Not implemented yet: SAML / setName');
    }

    /**
     * @return string service title.
     * @throws Internal
     */
    public function getTitle()
    {
        throw new Internal('Not implemented yet: SAML / getTitle');
    }

    /**
     * @param string $title service title.
     */
    public function setTitle($title)
    {
        throw new Internal('Not implemented yet: SAML / setTitle');
    }

    /**
     * @return array list of user attributes
     * @throws Internal
     */
    public function getUserAttributes()
    {
        throw new Internal('Not implemented yet: SAML / getUserAttributes');
    }

    /**
     * @return array view options in format: optionName => optionValue
     * @throws Internal
     */
    public function getViewOptions()
    {
        throw new Internal('Not implemented yet: SAML / getViewOptions');
    }
}