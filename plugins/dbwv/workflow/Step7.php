<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\{IMotion, MotionSupporter, Motion};
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};

class Step7
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V8)) {
            return null;
        }

        if (Workflow::canPublishResolutionV7($motion) && in_array($motion->status, [IMotion::STATUS_ACCEPTED, IMotion::STATUS_MODIFIED_ACCEPTED])) {
            return new AdminTodoItem(
                'todoDbwvPublishResolution' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Beschluss veröffentlichen',
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
        if (!Workflow::canPublishResolutionV7($motion) || !in_array($motion->status, [IMotion::STATUS_ACCEPTED, IMotion::STATUS_MODIFIED_ACCEPTED])) {
            return '';
        }

        return RequestContext::getController()->renderPartial(
            '@app/plugins/dbwv/views/admin_step_7_publish_resolution', ['motion' => $motion]
        );
    }

    public static function saveResolution(Motion $motion, array $postparams): Motion {
        if (!Workflow::canPublishResolutionV7($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V7) {
            throw new Access('Not allowed to perform this action (in this state)');
        }
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V8)) {
            throw new Access('A new version of this motion was already created');
        }

        $v8Motion = MotionDeepCopy::copyMotion(
            $motion,
            $motion->getMyMotionType(),
            $motion->agendaItem,
            $postparams['motionPrefix'],
            Workflow::STEP_V8,
            true,
            [MotionDeepCopy::SKIP_NON_AMENDABLE, MotionDeepCopy::SKIP_COMMENTS, MotionDeepCopy::SKIP_SUPPORTERS, MotionDeepCopy::SKIP_PROPOSED_PROCEDURE]
        );

        $v8Motion->status = IMotion::STATUS_RESOLUTION_FINAL;
        $v8Motion->save();

        $newProposer = new MotionSupporter();
        $newProposer->motionId = $v8Motion->id;
        $newProposer->position = 0;
        $newProposer->userId = null;
        $newProposer->role = MotionSupporter::ROLE_INITIATOR;
        $newProposer->personType = MotionSupporter::PERSON_ORGANIZATION;
        $newProposer->name = '';
        $newProposer->organization = $motion->getMyConsultation()->title;
        $newProposer->dateCreation = date('Y-m-d H:i:s');
        $newProposer->save();

        AdminTodoItem::flushConsultationTodoCount();

        return $v8Motion;
    }
}
