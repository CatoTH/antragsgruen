<?php

namespace app\plugins\frauenrat\pdf;

use app\models\db\MotionSection;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};

class FrauenratPdf extends IPdfWriter
{
    /** @var IPDFLayout */
    private $layout;

    public $calibri;
    public $calibriBold;
    public $calibriItalic;
    public $calibriItalicBold;

    public function __construct(IPDFLayout $layout)
    {
        $this->layout = $layout;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->calibri           = \TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Calibri Regular.ttf', 'TrueTypeUnicode', '', 96);
        $this->calibriBold       = \TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Calibri Bold.ttf', 'TrueTypeUnicode', '', 96);
        $this->calibriItalic     = \TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Calibri Italic.ttf', 'TrueTypeUnicode', '', 96);
        $this->calibriItalicBold = \TCPDF_FONTS::addTTFfont(__DIR__ . '/../fonts/Calibri Bold Italic.ttf', 'TrueTypeUnicode', '', 96);
    }

    public function getMotionFont(MotionSection $section): string
    {
        return $this->calibri;
    }

    public function getMotionFontSize(MotionSection $section): int
    {
        return 12;
    }

    /**
     * rewrite AddPage() for correct functionalities with PDF Concatenation
     *
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
    ) {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->setPrintFooter($footer);
    }

    // @codingStandardsIgnoreStart

    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont($this->calibri, '', 8);
        // Page number
        $this->Cell(0, 10, $this->getGroupPageNo(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
    // @codingStandardsIgnoreEnd
}
