<?php
ob_start();
?>

<section class="voting" aria-label="Current Voting">
    <h2 class="green">{{  voting.title }}</h2>
    <div class="content">
        <form method="POST" class="votingDataActions" v-if="mode === 'init'">
            <div class="data">
                <label>
                    Members present (NYO):<br>
                    <input type="number" class="form-control">
                </label>
                <label>
                    Members present (INGYO):<br>
                    <input type="number" class="form-control">
                </label>
                <label>
                    Quota (NYO):<br>
                    <input type="number" class="form-control">
                </label>
                <label>
                    Quota (INGYO):<br>
                    <input type="number" class="form-control">
                </label>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" @click="startVoting()">Start voting</button>
            </div>
        </form>
        <form method="POST" class="votingDataActions" v-if="mode === 'started'">
            <div class="data">
                Voting started: 2021-07-18 14:00<br>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" @click="finishVoting()">Close voting</button>
            </div>
        </form>
        <form method="POST" class="votingDataActions" v-if="mode === 'finished'">
            <div class="data">
                Voting started: 2021-07-18 14:00<br>
                Voting closed: 2021-07-18 14:15<br>
            </div>
            <div class="actions">
            </div>
        </form>
        <ul class="votingResultList">
            <li v-for="(item, index) in voting.items">
                <div class="titleLink">
                    {{ item.title_with_prefix }}
                    <a :href="item.url_html"><span class="glyphicon glyphicon-new-window" aria-label="Änderungsantrag anzeigen"></span></a><br>
                    <span class="amendmentBy">By {{ item.initiators_html }}</span>
                </div>
                <div class="votesDetailed" v-if="mode === 'started' || mode === 'finished'">
                    <div class="yes">Yes: 15.3 <small>(10 NYO, 8 INGYO)</small></div>
                    <div class="no">No: 8.5 <small>(6 NYO, 4 INGYO)</small></div>
                    <div class="abstention">Abstention: 2.2 <small>(2 NYO, 1 INGYO)</small></div>
                </div>
                <div class="result" v-if="mode === 'finished'">
                    <div class="accepted">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Accepted
                    </div>
                </div>
            </li>
        </ul>
        <footer class="votingFooter" v-if="mode === 'started' || mode === 'finished'">
            <div class="votingDetails">
                <strong>Full Members present:</strong> 25 NYO, 16 INGYO<br>
                <strong>Quorum:</strong> 20 for NYO, 12 for INGYO
            </div>
        </footer>
    </div>
</section>


<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('voting-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['voting'],
        data() {
            return {
                mode: 'init'
            }
        },
        computed: {
        },
        methods: {
            startVoting: function () {
                this.mode = 'started';
            },
            finishVoting: function () {
                this.mode = 'finished';
            },
        }
    });
</script>
