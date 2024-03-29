<?php

declare(strict_types=1);

namespace app\models\sectionTypes;

use app\components\diff\{AmendmentSectionFormatter, DataTypes\AffectedLineBlock, Diff, DiffRenderer};
use app\models\SectionedParagraph;
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

    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(false, false);
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    public function setMotionData($data): void
    {
        $this->section->dataRaw = $data;
        $this->section->setData(HTMLTools::cleanSimpleHtml($data, []));
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->dataRaw = null;
    }

    public function setAmendmentData($data): void
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::cleanSimpleHtml($data['consolidated'], []);
        $section->dataRaw = $data['raw'];
    }
}
