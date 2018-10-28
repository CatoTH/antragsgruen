<?php

namespace app\models\settings;

class Stylesheet implements \JsonSerializable
{
    use JsonConfigTrait;

    const TYPE_COLOR = 'color';

    public $colorLinks;
    public $colorLinksLight;
    public $brandPrimary;
    public $textColor;
    public $headingPrimaryText         = 'rgb(255, 255, 255)';
    public $headingPrimaryBackground   = 'rgb(40, 95, 25)';
    public $headingSecondaryText       = 'rgb(255, 255, 255)';
    public $headingSecondaryBackground = 'rgb(175, 203, 8)';
    public $headingTertiaryText        = 'black';
    public $headingTertiaryBackground  = 'rgb(27, 74, 251)';

    /**
     * @return array
     */
    public static function getAllSettings()
    {
        return [
            'colorLinks'      => [
                'default'  => '#6d7e00',
                'title'    => 'Color of links (normal)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinks',
            ],
            'colorLinksLight' => [
                'default'  => '#6d7e00',
                'title'    => 'Color of links (light)',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'colorLinksLight',
            ],
            'brandPrimary'    => [
                'default'  => '#e2007a',
                'title'    => 'Color of primary buttons',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'brand-primary',
            ],
            'textColor'       => [
                'default'  => '#484649',
                'title'    => 'Default text color',
                'type'     => static::TYPE_COLOR,
                'scssName' => 'textColor',
            ]
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
        if ($this->$field) {
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
            $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key) . ";\n";
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
