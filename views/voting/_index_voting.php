<?php

use app\components\{LiveDataChannels, UrlHelper};
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
// The voting endpoints authenticate by JWT, like the rest of the REST API
$layout->provideJwt = true;

if (!User::getCurrentUser()) {
    return;
}

$votingBlocksToRender = Factory::getOpenVotingBlocks($consultation, false, $assignedToMotion);
if (count($votingBlocksToRender) === 0 && !Factory::hasOnlineVotingBlocks($consultation)) {
    // Hint: we poll once there is a online voting block created
    return;
}

$layout->addJsTranslation('voting');
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_VOTING);

$apiData = [];
foreach ($votingBlocksToRender as $votingBlockToRender) {
    $apiData[] = $votingBlockToRender->getUserApiObject(User::getCurrentUser());
}

$CONSTANTS = include(__DIR__ . DIRECTORY_SEPARATOR . '_constants.php');
$assignedToMotionId = ($assignedToMotion ? $assignedToMotion->id : '');
$voteUrl  = UrlHelper::createUrl(['/rest/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => $assignedToMotionId]);
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));
if ($iAmAdmin) {
    $adminLink = UrlHelper::createUrl(['/consultation/admin-votings']);
} else {
    $adminLink = '';
}
?>
<section data-channel="<?= Html::encode(LiveDataChannels::CHANNEL_VOTING) ?>"
         data-filter-motion="<?= Html::encode((string)$assignedToMotionId) ?>"
         data-url-vote="<?= Html::encode($voteUrl) ?>"
         data-admin-link="<?= Html::encode($adminLink) ?>"
         class="currentVotingWidget votingCommon"
         data-voting="<?= Html::encode(\app\components\Tools::getSerializer()->serialize($apiData, 'json')) ?>"
>
    <div class="currentVoting"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { VotingBlock } from "/js/modules/frontend/VotingBlock.js";
    new VotingBlock(
        document.querySelector(".currentVotingWidget"),
        <?= json_encode($CONSTANTS) ?>
    );
</script>
