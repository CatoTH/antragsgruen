<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\latex\Exporter;
use app\models\db\AmendmentSection;
use app\models\db\Consultation;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\TcpdfFpdi;
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text;
use yii\web\View;

class Title extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        $type = $this->section->getSettings();
        $str  = '<div class="form-group plain-text" data-max-len="' . $type->maxLen . '">';
        $str .= $this->getFormLabel();

        if ($type->maxLen != 0) {
            $len = abs($type->maxLen);
            $str .= '<div class="maxLenHint"><span class="glyphicon glyphicon-info-sign icon"></span> ';
            $str .= str_replace(
                ['%LEN%', '%COUNT%'],
                [$len, '<span class="counter"></span>'],
                \Yii::t('motion', 'max_len_hint')
            );
            $str .= '</div>';
        }

        $str .= '<input type="text" class="form-control" id="sections_' . $type->id . '"' .
            ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->data) . '">';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert"></span> ' . \Yii::t('motion', 'max_len_alert');
            $str .= '</div>';
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        return $this->getMotionFormField();
    }

    /**
     * @param $data
     */
    public function setMotionData($data)
    {
        $this->section->data = $data;
    }

    /**
     * @param string $data
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    /**
     * @param bool $isRight
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSimple($isRight)
    {
        return Html::encode($this->section->data);
    }

    /**
     * @param string $sectionTitlePrefix
     * @return string
     */
    public function getAmendmentFormatted($sectionTitlePrefix = '')
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if (!$section->getOriginalMotionSection()) {
            return '';
        }
        if ($this->isEmpty() || $section->data === $section->getOriginalMotionSection()->data) {
            return '';
        }
        if ($sectionTitlePrefix) {
            $sectionTitlePrefix .= ': ';
        }
        $str = '<section id="section_title" class="motionTextHolder">';
        $str .= '<h3 class="green">' . Html::encode($sectionTitlePrefix . $section->getSettings()->title) . '</h3>';
        $str .= '<div id="section_title_0" class="paragraph"><div class="text fixedWidthFont motionTextFormattings">';
        $str .= '<h4 class="lineSummary">' . \Yii::t('amend', 'title_amend_to') . ':</h4>';
        $str .= '<p>' . Html::encode($section->data) . '</p>';
        $str .= '</div></div></section>';

        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data === '');
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
    {
        // TODO: Implement printMotionToPDF() method.
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $html = '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
            Html::encode($section->data) . '</p>';
        $pdf->writeHTMLCell(170, '', 24, '', $html, 0, 1, 0, true, '', true);
        $pdf->Ln(7);
    }

    /**
     * @param CommentForm|null $commentForm
     * @param int[] $openedComments
     * @return string
     */
    public function showMotionView($commentForm, $openedComments)
    {
        return (new View())->render(
            '@app/views/motion/showTitleSection',
            [
                'section'        => $this->section,
                'openedComments' => $openedComments,
                'commentForm'    => $commentForm,
            ],
            \Yii::$app->controller
        );
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return $this->section->data;
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return $this->section->data;
    }

    /**
     * @param bool $isRight
     * @param Content $content
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function printMotionTeX($isRight, Content $content, Consultation $consultation)
    {
        if ($isRight) {
            $content->textRight .= Exporter::encodePlainString($this->section->data);
        } else {
            $content->textMain .= Exporter::encodePlainString($this->section->data);
        }
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return;
        }
        $title = Exporter::encodePlainString($section->getSettings()->title);
        $tex   = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $html  = '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
            Html::encode($this->section->data) . '</p>';
        $tex .= Exporter::encodeHTMLString($html);
        if ($isRight) {
            $content->textRight .= $tex;
        } else {
            $content->textMain .= $tex;
        }
    }


    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>' . Html::encode($this->section->data) . '</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return '';
        }
        return '<strong>' . \Yii::t('amend', 'title_new') . ':</strong><br>' .
        Html::encode($section->data) . '<br><br>';
    }

    /**
     * @param Text $odt
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h1>' . Html::encode($this->section->data) . '</h1>', false);
    }

    /**
     * @param Text $odt
     */
    public function printAmendmentToODT(Text $odt)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return;
        }
        $odt->addHtmlTextBlock('<h2>' . \Yii::t('amend', 'title_new') . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . Html::encode($section->data) . '</p>', false);
    }

    /**
     * @param $text
     * @return bool
     */
    public function matchesFulltextSearch($text)
    {
        return (mb_stripos($this->section->data, $text) !== false);
    }
}
