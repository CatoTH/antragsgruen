<?php

namespace app\models\sectionTypes;

use app\components\html2pdf\Content as HtmlToPdfContent;
use app\components\latex\{Content as LatexContent, Exporter};
use app\models\db\{AmendmentSection, Consultation};
use app\models\forms\CommentForm;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;
use yii\web\View;

class Title extends ISectionType
{
    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $str  = '<div class="form-group plain-text" data-max-len="' . $type->maxLen . '">';

        $str .= $this->getFormLabel();
        $str .= $this->getHintsAfterFormLabel();

        if ($type->maxLen != 0) {
            $len = abs($type->maxLen);
            $str .= '<div class="maxLenHint"><span class="glyphicon glyphicon-info-sign icon" aria-hidden="true"></span> ';
            $str .= str_replace(
                ['%LEN%', '%COUNT%'],
                [(string) $len, '<span class="counter"></span>'],
                \Yii::t('motion', 'max_len_hint')
            );
            $str .= '</div>';
        }

        $str .= '<input type="text" class="form-control" id="sections_' . $type->id . '"' .
            ' dir="' . ($this->section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '"' .
            ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->getData()) . '">';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' . \Yii::t('motion', 'max_len_alert');
            $str .= '</div>';
        }

        $str .= '</div>';

        return $str;
    }

    public function getAmendmentFormField(): string
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        return $this->getMotionFormField();
    }

    /**
     * @param string $data
     */
    public function setMotionData($data): void
    {
        $this->section->setData($data);
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
    }

    /**
     * @param string $data
     */
    public function setAmendmentData($data): void
    {
        $this->setMotionData($data);
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        return Html::encode($this->getMotionPlainText());
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if (!$section->getOriginalMotionSection()) {
            return '';
        }
        if ($this->isEmpty() || $section->data === $section->getOriginalMotionSection()->getData()) {
            return '';
        }
        $str = '<section id="' . $htmlIdPrefix . 'section_title" class="motionTextHolder">';
        $str .= '<h3 class="green">' . Html::encode($this->getTitle()) . '</h3>';
        $str .= '<div id="' . $htmlIdPrefix . 'section_title_0" class="paragraph"><div class="text fixedWidthFont motionTextFormattings" ' .
            'dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';
        $str .= '<h4 class="lineSummary">' . \Yii::t('amend', 'title_amend_to') . ':</h4>';
        $str .= '<p>' . Html::encode($section->data) . '</p>';
        $str .= '</div></div></section>';

        return $str;
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
        return false;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data === $section->getOriginalMotionSection()->getData()) {
            return;
        }

        if ($section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $html = '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
            Html::encode($this->getMotionPlainText()) . '</p>';
        $pdf->writeHTMLCell(170, 0, 24, null, $html, 0, 1, false, true, '', true);
        $pdf->Ln(7);
    }

    public function showMotionView(?CommentForm $commentForm, array $openedComments): string
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

    public function getMotionPlainText(): string
    {
        try {
            $intro = $this->section->getSettings()->motionType->getSettingsObj()->motionTitleIntro;
        } catch (\Exception $e) {
            $intro = '';
        }
        if (grapheme_strlen($intro) > 0 && grapheme_substr($intro, grapheme_strlen($intro) - 1, 1) !== ' ') {
            $intro .= ' ';
        }

        return $intro . $this->section->getData();
    }

    public function getAmendmentPlainText(): string
    {
        return $this->section->getData();
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= Exporter::encodePlainString($this->getMotionPlainText());
        } else {
            $content->textMain .= Exporter::encodePlainString($this->getMotionPlainText());
        }
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->getOriginalMotionSection() && $section->data === $section->getOriginalMotionSection()->getData()) {
            return;
        }
        $title = Exporter::encodePlainString($this->getTitle());
        $tex   = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $html  = '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
            Html::encode($this->section->getData()) . '</p>';
        $tex .= Exporter::encodeHTMLString($html);
        if ($isRight) {
            $content->textRight .= $tex;
        } else {
            $content->textMain .= $tex;
        }
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= $this->getMotionPlainHtml();
        } else {
            $content->textMain .= $this->getMotionPlainHtml();
        }
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->getOriginalMotionSection() && $section->data === $section->getOriginalMotionSection()->getData()) {
            return;
        }

        $html = '<h3 class="green">' . Html::encode($this->getTitle()) . '</h3>';

        $html .= '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
                 Html::encode($this->section->getData()) . '</p>';
        if ($isRight) {
            $content->textRight .= $html;
        } else {
            $content->textMain .= $html;
        }
    }

    public function getMotionODS(): string
    {
        return '<p>' . Html::encode($this->getMotionPlainText()) . '</p>';
    }

    public function getAmendmentODS(): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data === $section->getOriginalMotionSection()->getData()) {
            return '';
        }
        return '<strong>' . \Yii::t('amend', 'title_new') . ':</strong><br>' .
        Html::encode($section->data) . '<br><br>';
    }

    public function printMotionToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h1>' . Html::encode($this->getMotionPlainText()) . '</h1>', false);
    }

    public function printAmendmentToODT(Text $odt): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data === $section->getOriginalMotionSection()->getData()) {
            return;
        }
        $odt->addHtmlTextBlock('<h2>' . \Yii::t('amend', 'title_new') . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . Html::encode($section->data) . '</p>', false);
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return (grapheme_stripos($this->section->getData(), $text) !== false);
    }
}
