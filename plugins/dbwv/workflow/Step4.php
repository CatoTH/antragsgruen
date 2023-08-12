<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};
use app\models\AdminTodoItem;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion, MotionSupporter};
use app\models\exceptions\Access;
use app\models\forms\MotionDeepCopy;
use app\plugins\dbwv\Module;

class Step4
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V5)) {
            return null;
        }
        if (!in_array($motion->status, [
            IMotion::STATUS_RESOLUTION_FINAL,
            IMotion::STATUS_RESOLUTION_FINAL,
            IMotion::STATUS_ACCEPTED,
            IMotion::STATUS_MODIFIED_ACCEPTED,
        ])) {
            return null;
        }

        if (Workflow::canMoveToMainV4($motion)) {
            return new AdminTodoItem(
                'todoDbwvMoveToMain' . $motion->id,
                $motion->getTitleWithPrefix(),
                'In die Hauptversammlung übernehmen',
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

        if (Workflow::canMoveToMainV4($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_4_next', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function getCorrespondingTagFromMain(ConsultationSettingsTag $lvTag): ConsultationSettingsTag
    {
        $main = Module::getBundConsultation();
        foreach ($main->tags as $mainTag) {
            if (mb_strtolower($mainTag->title) === mb_strtolower($lvTag->title) && $mainTag->type === $lvTag->type) {
                return $mainTag;
            }
        }

        if ($lvTag->parentTag) {
            $mainParentTag = self::getCorrespondingTagFromMain($lvTag->parentTag);
        } else {
            $mainParentTag = null;
        }
        $mainTag = new ConsultationSettingsTag();
        $mainTag->consultationId = $main->id;
        $mainTag->parentTagId = $mainParentTag?->id;
        $mainTag->type = $lvTag->type;
        $mainTag->title = $lvTag->title;
        $mainTag->position = 0;
        $mainTag->save();

        return $mainTag;
    }

    public static function moveToMain(Motion $motion): Motion
    {
        if (!Workflow::canMoveToMainV4($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V4) {
            throw new Access('Not allowed to perform this action (in this state)');
        }
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V5)) {
            throw new Access('A new version of this motion was already created');
        }

        $targetType = Module::getCorrespondingBundMotionType($motion->getMyMotionType());

        $v5Motion = MotionDeepCopy::copyMotion(
            $motion,
            $targetType,
            null,
            '',
            Workflow::STEP_V5,
            true,
            [MotionDeepCopy::SKIP_SUPPORTERS, MotionDeepCopy::SKIP_COMMENTS, MotionDeepCopy::SKIP_PROPOSED_PROCEDURE]
        );
        $v5Motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED;
        $v5Motion->save();

        $newProposer = new MotionSupporter();
        $newProposer->motionId = $v5Motion->id;
        $newProposer->position = 0;
        $newProposer->userId = null;
        $newProposer->role = MotionSupporter::ROLE_INITIATOR;
        $newProposer->personType = MotionSupporter::PERSON_ORGANIZATION;
        $newProposer->name = '';
        $newProposer->organization = $motion->getMyConsultation()->title;
        $newProposer->dateCreation = date('Y-m-d H:i:s');
        $newProposer->save();

        foreach ($motion->tags as $tag) {
            $newTag = self::getCorrespondingTagFromMain($tag);
            $v5Motion->link('tags', $newTag);
        }

        AdminTodoItem::flushConsultationTodoCount();

        return $v5Motion;
    }
}
