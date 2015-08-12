<?php

namespace app\models\db;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use yii\web\IdentityInterface;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $emailConfirmed
 * @property string $auth
 * @property string $dateCreation
 * @property string $status
 * @property string $pwdEnc
 * @property string $authKey
 * @property string $recoveryToken
 * @property string $recoveryAt
 *
 * @property null|AmendmentComment[] $amendmentComments
 * @property null|AmendmentSupporter[] $amendmentSupports
 * @property null|MotionComment[] $motionComments
 * @property null|MotionSupporter[] $motionSupports
 * @property Site[] $adminSites
 * @property ConsultationUserPrivilege[] $consultationPrivileges
 * @property ConsultationLog[] $logEntries
 * @property UserNotification[] $notifications
 */
class User extends ActiveRecord implements IdentityInterface
{

    const STATUS_UNCONFIRMED = 1;
    const STATUS_CONFIRMED   = 0;
    const STATUS_DELETED     = -1;

    const PRIVILEGE_ANY                   = 0;
    const PRIVILEGE_CONSULTATION_SETTINGS = 1;
    const PRIVILEGE_CONTENT_EDIT          = 2;
    const PRIVILEGE_SCREENING             = 3;
    const PRIVILEGE_MOTION_EDIT           = 4;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            1  => 'Nicht bestätigt',
            0  => 'Bestätigt',
            -1 => 'Gelöscht',
        ];
    }


    /**
     * @return null|User
     */
    public static function getCurrentUser()
    {
        if (\Yii::$app->user->isGuest) {
            return null;
        } else {
            return \Yii::$app->user->identity;
        }
    }

    /**
     * @param Consultation|null $consultation
     * @param int $privilege
     * @return bool
     * @throws Internal
     */
    public static function currentUserHasPrivilege($consultation, $privilege)
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($consultation, $privilege);
    }


    /**
     * @return string
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionComments()
    {
        return $this->hasMany(MotionComment::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSupports()
    {
        return $this->hasMany(MotionSupporter::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupports()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmailLogs()
    {
        return $this->hasMany(EMailLog::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogEntries()
    {
        return $this->hasMany(ConsultationLog::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdminSites()
    {
        return $this->hasMany(Site::className(), ['id' => 'siteId'])->viaTable('siteAdmin', ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationPrivileges()
    {
        return $this->hasMany(ConsultationUserPrivilege::className(), ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotifications()
    {
        return $this->hasMany(UserNotification::className(), ['userId' => 'id']);
    }

    /**
     * @param Consultation $consultation
     * @return ConsultationUserPrivilege
     */
    public function getConsultationPrivilege(Consultation $consultation)
    {
        foreach ($this->consultationPrivileges as $priv) {
            if ($priv->consultationId == $consultation->id) {
                return $priv;
            }
        }
        $priv                   = new ConsultationUserPrivilege();
        $priv->consultationId   = $consultation->id;
        $priv->userId           = $this->id;
        $priv->privilegeCreate  = 0;
        $priv->privilegeView    = 0;
        $priv->adminContentEdit = 0;
        $priv->adminScreen      = 0;
        $priv->adminSuper       = 0;
        return $priv;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['auth', 'status'], 'required'],
            [['id', 'emailConfirmed'], 'number'],
        ];
    }

    /**
     * Finds an identity by the given ID.
     * @param string|integer $userId the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($userId)
    {
        return static::findOne($userId);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be
     * `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['authKey' => $token]);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|integer an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey == $authKey;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->authKey      = \Yii::$app->getSecurity()->generateRandomString();
                $this->dateCreation = new Expression("NOW()");
            }
            return true;
        }
        return false;
    }


    /**
     * @return string
     */
    public static function createPassword()
    {
        return \Yii::$app->getSecurity()->generateRandomString(8);
    }

    /**
     * @param string $date
     * @return string
     */
    public function createEmailConfirmationCode($date = '')
    {
        if (YII_ENV == 'test') {
            return 'testCode';
        }

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if ($date == '') {
            $date = date('Ymd');
        }
        $code = $this->id . '-' . substr(md5($this->id . $date . $params->randomSeed), 0, 8);
        return $code;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function checkEmailConfirmationCode($code)
    {
        if ($code == $this->createEmailConfirmationCode()) {
            return true;
        }
        if ($code == $this->createEmailConfirmationCode(date('Ymd', time() - 24 * 3600))) {
            return true;
        }
        if ($code == $this->createEmailConfirmationCode(date('Ymd', time() - 2 * 24 * 3600))) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEntitledToCreateSites()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->createNeedsWurzelwerk) {
            return $this->isWurzelwerkUser();
        } else {
            return ($this->status == User::STATUS_CONFIRMED);
        }
    }

    /**
     * @return null|string
     */
    public function getWurzelwerkName()
    {
        if (preg_match("/https:\/\/([a-z0-9_-]+)\.netzbegruener\.in\//siu", $this->auth, $matches)) {
            return $matches[1];
        }
        if (preg_match("/https:\/\/service\.gruene.de\/openid\/([a-z0-9_-]+)/siu", $this->auth, $matches)) {
            return $matches[1];
        }
        return null;
    }


    /**
     * @return bool
     */
    public function isWurzelwerkUser()
    {
        if (preg_match("/https:\/\/[a-z0-9_-]+\.netzbegruener\.in\//siu", $this->auth)) {
            return true;
        }
        if (preg_match("/https:\/\/service\.gruene.de\/openid\/[a-z0-9_-]+/siu", $this->auth)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $username
     * @return string
     */
    public static function wurzelwerkId2Auth($username)
    {
        return 'openid:https://service.gruene.de/openid/' . $username;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function validatePassword($password)
    {
        return password_verify($password, $this->pwdEnc);
    }

    /**
     * @param string $newPassword
     */
    public function changePassword($newPassword)
    {
        $this->pwdEnc        = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->recoveryToken = null;
        $this->recoveryAt    = null;
        $this->save();
    }

    /**
     * @param Consultation $consultation
     * @return MotionSupporter[]
     */
    public function getMySupportedMotionsByConsultation(Consultation $consultation)
    {
        $query = (new Query())->select('motionSupporter.*')->from('motionSupporter');
        $query->innerJoin(
            'motion',
            'motionSupporter.motionId = motion.id AND motionSupporter.role = ' . IntVal(MotionSupporter::ROLE_INITIATOR)
        );
        $query->where('motion.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->andWhere('motionSupporter.userId = ' . IntVal($this->id));
        $query->orderBy("motion.dateCreation DESC");

        return $query->all();
    }

    /**
     * @param Consultation $consultation
     * @return AmendmentSupporter[]
     */
    public function getMySupportedAmendmentsByConsultation(Consultation $consultation)
    {
        $query = (new Query())->select('amendmentSupporter.*')->from('amendmentSupporter');
        $query->innerJoin(
            'amendment',
            'amendmentSupporter.amendmentId = amendment.id AND ' .
            'amendmentSupporter.role = ' . IntVal(AmendmentSupporter::ROLE_INITIATOR)
        );
        $query->innerJoin('motion', 'motion.id = amendment.motionId');
        $query->where('motion.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('amendment.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->andWhere('amendmentSupporter.userId = ' . IntVal($this->id));
        $query->orderBy("amendment.dateCreation DESC");

        return $query->all();
    }


    /**
     * @return string
     */
    public function getNotificationUnsubscribeCode()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $code = $this->id . '-' . substr(md5($this->id . 'unsubscribe' . $params->randomSeed), 0, 8);
        return $code;
    }

    /**
     * @param string $code
     * @return null|User
     */
    public static function getUserByUnsubscribeCode($code)
    {
        $parts = explode('-', $code);
        /** @var User $user */
        $user = User::findOne($parts[0]);
        if (!$user) {
            return null;
        }
        if ($user->getNotificationUnsubscribeCode() == $code) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * @param Consultation $consultation
     * @param string $subject
     * @param string $text
     */
    public function notificationEmail(Consultation $consultation, $subject, $text)
    {
        if ($this->email == '' || !$this->emailConfirmed) {
            return;
        }
        $code         = $this->getNotificationUnsubscribeCode();
        $blacklistUrl = UrlHelper::createUrl(['user/emailblacklist', 'code' => $code]);
        $blacklistUrl = UrlHelper::absolutizeLink($blacklistUrl);
        $gruss        = str_replace('%NAME%', $this->name, "Hallo %NAME%,\n\n");
        $sig          = "\n\nLiebe Grüße,\n   Das Antragsgrün-Team\n\n--\n\n" .
            "Falls du diese Benachrichtigung abbestellen willst, kannst du das hier tun:\n" . $blacklistUrl;
        $text         = $gruss . $text . $sig;
        $type         = EMailLog::TYPE_MOTION_NOTIFICATION_USER;
        \app\components\mail\Tools::sendWithLog($type, $consultation->site, $this->email, $this->id, $subject, $text);
    }

    /**
     * @param Motion $motion
     */
    public function notifyMotion(Motion $motion)
    {
        $subject = "[Antragsgrün] Neuer Antrag: " . $motion->getTitleWithPrefix();
        $link    = UrlHelper::createUrl(['motion/view', 'motionId' => $motion->id]);
        $link    = UrlHelper::absolutizeLink($link);
        $text    = "Es wurde ein neuer Antrag eingereicht:\nAnlass: " . $motion->consultation->title .
            "\nName: " . $motion->getTitleWithPrefix() . "\nLink: " . $link;
        $this->notificationEmail($motion->consultation, $subject, $text);
    }

    /**
     * @param Amendment $amendment
     */
    public function notifyAmendment(Amendment $amendment)
    {
        $subject  = "[Antragsgrün] Neuer Änderungsantrag zu " . $amendment->motion->getTitleWithPrefix();
        $motionId = $amendment->motion->id;
        $link     = UrlHelper::createUrl(['amendment/view', 'amendmentId' => $amendment->id, 'motionId' => $motionId]);
        $link     = UrlHelper::absolutizeLink($link);
        $link     = \Yii::$app->request->baseUrl . $link;
        $text     = "Es wurde ein neuer Änderungsantrag eingereicht:\nAnlass: " .
            $amendment->motion->consultation->title . "\nAntrag: " . $amendment->motion->getTitleWithPrefix() .
            "\nLink: " . $link;
        $this->notificationEmail($amendment->motion->consultation, $subject, $text);
    }

    /**
     * @param IComment $comment
     */
    public function notifyComment(IComment $comment)
    {
        $subject = "[Antragsgrün] Neuer Kommentar zu: " . $comment->getMotionTitle();
        $text    = "Es wurde ein neuer Kommentar zu " . $comment->getMotionTitle() . " geschrieben:\n" .
            UrlHelper::absolutizeLink($comment->getLink());
        $this->notificationEmail($comment->getConsultation(), $subject, $text);
    }

    /**
     * @param Consultation|null $consultation
     * @param int $privilege [not used yet; forward-compatibility]
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasPrivilege($consultation, $privilege)
    {
        if (!$consultation) {
            return false;
        }

        /** @var AntragsgruenApp $params */
        $params = \yii::$app->params;
        if (in_array($this->id, $params->adminUserIds)) {
            return true;
        }
        // @Respect privilege table
        foreach ($consultation->site->admins as $admin) {
            if ($admin->id == $this->id) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getAuthName()
    {
        $authparts = explode(':', $this->auth);
        switch ($authparts[0]) {
            case 'email':
                return 'E-Mail: ' . $authparts[1];
            case 'openid':
                if ($this->isWurzelwerkUser()) {
                    return 'Wurzelwerk: ' . $this->getWurzelwerkName();
                } else {
                    return $this->auth;
                }
                break;
            default:
                return $this->auth;
        }
    }

    /**
     * @return int
     */
    public function getAuthType()
    {
        if ($this->isWurzelwerkUser()) {
            return \app\models\settings\Site::LOGIN_WURZELWERK;
        }
        $authparts = explode(':', $this->auth);
        switch ($authparts[0]) {
            case 'email':
                return \app\models\settings\Site::LOGIN_STD;
            default:
                return \app\models\settings\Site::LOGIN_EXTERNAL;
        }
    }

    /**
     */
    public function sendRecoveryMail()
    {
        if ($this->recoveryAt) {
            $recTs = Tools::dateSql2timestamp($this->recoveryAt);
            if (time() - $recTs < 24 * 3600) {
                $msg = 'Es wurde bereits eine Wiederherstellungs-E-Mail in den letzten 24 Stunden verschickt.';
                throw new FormError($msg);
            }
        }

        $recoveryToken       = rand(1000000, 9999999);
        $this->recoveryAt    = date('Y-m-d H:i:s');
        $this->recoveryToken = password_hash($recoveryToken, PASSWORD_DEFAULT);
        $this->save();

        $type     = EMailLog::TYPE_PASSWORD_RECOVERY;
        $subject  = 'Antragsgrün: Passwort-Wiederherstellung';
        $url      = UrlHelper::createUrl(['user/recovery', 'email' => $this->email, 'code' => $recoveryToken]);
        $url      = UrlHelper::absolutizeLink($url);
        $text     = "Hallo!\n\nDu hast eine Passwort-Wiederherstellung angefordert. " .
            "Um diese durchzuführen, Rufe bitte folgenden Link auf und gib dort das neue Passwort ein:\n\n%URL%\n\n" .
            "Oder gib in dem Wiederherstellungs-Formular folgenden Code ein: %CODE%";
        $replaces = ['%URL%' => $url, '%CODE%' => $recoveryToken];
        \app\components\mail\Tools::sendWithLog($type, null, $this->email, $this->id, $subject, $text, $replaces);
    }

    /**
     * @param string $token
     * @return bool
     * @throws FormError
     */
    public function checkRecoveryToken($token)
    {
        if ($this->recoveryAt) {
            $recTs = Tools::dateSql2timestamp($this->recoveryAt);
        } else {
            $recTs = 0;
        }
        if (time() - $recTs > 24 * 3600) {
            $msg = 'Es wurde kein Wiederherstellungs-Antrag innerhalb der letzten 24 Stunden gestellt.';
            throw new FormError($msg);
        }
        if (!password_verify($token, $this->recoveryToken)) {
            throw new FormError('Der angegebene Wiederherstellungs-Code stimmt leider nicht.');
        }
        return true;
    }


    /**
     */
    public function deleteAccount()
    {
        $this->name           = '';
        $this->email          = '';
        $this->emailConfirmed = 0;
        $this->auth           = null;
        $this->status         = static::STATUS_DELETED;
        $this->pwdEnc         = null;
        $this->authKey        = '';
        $this->recoveryToken  = null;
        $this->recoveryAt     = null;
        $this->save(false);
    }
}
