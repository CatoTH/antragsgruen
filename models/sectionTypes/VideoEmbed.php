<?php

namespace app\models\sectionTypes;

use app\components\{html2pdf\Content as HtmlToPdfContent, HTMLTools, latex\Content as LatexContent, latex\Exporter};
use app\models\db\Consultation;
use app\models\exceptions\FormError;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text as ODTText;

class VideoEmbed extends ISectionType
{
    public function getVideoUrl(): ?string
    {
        return $this->section->getData();
    }

    private function extractYoutubeUrl(string $url): ?string
    {
        if (preg_match('/youtube\.com\/(watch)?\?v=(?<id>[a-z0-9_-]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/youtu\.be\/(?<id>[a-z0-9_-]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/youtube\.com\/embed\/(?<id>[a-z0-9_-]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/youtube\.com\/shorts\/(?<id>[a-z0-9_-]{11})/siu', $url, $matches)) {
            return $matches['id'];
        }
        return null;
    }

    private function extractVimeoUrl(string $url): ?string
    {
        if (preg_match('/vimeo\.com\/(?<id>[0-9]+)/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/vimeo\.com\/channels\/[a-z0-9]+\/(?<id>[0-9]+)/siu', $url, $matches)) {
            return $matches['id'];
        }
        return null;
    }

    private function extractFacebookUrl(string $url): ?string
    {
        if (preg_match('/facebook\.com\/([a-z0-9]*\/)?(?<id>[0-9]+)/siu', $url, $matches)) {
            return $matches['id'];
        }
        if (preg_match('/facebook\.com\/watch\/\?v=(?<id>[0-9]+)/siu', $url, $matches)) {
            return $matches['id'];
        }
        return null;
    }

    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $str  = '<section class="form-group section' . $this->section->sectionId . ' type' . static::TYPE_VIDEO_EMBED . '">';

        $str .= $this->getFormLabel();
        $str .= $this->getHintsAfterFormLabel();

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
        $this->section->metadata = '';
    }

    /**
     * @param string $data
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
        $youtubeId = $this->extractYoutubeUrl($this->getMotionPlainText());
        $vimeoId = $this->extractVimeoUrl($this->getMotionPlainText());
        $facebookId = $this->extractFacebookUrl($this->getMotionPlainText());
        $html = '<div class="videoHolder"><div class="videoSizer">';
        if ($vimeoId) {
            $url = 'https://player.vimeo.com/video/' . $vimeoId;
            $html .= '<iframe src="' . $url . '" allow="autoplay; fullscreen" allowfullscreen></iframe>';
        } elseif ($youtubeId) {
            $url = 'https://www.youtube.com/embed/' . $youtubeId;
            $html .= '<iframe src="' . $url . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } elseif ($facebookId) {
            $url = $this->getMotionPlainText();
            $url = 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=0&width=476';
            $html .= '<iframe src="' . $url . '" allowTransparency="true" allowFullScreen="true"></iframe>';
        } else {
            $html .= HTMLTools::plainToHtml($this->getMotionPlainText(), true);
        }
        $html .= '</div></div>';

        return $html;
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
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }
        $html = '<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>';

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $y    = $pdf->getY();
        $pdf->writeHTMLCell(12, 0, 12, $y, '', 0, 0, false, true, '', true);
        $pdf->writeHTMLCell(173, 0, 24, null, $html, 0, 1, false, true, '', true);
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

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }
        $title = Exporter::encodePlainString($this->getTitle());
        $text = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $text .= Exporter::encodeHTMLString('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>');

        if ($isRight) {
            $content->textRight .= $text;
        } else {
            $content->textMain .= $text;
        }
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        if ($this->isEmpty()) {
            return;
        }
        $title = Exporter::encodePlainString($this->getTitle());
        $text = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $text .= Exporter::encodeHTMLString('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>');

        if ($isRight) {
            $content->textRight .= $text;
        } else {
            $content->textMain .= $text;
        }
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
        return '<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>';
    }

    public function getAmendmentODS(): string
    {
        return '<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>';
    }

    public function printMotionToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>', false);
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>', false);
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return false;
    }
}
