<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @package app\models
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int $email_confirmed
 * @property string $auth
 * @property string $date_creation
 * @property string $status
 * @property string $pwd_enc
 * @property string $auth_key
 * @property null|int $site_namespace_id
 *
 * @property null|Site $site_namespace
 * @property null|AmendmentComment[] $amendment_comments
 * @property null|AmendmentSupporter[] $amendment_supports
 * @property null|MotionComment[] $motion_comments
 * @property null|MotionSupporter[] $motion_supports
 * @property Site[] $admin_sites
 * @property Consultation[] $admin_consultations
 * @property ConsultationSubscription[] $subscribed_consultations
 */
class User extends ActiveRecord implements IdentityInterface
{

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
    public function getSite_namespace()
    {
        return $this->hasOne(Site::className(), ['id' => 'site_namespace_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion_comments()
    {
        return $this->hasMany(MotionComment::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion_supports()
    {
        return $this->hasMany(MotionSupporter::className(), ['motion_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment_comments()
    {
        return $this->hasMany(AmendmentComment::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment_supports()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmail_logs()
    {
        return $this->hasMany(EMailLog::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin_sites()
    {
        return $this->hasMany(Site::className(), ['id' => 'site_id'])->viaTable('site_admin', ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin_consultations()
    {
        return $this->hasMany(Consultation::className(), ['id' => 'consultation_id'])->viaTable('consultation_admin', ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscribed_consultations()
    {
        return $this->hasMany(ConsultationSubscription::className(), ['id' => 'user_id']);
    }


    /**
     * Finds an identity by the given ID.
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
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
        return $this->auth_key;
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
        return $this->auth_key == $authKey;
    }
}
