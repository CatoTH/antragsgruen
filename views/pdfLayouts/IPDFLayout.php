<?php

namespace app\views\pdfLayouts;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, Motion, TexTemplate};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use TCPDF;

abstract class IPDFLayout
{
    public static function getAvailableTcpdfClasses(): array
    {
        $params = AntragsgruenApp::getInstance();

        $pdfClasses = [
            [
                'id'      => -1,
                'title'   => '- ' . \Yii::t('admin', 'pdf_templ_none') . ' -',
                'preview' => null,
            ],
            [
                'id'      => 0,
                'title'   => 'LDK Bayern',
                'preview' => $params->resourceBase . 'img/pdf_preview_byldk.png',
                'className'   => ByLDK::class,
            ],
            [
                'id'      => 1,
                'title'   => 'BDK',
                'preview' => $params->resourceBase . 'img/pdf_preview_bdk.png',
                'className'   => BDK::class,
            ],
            [
                'id'      => 2,
                'title'   => 'DBJR',
                'preview' => $params->resourceBase . 'img/pdf_preview_dbjr.png',
                'className'   => DBJR::class,
            ],
        ];
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $pdfClasses = $plugin::getProvidedPdfLayouts($pdfClasses);
        }

        return $pdfClasses;
    }

    private static ?array $_availableClassesWithLatex = null;

    public static function getAvailableClassesWithLatex(): array
    {
        if (self::$_availableClassesWithLatex) {
            return self::$_availableClassesWithLatex;
        }

        $return = [];
        foreach (self::getAvailableTcpdfClasses() as $data) {
            $return['php' . $data['id']] = $data;
        }

        $params = AntragsgruenApp::getInstance();
        if ($params->xelatexPath || $params->lualatexPath) {
            /** @var TexTemplate[] $texLayouts */
            $texLayouts = TexTemplate::find()->all();
            foreach ($texLayouts as $layout) {
                if ($layout->id === 1) {
                    $preview = $params->resourceBase . 'img/pdf_preview_latex_bdk.png';
                } else {
                    $preview = null;
                }
                $return[$layout->id] = [
                    'title'   => $layout->title,
                    'preview' => $preview,
                ];
            }
        }

        self::$_availableClassesWithLatex = $return;

        return $return;
    }

    /**
     * @return class-string<IPDFLayout>|null
     * @throws Internal
     */
    public static function getClassById(int $classId): ?string
    {
        if ($classId === -1) {
            return null;
        }
        foreach (static::getAvailableClassesWithLatex() as $data) {
            if (isset($data['id']) && $data['id'] === $classId) {
                return $data['className'];
            }
        }
        throw new Internal('Unknown PDF Layout');
    }

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

            $scaleWidth = $scaleHeight = 1;
            if ($maxWidth && $logo->width > $maxWidth) {
                $scaleWidth = $maxWidth / $logo->width;
            }
            if ($maxHeight && $logo->height > $maxHeight) {
                $scaleHeight = $maxHeight / $logo->height;
            }
            $this->headerlogo['scale'] = min($scaleHeight, $scaleWidth);
            $this->headerlogo['w']     = $logo->width * $this->headerlogo['scale'];
            $this->headerlogo['h']     = $logo->height * $this->headerlogo['scale'];
            $this->headerlogo['x']     = $dim['wk'] - $dim['rm'] - $this->headerlogo['w'];
            $this->headerlogo['data']  = $logo->data;
            if ($this->headerlogo['h'] + $abs < $dim['tm'] / 2) {
                $this->headerlogo['y'] = $dim['tm'] - $this->headerlogo['h'] - $abs;
            } else {
                $this->headerlogo['y'] = $dim['tm'];
            }
        }
    }

    abstract public function createPDFClass(): IPdfWriter;

    abstract public function printMotionHeader(Motion $motion): void;

    abstract public function printAmendmentHeader(Amendment $amendment): void;
}
