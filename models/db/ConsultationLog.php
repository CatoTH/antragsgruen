<?php

namespace app\models\db;

use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * @property int $id
 * @property int $userId
 * @property int $consultationId
 * @property int $actionType
 * @property int $actionReferenceId
 * @property string $actionTime
 *
 * @property Consultation $consultation
 * @property User $user
 */
class ConsultationLog extends ActiveRecord
{
    const MOTION_PUBLISH           = 0;
    const MOTION_WITHDRAW          = 1;
    const MOTION_DELETE            = 2;
    const MOTION_SCREEN            = 3;
    const MOTION_UNSCREEN          = 4;
    const MOTION_COMMENT           = 5;
    const MOTION_COMMENT_DELETE    = 6;
    const MOTION_COMMENT_SCREEN    = 7;
    const MOTION_LIKE              = 8;
    const MOTION_UNLIKE            = 9;
    const MOTION_DISLIKE           = 10;
    const MOTION_CHANGE            = 12;
    const MOTION_SUPPORT           = 24;
    const MOTION_SUPPORT_FINISH    = 26;
    const AMENDMENT_PUBLISH        = 13;
    const AMENDMENT_WITHDRAW       = 14;
    const AMENDMENT_DELETE         = 15;
    const AMENDMENT_SCREEN         = 16;
    const AMENDMENT_UNSCREEN       = 17;
    const AMENDMENT_COMMENT        = 18;
    const AMENDMENT_COMMENT_DELETE = 19;
    const AMENDMENT_COMMENT_SCREEN = 20;
    const AMENDMENT_LIKE           = 21;
    const AMENDMENT_UNLIKE         = 22;
    const AMENDMENT_DISLIKE        = 23;
    const AMENDMENT_CHANGE         = 25;

    public static $MOTION_ACTION_TYPES    = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 24, 26];
    public static $AMENDMENT_ACTION_TYPES = [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 25];

    /** @var null|Motion */
    private $motion = null;
    /** @var null|Amendment */
    private $amendment = null;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationLog';
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
            [['consultationId', 'actionTime'], 'required'],
            [['id', 'consultationId', 'userId', 'actionType', 'actionReferenceId'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param int|null $userId
     * @param int $type
     * @param int $typeRefId
     */
    public static function log(Consultation $consultation, $userId, $type, $typeRefId)
    {
        $log                    = new static();
        $log->userId            = $userId;
        $log->consultationId    = $consultation->id;
        $log->actionType        = $type;
        $log->actionReferenceId = $typeRefId;
        $log->actionTime        = date('Y-m-d H:i:s');
        $log->save();
    }

    /**
     * @param Consultation $consultation
     * @param int $type
     * @param int $typeRefId
     */
    public static function logCurrUser(Consultation $consultation, $type, $typeRefId)
    {
        $user = User::getCurrentUser();

        $log                    = new static();
        $log->userId            = ($user ? $user->id : null);
        $log->consultationId    = $consultation->id;
        $log->actionType        = $type;
        $log->actionReferenceId = $typeRefId;
        $log->actionTime        = date('Y-m-d H:i:s');
        $log->save();
    }

    /**
     * @param string $str
     * @param int $amendmentId
     */
    private function formatLogEntryAmendment($str, $amendmentId)
    {
        if (!$this->amendment || $this->amendment->id != $amendmentId) {
            $this->amendment = Amendment::findOne($amendmentId);
        }
        if ($this->amendment) {
            return str_replace('###AMENDMENT###', Html::encode($this->amendment->getTitle()), $str);
        } else {
            $deletedStr = '<span class="deleted">' . \Yii::t('structure', 'activity_deleted') . '</span>';
            return str_replace('###AMENDMENT###', $deletedStr, $str);
        }
    }

    /**
     * @param string $str
     * @param int $motionId
     */
    private function formatLogEntryMotion($str, $motionId)
    {
        if (!$this->motion || $this->motion->id != $motionId) {
            $this->motion = Motion::findOne($motionId);
        }
        if ($this->motion) {
            return str_replace('###MOTION###', Html::encode($this->motion->getTitleWithPrefix()), $str);
        } else {
            $deletedStr = '<span class="deleted">' . \Yii::t('structure', 'activity_deleted') . '</span>';
            return str_replace('###MOTION###', $deletedStr, $str);
        }
    }

    /**
     * @param string $str
     * @param string $fallback
     * @return string
     */
    private function formatLogEntryUser($str, $fallback)
    {
        if ($this->user) {
            return str_replace('###USER###', Html::encode($this->user->name), $str);
        } else {
            return str_replace('###USER###', Html::encode($fallback), $str);
        }
    }

    /**
     * @return string
     */
    public function formatLogEntry()
    {
        switch ($this->actionType) {
            case static::MOTION_PUBLISH:
                $str      = \Yii::t('structure', 'activity_MOTION_PUBLISH');
                $str      = $this->formatLogEntryMotion($str, $this->actionReferenceId);
                $fallback = ($this->motion ? $this->motion->getInitiatorsStr() : '-');
                $str      = $this->formatLogEntryUser($str, $fallback);
                return $str;
            case static::AMENDMENT_PUBLISH:
                $str      = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH');
                $str      = $this->formatLogEntryAmendment($str, $this->actionReferenceId);
                $fallback = ($this->amendment ? $this->amendment->getInitiatorsStr() : '-');
                $str      = $this->formatLogEntryUser($str, $fallback);
                return $str;
            default:
                return $this->actionType;
        }
    }
}
