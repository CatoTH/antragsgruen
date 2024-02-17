<?php

declare(strict_types=1);

namespace app\models;

class SectionedParagraph
{
    public string $html;
    /** @var string[] */
    public array $lines;
    public int $paragraphWithoutLineSplit;
    public ?int $paragraphWithLineSplit;

    public function __construct(string $html, int $paragraphWithoutLineSplit, ?int $paragraphWithLineSplit = null)
    {
        $this->html = str_replace("\r", "", $html);
        $this->paragraphWithoutLineSplit = $paragraphWithoutLineSplit;
        $this->paragraphWithLineSplit = $paragraphWithLineSplit;
    }
}
