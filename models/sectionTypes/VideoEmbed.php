<?php

namespace app\models\sectionTypes;

use app\components\{HTMLTools, latex\Content, latex\Exporter};
use app\models\db\Consultation;
use app\models\exceptions\FormError;
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


    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }
        $html = '<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>';

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $y    = $pdf->getY();
        $pdf->writeHTMLCell(12, '', 12, $y, '', 0, 0, 0, true, '', true);
        $pdf->writeHTMLCell(173, '', 24, '', $html, 0, 1, 0, true, '', true);
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
        if ($this->isEmpty()) {
            return;
        }
        $title = Exporter::encodePlainString($this->section->getSettings()->title);
        $text = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $text .= Exporter::encodeHTMLString('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>');

        if ($isRight) {
            $content->textRight .= $text;
        } else {
            $content->textMain .= $text;
        }
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        if ($this->isEmpty()) {
            return;
        }
        $title = Exporter::encodePlainString($this->section->getSettings()->title);
        $text = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $text .= Exporter::encodeHTMLString('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>');

        if ($isRight) {
            $content->textRight .= $text;
        } else {
            $content->textMain .= $text;
        }
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
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>', false);
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('<p>' . HTMLTools::plainToHtml($this->section->getData()) . '</p>', false);
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return false;
    }
}
