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
            <div class="proposer">
                Submitted by: XYZ
                &nbsp;
                <a href=""><span class="glyphicon glyphicon-chevron-right"></span> Full text</a>
            </div>
        </div>
    </div>
    <div class="content" style="border-top: solid 1px #ccc;">
        <div style="display: flex; width: 100%;">
            <div style="text-align: left; flex-basis: 66%;">
                <strong>Raised Secondary Motion:</strong><br>
                <button class="btn btn-default btn-xs" type="button">View (+ Second)</button> Point of Order <small>(By X)</small>
            </div>
            <div style="text-align: right; flex-basis: 34%;">
                <br>
                <button class="btn btn-xs btn-default">
                    Raise Secondary Motion
                </button>
            </div>
        </div>
    </div>
</section>

<section class="currentSpeechInline currentSpeechPageWidth">
    <h2 class="green">
        Currently Debated
        <?= $button ?>
    </h2>
    <div class="content">
        <div class="currentlyDiscussedMotion">
            <div class="title">Welcome &amp; Presentation of the Agenda</div>
            <div class="proposer">
                Presentation by: chairperson XYZ
            </div>
        </div>
    </div>
    <div class="content" style="border-top: solid 1px #ccc;">
        <div style="display: flex; width: 100%;">
            <div style="text-align: left; flex-basis: 66%;">
                <strong>Raised Secondary Motion:</strong><br>
                <button class="btn btn-default btn-xs" type="button">View (+ Second)</button> Point of Order <small>(By X)</small>
            </div>
            <div style="text-align: right; flex-basis: 34%;">
                <br>
                <button class="btn btn-xs btn-default">
                    Raise Secondary Motion
                </button>
            </div>
        </div>
    </div>
</section>

<section class="currentSpeechInline currentSpeechPageWidth">
    <h2 class="green">
        Currently Debated
        <?= $button ?>
    </h2>
    <div class="content">
        <div class="currentlyDiscussedMotion">
            <div class="title">Budget proposal ABC</div>
            <div class="proposer">
                Submitted by: XYZ
                &nbsp;
                <a href=""><span class="glyphicon glyphicon-chevron-right"></span> Full text</a>
            </div>
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
                <br>
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
            <div class="proposer">
                Submitted by: XYZ
                &nbsp;
                <a href=""><span class="glyphicon glyphicon-chevron-right"></span> Full text</a>
            </div>
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
        <div style="display: flex; width: 100%;">
            <div style="text-align: left; flex-basis: 66%;">
                <strong>Raised Secondary Motion:</strong><br>
                <button class="btn btn-default btn-xs" type="button">View (+ Second)</button> Point of Order <small>(By X)</small>
            </div>
            <div style="text-align: right; flex-basis: 34%;">
                <br>
                <button class="btn btn-xs btn-default">
                    Raise Secondary Motion
                </button>
            </div>
        </div>
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
            <div class="proposer">
                Submitted by: XYZ
                &nbsp;
                <a href=""><span class="glyphicon glyphicon-chevron-right"></span> Full text</a>
            </div>
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
            <div style="flex: 25%; text-align: center; color: green;">
                Debated Motion
            </div>
            <div style="flex: 25%; text-align: center; font-weight: bold;">
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
    <div v-5f5e629d="" class="toolbarBelowTitle settings"><!--v-if-->
        <div v-5f5e629d="" class="settingsActive"><span v-5f5e629d="" class="inactive">List inactive</span>
            <button v-5f5e629d="" class="btn btn-xs btn-default" type="button">Activate</button><!--v-if--></div>
        <div v-5f5e629d="" class="settingsOpen"><!--v-if--><!--v-if--></div>
        <div v-5f5e629d="" class="settingsPolicy" style="text-align: right;">
            <div v-5f5e629d="" class="btn-group">
                <button v-5f5e629d="" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">Settings<span v-5f5e629d="" class="caret" aria-hidden="true"></span></button>
                <ul v-5f5e629d="" class="dropdown-menu">
                    <li v-5f5e629d="" class="checkbox"><label v-5f5e629d=""><input v-5f5e629d="" type="checkbox" class="allowCustomNames">Speakers can
                            change their names</label></li>
                    <li v-5f5e629d="" class="checkbox"><label v-5f5e629d=""><input v-5f5e629d="" type="checkbox" class="preferNonspeaker">Prefer persons
                            who haven't talked yet</label></li>
                    <li v-5f5e629d="" class="checkbox"><label v-5f5e629d=""><input v-5f5e629d="" type="checkbox" class="showNames">Show names of
                            applicants publicly</label></li>
                    <li v-5f5e629d="" class="checkbox"><label v-5f5e629d=""><input v-5f5e629d="" type="checkbox" class="hasSpeakingTime">Show speaking
                            time</label></li><!--v-if-->
                    <li v-5f5e629d="" class="link"><a v-5f5e629d="" href="/ddd/admin/appearance#hasSpeechLists"><span v-5f5e629d=""
                                                                                                                      class="icon glyphicon glyphicon-chevron-right"
                                                                                                                      aria-hidden="true"></span>Quota,
                            (de-)activation</a></li>
                    <li v-5f5e629d="" class="randomizeQueues">
                        <button v-5f5e629d="" class="btn btn-default btn-sm" type="button">Randomize waiting list</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <section class="content">
        <article v-5f5e629d="" class="speechAdmin">
        <div class="currentlyDiscussedMotion">
            <div class="title">Budget proposal ABC</div>
            <div class="proposer">
                Submitted by: XYZ
                &nbsp;
                <a href=""><span class="glyphicon glyphicon-chevron-right"></span> Full text</a>
            </div>
        </div>

                <ol v-5f5e629d="" class="slots" aria-label="Speaking list"><!--v-if-->
                    <li v-5f5e629d="" class="slotEntry slotActive inactive"><span v-5f5e629d="" class="glyphicon glyphicon-comment iconBackground"
                                                                                  aria-hidden="true"></span>
                        <div v-5f5e629d="" class="status statusActive">Speaking:</div>
                        <div v-5f5e629d="" class="nameNobody">Nobody</div>
                    </li><!--v-if-->
                    <li v-5f5e629d="" class="slotPlaceholder inactive"><span v-5f5e629d="" class="glyphicon glyphicon-time iconBackground"
                                                                             aria-hidden="true"></span>
                        <div v-5f5e629d="" class="title">Start proposal:</div>
                        <div v-5f5e629d="" class="nameNobody">No proposal</div>
                    </li>
                </ol>
                <div v-5f5e629d="" class="subqueues">
                    <section v-34983ba1="" v-5f5e629d="" class="subqueue positionLeft" aria-label="Waiting list In Favor">
                        <header v-34983ba1="">In Favor</header><!--v-if-->
                        <ul v-34983ba1="" class="subqueueItems">
                            <li v-34983ba1="" class="dropPlaceholder hoverable">
                                <div v-34983ba1="" class="dropAdditionalSpace"></div>
                                <div v-34983ba1="" class="hoveredIndicator">Move here</div>
                            </li>
                        </ul>
                        <div v-34983ba1="" class="empty">no applications</div>
                        <section v-34983ba1="" class="subqueueAdder">
                            <button v-34983ba1="" class="link adderOpener" type="button"><span v-34983ba1="" class="glyphicon glyphicon-plus"
                                                                                               aria-hidden="true"></span>Add
                            </button><!--v-if--></section>
                    </section>
                    <section v-34983ba1="" v-5f5e629d="" class="subqueue positionRight" aria-label="Waiting list Against">
                        <header v-34983ba1="">Against</header><!--v-if-->
                        <ul v-34983ba1="" class="subqueueItems">
                            <li v-34983ba1="" class="dropPlaceholder hoverable">
                                <div v-34983ba1="" class="dropAdditionalSpace"></div>
                                <div v-34983ba1="" class="hoveredIndicator">Move here</div>
                            </li>
                        </ul>
                        <div v-34983ba1="" class="empty">no applications</div>
                        <section v-34983ba1="" class="subqueueAdder">
                            <button v-34983ba1="" class="link adderOpener" type="button"><span v-34983ba1="" class="glyphicon glyphicon-plus"
                                                                                               aria-hidden="true"></span>Add
                            </button><!--v-if--></section>
                    </section>
                </div>
            <section v-5f5e629d="" class="queueResetSection">
                <button v-5f5e629d="" type="button" class="btn btn-link btn-danger"><span v-5f5e629d="" class="glyphicon glyphicon-trash"
                                                                                          aria-hidden="true"></span>Reset / empty speaking list
                </button>
            </section>


    </section>
    </article>
</section>
