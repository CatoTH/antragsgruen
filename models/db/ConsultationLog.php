<?php

namespace app\models\db;

use app\components\{Tools, UrlHelper};
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * @property int $id
 * @property int $userId
 * @property int $consultationId
 * @property int $actionType
 * @property int $actionReferenceId
 * @property string $actionTime
 * @property string|null $data
 *
 * @property Consultation $consultation
 * @property User|null $user
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
    const MOTION_SET_PROPOSAL        = 35;
    const MOTION_NOTIFY_PROPOSAL     = 36;
    const MOTION_ACCEPT_PROPOSAL     = 31;
    const MOTION_PUBLISH_PROPOSAL    = 30;
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
    const AMENDMENT_SUPPORT          = 33;
    const AMENDMENT_SUPPORT_FINISH   = 34;
    const AMENDMENT_SET_PROPOSAL     = 37;
    const AMENDMENT_NOTIFY_PROPOSAL  = 38;
    const AMENDMENT_ACCEPT_PROPOSAL  = 32;
    const AMENDMENT_PUBLISH_PROPOSAL = 29;

    public static $MOTION_ACTION_TYPES    = [
        self::MOTION_PUBLISH,
        self::MOTION_WITHDRAW,
        self::MOTION_DELETE,
        self::MOTION_DELETE_PUBLISHED,
        self::MOTION_SCREEN,
        self::MOTION_UNSCREEN,
        self::MOTION_COMMENT,
        self::MOTION_COMMENT_DELETE,
        self::MOTION_COMMENT_SCREEN,
        self::MOTION_LIKE,
        self::MOTION_UNLIKE,
        self::MOTION_DISLIKE,
        self::MOTION_CHANGE,
        self::MOTION_SUPPORT,
        self::MOTION_SUPPORT_FINISH,
        self::MOTION_SET_PROPOSAL,
        self::MOTION_NOTIFY_PROPOSAL,
        self::MOTION_ACCEPT_PROPOSAL,
        self::MOTION_PUBLISH_PROPOSAL,
    ];

    public static $AMENDMENT_ACTION_TYPES = [
        self::AMENDMENT_PUBLISH,
        self::AMENDMENT_WITHDRAW,
        self::AMENDMENT_DELETE,
        self::AMENDMENT_DELETE_PUBLISHED,
        self::AMENDMENT_SCREEN,
        self::AMENDMENT_UNSCREEN,
        self::AMENDMENT_COMMENT,
        self::AMENDMENT_COMMENT_DELETE,
        self::AMENDMENT_COMMENT_SCREEN,
        self::AMENDMENT_LIKE,
        self::AMENDMENT_UNLIKE,
        self::AMENDMENT_DISLIKE,
        self::AMENDMENT_CHANGE,
        self::AMENDMENT_SUPPORT,
        self::AMENDMENT_SUPPORT_FINISH,
        self::AMENDMENT_SET_PROPOSAL,
        self::AMENDMENT_NOTIFY_PROPOSAL,
        self::AMENDMENT_ACCEPT_PROPOSAL,
        self::AMENDMENT_PUBLISH_PROPOSAL,
    ];

    public static $USER_INVISIBLE_EVENTS = [
        self::MOTION_COMMENT_DELETE,
        self::AMENDMENT_COMMENT_DELETE,
        self::MOTION_DELETE,
        self::AMENDMENT_DELETE,
        self::MOTION_SUPPORT,
        self::MOTION_SUPPORT_FINISH,
        self::AMENDMENT_SUPPORT,
        self::AMENDMENT_SUPPORT_FINISH,
        self::MOTION_LIKE,
        self::MOTION_UNLIKE,
        self::MOTION_DISLIKE,
        self::AMENDMENT_LIKE,
        self::AMENDMENT_UNLIKE,
        self::AMENDMENT_DISLIKE,
        self::MOTION_SET_PROPOSAL,
        self::MOTION_NOTIFY_PROPOSAL,
        self::MOTION_ACCEPT_PROPOSAL,
        self::MOTION_PUBLISH_PROPOSAL,
        self::AMENDMENT_SET_PROPOSAL,
        self::AMENDMENT_NOTIFY_PROPOSAL,
        self::AMENDMENT_ACCEPT_PROPOSAL,
        self::AMENDMENT_PUBLISH_PROPOSAL,
    ];

    /** @var null|Motion */
    private $motion = null;
    /** @var null|int */
    private $motionId = null;
    /** @var null|Amendment */
    private $amendment = null;
    /** @var null|int */
    private $amendmentId = null;
    /** @var null|MotionComment */
    private $motionComment = null;
    /** @var null|AmendmentComment */
    private $amendmentComment = null;

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

    public static function getLogForConsultation(int $consultationId, bool $showUserInvisible): array
    {
        $query = static::find();
        $query->where(['consultationId' => $consultationId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', static::$USER_INVISIBLE_EVENTS]);
        }
        $query->orderBy('actionTime DESC');
        return $query->all();
    }

    public static function getLogForMotion(int $consultationId, int $motionId, bool $showUserInvisible): array
    {
        $query = static::find();
        $query->where(['consultationId' => $consultationId]);
        $query->andWhere(['actionReferenceId' => $motionId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', static::$USER_INVISIBLE_EVENTS]);
        }
        $query->andWhere(['IN', 'actionType', static::$MOTION_ACTION_TYPES]);
        $query->orderBy('actionTime DESC');
        return $query->all();
    }

    public static function getLogForAmendment(int $consultationId, int $amendmentId, bool $showUserInvisible): array
    {
        $query = static::find();
        $query->where(['consultationId' => $consultationId]);
        $query->andWhere(['actionReferenceId' => $amendmentId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', static::$USER_INVISIBLE_EVENTS]);
        }
        $query->andWhere(['IN', 'actionType', static::$AMENDMENT_ACTION_TYPES]);
        $query->orderBy('actionTime DESC');
        return $query->all();
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

    public static function log(Consultation $consultation, ?int $userId, int $type, int $typeRefId, ?array $data = null): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if ($val === null) {
                    unset($data[$key]);
                }
            }
        }
        $log = new ConsultationLog();
        $log->userId = $userId;
        $log->consultationId = $consultation->id;
        $log->actionType = $type;
        $log->actionReferenceId = $typeRefId;
        $log->actionTime = date('Y-m-d H:i:s');
        $log->data = ($data ? json_encode($data) : null);
        $log->save();
    }

    public static function logCurrUser(Consultation $consultation, int $type, int $typeRefId, ?array $data = null): void
    {
        $user = User::getCurrentUser();
        static::log($consultation, ($user ? $user->id : null), $type, $typeRefId, $data);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function getTimeAgoFormatted(): string
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

    private function setMotionData(): void
    {
        if ($this->motion) {
            return;
        }

        if (in_array($this->actionType, [static::MOTION_COMMENT, static::MOTION_COMMENT_SCREEN])) {
            $this->motionComment = MotionComment::findOne($this->actionReferenceId);
            if ($this->motionComment) {
                $this->motion = $this->motionComment->getIMotion();
                $this->motionId = $this->motionComment->motionId;
            }

        } elseif (in_array($this->actionType, static::$MOTION_ACTION_TYPES)) {
            $this->motionId = $this->actionReferenceId;
            $this->motion   = Motion::findOne($this->actionReferenceId);

        } elseif (in_array($this->actionType, [static::AMENDMENT_COMMENT, static::AMENDMENT_COMMENT_SCREEN])) {
            $this->amendmentComment = AmendmentComment::findOne($this->actionReferenceId);
            if ($this->amendmentComment) {
                $this->amendment = $this->amendmentComment->getIMotion();
                $this->amendmentId = $this->amendmentComment->amendmentId;
                if ($this->amendment) {
                    $this->motion = $this->amendment->getMyMotion();
                    $this->motionId = $this->amendment->motionId;
                }
            }

        } elseif (in_array($this->actionType, static::$AMENDMENT_ACTION_TYPES)) {
            $this->amendmentId = $this->actionReferenceId;
            $this->amendment = Amendment::findOne($this->actionReferenceId);
            if ($this->amendment) {
                $this->motionId = $this->amendment->motionId;
                $this->motion = $this->amendment->getMyMotion();
            } else {
                $this->motion = static::amendmentId2Motion($this->actionReferenceId);
                if ($this->motion) {
                    $this->motionId = $this->motion->id;
                }
            }
        }
    }

    public function getLink(): ?string
    {
        $this->setMotionData();
        if ($this->motion && !$this->motion->isVisible()) {
            return null;
        }
        if ($this->amendment && !$this->amendment->isVisible()) {
            return null;
        }

        if (in_array($this->actionType, [static::MOTION_COMMENT, static::MOTION_COMMENT_SCREEN])) {
            if ($this->motionComment && $this->motionComment->getIMotion()) {
                return UrlHelper::createMotionCommentUrl($this->motionComment);
            } else {
                return null;
            }

        } elseif (in_array($this->actionType, static::$MOTION_ACTION_TYPES)) {
            return ($this->motion ? UrlHelper::createMotionUrl($this->motion) : null);

        } elseif (in_array($this->actionType, [static::AMENDMENT_COMMENT, static::AMENDMENT_COMMENT_SCREEN])) {
            if ($this->amendmentComment && $this->amendmentComment->getIMotion() &&
                $this->amendmentComment->getIMotion()->getMyMotion()) {
                return UrlHelper::createAmendmentCommentUrl($this->amendmentComment);
            } else {
                return null;
            }

        } elseif (in_array($this->actionType, static::$AMENDMENT_ACTION_TYPES)) {
            return ($this->amendment && $this->amendment->getMyMotion() ? UrlHelper::createAmendmentUrl($this->amendment) : null);
        }

        return null;
    }

    public function getMotion(): ?Motion
    {
        $this->setMotionData();
        return $this->motion;
    }

    private function formatLogEntryUser(string $str, string $fallback): string
    {
        if ($fallback === '') {
            $fallback = \Yii::t('structure', 'activity_someone');
        }
        if ($this->user) {
            if ($this->user->name) {
                $name = $this->user->name;
            } elseif ($this->user->isGruenesNetzUser()) {
                $name = $this->user->getGruenesNetzName();
            } else {
                $name = $fallback;
            }
            return str_replace('###USER###', Html::encode($name), $str);
        } else {
            return str_replace('###USER###', Html::encode($fallback), $str);
        }
    }

    private function formatLogEntryAmendment(string $str): string
    {
        $deleted = '<span class="deleted">' . \Yii::t('structure', 'activity_deleted') . '</span>';
        if ($this->amendment) {
            $str = str_replace('###AMENDMENT###', $this->amendment->titlePrefix, $str);
        } elseif ($this->amendmentId) {
            $prefix = static::amendmentId2Prefix($this->actionReferenceId) . ' ' . $deleted;
            $str    = str_replace('###AMENDMENT###', $prefix, $str);
        } else {
            $str = str_replace('###AMENDMENT###', $deleted, $str);
        }
        return $str;
    }

    private static function amendmentId2Prefix(int $amendmentId): ?string
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

    private static function amendmentId2Motion(int $amendmentId): ?Motion
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        $row = (new \yii\db\Query())
            ->select(['motionId'])
            ->from($app->tablePrefix . 'amendment')
            ->where(['id' => IntVal($amendmentId)])
            ->one();
        if (!$row) {
            return null;
        }
        return Motion::findOne($row['motionId']);
    }

    private static function motionId2Prefix(int $motionId): ?string
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

    public function formatLogEntry(bool $showInvisible = false): ?string
    {
        $this->setMotionData();
        if ($this->motion && !$showInvisible && !$this->motion->isVisible()) {
            return null;
        }
        if ($this->amendment && !$showInvisible && !$this->amendment->isVisible()) {
            return null;
        }
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
            case static::MOTION_CHANGE:
                $str = \Yii::t('structure', 'activity_MOTION_CHANGE');
                $str = $this->formatLogEntryUser($str, '');
                $prefix = static::motionId2Prefix($this->actionReferenceId);
                $str    = str_replace('###MOTION###', $prefix, $str);
                return $str;
            case static::MOTION_WITHDRAW:
                $str = \Yii::t('structure', 'activity_MOTION_WITHDRAW');
                $str = $this->formatLogEntryUser($str, '');
                return $str;
            case static::MOTION_COMMENT:
            case static::MOTION_COMMENT_SCREEN:
                if ($this->motionComment) {
                    $abstract = $this->motionComment->getTextAbstract(190);
                    $str      = $this->formatLogEntryUser('###USER###', $this->motionComment->name) . ': ';
                    $str      .= '<span class="quote">' . Html::encode($abstract) . '</span>';
                    return '<blockquote>' . $str . '</blockquote>';
                } else {
                    return null;
                }
            case static::MOTION_SCREEN:
                $str = \Yii::t('structure', 'activity_MOTION_SCREEN');
                return $str;
            case static::MOTION_UNSCREEN:
                return null;
            case static::MOTION_SUPPORT:
                $str = \Yii::t('structure', 'activity_MOTION_SUPPORT');
                $str = $this->formatLogEntryUser($str, '');
                return $str;
            case static::MOTION_PUBLISH_PROPOSAL:
                $str = \Yii::t('structure', 'activity_MOTION_PUBLISH_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                return $str;
            case static::MOTION_SET_PROPOSAL:
                $str = \Yii::t('structure', 'activity_MOTION_SET_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                // @TODO More detailed output
                return $str;
            case static::AMENDMENT_PUBLISH:
                $str = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH');
                $str = $this->formatLogEntryAmendment($str);
                $str = $this->formatLogEntryUser($str, ($this->amendment ? $this->amendment->getInitiatorsStr() : ''));
                return $str;
            case static::AMENDMENT_DELETE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_DELETE');
                $str = $this->formatLogEntryAmendment($str);
                $str = $this->formatLogEntryUser($str, '');
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
            case static::AMENDMENT_WITHDRAW:
                $str = \Yii::t('structure', 'activity_AMENDMENT_WITHDRAW');
                $str = $this->formatLogEntryAmendment($str);
                return $str;
            case static::AMENDMENT_COMMENT:
            case static::AMENDMENT_COMMENT_SCREEN:
                if ($this->amendmentComment) {
                    $abstract = $this->amendmentComment->getTextAbstract(190);
                    $str      = $this->formatLogEntryUser('###USER###', $this->amendmentComment->name) . ': ';
                    $str      .= '<span class="quote">' . Html::encode($abstract) . '</span>';
                    return '<blockquote>' . $str . '</blockquote>';
                } else {
                    return null;
                }
            case static::AMENDMENT_SCREEN:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SCREEN');
                $str = $this->formatLogEntryAmendment($str);
                return $str;
            case static::AMENDMENT_UNSCREEN:
                return null;
            case static::AMENDMENT_SUPPORT:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SUPPORT');
                $str = $this->formatLogEntryAmendment($str);
                $str = $this->formatLogEntryUser($str, '');
                return $str;
            case static::AMENDMENT_PUBLISH_PROPOSAL:
                $str = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                $str = $this->formatLogEntryAmendment($str);
                return $str;
            case static::AMENDMENT_SET_PROPOSAL:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SET_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                $str = $this->formatLogEntryAmendment($str);
                // @TODO More detailed output
                return $str;
            default:
                return (string)$this->actionType;
        }
    }
}
