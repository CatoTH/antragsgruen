<?php

namespace app\controllers\admin;

use app\models\consultationLog\ProposedProcedureChange;
use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, RedirectResponse, ResponseInterface};
use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Consultation, ConsultationLog, ConsultationMotionType, ConsultationSettingsTag, Motion, MotionSupporter, User};
use app\models\exceptions\FormError;
use app\models\events\MotionEvent;
use app\models\forms\{MotionDeepCopy, MotionEditForm, MotionMover};
use app\models\sectionTypes\ISectionType;
use app\models\settings\{AntragsgruenApp, PrivilegeQueryContext, Privileges};

class MotionController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_MOTION_STATUS_EDIT,
        Privileges::PRIVILEGE_MOTION_TEXT_EDIT,
        Privileges::PRIVILEGE_MOTION_INITIATORS,
    ];

    /**
     * @throws \Throwable
     */
    private function saveMotionSupporters(Motion $motion): void
    {
        $names         = $this->getHttpRequest()->post('supporterName', []);
        $orgas         = $this->getHttpRequest()->post('supporterOrga', []);
        $genders       = $this->getHttpRequest()->post('supporterGender', []);
        $preIds        = $this->getHttpRequest()->post('supporterId', []);
        $newSupporters = [];
        /** @var MotionSupporter[] $preSupporters */
        $preSupporters = [];
        foreach ($motion->getSupporters(true) as $supporter) {
            $preSupporters[$supporter->id] = $supporter;
        }
        for ($i = 0; $i < count($names); $i++) {
            if (trim($names[$i]) === '' && trim($orgas[$i]) === '') {
                continue;
            }
            if (isset($preSupporters[$preIds[$i]])) {
                $supporter = $preSupporters[$preIds[$i]];
            } else {
                $supporter               = new MotionSupporter();
                $supporter->motionId     = $motion->id;
                $supporter->role         = MotionSupporter::ROLE_SUPPORTER;
                $supporter->personType   = MotionSupporter::PERSON_NATURAL;
                $supporter->dateCreation = date('Y-m-d H:i:s');
            }
            $supporter->name         = $names[$i];
            $supporter->organization = $orgas[$i];
            $supporter->position     = $i;
            $supporter->setExtraDataEntry('gender', $genders[$i] ?? null);
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

        $motion->refresh();
    }

    private function saveMotionInitiator(Motion $motion): void
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
            $initiator->userId = $user?->id;
            $initiator->save();
            $initiator->refresh();
        }
        $motion->refresh();
    }

    public function actionGetAmendmentRewriteCollisions(int $motionId): HtmlResponse
    {
        $newSections = $this->getHttpRequest()->post('newSections', []);

        /** @var Motion $motion */
        $motion     = $this->consultation->getMotion($motionId);
        $collisions = $amendments = [];
        foreach ($motion->getAmendmentsRelevantForCollisionDetection() as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $amendments, true)) {
                        $amendments[$amendment->id] = $amendment;
                        $collisions[$amendment->id] = [];
                    }
                    $collisions[$amendment->id][$section->sectionId] = $coll;
                }
            }
        }

        return new HtmlResponse($this->renderPartial('@app/views/amendment/ajax_rewrite_collisions', [
            'amendments' => $amendments,
            'collisions' => $collisions,
        ]));
    }

    public function actionUpdate(string $motionId): ResponseInterface
    {
        $consultation = $this->consultation;

        $motion = $consultation->getMotion($motionId);
        if (!$motion) {
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        $privCtx = PrivilegeQueryContext::motion($motion);
        if (!User::haveOneOfPrivileges($consultation, self::REQUIRED_PRIVILEGES, $privCtx)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $this->layout = 'column2';
        $post         = $this->getHttpRequest()->post();

        if ($this->isPostSet('screen') && $motion->isInScreeningProcess() && User::havePrivilege($consultation, Privileges::PRIVILEGE_SCREENING, $privCtx)) {
            $toSetPrefix = (mb_strlen($post['titlePrefix']) > 50 ? mb_substr($post['titlePrefix'], 0, 50) : $post['titlePrefix']);
            if ($consultation->findMotionWithPrefixAndVersion($toSetPrefix, $post['version'], $motion)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
            } else {
                $motion->status = Motion::STATUS_SUBMITTED_SCREENED;
                $motion->titlePrefix = $toSetPrefix;
                $motion->version = $post['version'];
                $motion->save();
                $motion->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($motion));
                $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'motion_screened'));
            }
        }

        if ($this->isPostSet('delete') && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_DELETE, $privCtx)) {
            $motion->setDeleted();
            $motion->flushCacheStart(['lines']);
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'motion_deleted'));
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }

        if ($this->isPostSet('save') && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_STATUS_EDIT, $privCtx)) {
            $modat = $post['motion'];

            $sectionTypes = [];
            foreach ($motion->getMyMotionType()->motionSections as $section) {
                $sectionTypes[$section->id] = $section->type;
            }

            try {
                $form = new MotionEditForm($motion->getMyMotionType(), $motion->agendaItem, $motion);
                $form->setAdminMode(true);
                $form->setAttributes($post, $_FILES);

                $votingData = $motion->getVotingData();
                $votingData->setFromPostData($post['votes']);
                $motion->setVotingData($votingData);

                $ppChanges = new ProposedProcedureChange(null);
                try {
                    $motion->setProposalVotingPropertiesFromRequest(
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
                    ConsultationLog::logCurrUser($motion->getMyConsultation(), ConsultationLog::MOTION_SET_PROPOSAL, $motion->id, $ppChanges->jsonSerialize());
                }

                $form->saveMotion($motion);
                if (isset($post['sections']) && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_TEXT_EDIT, $privCtx)) {
                    $overrides = $post['amendmentOverride'] ?? [];
                    $newHtmls  = [];
                    foreach ($post['sections'] as $sectionId => $html) {
                        $htmlTypes = [ISectionType::TYPE_TEXT_SIMPLE, ISectionType::TYPE_TEXT_HTML];
                        if (isset($sectionTypes[$sectionId]) && in_array($sectionTypes[$sectionId], $htmlTypes)) {
                            $newHtmls[$sectionId] = HTMLTools::cleanSimpleHtml($html);
                        }
                    }
                    $form->updateTextRewritingAmendments($motion, $newHtmls, $overrides);
                }

                $motion->setProtocol(
                    $this->getPostValue('protocol'),
                    intval($this->getPostValue('protocol_public')) === 1
                );
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }

            if (intval($modat['motionType']) !== $motion->motionTypeId) {
                try {
                    $newType = ConsultationMotionType::findOne($modat['motionType']);
                    if (!$newType || $newType->consultationId !== $motion->consultationId) {
                        throw new FormError('The new motion type was not found');
                    }
                    $sectionMapping = MotionDeepCopy::getMotionSectionMapping($motion->getMyMotionType(), $newType, []);
                    $motion->setMotionType($newType, $sectionMapping);
                } catch (FormError $e) {
                    $this->getHttpSession()->setFlash('error', $e->getMessage());
                }
            }

            $motion->title        = $modat['title'];
            $motion->noteInternal = $modat['noteInternal'];
            $motion->agendaItemId = (isset($modat['agendaItemId']) && $modat['agendaItemId'] > 0 ? intval($modat['agendaItemId']) : null);
            $motion->nonAmendable = (isset($modat['nonAmendable']) ? 1 : 0);
            $motion->notCommentable = (isset($modat['notCommentable']) ? 1 : 0);

            $motion->status       = intval($modat['status']);
            if ($motion->status === Motion::STATUS_OBSOLETED_BY_MOTION) {
                $motion->statusString = (string)intval($modat['statusStringMotion']);
            } elseif ($motion->status === Motion::STATUS_OBSOLETED_BY_AMENDMENT) {
                $motion->statusString = (string)intval($modat['statusStringAmendment']);
            } else {
                $motion->statusString = mb_substr($modat['statusString'], 0, 55);
            }

            if (isset($modat['slug']) && preg_match('/^[\w_-]+$/i', $modat['slug'])) {
                $collision = false;
                foreach ($motion->getMyConsultation()->motions as $otherMotion) {
                    if (mb_strtolower($otherMotion->slug ?: '') === mb_strtolower($modat['slug']) && $otherMotion->id !== $motion->id) {
                        $collision = true;
                    }
                }
                if ($collision) {
                    $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'motion_url_path_err'));
                } else {
                    $motion->slug = mb_strtolower($modat['slug']);
                }
            }

            $roundedDate = Tools::dateBootstraptime2sql($modat['dateCreation']);
            if (substr($roundedDate, 0, 16) !== substr($motion->dateCreation, 0, 16)) {
                $motion->dateCreation = $roundedDate;
            }

            if ($modat['dateResolution'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['dateResolution']);
                if (substr($roundedDate, 0, 16) !== substr($motion->dateResolution ?: '', 0, 16)) {
                    $motion->dateResolution = $roundedDate;
                }
            } else {
                $motion->dateResolution = null;
            }

            if ($modat['datePublication'] !== '') {
                $roundedDate = Tools::dateBootstraptime2sql($modat['datePublication']);
                if (substr($roundedDate, 0, 16) !== substr($motion->datePublication ?: '', 0, 16)) {
                    $motion->datePublication = $roundedDate;
                }
            } else {
                $motion->datePublication = null;
            }

            if ($modat['parentMotionId'] && intval($modat['parentMotionId']) === $motion->parentMotionId) {
                // Just leave it untouched - skip check in case it's from a different consultation
            } elseif ($modat['parentMotionId'] && intval($modat['parentMotionId']) !== $motion->id &&
                $consultation->getMotion($modat['parentMotionId'])) {
                $motion->parentMotionId = intval($modat['parentMotionId']);
            } else {
                $motion->parentMotionId = null;
            }

            $toSetPrefix = (mb_strlen($modat['titlePrefix']) > 50 ? mb_substr($modat['titlePrefix'], 0, 50) : $modat['titlePrefix']);
            if ($consultation->findMotionWithPrefixAndVersion($toSetPrefix, $modat['version'], $motion)) {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'motion_prefix_collision'));
            } else {
                $motion->titlePrefix = $toSetPrefix;
                $motion->version = (mb_strlen($modat['version']) > 50 ? mb_substr($modat['version'], 0, 50) : $modat['version']);
            }

            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                $plugin::setMotionExtraSettingsFromForm($motion, $post);
            }

            $motion->save();

            if (User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_INITIATORS, $privCtx)) {
                $this->saveMotionSupporters($motion);
                $this->saveMotionInitiator($motion);
            }

            $motion->flushCache(true);
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        $form = new MotionEditForm($motion->getMyMotionType(), $motion->agendaItem, $motion);
        $form->setAdminMode(true);

        return new HtmlResponse($this->render('update', ['motion' => $motion, 'form' => $form]));
    }

    public function actionMove(string $motionId): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_MOTION_DELETE, PrivilegeQueryContext::motion($motion))) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $form = new MotionMover($this->consultation, $motion, User::getCurrentUser());

        if ($this->isPostSet('move')) {
            $newMotion = $form->move($this->getHttpRequest()->post());
            if ($newMotion) {
                if ($newMotion->consultationId === $this->consultation->id) {
                    return new RedirectResponse(UrlHelper::createMotionUrl($newMotion));
                } else {
                    Consultation::getCurrent()->flushMotionCache();
                    Consultation::getCurrent()->refresh();

                    return new HtmlResponse($this->render('moved_other_consultation', ['newMotion' => $newMotion]));
                }
            }
        }

        return new HtmlResponse($this->render('move', ['form' => $form]));
    }

    public function actionMoveCheck(string $motionId, string $checkType): ResponseInterface
    {
        $motion = $this->consultation->getMotion($motionId);
        if (!$motion) {
            return new RedirectResponse(UrlHelper::createUrl('admin/motion-list/index'));
        }
        $this->checkConsistency($motion);

        $result = null;
        if ($checkType === 'prefix') {
            // Returns true, if the provided motion prefix does not exist in the specified consultation yet
            if ($this->getHttpRequest()->get('newConsultationId')) {
                $consultationId = intval($this->getHttpRequest()->get('newConsultationId'));
            } else {
                $consultationId = $this->consultation->id;
            }

            $newMotionPrefix = $this->getHttpRequest()->get('newMotionPrefix');
            /** @var Consultation $newConsultation */
            $newConsultation = array_values(array_filter($this->site->consultations, function(Consultation $con) use ($consultationId) {
                return ($con->id === $consultationId);
            }))[0];

            $existingMotion = array_filter($newConsultation->motions, function(Motion $cmpMotion) use ($newMotionPrefix) {
                return (mb_strtolower($cmpMotion->titlePrefix) === mb_strtolower($newMotionPrefix));
            });

            // If the motion is copied (not moved) within the same consultation, then the new motion could collide with the old one.
            // If it's moved, however, we can ignore the old motion.
            if ($this->getHttpRequest()->get('operation') === 'move') {
                $existingMotion = array_filter($existingMotion, function(Motion $cmpMotion) use ($motion) {
                    return $cmpMotion->id !== $motion->id;
                });
            }
            $result = (count($existingMotion) === 0);
        }

        return new JsonResponse(['success' => $result]);
    }
}
