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
 */
class MotionComment extends IComment
{
    const STATUS_VISIBLE   = 0;
    const STATUS_DELETED   = -1;
    const STATUS_SCREENING = 1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'motionComment';
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
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED);
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
     * @param Consultation $consultation
     * @param int $limit
     * @return MotionComment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());

        return static::find()->joinWith('motion', true)
            ->where('motionComment.status = ' . IntVal(static::STATUS_VISIBLE))
            ->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')')
            ->andWhere('motion.consultationId = ' . IntVal($consultation->id))
            ->orderBy('motionComment.dateCreation DESC')
            ->offset(0)->limit($limit)->all();
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        return $this->motion->getMyConsultation();
    }

    /**
     * @return string
     */
    public function getMotionTitle()
    {
        return $this->motion->getTitleWithPrefix();
    }

    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
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

    /**
     * @return string
     */
    public function getLink()
    {
        return UrlHelper::createMotionCommentUrl($this);
    }

    /**
     * @param Consultation $consultation
     * @return MotionComment[]
     */
    public static function getScreeningComments(Consultation $consultation)
    {
        $query = MotionComment::find();
        $query->where('motionComment.status = ' . static::STATUS_SCREENING);
        $query->joinWith(
            [
                'motion' => function ($query) use ($consultation) {
                    $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy('dateCreation DESC');

        return $query->all();
    }
}
