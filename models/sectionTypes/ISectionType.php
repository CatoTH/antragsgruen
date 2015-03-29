<?php
namespace app\models\sectionTypes;

use app\models\db\MotionSection;
use app\models\exceptions\FormError;

abstract class ISectionType
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;

    /** @var  MotionSection */
    protected $section;

    /**
     * @param MotionSection $section
     */
    public function __construct(MotionSection $section)
    {
        $this->section = $section;
    }

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return [
            static::TYPE_TITLE       => 'Titel',
            static::TYPE_TEXT_SIMPLE => 'Text',
            static::TYPE_TEXT_HTML   => 'Text (erweitert)',
            static::TYPE_IMAGE       => 'Bild',
        ];
    }

    /**
     * @return string
     */
    abstract public function getFormField();

    /**
     * @param $data
     * @throws FormError
     */
    abstract public function setData($data);

    /**
     * @return string
     */
    abstract public function showSimple();


    /**
     * @return string
     */
    public function showMotionView()
    {
        return $this->showSimple();
    }
}
