<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\{IMotion, MotionSupporter, User, Motion};
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\models\http\RedirectResponse;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};

class Step6
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V7)) {
            return null;
        }

        if (Workflow::shouldPublishRecommendationV5($motion) && !$motion->isProposalPublic()) {
            return new AdminTodoItem(
                'todoDbwvSetPp' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Verfahrensvorschlag veröffentlichen',
                UrlHelper::createMotionUrl($motion),
                Tools::dateSql2timestamp($motion->dateCreation),
                $motion->getInitiatorsStr(),
                AdminTodoItem::TARGET_MOTION,
                $motion->id,
                $motion->getFormattedTitlePrefix(),
            );
        }

        $isScreening = in_array($motion->status, $motion->getMyConsultation()->getStatuses()->getScreeningStatuses(), true);
        $canScreen = User::havePrivilege($motion->getMyConsultation(), Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion));
        if ($isScreening && $canScreen) {
            $description = \Yii::t('admin', 'todo_from') . ': ' . $motion->getInitiatorsStr();

            return new AdminTodoItem(
                'motionScreen' . $motion->id,
                $motion->getTitleWithPrefix(),
                str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, \Yii::t('admin', 'todo_motion_screen')),
                UrlHelper::createUrl(['/admin/motion-list/index']),
                Tools::dateSql2timestamp($motion->dateCreation),
                $description,
                AdminTodoItem::TARGET_MOTION,
                $motion->id,
                $motion->getFormattedTitlePrefix(),
            );
        }
        if (Workflow::canSetResolutionV6($motion) && $motion->proposalVisibleFrom !== null) {
            return new AdminTodoItem(
                'todoDbwvSetPp' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Beschluss erarbeiten',
                UrlHelper::createMotionUrl($motion),
                Tools::dateSql2timestamp($motion->dateCreation),
                $motion->getInitiatorsStr(),
                AdminTodoItem::TARGET_MOTION,
                $motion->id,
                $motion->getFormattedTitlePrefix(),
            );
        }

        return null;
    }

    public static function renderMotionAdministration(Motion $motion): string
    {
        $html = '';

        if (Workflow::canSetResolutionV6($motion)) {
            RequestContext::getController()->layoutParams->loadCKEditor();

            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_6_decide', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function setDecision(Motion $motion, bool $followProposal, int $status, ?string $comment, bool $protocolPublic, ?string $protocol): RedirectResponse
    {
        if (!Workflow::canSetResolutionV6($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version === Workflow::STEP_V6) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V7)) {
                throw new Access('A new version of this motion was already created');
            }

            if ($followProposal) {
                $newInitiator = new MotionSupporter();
                $newInitiator->position = 0;
                $newInitiator->dateCreation = date('Y-m-d H:i:s');
                $newInitiator->personType = MotionSupporter::PERSON_ORGANIZATION;
                $newInitiator->role = MotionSupporter::ROLE_INITIATOR;
                $newInitiator->organization = $motion->getMyConsultation()->title;
                $newInitiator->resolutionDate = date('Y-m-d H:i:s');

                $v7Motion = $motion->followProposalAndCreateNewVersion(Workflow::STEP_V7, Motion::STATUS_ACCEPTED, [$newInitiator]);
            } else {
                if ($status === IMotion::STATUS_MODIFIED_ACCEPTED) {
                    $motion->setProtocol($protocol, $protocolPublic);

                    return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'merge-amendments-init'));
                }

                $v7Motion = MotionDeepCopy::copyMotion(
                    $motion,
                    $motion->getMyMotionType(),
                    $motion->agendaItem,
                    $motion->titlePrefix,
                    Workflow::STEP_V7,
                    true
                );

                $v7Motion->status = $status;
                $v7Motion->proposalComment = $comment;
                $v7Motion->save();
            }
        } else {
            $v7Motion = $motion;

            $v7Motion->status = $status;
            $v7Motion->proposalComment = $comment;
            $v7Motion->save();
        }
        unset($motion);

        $v7Motion->setProtocol($protocol, $protocolPublic);

        AdminTodoItem::flushConsultationTodoCount();

        return new RedirectResponse(UrlHelper::createMotionUrl($v7Motion));
    }
}
