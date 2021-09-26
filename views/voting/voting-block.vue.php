<?php


use app\components\UrlHelper;
use app\models\db\{Consultation, User};
use yii\helpers\Html;

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, User::PRIVILEGE_VOTINGS));

ob_start();
?>

<section class="voting" aria-label="<?= Yii::t('voting', 'voting_current_aria') ?>">
    <h2 class="green"><?= Yii::t('voting', 'title_user_single') ?>: {{ voting.title }}</h2>
    <div class="content">
        <?php
        if ($iAmAdmin) {
            $url = UrlHelper::createUrl(['consultation/admin-votings']);
            echo '<a href="' . Html::encode($url) . '" class="votingsAdminLink">';
            echo '<span class="glyphicon glyphicon-wrench" aria-hidden="true"></span> ';
            echo Yii::t('voting', 'voting_admin_all');
            echo '</a>';
        }
        ?>

        <template v-if="mode === 'vote'">
            <ul class="voteList">
                <li v-for="groupedVoting in groupedVotings" :class="['voting_' + groupedVoting[0].type + '_' + groupedVoting[0].id]">
                    <div class="titleLink">
                        <div v-for="item in groupedVoting">
                            {{ item.title_with_prefix }}
                            <a :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                    class="glyphicon glyphicon-new-window"
                                    aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a><br>
                            <span class="amendmentBy"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                        </div>
                    </div>

                    <div class="votingOptions" v-if="groupedVoting[0].can_vote">
                        <button type="button" class="btn btn-default btn-sm btnYes" @click="voteYes(groupedVoting)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_yes') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm btnNo" @click="voteNo(groupedVoting)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_no') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm btnAbstention" @click="voteAbstention(groupedVoting)">
                            <?= Yii::t('voting', 'vote_abstention') ?>
                        </button>
                    </div>
                    <div class="voted" v-if="groupedVoting[0].voted">
                        <span class="yes" v-if="groupedVoting[0].voted === 'yes'">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_yes') ?>
                        </span>
                        <span class="yes" v-if="groupedVoting[0].voted === 'no'">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_no') ?>
                        </span>
                        <span class="yes" v-if="groupedVoting[0].voted === 'abstention'">
                            <?= Yii::t('voting', 'vote_abstention') ?>
                        </span>

                        <button type="button" class="btn btn-link btn-sm btnUndo" @click="voteUndo(groupedVoting)"
                                title="<?= Yii::t('voting', 'vote_undo') ?>" aria-label="<?= Yii::t('voting', 'vote_undo') ?>">
                            <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        </button>
                    </div>
                </li>
            </ul>
            <footer class="votingFooter">
                <div class="votedCounter">
                    <strong><?= Yii::t('voting', 'voting_votes_status') ?>:</strong>
                    <span v-if="voting.votes_total === 0"><?= Yii::t('voting', 'voting_votes_0') ?></span>
                    <span v-if="voting.votes_total === 1"><?= Yii::t('voting', 'voting_votes_1_1') ?></span>
                    <span v-if="voting.votes_users === 1 && voting.votes_total > 1"><?= str_replace(['%VOTES%'], ['{{ voting.votes_total }}'],
                            Yii::t('voting', 'voting_votes_1_x')) ?></span>
                    <span v-if="voting.votes_users > 1"><?= str_replace(['%VOTES%', '%USERS%'], ['{{ voting.votes_total }}', '{{ voting.votes_users }}'],
                            Yii::t('voting', 'voting_votes_x')) ?></span>
                </div>
            </footer>
            <div class="votingExplanation">
                <div>
                    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                    <strong><?= Yii::t('voting', 'voting_visibility') ?></strong>
                </div>
                <div class="publicHint" v-if="votesPublicNo"><?= Yii::t('voting', 'voting_visibility_none') ?></div>
                <div class="publicHint" v-if="votesPublicAdmin"><?= Yii::t('voting', 'voting_visibility_admin') ?></div>
                <div class="publicHint" v-if="votesPublicAll"><?= Yii::t('voting', 'voting_visibility_all') ?></div>
            </div>
        </template>
    </div>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    // Keep in sync with VotingBlock.php
    const VOTES_PUBLIC_NO = 0;
    const VOTES_PUBLIC_ADMIN = 1;
    const VOTES_PUBLIC_ALL = 2;

    Vue.component('voting-block-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {
            return {
                mode: 'vote'
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
            votesPublicNo: function () {
                return this.voting.votesPublic === VOTES_PUBLIC_NO;
            },
            votesPublicAdmin: function () {
                return this.voting.votesPublic === VOTES_PUBLIC_ADMIN;
            },
            votesPublicAll: function () {
                return this.voting.votesPublic === VOTES_PUBLIC_ALL;
            }
        },
        methods: {
            voteYes: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'yes', this.voting.votesPublic);
            },
            voteNo: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'no', this.voting.votesPublic);
            },
            voteAbstention: function (groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'abstention', this.voting.votesPublic);
            },
            voteUndo: function(groupedVoting) {
                this.$emit('vote', this.voting.id, groupedVoting[0].item_group_same_vote, groupedVoting[0].type, groupedVoting[0].id, 'undo', this.voting.votesPublic);
            }
        }
    });
</script>
