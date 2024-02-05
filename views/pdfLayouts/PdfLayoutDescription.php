<?php

declare(strict_types=1);

namespace app\views\pdfLayouts;

class PdfLayoutDescription
{
    public ?int $id;
    public ?int $latexId;
    public string $title;
    public ?string $preview;

    /** @var class-string<IPDFLayout>|null */
    public ?string $className;

    /**
     * @param class-string<IPDFLayout>|null $className
     */
    public function __construct(?int $id, ?int $latexId, string $title, ?string $preview, ?string $className)
    {
        $this->id = $id;
        $this->latexId = $latexId;
        $this->title = $title;
        $this->preview = $preview;
        $this->className = $className;
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
