<?php

namespace app\models\wording;

use app\models\exceptions\Internal;

abstract class IWording
{
    const WORDING_PARTEITAG = 0;

    /**
     * @return IWording[]
     */
    public static function getWordings()
    {
        return [
            static::WORDING_PARTEITAG => Parteitag::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getWordingNames()
    {
        $names = [];
        foreach (static::getWordings() as $key => $pol) {
            $names[$key] = $pol::getWordingName();
        }
        return $names;
    }

    /**
     * @param string $pageKey
     * @return PageData|null
     */
    abstract protected function getPageDataInternal($pageKey);

    /**
     * @param string $pageKey
     * @return PageData
     * @throws Internal
     */
    public function getPageData($pageKey)
    {
        $internal = $this->getPageDataInternal($pageKey);
        if ($internal) {
            return $internal;
        } else {
            switch ($pageKey) {
                case 'maintainance':
                    $data = new PageData();
                    $data->pageTitle = 'Wartungsmodus';
                    $data->breadcrumbTitle = 'Wartungsmodus';
                    $data->text = '<p>Diese Veranstaltung wurde vom Admin noch nicht freigeschaltet.</p>';
                    return $data;
                    break;
                case 'help':
                    $data = new PageData();
                    $data->pageTitle = 'Hilfe';
                    $data->breadcrumbTitle = 'Hilfe';
                    $data->text = '<p>Hilfe...</p>';
                    return $data;
                    break;
                case 'legal':
                    $data = new PageData();
                    $data->pageTitle = 'Impressum';
                    $data->breadcrumbTitle = 'Impressum';
                    $data->text = '<p>Impressum</p>';
                    return $data;
                    break;
                case 'welcome':
                    $data = new PageData();
                    $data->pageTitle = 'Willkommen';
                    $data->breadcrumbTitle = 'Willkommen';
                    $data->text = '<p>Hallo auf Antragsgrün</p>';
                    return $data;
                    break;
                default:
                    throw new Internal('Unknown page Key: ' . $pageKey);
            }
        }
    }


    protected $translations = [];

    /**
     * @param string $strTitle
     * @return string
     */
    public function get($strTitle)
    {
        if (isset($this->translations[$strTitle])) {
            return $this->translations[$strTitle];
        }
        return $strTitle;
    }


    /**
     * @static
     * @abstract
     * @return int
     */
    public static function getWordingID()
    {
        return -1;
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getWordingName()
    {
        return "";
    }
}
