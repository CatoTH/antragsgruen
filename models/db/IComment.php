<?php

namespace app\models\db;

use app\components\Tools;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $userId
 * @property integer $paragraph
 * @property integer $parentCommentId
 * @property string $text
 * @property string $dateCreation
 * @property string $name
 * @property integer $status
 * @property User $user
 * @property IComment $parentComment
 * @property IComment[] $replies
 */
abstract class IComment extends ActiveRecord implements IRSSItem
{

    const STATUS_SCREENING = 1;
    const STATUS_VISIBLE   = 0;
    const STATUS_DELETED   = -1;

    const EVENT_PUBLISHED       = 'published';

    /**
     */
    public function init()
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'notifyUsers'], null, false);
    }

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_SCREENING => \Yii::t('comment', 'status_screening'),
            static::STATUS_VISIBLE   => \Yii::t('comment', 'status_visible'),
            static::STATUS_DELETED   => \Yii::t('comment', 'status_deleted'),
        ];
    }

    /**
     * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface|ActiveQuery]] instance.
     * @throws InvalidConfigException if there is no primary key defined
     * @internal
     */
    protected static function findByCondition($condition)
    {
        $query = parent::findByCondition($condition);
        $query->andWhere('status != ' . static::STATUS_DELETED);
        return $query;
    }

    /**
     * @return Consultation
     */
    abstract public function getConsultation();

    /**
     * @return string
     */
    abstract public function getMotionTitle();

    /**
     * @return IMotion
     */
    abstract public function getIMotion();

    /**
     * @return string
     */
    abstract public function getLink();

    /**
     * @param User|null $user
     * @return bool
     */
    public function canDelete($user)
    {
        if ($user === null) {
            return false;
        }
        if ($user->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }
        if (!$this->user) {
            return false;
        }
        if (!is_null($this->user->auth) && $user->auth === $this->user->auth) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isVisibleCurrUser()
    {
        if ($this->status === static::STATUS_DELETED) {
            return false;
        }
        if ($this->status === static::STATUS_VISIBLE) {
            return true;
        }

        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }
        /** @var User $identity */
        $identity = $user->identity;
        if ($identity->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }

        return ($identity->id === $this->userId);
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return IComment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        /** @var IComment[] $comments */
        $comments = array_merge(
            MotionComment::getNewestByConsultation($consultation, $limit),
            AmendmentComment::getNewestByConsultation($consultation, $limit)
        );
        usort($comments, function ($comm1, $comm2) {
            /** @var IComment $comm1 */
            /** @var IComment $comm2 */
            $ts1 = Tools::dateSql2timestamp($comm1->getDate());
            $ts2 = Tools::dateSql2timestamp($comm2->getDate());
            if ($ts1 < $ts2) {
                return 1;
            }
            if ($ts1 > $ts2) {
                return -1;
            }
            return 0;
        });
        return array_slice($comments, 0, $limit);
    }

    /**
     * @return int[]
     */
    public function getUserIdsBeingRepliedToByThis()
    {
        if ($this->parentCommentId === null) {
            return [];
        }

        $userIds = [];
        foreach ($this->getIMotion()->comments as $comment) {
            if ($comment->id === $this->id) {
                continue;
            }
            if ($comment->id === $this->parentCommentId || $comment->parentCommentId === $this->parentCommentId) {
                if ($comment->userId && !in_array($comment->userId, $userIds)) {
                    $userIds[] = $comment->userId;
                }
            }
        }

        return $userIds;
    }

    /**
     * @return int[]
     */
    public function getUserIdsActiveOnThisIMotion()
    {
        $userIds = [];

        foreach ($this->getIMotion()->getInitiators() as $initiator) {
            if ($initiator->userId && !in_array($initiator->userId, $userIds)) {
                $userIds[] = $initiator->userId;
            }
        }
        foreach ($this->getIMotion()->comments as $comment) {
            if ($comment->status === IComment::STATUS_DELETED) {
                continue;
            }
            if ($comment->userId && !in_array($comment->userId, $userIds)) {
                $userIds[] = $comment->userId;
            }
        }

        return $userIds;
    }

    /**
     */
    public function notifyUsers()
    {
        UserNotification::notifyNewComment($this);
    }
}
