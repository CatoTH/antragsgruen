<?php

namespace app\models\db;

use app\models\exceptions\FormError;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularData;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionTypeId
 * @property int $type
 * @property int $position
 * @property int $status
 * @property string $title
 * @property string $data
 * @property int $fixedWidth
 * @property int $maxLen
 * @property int $required
 * @property int $lineNumbers
 * @property int $hasComments
 * @property int $hasAmendments
 * @property int $positionRight
 *
 * @property MotionSection[] $sections
 * @property ConsultationMotionType $motionType
 */
class ConsultationSettingsMotionSection extends ActiveRecord
{
    const COMMENTS_NONE       = 0;
    const COMMENTS_MOTION     = 1;
    const COMMENTS_PARAGRAPHS = 2;

    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationSettingsMotionSection';
    }

    /**
     * @return string[]
     */
    public static function getCommentTypes()
    {
        return [
            static::COMMENTS_NONE       => \Yii::t('structure', 'section_comment_none'),
            static::COMMENTS_MOTION     => \Yii::t('structure', 'section_comment_motion'),
            static::COMMENTS_PARAGRAPHS => \Yii::t('structure', 'section_comment_paragraph'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(MotionSection::class, ['sectionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionType()
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAdminAttributes($data)
    {
        $this->setAttributes($data);

        $this->required      = (isset($data['required']) ? 1 : 0);
        $this->fixedWidth    = (isset($data['fixedWidth']) ? 1 : 0);
        $this->lineNumbers   = (isset($data['lineNumbers']) ? 1 : 0);
        $this->hasAmendments = (isset($data['hasAmendments']) ? 1 : 0);
        $this->positionRight = (isset($data['positionRight']) && $data['positionRight'] == 1 ? 1 : 0);
        if (isset($data['maxLenSet'])) {
            $this->maxLen = $data['maxLenVal'];
            if (isset($data['maxLenSoft'])) {
                $this->maxLen *= -1;
            }
        } else {
            $this->maxLen = 0;
        }

        if ($this->type == ISectionType::TYPE_TABULAR) {
            $this->data = TabularData::saveTabularDataSettingsFromPost($this->data, $data);
        } else {
            $this->data = null;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionTypeId', 'title', 'type', 'position', 'status', 'required'], 'required'],
            [['id', 'type', 'motionTypeId', 'status', 'required', 'positionRight'], 'number'],
            [['position', 'fixedWidth', 'maxLen', 'lineNumbers', 'hasComments', 'hasAmendments'], 'number'],
            [['title', 'maxLen', 'hasComments', 'hasAmendments'], 'safe'],
        ];
    }

    /**
     * @return int[]
     */
    public function getAvailableCommentTypes()
    {
        if ($this->type == ISectionType::TYPE_TEXT_SIMPLE) {
            return [static::COMMENTS_NONE, static::COMMENTS_MOTION, static::COMMENTS_PARAGRAPHS];
        }
        if ($this->type == ISectionType::TYPE_TEXT_HTML) {
            return [static::COMMENTS_NONE, static::COMMENTS_MOTION];
        }
        return [static::COMMENTS_NONE];
    }

    /**
     * @return string[]
     */
    public function getForbiddenMotionFormattings()
    {
        $forbidden = [];
        if ($this->hasAmendments) {
            $forbidden[] = 'strike';
        }
        return $forbidden;
    }
}
