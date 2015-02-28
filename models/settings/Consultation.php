<?php

namespace app\models\settings;

class Consultation
{
    /** @var bool */
    public $motionNeedsEmail  = false;
    public $motionNeedsPhone  = false;
    public $motionHasPhone    = false;
    public $commentNeedsEmail = false;

    public $iniatorsMayEdit = false;
    public $adminsMayEdit   = true;

    public $maintainanceMode      = false;
    public $confirmEmails         = false;
    public $lineNumberingGlobal   = false;
    public $amendNumberingGlobal  = false;
    public $amendNumberingByLine  = false;
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
    public $lineLength            = 80;
    public $startLayoutType       = 0;
    public $labelButtonNew        = "";
    public $commentWholeMotions   = false;
    public $allowMultipleTags     = false;

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
     */
    public function saveForm($formdata)
    {
        if (!isset($formdata["einstellungsfelder"])) {
            return;
        }

        $fields = get_object_vars($this);
        var_dump($fields);
        foreach ($formdata["einstellungsfelder"] as $key) {
            if (!array_key_exists($key, $fields)) {
                die("Ungültiges Feld: " . $key);
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

        /*
        if (isset($_REQUEST["antrags_typen_aktiviert"])) {
            $this->antrags_typen_deaktiviert = array();
            foreach (Motion::$TYPEN as $id => $name) if (!in_array($id, $_REQUEST["antrags_typen_aktiviert"]))
        $this->antrags_typen_deaktiviert[] = IntVal($id);
        }
        */

    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'ae_nummerierung_global'     => 'ÄA-Nummerierung für die ganze Veranstaltung',
            'zeilen_nummerierung_global' => 'Zeilennummerierung durchgehend für die ganze Veranstaltung',
            'bestaetigungs_emails'       => 'Bestätigungs-E-Mails an die NutzerInnen schicken'
        );
    }
}
