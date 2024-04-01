<?php

declare(strict_types=1);

namespace app\models\sectionTypes;

use app\models\settings\PrivilegeQueryContext;
use app\models\settings\Privileges;
use app\components\{HashedStaticCache, html2pdf\Content as HtmlToPdfContent, HTMLTools, LineSplitter, RequestContext, UrlHelper};
use app\components\latex\{Content as LatexContent, Exporter};
use app\models\db\{Amendment, AmendmentSection, Consultation, ConsultationMotionType, Motion, MotionSection, User};
use app\models\forms\CommentForm;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use yii\web\View;
use CatoTH\HTML2OpenDocument\Text as ODTText;

class TextEditorial extends TextSimpleCommon
{
    /**
     * @return array{author: string|null, lastUpdate: \DateTime|null}
     */
    public function getSectionMetadata(): array
    {
        if ($this->section->metadata === null || $this->section->metadata === '') {
            return ['author' => null, 'lastUpdate' => null];
        }
        $data = json_decode($this->section->metadata, true);
        $author = (isset($data['autor']) ? (string)$data['autor'] : null);
        if (isset($data['lastUpdate'])) {
            $lastUpdate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['lastUpdate']);
            if (!$lastUpdate) {
                $lastUpdate = null;
            }
        } else {
            $lastUpdate = null;
        }

        return ['author' => $author, 'lastUpdate' => $lastUpdate];
    }

    private function setSectionMetadata(?string $author, ?\DateTimeInterface $lastUpdate): void
    {
        $data = ($this->section->metadata ? json_decode($this->section->metadata, true) : []);
        $data['autor'] = $author;
        $data['lastUpdate'] = $lastUpdate?->format('Y-m-d H:i:s');
        $this->section->metadata = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function showIfEmpty(): bool
    {
        if (is_a($this->section, AmendmentSection::class)) {
            /** @var AmendmentSection $section */
            $section = $this->section;
            $imotion = $section->getAmendment();
        } else {
            /** @var MotionSection $section */
            $section = $this->section;
            $imotion = $section->getMotion();
        }

        return User::havePrivilege($imotion->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::imotion($imotion));
    }

    public function showMotionView(?CommentForm $commentForm, array $openedComments): string
    {
        return (new View())->render(
            '@app/views/motion/showEditorialTextSection',
            [
                'section'        => $this->section,
            ],
            \Yii::$app->controller
        );
    }

    public function getFormattedSectionMetadata(bool $allowRelativeDates): string
    {
        $metadata = $this->getSectionMetadata();
        $data = [];
        if ($metadata['author']) {
            $data[] = Html::encode($metadata['author']);
        }
        if ($metadata['lastUpdate']) {
            $data[] = Html::encode(\app\components\Tools::formatMysqlDateTime($metadata['lastUpdate']->format('Y-m-d H:i:s'), $allowRelativeDates));
        }
        return implode(', ', $data);
    }

    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(false, false);
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;
        $settings = $section->getSettings();

        $html = '<section class="motionSection">';
        if ($settings->printTitle) {
            $html .= '<h2>' . Html::encode($this->getTitle()) . "</h2>\n";
        }
        $html .= '<div class="editorialMetadata">';
        $html .= $this->getFormattedSectionMetadata(false);
        $html .= '</div>';
        $html .= '<div class="text motionTextFormattings">';
        $html .= $this->section->data;
        $html .= '</div></section>';

        if ($section->isLayoutRight()) {
            $content->textRight .= $html;
        } else {
            $content->textMain .= $html;
        }
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        $content->textMain .= '<h2>Amendments not supported for editorial texts</h2>';
    }

    public function printMotionToODT(ODTText $odt): void
    {
        if ($this->isEmpty()) {
            return;
        }
        /** @var MotionSection $section */
        $section = $this->section;
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        $odt->addHtmlTextBlock('<p><em>' . Html::encode($this->getFormattedSectionMetadata(false)) . '</em></p>', false);
        $paras = $section->getTextParagraphLines();
        foreach ($paras as $para) {
            $html = str_replace('###LINENUMBER###', '', implode('', $para->lines));
            $html = HTMLTools::correctHtmlErrors($html);
            $odt->addHtmlTextBlock($html, false);
        }
    }

    public function printAmendmentToODT(ODTText $odt): void
    {
        $odt->addHtmlTextBlock('<h2>Amendments not supported for editorial texts</h2>', false);
    }

    public function getMotionPlainHtml(): string
    {
        $html = $this->section->getData();
        $html = str_replace('<span class="underline">', '<span style="text-decoration: underline;">', $html);
        $html = str_replace('<span class="strike">', '<span style="text-decoration: line-through;">', $html);

        return '<p><em>' . $this->getFormattedSectionMetadata(false) . '</em></p>' . $html;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;

        if ($section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $pdf->writeHTML('<p><em>' . $this->getFormattedSectionMetadata(false) . '</em></p>');

        $pdf->printMotionSection($section);
    }

    /**
     * @param string $data
     */
    public function setMotionData($data): void
    {
        $this->section->dataRaw = $data;
        $this->section->setData(HTMLTools::cleanSimpleHtml($data, []));
    }

    public function setEditorialData(string $data, ?string $author, ?\DateTimeInterface $lastUpdate): void
    {
        $this->setMotionData($data);
        $this->setSectionMetadata($author, $lastUpdate);
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->dataRaw = null;
    }

    /**
     * @param array $data
     */
    public function setAmendmentData($data): void
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::cleanSimpleHtml($data['consolidated'], []);
        $section->dataRaw = $data['raw'];
    }
}
