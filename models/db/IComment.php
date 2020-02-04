<?php

namespace app\models\db;

use app\components\Tools;
use yii\base\InvalidConfigException;
use yii\db\{ActiveQueryInterface, ActiveRecord};

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
    const STATUS_PRIVATE   = -2;

    const EVENT_PUBLISHED = 'published';

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
    public static function getStatuses()
    {
        return [
            static::STATUS_SCREENING => \Yii::t('comment', 'status_screening'),
            static::STATUS_VISIBLE   => \Yii::t('comment', 'status_visible'),
            static::STATUS_DELETED   => \Yii::t('comment', 'status_deleted'),
            static::STATUS_PRIVATE   => \Yii::t('comment', 'status_private'),
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
     * @param int $maxLength
     * @return string
     */
    public function getTextAbstract($maxLength)
    {
        $urlsearch = $urlreplace = [];
        $wwwsearch = $wwwreplace = [];

        $urlMaxlen      = 250;
        $urlMaxlenEnd   = 50;
        $urlMaxlenHost  = 150;
        $urlPatternHost = '[-a-zäöüß0-9\_\.]';
        $urlPattern     = '([-a-zäöüß0-9\_\$\.\:;\/?=\+\~@,%#!\'\[\]\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)';
        $urlPatternEnd  = '([-a-zäöüß0-9\_\$\:\/=\+\~@%#\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)';

        $endPattern     = "($urlPatternEnd|($urlPattern*\\($urlPattern{0,$urlMaxlenEnd}\\)){1,3})";
        $hostUrlPattern = "$urlPatternHost{1,$urlMaxlenHost}(\\/?($urlPattern{0,$urlMaxlen}$endPattern)?)?";

        $urlsearch[]  = "/([({\\[\\|>\\s])((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "\\1[LINK]";

        $urlsearch[]  = "/^((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "[LINK]";

        $wwwsearch[]  = "/([({\\[\\|>\\s])((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "\\1[LINK]";

        $wwwsearch[]  = "/^((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "[LINK]";

        $text = preg_replace($urlsearch, $urlreplace, $this->text);
        $text = preg_replace($wwwsearch, $wwwreplace, $text);

        if (mb_strlen($this->text) > $maxLength) {
            $text = explode("\n", wordwrap(str_replace("\n", " ", $text), $maxLength))[0] . '…';
        }

        return $text;
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function canDelete($user)
    {
        if ($user === null) {
            return false;
        }
        if ($this->status !== static::STATUS_PRIVATE &&
            $user->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }
        return ($this->userId && $this->userId === $user->id);
    }

    /**
     * @return bool
     */
    public function isVisibleCurrUser()
    {
        $user = User::getCurrentUser();
        switch ($this->status) {
            case static::STATUS_DELETED:
                return false;
            case static::STATUS_VISIBLE:
                return true;
            case static::STATUS_PRIVATE:
                return ($user && $user->id === $this->userId);
            case static::STATUS_SCREENING:
                if ($user && $user->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
                    return true;
                } else {
                    return ($user && $user->id === $this->userId);
                }
            default:
                return false;
        }
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
        usort($comments, function (IComment $comm1, IComment $comm2) {
            return -1 * Tools::compareSqlTimes($comm1->getDate(), $comm2->getDate());
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
            if ($comment->status === IComment::STATUS_DELETED || $comment->status === IComment::STATUS_PRIVATE) {
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

    /**
     * @return array
     */
    abstract public function getUserdataExportObject();

    /**
     * @param Consultation[] $consultations
     * @param int $limit
     * @return IComment[]
     */
    public static function getNewestForConsultations(array $consultations, int $limit): array
    {
        /** @var IComment[] $comments */
        $comments = [];
        foreach ($consultations as $consultation) {
            $comments = array_merge(
                MotionComment::getNewestByConsultation($consultation, $limit * 5),
                AmendmentComment::getNewestByConsultation($consultation, $limit * 5)
            );
        }

        usort($comments, function (IComment $comm1, IComment $comm2) {
            return -1 * Tools::compareSqlTimes($comm1->getDate(), $comm2->getDate());
        });

        $filtered = [];
        $foundIds = [];
        foreach ($comments as $comment) {
            $id = null;
            if (is_a($comment, MotionComment::class)) {
                $id = 'motion.' . $comment->motionId;
            }
            if (is_a($comment, AmendmentComment::class)) {
                $id = 'amendment.' . $comment->amendmentId;
            }
            if (!in_array($id, $foundIds) && count($filtered) < $limit) {
                $foundIds[] = $id;
                $filtered[] = $comment;
            }
        }

        return array_slice($filtered, 0, $limit);
    }
}
