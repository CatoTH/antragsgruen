<?php
$button = $this->render('@app/views/shared/_fullscreen_toggle.php', [
    'init_page' => 'motion-',
    'init_content_url' => '',
    'consultation' => null,
]);
?>

<style>
    .currentlyDiscussedMotion {
        margin-top: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    .currentlyDiscussedMotion .title {
        font-size: 20px;
    }
    .currentlyDiscussedMotion .proposer {
        font-size: 12px;
    }
    .currentSpeechInline .btnFullscreen {
        float: right;
        margin-top: -8px;
        margin-right: -12px;
    }
    .currentVotingWidget .btnFullscreen {
        float: right;
        margin-top: -8px;
        margin-right: -12px;
    }
</style>

<section class="currentSpeechInline currentSpeechPageWidth">
    <h2 class="green">
        Currently Debated
        <?= $button ?>
    </h2>
    <div class="content">
        <div class="currentlyDiscussedMotion">
            <div class="title">Budget proposal ABC</div>
            <div class="proposer">Submitted by: XYZ</div>
        </div>
        <div class="currentSpeechList" data-v-app="">
            <article v-335defdd="" class="speechUser">
                <div v-335defdd="" class="activeSpeaker"><span v-335defdd="" class="glyphicon glyphicon-comment leftIcon" aria-hidden="true"></span>
                    <!--v-if--><span v-335defdd="" class="notExisting">Nobody currently speaking</span></div><!--v-if--><!--v-if--><!--v-if-->
                <section v-335defdd="" class="waiting waitingMultiple" aria-label="Wartelisten für Redebeiträge">
                    <header v-335defdd=""><span v-335defdd="" class="glyphicon glyphicon-time leftIcon" aria-hidden="true"></span>Speaking Lists</header>
                    <div v-335defdd="" class="waitingSubqueues">
                        <div v-335defdd="" class="subqueue">
                            <div v-335defdd="" class="name">In Favor:</div>
                            <div v-335defdd="" class="applied"><!-- Regular waiting lists --><!--v-if-->
                                <button v-335defdd="" class="btn btn-default btn-xs" type="button">Apply</button><!--v-if--><span v-335defdd=""
                                                                                                                                     class="number"
                                                                                                                                     title="Personen auf der Warteliste"><span
                                        v-335defdd="" class="glyphicon glyphicon-time" aria-label="Personen auf der Warteliste"></span> 0</span><!--v-if-->
                                <!--v-if--><!--v-if--><!-- Point of Order --><!--v-if--><!--v-if--><!--v-if--></div>
                        </div>
                        <div v-335defdd="" class="subqueue">
                            <div v-335defdd="" class="name">Opposed:</div>
                            <div v-335defdd="" class="applied"><!-- Regular waiting lists --><!--v-if-->
                                <button v-335defdd="" class="btn btn-default btn-xs" type="button">Apply</button><!--v-if--><span v-335defdd=""
                                                                                                                                     class="number"
                                                                                                                                     title="Personen auf der Warteliste"><span
                                        v-335defdd="" class="glyphicon glyphicon-time" aria-label="Personen auf der Warteliste"></span> 0</span><!--v-if-->
                                <!--v-if--><!--v-if--><!-- Point of Order --><!--v-if--><!--v-if--><!--v-if--></div>
                        </div>
                    </div><!--v-if--></section>
            </article>
        </div>
    </div>
    <div class="content" style="border-top: solid 1px #ccc;">
        <div style="display: flex; width: 100%;">
            <div style="text-align: left; flex-basis: 66%;">
                <strong>Raised Secondary Motion:</strong><br>
                <button class="btn btn-default btn-xs" type="button">View (+ Second)</button> Point of Order <small>(By X)</small>
            </div>
            <div style="text-align: right; flex-basis: 34%;">
                <button class="btn btn-xs btn-default">
                    Raise Secondary Motion
                </button>
            </div>
        </div>
    </div>
</section>

<section class="currentVotingWidget votingCommon">
    <h2 class="green">
        Currently Debated
        <?= $button ?>
    </h2>
    <div class="content">
        <div class="currentlyDiscussedMotion">
            <div class="title">Budget proposal ABC</div>
            <div class="proposer">Submitted by: XYZ</div>
        </div>
        <!--v-if-->
        <ul class="votingListUser votingListCommon">
            <li class="voting_question_1 answer_template_0 noDetailedResults">
                <div class="titleLink"><!--v-if-->
                    <div>Are you in favor of it? <!--v-if--><br><!--v-if--></div><!--v-if--><!--v-if--><!--v-if--></div>
                <div class="votingOptions">
                    <button type="button" class="btn btn-sm btnYes btn-default"><span class="glyphicon glyphicon-ok"
                                                                                                    aria-hidden="true"></span><!--v-if--> Yes
                    </button>
                    <button type="button" class="btn btn-sm btnNo btn-default"><!--v-if--><span class="glyphicon glyphicon-minus"
                                                                                                              aria-hidden="true"></span> No
                    </button>
                    <button type="button" class="btn btn-sm btnAbstention btn-default"><!--v-if--><!--v-if--> Abstention</button>
                </div><!--v-if--><!--v-if--><!--v-if--></li><!--v-if--><!--v-if--></ul>
        <footer class="votingFooter">
            <div class="votedCounter"><strong>Status</strong>&nbsp; <span>No vote has been cast yet.</span><!--v-if-->
                <!--v-if--><!--v-if--><!--v-if--><span>&nbsp;</span><!--v-if--><!--v-if--><!--v-if--></div><!--v-if--><!--v-if--></footer>
        <div class="votingExplanation">
            <div><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span><strong>Who can see how I
                    voted?</strong></div><!--v-if--><!--v-if-->
            <div class="publicHint"><strong>All logged in users</strong> can see who voted how.</div>
        </div>
    </div>
    <div class="content" style="text-align: right; border-top: solid 1px #ccc;">
        <button class="btn btn-xs btn-default">
            Raise Secondary Motion
        </button>
    </div>
</section>



<section class="currentVotingWidget votingCommon">
    <h2 class="green">
        Currently Debated [Administration]
        <?= $button ?>
        <button class="btn btn-link" style="float: right; margin-top: -8px; margin-right: -12px;">
            <span class="glyphicon glyphicon-wrench" style="color: green; float: right;"></span>
        </button>
    </h2>
    <section class="motionListExportRow toolbarBelowTitle">
        <div style="width: 100%; display: flex; flex-direction: row;">
            <div style="flex: 25%; text-align: center; font-weight: bold;">
                Debated Motion
            </div>
            <div style="flex: 25%; text-align: center; color: green;">
                Speaking List
            </div>
            <div style="flex: 25%; text-align: center; color: green;">
                Ongoing Voting
            </div>
            <div style="flex: 25%; text-align: center; color: green;">
                Protocol
            </div>
        </div>
    </section>
    <section class="content">
        <div class="currentlyDiscussedMotion">
            <div class="title">Budget proposal ABC</div>
            <div class="proposer">Submitted by: XYZ</div>
        </div>

        <div style="font-weight: bold;">Start Debate over:</div>
        <br>
        <div style="display: flex; flex-direction: row; margin-bottom: 10px;">
            <div style="width: 150px;">Motion:</div>
            <div style="width: 360px;"><select class="stdDropdown">
                    <option value="">A2: Budget proposal ABC</option>
                </select>
            </div>
            <div>
                <button class="btn btn-default">Select</button>
            </div>
        </div>
        <div style="display: flex; flex-direction: row; margin-bottom: 10px;">
            <div style="width: 150px;">Agenda Item:</div>
            <div style="width: 360px;"><select class="stdDropdown">
                    <option value="">Reading and Approval of Minutes</option>
                </select>
            </div>
            <div>
                <button class="btn btn-default">Select</button>
            </div>
        </div>
        <div style="display: flex; flex-direction: row;">
            <div style="width: 150px;">Pending Secondary Motions:</div>
            <div style="width: 360px;">
                <label style="font-weight: normal; display: block;">
                    <input type="radio">
                    Point of Order <small>(By member X, seconded by Y)</small>
                </label>
                <label style="font-weight: normal; display: block;">
                    <input type="radio">
                    Postpone to a certain time <small>(By member X, seconded by Z)</small>
                </label>
                <label style="font-weight: normal; display: block;">
                    <input type="radio">
                    Motion to Recess <small>(By member X, seconded by Y)</small>
                </label>
            </div>
            <div>
                <button class="btn btn-default">Select</button>
            </div>
        </div>
    </section>
</section>
