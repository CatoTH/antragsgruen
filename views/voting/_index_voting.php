<?php

use app\components\UrlHelper;
use app\models\settings\Privileges;
use app\models\db\{Consultation, Motion, User};
use app\models\proposedProcedure\Factory;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Motion|null $assignedToMotion
 */

$controller = $this->context;
$consultation = $controller->consultation;
$user = User::getCurrentUser();
$layout = $controller->layoutParams;

if (!User::getCurrentUser()) {
    return;
}

$votingBlocksToRender = Factory::getOpenVotingBlocks($consultation, false, $assignedToMotion);
if (count($votingBlocksToRender) === 0 && !Factory::hasOnlineVotingBlocks($consultation)) {
    // Hint: we poll once there is a online voting block created
    return;
}

$apiData = [];
foreach ($votingBlocksToRender as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserVotingApiObject(User::getCurrentUser());
}

$CONSTANTS = include(__DIR__ . DIRECTORY_SEPARATOR . '_constants.php');
$assignedToMotionId = ($assignedToMotion ? $assignedToMotion->id : '');
$pollUrl  = UrlHelper::createUrl(['/voting/get-open-voting-blocks', 'assignedToMotionId' => $assignedToMotionId, 'showAllOpen' => 0]);
$voteUrl  = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => $assignedToMotionId]);
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));
if ($iAmAdmin) {
    $adminLink = UrlHelper::createUrl(['/consultation/admin-votings']);
} else {
    $adminLink = '';
}
?>
<section data-url-poll="<?= Html::encode($pollUrl) ?>"
         data-url-vote="<?= Html::encode($voteUrl) ?>"
         data-admin-link="<?= Html::encode($adminLink) ?>"
         class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(json_encode($apiData)) ?>"
>
    <div class="currentVoting"></div>
</section>

<script type="module">
    import { VotingBlock } from "/js/modules/frontend/VotingBlock.js";
    new VotingBlock(
        document.querySelector(".currentVotingWidget"),
        <?= json_encode($CONSTANTS) ?>,
        <?= json_encode(\app\components\JsTools::getTranslations(Consultation::getCurrent(), "voting") ) ?>
    );
</script>
