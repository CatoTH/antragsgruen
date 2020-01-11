<?php

namespace app\models\db;

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
 * @property int $printTitle
 * @property string|null $settings
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

    public static function tableName(): string
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationSettingsMotionSection';
    }

    /**
     * @return string[]
     */
    public static function getCommentTypes(): array
    {
        return [
            static::COMMENTS_NONE       => \Yii::t('structure', 'section_comment_none'),
            static::COMMENTS_MOTION     => \Yii::t('structure', 'section_comment_motion'),
            static::COMMENTS_PARAGRAPHS => \Yii::t('structure', 'section_comment_paragraph'),
        ];
    }

    public function getSections(): \yii\db\ActiveQuery
    {
        return $this->hasMany(MotionSection::class, ['sectionId' => 'id']);
    }

    public function getMotionType(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    /** @var null|\app\models\settings\MotionSection */
    private $settingsObject = null;

    public function getSettingsObj(): \app\models\settings\MotionSection
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\MotionSection($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(\app\models\settings\MotionSection $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    public function setAdminAttributes(array $data): void
    {
        $this->setAttributes($data);

        $this->required      = (isset($data['required']) ? 1 : 0);
        $this->fixedWidth    = (isset($data['fixedWidth']) ? 1 : 0);
        $this->lineNumbers   = (isset($data['lineNumbers']) ? 1 : 0);
        $this->hasAmendments = (isset($data['hasAmendments']) ? 1 : 0);
        $this->positionRight = (isset($data['positionRight']) && intval($data['positionRight']) === 1 ? 1 : 0);
        $this->printTitle    = (isset($data['printTitle']) && intval($data['printTitle']) === 1 ? 1 : 0);
        if (isset($data['maxLenSet'])) {
            $this->maxLen = $data['maxLenVal'];
            if (isset($data['maxLenSoft'])) {
                $this->maxLen *= -1;
            }
        } else {
            $this->maxLen = 0;
        }

        if (intval($this->type) === ISectionType::TYPE_TABULAR) {
            $this->data = TabularData::saveTabularDataSettingsFromPost($this->data, $data);
        } else {
            $this->data = null;
        }

        $settings = $this->getSettingsObj();
        if (isset($data['imgMaxWidth']) && $data['imgMaxWidth'] > 0) {
            $settings->imgMaxWidth = floatval($data['imgMaxWidth']);
        } else {
            $settings->imgMaxWidth = 0;
        }
        if (isset($data['imgMaxHeight']) && $data['imgMaxHeight'] > 0) {
            $settings->imgMaxHeight = floatval($data['imgMaxHeight']);
        } else {
            $settings->imgMaxHeight = 0;
        }
        $this->setSettingsObj($settings);
    }

    public function rules(): array
    {
        return [
            [['motionTypeId', 'title', 'type', 'position', 'status', 'required'], 'required'],
            [['id', 'type', 'motionTypeId', 'status', 'required', 'positionRight', 'printTitle'], 'number'],
            [['position', 'fixedWidth', 'maxLen', 'lineNumbers', 'hasComments', 'hasAmendments'], 'number'],
            [['title', 'maxLen', 'hasComments', 'hasAmendments'], 'safe'],
        ];
    }

    /**
     * @return int[]
     */
    public function getAvailableCommentTypes(): array
    {
        if ($this->type === ISectionType::TYPE_TEXT_SIMPLE) {
            return [static::COMMENTS_NONE, static::COMMENTS_MOTION, static::COMMENTS_PARAGRAPHS];
        }
        if ($this->type === ISectionType::TYPE_TEXT_HTML) {
            return [static::COMMENTS_NONE, static::COMMENTS_MOTION];
        }
        return [static::COMMENTS_NONE];
    }

    /**
     * @return string[]
     */
    public function getForbiddenMotionFormattings(): array
    {
        $forbidden = [];
        if ($this->hasAmendments) {
            $forbidden[] = 'strike';
        }
        return $forbidden;
    }
}
