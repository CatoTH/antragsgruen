<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\components\{Tools, UrlHelper};
use yii\db\{ActiveQuery, ActiveRecord};
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
    public const MOTION_PUBLISH             = 0;
    public const MOTION_WITHDRAW            = 1;
    public const MOTION_DELETE              = 2;
    public const MOTION_DELETE_PUBLISHED    = 27;
    public const MOTION_SCREEN              = 3;
    public const MOTION_UNSCREEN            = 4;
    public const MOTION_COMMENT             = 5;
    public const MOTION_COMMENT_DELETE      = 6;
    public const MOTION_COMMENT_SCREEN      = 7;
    public const MOTION_LIKE                = 8;
    public const MOTION_UNLIKE              = 9;
    public const MOTION_DISLIKE             = 10;
    public const MOTION_CHANGE              = 12;
    public const MOTION_SUPPORT             = 24;
    public const MOTION_SUPPORT_FINISH      = 26;
    public const MOTION_SET_PROPOSAL        = 35;
    public const MOTION_NOTIFY_PROPOSAL     = 36;
    public const MOTION_ACCEPT_PROPOSAL     = 31;
    public const MOTION_PUBLISH_PROPOSAL    = 30;
    public const MOTION_VOTE_ACCEPTED       = 39;
    public const MOTION_VOTE_REJECTED       = 40;
    public const AMENDMENT_PUBLISH          = 13;
    public const AMENDMENT_WITHDRAW         = 14;
    public const AMENDMENT_DELETE           = 15;
    public const AMENDMENT_DELETE_PUBLISHED = 28;
    public const AMENDMENT_SCREEN           = 16;
    public const AMENDMENT_UNSCREEN         = 17;
    public const AMENDMENT_COMMENT          = 18;
    public const AMENDMENT_COMMENT_DELETE   = 19;
    public const AMENDMENT_COMMENT_SCREEN   = 20;
    public const AMENDMENT_LIKE             = 21;
    public const AMENDMENT_UNLIKE           = 22;
    public const AMENDMENT_DISLIKE          = 23;
    public const AMENDMENT_CHANGE           = 25;
    public const AMENDMENT_SUPPORT          = 33;
    public const AMENDMENT_SUPPORT_FINISH   = 34;
    public const AMENDMENT_SET_PROPOSAL     = 37;
    public const AMENDMENT_NOTIFY_PROPOSAL  = 38;
    public const AMENDMENT_ACCEPT_PROPOSAL  = 32;
    public const AMENDMENT_PUBLISH_PROPOSAL = 29;
    public const AMENDMENT_VOTE_ACCEPTED    = 41;
    public const AMENDMENT_VOTE_REJECTED    = 42;
    public const VOTING_OPEN                = 43;
    public const VOTING_CLOSE               = 44;
    public const VOTING_DELETE              = 45;
    public const VOTING_QUESTION_ACCEPTED   = 46;
    public const VOTING_QUESTION_REJECTED   = 47;
    public const USER_ADD_TO_GROUP          = 48;
    public const USER_REMOVE_FROM_GROUP     = 49;

    private const MOTION_ACTION_TYPES    = [
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
        self::MOTION_VOTE_ACCEPTED,
        self::MOTION_VOTE_REJECTED,
    ];

    private const AMENDMENT_ACTION_TYPES = [
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
        self::AMENDMENT_VOTE_ACCEPTED,
        self::AMENDMENT_VOTE_REJECTED,
    ];

    private const VOTING_ACTION_TYPES = [
        self::VOTING_OPEN,
        self::VOTING_CLOSE,
        self::VOTING_DELETE,
    ];

    private const VOTING_QUESTION_ACTION_TYPES = [
        self::VOTING_QUESTION_REJECTED,
        self::VOTING_QUESTION_ACCEPTED,
    ];

    private const USER_ACTION_TYPES = [
        self::USER_ADD_TO_GROUP,
        self::USER_REMOVE_FROM_GROUP,
    ];

    private const USER_GROUP_ACTION_TYPES = [
        self::USER_ADD_TO_GROUP,
        self::USER_REMOVE_FROM_GROUP,
    ];

    private const USER_INVISIBLE_EVENTS = [
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
        self::VOTING_DELETE,
        self::USER_ADD_TO_GROUP,
        self::USER_REMOVE_FROM_GROUP,
    ];

    private ?Motion $motion = null;
    private ?Amendment $amendment = null;
    private ?int $amendmentId = null;
    private ?MotionComment $motionComment = null;
    private ?AmendmentComment $amendmentComment = null;
    private ?VotingBlock $votingBlock = null;
    private ?VotingQuestion $votingQuestion = null;
    private ?ConsultationUserGroup $userGroup = null;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationLog';
    }

    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    public static function getLogForConsultation(int $consultationId, bool $showUserInvisible): array
    {
        $query = self::find();
        $query->where(['consultationId' => $consultationId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', self::USER_INVISIBLE_EVENTS]);
        }
        $query->orderBy('actionTime DESC');
        return $query->all();
    }

    private static function getLogForActionTypes(int $consultationId, int $referenceId, bool $showUserInvisible, array $actionTypes): array
    {
        $query = self::find();
        $query->where(['consultationId' => $consultationId]);
        $query->andWhere(['actionReferenceId' => $referenceId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', self::USER_INVISIBLE_EVENTS]);
        }
        $query->andWhere(['IN', 'actionType', $actionTypes]);
        $query->orderBy('actionTime DESC');
        return $query->all();
    }

    private static function getLogForUser(int $consultationId, int $userId, bool $showUserInvisible, array $actionTypes): array
    {
        $query = self::find();
        $query->where(['consultationId' => $consultationId]);
        $query->andWhere(['userId' => $userId]);
        if (!$showUserInvisible) {
            $query->andWhere(['NOT IN', 'actionType', self::USER_INVISIBLE_EVENTS]);
        }
        $query->andWhere(['IN', 'actionType', $actionTypes]);
        $query->orderBy('actionTime DESC');
        return $query->all();
    }

    public static function getLogForMotion(int $consultationId, int $motionId, bool $showUserInvisible): array
    {
        return self::getLogForActionTypes($consultationId, $motionId, $showUserInvisible, self::MOTION_ACTION_TYPES);
    }

    public static function getLogForAmendment(int $consultationId, int $amendmentId, bool $showUserInvisible): array
    {
        return self::getLogForActionTypes($consultationId, $amendmentId, $showUserInvisible, self::AMENDMENT_ACTION_TYPES);
    }

    public static function getLogForUserId(int $consultationId, int $userId): array
    {
        return self::getLogForUser($consultationId, $userId, true, self::USER_ACTION_TYPES);
    }

    public static function getLogForUserGroupId(int $consultationId, int $userGroupId): array
    {
        return self::getLogForActionTypes($consultationId, $userGroupId, true, self::USER_GROUP_ACTION_TYPES);
    }

    public function rules(): array
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
        $log->data = ($data ? json_encode($data, JSON_THROW_ON_ERROR) : null);
        $log->save();
    }

    public static function logCurrUser(Consultation $consultation, int $type, int $typeRefId, ?array $data = null): void
    {
        $user = User::getCurrentUser();
        self::log($consultation, $user?->id, $type, $typeRefId, $data);
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

    private function setLogEntryData(): void
    {
        if ($this->motion) {
            return;
        }

        if (in_array($this->actionType, [self::MOTION_COMMENT, self::MOTION_COMMENT_SCREEN])) {
            $this->motionComment = MotionComment::findOne($this->actionReferenceId);
            if ($this->motionComment) {
                $this->motion = $this->motionComment->getIMotion();
            }
            if (!$this->motion || !$this->motion->getMyMotionType()->maySeeIComments()) {
                $this->motion = null;
                $this->motionComment = null;
            }

        } elseif (in_array($this->actionType, self::MOTION_ACTION_TYPES)) {
            $this->motion   = Motion::findOne($this->actionReferenceId);

        } elseif (in_array($this->actionType, [self::AMENDMENT_COMMENT, self::AMENDMENT_COMMENT_SCREEN])) {
            $this->amendmentComment = AmendmentComment::findOne($this->actionReferenceId);
            if ($this->amendmentComment) {
                $this->amendment = $this->amendmentComment->getIMotion();
                $this->amendmentId = $this->amendmentComment->amendmentId;
                if ($this->amendment) {
                    $this->motion = $this->amendment->getMyMotion();
                }
            }
            if (!$this->motion || !$this->motion->getMyMotionType()->maySeeIComments()) {
                $this->amendment = null;
                $this->amendmentId = null;
                $this->motion = null;
                $this->amendmentComment = null;
            }

        } elseif (in_array($this->actionType, self::AMENDMENT_ACTION_TYPES)) {
            $this->amendmentId = $this->actionReferenceId;
            $this->amendment = Amendment::findOne($this->actionReferenceId);
            if ($this->amendment) {
                $this->motion = $this->amendment->getMyMotion();
            } else {
                $this->motion = self::amendmentId2Motion($this->actionReferenceId);
            }
        } elseif (in_array($this->actionType, self::VOTING_ACTION_TYPES)) {
            $this->votingBlock = VotingBlock::findOne($this->actionReferenceId);
        } elseif (in_array($this->actionType, self::VOTING_QUESTION_ACTION_TYPES)) {
            $this->votingQuestion = VotingQuestion::findOne($this->actionReferenceId);
        } elseif (in_array($this->actionType, self::USER_GROUP_ACTION_TYPES)) {
            $this->userGroup = $this->consultation->getUserGroupById($this->actionReferenceId);
        }
    }

    public function getLink(): ?string
    {
        $this->setLogEntryData();
        if ($this->motion && !$this->motion->isVisible()) {
            return null;
        }
        if ($this->amendment && !$this->amendment->isVisible()) {
            return null;
        }

        if (in_array($this->actionType, [self::MOTION_COMMENT, self::MOTION_COMMENT_SCREEN])) {
            if ($this->motionComment && $this->motionComment->getIMotion()) {
                return UrlHelper::createMotionCommentUrl($this->motionComment);
            } else {
                return null;
            }

        } elseif (in_array($this->actionType, self::MOTION_ACTION_TYPES)) {
            return ($this->motion ? UrlHelper::createMotionUrl($this->motion) : null);

        } elseif (in_array($this->actionType, [self::AMENDMENT_COMMENT, self::AMENDMENT_COMMENT_SCREEN])) {
            if ($this->amendmentComment && $this->amendmentComment->getIMotion() &&
                $this->amendmentComment->getIMotion()->getMyMotion()) {
                return UrlHelper::createAmendmentCommentUrl($this->amendmentComment);
            } else {
                return null;
            }

        } elseif (in_array($this->actionType, self::AMENDMENT_ACTION_TYPES)) {
            return ($this->amendment && $this->amendment->getMyMotion() ? UrlHelper::createAmendmentUrl($this->amendment) : null);

        } elseif (in_array($this->actionType, self::VOTING_ACTION_TYPES)) {
            if ($this->votingBlock) {
                return $this->votingBlock->getUserLink();
            } else {
                return null;
            }

        } elseif (in_array($this->actionType, self::VOTING_QUESTION_ACTION_TYPES)) {
            if ($this->votingQuestion && $this->votingQuestion->votingBlock) {
                return $this->votingQuestion->votingBlock->getUserLink();
            } else {
                return null;
            }
        }

        return null;
    }

    public function getMotion(): ?Motion
    {
        $this->setLogEntryData();
        return $this->motion;
    }

    public function getVoting(): ?VotingBlock
    {
        $this->setLogEntryData();
        return $this->votingBlock;
    }

    public function getVotingQuestion(): ?VotingQuestion
    {
        $this->setLogEntryData();
        return $this->votingQuestion;
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

    private function formatLogEntryUserGroup(string $str): string
    {
        if ($this->user) {
            $user = $this->user->getAuthUsername();
        } else {
            $user = \Yii::t('structure', 'activity_someone');
        }
        if ($this->userGroup) {
            $group = $this->userGroup->getNormalizedTitle();
        } else {
            $group = '???';
        }
        return str_replace(['###USER###', '###GROUP###'], [Html::encode($user), Html::encode($group)], $str);
    }

    private function formatLogEntryAmendment(string $str): string
    {
        $deleted = '<span class="deleted">' . \Yii::t('structure', 'activity_deleted') . '</span>';
        if ($this->amendment) {
            $str = str_replace('###AMENDMENT###', $this->amendment->getFormattedTitlePrefix(), $str);
        } elseif ($this->amendmentId) {
            $prefix = self::amendmentId2Prefix($this->actionReferenceId) . ' ' . $deleted;
            $str  = str_replace('###AMENDMENT###', $prefix, $str);
        } else {
            $str = str_replace('###AMENDMENT###', $deleted, $str);
        }
        return $str;
    }

    private static function amendmentId2Prefix(int $amendmentId): ?string
    {
        $row = (new \yii\db\Query())
            ->select(['titlePrefix'])
            ->from(AntragsgruenApp::getInstance()->tablePrefix . 'amendment')
            ->where(['id' => IntVal($amendmentId)])
            ->one();
        /** @var array{titlePrefix: string|null}|null $row */
        return ($row ? $row['titlePrefix'] : null);
    }

    private static function amendmentId2Motion(int $amendmentId): ?Motion
    {
        $row = (new \yii\db\Query())
            ->select(['motionId'])
            ->from(AntragsgruenApp::getInstance()->tablePrefix . 'amendment')
            ->where(['id' => IntVal($amendmentId)])
            ->one();
        /** @var array{motionId: int}|null $row */
        if (!$row) {
            return null;
        }
        return Motion::findOne($row['motionId']);
    }

    private static function motionId2Prefix(int $motionId): ?string
    {
        $row = (new \yii\db\Query())
            ->select(['titlePrefix'])
            ->from(AntragsgruenApp::getInstance()->tablePrefix . 'motion')
            ->where(['id' => IntVal($motionId)])
            ->one();
        /** @var array{titlePrefix: string|null}|null $row */
        return ($row ? $row['titlePrefix'] : null);
    }

    public function formatLogEntry(bool $showInvisible = false): ?string
    {
        $this->setLogEntryData();
        if ($this->motion && !$showInvisible && !$this->motion->isVisible()) {
            return null;
        }
        if ($this->amendment && !$showInvisible && !$this->amendment->isVisible()) {
            return null;
        }
        switch ($this->actionType) {
            case self::MOTION_PUBLISH:
                $str      = \Yii::t('structure', 'activity_MOTION_PUBLISH');
                $fallback = ($this->motion ? $this->motion->getInitiatorsStr() : '-');
                return $this->formatLogEntryUser($str, $fallback);
            case self::MOTION_DELETE:
                $str    = \Yii::t('structure', 'activity_MOTION_DELETE');
                $prefix = self::motionId2Prefix($this->actionReferenceId);
                $str    = str_replace('###MOTION###', $prefix, $str);
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_DELETE_PUBLISHED:
                $str    = \Yii::t('structure', 'activity_MOTION_DELETE_PUBLISHED');
                $str    = $this->formatLogEntryUser($str, '');
                $prefix = self::motionId2Prefix($this->actionReferenceId);
                return str_replace('###MOTION###', $prefix, $str);
            case self::MOTION_CHANGE:
                $str = \Yii::t('structure', 'activity_MOTION_CHANGE');
                $str = $this->formatLogEntryUser($str, '');
                $prefix = self::motionId2Prefix($this->actionReferenceId);
                return str_replace('###MOTION###', $prefix, $str);
            case self::MOTION_WITHDRAW:
                $str = \Yii::t('structure', 'activity_MOTION_WITHDRAW');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_COMMENT:
            case self::MOTION_COMMENT_SCREEN:
                if ($this->motionComment) {
                    $abstract = $this->motionComment->getTextAbstract(190);
                    $str      = $this->formatLogEntryUser('###USER###', $this->motionComment->name) . ': ';
                    $str      .= '<span class="quote">' . Html::encode($abstract) . '</span>';
                    return '<blockquote>' . $str . '</blockquote>';
                } else {
                    return null;
                }
            case self::MOTION_SCREEN:
                return \Yii::t('structure', 'activity_MOTION_SCREEN');
            case self::MOTION_UNSCREEN:
                return null;
            case self::MOTION_SUPPORT:
                $str = \Yii::t('structure', 'activity_MOTION_SUPPORT');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_LIKE:
                $str = \Yii::t('structure', 'activity_MOTION_LIKE');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_UNLIKE:
                $str = \Yii::t('structure', 'activity_MOTION_UNLIKE');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_DISLIKE:
                $str = \Yii::t('structure', 'activity_MOTION_DISLIKE');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_SUPPORT_FINISH:
                $str = \Yii::t('structure', 'activity_MOTION_SUPPORT_FINISH');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_PUBLISH_PROPOSAL:
                $str = \Yii::t('structure', 'activity_MOTION_PUBLISH_PROPOSAL');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_SET_PROPOSAL:
                $str = \Yii::t('structure', 'activity_MOTION_SET_PROPOSAL');
                // @TODO More detailed output
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_VOTE_ACCEPTED:
                $str = \Yii::t('structure', 'activity_MOTION_VOTE_ACCEPTED');
                return $this->formatLogEntryUser($str, '');
            case self::MOTION_VOTE_REJECTED:
                $str = \Yii::t('structure', 'activity_MOTION_VOTE_REJECTED');
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_PUBLISH:
                $str = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, ($this->amendment ? $this->amendment->getInitiatorsStr() : ''));
            case self::AMENDMENT_DELETE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_DELETE');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_DELETE_PUBLISHED:
                $str    = \Yii::t('structure', 'activity_AMENDMENT_DELETE_PUBLISHED');
                $str    = $this->formatLogEntryUser($str, '');
                $prefix = self::amendmentId2Prefix($this->actionReferenceId);
                return str_replace('###AMENDMENT###', $prefix, $str);
            case self::AMENDMENT_CHANGE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_CHANGE');
                $str = $this->formatLogEntryUser($str, '');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_WITHDRAW:
                $str = \Yii::t('structure', 'activity_AMENDMENT_WITHDRAW');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_COMMENT:
            case self::AMENDMENT_COMMENT_SCREEN:
                if ($this->amendmentComment) {
                    $abstract = $this->amendmentComment->getTextAbstract(190);
                    $str      = $this->formatLogEntryUser('###USER###', $this->amendmentComment->name) . ': ';
                    $str      .= '<span class="quote">' . Html::encode($abstract) . '</span>';
                    return '<blockquote>' . $str . '</blockquote>';
                } else {
                    return null;
                }
            case self::AMENDMENT_VOTE_ACCEPTED:
                $str = \Yii::t('structure', 'activity_AMENDMENT_VOTE_ACCEPTED');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_VOTE_REJECTED:
                $str = \Yii::t('structure', 'activity_AMENDMENT_VOTE_REJECTED');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_SCREEN:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SCREEN');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_UNSCREEN:
                return null;
            case self::AMENDMENT_SUPPORT:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SUPPORT');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_LIKE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_LIKE');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_DISLIKE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_DISLIKE');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_UNLIKE:
                $str = \Yii::t('structure', 'activity_AMENDMENT_UNLIKE');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_SUPPORT_FINISH:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SUPPORT_FINISH');
                $str = $this->formatLogEntryAmendment($str);
                return $this->formatLogEntryUser($str, '');
            case self::AMENDMENT_PUBLISH_PROPOSAL:
                $str = \Yii::t('structure', 'activity_AMENDMENT_PUBLISH_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                return $this->formatLogEntryAmendment($str);
            case self::AMENDMENT_SET_PROPOSAL:
                $str = \Yii::t('structure', 'activity_AMENDMENT_SET_PROPOSAL');
                $str = $this->formatLogEntryUser($str, '');
                // @TODO More detailed output
                return $this->formatLogEntryAmendment($str);
            case self::VOTING_OPEN:
                return \Yii::t('structure', 'activity_VOTING_OPEN');
            case self::VOTING_CLOSE:
                return \Yii::t('structure', 'activity_VOTING_CLOSE');
            case self::VOTING_DELETE:
                return \Yii::t('structure', 'activity_VOTING_DELETE');
            case self::VOTING_QUESTION_ACCEPTED:
                return \Yii::t('structure', 'activity_VOTING_QUESTION_ACCEPTED');
            case self::VOTING_QUESTION_REJECTED:
                return \Yii::t('structure', 'activity_VOTING_QUESTION_REJECTED');
            case self::USER_ADD_TO_GROUP:
                $str = \Yii::t('structure', 'activity_USER_ADD_TO_GROUP');
                return $this->formatLogEntryUserGroup($str);
            case self::USER_REMOVE_FROM_GROUP:
                $str = \Yii::t('structure', 'activity_USER_REMOVE_FROM_GROUP');
                return $this->formatLogEntryUserGroup($str);
            default:
                return (string)$this->actionType;
        }
    }
}
