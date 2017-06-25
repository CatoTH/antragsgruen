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
 * @property string $text
 * @property string $name
 * @property string $contactEmail
 * @property string $dateCreation
 * @property int $status
 * @property int $replyNotification
 *
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentComment extends IComment
{

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
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
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
            ['name', 'required', 'message' => 'Bitte gib deinen Namen an.'],
            ['text', 'required', 'message' => 'Bitte gib etwas Text ein.'],
            [['id', 'amendmentId', 'paragraph', 'status'], 'number'],
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
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());

        return static::find()->joinWith('amendment', true)->joinWith('amendment.motionJoin', true)
            ->where('amendmentComment.status = ' . IntVal(static::STATUS_VISIBLE))
            ->andWhere('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')')
            ->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')')
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
        $query->where('amendmentComment.status = ' . static::STATUS_SCREENING);
        $query->joinWith(
            [
                'amendment' => function ($query) use ($consultation) {
                    $invisibleStati = array_map('IntVal', $consultation->getInvisibleAmendmentStati());
                    /** @var ActiveQuery $query */
                    $query->andWhere('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                    $query->andWhere('amendment.motionId = motion.id');

                    $query->joinWith(
                        [
                            'motionJoin'    => function ($query) use ($consultation) {
                                $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
                                /** @var ActiveQuery $query */
                                $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
                                $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                            }
                        ]
                    );
                }
            ]
        );
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }
}
