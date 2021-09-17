<?php

use app\components\UrlHelper;
use app\models\db\{IMotion, VotingBlock};
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
        <div class="majorityType" v-if="isPreparing">
            <strong><?= Yii::t('voting', 'majority_simple') ?></strong><br>
            <small><?= Yii::t('voting', 'majority_simple_h') ?></small>
        </div>
        <div class="alert alert-success" v-if="isOpen">
            <p><?= Yii::t('voting', 'admin_status_opened') ?></p>
        </div>
        <div class="alert alert-info" v-if="isClosed">
            <p><?= Yii::t('voting', 'admin_status_closed') ?></p>
        </div>
        <form method="POST" class="votingDataActions" v-if="isPreparing">
            <div class="data form-inline" v-if="organizations.length === 1">
                <label>
                    <?= Yii::t('voting', 'admin_members_present') ?>:
                    <input type="number" class="form-control" v-model="organizations[0].members_present">
                </label>
            </div>
            <div class="data" v-if="organizations.length > 1">
                <label v-for="orga in organizations">
                    <?= Yii::t('voting', 'admin_members_present') ?> ({{ orga.title }}):<br>
                    <input type="number" class="form-control" v-model="orga.members_present">
                </label>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary btnOpen" @click="openVoting()"><?= Yii::t('voting', 'admin_btn_open') ?></button>
            </div>
        </form>
        <form method="POST" class="votingDataActions" v-if="isOpen || isClosed">
            <div class="data" v-if="organizations.length > 0 && !(organizations.length === 1 && organizations[0].members_present === null)">
                <div class="votingDetails" v-if="organizations.length === 1">
                    <strong><?= Yii::t('voting', 'admin_members_present') ?>:</strong>
                    {{ organizations[0].members_present }}
                </div>
                <div class="votingDetails" v-if="organizations.length > 1">
                    <strong><?= Yii::t('voting', 'admin_members_present') ?>:</strong>
                    <ul>
                        <li v-for="orga in organizationsWithUsersEntered">{{ orga.members_present }} {{ orga.title }}</li>
                    </ul>
                </div>
            </div>
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
        <ul class="votingAdminList" v-if="groupedVotings.length > 0">
            <li v-for="groupedVoting in groupedVotings" :class="['voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id]">
                <div class="titleLink">
                    <div v-for="item in groupedVoting">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window" aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a>
                        <a :href="itemAdminUrl(item)" title="<?= Html::encode(Yii::t('voting', 'voting_edit_amend')) ?>"
                           :class="'adminUrl' + item.id"><span class="glyphicon glyphicon-wrench" aria-label="<?= Html::encode(Yii::t('voting', 'voting_edit_amend')) ?>"></span></a>
                        <br>
                        <span class="amendmentBy"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>
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
                                <th><?= Yii::t('voting', 'vote_yes') ?></th>
                                <th><?= Yii::t('voting', 'vote_no') ?></th>
                                <th><?= Yii::t('voting', 'vote_abstention') ?></th>
                                <th><?= Yii::t('voting', 'admin_votes_total') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="voteCountYes">{{ groupedVoting[0].vote_results[0].yes }}</td>
                                <td class="voteCountNo">{{ groupedVoting[0].vote_results[0].no }}</td>
                                <td class="voteCountAbstention">{{ groupedVoting[0].vote_results[0].abstention }}</td>
                                <td class="voteCountTotal total">{{ groupedVoting[0].vote_results[0].yes + groupedVoting[0].vote_results[0].no + groupedVoting[0].vote_results[0].abstention }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    }
                    ?>
                </div>
                <div class="result" v-if="isClosed">
                    <div class="accepted" v-if="itemIsAccepted(groupedVoting)">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_accepted') ?>
                    </div>
                    <div class="rejected" v-if="itemIsRejected(groupedVoting)">
                        <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                        <?= Yii::t('voting', 'status_rejected') ?>
                    </div>
                </div>
            </li>
        </ul>
        <div v-if="isPreparing" class="addingMotionForm">
            <button class="btn btn-link btn-xs" type="button" v-if="!addingMotions" @click="addingMotions = true">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('voting', 'admin_add_amendments') ?>
            </button>
            <div v-if="addingMotions" class="addingMotions">
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
                <button type="button" :disabled="!addableMotionSelected" class="btn btn-default" @click="addItem()">
                    <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('voting', 'admin_add_btn') ?></span>
                </button>
            </div>
        </div>
        <footer class="votingFooter" v-if="voting.log.length > 2" aria-label="<?= Yii::t('voting', 'activity_title') ?>">
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
        <footer class="votingFooter" v-if="voting.log.length > 0 && voting.log.length < 2" aria-label="<?= Yii::t('voting', 'activity_title') ?>">
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
    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = <?= VotingBlock::STATUS_OFFLINE ?>;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = <?= VotingBlock::STATUS_PREPARING ?>;

    // Currently open for voting. Currently there should only be one voting in this status at a time.
    const STATUS_OPEN = <?= VotingBlock::STATUS_OPEN ?>;

    // Vorting is closed.
    const STATUS_CLOSED = <?= VotingBlock::STATUS_CLOSED ?>;

    const ACTIVITY_TYPE_OPENED = <?= VotingBlock::ACTIVITY_TYPE_OPENED ?>;
    const ACTIVITY_TYPE_CLOSED = <?= VotingBlock::ACTIVITY_TYPE_CLOSED ?>;
    const ACTIVITY_TYPE_RESET = <?= VotingBlock::ACTIVITY_TYPE_RESET ?>;
    const ACTIVITY_TYPE_REOPENED = <?= VotingBlock::ACTIVITY_TYPE_REOPENED ?>;

    const VOTING_STATUS_ACCEPTED = <?= IMotion::STATUS_ACCEPTED ?>;
    const VOTING_STATUS_REJECTED = <?= IMotion::STATUS_REJECTED ?>;

    const resetConfirmation = <?= json_encode(Yii::t('voting', 'admin_btn_reset_bb')) ?>;
    const deleteConfirmation = <?= json_encode(Yii::t('voting', 'settings_delete_bb')) ?>;

    const motionEditUrl = <?= json_encode(UrlHelper::createUrl(['/admin/motion/update', 'motionId' => '00000000'])) ?>;
    const amendmentEditUrl = <?= json_encode(UrlHelper::createUrl(['/admin/amendment/update', 'amendmentId' => '00000000'])) ?>;

    Vue.directive('tooltip', function (el, binding) {
        $(el).tooltip({
            title: binding.value,
            placement: 'top',
            trigger: 'hover'
        })
    });

    Vue.component('voting-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting', 'addableMotions', 'alreadyAddedItems'],
        data() {
            return {
                activityClosed: true,
                addingMotions: false,
                addableMotionSelected: '',
                settingsOpened: false,
                changedSettings: {
                    // null = uninitialized
                    title: null,
                    assignedMotion: null
                }
            }
        },
        computed: {
            groupedVotings: function () {
                const knownGroupIds = {};
                const allGroups = [];
                this.voting.items.forEach(function(item) {
                    if (item.item_group_same_vote) {
                        if (knownGroupIds[item.item_group_same_vote] !== undefined) {
                            allGroups[knownGroupIds[item.item_group_same_vote]].push(item);
                        } else {
                            knownGroupIds[item.item_group_same_vote] = allGroups.length;
                            allGroups.push([item]);
                        }
                    } else {
                        allGroups.push([item]);
                    }
                });
                return allGroups;
            },
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
            isPreparing: function () {
                return this.voting.status === STATUS_PREPARING;
            },
            isOpen: function () {
                return this.voting.status === STATUS_OPEN;
            },
            isClosed: function () {
                return this.voting.status === STATUS_CLOSED;
            },
            organizationsWithUsersEntered: function () {
                return this.organizations.filter(function (organization) {
                    return organization.members_present !== null;
                });
            },
            settingsTitle: {
                get: function () {
                    return (this.changedSettings.title !== null ? this.changedSettings.title : this.voting.title);
                },
                set: function (value) {
                    this.changedSettings.title = value;
                }
            },
            settingsAssignedMotion: {
                get: function () {
                    const origAssigned = (this.voting.assignedMotion !== null ? this.voting.assignedMotion : '');
                    return (this.changedSettings.assignedMotion !== null ? this.changedSettings.assignedMotion : origAssigned);
                },
                set: function (value) {
                    this.changedSettings.assignedMotion = value;
                }
            },
            motionsAssignable: function () {
                return this.addableMotions.filter(motion => motion.type === 'motion'); // All, save for statute amendments
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
            removeItem: function (groupedVoting) {
                this.$emit('remove-item', this.voting.id, groupedVoting[0].type, groupedVoting[0].id);
            },
            addItem: function () {
                this.$emit('add-item', this.voting.id, this.addableMotionSelected);
                this.addableMotionSelected = '';
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
            openVoting: function () {
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
                this.$emit('set-status', this.voting.id, this.voting.status, this.organizations);
            },
            updateOrganizations: function () {
                if (this.organizations === undefined) {
                    this.organizations = Object.assign([], this.voting.user_organizations);
                }
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
                return '???';
            },
            itemIsAccepted: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_ACCEPTED;
            },
            itemIsRejected: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_REJECTED;
            },
            openSettings: function () {
                this.settingsOpened = true;
            },
            closeSettings: function () {
                this.settingsOpened = false;
            },
            saveSettings: function ($event) {
                $event.preventDefault();
                $event.stopPropagation();
                this.$emit('save-settings', this.voting.id, this.settingsTitle, this.settingsAssignedMotion);
                this.settingsOpened = false;
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
            this.updateOrganizations();
        },
        beforeMount: function () {
            this.updateOrganizations();
        }
    });
</script>
