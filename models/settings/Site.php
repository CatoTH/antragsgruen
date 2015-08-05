<?php

namespace app\models\settings;

use app\models\exceptions\FormError;

class Site
{
    /** @var string */
    public $siteLayout = 'layout-classic';

    /** @var bool */
    public $showAntragsgruenAd  = true;
    public $forceLogin          = false;
    public $managedUserAccounts = false;

    /** @var int */
    public $willingToPay = 0;

    /** @var int[] */
    public $loginMethods = [0, 1, 3];

    /** @var null|string */
    public $emailReplyTo  = null;
    public $emailFromName = null;

    const PAYS_NOT   = 0;
    const PAYS_MAYBE = 1;
    const PAYS_YES   = 2;

    const LOGIN_STD        = 0;
    const LOGIN_WURZELWERK = 1;
    const LOGIN_NAMESPACED = 2;
    const LOGIN_EXTERNAL   = 3;

    public static $SITE_MANAGER_LOGIN_METHODS = [0, 1, 3];

    /**
     * @return string[]
     */
    public static function getPaysValues()
    {
        return [
            2 => 'Ja',
            0 => 'Nein',
            1 => 'Will mich später entscheiden'
        ];
    }

    /**
     * @param string|null $data
     */
    public function __construct($data)
    {
        if ($data == '') {
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
