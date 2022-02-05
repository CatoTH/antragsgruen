<?php

use app\components\UrlHelper;
use app\models\db\User;
use app\models\proposedProcedure\Factory;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addBreadcrumb(Yii::t('voting', 'bc'));
$this->title = Yii::t('voting', 'results_title');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/_voting_common_mixins.vue.php');
$layout->addVueTemplate('@app/views/voting/_voting_vote_list.vue.php');
$layout->addVueTemplate('@app/views/voting/voting-block.vue.php');

$apiData = [];
foreach (Factory::getClosedVotingBlocks($consultation) as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserResultsApiObject(User::getCurrentUser());
}

$pollUrl   = UrlHelper::createUrl(['/voting/get-closed-voting-blocks']);
?>
<h1><?= Yii::t('voting', 'results_title') ?></h1>

<?php
if (count($apiData) === 0) {
    echo '<div class="content resultsNone"><div class="alert alert-info">';
    echo Yii::t('voting', 'results_none');
    echo '</div></div>';
}
?>

<section data-url-poll="<?= Html::encode($pollUrl) ?>"
         data-antragsgruen-widget="frontend/VotingBlock" class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>
