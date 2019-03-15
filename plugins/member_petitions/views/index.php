<?php

/**
 * @var yii\web\View $this
 */

use app\components\UrlHelper;
use app\plugins\member_petitions\Tools;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller               = $this->context;
$layout                   = $controller->layoutParams;
$user                     = \app\models\db\User::getCurrentUser();
$site                     = $controller->site;
$layout->bodyCssClasses[] = 'memberPetitionList memberPetitionHome';

$myConsultations = Tools::getUserConsultations($site, $user);

$this->title = \Yii::t('member_petitions', 'title');
?>
    <h1><?= \Yii::t('member_petitions', 'title') ?></h1>
    <div class="content">
        <!--
        <section class="createPetition" data-antragsgruen-widget="memberpetitions/HomeCreatePetitions">
            <button type="button" class="btn btn-primary pull-right showWidget">
                <span class="glyphicon glyphicon-plus"></span>
                <?= \Yii::t('member_petitions', 'index_create') ?>
            </button>
            <div class="alert alert-success hidden addWidget">
                <?= \Yii::t('member_petitions', 'index_create_hint') ?>
                <?php
        foreach ($myConsultations as $consultation) {
            echo '<div class="createRow">';
            if (count($consultation->motionTypes) === 0) {
                continue;
            }
            $createUrl = UrlHelper::createUrl([
                '/motion/create',
                'consultationPath' => $consultation->urlPath,
                'motionTypeId'     => $consultation->motionTypes[0]->id,
            ]);
            echo Html::a(Html::encode($consultation->title), $createUrl, ['class' => 'btn btn-primary']);
            echo '</div>';
        }
        ?>
            </div>
        </section>
        -->

        <?php
        if (count($myConsultations) > 0) {
            echo \Yii::t('member_petitions', 'index_orga_hint');
            echo '<div class="orgaList">';
            foreach ($myConsultations as $consultation) {
                $url   = UrlHelper::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
                $title = '<span class="glyphicon glyphicon-chevron-right"></span> ' . Html::encode($consultation->title);
                echo '<div class="orgaListItem">' . Html::a($title, $url, ['class' => 'btn btn-primary']) . '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
<?php
/*
foreach (Tools::getUserConsultations($controller->site, $user) as $consultation) {
    $url       = UrlHelper::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
    $gotoTitle = '<span class="glyphicon glyphicon-chevron-right"></span> Zur Verbands-Seite';
    ?>
    <h2 class="green">
        <?= Html::encode($consultation->title) ?>
        <?= Html::a($gotoTitle, $url, ['class' => 'pull-right orgaLink']) ?>
    </h2>
    <div class="content">
        <?php
        if (Tools::isConsultationFullyConfigured($consultation)) {
            echo $this->render('_motion_list', ['motions' => array_merge(
                Tools::getMotionsInDiscussion($consultation),
                Tools::getMotionsAnswered($consultation),
                Tools::getMotionsUnanswered($consultation),
                Tools::getMotionsCollecting($consultation)
            ), 'bold' => 'organization']);
        } else {
            echo '<div class="alert">Nicht vollständig eingerichtet.</div>';
        }
        ?>
    </div>
    <?php
}

echo $this->render('_motion_sorter', ['myConsultations' => $myConsultations, 'bold' => 'organization']);

*/

$myMotions  = Tools::getMyMotions($controller->site);
$mySupports = Tools::getSupportedMotions($controller->site);

if (count($myMotions) > 0) {
    ?>
    <h2 class="green"><?= \Yii::t('member_petitions', 'index_my_petitions') ?></h2>
    <div class="content">
        <?php
        echo $this->render('_motion_list', [
            'motions'          => $myMotions,
            'bold'             => 'organization',
            'statusClustering' => false
        ]);
        ?>
    </div>
    <?php
}

if (count($mySupports) > 0) {
    ?>
    <h2 class="green"><?= \Yii::t('member_petitions', 'index_my_supports') ?></h2>
    <div class="content">
        <?php
        echo $this->render('_motion_list', [
            'motions'          => $mySupports,
            'bold'             => 'organization',
            'statusClustering' => false,
        ]);
        ?>
    </div>
    <?php
}
