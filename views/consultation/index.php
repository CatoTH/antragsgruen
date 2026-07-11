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
 * @var MotionComment[] $myMotionComments
 * @var AmendmentComment[] $myAmendmentComments
 * @var HashedStaticCache $cache
 */

/** @var \app\controllers\ConsultationController $controller */
$controller               = $this->context;
$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'consultationIndex';
$this->title              = $consultation->title;

$layout->addJsTranslation('amend');

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

if ($consultation->getSettings()->hasCurrentlyDebated) {
    if (User::havePrivilege($consultation, Privileges::PRIVILEGE_DEBATE_MODERATION, null)) {
        echo $this->render('_index_debate_admin', ['consultation' => $consultation]);
    }
    echo $this->render('_index_debate', ['consultation' => $consultation]);
}

echo $this->render('_index_current_discussion', ['consultation' => $consultation]);

if ($myself) {
    echo $this->render('_index_my_motions', ['consultation' => $consultation, 'myself' => $myself]);
}

if ($consultation->getSettings()->hasSpeechLists) {
    $queue = $consultation->getActiveSpeechQueue();
    echo $this->render('@app/views/speech/_index_speech', [
        'queue' => $queue,
        'showHeader' => true,
        'headingLevel' => 2,
    ]);
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
