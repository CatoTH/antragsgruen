<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\components\{RSSExporter, Tools, UrlHelper};
use yii\db\ActiveQuery;

/**
 * @property int|null $id
 * @property int $userId
 * @property int $motionId
 * @property int $sectionId
 * @property int $parentCommentId
 * @property int $paragraph
 * @property string $text
 * @property string $name
 * @property string $contactEmail
 * @property string $dateCreation
 * @property int $status
 * @property int $replyNotification
 *
 * @property User $user
 * @property Motion $motion
 * @property MotionCommentSupporter[] $supporters
 * @property MotionSection $section
 * @property MotionComment $parentComment
 * @property MotionComment[] $replies
 */
class MotionComment extends IComment
{
    public function init()
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'logToConsultationLog'], null, false);
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionComment';
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    private $imotion = null;

    /**
     * @return Motion|null
     */
    public function getIMotion()
    {
        if (!$this->imotion) {
            $current = Consultation::getCurrent();
            if ($current) {
                $motion = $current->getMotion($this->motionId);
                if ($motion) {
                    $this->imotion = $motion;
                } else {
                    $this->imotion = Motion::findOne($this->motionId);
                }
            } else {
                $this->imotion = Motion::findOne($this->motionId);
            }
        }
        return $this->imotion;
    }

    /**
     * @return ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasMany(MotionCommentSupporter::class, ['motionCommentId' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(MotionSection::class, ['motionId' => 'motionId', 'sectionId' => 'sectionId']);
    }

    /**
     * @return ActiveQuery
     */
    public function getParentComment()
    {
        return $this->hasOne(MotionComment::class, ['id' => 'parentCommentId'])
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED)
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_PRIVATE);
    }

    /**
     * @return ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(MotionComment::class, ['parentCommentId' => 'id'])
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED)
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_PRIVATE);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId', 'paragraph', 'status', 'dateCreation'], 'required'],
            ['name', 'required', 'message' => \Yii::t('comment', 'err_no_name')],
            ['text', 'required', 'message' => \Yii::t('comment', 'err_no_text')],
            [['id', 'motionId', 'sectionId', 'paragraph', 'status', 'parentCommentId'], 'number'],
            [['text', 'paragraph'], 'safe'],
        ];
    }

    /**
     * @return MotionComment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, int $limit = 5): array
    {
        $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());
        $all = static::find()->joinWith('motion', true)
            ->where('motionComment.status = ' . intval(static::STATUS_VISIBLE))
            ->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')')
            ->andWhere('motion.consultationId = ' . intval($consultation->id))
            ->orderBy('motionComment.dateCreation DESC')
            ->offset(0)->limit($limit)->all();

        return array_values(array_filter($all, function (IComment $comment): bool {
            return $comment->getIMotion()->getMyMotionType()->maySeeIComments();
        }));
    }

    public function getConsultation(): ?Consultation
    {
        $motion = $this->getIMotion();
        return $motion->getMyConsultation();
    }

    public function getMotionTitle(): string
    {
        $motion = $this->getIMotion();
        return $motion->getTitleWithPrefix();
    }

    public function addToFeed(RSSExporter $feed): void
    {
        if ($this->status === static::STATUS_PRIVATE) {
            return;
        }
        $feed->addEntry(
            UrlHelper::createMotionCommentUrl($this),
            \Yii::t('motion', 'comment_for') . ': ' . $this->getMotionTitle(),
            $this->name,
            $this->text,
            Tools::dateSql2timestamp($this->dateCreation)
        );
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    public function getLink(): string
    {
        return UrlHelper::createMotionCommentUrl($this);
    }

    /**
     * @return MotionComment[]
     */
    public static function getScreeningComments(Consultation $consultation)
    {
        $query = MotionComment::find();
        $query->where('motionComment.status = ' . static::STATUS_SCREENING);
        $query->joinWith(
            [
                'motion' => function ($query) use ($consultation) {
                    $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('motion.consultationId = ' . intval($consultation->id));
                }
            ]
        );
        $query->orderBy('dateCreation DESC');

        return $query->all();
    }

    public function logToConsultationLog(): void
    {
        if ($this->status === static::STATUS_PRIVATE) {
            return;
        }
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::MOTION_COMMENT, $this->id);
    }

    public function getUserdataExportObject(): array
    {
        return [
            'motion_title'  => $this->getIMotion()->getTitleWithPrefix(),
            'motion_link'   => $this->getIMotion()->getLink(true),
            'text'          => $this->text,
            'name'          => $this->name,
            'email'         => $this->contactEmail,
            'date_creation' => $this->dateCreation,
            'status'        => $this->status,
        ];
    }
}
