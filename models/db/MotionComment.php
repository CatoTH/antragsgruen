<?php

namespace app\models\db;

use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\Url;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $motionId
 * @property string $text
 * @property string $dateCreation
 * @property int $status
 * @property int $replyNotification
 *
 * @property User $user
 * @property Motion $motion
 * @property MotionCommentSupporter[] $supporters
 */
class MotionComment extends IComment
{
    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motionComment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasOne(MotionCommentSupporter::className(), ['motionCommentId' => 'id']);
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', Motion::getInvisibleStati());

        $query = (new Query())->select('motionComment.*')->from('motionComment');
        $query->innerJoin('motion', 'motion.id = motionComment.motionId');
        $query->where('motionComment.status = ' . IntVal(static::STATUS_VISIBLE));
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->where('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        return $this->motion->consultation;
    }

    /**
     * @return string
     */
    public function getMotionName()
    {
        return $this->motion->getNameWithPrefix();
    }

    /**
     * @param bool $absolute
     * @return string
     */
    public function getLink($absolute = false)
    {
        $url = Url::toRoute(
            [
                'motion/view',
                'subdomain'        => $this->motion->consultation->site->subdomain,
                'consultationPath' => $this->motion->consultation->urlPath,
                'motionId'         => $this->motion->id,
                'commentId'        => $this->id,
                '#'                => 'comment' . $this->id
            ]
        );
        if ($absolute) {
            // @TODO Testen
            $url = \Yii::$app->basePath . $url;
        }
        return $url;
    }
}
