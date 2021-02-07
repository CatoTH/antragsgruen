<?php

use app\components\Tools;
use app\models\db\{Amendment, AmendmentSupporter, Motion, MotionSupporter, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var Motion[] $motions
 * @var User|null $myself
 * @var MotionSupporter[] $myMotions
 * @var AmendmentSupporter[] $myAmendments
 */

/** @var \app\controllers\ConsultationController $controller */
$controller               = $this->context;
$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'consultationIndex';
$this->title              = $consultation->title;

$contentAdmin = User::havePrivilege($consultation, User::PRIVILEGE_CONTENT_EDIT);

if ($contentAdmin) {
    $layout->loadCKEditor();
    $layout->loadDatepicker();
}


echo '<h1>' . Html::encode($consultation->title) . '</h1>';

echo $layout->getMiniMenu('sidebarSmall');

echo $this->render('_index_welcome_content', ['consultation' => $consultation]);
echo $this->render('_index_phases_progress', ['consultation' => $consultation]);

echo $controller->showErrors();

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
            $motionLink = \app\components\UrlHelper::createMotionUrl($motion);
            echo Html::a(Html::encode($motion->getTitleWithPrefix()), $motionLink, ['class' => 'motion' . $motion->id]);
            echo ' (' . Html::encode($title) . ')';
            echo ': ' . Html::encode($motion->getStatusNames()[$motion->status]);
            echo '</div>';
            if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . Yii::t('motion', 'support_collect_status') . ': ';
                echo count($motion->getSupporters());
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
            $amendmentUrl = \app\components\UrlHelper::createAmendmentUrl($amendment);
            echo Html::a(Html::encode($amendment->getTitle()), $amendmentUrl, ['class' => 'amendment' . $amendment->id]);
            echo ' (' . Html::encode($title) . ')';
            echo ': ' . Html::encode($amendment->getStatusNames()[$amendment->status]);
            echo '</div>';
            if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . Yii::t('motion', 'support_collect_status') . ': ';
                echo count($amendment->getSupporters());
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


if (count($consultation->motionTypes) > 0) {
    echo $this->render($consultation->getSettings()->getStartLayoutView(), [
        'consultation' => $consultation,
        'layout' => $layout,
        'admin' => $contentAdmin,
    ]);
}
