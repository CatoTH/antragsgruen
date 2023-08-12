<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};
use app\models\forms\MotionDeepCopy;
use app\models\db\{Motion, User};
use app\models\exceptions\Access;

class Step2
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V3)) {
            return null;
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

        if (Workflow::canSetRecommendationV2($motion)) {
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

        if (Workflow::canMakeEditorialChangesV1($motion)) {
            $html .= RequestContext::getController()->renderPartial(
                '@app/plugins/dbwv/views/admin_step_1_assign_number', ['motion' => $motion]
            );
        }

        return $html;
    }

    public static function gotoNext(Motion $motion): Motion
    {
        if (!Workflow::canSetRecommendationV2($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if (!in_array($motion->version, [Workflow::STEP_V2, Workflow::STEP_V3, true])) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if ($motion->version === Workflow::STEP_V2) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V3)) {
                throw new Access('A new version of this motion was already created');
            }
            $v3Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $motion->titlePrefix,
                Workflow::STEP_V3,
                true
            );
        } else {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V4)) {
                throw new Access('A new version of this motion was already created');
            }
            $v3Motion = $motion;
        }
        unset($motion);

        AdminTodoItem::flushConsultationTodoCount();

        return $v3Motion;
    }
}
