<?php

namespace app\models\settings;

use app\models\exceptions\FormError;
use app\models\exceptions\Internal;

class Consultation
{
    const START_LAYOUT_STD    = 0;
    const START_LAYOUT_BDK    = 1;
    const START_LAYOUT_TAGS   = 2;
    const START_LAYOUT_AGENDA = 3;


    // TESTS WITH TEST CASES

    /** @var bool */
    public $maintainanceMode    = false;
    public $screeningMotions    = false;
    public $lineNumberingGlobal = false;

    // TESTS WITHOUT TEST CASES

    /** @var bool */
    public $motionNeedsEmail      = false;
    public $motionNeedsPhone      = false;
    public $motionHasPhone        = false;
    public $commentNeedsEmail     = false;
    public $iniatorsMayEdit       = false;
    public $adminsMayEdit         = true;
    public $confirmEmails         = false;
    public $hideRevision          = false;
    public $minimalisticUI        = false;
    public $showFeeds             = true;
    public $commentsSupportable   = false;
    public $screeningMotionsShown = false;
    public $screeningAmendments   = false;
    public $screeningComments     = false;
    public $initiatorsMayReject   = false;
    public $hasPDF                = true;
    public $commentWholeMotions   = false;
    public $allowMultipleTags     = false;
    public $allowStrikeFormat     = false;

    /** @var int */
    public $lineLength      = 80;
    public $startLayoutType = 0;

    /** @var null|string */
    public $logoUrl     = null;
    public $logoUrlFB   = null;
    public $motionIntro = null;

    /**
     * @param string|null $data
     */
    public function __construct($data)
    {
        if ($data == "") {
            return;
        }
        $data = (array)json_decode($data);

        if (!is_array($data)) {
            return;
        }
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(get_object_vars($this));
    }

    /**
     * @param array $formdata
     * @param array $affectedFields
     * @throws FormError
     */
    public function saveForm($formdata, $affectedFields)
    {
        $fields = get_object_vars($this);
        foreach ($affectedFields as $key) {
            if (!array_key_exists($key, $fields)) {
                throw new FormError('Unknown field: ' . $key);
            }
            $val = $fields[$key];
            if (is_bool($val)) {
                $this->$key = (isset($formdata[$key]) && (bool)$formdata[$key]);
            } elseif (is_int($val)) {
                $this->$key = (int)$formdata[$key];
            } else {
                $this->$key = $formdata[$key];
            }
        }
    }

    /**
     * @return string[]
     */
    public function getStartLayouts()
    {
        return [
            0 => 'Standard',
            1 => 'Tabellarisch, gegliedert nach Antragstyp',
            2 => 'Tabellarisch, gegliedert nach Schlagworten',
            3 => 'Tagesordnung',
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
            case Consultation::START_LAYOUT_BDK:
                return 'index_layout_bdk'; // @Todo not implemented
            case Consultation::START_LAYOUT_TAGS:
                return 'index_layout_tags';  // @Todo not implemented
            case Consultation::START_LAYOUT_AGENDA:
                return 'index_layout_agenda';
            default:
                throw new Internal('Unknown layout: ' . $this->startLayoutType);
        }
    }
}
