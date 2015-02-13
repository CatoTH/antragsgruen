<?php
namespace app\models\db;

use yii\db\Query;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $parentMotionId
 * @property string $title
 * @property string $titlePrefix
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $cacheLineNumber
 * @property int $cacheParagraphNumber
 * @property int $textFixed
 *
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property MotionComment[] $comments
 * @property ConsultationSettingsTag[] $tags
 * @property MotionSection[] $sections
 */
class Motion extends IMotion
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motion';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(MotionComment::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasMany(MotionSupporter::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasOne(Amendment::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::className(), ['id' => 'tagId'])
            ->viaTable('motionTag', ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(MotionSection::className(), ['motionId' => 'id']);
    }


    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Motion[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', static::getInvisibleStati());

        $query = (new Query())->select('motion.*')->from('motion');
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->where('motion.consultationId = ' . $consultation->id);
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }


    /**
     * @return User[]
     */
    public function getInitiators()
    {
        // TODO: Implement getInitiators() method.
    }

    /**
     * @return User[]
     */
    public function getLikes()
    {
        // TODO: Implement getLikes() method.
    }

    /**
     * @return User[]
     */
    public function getDislikes()
    {
        // TODO: Implement getDislikes() method.
    }
}
