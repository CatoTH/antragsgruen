<?php

namespace app\models\sectionTypes;

use app\components\latex\{Content, Exporter};
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
            ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->getData()) . '">';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert"></span> ' . \Yii::t('motion', 'max_len_alert');
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
     * @param $data
     */
    public function setMotionData($data)
    {
        $this->section->setData($data);
    }

    public function deleteMotionData()
    {
        $this->section->setData('');
    }

    /**
     * @param string $data
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        return Html::encode($this->getMotionPlainText());
    }

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if (!$section->getOriginalMotionSection()) {
            return '';
        }
        if ($this->isEmpty() || $section->data === $section->getOriginalMotionSection()->getData()) {
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

    public function isEmpty(): bool
    {
        return ($this->section->getData() === '');
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
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $html = '<p><strong>' . \Yii::t('amend', 'title_amend_to') . ':</strong><br>' .
            Html::encode($this->getMotionPlainText()) . '</p>';
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

    public function getMotionPlainText(): string
    {
        try {
            $intro = $this->section->getSettings()->motionType->getSettingsObj()->motionTitleIntro;
        } catch (\Exception $e) {
            $intro = '';
        }
        if (mb_strlen($intro) > 0 && mb_substr($intro, mb_strlen($intro) - 1, 1) !== ' ') {
            $intro .= ' ';
        }

        return $intro . $this->section->getData();
    }

    public function getAmendmentPlainText(): string
    {
        return $this->section->getData();
    }

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {

        if ($isRight) {
            $content->textRight .= Exporter::encodePlainString($this->getMotionPlainText());
        } else {
            $content->textMain .= Exporter::encodePlainString($this->getMotionPlainText());
        }
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data === $section->getOriginalMotionSection()->getData()) {
            return;
        }
        $title = Exporter::encodePlainString($section->getSettings()->title);
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

    /**
     * @param $text
     * @return bool
     */
    public function matchesFulltextSearch($text)
    {
        return (mb_stripos($this->section->getData(), $text) !== false);
    }
}
