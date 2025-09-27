<?php

declare(strict_types=1);

namespace app\models\sectionTypes;

use app\components\html2pdf\Content as HtmlToPdfContent;
use app\components\latex\{Content as LatexContent, Exporter};
use app\models\db\{ConsultationSettingsMotionSection, Consultation};
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text;

class Choice extends ISectionType
{
    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $value = $this->section->getData();

        $str  = '<section class="section' . $this->section->sectionId . ' type' . static::TYPE_CHOICE . '">';
        $str .= $this->getFormLabel();
        $str .= $this->getHintsAfterFormLabel();

        $requiredHtml = match($type->required) {
            ConsultationSettingsMotionSection::REQUIRED_YES => ' required',
            ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED => ' data-encouraged="true"',
            default => '',
        };
        $id = 'sections_' . $type->id;
        $nameId = 'name="sections[' . $type->id . ']" id="' . $id . '"';

        if ($type->getSettingsObj()->choiceType === \app\models\settings\MotionSection::CHOICE_TYPE_RADIO) {
            foreach (($type->getSettingsObj()->choices ?? []) as $option) {
                $str .= '<label class="choiceLabel">';
                $str .= '<input type="radio" ' . $nameId . $requiredHtml . ' value="' . Html::encode($option) . '"';
                if ($option === $value) {
                    $str .= ' checked="checked"';
                }
                $str .= '> ' . Html::encode($option);
                $str .= '</label>';
            }
        }

        if ($type->getSettingsObj()->choiceType === \app\models\settings\MotionSection::CHOICE_TYPE_SELECT) {
            $str .= '<select ' . $nameId . $requiredHtml . ' class="stdDropdown"><option></option>';
            foreach (($type->getSettingsObj()->choices ?? []) as $option) {
                $str .= '<option value="' . Html::encode($option) . '"';
                if ($value === $option) {
                    $str .= ' selected';
                }
                $str .= '>' . Html::encode($option) . '</option>';
            }
            $str .= '</select>';
        }

        $str .= '</section>';
        return $str;
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    /**
     * @param string $data
     */
    public function setMotionData($data): void
    {
        $type = $this->section->getSettings();

        $sanitized = '';
        foreach ($type->getSettingsObj()->choices ?? [] as $choice) {
            if ($data === $choice) {
                $sanitized = $choice;
            }
        }

        $this->section->setData($sanitized);
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
        if ($this->isEmpty()) {
            return '';
        }

        $str = '<div class="stdPadding">';
        $str .= Html::encode($this->section->data);
        $str .= '</div>';

        return $str;
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        return ''; // @TODO
    }

    public function isEmpty(): bool
    {
        return $this->section->getData() === '';
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
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $pdf->writeHTMLCell(170, 0, 24, null, Html::encode($this->section->data), 0, 1, false, true, '', true);
        $pdf->Ln(4);
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $this->printAmendmentToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {
        return $this->section->getData();
    }

    public function getAmendmentPlainText(): string
    {
        return '@TODO'; // @TODO
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        $content->textMain .= Exporter::encodePlainString($this->section->getData());
        $content->textMain .= "\\newline\n";
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST DATA]'; // @TODO
        } else {
            $content->textMain .= '[TEST DATA]'; // @TODO
        }
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        $html = $this->getSimple($isRight);

        if ($isRight) {
            $content->textRight .= $html;
        } else {
            $content->textMain .= $html;
        }
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        // TODO: Implement printAmendmentHtml2Pdf() method.
    }

    public function getMotionODS(): string
    {
        return Html::encode($this->section->getData());
    }

    public function getAmendmentODS(): string
    {
        return 'Test'; //  @TODO
    }

    public function printMotionToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock(Html::encode($this->section->getData()), false);
    }

    public function printAmendmentToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return mb_stripos($this->section->getData(), $text) !== false;
    }
}
