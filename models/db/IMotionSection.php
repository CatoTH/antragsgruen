<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\sectionTypes\{Image, ISectionType, TabularData, TextEditorial, TextHTML, TextSimple, Title, PDF, VideoEmbed};
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
     * @throws Internal
     */
    public function getSectionType(): ISectionType
    {
        return match ($this->getSettings()->type) {
            ISectionType::TYPE_TITLE => new Title($this),
            ISectionType::TYPE_TEXT_HTML => new TextHTML($this),
            ISectionType::TYPE_TEXT_SIMPLE => new TextSimple($this),
            ISectionType::TYPE_TEXT_EDITORIAL => new TextEditorial($this),
            ISectionType::TYPE_IMAGE => new Image($this),
            ISectionType::TYPE_TABULAR => new TabularData($this),
            ISectionType::TYPE_PDF_ATTACHMENT, ISectionType::TYPE_PDF_ALTERNATIVE => new PDF($this),
            ISectionType::TYPE_VIDEO_EMBED => new VideoEmbed($this),
            default => throw new Internal('Unknown Field Type: ' . $this->getSettings()->type),
        };
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
