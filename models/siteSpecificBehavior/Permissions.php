<?php

namespace app\models\siteSpecificBehavior;

use app\models\db\{ConsultationMotionType, ConsultationUserGroup, IMotion, MotionSupporter, User, Motion};
use app\models\exceptions\{Internal, NotAmendable};
use app\models\policies\{All, IPolicy};
use app\models\supportTypes\SupportBase;

class Permissions
{
    /**
     * @throws Internal
     */
    public function motionCanEdit(Motion $motion): bool
    {
        $consultation = $motion->getMyConsultation();

        if ($motion->status === Motion::STATUS_DRAFT) {
            // As long as motions are not confirmed, the following can edit and confirm them:
            // - The account that was used to create the motion, if an account was used
            // - Everyone, if no account was used and "All" is selected
            // - Admins
            $hadLoggedInUser = false;
            $currUser = User::getCurrentUser();
            if ($currUser && $currUser->hasPrivilege($consultation, ConsultationUserGroup::PRIVILEGE_MOTION_EDIT)) {
                return true;
            }
            foreach ($motion->motionSupporters as $supp) {
                if ($supp->role === MotionSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    if ($currUser && $currUser->id === $supp->userId) {
                        return true;
                    }
                }
            }
            if ($hadLoggedInUser) {
                return false;
            } else {
                if ($motion->getMyMotionType()->getMotionPolicy()->getPolicyID() === All::getPolicyID()) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        if ($motion->textFixed) {
            return false;
        }

        if ($consultation->getSettings()->iniatorsMayEdit && $motion->iAmInitiator()) {
            if ($motion->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
                if (count($motion->getVisibleAmendments()) > 0) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    public function motionCanWithdraw(Motion $motion): bool
    {
        if (!in_array($motion->status, [
            Motion::STATUS_SUBMITTED_SCREENED,
            Motion::STATUS_SUBMITTED_UNSCREENED,
            Motion::STATUS_COLLECTING_SUPPORTERS
        ])
        ) {
            return false;
        }
        return $motion->iAmInitiator();
    }

    public function motionCanMergeAmendments(Motion $motion): bool
    {
        $replacedByMotions = array_filter($motion->replacedByMotions, function (Motion $motion) {
            $draftStatuses = [
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE,
                Motion::STATUS_WITHDRAWN_INVISIBLE,
                Motion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
                Motion::STATUS_PROPOSED_MODIFIED_MOTION,
                Motion::STATUS_INLINE_REPLY,
                Motion::STATUS_DRAFT_ADMIN,
            ];
            return !in_array($motion->status, $draftStatuses);
        });
        if (count($replacedByMotions) > 0) {
            return false;
        }
        if (!$motion->getMyMotionType()->isInDeadline(ConsultationMotionType::DEADLINE_MERGING)) {
            return false;
        }
        if (User::havePrivilege($motion->getMyConsultation(), ConsultationUserGroup::PRIVILEGE_MOTION_EDIT)) {
            return true;
        } elseif ($motion->iAmInitiator()) {
            $policy = $motion->getMyMotionType()->initiatorsCanMergeAmendments;
            if ($policy === ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION) {
                return true;
            } elseif ($policy === ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION) {
                return (count($motion->getVisibleAmendments()) === 0);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @throws NotAmendable
     * @throws Internal
     */
    public function isCurrentlyAmendable(Motion $motion, bool $allowAdmins = true, bool $assumeLoggedIn = false, bool $exceptions = false): bool
    {
        $iAmAdmin = User::havePrivilege($motion->getMyConsultation(), ConsultationUserGroup::PRIVILEGE_ANY);

        if (!($allowAdmins && $iAmAdmin)) {
            if ($motion->nonAmendable) {
                if ($exceptions) {
                    throw new NotAmendable('Not amendable in the current state', false);
                } else {
                    return false;
                }
            }
            $notAmendableStatuses = [
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_COLLECTING_SUPPORTERS,
                Motion::STATUS_SUBMITTED_UNSCREENED,
                Motion::STATUS_SUBMITTED_UNSCREENED_CHECKED,
                Motion::STATUS_DRAFT_ADMIN,
                Motion::STATUS_MODIFIED,
                Motion::STATUS_RESOLUTION_PRELIMINARY,
                Motion::STATUS_RESOLUTION_FINAL,
                Motion::STATUS_MOVED,
            ];
            if (in_array($motion->status, $notAmendableStatuses)) {
                if ($exceptions) {
                    throw new NotAmendable('Not amendable in the current state', false);
                } else {
                    return false;
                }
            }
            if (!$motion->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
                if ($exceptions) {
                    throw new NotAmendable(\Yii::t('structure', 'policy_deadline_over'), true);
                } else {
                    return false;
                }
            }
        }
        $policy  = $motion->motionType->getAmendmentPolicy();
        $allowed = $policy->checkCurrUser($allowAdmins, $assumeLoggedIn);

        if (!$allowed) {
            if ($exceptions) {
                $msg    = $policy->getPermissionDeniedAmendmentMsg();
                $public = ($msg !== '' && $policy->getPolicyID() != IPolicy::POLICY_NOBODY);
                throw new NotAmendable($msg, $public);
            } else {
                return false;
            }
        }
        return true;
    }

    public function canFinishSupportCollection(IMotion $motion, SupportBase $supportType): bool
    {
        if (!$motion->iAmInitiator()) {
            return false;
        }
        if ($motion->status !== Motion::STATUS_COLLECTING_SUPPORTERS) {
            return false;
        }
        if ($motion->isDeadlineOver()) {
            return false;
        }
        $supporters    = count($motion->getSupporters(true));
        $minSupporters = $supportType->getSettingsObj()->minSupporters;
        if ($supporters >= $minSupporters && !$motion->getMissingSupporterCountByGender($supportType, 'female')) {
            return true;
        } else {
            return false;
        }
    }
}
