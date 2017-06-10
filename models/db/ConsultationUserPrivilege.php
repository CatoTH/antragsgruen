<?php

namespace app\models\db;

use app\components\UrlHelper;
use app\models\exceptions\AlreadyExists;
use app\models\exceptions\MailNotSent;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $userId
 * @property int $consultationId
 * @property string $privilegeView
 * @property string $privilegeCreate
 * @property string $adminSuper
 * @property string $adminContentEdit
 * @property string $adminScreen
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
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationUserPrivilege';
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
            [['adminSuper', 'adminContentEdit', 'adminScreen'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param string $email
     * @param string $name
     * @param string $emailText
     * @param string|null $setPassword
     * @throws AlreadyExists
     */
    public static function createWithUser(Consultation $consultation, $email, $name, $emailText, $setPassword = null)
    {
        $email = mb_strtolower($email);
        $auth  = 'email:' . $email;

        /** @var User $user */
        $user = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if (!$user) {
            if ($setPassword) {
                $password = $setPassword;
            } else {
                $password = User::createPassword();
            }

            $user                 = new User();
            $user->auth           = 'email:' . $email;
            $user->name           = $name;
            $user->emailConfirmed = 0;
            $user->pwdEnc         = password_hash($password, PASSWORD_DEFAULT);
            $user->status         = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
            $user->save();

            $accountText = str_replace(
                ['%EMAIL%', '%PASSWORD%'],
                [$email, $password],
                \Yii::t('user', 'acc_grant_email_userdata')
            );
        } else {
            $accountText = '';
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
            $privilege->privilegeCreate  = 1;
            $privilege->privilegeView    = 1;
            $privilege->save();
        }

        $consUrl   = UrlHelper::createUrl('consultation/index');
        $consUrl   = UrlHelper::absolutizeLink($consUrl);
        $emailText = str_replace('%LINK%', $consUrl, $emailText);

        try {
            \app\components\mail\Tools::sendWithLog(
                EMailLog::TYPE_ACCESS_GRANTED,
                $consultation->site,
                $email,
                $user->id,
                \Yii::t('user', 'acc_grant_email_title'),
                $emailText,
                '',
                ['%ACCOUNT%' => $accountText]
            );
        } catch (MailNotSent $e) {
            \yii::$app->session->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
        }
    }

    /**
     * @param int $permission
     * @return boolean
     */
    public function containsPrivilege($permission)
    {
        switch ($permission) {
            case User::PRIVILEGE_ANY:
                return ($this->adminSuper == 1 || $this->adminContentEdit == 1 || $this->adminScreen);
            case User::PRIVILEGE_CONSULTATION_SETTINGS:
                return ($this->adminSuper == 1);
            case User::PRIVILEGE_CONTENT_EDIT:
                return ($this->adminContentEdit == 1);
            case User::PRIVILEGE_SCREENING:
                return ($this->adminScreen == 1);
            case User::PRIVILEGE_MOTION_EDIT:
                return ($this->adminSuper == 1);
            case User::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS:
                return ($this->adminSuper == 1);
            default:
                return false;
        }
    }
}
