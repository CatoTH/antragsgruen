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
 * @property MotionSupporter[] $supporters
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
     * @return string
     */
    public function getNameWithPrefix()
    {
        if ($this->consultation->getSettings()->hideRevision) {
            return $this->title;
        }

        $name = $this->titlePrefix;
        if (strlen($name) > 1 && !in_array($name[strlen($name) - 1], array(":", "."))) {
            $name .= ":";
        }
        $name .= " " . $this->title;
        return $name;
    }

    /**
     * @return Amendment[]
     */
    public function getVisibleAmendments()
    {
        $amendments = [];
        foreach ($this->amendments as $amend) {
            if (!in_array($amend->status, Amendment::getInvisibleStati())) {
                $amendments[] = $amend;
            }
        }
        return $amendments;
    }

    /**
     * @return bool
     */
    public function iAmInitiator()
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->supporters as $supp) {
            if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return bool
     */
    public function canEdit()
    {
        if ($this->status == static::STATUS_DRAFT) {
            return true;
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->consultation->getSettings()->adminsMayEdit) {
            if ($this->consultation->isAdminCurUser()) {
                return true;
            }
            if ($this->consultation->site->isAdminCurUser()) {
                return true;
            }
        }

        if ($this->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->consultation->motionDeadlineIsOver()) {
                return false;
            } else {
                return true;
            }
        }

        return false;
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
