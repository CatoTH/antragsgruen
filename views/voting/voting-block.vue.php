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
            echo '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ';
            echo 'Administrate votings';
            echo '</a>';
        }
        ?>


        <template v-if="mode === 'vote'">
            <ul class="voteList">
                <li v-for="(item, index) in voting.items">
                    <div class="titleLink">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html"><span class="glyphicon glyphicon-new-window" aria-label="Änderungsantrag anzeigen"></span></a><br>
                        <span class="amendmentBy">By {{ item.initiators_html }}</span>
                    </div>

                    <div class="votingOptions" v-if="item.can_vote">
                        <button type="button" class="btn btn-default btn-sm" @click="voteYes(item)">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            Yes
                        </button>
                        <button type="button" class="btn btn-default btn-sm" @click="voteNo(item)">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            No
                        </button>
                        <button type="button" class="btn btn-default btn-sm" @click="voteAbstention(item)">
                            Abstention
                        </button>
                    </div>
                    <div class="voted" v-if="item.voted">
                        <span class="yes" v-if="item.voted === 'yes'">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                            Yes
                        </span>
                        <span class="yes" v-if="item.voted === 'no'">
                            <span class="glyphicon glyphicon-minus" aria-hidden="true"></span>
                            No
                        </span>
                        <span class="yes" v-if="item.voted === 'abstention'">
                            Abstention
                        </span>
                    </div>
                </li>
            </ul>
            <footer class="votingFooter">
                <div class="votedCounter">
                    <strong>Status:</strong> 13 votes have been cast
                </div>
                <div class="showAll">
                    <a href=""><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Show all votings</a>
                </div>
            </footer>
            <div class="votingExplanation">
                <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> <strong>Who can see how I voted?</strong>
                The votes are visible to the administrators of this page, but not for other participants using this page.
            </div>
        </template>
        <template v-if="mode === 'result'">
            <ul class="votingResultList">
                <li v-for="(item, index) in voting.items">
                    <div class="titleLink">
                        {{ item.title_with_prefix }}
                        <a :href="item.url_html"><span class="glyphicon glyphicon-new-window" aria-label="Änderungsantrag anzeigen"></span></a><br>
                        <span class="amendmentBy">By {{ item.initiators_html }}</span>
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
                    <a href=""><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> Show all votings</a>
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
            }
        }
    });
</script>
