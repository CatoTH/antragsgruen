<?php

namespace app\models\settings;

class Stylesheet implements \JsonSerializable
{
    use JsonConfigTrait;

    const TYPE_COLOR    = 'color';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_PIXEL    = 'pixel';
    const TYPE_NUMBER   = 'number;';

    public $colorLinks;
    public $colorLinksLight;
    public $brandPrimary;
    public $textColor;
    public $headingPrimaryText;
    public $headingPrimaryBackground;
    public $headingSecondaryText;
    public $headingSecondaryBackground;
    public $headingTertiaryText;
    public $headingTertiaryBackground;

    public $useBoxShadow;
    public $contentBorderRadius;

    /**
     * @return array
     */
    public static function getAllSettings()
    {
        return [
            'colorLinks'          => [
                'default'  => '#6d7e00',
                'title'    => 'Color of links (normal)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinks',
            ],
            'colorLinksLight'     => [
                'default'  => '#6d7e00',
                'title'    => 'Color of links (light)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinksLight',
            ],
            'brandPrimary'        => [
                'default'  => '#e2007a',
                'title'    => 'Color of primary buttons',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'brand-primary',
            ],
            'textColor'           => [
                'default'  => '#484649',
                'title'    => 'Default text color',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'textColor',
            ],
            'useBoxShadow'        => [
                'default'  => true,
                'title'    => 'Box shadows',
                'type'     => static::TYPE_CHECKBOX,
                'scssName' => 'use-box-shadow',
            ],
            'contentBorderRadius' => [
                'default'  => 10,
                'title'    => 'Content border radius (px)',
                'type'     => static::TYPE_PIXEL,
                'scssName' => 'contentBorderRadius',
            ],

        ];
    }

    /**
     * Stylesheet constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @param string $field
     * @return string
     */
    public function getValue($field)
    {
        if ($this->$field !== null) {
            return $this->$field;
        } else {
            return static::getAllSettings()[$field]['default'];
        }
    }

    /**
     * @return string
     */
    public function toScssVariables()
    {
        $scss = '';
        foreach (static::getAllSettings() as $key => $data) {
            switch ($data['type']) {
                case static::TYPE_PIXEL:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key) . "px;\n";
                    break;
                case static::TYPE_CHECKBOX:
                    $scss .= '$' . $data['scssName'] . ': ' . ($this->getValue($key) ? 'true' : 'false') . ";\n";
                    break;
                case static::TYPE_NUMBER:
                case static::TYPE_COLOR:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key) . ";\n";
                    break;
            }
        }
        return $scss;
    }

    /**
     * @return string
     */
    public function getSettingsHash()
    {
        return sha1(json_encode($this));
    }
}
