<?php

use app\models\db\Amendment;
use yii\helpers\Html;

$statuses = [
    Amendment::STATUS_PROCESSED         => Yii::t('structure', 'STATUS_PROCESSED'),
    Amendment::STATUS_ACCEPTED          => Yii::t('structure', 'STATUS_ACCEPTED'),
    Amendment::STATUS_REJECTED          => Yii::t('structure', 'STATUS_REJECTED'),
    Amendment::STATUS_MODIFIED_ACCEPTED => Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
];

ob_start();
?>
<div class="btn-group amendmentStatus" v-bind:class="['amendmentStatus' + amendment.id]" v-bind:data-amendment-id="amendment.id">
    <button class="btn btn-sm dropdown-toggle dropdownAmendment"
            v-if="!amendment.isMotionModU"
            v-bind:class="[active ? 'toggleActive' : 'btn-default']"
            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="caret" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('base', 'toggle_dropdown') ?></span>
    </button>
    <button type="button" class="btn btn-sm toggleAmendment"
            v-bind:class="[active ? 'toggleActive' : 'btn-default', 'toggleAmendment' + amendment.id]"
            v-on:click="activeToggle()"
    >
        <input v-bind:name="nameBase + '[' + amendment.id + ']'" v-bind:value="active ? '1' : '0'"
               type="hidden" class="amendmentActive" v-bind:data-amendment-id="amendment.id">
        {{ amendment.titlePrefix }}
        <span v-html="amendment.bookmarkName"></span>
        <span v-if="amendment.isMotionModU">
            <?= Yii::t('amend', 'merge_amend_modu') ?>
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" v-if="!amendment.isMotionModU">
        <li>
            <a v-bind:href="amendment.url" class="amendmentLink" target="_blank">
                <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>
                <?= Yii::t('amend', 'merge_amend_show') ?>
            </a>
        </li>
        <li v-if="amendment.hasProposal" class="divider"></li>
        <li v-if="amendment.hasProposal" class="versionorig" v-bind:class="version == 'orig' ? 'selected' : ''">
            <a href="#" class="setVersion" v-on:click="setVersion($event, 'orig')">
                <?= Yii::t('amend', 'merge_amtable_text_orig') ?>
            </a>
        </li>
        <li v-if="amendment.hasProposal" class="versionprop" v-bind:class="version == 'prop' ? 'selected' : ''">
            <a href="#" class="setVersion" v-on:click="setVersion($event, 'prop')">
                <?= Yii::t('amend', 'merge_amtable_text_prop') ?>
            </a>
        </li>
        <li class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_status_set')) ?>:"></li>
        <li v-for="(statusName, statusId) in statuses" v-bind:class="['status' + statusId, status == statusId ? 'selected' : '']">
            <a href="" class="setStatus" v-bind:data-status="statusId" v-on:click="setStatus($event, statusId)">{{ statusName }}</a>
        </li>
        <li class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_voting_set')) ?>:"></li>
        <li>
            <div class="votingResult">
                <label v-bind:for="'votesComment' + idAdd"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
                <input class="form-control votesComment" type="text" v-bind:id="'votesComment' + idAdd"
                       v-model.lazy="votingData.comment" v-on:change="setVotes()">
            </div>
        </li>
        <li>
            <div class="votingData">
                <div>
                    <label v-bind:for="'votesYes' + idAdd"><?= Yii::t('amend', 'merge_amend_votes_yes') ?></label>
                    <input class="form-control votesYes" type="number" v-bind:id="'votesYes' + idAdd"
                           v-model.lazy="votingData.votesYes" v-on:change="setVotes()">
                </div>
                <div>
                    <label v-bind:for="'votesNo' + idAdd"><?= Yii::t('amend', 'merge_amend_votes_no') ?></label>
                    <input class="form-control votesNo" type="number" v-bind:id="'votesNo' + idAdd"
                           v-model.lazy="votingData.votesNo" v-on:change="setVotes()">
                </div>
                <div>
                    <label v-bind:for="'votesAbstention' + idAdd"><?= Yii::t('amend', 'merge_amend_votes_abstention') ?></label>
                    <input class="form-control votesAbstention" type="number" v-bind:id="'votesAbstention' + idAdd"
                           v-model.lazy="votingData.votesAbstention" v-on:change="setVotes()">
                </div>
                <div>
                    <label v-bind:for="'votesInvalid' + idAdd"><?= Yii::t('amend', 'merge_amend_votes_invalid') ?></label>
                    <input class="form-control votesInvalid" type="number" v-bind:id="'votesInvalid' + idAdd"
                           v-model.lazy="votingData.votesInvalid" v-on:change="setVotes()">
                </div>
            </div>
        </li>
    </ul>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('merging', 'component', 'paragraph-amendment-settings', {
        template: <?= json_encode($html) ?>,
        props: ['nameBase', 'amendment', 'active', 'idAdd', 'status', 'version', 'votingData'],
        data() {
            return {};
        },

        computed: {
            statuses: function () {
                let statuses = <?= json_encode($statuses) ?>;
                statuses[this.amendment.oldStatusId] = <?= json_encode(Yii::t('amend', 'merge_status_unchanged')) ?> +': ' + this.amendment.oldStatusName;
                return statuses;
            }
        },

        methods: {
            activeToggle() {
                this.$emit('update', ['set-active', parseInt(this.amendment.id), !this.active]);
            },
            setStatus($event, statusId) {
                $event.preventDefault();
                this.$emit('update', ['set-status', parseInt(this.amendment.id), parseInt(statusId)]);
            },
            setVersion($event, version) {
                $event.preventDefault();
                this.$emit('update', ['set-version', parseInt(this.amendment.id), version]);
            },
            setVotes() {
                const votingData = {
                    comment: this.votingData.comment,
                    votesYes: this.votingData.votesYes,
                    votesNo: this.votingData.votesNo,
                    votesAbstention: this.votingData.votesAbstention,
                    votesInvalid: this.votingData.votesInvalid,
                };
                this.$emit('update', ['set-votes', parseInt(this.amendment.id), votingData]);
            }
        },

    });
</script>
