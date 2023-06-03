<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\models\AdminTodoItem;
use app\models\db\User;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\components\{MotionNumbering, Tools, UrlHelper};
use app\models\db\Motion;

class Step6
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V7)) {
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
                $description
            );
        }

        return null;
    }
}
