<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $userId
 * @property integer $paragraph
 * @property string $text
 * @property string $dateCreated
 * @property integer $status
 * @property User $user
 */
abstract class IComment extends ActiveRecord
{

    const STATUS_SCREENING = 1;
    const STATUS_VISIBLE   = 0;
    const STATUS_DELETED   = -1;

    public static function getStati()
    {
        return [
            static::STATUS_SCREENING => 'Nicht freigeschaltet',
            static::STATUS_VISIBLE => 'Sichtbar',
            static::STATUS_DELETED => 'Gelöscht',
        ];
    }


    /**
     * @return Consultation
     */
    abstract public function getConsultation();

    /**
     * @return string
     */
    abstract public function getMotionName();

    /**
     * @param bool $absolute
     * @return string
     */
    abstract public function getLink($absolute = false);

    /**
     * @param User $user
     * @return bool
     */
    public function canDelete($user)
    {
        if ($this->getConsultation()->isAdminCurUser()) {
            return true;
        }
        if (!is_null($this->user->auth) && $user->auth == $this->user->auth) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isVisibleCurrUser()
    {
        if ($this->status == static::STATUS_DELETED) {
            return false;
        }
        if ($this->status == static::STATUS_VISIBLE) {
            return true;
        }
        if ($this->getConsultation()->isAdminCurUser()) {
            return true;
        }

        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }
        return ($user->id == $this->userId);
    }
}
