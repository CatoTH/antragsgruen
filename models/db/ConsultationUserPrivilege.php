<?php
namespace app\models\db;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\AlreadyExists;
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
        return 'consultationUserPrivilege';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
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
     */
    public static function createWithUser(Consultation $consultation, $email, $name, $emailText)
    {
        $email = mb_strtolower($email);

        /** @var User $user */
        $user = User::findOne(['auth' => 'email:' . $email]);
        if (!$user) {
            $password = User::createPassword();

            $user                 = new User();
            $user->auth           = 'email:' . $email;
            $user->name           = $name;
            $user->emailConfirmed = 0;
            $user->pwdEnc         = password_hash($password, PASSWORD_DEFAULT);
            $user->status         = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
            $user->save();

            $accountText = 'E-Mail / BenutzerInnenname: ' . $email . "\n";
            $accountText .= 'Passwort: ' . $password;
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

        $fromName = $consultation->site->getBehaviorClass()->getMailFromName();
        Tools::sendMailLog(
            EMailLog::TYPE_ACCESS_GRANTED,
            $email,
            $user->id,
            'Antragsgrün-Zugriff',
            $emailText,
            $fromName,
            null,
            ['%ACCOUNT%' => $accountText]
        );
    }
}
