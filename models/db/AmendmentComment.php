<?php

namespace app\models\db;

use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use yii\db\ActiveQuery;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $amendmentId
 * @property int $parentCommentId
 * @property string $text
 * @property string $name
 * @property string $contactEmail
 * @property string $dateCreation
 * @property int $status
 * @property int $replyNotification
 *
 * @property User $user
 * @property Amendment $amendment
 * @property AmendmentComment $parentComment
 * @property AmendmentComment[] $replies
 */
class AmendmentComment extends IComment
{
    /**
     */
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
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'amendmentComment';
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . IntVal(User::STATUS_DELETED));
    }

    /**
     * @return ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId'])
            ->andWhere(Amendment::tableName() . '.status != ' . IntVal(Amendment::STATUS_DELETED));
    }

    private $imotion = null;

    /**
     * @return Amendment|null
     */
    public function getIMotion()
    {
        if (!$this->imotion) {
            $current = Consultation::getCurrent();
            if ($current) {
                $amendment = $current->getAmendment($this->imotion);
                if ($amendment) {
                    $this->imotion = $amendment;
                } else {
                    $this->imotion = Amendment::findOne($this->amendmentId);
                }
            } else {
                $this->imotion = Amendment::findOne($this->amendmentId);
            }
        }
        return $this->imotion;
    }

    /**
     * @return ActiveQuery
     */
    public function getParentComment()
    {
        return $this->hasOne(AmendmentComment::class, ['id' => 'parentCommentId'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . IntVal(AmendmentComment::STATUS_DELETED))
            ->andWhere(AmendmentComment::tableName() . '.status != ' . IntVal(AmendmentComment::STATUS_PRIVATE));
    }

    /**
     * @return ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(AmendmentComment::class, ['parentCommentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . IntVal(AmendmentComment::STATUS_DELETED))
            ->andWhere(AmendmentComment::tableName() . '.status != ' . IntVal(AmendmentComment::STATUS_PRIVATE));
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        return $this->amendment->getMyConsultation();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'paragraph', 'status', 'dateCreation'], 'required'],
            ['name', 'required', 'message' => \Yii::t('comment', 'err_no_name')],
            ['text', 'required', 'message' => \Yii::t('comment', 'err_no_text')],
            [['id', 'amendmentId', 'paragraph', 'status', 'parentCommentId'], 'number'],
            [['text', 'paragraph'], 'safe'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return AmendmentComment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleMotionStatuses());

        return static::find()->joinWith('amendment', true)->joinWith('amendment.motionJoin', true)
            ->where('amendmentComment.status = ' . IntVal(static::STATUS_VISIBLE))
            ->andWhere('amendment.status NOT IN (' . implode(', ', $invisibleStatuses) . ')')
            ->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')')
            ->andWhere('motion.consultationId = ' . IntVal($consultation->id))
            ->orderBy('amendmentComment.dateCreation DESC')
            ->offset(0)->limit($limit)->all();
    }

    /**
     * @return string
     */
    public function getMotionTitle()
    {
        return $this->amendment->titlePrefix . ' ' . \Yii::t('amend', 'amend_for_motion') .
            ' ' . $this->amendment->getMyMotion()->getTitleWithPrefix();
    }

    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
        if ($this->status === static::STATUS_PRIVATE) {
            return;
        }
        $feed->addEntry(
            UrlHelper::createAmendmentCommentUrl($this),
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

    /**
     * @return string
     */
    public function getLink()
    {
        return UrlHelper::createAmendmentCommentUrl($this);
    }

    /**
     * @param Consultation $consultation
     * @return AmendmentComment[]
     */
    public static function getScreeningComments(Consultation $consultation)
    {
        $query = AmendmentComment::find();
        $query->where('amendmentComment.status = ' . IntVal(static::STATUS_SCREENING));
        $query->joinWith(
            [
                'amendment' => function ($query) use ($consultation) {
                    $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleAmendmentStatuses());
                    /** @var ActiveQuery $query */
                    $query->andWhere('amendment.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('amendment.motionId = motion.id');

                    $query->joinWith(
                        [
                            'motionJoin' => function ($query) use ($consultation) {
                                $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleMotionStatuses());
                                /** @var ActiveQuery $query */
                                $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                                $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                            }
                        ]
                    );
                }
            ]
        );
        $query->orderBy('dateCreation DESC');

        return $query->all();
    }

    /**
     */
    public function logToConsultationLog()
    {
        if ($this->status === static::STATUS_PRIVATE) {
            return;
        }
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::AMENDMENT_COMMENT, $this->id);
    }

    /**
     * @return array
     */
    public function getUserdataExportObject()
    {
        return [
            'amendment_title' => $this->amendment->getTitle(),
            'amendment_link'  => $this->amendment->getLink(true),
            'text'            => $this->text,
            'name'            => $this->name,
            'email'           => $this->contactEmail,
            'date_creation'   => $this->dateCreation,
            'status'          => $this->status,
        ];
    }
}
