<?php

namespace app\models\settings;

use app\models\exceptions\Internal;

class Consultation
{
    use JsonConfigTrait;

    const START_LAYOUT_STD         = 0;
    const START_LAYOUT_TAGS        = 2;
    const START_LAYOUT_AGENDA      = 3;
    const START_LAYOUT_AGENDA_LONG = 4;


    // SETTINGS WITH TEST CASES

    /** @var bool */
    public $maintenanceMode        = false;
    public $screeningMotions       = false;
    public $screeningAmendments    = false;
    public $lineNumberingGlobal    = false;
    public $iniatorsMayEdit        = false;
    public $hideTitlePrefix        = false;
    public $showFeeds              = true;
    public $commentNeedsEmail      = false;
    public $screeningComments      = false;
    public $initiatorConfirmEmails = false;
    public $adminsMayEdit          = true;
    public $forceMotion            = null;
    public $editorialAmendments    = true;
    public $globalAlternatives     = true;

    // SETTINGS WITHOUT TEST CASES

    /** @var bool */
    public $minimalisticUI         = false;
    public $commentsSupportable    = false;
    public $screeningMotionsShown  = false;
    public $initiatorsMayReject    = false;
    public $allowMultipleTags      = false;
    public $odtExportHasLineNumers = true;

    /** @var int */
    public $lineLength      = 80;
    public $startLayoutType = 0;

    /** @var null|string */
    public $logoUrl         = null;
    public $logoUrlFB       = null;
    public $motionIntro     = null;
    public $pdfIntroduction = '';

    /**
     * @return string[]
     */
    public static function getStartLayouts()
    {
        return [
            static::START_LAYOUT_STD         => \Yii::t('structure', 'home_layout_std'),
            static::START_LAYOUT_TAGS        => \Yii::t('structure', 'home_layout_tags'),
            static::START_LAYOUT_AGENDA      => \Yii::t('structure', 'home_layout_agenda'),
            static::START_LAYOUT_AGENDA_LONG => \Yii::t('structure', 'home_layout_agenda_long'),
        ];
    }

    /**
     * @return string
     * @throws Internal
     */
    public function getStartLayoutView()
    {
        switch ($this->startLayoutType) {
            case Consultation::START_LAYOUT_STD:
                return 'index_layout_std';
            case Consultation::START_LAYOUT_TAGS:
                return 'index_layout_tags';
            case Consultation::START_LAYOUT_AGENDA:
                return 'index_layout_agenda';
            case Consultation::START_LAYOUT_AGENDA_LONG:
                return 'index_layout_agenda';
            default:
                throw new Internal('Unknown layout: ' . $this->startLayoutType);
        }
    }
}
