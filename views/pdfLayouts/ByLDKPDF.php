<?php

namespace app\views\pdfLayouts;

class ByLDKPDF extends IPdfWriter
{
    private IPDFLayout $layout;

    public function __construct(IPDFLayout $layout)
    {
        $this->layout  = $layout;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    /**
     * rewrite AddPage() for correct functionalities with PDF Concatenation
     * @param string $orientation
     * @param string $format
     * @param bool $keepmargins
     * @param bool $tocpage
     * @param bool $footer
     */
    public function AddPage(
        $orientation = PDF_PAGE_ORIENTATION,
        $format = PDF_PAGE_FORMAT,
        $keepmargins = false,
        $tocpage = false,
        $footer = true
    ): void {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->setPrintFooter($footer);
    }

    public function Header(): void
    {
        if (count($this->pagegroups) === 0) {
            // This is most likely a PDF-only application => we don't need page numbers
            return;
        }

        parent::Header();
    }

    // @codingStandardsIgnoreStart
    public function Footer(): void
    {
        if (count($this->pagegroups) === 0) {
            // This is most likely a PDF-only application => we don't need page numbers
            return;
        }

        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', '', 8);
        // Page number
        $this->Cell(
            0,
            10,
            \Yii::t('export', 'Page') . ' ' . $this->getGroupPageNo() . ' / ' . $this->getPageGroupAlias(),
            0,
            0,
            'C',
            false,
            '',
            0,
            false,
            'T',
            'M'
        );
    }
    // @codingStandardsIgnoreEnd
}
