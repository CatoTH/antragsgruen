<?php

namespace app\plugins\frauenrat\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\mergeAmendments\Init;
use app\plugins\frauenrat\pdf\Frauenrat;
use app\plugins\frauenrat\pdf\FrauenratPdf;
use app\views\pdfLayouts\IPdfWriter;
use app\models\db\{ConsultationSettingsTag, ConsultationUserGroup, Motion, User};

class MotionController extends Base
{
    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionSaveTag($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpResponse()->statusCode = 404;
            return 'Motion not found';
        }
        if (!$this->consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SCREENING)) {
            $this->getHttpResponse()->statusCode = 403;
            return 'Not permitted to change the tag';
        }

        foreach ($motion->getPublicTopicTags() as $tag) {
            $motion->unlink('tags', $tag, true);
        }
        foreach ($this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if ($tag->id === intval($this->getHttpRequest()->post('newTag'))) {
                $motion->link('tags', $tag);
            }
        }

        return $this->redirect(UrlHelper::createMotionUrl($motion));
    }

    /**
     * @param string $motionSlug
     *
     * @return string
     * @throws \Yii\base\ExitException
     */
    public function actionSaveProposal($motionSlug)
    {
        $motion = $this->consultation->getMotion($motionSlug);
        if (!$motion) {
            $this->getHttpResponse()->statusCode = 404;
            return 'Motion not found';
        }
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CHANGE_PROPOSALS)) {
            $this->getHttpResponse()->statusCode = 403;
            return 'Not permitted to change the status';
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

        return $this->redirect(UrlHelper::createMotionUrl($motion));
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

    public function actionSchwerpunktthemen()
    {
        $motions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
            if (strpos($motion->titlePrefix, 'A') === 0 && is_a($motion, Motion::class)) {
                $motions[] = $motion;
            }
        }
        switch ($this->consultation->urlPath) {
            case 'mv2021':
                $topPageFile = __DIR__ . '/../assets/2021_top5_antragsspiegel.pdf';
                break;
            case 'mv2022':
                $topPageFile = __DIR__ . '/../assets/2022_top5_antragsspiegel.pdf';
                break;
            default:
                return 'This consultation does not have a PDF template assigned';
        }

        $pdf = $this->createPdfFromMotions($motions, 'Schwerpunktthemen', $topPageFile);
        $pdf->Output('TOP_5_Schwerpunktthemen.pdf');
    }

    public function actionSachantraege()
    {
        $motions = [];
        foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
            if (strpos($motion->titlePrefix, 'A') !== 0 && is_a($motion, Motion::class)) {
                $motions[] = $motion;
            }
        }
        switch ($this->consultation->urlPath) {
            case 'mv2021':
                $topPageFile = __DIR__ . '/../assets/2021_top6_antragsspiegel.pdf';
                break;
            case 'mv2022':
                $topPageFile = __DIR__ . '/../assets/2022_top6_antragsspiegel.pdf';
                break;
            default:
                return 'This consultation does not have a PDF template assigned';
        }

        $pdf = $this->createPdfFromMotions($motions, 'Sachanträge', $topPageFile);
        $pdf->Output('TOP_6_Sachantraege.pdf');
    }
}
