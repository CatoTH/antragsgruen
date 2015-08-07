<?php

namespace app\commands;

use app\components\HTMLTools;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\sectionTypes\ISectionType;
use yii\console\Controller;

/**
 * Tool to fix some problems (usually only during development)
 * @package app\commands
 */
class BugfixController extends Controller
{
    /**
     * Runs cleanSimpleHtml for a given motion
     * @param int $motionId
     */
    public function actionFixMotionText($motionId)
    {
        /** @var Motion $motion */
        $motion = Motion::findOne($motionId);
        if (!$motion) {
            $this->stderr('Motion not found' . "\n");
        }
        $changedCount = 0;
        foreach ($motion->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $newText = HTMLTools::cleanSimpleHtml($section->data);
            if ($newText != $section->data) {
                $changedCount++;
                $section->data = HTMLTools::cleanSimpleHtml($section->data);
                $section->save();
            }
        }
        if ($changedCount > 0) {
            $this->stdout('Changed section(s): ' . $changedCount . "\n");
        } else {
            $this->stdout('No sections changed' . "\n");
        }
    }

    /**
     * Runs cleanSimpleHtml for a given amendment
     * @param int $amendmentId
     */
    public function actionFixAmendmentText($amendmentId)
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($amendmentId);
        if (!$amendment) {
            $this->stderr('Amendment not found' . "\n");
        }
        $changedCount = 0;
        foreach ($amendment->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $newText = HTMLTools::cleanSimpleHtml($section->dataRaw);
            if ($newText != $section->data) {
                $changedCount++;
                $section->data = $newText;
                $section->save();
            }
        }
        if ($changedCount > 0) {
            $this->stdout('Changed section(s): ' . $changedCount . "\n");
        } else {
            $this->stdout('No sections changed' . "\n");
        }
    }

    /**
     * Runs cleanSimpleHtml on all texts
     */
    public function actionFixAllTexts()
    {
        /** @var Amendment[] $amendments */
        $amendments = Amendment::find()->where('status != ' . Amendment::STATUS_DELETED)->all();
        foreach ($amendments as $amend) {
            $this->actionFixAmendmentText($amend->id);
        }

        /** @var Motion[] $motions */
        $motions = Motion::find()->where('status != ' . Motion::STATUS_DELETED)->all();
        foreach ($motions as $motion) {
            $this->actionFixMotionText($motion->id);
        }
    }
}
