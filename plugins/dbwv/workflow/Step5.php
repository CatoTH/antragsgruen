<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion};
use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};
use app\models\forms\MotionDeepCopy;
use app\models\exceptions\Access;

class Step5
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V6)) {
            return null;
        }

        if (Workflow::canSetRecommendationV5($motion)) {
            return new AdminTodoItem(
                'todoDbwvSetPp' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Verfahrensvorschlag erarbeiten',
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

        if (Workflow::canMakeEditorialChangesV5($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_5_assign_number', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function saveNumber(Motion $motion, array $postparams): Motion
    {
        if (!Workflow::canMakeEditorialChangesV5($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if ($motion->version !== Workflow::STEP_V5) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        $newTags = [];
        if (isset($postparams['tag']) && $postparams['tag'] > 0) {
            $newTags[] = intval($postparams['tag']);
        }
        $motion->setTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $newTags);

        $motion->titlePrefix = $postparams['motionPrefix'];
        $motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        $motion->save();

        return $motion;
    }

    public static function gotoNext(Motion $motion): Motion
    {
        if (!Workflow::canSetRecommendationV5($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if (!in_array($motion->version, [Workflow::STEP_V5, Workflow::STEP_V6, true])) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if ($motion->version === Workflow::STEP_V5) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V6)) {
                throw new Access('A new version of this motion was already created');
            }
            $v6Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $motion->titlePrefix,
                Workflow::STEP_V6,
                true
            );
        } else {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V7)) {
                throw new Access('A new version of this motion was already created');
            }
            $v6Motion = $motion;
        }
        unset($motion);

        AdminTodoItem::flushConsultationTodoCount();

        return $v6Motion;
    }
}
