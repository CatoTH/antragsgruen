<?php

namespace app\models\db;

use app\components\PasswordFunctions;
use app\models\AntragsgruenAppParams;
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
        /** @var AntragsgruenAppParams $params */
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
}
