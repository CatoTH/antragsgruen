<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\sectionTypes\{Image, ISectionType, TabularData, TextHTML, TextSimple, Title, PDF, VideoEmbed};
use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * Class IMotionSection
 * @package app\models\db
 * @property string $data
 * @property string $dataRaw
 * @property int $sectionId
 * @property string $metadata
 */
abstract class IMotionSection extends ActiveRecord
{
    /**
     * @return ConsultationSettingsMotionSection
     */
    abstract public function getSettings();

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


    /**
     * @return bool
     */
    public function checkLength()
    {
        // @TODO
        return true;
    }

    /**
     * @return int
     */
    abstract public function getFirstLineNumber();

    /**
     * @return bool
     */
    public function isLayoutRight()
    {
        return ($this->getSettings()->positionRight == 1);
    }

    /**
     * @return string
     */
    public function getShowAlwaysToken()
    {
        return sha1('createToken' . AntragsgruenApp::getInstance()->randomSeed . $this->getData());
    }

    abstract public function getData(): string;

    abstract public function setData(string $data);
}
