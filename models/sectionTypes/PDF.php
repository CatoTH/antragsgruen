<?php

namespace app\models\sectionTypes;
use app\components\{latex\Content as LatexContent, html2pdf\Content as HtmlToPdfContent, Tools, UrlHelper};
use app\models\db\{Consultation, ConsultationSettingsMotionSection, MotionSection};
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\StreamReader;
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class PDF extends ISectionType
{
    public function getPdfUrl(bool $absolute = false, bool $showAlways = false): ?string
    {
        $app = AntragsgruenApp::getInstance();
        $externallySavedData = ($app->binaryFilePath !== null && trim($app->binaryFilePath) !== '');

        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion) {
            return null;
        }
        if (!$externallySavedData && !$section->getData()) {
            // If the data IS saved externally, we always create an URL, as it might be saved on a path
            // not accessible by the current process.
            return null;
        }

        $params = [
            'sectionId'  => $section->sectionId
        ];
        if ($showAlways) {
            $params['showAlways'] = $section->getShowAlwaysToken();
        }
        $url    = UrlHelper::createMotionUrl($motion, 'viewpdf', $params);
        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $url  = $this->getPdfUrl();
        $str  = '<section class="section' . $this->section->sectionId . ' type' . static::TYPE_PDF_ATTACHMENT . '">';
        $str .= '<div class="form-group">';

        $str .= $this->getFormLabel();
        $str .= $this->getHintsAfterFormLabel();

        if ($url) {
            $required = '';
        } else {
            $required = match($type->required) {
                ConsultationSettingsMotionSection::REQUIRED_YES => 'required',
                ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED => 'data-encouraged="true"',
                default => '',
            };
        }

        $maxSize = (int)floor(Tools::getMaxUploadSize() / 1024 / 1024);
        $str     .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign" aria-hidden="true"></span> ';
        $str     .= str_replace('%MB%', (string)$maxSize, \Yii::t('motion', 'max_size_hint'));
        $str     .= '</div>';

        $str .= '<input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">';

        if ($url) {
            $str .= '<a href="' . Html::encode($this->getPdfUrl()) . '" class="currentPdf">';
            $str .= \Yii::t('motion', 'pdf_current') . '</a>';
        }
        if ($url && $type->required !== ConsultationSettingsMotionSection::REQUIRED_YES) {
            $str .= '<label class="deletePdf"><input type="checkbox" name="sectionDelete[' . $type->id . ']">';
            $str .= \Yii::t('motion', 'pdf_delete');
            $str .= '</label>';
        }
        $str .= '</div>';
        $str .= '</section>';
        return $str;
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setMotionData($data): void
    {
        if (!isset($data['tmp_name'])) {
            throw new FormError('Invalid Image');
        }
        $mime = mime_content_type($data['tmp_name']);
        if (!in_array($mime, ['application/pdf'])) {
            throw new FormError('Please only upload PDFs.');
        }
        $metadata                = [
            'filesize' => filesize($data['tmp_name']),
        ];
        $this->section->setData((string)file_get_contents($data['tmp_name']));
        $this->section->metadata = json_encode($metadata, JSON_THROW_ON_ERROR);
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->metadata = '';
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data): void
    {
        $this->setMotionData($data);
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        return ''; // @TODO
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        if (!is_a($this->section, MotionSection::class)) {
            return ''; // PDF-Amendments are not supported
        }

        /** @var MotionSection $section */
        $section   = $this->section;
        $pdfUrl    = $this->getPdfUrl(false, $showAlways);
        $iframeUrl = UrlHelper::createMotionUrl($section->getMotion(), 'embeddedpdf', ['file' => $pdfUrl]);

        return '<iframe class="pdfViewer" src="' . Html::encode($iframeUrl) . '"></iframe>';
    }

    public function getMotionEmailHtml(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $url = $this->getPdfUrl(true);
        return '<a href="' . Html::encode($url) . '">' . \Yii::t('motion', 'pdf_current') . '</a>';
    }

    public function isEmpty(): bool
    {
        return ($this->section->getData() === '');
    }

    public function showIfEmpty(): bool
    {
        return false;
    }

    public function isFileUploadType(): bool
    {
        return true;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $params = AntragsgruenApp::getInstance();

        $abs = 5;
        $pdf->setY($pdf->getY() + $abs);

        if ($this->section->getSettings()->type === ISectionType::TYPE_PDF_ATTACHMENT) {
            $title = $this->getTitle();
            if (str_replace('pdf', '', strtolower($title)) === strtolower($title)) {
                $title .= ' [PDF]';
            }
            $pdfLayout->printSectionHeading($title);
        }

        $data = $this->section->getData();

        try {
            $pageCount = $pdf->setSourceFile(StreamReader::createByString($data));
        } catch (CrossReferenceException $e) {
            $pdf->AddPage();
            $pdf->writeHTML('<p style="font-size: 12px; color: red;"><br>The embedded PDF can not be rendered:</p>');
            /** @noinspection CssNoGenericFontName */
            $pdf->writeHTML('<p style="font-size: 12px; font-family: Courier; color: red;"><br>' .
                            Html::encode($e->getMessage()) . '</p>');

            return;
        }

        $lastprint = null;
        $pdim      = $pdf->getPageDimensions();
        $printArea = [
            'w' => $pdim['wk'] - ($pdim['lm'] + $pdim['rm']),
            'h' => $pdim['hk'] - ($pdim['tm'] + $pdim['bm']),
        ];
        $pdf->setX($pdim['lm']);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $page = $pdf->ImportPage($pageNo);

            /** @var array{width: float, height: float, orientation: string} $dim */
            $dim  = $pdf->getTemplatesize($page);
            if ($params->pdfExportConcat) {
                $pdf->AddPage($dim['width'] > $dim['height'] ? 'L' : 'P', [$dim['width'], $dim['height']], false);
                $pdf->useTemplate($page);
            } else {
                $scale = min([
                    1,
                    $printArea['w'] / $dim['width'],
                    $printArea['h'] / $dim['height'],
                ]);
                $print = [
                    'w' => $scale * $dim['width'],
                    'h' => $scale * $dim['height'],
                ];
                $curX  = $pdf->getX();
                if ($curX > $pdim['lm'] and $print['w'] < $pdim['wk'] - ($curX + $pdim['rm'])) {
                    $curX += $abs;
                    $curY = $pdf->getY() - $lastprint['h'];
                } else {
                    $curX = $pdim['lm'];
                    $curY = $pdf->getY() + $abs;
                }
                $print['x'] = $curX;
                if ($print['h'] < $pdim['hk'] - ($curY + $pdim['bm'])) {
                    $print['y'] = $curY;
                } else {
                    $pdf->AddPage();
                    $print['y'] = $pdim['tm'];
                }
                $pdf->useTemplate($page, $print['x'], $print['y'], $print['w'], $print['h']);

                if (is_numeric($params->pdfExportIntegFrame)) {
                    $border = ['all' => ['width' => $params->pdfExportIntegFrame, 'color' => [0, 0, 0], 'dash' => 0]];
                    $pdf->Rect($print['x'], $print['y'], $print['w'], $print['h'], 'D', $border);
                } elseif (is_array($params->pdfExportIntegFrame)) {
                    $config = $params->pdfExportIntegFrame;
                    $color  = [0, 0, 0];
                    if (isset($config['color'])) {
                        $color = $config['color'];
                        unset($config['color']);
                    }
                    if (isset($config['lw'])) {
                        $linewith = $config['lw'];
                        unset($config['lw']);
                    }
                    if (isset($config['abs'])) {
                        unset($config['abs']);
                    }
                    foreach ($config as $key => $length) {
                        if (in_array($key, ['tld', 'tlr', 'trd', 'trl', 'blu', 'blr', 'bru', 'brl'])) {
                            if (in_array(substr($key, -1), ['u', 'l'])) {
                                $length = -$length;
                            }
                            if (in_array(substr($key, -1), ['r', 'l'])) {
                                $length = $length * $print['w'];
                            } else {
                                $length = $length * $print['h'];
                            }
                            $larr = [];
                            if (in_array(substr($key, -1), ['u', 'd'])) {
                                $larr['x'] = 0;
                                $larr['y'] = $length;
                            } else {
                                $larr['x'] = $length;
                                $larr['y'] = 0;
                            }
                            if (str_starts_with($key, 't')) {
                                $line['y'] = $print['y'];
                            } else {
                                $line['y'] = $print['y'] + $print['h'];
                            }
                            if (substr($key, 1, 1) == 'l') {
                                $line['x'] = $print['x'];
                            } else {
                                $line['x'] = $print['x'] + $print['w'];
                            }
                            $styl = ['width' => $linewith, 'color' => $color];
                            $pdf->Line($line['x'], $line['y'], $line['x'] + $larr['x'], $line['y'] + $larr['y'], $styl);
                        }
                    }
                }

                $pdf->setXY($print['x'] + $print['w'], $print['y'] + $print['h']);
                $lastprint = $print;
            }
        }
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {
        return '[PDF]';
    }

    public function getAmendmentPlainText(): string
    {
        return '[PDF]';
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $filenameBase                         = uniqid('motion-pdf-attachment') . '.pdf';
        if ($this->section->getSettings()->type === ISectionType::TYPE_PDF_ATTACHMENT) {
            $content->attachedPdfs[$filenameBase] = $this->section->getData();
        }
        if ($this->section->getSettings()->type === ISectionType::TYPE_PDF_ALTERNATIVE) {
            $content->replacingPdf = $this->section->getData();
        }
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        // @TODO
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        // TODO: Implement printMotionHtml2Pdf() method.
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        // TODO: Implement printAmendmentHtml2Pdf() method.
    }

    public function getMotionODS(): string
    {
        return '<p>[PDF]</p>';
    }

    public function getAmendmentODS(): string
    {
        return '<p>[PDF]</p>';
    }

    public function printMotionToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    public function printAmendmentToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return false;
    }
}
