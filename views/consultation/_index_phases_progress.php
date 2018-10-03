<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

$namedPhases = [];

foreach ($consultation->motionTypes as $motionType) {
    foreach (\app\models\db\ConsultationMotionType::$DEADLINE_TYPES as $deadlineType) {
        foreach ($motionType->getDeadlinesByType($deadlineType) as $deadline) {
            if (!$deadline['title']) {
                continue;
            }
            $namedPhases[$deadline['title']] = [
                'title'       => $deadline['title'],
                'start'       => $deadline['start'],
                'end'         => $deadline['end'],
                'permissions' => [],
            ];
        }
    }
}
foreach ($consultation->motionTypes as $motionType) {
    foreach (ConsultationMotionType::$DEADLINE_TYPES as $deadlineType) {
        switch ($deadlineType) {
            case ConsultationMotionType::DEADLINE_MOTIONS:
                $deadlineName = $motionType->createTitle;
                break;
            case ConsultationMotionType::DEADLINE_AMENDMENTS:
                $deadlineName = \Yii::t('admin', 'motion_type_perm_amend');
                break;
            case ConsultationMotionType::DEADLINE_COMMENTS:
                $deadlineName = \Yii::t('admin', 'motion_type_perm_comment');
                break;
            case ConsultationMotionType::DEADLINE_MERGING:
                $deadlineName = \Yii::t('admin', 'motion_type_perm_merge');
                break;
            default:
                $deadlineName = '';
        }
        foreach ($motionType->getDeadlinesByType($deadlineType) as $deadline) {
            foreach ($namedPhases as $title => $namedPhase) {
                if ($namedPhase['start'] === $deadline['start'] && $namedPhase['end'] === $deadline['end']) {
                    if (!in_array($deadlineName, $namedPhase['permissions'])) {
                        $namedPhases[$title]['permissions'][] = $deadlineName;
                    }
                }
            }
        }
    }
}

usort($namedPhases, function ($phase1, $phase2) {
    if (!$phase1['start'] && !$phase2['start']) {
        return 0;
    }
    if (!$phase1['start'] && $phase2['start']) {
        return -1;
    }
    if ($phase1['start'] && !$phase2['start']) {
        return 1;
    }
    $ts1 = Tools::dateSql2timestamp($phase1['start']);
    $ts2 = Tools::dateSql2timestamp($phase2['start']);
    if ($ts1 < $ts2) {
        return -1;
    } elseif ($ts1 > $ts2) {
        return 1;
    } else {
        return 0;
    }
});

if (count($namedPhases) === 1) {
    echo '<div class="alert alert-info">';
    echo \Yii::t('con', 'current_phase') . ': ' . Html::encode($namedPhases[0]['title']);
    echo '</div>';
} elseif (count($namedPhases) > 1) {
    ?>
    <div class="consultationPhasesWizard fuelux">
        <div class="wizard">
            <ul class="steps">
                <?php
                foreach ($namedPhases as $namedPhase) {
                    if (ConsultationMotionType::isInDeadlineRange($namedPhase)) {
                        echo '<li class="active">';
                    } else {
                        echo '<li>';
                    }
                    echo '<div class="step-content">';
                    echo '<div class="title">' . HTMLTools::encodeAddShy($namedPhase['title']) . '</div>';
                    echo '<div class="permissions">';
                    foreach ($namedPhase['permissions'] as $permission) {
                        echo Html::encode($permission) . '<br>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
}
