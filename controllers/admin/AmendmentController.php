<?php

namespace app\controllers\admin;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\http\HtmlErrorResponse;
use app\models\http\HtmlResponse;
use app\models\http\RedirectResponse;
use app\models\http\ResponseInterface;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Site;
use app\components\{Tools, UrlHelper, ZipWriter};
use app\models\db\{Amendment,
    AmendmentSupporter,
    ConsultationLog,
    ConsultationSettingsTag,
    ConsultationUserGroup,
    Motion,
    User};
use app\models\events\AmendmentEvent;
use app\models\exceptions\FormError;
use app\models\forms\AmendmentEditForm;
use app\views\amendment\LayoutHelper;
use yii\web\Response;

class AmendmentController extends AdminBase
{
    /**
     * @param bool $textCombined
     * @param int $withdrawn
     *
     * @return string
     */
    public function actionOdslist($textCombined = false, $withdrawn = 0)
    {
        $withdrawn = (intval($withdrawn) === 1);

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list', [
            'motions'      => $this->consultation->getVisibleIMotionsSorted($withdrawn),
            'textCombined' => $textCombined,
            'withdrawn'    => $withdrawn,
        ]);
    }

    /**
     * @param int $textCombined
     * @param int $withdrawn
     * @param int $maxLen
     *
     * @return string
     */
    public function actionOdslistShort($textCombined = 0, $withdrawn = 0, $maxLen = 2000)
    {
        $withdrawn    = (intval($withdrawn) === 1);
        $maxLen       = intval($maxLen);
        $textCombined = (intval($textCombined) === 1);

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=amendments.ods');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $this->renderPartial('ods_list_short', [
            'motions'      => $this->consultation->getVisibleIMotionsSorted($withdrawn),
            'textCombined' => $textCombined,
            'maxLen'       => $maxLen,
            'withdrawn'    => $withdrawn,
        ]);
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     */
    public function actionPdflist($withdrawn = 0)
    {
        $withdrawn = (intval($withdrawn) === 1);

        return $this->render('pdf_list', ['consultation' => $this->consultation, 'withdrawn' => $withdrawn]);
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     * @throws \Exception
     */
    public function actionPdfziplist($withdrawn = 0)
    {
        $withdrawn = (IntVal($withdrawn) === 1);
        $zip       = new ZipWriter();
        $hasLaTeX  = ($this->getParams()->xelatexPath || $this->getParams()->lualatexPath);
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            if ($motion->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
                if ($hasLaTeX && $amendment->getMyMotionType()->texTemplateId) {
                    $file = LayoutHelper::createPdfLatex($amendment);
                } else {
                    $file = LayoutHelper::createPdfTcpdf($amendment);
                }
                $zip->addFile($amendment->getFilenameBase(false) . '.pdf', $file);
            }
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/zip');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=amendments_pdf.zip');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @param int $withdrawn
     *
     * @return string
     * @throws \Exception
     */
    public function actionOdtziplist($withdrawn = 0)
    {
        $withdrawn = (intval($withdrawn) === 1);
        $zip       = new ZipWriter();
        foreach ($this->consultation->getVisibleMotions($withdrawn) as $motion) {
            if ($motion->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            foreach ($motion->getVisibleAmendments($withdrawn) as $amendment) {
                $content = $this->renderPartial('@app/views/amendment/view_odt', ['amendment' => $amendment]);
                $zip->addFile($amendment->getFilenameBase(false) . '.odt', $content);
            }
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/zip');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=amendments_odt.zip');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }

    /**
     * @throws \Exception
     */
    private function saveAmendmentSupporters(Amendment $amendment)
    {
        $names         = $this->getHttpRequest()->post('supporterName', []);
        $orgas         = $this->getHttpRequest()->post('supporterOrga', []);
        $genders       = $this->getHttpRequest()->post('supporterGender', []);
        $preIds        = $this->getHttpRequest()->post('supporterId', []);
        $newSupporters = [];
        /** @var AmendmentSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($amendment->getSupporters(true) as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) === '' && trim($orgas[$i]) === '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter               = new AmendmentSupporter();
                $supporter->amendmentId  = $amendment->id;
                $supporter->role         = AmendmentSupporter::ROLE_SUPPORTER;
                $supporter->personType   = AmendmentSupporter::PERSON_NATURAL;
                $supporter->dateCreation = date('Y-m-d H:i:s');
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            $supporter->setExtraDataEntry('gender', (isset($genders[$i]) ? $genders[$i] : null));
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
                die();
            }
            $newSupporters[$supporter->id] = $supporter;
        }

        foreach ($preSupporters as $supporter) {
            if (!isset($newSupporters[$supporter->id])) {
                $supporter->delete();
            }
        }

        $amendment->refresh();
    }

    private function saveAmendmentInitiator(Amendment $motion): void
    {
        if ($this->getHttpRequest()->post('initiatorSet') !== '1') {
            return;
        }
        $setType = $this->getHttpRequest()->post('initiatorSetType');
        $setUsername = $this->getHttpRequest()->post('initiatorSetUsername');
        $user = User::findByAuthTypeAndName($setType, $setUsername);

        if ($setUsername && !$user) {
            $this->getHttpSession()->setFlash('error', \Yii::t('motion', 'err_user_not_found'));
            return;
        }

        foreach ($motion->getInitiators() as $initiator) {
            $initiator->userId = ($user ? $user->id : null);
            $initiator->save();
            $initiator->refresh();
        }
        $motion->refresh();
    }

    public function actionUpdate(string $amendmentId): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($amendment->getMyMotion(), $amendment);

        $this->layout = 'column2';

        $post = $this->getHttpRequest()->post();

        if ($this->isPostSet('screen') && $amendment->isInScreeningProcess()) {
            if ($amendment->getMyMotion()->findAmendmentWithPrefix($post['titlePrefix'], $amendment)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->status      = Amendment::STATUS_SUBMITTED_SCREENED;
                $amendment->titlePrefix = $post['titlePrefix'];
                $amendment->save();
                $amendment->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($amendment));
                $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'amend_screened'));
            }
        }

        if ($this->isPostSet('delete')) {
            $amendment->status = Amendment::STATUS_DELETED;
            $amendment->save();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'amend_deleted'));
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }

        if ($this->isPostSet('save')) {
            if (!isset($post['edittext'])) {
                unset($post['sections']);
            }
            $form = new AmendmentEditForm($amendment->getMyMotion(), $amendment->getMyAgendaItem(), $amendment);
            $form->setAdminMode(true);
            $form->setAttributes([$post, $_FILES]);

            $votingData = $amendment->getVotingData();
            $votingData->setFromPostData($post['votes']);
            $amendment->setVotingData($votingData);

            try {
                $form->saveAmendment($amendment);
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }

            $amdat                        = $post['amendment'];
            $amendment->statusString      = mb_substr($amdat['statusString'], 0, 55);
            $amendment->dateCreation      = Tools::dateBootstraptime2sql($amdat['dateCreation']);
            $amendment->noteInternal      = $amdat['noteInternal'];
            $amendment->status            = intval($amdat['status']);
            $amendment->globalAlternative = (isset($amdat['globalAlternative']) ? 1 : 0);
            $amendment->dateResolution    = null;
            $amendment->notCommentable = (isset($amdat['notCommentable']) ? 1 : 0);
            $amendment->setExtraDataKey(
                Amendment::EXTRA_DATA_VIEW_MODE_FULL,
                (isset($amdat['viewMode']) && $amdat['viewMode'] === '1')
            );
            if ($amdat['dateResolution'] !== '') {
                $amendment->dateResolution = Tools::dateBootstraptime2sql($amdat['dateResolution']);
            }
            $amendment->agendaItemId = null;
            if (isset($amdat['agendaItemId'])) {
                foreach ($this->consultation->agendaItems as $agendaItem) {
                    if ($agendaItem->id === intval($amdat['agendaItemId'])) {
                        $amendment->agendaItemId = intval($amdat['agendaItemId']);
                    }
                }
            }

            if ($amendment->getMyMotion()->findAmendmentWithPrefix($amdat['titlePrefix'], $amendment)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'amend_prefix_collision'));
            } else {
                $amendment->titlePrefix = $post['amendment']['titlePrefix'];
            }

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $plugin::setAmendmentExtraSettingsFromForm($amendment, $post);
            }

            $ppChanges = new ProposedProcedureChange(null);
            try {
                $amendment->setProposalVotingPropertiesFromRequest(
                    $this->getHttpRequest()->post('votingStatus', null),
                    $this->getHttpRequest()->post('votingBlockId', null),
                    $this->getHttpRequest()->post('votingItemBlockId', []),
                    $this->getHttpRequest()->post('votingItemBlockName', ''),
                    $this->getHttpRequest()->post('newBlockTitle', ''),
                    false,
                    $ppChanges
                );
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
            if ($ppChanges->hasChanges()) {
                ConsultationLog::logCurrUser($amendment->getMyConsultation(), ConsultationLog::AMENDMENT_SET_PROPOSAL, $amendment->id, $ppChanges->jsonSerialize());
            }

            $amendment->save();

            foreach ($this->consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
                if (!$this->isPostSet('tags') || !in_array($tag->id, $post['tags'])) {
                    $amendment->unlink('tags', $tag, true);
                } else {
                    try {
                        $amendment->link('tags', $tag);
                    } catch (\Exception $e) {
                    }
                }
            }

            $this->saveAmendmentSupporters($amendment);
            $this->saveAmendmentInitiator($amendment);

            // This forces recalculating the motion's view page. This is necessary at least when the text has changed
            // or the names of the initiators.
            $amendment->getMyMotion()->flushViewCache();

            $amendment->flushCache(true);
            $amendment->refresh();
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'saved'));
        }

        $form = new AmendmentEditForm($amendment->getMyMotion(),$amendment->getMyAgendaItem(), $amendment);
        $form->setAdminMode(true);

        return new HtmlResponse($this->render('update', ['amendment' => $amendment, 'form' => $form]));
    }

    /**
     * @return string
     */
    public function actionOpenslides()
    {
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'text/csv');
        $this->getHttpResponse()->headers->add('Content-Disposition', 'attachment;filename=Amendments.csv');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        $amendments = [];
        foreach ($this->consultation->getVisibleIMotionsSorted(false) as $motion) {
            if (!is_a($motion, Motion::class)) {
                continue;
            }
            foreach ($motion->getVisibleAmendmentsSorted(false) as $amendment) {
                $amendments[] = $amendment;
            }
        }

        return $this->renderPartial('openslides_list', [
            'amendments' => $amendments,
        ]);
    }
}
