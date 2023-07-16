<?php

namespace app\views\pdfLayouts;

use yii\helpers\Html;

class BDKPDF extends IPdfWriter
{
    private string $headerTitle;
    private string $headerPrefix;

    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    }

    /**
     * rewrite AddPage() for correct functionalities with PDF Concatenation
     * @param string $orientation
     * @param string $format
     * @param bool $keepmargins
     * @param bool $tocpage
     */
    public function AddPage(
        $orientation = PDF_PAGE_ORIENTATION,
        $format = PDF_PAGE_FORMAT,
        $keepmargins = false,
        $tocpage = false,
        bool $footer = true
    ): void {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->setPrintFooter($footer);
    }

    public function setMotionTitle(string $prefix, string $title): void
    {
        $this->headerPrefix = $prefix;
        $this->headerTitle  = $title;
    }

    // @codingStandardsIgnoreStart

    public function Header(): void
    {
        if (count($this->pagegroups) === 0) {
            // This is most likely a PDF-only application => we don't need the header
            return;
        }

        $this->SetFont('helvetica', '', 10);
        $title = '<span style="font-size: 16px;">' . Html::encode($this->headerPrefix) . ' </span>';
        $title .= '<span style="font-size: 14px;">' . Html::encode($this->headerTitle) . '</span>';
        $this->writeHTMLCell(
            170,
            10,
            25,
            5,
            $title,
            ['B' => ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]]],
            1,
            false,
            true,
            'C'
        );
    }

    public function Footer(): void
    {
        if (count($this->pagegroups) === 0) {
            // This is most likely a PDF-only application => we don't need page numbers
            return;
        }

        $this->SetY(-15);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(
            185,
            10,
            \Yii::t('export', 'Page') . ' ' . $this->getGroupPageNo() . ' / ' . $this->getPageGroupAlias(),
            0,
            0,
            'R',
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
