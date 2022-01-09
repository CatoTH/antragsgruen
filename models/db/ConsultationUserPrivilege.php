<?php

namespace app\models\db;

use app\components\mail\Tools as MailTools;
use app\components\UrlHelper;
use app\models\exceptions\{AlreadyExists, FormError, MailNotSent};
use app\models\notifications\UserAsksPermission;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @deprecated
 *
 * @property int $userId
 * @property int $consultationId
 * @property int $privilegeView
 * @property int $privilegeCreate
 * @property int $adminSuper
 * @property int $adminContentEdit
 * @property int $adminScreen
 * @property int $adminProposals
 *
 * @property User $user
 * @property Consultation $consultation
 */
class ConsultationUserPrivilege extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationUserPrivilege';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'userId'], 'required'],
            [['consultationId', 'userId'], 'number'],
            [['privilegeView', 'privilegeCreate'], 'number'],
            [['adminSuper', 'adminContentEdit', 'adminScreen', 'adminProposals'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param string $username
     * @throws AlreadyExists
     * @throws FormError
     */
    public static function createWithUserSamlWW(Consultation $consultation, $username)
    {
        if (preg_match('/[^\w]/siu', $username)) {
            throw new FormError('Invalid username');
        }
        $auth = 'openid:https://service.gruene.de/openid/' . $username;

        /** @var User $user */
        $user = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if (!$user) {
            $user                  = new User();
            $user->auth            = $auth;
            $user->email           = '';
            $user->name            = '';
            $user->emailConfirmed  = 0;
            $user->pwdEnc          = null;
            $user->status          = User::STATUS_CONFIRMED;
            $user->organizationIds = '';
            $user->save();
        }

        /** @var ConsultationUserPrivilege $privilege */
        $privilege = static::findOne(['userId' => $user->id, 'consultationId' => $consultation->id]);
        if ($privilege) {
            throw new AlreadyExists();
        } else {
            $privilege                   = new ConsultationUserPrivilege();
            $privilege->consultationId   = $consultation->id;
            $privilege->userId           = $user->id;
            $privilege->adminContentEdit = 0;
            $privilege->adminScreen      = 0;
            $privilege->adminSuper       = 0;
            $privilege->adminProposals   = 0;
            $privilege->privilegeCreate  = 1;
            $privilege->privilegeView    = 1;
            $privilege->save();
        }
    }

    /**
     * @return bool
     */
    public function isAskingForPermission()
    {
        // @TODO Find another implementation for this
        return ($this->privilegeCreate === 0 && $this->privilegeView === 0 &&
            !$this->containsPrivilege(User::PRIVILEGE_ANY));
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @return ConsultationUserPrivilege
     */
    public static function askForConsultationPermission(User $user, Consultation $consultation)
    {
        foreach ($consultation->userPrivileges as $userPrivilege) {
            if ($userPrivilege->userId === $user->id) {
                // Already asked
                return $userPrivilege;
            }
        }

        $priv                   = new ConsultationUserPrivilege();
        $priv->userId           = $user->id;
        $priv->consultationId   = $consultation->id;
        $priv->privilegeView    = 0;
        $priv->privilegeCreate  = 0;
        $priv->adminContentEdit = 0;
        $priv->adminProposals   = 0;
        $priv->adminScreen      = 0;
        $priv->adminSuper       = 0;
        $priv->save();

        new UserAsksPermission($user, $consultation);

        return $priv;
    }

    public function grantPermission()
    {
        $this->privilegeCreate = 1;
        $this->privilegeView   = 1;
        $this->save();

        if ($this->user->email && $this->user->emailConfirmed) {
            $consUrl   = UrlHelper::createUrl('consultation/index');
            $consUrl   = UrlHelper::absolutizeLink($consUrl);
            $emailText = str_replace('%LINK%', $consUrl, \Yii::t('user', 'access_granted_email'));

            MailTools::sendWithLog(
                EMailLog::TYPE_ACCESS_GRANTED,
                $this->consultation,
                $this->user->email,
                $this->user->id,
                \Yii::t('user', 'acc_grant_email_title'),
                $emailText
            );
        }
    }
}
