<?php

namespace app\models\sectionTypes;

use app\components\{latex\Content, Tools, UrlHelper};
use app\models\db\{Consultation, MotionSection};
use app\models\exceptions\{FormError, Internal};
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text as ODTText;

class VideoEmbed extends ISectionType
{
    public function getVideoUrl(): ?string
    {
        return $this->section->getData();
    }

    private function extractYoutubeUrl(string $url): ?string
    {
        if (preg_match('/youtube\.com\/(watch)?\?v=(?<id>[a-z0-9]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/youtu\.be\/(?<id>[a-z0-9]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/youtube\.com\/embed\/(?<id>[a-z0-9]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        return null;
    }

    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $str  = '<section class="form-group section' . $this->section->sectionId . ' type' . static::TYPE_VIDEO_EMBED . '">';
        $str .= $this->getFormLabel();

        $str .= '<input type="text" class="form-control" id="sections_' . $type->id . '"' .
            ' name="sections[' . $type->id . ']" value="' . Html::encode($this->getVideoUrl()) . '"' .
            ' placeholder="https://www.youtube.com/watch?v=..., https://vimeo.com/...">';

        $str .= '</section>';

        return $str;
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    public function setMotionData($data)
    {
        $this->section->setData($data);
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
        $youtubeId = $this->extractYoutubeUrl($this->getMotionPlainText());
        $html = '<div class="videoHolder"><div class="videoSizer">';
        if ($youtubeId) {
            $url = 'https://www.youtube.com/embed/' . $youtubeId;
            $html .= '<iframe src="' . $url . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } else {
            $html .= Html::encode($this->getMotionPlainText());
        }
        $html .= '</div></div>';

        return $html;
    }

    public function isEmpty(): bool
    {
        return ($this->section->getData() === '');
    }


    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {

        return ($this->isEmpty() ? '' : $this->section->getData());
    }

    public function getAmendmentPlainText(): string
    {
        return ($this->isEmpty() ? '' : $this->section->getData());
    }

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST VIDEO]';
        } else {
            $content->textMain .= '[TEST VIDEO]';
        }
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST VIDEO]'; // @TODO
        } else {
            $content->textMain .= '[TEST VIDEO]'; // @TODO
        }
    }

    public function getMotionODS(): string
    {
        return '<p>Full HTML is not convertable to Spreadsheets</p>';
    }

    public function getAmendmentODS(): string
    {
        return '<p>Full HTML is not convertable to Spreadsheets</p>';
    }

    public function printMotionToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertable to ODT]', false); // @TODO
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[Full HTML is not convertable to ODT]', false); // @TODO
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
