<?php

use app\models\db\{Amendment, AmendmentComment, AmendmentSupporter, Consultation, Motion, MotionComment, MotionSupporter, User};
use app\components\{HashedStaticCache, MotionSorter, UrlHelper};
use app\models\settings\{Privileges, Consultation as ConsultationSettings};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Consultation $consultation
 * @var Motion[] $motions
 * @var User|null $myself
 * @var MotionSupporter[] $myMotions
 * @var AmendmentSupporter[] $myAmendments
 * @var MotionComment[] $myMotionComments
 * @var AmendmentComment[] $myAmendmentComments
 * @var HashedStaticCache $cache
 */

/** @var \app\controllers\ConsultationController $controller */
$controller               = $this->context;
$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'consultationIndex';
$this->title              = $consultation->title;

$contentAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null);

if ($contentAdmin) {
    $layout->loadCKEditor();
    $layout->loadDatepicker();
}

echo '<h1>' . Html::encode($consultation->title) . '</h1>';

echo $layout->getMiniMenu('sidebarSmall');

echo $controller->showErrors();

echo $this->render('_index_welcome_content', ['consultation' => $consultation]);
echo $this->render('_index_phases_progress', ['consultation' => $consultation]);

$getMyMotionAttrs = function (\app\models\db\IMotion $motion, \app\models\db\ISupporter $supporter) {
    $class = [];
    $title = '';
    switch ($supporter->role) {
        case MotionSupporter::ROLE_INITIATOR:
            $title = Yii::t('motion', 'Initiator');
            $class[] = 'initiator';
            break;
        case MotionSupporter::ROLE_SUPPORTER:
            $title = Yii::t('motion', 'Supporter');
            $class[] = 'supporter';
            break;
        case MotionSupporter::ROLE_LIKE:
            $title = Yii::t('motion', 'like');
            $class[] = 'like';
            break;
        case MotionSupporter::ROLE_DISLIKE:
            $title = Yii::t('motion', 'dislike');
            $class[] = 'dislike';
            break;
    }
    if ($motion->status === Motion::STATUS_WITHDRAWN) {
        $class[] = 'withdrawn';
    }
    if ($motion->status === Motion::STATUS_MOVED) {
        $class[] = 'moved';
    }
    return [implode(" ", $class), $title];
};


if ($myself) {
    if (count($myMotions)) {
        echo '<section class="sectionMyMotions" aria-labelledby="sectionMyMotionsTitle">';
        echo '<h2 class="green" id="sectionMyMotionsTitle">' . Yii::t('con', 'My Motions') . '</h2>';
        echo '<div class="content myImotionList myMotionList"><ul>';

        foreach ($myMotions as $motionSupport) {
            /** @var Motion $motion */
            $motion = $motionSupport->getIMotion();
            list($class, $title) = $getMyMotionAttrs($motion, $motionSupport);
            echo '<li class="' . $class . '"><div class="firstLine">';
            if ($motion->status === \app\models\db\IMotion::STATUS_DRAFT) {
                $motionLink = UrlHelper::createMotionUrl($motion, 'createconfirm', ['fromMode' => 'create']);
            } else {
                $motionLink = UrlHelper::createMotionUrl($motion);
            }
            echo Html::a(Html::encode($motion->getTitleWithPrefix()), $motionLink, ['class' => 'motion' . $motion->id]);
            echo ' (' . Html::encode($title) . ')';
            echo ': ' . Html::encode($motion->getMyConsultation()->getStatuses()->getStatusName($motion->status));
            echo '</div>';
            if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . Yii::t('motion', 'support_collect_status') . ': ';
                echo count($motion->getSupporters(true));
                echo ' <small>(' . Yii::t('motion', 'support_collect_min') . ': ';
                echo $motion->getMyMotionType()->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
                echo ')</small></div>';
            }
            echo "</li>\n";
        }
        echo '</ul></div>';
        echo '</section>';
    }

    if (count($myAmendments) > 0) {
        echo '<section class="sectionMyAmendments" aria-labelledby="sectionMyAmendmentsTitle">';
        echo '<h2 class="green" id="sectionMyAmendmentsTitle">' . Yii::t('con', 'My Amendments') . '</h2>';
        echo '<div class="content myImotionList myAmendmentList"><ul>';
        foreach ($myAmendments as $amendmentSupport) {
            /** @var Amendment $amendment */
            $amendment = $amendmentSupport->getIMotion();
            list($class, $title) = $getMyMotionAttrs($amendment, $amendmentSupport);
            echo '<li class="' . $class . '"><div class="firstLine">';
            if ($amendment->status === \app\models\db\IMotion::STATUS_DRAFT) {
                $amendmentUrl = UrlHelper::createAmendmentUrl($amendment, 'createconfirm', ['fromMode' => 'create']);
            } else {
                $amendmentUrl = UrlHelper::createAmendmentUrl($amendment);
            }
            echo Html::a(Html::encode($amendment->getTitle()), $amendmentUrl, ['class' => 'amendment' . $amendment->id]);
            echo ' (' . Html::encode($title) . ')';
            echo ': ' . Html::encode($amendment->getMyConsultation()->getStatuses()->getStatusName($amendment->status));
            echo '</div>';
            if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . Yii::t('motion', 'support_collect_status') . ': ';
                echo count($amendment->getSupporters(true));
                echo ' <small>(' . Yii::t('motion', 'support_collect_min') . ': ';
                echo $amendment->getMyMotionType()->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
                echo ')</small></div>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
        echo '</section>';
    }
}

if ($consultation->getSettings()->hasSpeechLists) {
    $queue = $consultation->getActiveSpeechQueue();
    echo $this->render('@app/views/speech/_index_speech', ['queue' => $queue]);
}

echo $this->render('@app/views/voting/_index_voting', ['assignedToMotion' => null]);


if ($contentAdmin && in_array($consultation->getSettings()->startLayoutType, [ConsultationSettings::START_LAYOUT_AGENDA_LONG, ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND, ConsultationSettings::START_LAYOUT_AGENDA])) {
    $cache->setSkipCache(true);
}

echo $cache->getCached(function () use ($consultation, $layout, $contentAdmin) {
    $output = '';
    $resolutionMode = $consultation->getSettings()->startLayoutResolutions;
    list($imotions, $resolutions) = MotionSorter::getIMotionsAndResolutions($consultation->motions);
    if (count($resolutions) > 0 && $resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_ABOVE) {
        $output .= $this->render('_index_resolutions', ['consultation' => $consultation, 'resolutions' => $resolutions]);
    }

    if (count($consultation->motionTypes) > 0 && $consultation->getSettings()->getStartLayoutView()) {
        if ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
            $toShowImotions = $resolutions;
        } else {
            $toShowImotions = $imotions;
        }
        $output .= $this->render($consultation->getSettings()->getStartLayoutView(), [
            'consultation' => $consultation,
            'layout' => $layout,
            'admin' => $contentAdmin,
            'imotions' => $toShowImotions,
            'isResolutionList' => ($resolutionMode === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT),
            'skipTitle' => false,
        ]);
    }
    return $output;
});

echo $this->render('_index_private_comment_list', [
    'myMotionComments' => $myMotionComments,
    'myAmendmentComments' => $myAmendmentComments,
]);
