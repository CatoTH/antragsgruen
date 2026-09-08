<?php

use app\components\{LiveDataChannels, Tools, UrlHelper, VotingAdminWidgetData};
use app\models\api\debate\DebateState;
use app\models\db\User;
use app\models\settings\Privileges;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addJsTranslation('debate');
$layout->addJsTranslation('speech');
$layout->addLiveDataChannel(LiveDataChannels::ROLE_ADMIN, LiveDataChannels::CHANNEL_SPEECH);
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_SPEECH);
// The debated item is the same data for moderators as for everyone else, so the admin widget follows
// the regular user channel to notice when another moderator changes it.
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_DEBATE);
$layout->provideJwt = true;

// Administering the voting of the debated item is a privilege of its own: moderating a debate says
// which voting belongs to the item, not that its result may be decided. Where it is held, the voting
// tab hosts the same widget the voting administration page does - fed by the same admin channel.
$canAdministerVotings = User::havePrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null);
if ($canAdministerVotings) {
    $layout->addLiveDataChannel(LiveDataChannels::ROLE_ADMIN, LiveDataChannels::CHANNEL_VOTING);
    $layout->addJsTranslation('voting');
    $layout->loadSelectize();

    $votingConstants = VotingAdminWidgetData::getConstants();
    $votingAddableMotions = VotingAdminWidgetData::getAddableMotions($consultation);
    $votingUserGroups = VotingAdminWidgetData::getUserGroups($consultation);
    $voteSettingsUrl = VotingAdminWidgetData::getVoteSettingsUrl();
    $voteDownloadUrl = VotingAdminWidgetData::getVoteDownloadUrl();
} else {
    $votingConstants = [];
    $votingAddableMotions = [];
    $votingUserGroups = [];
    $voteSettingsUrl = '';
    $voteDownloadUrl = '';
}

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$debateUrl = UrlHelper::createUrl(['/rest/debate/index']);
$selectableUrl = UrlHelper::createUrl(['/rest/debate/selectable']);
$speechQueueUrl = UrlHelper::createUrl(['/rest/debate/speech-queue']);
$votingUrl = UrlHelper::createUrl(['/rest/debate/voting']);

// Endpoints of the embedded speech-admin widget (JWT REST API, shared with the standalone speech admin page).
// The "QUEUEID" placeholder is substituted with the concrete queue id inside the Vue widget.
$speechComponentAdminLink = UrlHelper::createUrl('admin/index/appearance') . '#hasSpeechLists';
$speechSetStatusUrl      = UrlHelper::createUrl(['/rest/speech/post-queue-settings', 'queueId' => 'QUEUEID']);
$speechItemPerformOpUrl  = UrlHelper::createUrl(['/rest/speech/post-item-operation', 'queueId' => 'QUEUEID', 'itemId' => 'ITEMID', 'op' => 'OPERATION']);
$speechCreateItemUrl     = UrlHelper::createUrl(['/rest/speech/admin-create-item', 'queueId' => 'QUEUEID']);
$speechResetQueueUrl     = UrlHelper::createUrl(['/rest/speech/admin-queue-reset', 'queueId' => 'QUEUEID']);
$speechRandomizeQueueUrl = UrlHelper::createUrl(['/rest/speech/admin-queue-randomize', 'queueId' => 'QUEUEID']);

?>
<section class="currentDebateAdmin currentSpeechPageWidth" aria-labelledby="currentDebateAdminTitle"
         data-init-state="<?= Html::encode($initState) ?>"
         data-debate-url="<?= Html::encode($debateUrl) ?>"
         data-selectable-url="<?= Html::encode($selectableUrl) ?>"
         data-speech-queue-url="<?= Html::encode($speechQueueUrl) ?>"
         data-voting-url="<?= Html::encode($votingUrl) ?>"
         data-speech-component-admin-link="<?= Html::encode($speechComponentAdminLink) ?>"
         data-speech-item-perform-operation-url="<?= Html::encode($speechItemPerformOpUrl) ?>"
         data-speech-randomize-queue-url="<?= Html::encode($speechRandomizeQueueUrl) ?>"
         data-speech-reset-queue-url="<?= Html::encode($speechResetQueueUrl) ?>"
         data-speech-create-item-url="<?= Html::encode($speechCreateItemUrl) ?>"
         data-speech-set-status-url="<?= Html::encode($speechSetStatusUrl) ?>"
         data-voting-constants="<?= Html::encode(json_encode($votingConstants)) ?>"
         data-voting-addable-motions="<?= Html::encode(json_encode($votingAddableMotions)) ?>"
         data-voting-user-groups="<?= Html::encode(json_encode($votingUserGroups)) ?>"
         data-vote-settings-url="<?= Html::encode($voteSettingsUrl) ?>"
         data-vote-download-url="<?= Html::encode($voteDownloadUrl) ?>"
>
    <h2 class="green" id="currentDebateAdminTitle">
        <?= Yii::t('debate', 'admin_title') ?>
        <?= $this->render('@app/views/shared/_fullscreen_toggle.php', ['init_page' => 'debate', 'init_content_url' => null]) ?>
    </h2>
    <div class="currentDebateAdminWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import tooltipDirective from "/js/vue/Tooltip.vue.js";
    import debateAdminWidget from "/js/vue/debate/DebateAdminWidget.js";
    import AdminWidgetComponent from "/js/vue/speech/AdminWidget.js";
    import AdminSubqueueComponent from "/js/vue/speech/AdminSubqueue.js";
    // The voting tab hosts the same widget the voting administration page does
    import votingAdminWidget from "/js/vue/voting/VotingAdmin.js";
    import voteList from "/js/vue/voting/VotingList.js";
    import policySelect from "/js/vue/PolicySelect.js";
    import selectize from "/js/vue/Selectize.js";
    import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";

    const $element = $('.currentDebateAdmin');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('debate-admin-widget'), {
                initState: this.initState,
                debateUrl: this.debateUrl,
                selectableUrl: this.selectableUrl,
                speechQueueUrl: this.speechQueueUrl,
                votingUrl: this.votingUrl,
                csrf: this.csrf,
                speechComponentAdminLink: this.speechComponentAdminLink,
                speechItemPerformOperationUrl: this.speechItemPerformOperationUrl,
                speechRandomizeQueueUrl: this.speechRandomizeQueueUrl,
                speechResetQueueUrl: this.speechResetQueueUrl,
                speechCreateItemUrl: this.speechCreateItemUrl,
                speechSetStatusUrl: this.speechSetStatusUrl,
                votingAddableMotions: this.votingAddableMotions,
                votingUserGroups: this.votingUserGroups,
                voteSettingsUrl: this.voteSettingsUrl,
                voteDownloadUrl: this.voteDownloadUrl,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                debateUrl: $element.data('debate-url'),
                selectableUrl: $element.data('selectable-url'),
                speechQueueUrl: $element.data('speech-queue-url'),
                votingUrl: $element.data('voting-url'),
                csrf: document.querySelector("meta[name='csrf-token']").getAttribute("content"),
                speechComponentAdminLink: $element.data('speech-component-admin-link'),
                speechItemPerformOperationUrl: $element.data('speech-item-perform-operation-url'),
                speechRandomizeQueueUrl: $element.data('speech-randomize-queue-url'),
                speechResetQueueUrl: $element.data('speech-reset-queue-url'),
                speechCreateItemUrl: $element.data('speech-create-item-url'),
                speechSetStatusUrl: $element.data('speech-set-status-url'),
                votingAddableMotions: $element.data('voting-addable-motions'),
                votingUserGroups: $element.data('voting-user-groups'),
                voteSettingsUrl: $element.data('vote-settings-url'),
                voteDownloadUrl: $element.data('vote-download-url'),
            };
        }
    });

    // The voting components read the constants and the payload helpers off this mixin. Applied per
    // component rather than globally: it shares method names with the speech widgets in this app
    // (startPolling, recalcRemainingTime, …), as _index_debate.php notes for the same reason.
    const VOTING_MIXINS = getVotingCommonMixins($element.data('voting-constants') || {});

    widget.component('speech-admin-subqueue', AdminSubqueueComponent);
    widget.component('speech-admin-widget', AdminWidgetComponent);
    widget.component('debate-admin-widget', debateAdminWidget);
    widget.component('voting-admin-widget', { ...votingAdminWidget, mixins: [VOTING_MIXINS] });
    widget.component('vote-list', { ...voteList, mixins: [VOTING_MIXINS] });
    widget.component('policy-select', policySelect);
    widget.component('v-selectize', selectize);
    widget.directive('t', translateDirective);
    widget.directive('tooltip', tooltipDirective);

    widget.mount('.currentDebateAdmin .currentDebateAdminWidget');
</script>
