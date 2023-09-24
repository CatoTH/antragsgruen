<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\sectionTypes\{Image, ISectionType, TabularData, TextHTML, TextSimple, Title, PDF, VideoEmbed};
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property string $data
 * @property string|null $dataRaw
 * @property int $public
 * @property int $sectionId
 * @property string $metadata
 */
abstract class IMotionSection extends ActiveRecord
{
    abstract public function getSettings(): ?ConsultationSettingsMotionSection;

    /**
     * @return ISectionType
     * @throws Internal
     */
    public function getSectionType()
    {
        switch ($this->getSettings()->type) {
            case ISectionType::TYPE_TITLE:
                return new Title($this);
            case ISectionType::TYPE_TEXT_HTML:
                return new TextHTML($this);
            case ISectionType::TYPE_TEXT_SIMPLE:
                return new TextSimple($this);
            case ISectionType::TYPE_IMAGE:
                return new Image($this);
            case ISectionType::TYPE_TABULAR:
                return new TabularData($this);
            case ISectionType::TYPE_PDF_ATTACHMENT:
                return new PDF($this);
            case ISectionType::TYPE_PDF_ALTERNATIVE:
                return new PDF($this);
            case ISectionType::TYPE_VIDEO_EMBED:
                return new VideoEmbed($this);
        }
        throw new Internal('Unknown Field Type: ' . $this->getSettings()->type);
    }

    public function checkLength(): bool
    {
        // @TODO
        return true;
    }

    abstract public function getFirstLineNumber(): int;

    public function isLayoutRight(): bool
    {
        return ($this->getSettings()->positionRight == 1);
    }

    public function getShowAlwaysToken(): string
    {
        return sha1('createToken' . AntragsgruenApp::getInstance()->randomSeed . $this->getData());
    }

    abstract public function getData(): string;

    abstract public function setData(string $data): void;
}
