<?php

namespace app\models\db;

use app\models\sectionTypes\ISectionType;
use yii\db\ActiveRecord;

/**
 * Class IMotion
 * @package app\models\db
 *
 * @property int $id
 * @property IMotionSection[] $sections
 * @property int $status
 */
abstract class IMotion extends ActiveRecord
{
    const STATUS_DELETED              = -2;
    const STATUS_WITHDRAWN            = -1;
    const STATUS_UNCONFIRMED          = 0;
    const STATUS_DRAFT                = 1;
    const STATUS_SUBMITTED_UNSCREENED = 2;
    const STATUS_SUBMITTED_SCREENED   = 3;
    const STATUS_ACCEPTED             = 4;
    const STATUS_DECLINED             = 5;
    const STATUS_MODIFIED_ACCEPTED    = 6;
    const STATUS_MODIFIED             = 7;
    const STATUS_ADOPTED              = 8;
    const STATUS_COMPLETED            = 9;
    const STATUS_REFERRED             = 10;
    const STATUS_VOTE                 = 11;
    const STATUS_PAUSED               = 12;
    const STATUS_MISSING_INFORMATION  = 13;
    const STATUS_DISMISSED            = 14;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_DELETED              => "Gelöscht",
            static::STATUS_WITHDRAWN            => "Zurückgezogen",
            static::STATUS_UNCONFIRMED          => "Unbestätigt",
            static::STATUS_DRAFT                => "Entwurf",
            static::STATUS_SUBMITTED_UNSCREENED => "Eingereicht (ungeprüft)",
            static::STATUS_SUBMITTED_SCREENED   => "Eingereicht",
            static::STATUS_ACCEPTED             => "Angenommen",
            static::STATUS_DECLINED             => "Abgelehnt",
            static::STATUS_MODIFIED_ACCEPTED    => "Modifizierte Übernahme",
            static::STATUS_MODIFIED             => "Modifziert",
            static::STATUS_ADOPTED              => "Übernahme",
            static::STATUS_COMPLETED            => "Erledigt",
            static::STATUS_REFERRED             => "Überweisung",
            static::STATUS_VOTE                 => "Abstimmung",
            static::STATUS_PAUSED               => "Pausiert",
            static::STATUS_MISSING_INFORMATION  => "Informationen fehlen",
            static::STATUS_DISMISSED            => "Nicht zugelassen",
        ];
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return !in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati());
    }


    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators();

    /**
     * @return string
     */
    public function getInitiatorsStr()
    {
        $inits = $this->getInitiators();
        $str   = [];
        foreach ($inits as $init) {
            $str[] = $init->getNameWithResolutionDate(false);
        }
        return implode(', ', $str);
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getSupporters();

    /**
     * @return ISupporter[]
     */
    abstract public function getLikes();

    /**
     * @return ISupporter[]
     */
    abstract public function getDislikes();

    /**
     * @return Consultation
     */
    abstract public function getMyConsultation();

    /**
     * @return ConsultationSettingsMotionSection
     */
    abstract public function getMySections();

    /**
     * @return IMotionSection|null
     */
    public function getTitleSection()
    {
        foreach ($this->sections as $section) {
            if ($section->consultationSetting->type == ISectionType::TYPE_TITLE) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @param bool $withoutTitle
     * @return IMotionSection[]
     */
    public function getSortedSections($withoutTitle = false)
    {
        $sectionsIn = array();
        $title = $this->getTitleSection();
        foreach ($this->sections as $section) {
            if (!$withoutTitle || $section != $title) {
                $sectionsIn[$section->consultationSetting->id] = $section;
            }
        }
        $sectionsOut = array();
        foreach ($this->getMySections() as $section) {
            if (isset($sectionsIn[$section->id])) {
                $sectionsOut[] = $sectionsIn[$section->id];
            }
        }
        return $sectionsOut;
    }

    /**
     * @return int
     */
    abstract public function getNumberOfCountableLines();

    /**
     * @return int
     */
    abstract public function getFirstLineNumber();
}
