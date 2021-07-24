<?php
ob_start();
?>

<section class="voting" aria-label="Administrate voting">
    <h2 class="green">{{ voting.title }}</h2>
    <div class="content">
        <form method="POST" class="votingDataActions" v-if="mode === 'init'">
            <div class="data">
                <label>
                    Members present (NYO):<br>
                    <input type="number" class="form-control" value="24">
                </label>
                <label>
                    Members present (INGYO):<br>
                    <input type="number" class="form-control" value="32">
                </label>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" @click="startVoting()">Start voting</button>
            </div>
        </form>
        <form method="POST" class="votingDataActions" v-if="mode === 'started'">
            <div class="data">
                Voting started: 2021-07-18 14:00<br>
                Members present (NYO): 24<br>
                Members present (INGYO): 32<br>
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
        <ul class="votingAdminList">
            <li v-for="(item, index) in voting.items">
                <div class="titleLink">
                    <!--
                    <button class="btn btn-link btnRemove" type="button" title="Remove this amendment from this voting">
                        <span class="glyphicon glyphicon-remove-circle" aria-label="Remove this amendment from this voting"></span>
                    </button>
                    -->
                    {{ item.title_with_prefix }}
                    <a :href="item.url_html"><span class="glyphicon glyphicon-new-window" aria-label="Show amendment"></span></a><br>
                    <span class="amendmentBy">By {{ item.initiators_html }}</span>
                </div>
                <div class="votesDetailed" v-if="mode === 'started' || mode === 'finished'">
                    <table class="votingTable">
                        <thead>
                            <tr>
                                <th rowspan="2"></th>
                                <th rowspan="2">Votes cast</th>
                                <th colspan="2">Yes</th>
                                <th colspan="2">No</th>
                                <th>Abs.</th>
                                <th>Total</th>
                            </tr>
                            <tr>
                                <th>Ticks</th>
                                <th>Votes</th>
                                <th>Ticks</th>
                                <th>Votes</th>
                                <th>Ticks</th>
                                <th>Ticks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>NYC</th>
                                <td>224</td>
                                <td>4</td>
                                <td>128</td>
                                <td>3</td>
                                <td>96</td>
                                <td>2</td>
                                <td>9</td>
                            </tr>
                            <tr>
                                <th>INGYO</th>
                                <td>168</td>
                                <td>4</td>
                                <td>96</td>
                                <td>3</td>
                                <td>72</td>
                                <td>2</td>
                                <td>9</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td colspan="2">392</td>
                                <td colspan="2">224</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="result" v-if="mode === 'finished'">
                    <div class="accepted">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Accepted
                    </div>
                </div>
            </li>
        </ul>
        <footer class="votingFooter" v-if="mode === 'init'">
            <div class="votingAmendmentAdder">
                Add an amendment to this voting:
                <select name="amendment">
                    <option></option>
                    <option>Amendment 1</option>
                    <option>Amendment 2</option>
                </select>
            </div>
        </footer>
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
