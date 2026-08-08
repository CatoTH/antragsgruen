<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    public const PUBLIC_NO = 0;
    public const PUBLIC_YES = 1;

    public const CHOICE_TYPE_SELECT = 'select';
    public const CHOICE_TYPE_RADIO = 'radio';

    public int $imgMaxWidth = 0;
    public int $imgMaxHeight = 0;

    public string $choiceType = self::CHOICE_TYPE_SELECT;
    /** @param string[] $choices */
    public ?array $choices = null;

    public bool $showInHtml = false; // Used for titles and PDF-alternatives
    public int $public = MotionSection::PUBLIC_YES;
    public bool $isRtl = false; // If true, the text is to be shown in Right-to-Left direction

    public ?string $explanationHtml = null; // shown above the input field

    // If set, this section only applies to motions/amendments viewed/submitted in this language.
    // If null, the section applies regardless of the language (default).
    public ?string $language = null;

    // Sections sharing the same (non-empty) languageGrouping are translations of each other:
    // they are meant to hold the same content, just in different languages.
    public ?string $languageGrouping = null;
}
