<?php

declare(strict_types=1);

namespace app\plugins\dbwv\workflow;

use app\components\{MotionNumbering, RequestContext, Tools, UrlHelper};
use app\models\AdminTodoItem;
use app\models\forms\MotionDeepCopy;
use app\models\db\{ConsultationSettingsTag, IMotion, Motion};
use app\models\exceptions\{Access, NotFound};

class Step1
{
    public static function getAdminTodo(Motion $motion): ?AdminTodoItem
    {
        if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V2)) {
            return null;
        }
        if (count($motion->getPublicTopicTags()) === 0 && Workflow::canAssignTopic($motion)) {
            return new AdminTodoItem(
                'todoDbwvAssignTopic' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Sachgebiet zuordnen',
                UrlHelper::createMotionUrl($motion),
                Tools::dateSql2timestamp($motion->dateCreation),
                $motion->getInitiatorsStr(),
                AdminTodoItem::TARGET_MOTION,
                $motion->id,
                $motion->getFormattedTitlePrefix(),
            );
        }
        if (count($motion->getPublicTopicTags()) > 0 && $motion->titlePrefix === '' && Workflow::canMakeEditorialChangesV1($motion)) {
            return new AdminTodoItem(
                'todoDbwvEditorial' . $motion->id,
                $motion->getTitleWithPrefix(),
                'Für die Antragsversammlung aufbereiten',
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
        if (!Workflow::canMakeEditorialChangesV1($motion)) {
            return '';
        }

        return RequestContext::getController()->renderPartial(
            '@app/plugins/dbwv/views/admin_step_1_assign_number', ['motion' => $motion]
        );
    }

    public static function saveEditorial(Motion $motion, array $postparams): Motion
    {
        if (!Workflow::canMakeEditorialChangesV1($motion)) {
            throw new Access('Not allowed to perform this action (generally)');
        }
        if (!in_array($motion->version, [Workflow::STEP_V1, Workflow::STEP_V2, true])) {
            throw new Access('Not allowed to perform this action (in this state)');
        }

        if ($motion->version === Workflow::STEP_V1) {
            if (MotionNumbering::findMotionInHistoryOfVersion($motion, Workflow::STEP_V2)) {
                throw new Access('A new version of this motion was already created');
            }
            $v2Motion = MotionDeepCopy::copyMotion(
                $motion,
                $motion->getMyMotionType(),
                $motion->agendaItem,
                $postparams['motionPrefix'],
                Workflow::STEP_V2,
                true
            );
        } else {
            $v2Motion = $motion;
        }
        unset($motion);

        if (count($v2Motion->getPublicTopicTags()) > 0) {
            if ($postparams['subtag'] === 'new') {
                $tag = $v2Motion->getPublicTopicTags()[0];
                $createTitle = trim($postparams['subtagNew']);
                $newTag = $tag->createSubtagOfType(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, $createTitle);
                $setTagIds = [$newTag->id];
            } elseif ($postparams['subtag']) {
                $tag = $v2Motion->getPublicTopicTags()[0];
                $subtag = $v2Motion->getMyConsultation()->getTagById(intval($postparams['subtag']));
                if (!$subtag || $subtag->type !== ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE || $subtag->parentTagId !== $tag->id) {
                    throw new NotFound('Tag not found');
                }
                $setTagIds = [$subtag->id];
            } else {
                $setTagIds = [];
            }
            MotionNumbering::updateAllVersionsOfMotion($v2Motion, true, function (Motion $mot) use ($setTagIds) {
                $mot->setTags(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, $setTagIds);
            });
        }

        MotionNumbering::updateAllVersionsOfMotion($v2Motion, true, function (Motion $mot) use ($postparams) {
            $mot->titlePrefix = $postparams['motionPrefix'];
            $mot->save();
        });

        if (!MotionNumbering::findMotionInHistoryOfVersion($v2Motion, Workflow::STEP_V3)) {
            $v2Motion->status = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
            $v2Motion->save();
        }

        AdminTodoItem::flushConsultationTodoCount();

        return $v2Motion;
    }
}
