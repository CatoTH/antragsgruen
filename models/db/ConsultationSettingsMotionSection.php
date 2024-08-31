<?php

namespace app\models\db;

use app\components\HTMLTools;
use app\models\sectionTypes\{ISectionType, TabularData};
use app\models\settings\{AntragsgruenApp, MotionSection as MotionSectionSettings};
use yii\db\ActiveRecord;

/**
 * @property int|null $id
 * @property int $motionTypeId
 * @property int $type
 * @property int $position
 * @property int $status
 * @property string $title
 * @property string|null $data
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
    public const COMMENTS_NONE       = 0;
    public const COMMENTS_MOTION     = 1;
    public const COMMENTS_PARAGRAPHS = 2;

    public const STATUS_VISIBLE = 0;
    public const STATUS_DELETED = -1;

    public const REQUIRED_NO = 0;
    public const REQUIRED_YES = 1;
    public const REQUIRED_ENCOURAGED = 2;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationSettingsMotionSection';
    }

    /**
     * @return string[]
     */
    public static function getCommentTypes(): array
    {
        return [
            self::COMMENTS_NONE       => \Yii::t('structure', 'section_comment_none'),
            self::COMMENTS_MOTION     => \Yii::t('structure', 'section_comment_motion'),
            self::COMMENTS_PARAGRAPHS => \Yii::t('structure', 'section_comment_paragraph'),
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

    private ?MotionSectionSettings $settingsObject = null;

    public function getSettingsObj(): MotionSectionSettings
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new MotionSectionSettings($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(MotionSectionSettings $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function setAdminAttributes(array $data): void
    {
        $this->setAttributes($data);

        $this->required      = (isset($data['required']) ? intval($data['required']) : 0);
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

        if (grapheme_strlen($this->title) > 100) {
            $this->title = (string)grapheme_substr($this->title, 0, 100);
        }

        $settings = $this->getSettingsObj();
        if (isset($data['imgMaxWidth']) && $data['imgMaxWidth'] > 0) {
            $settings->imgMaxWidth = intval($data['imgMaxWidth']);
        } else {
            $settings->imgMaxWidth = 0;
        }
        if (isset($data['imgMaxHeight']) && $data['imgMaxHeight'] > 0) {
            $settings->imgMaxHeight = intval($data['imgMaxHeight']);
        } else {
            $settings->imgMaxHeight = 0;
        }
        if (isset($data['hasExplanation'])) {
            $settings->explanationHtml = HTMLTools::cleanSimpleHtml($data['explanationHtml']);
        } else {
            $settings->explanationHtml = null;
        }
        $settings->showInHtml = (isset($data['showInHtml']) || !in_array($this->type, [ISectionType::TYPE_PDF_ALTERNATIVE, ISectionType::TYPE_TITLE]));
        $settings->isRtl = isset($data['isRtl']);
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
            return [self::COMMENTS_NONE, self::COMMENTS_MOTION, self::COMMENTS_PARAGRAPHS];
        }
        if ($this->type === ISectionType::TYPE_TEXT_HTML) {
            return [self::COMMENTS_NONE, self::COMMENTS_MOTION];
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

    public function requiresAutoCreationWhenMissing(): bool
    {
        return $this->type === ISectionType::TYPE_TEXT_EDITORIAL; // Required for editing the text in the frontend
    }
}
