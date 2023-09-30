<?php

namespace app\plugins\frauenrat\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\http\{BinaryFileResponse, HtmlErrorResponse, RedirectResponse, ResponseInterface};
use app\models\mergeAmendments\Init;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\plugins\frauenrat\pdf\{Frauenrat, FrauenratPdf};
use app\views\pdfLayouts\IPdfWriter;
use app\models\db\{ConsultationSettingsTag, Motion, User};

class MotionController extends Base
{
    public function actionSaveTag(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404,  'Motion not found');
        }
        if (!$this->consultation->havePrivilege(Privileges::PRIVILEGE_SCREENING, null)) {
            return new HtmlErrorResponse(403,  'Not permitted to change the tag');
        }

        foreach ($motion->getPublicTopicTags() as $tag) {
            $motion->unlink('tags', $tag, true);
        }
        foreach ($this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('newTag'))) {
                $motion->link('tags', $tag);
            }
        }

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    public function actionSaveProposal(string $motionSlug): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            return new HtmlErrorResponse(404, 'Motion not found');
        }
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::motion($motion))) {
            return new HtmlErrorResponse(403, 'Not permitted to change the status');
        }

        $newStatus = $this->getHttpRequest()->post('newProposal');
        $motion->proposalVisibleFrom = date("Y-m-d H:i:s");
        switch ($newStatus) {
            case 'accept':
                $motion->proposalStatus = Motion::STATUS_ACCEPTED;
                $motion->proposalComment = '';
                break;
            case 'reject':
                $motion->proposalStatus = Motion::STATUS_REJECTED;
                $motion->proposalComment = '';
                break;
            case 'modified':
                $motion->proposalStatus = Motion::STATUS_MODIFIED_ACCEPTED;
                $motion->proposalComment = '';
                break;
            case 'voting':
                $motion->proposalStatus = Motion::STATUS_VOTE;
                $motion->proposalComment = '';
                break;
            case '':
                $motion->proposalVisibleFrom = null;
                break;
            default:
                $motion->proposalStatus = Motion::STATUS_CUSTOM_STRING;
                $motion->proposalComment = $newStatus;
        }
        $motion->save();

        return new RedirectResponse(UrlHelper::createMotionUrl($motion));
    }

    /**
     * @param Motion[] $motions
     */
    private function createPdfFromMotions(array $motions, string $title, string $topPageFile): IPdfWriter
    {
        $motionType = $motions[0]->getMyMotionType();
        $pdfLayout = new Frauenrat($motionType);
        /** @var FrauenratPdf $pdf */
        $pdf        = $pdfLayout->createPDFClass();

        $pageCount = $pdf->setSourceFile($topPageFile);
        $pdf->pageNumberStartPage = $pageCount + 1;

        $pdf->SetCreator('Deutscher Frauenrat');
        $pdf->SetAuthor('Deutscher Frauenrat');
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId, ['adjustPageSize' => true]);
        }

        foreach ($motions as $motion) {
            $form = Init::forEmbeddedAmendmentsExport($motion);
            \app\views\motion\LayoutHelper::printMotionWithEmbeddedAmendmentsToPdf($form, $pdfLayout, $pdf);

            foreach ($motion->getVisibleAmendmentsSorted(false, false) as $amendment) {
                \app\views\amendment\LayoutHelper::printToPDF($pdf, $pdfLayout, $amendment);
            }
        }

        return $pdf;
    }

    public function actionSchwerpunktthemen(): ResponseInterface
    {
        $motions = [];
        switch ($this->consultation->urlPath) {
            case 'mv2021':
                $topPageFile = __DIR__ . '/../assets/2021_top5_antragsspiegel.pdf';
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (str_starts_with($motion->titlePrefix, 'A') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                break;
            case 'mv2022':
                $topPageFile = __DIR__ . '/../assets/2022_top5_antragsspiegel.pdf';
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (str_starts_with($motion->titlePrefix, 'A') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                break;
            case 'mv2023':
                $topPageFile = __DIR__ . '/../assets/2023_schwerpunkt.pdf';
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (str_starts_with($motion->titlePrefix, 'SPT') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                break;
            default:
                return new HtmlErrorResponse(500, 'This consultation does not have a PDF template assigned');
        }

        $pdf = $this->createPdfFromMotions($motions, 'Schwerpunktthemen', $topPageFile);
        $pdfStr = $pdf->Output('TOP_5_Schwerpunktthemen.pdf', 'S');

        return new BinaryFileResponse(BinaryFileResponse::TYPE_PDF, $pdfStr, false, 'TOP_5_Schwerpunktthemen.pdf');
    }

    public function actionSachantraege(): ResponseInterface
    {
        $motions = [];
        switch ($this->consultation->urlPath) {
            case 'mv2021':
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (!str_starts_with($motion->titlePrefix, 'A') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                $topPageFile = __DIR__ . '/../assets/2021_top6_antragsspiegel.pdf';
                break;
            case 'mv2022':
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (!str_starts_with($motion->titlePrefix, 'A') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                $topPageFile = __DIR__ . '/../assets/2022_top6_antragsspiegel.pdf';
                break;
            case 'mv2023':
                foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
                    if (!str_starts_with($motion->titlePrefix, 'SPT') && !str_starts_with($motion->titlePrefix, 'POS') && !str_starts_with($motion->titlePrefix, 'SAT') && is_a($motion, Motion::class)) {
                        $motions[] = $motion;
                    }
                }
                $topPageFile = __DIR__ . '/../assets/2023_sachantraege.pdf';
                break;
            default:
                return new HtmlErrorResponse(500, 'This consultation does not have a PDF template assigned');
        }

        $pdf = $this->createPdfFromMotions($motions, 'Sach- und Änderungsanträge', $topPageFile);
        $pdfStr = $pdf->Output('Sach- und Aenderungsantraege.pdf', 'S');

        return new BinaryFileResponse(BinaryFileResponse::TYPE_PDF, $pdfStr, false, 'Sach- und Aenderungsantraege.pdf');
    }
}
