<?php

use app\models\db\Amendment;
use app\models\mergeAmendments\Init;
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
            v-bind:class="[active ? 'toggleActive' : 'btn-default']"
            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="caret" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('base', 'toggle_dropdown') ?></span>
    </button>
    <button type="button" class="btn btn-sm toggleAmendment"
            v-bind:class="[active ? 'toggleActive' : 'btn-default', 'toggleAmendment' + amendment.id]"
    >
        <input v-bind:name="nameBase + '[' + amendment.id + ']'" v-bind:value="active ? '1' : '0'"
               type="hidden" class="amendmentActive" v-bind:data-amendment-id="amendment.id">
        {{ amendment.titlePrefix }}
        {{ amendment.bookmarkName }}
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <li>
            <a v-bind:href="amendment.url" class="amendmentLink" target="_blank">
                <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>
                <?= Yii::t('amend', 'merge_amend_show') ?>
            </a>
        </li>
        <li v-if="amendment.hasProposal" class="divider"></li>
        <li v-if="amendment.hasProposal" class="versionorig">
            <a href="#" class="setVersion" data-version="<?= Init::TEXT_VERSION_ORIGINAL ?>">
                <?= Yii::t('amend', 'merge_amtable_text_orig') ?>
            </a>
        </li>
        <li v-if="amendment.hasProposal" class="versionprop">
            <a href="#" class="setVersion" data-version="<?= Init::TEXT_VERSION_PROPOSAL ?>">
                <?= Yii::t('amend', 'merge_amtable_text_prop') ?>
            </a>
        </li>
        <li class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_status_set')) ?>:"></li>
        <li v-for="(statusName, statusId) in statuses" v-bind:class="'status' + statusId">
            <a href="" class="setStatus" v-bind:data-status="statusId">{{ statusName }}</a>
        </li>
        <li class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_voting_set')) ?>:"></li>
        <li>
            <div class="votingResult">
                <label v-bind:for="'votesComment' + idadd"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
                <input class="form-control votesComment" type="text" id="'votesComment' + idadd" value="">
            </div>
        </li>
        <li>
            <div class="votingData">
                <div>
                    <label v-bind:for="'votesYes' + idadd"><?= Yii::t('amend', 'merge_amend_votes_yes') ?></label>
                    <input class="form-control votesYes" type="number" v-bind:id="'votesYes' + idadd" value="">
                </div>
                <div>
                    <label v-bind:for="'votesNo' + idadd"><?= Yii::t('amend', 'merge_amend_votes_no') ?></label>
                    <input class="form-control votesNo" type="number" v-bind:id="'votesNo' + idadd" value="">
                </div>
                <div>
                    <label v-bind:for="'votesAbstention' + idadd"><?= Yii::t('amend', 'merge_amend_votes_abstention') ?></label>
                    <input class="form-control votesAbstention" type="number" v-bind:id="'votesAbstention' + idadd" value="">
                </div>
                <div>
                    <label v-bind:for="'votesInvalid' + idadd"><?= Yii::t('amend', 'merge_amend_votes_invalid') ?></label>
                    <input class="form-control votesInvalid" type="number" v-bind:id="'votesInvalid' + idadd" value="">
                </div>
            </div>
        </li>
    </ul>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('paragraph-amendment-settings', {
        template: <?= json_encode($html) ?>,
        props: ['nameBase', 'amendment', 'active', 'idadd'],
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
            increment() {
                this.count++;
            },

            decrement() {
                this.count--;
            }
        },

    });
</script>
