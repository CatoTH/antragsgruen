<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\UrlHelper;
use app\components\VarStream;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class PDF extends ISectionType
{
    /**
     * @return null|string
     */
    public function getPdfUrl()
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion || !$section->data) {
            return null;
        }

        $url = UrlHelper::createUrl(
            [
                'motion/viewpdf',
                'motionSlug' => $section->getMotion()->getMotionSlug(),
                'sectionId'  => $section->sectionId
            ]
        );
        return $url;
    }

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $str  = '';
        $url  = $this->getPdfUrl();
        if ($url) {
            $str      .= '<a href="' . Html::encode($this->getPdfUrl()) . '">Current PDF</a>';
            $required = false;
        } else {
            $required = ($type->required ? 'required' : '');
        }
        $str .= '<div class="form-group" style="overflow: auto;">';
        $str .= '
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">
        </div>';
        if ($url) {
            $str .= '<br style="clear: both;">';
        }
        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
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
        $this->section->data     = base64_encode(file_get_contents($data['tmp_name']));
        $this->section->metadata = json_encode($metadata);
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
    {
        return ''; // @TODO
    }

    /**
     * @param bool $isRight
     * @return string
     */
    public function getSimple($isRight)
    {
        if ($this->isEmpty()) {
            return '';
        }

        $pdfUrl    = $this->getPdfUrl();
        $iframeUrl = UrlHelper::createUrl(['motion/embeddedpdf', 'file' => $pdfUrl]);

        $str = '<iframe class="pdfViewer" src="' . Html::encode($iframeUrl) . '"></iframe>';

        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
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

        $data = base64_decode($this->section->data);

        $pageCount = $pdf->setSourceFile(VarStream::createReference($data));

        $pdim      = $pdf->getPageDimensions();
        $printArea = array(
            'w' => $pdim['wk'] - ($pdim['lm'] + $pdim['rm']),
            'h' => $pdim['hk'] - ($pdim['tm'] + $pdim['bm']),
        );
        $pdf->setX($pdim['lm']);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $page = $pdf->ImportPage($pageNo);
            $dim  = $pdf->getTemplatesize($page);
            if ($params->pdfExportConcat) {
                $pdf->AddPage($dim['w'] > $dim['h'] ? 'L' : 'P', array($dim['w'], $dim['h']), false, false, false);
                $pdf->useTemplate($page);
            } else {
                $scale = min(array(
                    1,
                    $printArea['w'] / $dim['w'],
                    $printArea['h'] / $dim['h'],
                ));
                $print = array(
                    'w' => $scale * $dim['w'],
                    'h' => $scale * $dim['h'],
                );
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
                    $pdf->Rect($print['x'], $print['y'], $print['w'], $print['h'], 'D', ['all' => ['width' => $params->pdfExportIntegFrame, 'color' => [0, 0, 0], 'dash' => 0]]);
                } elseif (is_array($params->pdfExportIntegFrame)) {
                    $config   = $params->pdfExportIntegFrame;
                    $color    = [0, 0, 0];
                    $lw       = 0.1;
                    $absolute = true;
                    if (isset($config['color'])) {
                        $color = $config['color'];
                        unset($config['color']);
                    }
                    if (isset($config['lw'])) {
                        $linewith = $config['lw'];
                        unset($config['lw']);
                    }
                    if (isset($config['abs'])) {
                        $absolute = $config['abs'];
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
                            $pdf->Line($line['x'], $line['y'], $line['x'] + $larr['x'], $line['y'] + $larr['y'], ['width' => $linewith, 'color' => $color]);
                        }
                    }
                }

                $pdf->setXY($print['x'] + $print['w'], $print['y'] + $print['h']);
                $lastprint = $print;
            }
        }
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return '[PDF]';
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return '[PDF]';
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        // @TODO
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        // @TODO
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>[PDF]</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return '<p>[PDF]</p>';
    }

    /**
     * @param Text $odt
     * @return void
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    /**
     * @param Text $odt
     * @return void
     */
    public function printAmendmentToODT(Text $odt)
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
