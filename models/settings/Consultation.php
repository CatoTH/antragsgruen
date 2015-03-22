<?php

namespace app\models\settings;

use app\models\exceptions\FormError;

class Consultation
{
    /** @var bool */
    public $motionNeedsEmail      = false;
    public $motionNeedsPhone      = false;
    public $motionHasPhone        = false;
    public $commentNeedsEmail     = false;
    public $iniatorsMayEdit       = false;
    public $adminsMayEdit         = true;
    public $maintainanceMode      = false;
    public $confirmEmails         = false;
    public $lineNumberingGlobal   = false;
    public $hideRevision          = false;
    public $minimalisticUI        = false;
    public $showFeeds             = true;
    public $commentsSupportable   = false;
    public $screeningMotions      = false;
    public $screeningMotionsShown = false;
    public $screeningAmendments   = false;
    public $screeningComments     = false;
    public $initiatorsMayReject   = false;
    public $titleHasLineNumber    = true;
    public $hasPDF                = true;
    public $commentWholeMotions   = false;
    public $allowMultipleTags     = false;
    public $allowStrikeFormat     = false;

    /** @var int */
    public $titleMaxLength        = 0;
    public $lineLength            = 80;
    public $startLayoutType       = 0;

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
}
