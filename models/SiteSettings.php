<?php

namespace app\models;

class SiteSettings
{

    /** @var bool */
    public $onlyNamespacedAccounts = false;
    public $onlyWurzelwerk         = false;

    /** @var int */
    public $willingToPay = 0;

    const PAYS_NOT   = 0;
    const PAYS_MAYBE = 1;
    const PAYS_YES   = 2;

    /**
     * @return string[]
     */
    public static function getPaysValues()
    {
        return array(
            2 => "Ja",
            0 => "Nein",
            1 => "Will mich später entscheiden"
        );
    }

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
        $fields = get_object_vars($this);
        foreach ($fields as $key => $val) {
            if (isset($formdata[$key])) {
                if (is_bool($val)) {
                    $this->$key = (bool)$formdata[$key];
                } elseif (is_int($val)) {
                    $this->$key = (int)$formdata[$key];
                } else {
                    $this->$key = $formdata[$key];
                }
            }
        }
    }
}
