<?php

namespace app\models\siteSpecificBehavior;

use app\models\db\ConsultationMotionType;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\exceptions\NotAmendable;
use app\models\policies\All;
use app\models\db\Motion;
use app\models\policies\IPolicy;

class Permissions
{
    /**
     * @param Motion $motion
     * @return bool
     * @throws Internal
     */
    public function motionCanEdit($motion)
    {
        $consultation = $motion->getMyConsultation();

        if ($motion->status == Motion::STATUS_DRAFT) {
            $hadLoggedInUser = false;
            foreach ($motion->motionSupporters as $supp) {
                $currUser = User::getCurrentUser();
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    if ($currUser && $currUser->id == $supp->userId) {
                        return true;
                    }
                }
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId === null) {
                    if ($currUser && $currUser->hasPrivilege($consultation, User::PRIVILEGE_MOTION_EDIT)) {
                        return true;
                    }
                }
            }
            if ($hadLoggedInUser) {
                return false;
            } else {
                if ($motion->motionType->getMotionPolicy()->getPolicyID() == All::getPolicyID()) {
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

    /**
     * @param Motion $motion
     * @return bool
     */
    public function motionCanWithdraw($motion)
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

    /**
     * @param Motion $motion
     * @return bool
     */
    public function motionCanMergeAmendments($motion)
    {
        $replacedByMotions = array_filter($motion->replacedByMotions, function (Motion $motion) {
            $draftStati = [
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE,
                Motion::STATUS_WITHDRAWN_INVISIBLE,
                Motion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
                Motion::STATUS_INLINE_REPLY,
            ];
            return !in_array($motion->status, $draftStati);
        });
        if (count($replacedByMotions) > 0) {
            return false;
        }
        if (!$motion->getMyMotionType()->isInDeadline(ConsultationMotionType::DEADLINE_MERGING)) {
            return false;
        }
        if (User::havePrivilege($motion->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
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
     * @param Motion $motion
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @param bool $exceptions
     * @return bool
     * @throws NotAmendable
     * @throws Internal
     */
    public function isCurrentlyAmendable($motion, $allowAdmins = true, $assumeLoggedIn = false, $exceptions = false)
    {
        $iAmAdmin = User::havePrivilege($motion->getMyConsultation(), User::PRIVILEGE_ANY);

        if (!($allowAdmins && $iAmAdmin)) {
            if ($motion->nonAmendable) {
                if ($exceptions) {
                    throw new NotAmendable('Not amendable in the current state', false);
                } else {
                    return false;
                }
            }
            $notAmendableStati = [
                Motion::STATUS_DELETED,
                Motion::STATUS_DRAFT,
                Motion::STATUS_COLLECTING_SUPPORTERS,
                Motion::STATUS_SUBMITTED_UNSCREENED,
                Motion::STATUS_SUBMITTED_UNSCREENED_CHECKED,
                Motion::STATUS_DRAFT_ADMIN,
                Motion::STATUS_MODIFIED,
            ];
            if (in_array($motion->status, $notAmendableStati)) {
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
                $public = ($msg != '' && $policy->getPolicyID() != IPolicy::POLICY_NOBODY);
                throw new NotAmendable($msg, $public);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Motion $motion
     * @return bool
     * @throws Internal
     */
    public function motionCanFinishSupportCollection($motion)
    {
        if (!$motion->iAmInitiator()) {
            return false;
        }
        if ($motion->status != Motion::STATUS_COLLECTING_SUPPORTERS) {
            return false;
        }
        if ($motion->isDeadlineOver()) {
            return false;
        }
        $supporters    = count($motion->getSupporters());
        $minSupporters = $motion->motionType->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
        return ($supporters >= $minSupporters);
    }
}
