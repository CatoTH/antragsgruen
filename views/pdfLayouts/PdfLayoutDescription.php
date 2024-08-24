<?php

declare(strict_types=1);

namespace app\views\pdfLayouts;

class PdfLayoutDescription
{
    public const RENDERER_NONE = 0;
    public const RENDERER_PHP = 1;
    public const RENDERER_LATEX = 2;
    public const RENDERER_WEASYPRINT = 3;

    public ?int $id;
    public int $renderer;
    public ?int $latexId;
    public string $title;
    public ?string $preview;

    /** @var class-string<IPDFLayout>|null */
    public ?string $className;

    /**
     * @param class-string<IPDFLayout>|null $className
     */
    public function __construct(?int $id, int $renderer, ?int $latexId, string $title, ?string $preview, ?string $className)
    {
        $this->id = $id;
        $this->renderer = $renderer;
        $this->latexId = $latexId;
        $this->title = $title;
        $this->preview = $preview;
        $this->className = $className;
    }

    public function isHtmlToPdfLayout(): bool
    {
        return $this->id === IPDFLayout::LAYOUT_WEASYPRINT_DEFAULT;
    }

    public function getHtmlId(): string
    {
        if ($this->id !== null) {
            return 'php' . $this->id;
        } else {
            return 'latex' . $this->latexId;
        }
    }
}
