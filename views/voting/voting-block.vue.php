<?php


use app\components\UrlHelper;
use app\models\db\{Consultation, User};
use yii\helpers\Html;

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, User::PRIVILEGE_VOTINGS));

ob_start();
?>

<section class="voting" aria-label="Current Voting">
    <h2 class="green">{{ voting.title }}</h2>
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
                <li v-for="(item, index) in voting.items">
                    <div class="titleLink">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window" aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a><br>
                        <span class="amendmentBy"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>

                    <div class="votingOptions" v-if="item.can_vote">
                        <button type="button" class="btn btn-default btn-sm" @click="voteYes(item)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_yes') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm" @click="voteNo(item)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_no') ?>
                        </button>
                        <button type="button" class="btn btn-default btn-sm" @click="voteAbstention(item)">
                            <?= Yii::t('voting', 'vote_abstention') ?>
                        </button>
                    </div>
                    <div class="voted" v-if="item.voted">
                        <span class="yes" v-if="item.voted === 'yes'">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_yes') ?>
                        </span>
                        <span class="yes" v-if="item.voted === 'no'">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            <?= Yii::t('voting', 'vote_no') ?>
                        </span>
                        <span class="yes" v-if="item.voted === 'abstention'">
                            <?= Yii::t('voting', 'vote_abstention') ?>
                        </span>

                        <button type="button" class="btn btn-link btn-sm" @click="voteUndo(item)"
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
                <div class="showAll">
                    <a href=""><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> <?= Yii::t('voting', 'voting_show_all') ?></a>
                </div>
            </footer>
            <div class="votingExplanation">
                <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                <strong><?= Yii::t('voting', 'voting_visibility') ?></strong>
                <?= Yii::t('voting', 'voting_visibility_admin') ?>
            </div>
        </template>
        <template v-if="mode === 'result'">
            <ul class="votingResultList">
                <li v-for="(item, index) in voting.items">
                    <div class="titleLink">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html" title="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"><span
                                class="glyphicon glyphicon-new-window" aria-label="<?= Html::encode(Yii::t('voting', 'voting_show_amend')) ?>"></span></a><br>
                        <span class="amendmentBy"><?= Yii::t('voting', 'voting_by') ?> {{ item.initiators_html }}</span>
                    </div>
                    <div class="votesDetailed">
                        <div class="yes">Yes: 15.3 <small>(10 NYO, 8 INGYO)</small></div>
                        <div class="no">No: 8.5 <small>(6 NYO, 4 INGYO)</small></div>
                        <div class="abstention">Abstention: 2.2 <small>(2 NYO, 1 INGYO)</small></div>
                    </div>
                    <div class="result">
                        <div class="accepted">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Accepted
                        </div>
                    </div>
                </li>
            </ul>
            <footer class="votingFooter">
                <div class="votingDetails">
                    <strong>Full Members present:</strong> 25 NYO, 16 INGYO<br>
                    <strong>Quorum:</strong> 20 for NYO, 12 for INGYO
                </div>
                <div class="showAll">
                    <a href=""><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> <?= Yii::t('voting', 'voting_show_all') ?></a>
                </div>
            </footer>
        </template>
    </div>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('voting-block-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {
            return {
                mode: 'vote'
            }
        },
        computed: {},
        methods: {
            voteYes: function (item) {
                this.$emit('vote', this.voting.id, item.type, item.id, 'yes');
            },
            voteNo: function (item) {
                this.$emit('vote', this.voting.id, item.type, item.id, 'no');
            },
            voteAbstention: function (item) {
                this.$emit('vote', this.voting.id, item.type, item.id, 'abstention');
            },
            voteUndo: function(item) {
                this.$emit('vote', this.voting.id, item.type, item.id, 'undo');
            }
        }
    });
</script>
