<?php

namespace app\models\db;

use app\components\Tools;
use app\components\UrlHelper;
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
    const MOTION_PUBLISH             = 0;
    const MOTION_WITHDRAW            = 1;
    const MOTION_DELETE              = 2;
    const MOTION_DELETE_PUBLISHED    = 27;
    const MOTION_SCREEN              = 3;
    const MOTION_UNSCREEN            = 4;
    const MOTION_COMMENT             = 5;
    const MOTION_COMMENT_DELETE      = 6;
    const MOTION_COMMENT_SCREEN      = 7;
    const MOTION_LIKE                = 8;
    const MOTION_UNLIKE              = 9;
    const MOTION_DISLIKE             = 10;
    const MOTION_CHANGE              = 12;
    const MOTION_SUPPORT             = 24;
    const MOTION_SUPPORT_FINISH      = 26;
    const AMENDMENT_PUBLISH          = 13;
    const AMENDMENT_WITHDRAW         = 14;
    const AMENDMENT_DELETE           = 15;
    const AMENDMENT_DELETE_PUBLISHED = 28;
    const AMENDMENT_SCREEN           = 16;
    const AMENDMENT_UNSCREEN         = 17;
    const AMENDMENT_COMMENT          = 18;
    const AMENDMENT_COMMENT_DELETE   = 19;
    const AMENDMENT_COMMENT_SCREEN   = 20;
    const AMENDMENT_LIKE             = 21;
    const AMENDMENT_UNLIKE           = 22;
    const AMENDMENT_DISLIKE          = 23;
    const AMENDMENT_CHANGE           = 25;

    public static $MOTION_ACTION_TYPES    = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 24, 26, 27];
    public static $AMENDMENT_ACTION_TYPES = [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 25, 28];

    public static $USER_INVISIBLE_EVENTS = [15, 2];

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
     * @return string
     */
    public function getTimeAgoFormatted()
    {
        $time = time() - Tools::dateSql2timestamp($this->actionTime);
        if ($time < 60) {
            return $time . 's';
        } elseif ($time < 3600) {
            return floor($time / 60) . 'm';
        } elseif ($time < 3600 * 24) {
            return floor($time / 3600) . 'h';
        } else {
            return Tools::formatMysqlDate($this->actionTime);
        }
    }

    /**
     */
    private function setMotionData()
    {
        if ($this->motion) {
            return;
        }
        switch ($this->actionType) {
            case static::MOTION_PUBLISH:
            case static::MOTION_CHANGE:
            case static::MOTION_WITHDRAW:
            case static::MOTION_DELETE:
            case static::MOTION_DELETE_PUBLISHED:
            case static::MOTION_SCREEN:
            case static::MOTION_UNSCREEN:
            case static::MOTION_LIKE:
            case static::MOTION_UNLIKE:
            case static::MOTION_SUPPORT:
            case static::MOTION_SUPPORT_FINISH:
                $this->motion = Motion::findOne($this->actionReferenceId);
                break;
            case static::AMENDMENT_PUBLISH:
            case static::AMENDMENT_WITHDRAW:
            case static::AMENDMENT_DELETE:
            case static::AMENDMENT_DELETE_PUBLISHED:
            case static::AMENDMENT_SCREEN:
            case static::AMENDMENT_UNSCREEN:
            case static::AMENDMENT_LIKE:
            case static::AMENDMENT_UNLIKE:
            case static::AMENDMENT_DISLIKE:
            case static::AMENDMENT_CHANGE:
                $this->amendment = Amendment::findOne($this->actionReferenceId);
                if ($this->amendment) {
                    $this->motion = $this->amendment->getMyMotion();
                }
        }
    }

    /**
     * @return null|string
     */
    public function getLink()
    {
        $this->setMotionData();
        switch ($this->actionType) {
            case static::MOTION_PUBLISH:
            case static::MOTION_CHANGE:
            case static::MOTION_WITHDRAW:
            case static::MOTION_DELETE:
            case static::MOTION_DELETE_PUBLISHED:
            case static::MOTION_SCREEN:
            case static::MOTION_UNSCREEN:
            case static::MOTION_LIKE:
            case static::MOTION_UNLIKE:
            case static::MOTION_SUPPORT:
            case static::MOTION_SUPPORT_FINISH:
                if ($this->motion) {
                    return UrlHelper::createMotionUrl($this->motion);
                } else {
                    return null;
                }
                break;
            case static::AMENDMENT_PUBLISH:
            case static::AMENDMENT_WITHDRAW:
            case static::AMENDMENT_DELETE:
            case static::AMENDMENT_DELETE_PUBLISHED:
            case static::AMENDMENT_SCREEN:
            case static::AMENDMENT_UNSCREEN:
            case static::AMENDMENT_LIKE:
            case static::AMENDMENT_UNLIKE:
            case static::AMENDMENT_DISLIKE:
            case static::AMENDMENT_CHANGE:
                if ($this->amendment) {
                    return UrlHelper::createAmendmentUrl($this->amendment);
                } else {
                    return null;
                }
            default:
                return null;
        }
    }

    /**
     * @return Motion|null
     */
    public function getMotion()
    {
        $this->setMotionData();
        return $this->motion;
    }

    /**
     * @param string $str
     * @param string $fallback
     * @return string
     */
    private function formatLogEntryUser($str, $fallback)
    {
        if ($fallback == '') {
            $fallback = \Yii::t('structure', 'activity_someone');
        }
        if ($this->user) {
            if ($this->user->name) {
                $name = $this->user->name;
            } elseif ($this->user->isWurzelwerkUser()) {
                $name = $this->user->getWurzelwerkName();
            } else {
                $name = $fallback;
            }
            return str_replace('###USER###', Html::encode($name), $str);
        } else {
            return str_replace('###USER###', Html::encode($fallback), $str);
        }
    }

    /**
     * @param string $str
     * @return string
     */
    private function formatLogEntryAmendment($str)
    {
        if ($this->amendment) {
            $str = str_replace('###AMENDMENT###', $this->amendment->titlePrefix, $str);
        } else {
            $deleted = '<span class="deleted">' . \Yii::t('structure', 'activity_deleted') . '</span>';
            $str = str_replace('###AMENDMENT###', $deleted, $str);
        }
        return $str;
    }

    /**
     * @param int $amendmentId
     * @return string|null
     */
    private static function amendmentId2Prefix($amendmentId)
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        $row = (new \yii\db\Query())
            ->select(['titlePrefix'])
            ->from($app->tablePrefix . 'amendment')
            ->where(['id' => IntVal($amendmentId)])
            ->one();
        return ($row ? $row['titlePrefix'] : null);
    }

    /**
     * @param int $motionId
     * @return string|null
     */
    private static function motionId2Prefix($motionId)
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        $row = (new \yii\db\Query())
            ->select(['titlePrefix'])
            ->from($app->tablePrefix . 'motion')
            ->where(['id' => IntVal($motionId)])
            ->one();
        return ($row ? $row['titlePrefix'] : null);
    }

    /**
     * @return string
     */
    public function formatLogEntry()
    {
        $this->setMotionData();
        switch ($this->actionType) {
            case static::MOTION_PUBLISH:
                $str      = \Yii::t('structure', 'activity_MOTION_PUBLISH');
                $fallback = ($this->motion ? $this->motion->getInitiatorsStr() : '-');
                $str      = $this->formatLogEntryUser($str, $fallback);
                return $str;
            case static::MOTION_DELETE:
                $str    = \Yii::t('structure', 'activity_MOTION_DELETE');
                $prefix = static::motionId2Prefix($this->actionReferenceId);
                $str    = str_replace('###MOTION###', $prefix, $str);
                $str    = $this->formatLogEntryUser($str, '');
                return $str;
            case static::MOTION_DELETE_PUBLISHED:
                $str    = \Yii::t('structure', 'activity_MOTION_DELETE_PUBLISHED');
                $str    = $this->formatLogEntryUser($str, '');
                $prefix = static::motionId2Prefix($this->actionReferenceId);
                $str    = str_replace('###MOTION###', $prefix, $str);
                return $str;
            case static::AMENDMENT_PUBLISH:
                $str = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH');
                $str = $this->formatLogEntryAmendment($str);
                $str = $this->formatLogEntryUser($str, ($this->amendment ? $this->amendment->getInitiatorsStr() : ''));
                return $str;
            case static::AMENDMENT_DELETE:
                $str    = \Yii::t('structure', 'activity_AMENDMENT_DELETE');
                $prefix = static::amendmentId2Prefix($this->actionReferenceId);
                $str    = str_replace('###AMENDMENT###', $prefix, $str);
                $str    = $this->formatLogEntryUser($str, '');
                return $str;
            case static::AMENDMENT_DELETE_PUBLISHED:
                $str    = \Yii::t('structure', 'activity_AMENDMENT_DELETE_PUBLISHED');
                $str    = $this->formatLogEntryUser($str, '');
                $prefix = static::amendmentId2Prefix($this->actionReferenceId);
                $str    = str_replace('###AMENDMENT###', $prefix, $str);
                return $str;
            case static::AMENDMENT_CHANGE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_CHANGE');
                $str = $this->formatLogEntryUser($str, '');
                $str = $this->formatLogEntryAmendment($str);
                return $str;
            default:
                return $this->actionType;
        }
    }
}
