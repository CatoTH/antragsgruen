<?php

use app\components\{LiveDataChannels, UrlHelper};
use app\models\settings\Privileges;
use app\models\db\{Consultation, DebateItem, Motion, User};
use app\models\proposedProcedure\Factory;
use yii\helpers\Html;

/**
 * @var \app\controllers\Base $controller
 * @var Motion|null $assignedToMotion
 * @var bool $excludeDebatedVoting Optional. Set where the debate is shown next to this widget: the
 *                                 voting of the item being debated is presented there, so this
 *                                 widget shows every open voting except that one.
 */

$controller = $this->context;
$consultation = $controller->consultation;
$user = User::getCurrentUser();
$layout = $controller->layoutParams;
// The voting endpoints authenticate by JWT, like the rest of the REST API
$layout->provideJwt = true;
$excludeDebatedVoting = ($excludeDebatedVoting ?? false);

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

// Which voting the debate is on can change while the page is open, so the widget follows the debate
// rather than being told once which one to leave out. The ID here is only what it starts with.
$debatedVotingBlockId = null;
if ($excludeDebatedVoting) {
    $layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_DEBATE);

    $debated = DebateItem::getCurrentForConsultation($consultation);
    // The same rule the debate itself applies: an item that was deleted or hidden is debated by
    // nobody, and its voting is therefore not being shown anywhere else either
    if ($debated && $debated->isTargetVisible()) {
        $debatedVotingBlockId = $debated->votingBlockId;
    }
}

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
         data-exclude-debated="<?= ($excludeDebatedVoting ? '1' : '') ?>"
         data-debated-voting="<?= Html::encode((string)$debatedVotingBlockId) ?>"
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
