<?php

/**
 * @var yii\web\View $this
 */

use app\components\UrlHelper;
use app\memberPetitions\Tools;
use app\models\db\Motion;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$user       = \app\models\db\User::getCurrentUser();
$layout->addCSS('css/memberpetitions.css');
$layout->bodyCssClasses[] = 'memberPetitionHome';

$this->title = 'Grüne Mitgliederbegehren';

?>
    <h1>Grüne Mitgliederbegehren</h1>
    <div class="content">

        <section class="createPetition" data-antragsgruen-widget="memberpetitions/HomeCreatePetitions">
            <button type="button" class="btn btn-primary pull-right showWidget">
                <span class="glyphicon glyphicon-plus"></span>
                Petition anlegen
            </button>
            <div class="alert alert-success hidden addWidget">
                Hiermit kannst du eine neue Petition anlegen.
                Wähle zunächst aus, an welchen Verband sich die Petition richtet:
                <?php
                foreach (Tools::getUserConsultations($controller->site, $user) as $consultation) {
                    echo '<div class="createRow">';
                    if (count($consultation->motionTypes) === 0) {
                        continue;
                    }
                    $createUrl = UrlHelper::createUrl([
                        'motion/create',
                        'consultationPath' => $consultation->urlPath,
                        'motionTypeId'     => $consultation->motionTypes[0]->id,
                    ]);
                    echo Html::a(Html::encode($consultation->title), $createUrl, ['class' => 'btn btn-primary']);
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        Du bist Mitglied in folgenden Verbänden, die dieses Angebot nutzen:
    </div>
<?php
foreach (Tools::getUserConsultations($controller->site, $user) as $consultation) {
    $url       = UrlHelper::createUrl(['consultation/index', 'consultationPath' => $consultation->urlPath]);
    $gotoTitle = '<span class="glyphicon glyphicon-chevron-right"></span> Zur Verbands-Seite';
    ?>
    <h2 class="green">
        <?= Html::encode($consultation->title) ?>
        <?= Html::a($gotoTitle, $url, ['class' => 'pull-right orgaLink']) ?>
    </h2>
    <div class="content">
        <?php
        echo $this->render('_motion_list', ['motions' => array_merge(
            Tools::getMotionsAnswered($consultation),
            Tools::getMotionsUnanswered($consultation),
            Tools::getMotionsCollecting($consultation)
        )]);
        ?>
    </div>
    <?php
}

$myMotions  = Tools::getMyMotions($controller->site);
$mySupports = Tools::getSupportedMotions($controller->site);

if (count($myMotions) > 0) {
    ?>
    <h2 class="green">Meine Mitgliederbegehren</h2>
    <div class="content">
        <?php
        echo $this->render('_motion_list', ['motions' => $myMotions]);
        ?>
    </div>
    <?php
}

if (count($mySupports) > 0) {
    ?>
    <h2 class="green">Meine unterstützten Mitgliederbegehren</h2>
    <div class="content">
        <?php
        echo $this->render('_motion_list', ['motions' => $mySupports]);
        ?>
    </div>
    <?php
}
