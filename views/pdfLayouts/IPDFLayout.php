<?php

declare(strict_types=1);

namespace app\views\pdfLayouts;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, Motion, TexTemplate};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use TCPDF;

abstract class IPDFLayout
{
    public const LAYOUT_NONE = -1;
    public const LAYOUT_WEASYPRINT_DEFAULT = 3;

    public static function getTcpdfDefaultLayout(): PdfLayoutDescription
    {
        $params = AntragsgruenApp::getInstance();

        return new PdfLayoutDescription(0, PdfLayoutDescription::RENDERER_PHP, null, 'LDK Bayern', $params->resourceBase . 'img/pdf_preview_byldk.png', ByLDK::class);
    }

    public static function getWeasyprintDefaultLayout(): PdfLayoutDescription
    {
        $params = AntragsgruenApp::getInstance();

        return new PdfLayoutDescription(
            self::LAYOUT_WEASYPRINT_DEFAULT,
            PdfLayoutDescription::RENDERER_WEASYPRINT,
            null,
            'Default',
            $params->resourceBase . 'img/pdf_preview_latex_bdk.png',
            null
        );
    }

    /**
     * @return array<PdfLayoutDescription>
     */
    public static function getAvailableTcpdfClasses(): array
    {
        $params = AntragsgruenApp::getInstance();

        $pdfClasses = [
            new PdfLayoutDescription(-1, PdfLayoutDescription::RENDERER_NONE, null, '- ' . \Yii::t('admin', 'pdf_templ_none') . ' -', null, null),
            self::getTcpdfDefaultLayout(),
            new PdfLayoutDescription(1, PdfLayoutDescription::RENDERER_PHP, null, 'BDK', $params->resourceBase . 'img/pdf_preview_bdk.png', BDK::class),
            new PdfLayoutDescription(2, PdfLayoutDescription::RENDERER_PHP, null, 'DBJR', $params->resourceBase . 'img/pdf_preview_dbjr.png', DBJR::class),
        ];
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $pdfClasses = $plugin::getProvidedPdfLayouts($pdfClasses);
        }

        return $pdfClasses;
    }

    /** @var array<PdfLayoutDescription>|null */
    private static ?array $_availablePdfLayouts = null;

    /**
     * @return PdfLayoutDescription[]
     */
    public static function getSelectablePdfLayouts(): array
    {
        if (self::$_availablePdfLayouts) {
            return self::$_availablePdfLayouts;
        }

        $return = [];
        foreach (self::getAvailableTcpdfClasses() as $data) {
            $return[] = $data;
        }

        $params = AntragsgruenApp::getInstance();
        if ($params->weasyprintPath) {
            $return[] = self::getWeasyprintDefaultLayout();
        } elseif ($params->xelatexPath || $params->lualatexPath) {
            /** @var TexTemplate[] $texLayouts */
            $texLayouts = TexTemplate::find()->all();
            foreach ($texLayouts as $layout) {
                if ($layout->id === 1) {
                    $preview = $params->resourceBase . 'img/pdf_preview_latex_bdk.png';
                } else {
                    $preview = null;
                }
                $return[] = new PdfLayoutDescription(null, PdfLayoutDescription::RENDERER_LATEX, $layout->id,  $layout->title, $preview, null);
            }
        }

        self::$_availablePdfLayouts = $return;

        return $return;
    }

    public static function getPdfLayoutForMotionType(ConsultationMotionType $motionType): PdfLayoutDescription
    {
        $weasyPrintActive = AntragsgruenApp::getInstance()->weasyprintPath !== null;

        foreach (self::getSelectablePdfLayouts() as $layout) {
            $isWeasyprint = $layout->isHtmlToPdfLayout();
            if ($weasyPrintActive) {
                if ($isWeasyprint && $motionType->texTemplateId !== null) {
                    return $layout;
                }
            } else {
                if ($layout->latexId !== null && $motionType->texTemplateId === $layout->latexId) {
                    return $layout;
                }
            }
            if ($motionType->texTemplateId === null && $layout->id === $motionType->pdfLayout) {
                return $layout;
            }
        }

        if (AntragsgruenApp::getInstance()->weasyprintPath) {
            return self::getWeasyprintDefaultLayout();
        } else {
            return self::getTcpdfDefaultLayout();
        }
    }

    /**
     * @throws Internal
     */
    public static function getClassById(int $classId): ?PdfLayoutDescription
    {
        if ($classId === -1) {
            return null;
        }
        foreach (static::getSelectablePdfLayouts() as $data) {
            if ($data->id !== null && $data->id === $classId) {
                return $data;
            }
        }
        throw new Internal('Unknown PDF Layout');
    }

    /** @var array{scale: float, x: float, y: float, w: float, h: float, data: string}|null */
    protected ?array $headerlogo = null;

    protected TCPDF $pdf;

    public function __construct(
        protected ConsultationMotionType $motionType
    ) {
    }

    public function printSectionHeading(string $text): void
    {
        $this->pdf->setFont('helvetica', '', 12);
        $this->pdf->ln(2);
        $this->pdf->MultiCell(0, 0, '<h4>' . $text . '</h4>', 0, 'L', false, 1, null, null, true, 0, true);
    }

    protected function setHeaderLogo(Consultation $consultation, int $abs, ?float $maxWidth, ?float $maxHeight): void
    {
        $logo = $consultation->getAbsolutePdfLogo();
        if ($logo && !$this->headerlogo) {
            $dim = $this->pdf->getPageDimensions();

            $scaleWidth = $scaleHeight = 1.0;
            if ($maxWidth && $logo->width > $maxWidth) {
                $scaleWidth = $maxWidth / $logo->width;
            }
            if ($maxHeight && $logo->height > $maxHeight) {
                $scaleHeight = $maxHeight / $logo->height;
            }

            $logoData = [];
            $logoData['scale'] = min($scaleHeight, $scaleWidth);
            $logoData['w'] = $logo->width * $logoData['scale'];
            $logoData['h'] = $logo->height * $logoData['scale'];
            $logoData['x'] = $dim['wk'] - $dim['rm'] - $logoData['w'];
            $logoData['data'] = $logo->data;
            if ($logoData['h'] + $abs < $dim['tm'] / 2) {
                $logoData['y'] = $dim['tm'] - $logoData['h'] - $abs;
            } else {
                $logoData['y'] = $dim['tm'];
            }
            $this->headerlogo = $logoData;
        }
    }

    abstract public function createPDFClass(): IPdfWriter;

    abstract public function printMotionHeader(Motion $motion): void;

    abstract public function printAmendmentHeader(Amendment $amendment): void;
}
