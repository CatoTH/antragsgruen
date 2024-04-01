<?php

namespace app\models\settings;

class Stylesheet implements \JsonSerializable
{
    use JsonConfigTrait;

    public const TYPE_COLOR = 'color';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_PIXEL = 'pixel';
    public const TYPE_NUMBER = 'number';
    public const TYPE_FONT = 'font';
    public const TYPE_IMAGE = 'image';

    public const DEFAULTS_LAYOUT_CLASSIC = 'layout-classic';
    public const DEFAULTS_LAYOUT_DBJR = 'layout-dbjr';

    public string $bodyFont;
    public int $bodyFontSize;
    public int $containerSize;
    public string $colorLinks;
    public string $colorLinksLight;
    public string $colorDelLink;
    public string $brandPrimary;
    public string $buttonFont;
    public string $buttonSuccessBackground;
    public string $textColor;
    public int $sidebarWidth;
    public string $sidebarBackground;
    public string $sidebarActionFont;
    public string$createMotionBtnColor;
    public string$bookmarkAmendmentBackground;
    public string $bookmarkCommentColor;
    public string $headingFont;
    public string $headingPrimaryText;
    public string $headingPrimaryBackground;
    public int $headingPrimarySize;
    public string $headingSecondaryText;
    public string $headingSecondaryBackground;
    public int $headingSecondarySize;
    public string $headingTertiaryText;
    public string $headingTertiaryBackground;
    public int $headingTertiarySize;
    public bool $headingFontUppercase;
    public bool $headingFontBold;
    public bool $headingTextShadow;
    public bool $linkTextDecoration;
    public bool $useBoxShadow;
    public int $contentBorderRadius;
    public ?string $backgroundImage;
    public string $menuActive;
    public string $menuLink;
    public string $menuFont;
    public string $motionFixedFontColor;
    public string $motionFixedFont;
    public int $motionStdFontSize;
    public bool $uppercaseTitles;

    public const DEFAULTS_CLASSIC = [
        'useBoxShadow'                => true,
        'containerSize'               => 1024,
        'contentBorderRadius'         => 10,
        'sidebarWidth'                => 256,
        'sidebarBackground'           => '#D40074',
        'sidebarActionFont'           => '"Open Sans", sans-serif',
        'createMotionBtnColor'        => '#D40074',
        'bookmarkAmendmentBackground' => '#afcb08',
        'bookmarkCommentColor'        => '#D40074',
        'headingFont'                 => '"Open Sans", sans-serif',
        'headingPrimaryText'          => '#ffffff',
        'headingPrimaryBackground'    => '#285f19',
        'headingPrimarySize'          => 15,
        'headingSecondaryText'        => '#444',
        'headingSecondaryBackground'  => '#afcb08',
        'headingSecondarySize'        => 15,
        'headingTertiaryText'         => '#000000',
        'headingTertiaryBackground'   => '#1b4afb',
        'headingTertiarySize'         => 15,
        'headingFontUppercase'        => true,
        'headingFontBold'             => true,
        'headingTextShadow'           => true,
        'menuFont'                    => '"Open Sans", sans-serif',
        'menuLink'                    => '#4b7000',
        'menuActive'                  => '#646464',
        'backgroundImage'             => '',
        'bodyFont'                    => '"Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu sans", "Helvetica Neue", Helvetica, Arial, sans-serif',
        'bodyFontSize'                => 14,
        'textColor'                   => '#484649',
        'colorLinks'                  => '#6d7e00',
        'colorLinksLight'             => '#6d7e00',
        'colorDelLink'                => '#FF7777',
        'linkTextDecoration'          => false,
        'motionFixedFont'             => '"VeraMono", Consolas, Courier, sans-serif',
        'motionFixedFontColor'        => '#222',
        'motionStdFontSize'           => 14,
        'brandPrimary'                => '#D40074',
        'buttonSuccessBackground'     => '#2c882c',
        'buttonFont'                  => '"Open Sans", sans-serif',
        'uppercaseTitles'             => false,
    ];

    public const DEFAULTS_DBJR = [
        'useBoxShadow'                => true,
        'containerSize'               => 1024,
        'contentBorderRadius'         => 10,
        'sidebarWidth'                => 256,
        'sidebarBackground'           => '#dd0b18',
        'sidebarActionFont'           => '"FiraSans", sans-serif',
        'createMotionBtnColor'        => '#dd0b18',
        'bookmarkAmendmentBackground' => '#dd0b18',
        'bookmarkCommentColor'        => '#008000',
        'headingFont'                 => '"FiraSans", sans-serif',
        'headingPrimaryText'          => '#ffffff',
        'headingPrimaryBackground'    => '#404040',
        'headingPrimarySize'          => 15,
        'headingSecondaryText'        => '#333333',
        'headingSecondaryBackground'  => '#c8c8c8',
        'headingSecondarySize'        => 15,
        'headingTertiaryText'         => '#000000',
        'headingTertiaryBackground'   => '#dcdcdc',
        'headingTertiarySize'         => 15,
        'headingFontUppercase'        => true,
        'headingFontBold'             => true,
        'headingTextShadow'           => true,
        'menuFont'                    => '"FiraSans", sans-serif',
        'menuLink'                    => '#646464',
        'menuActive'                  => '#333333',
        'backgroundImage'             => '',
        'bodyFont'                    => '"FiraSans", "Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu sans", "Helvetica Neue", Helvetica, Arial, sans-serif',
        'bodyFontSize'                => 14,
        'textColor'                   => '#484649',
        'colorLinks'                  => '#dd0b18',
        'colorLinksLight'             => '#dd0b18',
        'colorDelLink'                => '#dd0b18',
        'linkTextDecoration'          => false,
        'motionFixedFont'             => '"VeraMono", Consolas, Courier, sans-serif',
        'motionFixedFontColor'        => '#222',
        'motionStdFontSize'           => 14,
        'brandPrimary'                => '#dd0b18',
        'buttonSuccessBackground'     => '#dd0b18',
        'buttonFont'                  => '"FiraSans", sans-serif',
        'uppercaseTitles'             => false,
    ];

    public static function getAllSettings(string $defaults = 'layout-classic'): array
    {
        $settings = [
            'useBoxShadow'                => [
                'group'    => 'layout',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'use-box-shadow',
            ],
            'containerSize'               => [
                'group'    => 'layout',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'container-md',
            ],
            'contentBorderRadius'         => [
                'group'    => 'layout',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'contentBorderRadius',
            ],
            'sidebarBackground'           => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'sidebarBackground',
            ],
            'sidebarWidth'                => [
                'group'    => 'layout',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'sidebarWidth',
            ],
            'sidebarActionFont'           => [
                'group'    => 'layout',
                'type'     => self::TYPE_FONT,
                'scssName' => 'sidebarActionFont',
            ],
            'createMotionBtnColor'        => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'createMotionBtnColor',
            ],
            'bookmarkAmendmentBackground' => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'bookmarkAmendmentBackground',
            ],
            'bookmarkCommentColor'        => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'bookmarkCommentColor',
            ],
            'headingFont'                 => [
                'group'    => 'headings',
                'type'     => self::TYPE_FONT,
                'scssName' => 'headingFont',
            ],
            'headingPrimaryText'          => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingPrimaryText',
            ],
            'headingPrimaryBackground'    => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingPrimaryBackground',
            ],
            'headingPrimarySize'          => [
                'group'    => 'headings',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'headingPrimarySize',
            ],
            'headingSecondaryText'        => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingSecondaryText',
            ],
            'headingSecondaryBackground'  => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingSecondaryBackground',
            ],
            'headingSecondarySize'        => [
                'group'    => 'headings',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'headingSecondarySize',
            ],
            'headingTertiaryText'         => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingTertiaryText',
            ],
            'headingTertiaryBackground'   => [
                'group'    => 'headings',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'headingTertiaryBackground',
            ],
            'headingTertiarySize'         => [
                'group'    => 'headings',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'headingTertiarySize',
            ],
            'headingFontUppercase'        => [
                'group'    => 'headings',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'headingFontUppercase',
            ],
            'headingFontBold'             => [
                'group'    => 'headings',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'headingFontBold',
            ],
            'headingTextShadow'           => [
                'group'    => 'headings',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'headingTextShadow',
            ],
            'uppercaseTitles'             => [
                'group'    => 'headings',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'uppercaseTitles',
            ],
            'menuFont'                    => [
                'group'    => 'layout',
                'type'     => self::TYPE_FONT,
                'scssName' => 'menuFont',
            ],
            'menuLink'                    => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'menuLink',
            ],
            'menuActive'                  => [
                'group'    => 'layout',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'menuActive',
            ],
            'backgroundImage'             => [
                'group'    => 'layout',
                'type'     => self::TYPE_IMAGE,
                'scssName' => 'backgroundImage',
            ],
            'bodyFont'                    => [
                'group'    => 'text',
                'type'     => self::TYPE_FONT,
                'scssName' => 'bodyFont',
            ],
            'bodyFontSize'                => [
                'group'    => 'text',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'font-size-base',
            ],
            'textColor'                   => [
                'group'    => 'text',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'textColor',
            ],
            'colorLinks'                  => [
                'group'    => 'text',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'colorLinks',
            ],
            'colorLinksLight'             => [
                'group'    => 'text',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'colorLinksLight',
            ],
            'colorDelLink'                => [
                'group'    => 'text',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'colorDelLink',
            ],
            'linkTextDecoration'          => [
                'group'    => 'text',
                'type'     => self::TYPE_CHECKBOX,
                'scssName' => 'linkTextDecoration',
            ],
            'motionFixedFont'             => [
                'group'    => 'text',
                'type'     => self::TYPE_FONT,
                'scssName' => 'motionFixedFont',
            ],
            'motionFixedFontColor'        => [
                'group'    => 'text',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'motionFixedFontColor',
            ],
            'motionStdFontSize'           => [
                'group'    => 'text',
                'type'     => self::TYPE_PIXEL,
                'scssName' => 'motionStdFontSize',
            ],
            'brandPrimary'                => [
                'group'    => 'buttons',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'brand-primary',
            ],
            'buttonSuccessBackground'     => [
                'group'    => 'buttons',
                'type'     => self::TYPE_COLOR,
                'scssName' => 'btn-success-bg',
            ],
            'buttonFont'                  => [
                'group'    => 'buttons',
                'type'     => self::TYPE_FONT,
                'scssName' => 'buttonFont',
            ],
        ];

        $defaultsArr = ($defaults === self::DEFAULTS_LAYOUT_DBJR ? self::DEFAULTS_DBJR : self::DEFAULTS_CLASSIC);
        foreach ($defaultsArr as $key => $value) {
            $settings[$key]['default'] = $value;
        }

        return $settings;
    }

    public function __construct(array $data)
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    public function getValue(string $field, string $defaults): string
    {
        $type = (string)(new \ReflectionProperty(self::class, $field))->getType();

        if ($type === 'string' && (!isset($this->$field) || trim($this->$field) === '') && $field !== 'backgroundImage') {
            // Empty strings are only allowed for backgroundImage
            return (string)self::getAllSettings($defaults)[$field]['default'];
        } elseif (isset($this->$field)) {
            return (string)$this->$field;
        } else {
            return (string)self::getAllSettings($defaults)[$field]['default'];
        }
    }

    public function toScssVariables(string $defaults): string
    {
        $scss = '';
        foreach (self::getAllSettings() as $key => $data) {
            switch ($data['type']) {
                case self::TYPE_PIXEL:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key, $defaults) . "px;\n";
                    break;
                case self::TYPE_CHECKBOX:
                    if ($key === 'linkTextDecoration') {
                        $scss .= '$linkTextDecoration: ' . ($this->getValue($key, $defaults) ? 'underline' : 'none') . ";\n";
                    } else {
                        $scss .= '$' . $data['scssName'] . ': ' . ($this->getValue($key, $defaults) ? 'true' : 'false') . ";\n";
                    }
                    break;
                case self::TYPE_NUMBER:
                case self::TYPE_COLOR:
                case self::TYPE_FONT:
                    $scss .= '$' . $data['scssName'] . ': ' . $this->getValue($key, $defaults) . ";\n";
                    break;
            }
        }

        return $scss;
    }

    public function getSettingsHash(): string
    {
        return sha1((string)json_encode($this));
    }
}
