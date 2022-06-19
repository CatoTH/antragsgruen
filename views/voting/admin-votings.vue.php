<?php

use app\components\UrlHelper;
use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;
use app\models\db\VotingBlock;
use app\models\layoutHooks\Layout;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;

ob_start();
?>

<section class="voting" :class="['voting' + voting.id]" :id="'voting' + voting.id"
         :aria-label="'<?= Yii::t('voting', 'admin_aria_single') ?>: ' + voting.title">
    <h2 class="green">
        {{ voting.title }}
        <span class="btn-group btn-group-xs settingsToggleGroup">
            <button class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false"
                    title="<?= Yii::t('voting', 'admin_settings_open') ?>" @click="openSettings()" v-if="!settingsOpened">
                <span class="sr-only"><?= Yii::t('voting', 'admin_settings_open') ?></span>
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </button>
            <button class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false" v-if="settingsOpened"
                    title="<?= Yii::t('voting', 'admin_settings_close') ?>" @click="closeSettings()">
                <span class="sr-only"><?= Yii::t('voting', 'admin_settings_close') ?></span>
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </button>
        </span>

        <label class="activateHeader">
            <input type="checkbox" v-model="isUsed">
            <?= Yii::t('voting', 'admin_voting_use') ?>
            <span class="glyphicon glyphicon-info-sign"
                  aria-label="<?= Html::encode(Yii::t('voting', 'admin_voting_use_h')) ?>"
                  v-tooltip="'<?= addslashes(Yii::t('voting', 'admin_voting_use_h')) ?>'"></span>
        </label>
    </h2>
    <div class="content votingShow" v-if="!settingsOpened">

        <div class="votingSettingsSummary">
            <div class="majorityType" v-for="majorityType in MAJORITY_TYPES"
                 v-if="majorityType.id === voting.majority_type && votingHasMajority">
                <strong><?= Yii::t('voting', 'settings_majoritytype') ?>:</strong>
                {{ majorityType.name }}
                <span class="glyphicon glyphicon-info-sign" :aria-label="majorityType.description" v-tooltip="majorityType.description"></span>
            </div>
            <div class="quorumType" v-for="quorumType in QUORUM_TYPES" v-if="quorumType.id === voting.quorum_type && votingHasQuorum">
                <strong><?= Yii::t('voting', 'settings_quorumtype') ?>:</strong>
                {{ quorumType.name }}
                ({{ quorumIndicator }})
                <span class="glyphicon glyphicon-info-sign" :aria-label="quorumType.description" v-tooltip="quorumType.description" v-if="quorumType.description !== ''"></span>
            </div>
            <div class="votingPolicy">
                <strong><?= Yii::t('voting', 'settings_votepolicy') ?>:</strong>
                {{ voting.vote_policy.description }}
            </div>
            <div class="votingVisibility">
                <strong><?= Yii::t('voting', 'settings_votespublic') ?>:</strong>
                <span v-if="voting.votes_public === 0"><?= Yii::t('voting', 'settings_votespublic_nobody') ?></span>
                <span v-if="voting.votes_public === 1"><?= Yii::t('voting', 'settings_votespublic_admins') ?></span>
                <span v-if="voting.votes_public === 2"><?= Yii::t('voting', 'settings_votespublic_all') ?></span>
            </div>
        </div>
        <div class="alert alert-success" v-if="isOpen">
            <p><?= Yii::t('voting', 'admin_status_opened') ?></p>
        </div>
        <div class="alert alert-info" v-if="isClosed">
            <p><?= Yii::t('voting', 'admin_status_closed') ?></p>
        </div>
        <form method="POST" class="votingDataActions" v-if="isPreparing" @submit="openVoting($event)">
            <div v-if="voting.admin_setup_hint_html" class="votingAdminHint" v-html="voting.admin_setup_hint_html"></div>
            <div class="actions">
                <button type="button" class="btn btn-primary btnOpen" @click="openVoting()"><?= Yii::t('voting', 'admin_btn_open') ?></button>
            </div>
        </form>
        <form method="POST" class="votingDataActions" v-if="isOpen || isClosed">
            <div v-if="voting.admin_setup_hint_html" class="votingAdminHint" v-html="voting.admin_setup_hint_html"></div>
            <div class="actions" v-if="isOpen">
                <button type="button" class="btn btn-default btnReset" @click="resetVoting()"><?= Yii::t('voting', 'admin_btn_reset') ?></button>
                <button type="button" class="btn btn-primary btnClose" @click="closeVoting()"><?= Yii::t('voting', 'admin_btn_close') ?></button>
            </div>
            <div class="actions" v-if="isClosed">
                <button type="button" class="btn btn-default btnReset" @click="resetVoting()"><?= Yii::t('voting', 'admin_btn_reset') ?></button>
                <button type="button" class="btn btn-default btnReopen" @click="reopenVoting()"><?= Yii::t('voting', 'admin_btn_reopen') ?></button>
            </div>
        </form>
        <div v-if="groupedVotings.length === 0" class="noVotingsYet">
            <div class="alert alert-info"><p>
                    <?= Yii::t('voting', 'admin_no_items_yet') ?>
            </p></div>
        </div>
        <ul class="votingListAdmin votingListCommon" v-if="groupedVotings.length > 0">
            <template v-for="groupedVoting in groupedVotings">
            <li :class="[
                'voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id,
                'answer_template_' + answerTemplate,
                (isClosed ? 'showResults' : ''),
                (isClosed ? 'showDetailedResults' : ''),
                (isVoteListShown(groupedVoting) ? 'voteListShown' : '')
            ]">
                <div class="titleLink" :class="{'question': voting.answers.length === 1}">
                    <div v-if="groupedVoting[0].item_group_name" class="titleGroupName">
                        {{ groupedVoting[0].item_group_name }}
                    </div>
                    <div v-for="item in groupedVoting">
                        {{ item.title_with_prefix }}
                        <a v-if="item.url_html" :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window" aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a>
                        <a v-if="itemAdminUrl(item)" :href="itemAdminUrl(item)" title="<?= Html::encode(Yii::t('voting', 'voting_edit_amend')) ?>"
                           :class="'adminUrl' + item.id"><span class="glyphicon glyphicon-wrench" aria-label="<?= Html::encode(Yii::t('voting', 'voting_edit_amend')) ?>"></span></a>
                        <br>
                        <span class="amendmentBy" v-if="item.initiators_html"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>
                    <div v-if="votingHasQuorum" class="quorumCounter">
                        {{ quorumCounter(groupedVoting) }}
                    </div>
                    <button v-if="hasVoteList(groupedVoting) && !isVoteListShown(groupedVoting)" @click="showVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                        <span class="glyphicon glyphicon-chevron-down" aria-label="true"></span>
                        <?= Yii::t('voting', 'voting_show_votes') ?>
                    </button>
                    <button v-if="hasVoteList(groupedVoting) && isVoteListShown(groupedVoting)" @click="hideVoteList(groupedVoting)" class="btn btn-link btn-xs btnShowVotes">
                        <span class="glyphicon glyphicon-chevron-up" aria-label="true"></span>
                        <?= Yii::t('voting', 'voting_hide_votes') ?>
                    </button>
                </div>
                <div class="prepActions" v-if="isPreparing">
                    <button class="btn btn-link btn-xs removeBtn" type="button" @click="removeItem(groupedVoting)"
                            title="<?= Yii::t('voting', 'admin_btn_remove_item') ?>" aria-label="<?= Yii::t('voting', 'admin_btn_remove_item') ?>">
                        <span class="glyphicon glyphicon-minus-sign" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="votesDetailed" v-if="isOpen || isClosed">
                    <?php
                    $alternativeResults = Layout::getVotingAlternativeAdminResults($consultation);
                    if ($alternativeResults) {
                        echo $alternativeResults;
                    } else {
                    ?>
                    <div v-if="groupedVoting[0].vote_results.length === 1 && groupedVoting[0].vote_results[0]">
                        <table class="votingTable votingTableSingle">
                            <thead>
                            <tr>
                                <th v-for="answer in voting.answers">{{ answer.title }}</th>
                                <th v-if="voting.answers.length > 1"><?= Yii::t('voting', 'admin_votes_total') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td v-for="answer in voting.answers" :class="'voteCount_' + answer.api_id">
                                    {{ groupedVoting[0].vote_results[0][answer.api_id] }}
                                </td>
                                <td class="voteCountTotal total" v-if="voting.answers.length > 1">
                                    {{ groupedVoting[0].vote_results[0].yes + groupedVoting[0].vote_results[0].no + groupedVoting[0].vote_results[0].abstention }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    }
                    ?>
                </div>
                <div class="result" v-if="isClosed && (votingHasMajority || votingHasQuorum)">
                    <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_accepted') ?>
                    </div>
                    <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_rejected') ?>
                    </div>
                    <div class="accepted" v-if="itemIsQuorumReached(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_quorum_reached') ?>
                    </div>
                    <div class="rejected" v-if="itemIsQuorumFailed(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_quorum_missed') ?>
                    </div>
                </div>
            </li>
            <li class="voteResults" v-if="isVoteListShown(groupedVoting)">
                <voting-vote-list :voting="voting" :groupedVoting="groupedVoting" :showNotVotedList="true"
                                  :setToUserGroupSelection="userGroups" @set-user-group="setVotersToUserGroup"></voting-vote-list>
            </li>
            </template>
        </ul>
        <div v-if="isPreparing" class="addingItemsForm">
            <button class="btn btn-link btn-xs addIMotions" type="button" v-if="!addingMotions && !addingQuestions" @click="addingMotions = true">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('voting', 'admin_add_amendments') ?>
            </button>
            <button class="btn btn-link btn-xs addQuestions" type="button" v-if="!addingMotions && !addingQuestions" @click="openQuestionAdder()">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('voting', 'admin_add_question') ?>
            </button>
            <button class="btn btn-link btn-xs btnRemove" type="button" v-if="addingMotions || addingQuestions" @click="addingQuestions = false; addingMotions = false"
                aria-label="<?= Yii::t('voting', 'admin_add_abort') ?>" title="<?= Yii::t('voting', 'admin_add_abort') ?>">
                <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
            </button>
            <form v-if="addingMotions" class="addingMotions" @submit="addIMotion($event)">
                <select class="stdDropdown" v-model="addableMotionSelected"
                    aria-label="<?= Yii::t('voting', 'admin_add_amendments') ?>" title="<?= Yii::t('voting', 'admin_add_amendments') ?>">
                    <option value=""><?= Yii::t('voting', 'admin_add_amendments_opt') ?></option>
                    <template v-for="item in addableMotions">
                        <!-- Statute amendment -->
                        <option v-if="item.type === 'amendment'" :value="'amendment-' + item.id" :disabled="!isAddable(item)">{{ item.title }}</option>

                        <option v-if="item.type === 'motion' && item.amendments.length === 0" :value="'motion-' + item.id" :disabled="!isAddable(item)">{{ item.title }}</option>

                        <optgroup v-if="item.type === 'motion' && item.amendments.length > 0" :label="item.title">
                            <option :value="'motion-' + item.id" :disabled="!isAddable(item)"><?= Yii::t('voting', 'admin_add_opt_motion') ?></option>
                            <option :value="'motion-' + item.id + '-amendments'" v-if="item.amendments.length > 1"><?= Yii::t('voting', 'admin_add_opt_all_amend') ?></option>
                            <option v-for="amendment in item.amendments" :value="'amendment-' + amendment.id" :disabled="!isAddable(amendment)">{{ amendment.title }}</option>
                        </optgroup>
                    </template>
                </select>
                <button type="submit" :disabled="!addableMotionSelected" class="btn btn-default">
                    <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('voting', 'admin_add_btn') ?></span>
                </button>
            </form>
            <form v-if="addingQuestions" class="addingQuestions" @submit="addQuestion($event)">
                <label>
                    <?= Yii::t('voting', 'admin_add_question_title') ?>:
                    <input type="text" class="form-control" v-model="addingQuestionText" :id="'voting_question_' + voting.id">
                </label>
                <button type="submit" :disabled="!addingQuestionText" class="btn btn-default">
                    <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('voting', 'admin_add_btn') ?></span>
                </button>
            </form>
        </div>
        <footer class="votingFooter" v-if="voting.log.length > 2" aria-label="<?= Yii::t('voting', 'activity_title') ?>">
            <div class="downloadResults" v-if="isClosed">
                <a class="btn btn-xs btn-link" :href="resultDownloadLink">
                    <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                    <?= Yii::t('voting', 'results_download') ?>
                </a>
            </div>
            <div class="activityOpener" v-if="activityClosed">
                <button type="button" class="btn btn-link btn-xs" @click="openActivities()">
                    <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    <?= Yii::t('voting', 'activity_show_all') ?>
                </button>
            </div>
            <div class="activityCloser" v-if="!activityClosed">
                <button type="button" class="btn btn-link btn-xs" @click="closeActivities()">
                    <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                    <?= Yii::t('voting', 'activity_show_all') ?>
                </button>
            </div>
            <ol class="activityLog" :class="{ closed: activityClosed }">
                <li v-for="logEntry in voting.log" v-html="formatLogEntry(logEntry)"></li>
            </ol>
        </footer>
        <footer class="votingFooter" v-if="voting.log.length > 0 && voting.log.length <= 2" aria-label="<?= Yii::t('voting', 'activity_title') ?>">
            <div class="downloadResults" v-if="isClosed">
                <a class="btn btn-xs btn-link" :href="resultDownloadLink">
                    <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                    <?= Yii::t('voting', 'results_download') ?>
                </a>
            </div>
            <ol class="activityLog">
                <li v-for="logEntry in voting.log" v-html="formatLogEntry(logEntry)"></li>
            </ol>
        </footer>
    </div>
    <form method="POST" class="content votingSettings" v-if="settingsOpened" @submit="saveSettings($event)">
        <label class="titleSetting">
            <?= Yii::t('voting', 'settings_title') ?>:<br>
            <input type="text" v-model="settingsTitle" class="form-control">
        </label>
        <fieldset class="answerTemplate">
            <legend><?= Yii::t('voting', 'settings_answers') ?>:</legend>
            <label>
                <input type="radio" :value="ANSWER_TEMPLATE_YES_NO_ABSTENTION" v-model="answerTemplate" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_answers_yesnoabst') ?>
            </label>
            <label>
                <input type="radio" :value="ANSWER_TEMPLATE_YES_NO" v-model="answerTemplate" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_answers_yesno') ?>
            </label>
            <label>
                <input type="radio" :value="ANSWER_TEMPLATE_PRESENT" v-model="answerTemplate" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_answers_present') ?>
                <span class="glyphicon glyphicon-info-sign"
                  :aria-label="'<?= Yii::t('voting', 'settings_answers_presenth') ?>'" v-tooltip="'<?= Yii::t('voting', 'settings_answers_presenth') ?>'"></span>
            </label>
        </fieldset>
        <fieldset class="votePolicy">
            <legend><?= Yii::t('voting', 'settings_votepolicy') ?>:</legend>
            <policy-select allow-anonymous="false" :policy="votePolicy" :all-groups="voting.user_groups" @change="setPolicy($event)" ref="policy-select"></policy-select>
        </fieldset>
        <fieldset class="majorityTypeSettings" v-if="selectedAnswersHaveMajority">
            <legend><?= Yii::t('voting', 'settings_majoritytype') ?></legend>
            <label v-for="majorityTypeDef in MAJORITY_TYPES">
                <input type="radio" :value="majorityTypeDef.id" v-model="majorityType" :disabled="isOpen || isClosed">
                {{ majorityTypeDef.name }}
                <span class="glyphicon glyphicon-info-sign"
                      :aria-label="majorityTypeDef.description" v-tooltip="majorityTypeDef.description"></span>
            </label>
        </fieldset>
        <fieldset class="quorumTypeSettings" v-if="votePolicy.id === VOTE_POLICY_USERGROUPS">
            <legend><?= Yii::t('voting', 'settings_quorumtype') ?></legend>
            <label v-for="quorumTypeDef in QUORUM_TYPES">
                <input type="radio" :value="quorumTypeDef.id" v-model="quorumType" :disabled="isOpen || isClosed">
                {{ quorumTypeDef.name }}
                <span class="glyphicon glyphicon-info-sign"
                      :aria-label="quorumTypeDef.description" v-tooltip="quorumTypeDef.description"></span>
            </label>
        </fieldset>
        <fieldset class="resultsPublicSettings">
            <legend><?= Yii::t('voting', 'settings_resultspublic') ?>:</legend>
            <label>
                <input type="radio" value="0" v-model="resultsPublic">
                <?= Yii::t('voting', 'settings_resultspublic_admins') ?>
            </label>
            <label>
                <input type="radio" value="1" v-model="resultsPublic">
                <?= Yii::t('voting', 'settings_resultspublic_all') ?>
            </label>
        </fieldset>
        <fieldset class="votesPublicSettings">
            <legend><?= Yii::t('voting', 'settings_votespublic') ?>:</legend>
            <label>
                <input type="radio" value="0" v-model="votesPublic" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_votespublic_nobody') ?>
            </label>
            <label>
                <input type="radio" value="1" v-model="votesPublic" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_votespublic_admins') ?>
            </label>
            <label>
                <input type="radio" value="2" v-model="votesPublic" :disabled="isOpen || isClosed">
                <?= Yii::t('voting', 'settings_votespublic_all') ?>
            </label>
            <div class="hint"><?= Yii::t('voting', 'settings_votespublic_hint') ?></div>
        </fieldset>
        <label class="assignedMotion">
            <?= Yii::t('voting', 'settings_motionassign') ?>:
            <span class="glyphicon glyphicon-info-sign"
                  aria-label="<?= Html::encode(Yii::t('voting', 'settings_motionassign_h')) ?>"
                  v-tooltip="'<?= addslashes(Yii::t('voting', 'settings_motionassign_h')) ?>'"></span>
            <br>
            <select class="stdDropdown" v-model="settingsAssignedMotion">
                <option :value="''"> - <?= Yii::t('voting', 'settings_motionassign_none') ?> - </option>
                <option v-for="motion in motionsAssignable" :value="motion.id">
                    {{ motion.title }}
                </option>
            </select>
        </label>
        <button type="submit" class="btn btn-success btnSave">
            <?= Yii::t('voting', 'settings_save') ?>
        </button>
        <button type="button" class="btn btn-link btnDelete" @click="deleteVoting()"
                title="<?= Yii::t('voting', 'settings_delete') ?>" aria-label="<?= Yii::t('voting', 'settings_delete') ?>">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </form>
</section>


<?php
$html = ob_get_clean();
?>

<script>
    const ACTIVITY_TYPE_OPENED = <?= VotingBlock::ACTIVITY_TYPE_OPENED ?>;
    const ACTIVITY_TYPE_CLOSED = <?= VotingBlock::ACTIVITY_TYPE_CLOSED ?>;
    const ACTIVITY_TYPE_RESET = <?= VotingBlock::ACTIVITY_TYPE_RESET ?>;
    const ACTIVITY_TYPE_REOPENED = <?= VotingBlock::ACTIVITY_TYPE_REOPENED ?>;

    const VOTE_POLICY_USERGROUPS = <?= IPolicy::POLICY_USER_GROUPS ?>;

    const ANSWER_TEMPLATE_YES_NO_ABSTENTION = <?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>;
    const ANSWER_TEMPLATE_YES_NO = <?= AnswerTemplates::TEMPLATE_YES_NO ?>;
    const ANSWER_TEMPLATE_PRESENT = <?= AnswerTemplates::TEMPLATE_PRESENT ?>;

    const MAJORITY_TYPES = <?= json_encode(array_map(function ($className) {
        return [
            'id' => $className::getID(),
            'name' => $className::getName(),
            'description' => $className::getDescription(),
        ];
    }, IMajorityType::getMajorityTypes())); ?>;

    const QUORUM_TYPES = <?= json_encode(array_map(function ($className) {
        return [
            'id' => $className::getID(),
            'name' => $className::getName(),
            'description' => $className::getDescription(),
        ];
    }, IQuorumType::getQuorumTypes())); ?>;

    const quorumIndicator = <?= json_encode(Yii::t('voting', 'quorum_limit')) ?>;
    const quorumCounter = <?= json_encode(Yii::t('voting', 'quorum_counter')) ?>;
    const resetConfirmation = <?= json_encode(Yii::t('voting', 'admin_btn_reset_bb')) ?>;
    const deleteConfirmation = <?= json_encode(Yii::t('voting', 'settings_delete_bb')) ?>;

    const motionEditUrl = <?= json_encode(UrlHelper::createUrl(['/admin/motion/update', 'motionId' => '00000000'])) ?>;
    const amendmentEditUrl = <?= json_encode(UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => '00000000'])) ?>;

    Vue.component('v-select', VueSelect.VueSelect);
    Vue.directive('tooltip', function (el, binding) {
        $(el).tooltip({
            title: binding.value,
            placement: 'top',
            trigger: 'hover'
        })
    });

    Vue.component('voting-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'addableMotions', 'alreadyAddedItems', 'userGroups', 'voteDownloadUrl'],
        mixins: [VOTING_COMMON_MIXIN],
        data() {
            return {
                activityClosed: true,
                addingMotions: false,
                addableMotionSelected: '',
                addingQuestions: false,
                addingQuestionText: '',
                settingsOpened: false,
                changedSettings: {
                    // Caching the changed values here prevents unsaved changes to settings from being reset by AJAX polling
                    // null = uninitialized
                    title: null,
                    answerTemplate: null,
                    assignedMotion: null,
                    majorityType: null,
                    quorumType: null,
                    votesPublic: null,
                    resultsPublic: null,
                    votePolicy: null
                },
                shownVoteLists: []
            }
        },
        computed: {
            isUsed: {
                get() {
                    return this.voting.status !== STATUS_OFFLINE;
                },
                set(val) {
                    if (val && this.voting.status === STATUS_OFFLINE) {
                        this.voting.status = STATUS_PREPARING;
                        this.statusChanged();
                    }
                    if (!val) {
                        this.voting.status = STATUS_OFFLINE;
                        this.statusChanged();
                    }
                }
            },
            selectedAnswersHaveMajority: function () {
                // Used by the settings form
                return this.answerTemplate === ANSWER_TEMPLATE_YES_NO_ABSTENTION || this.answerTemplate === ANSWER_TEMPLATE_YES_NO;
            },
            settingsTitle: {
                get: function () {
                    return (this.changedSettings.title !== null ? this.changedSettings.title : this.voting.title);
                },
                set: function (value) {
                    this.changedSettings.title = value;
                }
            },
            majorityType: {
                get: function () {
                    return (this.changedSettings.majorityType !== null ? this.changedSettings.majorityType : this.voting.majority_type);
                },
                set: function (value) {
                    this.changedSettings.majorityType = value;
                }
            },
            quorumType: {
                get: function () {
                    return (this.changedSettings.quorumType !== null ? this.changedSettings.quorumType : this.voting.quorum_type);
                },
                set: function (value) {
                    this.changedSettings.quorumType = value;
                }
            },
            answerTemplate: {
                get: function () {
                    return (this.changedSettings.answerTemplate !== null ? this.changedSettings.answerTemplate : this.voting.answers_template);
                },
                set: function (value) {
                    this.changedSettings.answerTemplate = value;
                }
            },
            votePolicy: {
                get: function () {
                    return (this.changedSettings.votePolicy !== null ? this.changedSettings.votePolicy : this.voting.vote_policy);
                },
                set: function (value) {
                    this.changedSettings.votePolicy = value;
                }
            },
            votesPublic: {
                get: function () {
                    return (this.changedSettings.votesPublic !== null ? this.changedSettings.votesPublic : this.voting.votes_public);
                },
                set: function (value) {
                    this.changedSettings.votesPublic = value;
                }
            },
            resultsPublic: {
                get: function () {
                    return (this.changedSettings.resultsPublic !== null ? this.changedSettings.resultsPublic : this.voting.results_public);
                },
                set: function (value) {
                    this.changedSettings.resultsPublic = value;
                }
            },
            settingsAssignedMotion: {
                get: function () {
                    const origAssigned = (this.voting.assigned_motion !== null ? this.voting.assigned_motion : '');
                    return (this.changedSettings.assignedMotion !== null ? this.changedSettings.assignedMotion : origAssigned);
                },
                set: function (value) {
                    this.changedSettings.assignedMotion = value;
                }
            },
            motionsAssignable: function () {
                return this.addableMotions.filter(motion => motion.type === 'motion'); // All, save for statute amendments
            },
            resultDownloadLink: function () {
                return this.voteDownloadUrl.replace(/VOTINGBLOCKID/, this.voting.id).replace(/FORMAT/, 'ods');
            },
            quorumIndicator: function () {
                return quorumIndicator.replace(/%QUORUM%/, this.voting.quorum).replace(/%ALL%/, this.voting.quorum_eligible);
            }
        },
        methods: {
            formatLogEntry: function (logEntry) {
                let description = '?';
                switch (logEntry['type']) {
                    case ACTIVITY_TYPE_OPENED:
                        description = <?= json_encode(Yii::t('voting', 'activity_opened')) ?>;
                        break;
                    case ACTIVITY_TYPE_RESET:
                        description = <?= json_encode(Yii::t('voting', 'activity_reset')) ?>;
                        break;
                    case ACTIVITY_TYPE_CLOSED:
                        description = <?= json_encode(Yii::t('voting', 'activity_closed')) ?>;
                        break;
                    case ACTIVITY_TYPE_REOPENED:
                        description = <?= json_encode(Yii::t('voting', 'activity_reopened')) ?>;
                        break;
                }
                let date = new Date(logEntry['date']);
                return date.toLocaleString() + ': ' + description;
            },
            quorumCounter: function (groupedVoting) {
                return quorumCounter.replace(/%QUORUM%/, this.voting.quorum).replace(/%CURRENT%/, groupedVoting[0].quorum_votes);
            },
            removeItem: function (groupedVoting) {
                this.$emit('remove-item', this.voting.id, groupedVoting[0].type, groupedVoting[0].id);
            },
            addIMotion: function ($event) {
                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
                this.$emit('add-imotion', this.voting.id, this.addableMotionSelected);
                this.addableMotionSelected = '';
            },
            openQuestionAdder: function() {
                this.addingQuestions = true;
                window.setTimeout(() => {
                    document.getElementById('voting_question_' + this.voting.id).focus();
                }, 1);
            },
            addQuestion: function ($event) {
                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
                this.$emit('add-question', this.voting.id, this.addingQuestionText);
                this.addingQuestionText = '';
                this.addingQuestions = false;
            },
            isAddable: function (item) {
                if (item.type === 'motion') {
                    return this.alreadyAddedItems.motions.indexOf(item.id) === -1;
                }
                if (item.type === 'amendment') {
                    return this.alreadyAddedItems.amendments.indexOf(item.id) === -1;
                }
                return false;
            },
            openVoting: function ($event) {
                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
                this.voting.status = STATUS_OPEN;
                this.statusChanged();
            },
            closeVoting: function () {
                this.voting.status = STATUS_CLOSED;
                this.statusChanged();
            },
            resetVoting: function () {
                const widget = this;
                bootbox.confirm(resetConfirmation, function(result) {
                    if (result) {
                        widget.voting.status = STATUS_PREPARING;
                        widget.statusChanged();
                    }
                });
            },
            reopenVoting: function () {
                this.voting.status = STATUS_OPEN;
                this.statusChanged();
            },
            statusChanged: function () {
                this.$emit('set-status', this.voting.id, this.voting.status);
            },
            openActivities: function () {
                this.activityClosed = false;
            },
            closeActivities: function () {
                this.activityClosed = true;
            },
            itemAdminUrl: function (item) {
                if (item.type === 'motion') {
                    return motionEditUrl.replace(/00000000/, item.id);
                }
                if (item.type === 'amendment') {
                    return amendmentEditUrl.replace(/00000000/, item.id);
                }
                return null;
            },
            hasVoteList: function (groupedItem) {
                return groupedItem[0].votes !== undefined && (this.isOpen || this.isClosed);
            },
            isVoteListShown: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                return this.shownVoteLists.indexOf(showId) !== -1;
            },
            showVoteList: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists.push(showId);
            },
            hideVoteList: function (groupedItem) {
                const hideId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists = this.shownVoteLists.filter(id => id !== hideId);
            },
            setVotersToUserGroup: function (userIds, newUserGroup) {
                this.$emit('set-voters-to-user-group', this.voting.id, userIds, newUserGroup);
            },
            openSettings: function () {
                this.settingsOpened = true;
            },
            closeSettings: function () {
                this.settingsOpened = false;
            },
            saveSettings: function ($event) {
                if ($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                }
                this.$emit('save-settings', this.voting.id, this.settingsTitle, this.answerTemplate, this.majorityType, this.quorumType, this.votePolicy, this.resultsPublic, this.votesPublic, this.settingsAssignedMotion);
                this.changedSettings.votesPublic = null;
                this.changedSettings.majorityType = null;
                this.changedSettings.quorumType = null;
                this.changedSettings.answerTemplate = null;
                this.changedSettings.votePolicy = null;
                this.settingsOpened = false;
            },
            setPolicy: function (data) {
                this.votePolicy = data;
            },
            deleteVoting: function () {
                const widget = this;
                bootbox.confirm(deleteConfirmation, function(result) {
                    if (result) {
                        widget.$emit('delete-voting', widget.voting.id);
                    }
                });
            }
        },
        updated() {
            $(this.$el).find('[data-toggle="tooltip"]').tooltip();
        },
        beforeMount: function () {}
    });
</script>
