<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\{IMotion, MotionSupporter, Motion};
use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\models\http\RedirectResponse;

class Step3
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
            return null;
        }
        if (Workflow::canSetRecommendationV2($motion) && !$motion->isProposalPublic()) {
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
        if (Workflow::canSetResolutionV3($motion) && $motion->isProposalPublic()) {
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

        if (Workflow::canSetResolutionV3($motion)) {
            RequestContext::getController()->layoutParams->loadCKEditor();

            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_3_decide', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function setDecision(Motion $motion, bool $followProposal, int $status, ?string $comment, bool $protocolPublic, ?string $protocol): RedirectResponse
    {
        if (!Workflow::canSetResolutionV3($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version === Workflow::STEP_V3) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
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

                $v4Motion = $motion->followProposalAndCreateNewVersion(Workflow::STEP_V4, Motion::STATUS_RESOLUTION_FINAL, [$newInitiator]);
            } else {
                if ($status === IMotion::STATUS_MODIFIED_ACCEPTED) {
                    $motion->setProtocol($protocol, $protocolPublic);

                    return new RedirectResponse(UrlHelper::createMotionUrl($motion, 'merge-amendments-init'));
                }

                $v4Motion = MotionDeepCopy::copyMotion(
                    $motion,
                    $motion->getMyMotionType(),
                    $motion->agendaItem,
                    $motion->titlePrefix,
                    Workflow::STEP_V4,
                    true,
                    [MotionDeepCopy::SKIP_PROPOSED_PROCEDURE]
                );
                $v4Motion->status = $status;
                $v4Motion->proposalComment = $comment;
                $v4Motion->save();
            }

            foreach ($motion->getProposedProcedureTags() as $tag) {
                $v4Motion->link('tags', $tag);
            }
        } else {
            $v4Motion = $motion;

            $v4Motion->status = $status;
            $v4Motion->proposalComment = $comment;
            $v4Motion->save();
        }
        unset($motion);

        $v4Motion->setProtocol($protocol, $protocolPublic);

        AdminTodoItem::flushConsultationTodoCount();

        return new RedirectResponse(UrlHelper::createMotionUrl($v4Motion));
    }
}
