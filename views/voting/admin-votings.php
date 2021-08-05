<?php

use app\components\UrlHelper;
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
$layout->addBreadcrumb(Yii::t('voting', 'admin_bc'));
$this->title = Yii::t('voting', 'admin_title');

$layout->loadVue();
$layout->addVueTemplate('@app/views/voting/admin-votings.vue.php');

$proposalFactory = new Factory($consultation, false);
$apiData = [];
foreach ($proposalFactory->getAllVotingBlocks() as $votingBlock) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $apiData[] = $votingBlock->getAdminApiObject();
}

$voteSettingsUrl = UrlHelper::createUrl(['/voting/post-vote-settings', 'votingBlockId' => 'VOTINGBLOCKID']);

?>
<h1><?= Yii::t('voting', 'admin_title') ?></h1>
<div class="content">
    ...
</div>
<div class="manageVotings votingCommon"
     data-url-vote-settings="<?= Html::encode($voteSettingsUrl) ?>"
     data-antragsgruen-widget="backend/VotingAdmin"
     data-voting="<?= Html::encode(json_encode($apiData)) ?>">
    <div class="votingAdmin"></div>
</div>
<!--
<div class="content votingAdderForm">
    <button class="btn btn-link btnAddOpener" type="button">
        <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
        Create a new voting
    </button>
</div>
-->
