<?php

namespace app\models\sectionTypes;

use app\components\{latex\Content, Tools, UrlHelper, VarStream};
use app\models\db\{Consultation, MotionSection};
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class PDF extends ISectionType
{
    /**
     * @param bool $absolute
     * @param bool $showAlways
     * @return null|string
     */
    public function getPdfUrl($absolute = false, $showAlways = false)
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion || !$section->getData()) {
            return null;
        }

        $params = [
            'motion/viewpdf',
            'motionSlug' => $section->getMotion()->getMotionSlug(),
            'sectionId'  => $section->sectionId
        ];
        if ($showAlways) {
            $params['showAlways'] = $section->getShowAlwaysToken();
        }
        $url    = UrlHelper::createUrl($params, $motion->getMyConsultation());
        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    public function getMotionFormField(): string
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $url  = $this->getPdfUrl();
        $str  = '<section class="section' . $this->section->sectionId . ' type' . static::TYPE_PDF_ATTACHMENT . '">';
        $str .= '<div class="form-group">';
        $str .= $this->getFormLabel();

        if ($url) {
            $required = false;
        } else {
            $required = ($type->required ? 'required' : '');
        }

        $maxSize = floor(Tools::getMaxUploadSize() / 1024 / 1024);
        $str     .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign"></span> ';
        $str     .= str_replace('%MB%', $maxSize, \Yii::t('motion', 'max_size_hint'));
        $str     .= '</div>';

        $str .= '<input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">';

        if ($url) {
            $str .= '<a href="' . Html::encode($this->getPdfUrl()) . '" class="currentPdf">';
            $str .= \Yii::t('motion', 'pdf_current') . '</a>';
        }
        if ($url && !$type->required) {
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
    public function setMotionData($data)
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
        $this->section->setData(base64_encode(file_get_contents($data['tmp_name'])));
        $this->section->metadata = json_encode($metadata);
    }

    public function deleteMotionData()
    {
        $this->section->setData('');
        $this->section->metadata = '';
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
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

    public function printMotionToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var AntragsgruenApp $params */
        $params = \yii::$app->params;

        $abs = 5;
        $pdf->setY($pdf->getY() + $abs);

        $title = $this->section->getSettings()->title;
        if (str_replace('pdf', '', strtolower($title)) == strtolower($title)) {
            $title .= ' [PDF]';
        }
        $pdf->writeHTML('<h3>' . $title . '</h3>');

        $data = base64_decode($this->section->getData());

        try {
            $pageCount = $pdf->setSourceFile(VarStream::createReference($data));
        } catch (CrossReferenceException $e) {
            $pdf->writeHTML('<p style="font-size: 12px; color: red;"><br>The embedded PDF can not be rendered:</p>');
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
            $dim  = $pdf->getTemplatesize($page);
            if ($params->pdfExportConcat) {
                $pdf->AddPage($dim['width'] > $dim['height'] ? 'L' : 'P', [$dim['width'], $dim['height']], false);
                $pdf->useTemplate($page);
            } else {
                $scale = min([
                    1,
                    $printArea['w'] / $dim['w'],
                    $printArea['h'] / $dim['h'],
                ]);
                $print = [
                    'w' => $scale * $dim['w'],
                    'h' => $scale * $dim['h'],
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
                            if (!$abs) {
                                if (in_array(substr($key, -1), ['r', 'l'])) {
                                    $length = $length * $print['w'];
                                } else {
                                    $length = $length * $print['h'];
                                }
                            }
                            $larr = [];
                            if (in_array(substr($key, -1), ['u', 'd'])) {
                                $larr['x'] = 0;
                                $larr['y'] = $length;
                            } else {
                                $larr['x'] = $length;
                                $larr['y'] = 0;
                            }
                            if (substr($key, 0, 1) == 't') {
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

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
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

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $filenameBase                         = uniqid('motion-pdf-attachment') . '.pdf';
        if ($this->section->getSettings()->type === ISectionType::TYPE_PDF_ATTACHMENT) {
            $content->attachedPdfs[$filenameBase] = base64_decode($this->section->getData());
        }
        if ($this->section->getSettings()->type === ISectionType::TYPE_PDF_ALTERNATIVE) {
            $content->replacingPdf = base64_decode($this->section->getData());
        }
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        // @TODO
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
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    public function printAmendmentToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        return false;
    }
}
