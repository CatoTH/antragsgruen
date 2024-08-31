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

    /** @var class-string<IPDFLayout|IHtmlToPdfLayout>|null */
    public ?string $className;

    /**
     * @param class-string<IPDFLayout|IHtmlToPdfLayout>|null $className
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
        return $this->renderer === self::RENDERER_WEASYPRINT;
    }

    public function getHtmlId(): string
    {
        if ($this->isHtmlToPdfLayout()) {
            return 'html2pdf' . $this->id;
        } elseif ($this->id !== null) {
            return 'php' . $this->id;
        } else {
            return 'latex' . $this->latexId;
        }
    }
}
