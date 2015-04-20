<?php

namespace app\models\db;

use app\components\PasswordFunctions;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;
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
 * @property null|int $siteNamespaceId
 *
 * @property null|Site $siteNamespace
 * @property null|AmendmentComment[] $amendmentComments
 * @property null|AmendmentSupporter[] $amendmentSupports
 * @property null|MotionComment[] $motionComments
 * @property null|MotionSupporter[] $motionSupports
 * @property Site[] $adminSites
 * @property Consultation[] $adminConsultations
 * @property ConsultationSubscription[] $subscribedConsultations
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


    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            1  => "Nicht bestätigt",
            0  => "Bestätigt",
            -1 => "Gelöscht",
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
    public function getSiteNamespace()
    {
        return $this->hasOne(Site::className(), ['id' => 'siteNamespaceId']);
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
    public function getAdminSites()
    {
        return $this->hasMany(Site::className(), ['id' => 'siteId'])->viaTable('siteAdmin', ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdminConsultations()
    {
        return $this->hasMany(Consultation::className(), ['id' => 'consultationId'])
            ->viaTable('consultationAdmin', ['userId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscribedConsultations()
    {
        return $this->hasMany(ConsultationSubscription::className(), ['id' => 'userId']);
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
     * @return string
     */
    public static function createPassword()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max   = strlen($chars) - 1;
        $pass  = "";
        for ($i = 0; $i < 8; $i++) {
            $pass .= $chars[rand(0, $max)];
        }
        return $pass;
    }

    /**
     * @param string $date
     * @return string
     */
    public function createEmailConfirmationCode($date = "")
    {
        if (YII_ENV == 'test') {
            return 'testCode';
        }

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if ($date == "") {
            $date = date("Ymd");
        }
        $code = $this->id . "-" . substr(md5($this->id . $date . $params->randomSeed), 0, 8);
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
        if ($code == $this->createEmailConfirmationCode(date("Ymd", time() - 24 * 3600))) {
            return true;
        }
        if ($code == $this->createEmailConfirmationCode(date("Ymd", time() - 2 * 24 * 3600))) {
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
        return PasswordFunctions::validatePassword($password, $this->pwdEnc);
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
        $query->where('motion.consultationId = ' . IntVal($consultation->id));
        $query->where('motionSupporter.userId = ' . IntVal($this->id));
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
        $query->where('amendment.status != ' . IntVal(Motion::STATUS_DELETED));
        $query->where('motion.consultationId = ' . IntVal($consultation->id));
        $query->where('amendmentSupporter.userId = ' . IntVal($this->id));
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

        $code = $this->id . "-" . substr(md5($this->id . "abmelden" . $params->randomSeed), 0, 8);
        return $code;
    }

    /**
     * @param Consultation $consultation
     * @param string $subject
     * @param string $text
     */
    public function notificationEmail(Consultation $consultation, $subject, $text)
    {
        if ($this->email == "" || !$this->emailConfirmed) {
            return;
        }
        $code           = $this->getNotificationUnsubscribeCode();
        $unsubscribeUrl = UrlHelper::createUrl(['user/unsubscribe', 'code' => $code]);
        $unsubscribeUrl = \Yii::$app->request->absoluteUrl . $unsubscribeUrl;
        $gruss          = "Hallo " . $this->name . ",\n\n";
        $from_name      = $consultation->site->getBehaviorClass()->getMailFromName();
        $sig            = "\n\nLiebe Grüße,\n   Das Antragsgrün-Team\n\n--\n\n" .
            "Falls du diese Benachrichtigung abbestellen willst, kannst du das hier tun:\n" . $unsubscribeUrl;
        $text           = $gruss . $text . $sig;
        $type           = EmailLog::TYPE_MOTION_NOTIFICATION_USER;
        Tools::sendMailLog($type, $this->email, $this->id, $subject, $text, $from_name);
    }

    /**
     * @param Motion $motion
     */
    public function notifyMotion(Motion $motion)
    {
        $subject = "[Antragsgrün] Neuer Antrag: " . $motion->getTitleWithPrefix();
        $link    = UrlHelper::createUrl(['motion/view', 'motionId' => $motion->id]);
        $link    = \Yii::$app->request->baseUrl . $link;
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
            $comment->getLink(true);
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
        foreach ($consultation->admins as $admin) {
            if ($admin->id == $this->id) {
                return true;
            }
        }
        foreach ($consultation->site->admins as $admin) {
            if ($admin->id == $this->id) {
                return true;
            }
        }
        return false;
    }
}
