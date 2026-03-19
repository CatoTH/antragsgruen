<?php

use app\components\UrlHelper;
use app\models\layoutHooks\Layout;
use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\policies\UserGroups;
use app\models\quorumType\IQuorumType;
use app\models\settings\Privileges;
use app\models\votings\AnswerTemplates;
use app\models\db\{Consultation, IMotion, User, VotingBlock};
use yii\helpers\Html;

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));

?>

<script type="module">
    import { createApp } from '/npm/vue.esm-browser.prod.js';

    const quorumCounter = <?= json_encode(Yii::t('voting', 'quorum_counter')) ?>;

    const CONSTANTS = {
        // Keep in sync with VotingBlock.php

        VOTING_STATUS_ACCEPTED: <?= IMotion::STATUS_ACCEPTED ?>,
        VOTING_STATUS_REJECTED: <?= IMotion::STATUS_REJECTED ?>,
        VOTING_STATUS_QUORUM_MISSED: <?= IMotion::STATUS_QUORUM_MISSED ?>,
        VOTING_STATUS_QUORUM_REACHED: <?= IMotion::STATUS_QUORUM_REACHED ?>,

        POLICY_USER_GROUPS: <?= UserGroups::POLICY_USER_GROUPS ?>,

        // The voting is not performed using Antragsgrün
        STATUS_OFFLINE: <?= VotingBlock::STATUS_OFFLINE ?>,

        // Votings that have been created and will be using Antragsgrün, but are not active yet
        STATUS_PREPARING: <?= VotingBlock::STATUS_PREPARING ?>,

        // Currently open for voting.
        STATUS_OPEN: <?= VotingBlock::STATUS_OPEN ?>,

        // Voting is closed, results are visible for users.
        STATUS_CLOSED_PUBLISHED: <?= VotingBlock::STATUS_CLOSED_PUBLISHED ?>,

        // Voting is closed, results are not visible for users.
        STATUS_CLOSED_UNPUBLISHED: <?= VotingBlock::STATUS_CLOSED_UNPUBLISHED ?>,

        QUORUM_TYPE_NONE: <?= IQuorumType::QUORUM_TYPE_NONE ?>,

        VOTES_PUBLIC_NO: <?= VotingBlock::VOTES_PUBLIC_NO ?>,
        VOTES_PUBLIC_ADMIN: <?= VotingBlock::VOTES_PUBLIC_ADMIN ?>,
        VOTES_PUBLIC_ALL: <?= VotingBlock::VOTES_PUBLIC_ALL ?>,

        RESULTS_PUBLIC_YES: <?= VotingBlock::RESULTS_PUBLIC_YES ?>,
        RESULTS_PUBLIC_NO: <?= VotingBlock::RESULTS_PUBLIC_NO ?>,

        ANSWER_TEMPLATE_YES_NO_ABSTENTION: <?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>,
        ANSWER_TEMPLATE_YES_NO: <?= AnswerTemplates::TEMPLATE_YES_NO ?>,
        ANSWER_TEMPLATE_YES: <?= AnswerTemplates::TEMPLATE_YES ?>,
        ANSWER_TEMPLATE_PRESENT: <?= AnswerTemplates::TEMPLATE_PRESENT ?>,

        ACTIVITY_TYPE_OPENED: <?= VotingBlock::ACTIVITY_TYPE_OPENED ?>,
        ACTIVITY_TYPE_CLOSED: <?= VotingBlock::ACTIVITY_TYPE_CLOSED ?>,
        ACTIVITY_TYPE_RESET: <?= VotingBlock::ACTIVITY_TYPE_RESET ?>,
        ACTIVITY_TYPE_REOPENED: <?= VotingBlock::ACTIVITY_TYPE_REOPENED ?>,

        VOTE_POLICY_USERGROUPS: <?= IPolicy::POLICY_USER_GROUPS ?>,

        MAJORITY_TYPES: <?= json_encode(array_map(function ($className) {
            return [
                'id' => $className::getID(),
                'name' => $className::getName(),
                'description' => $className::getDescription(),
            ];
        }, IMajorityType::getMajorityTypes())); ?>,

        QUORUM_TYPES: <?= json_encode(array_map(function ($className) {
            return [
                'id' => $className::getID(),
                'name' => $className::getName(),
                'description' => $className::getDescription(),
            ];
        }, IQuorumType::getQuorumTypes())); ?>
    }

    import { getVotingCommonMixins } from "/js/modules/shared/VotingCommonMixins.js";
    import translateDirective from "/js/modules/shared/Translate.vue.js";
    import votingBlockWidget from "/js/build/VotingBlockWidget.js";
    const commonsMixins = getVotingCommonMixins(CONSTANTS, quorumCounter);

    translateDirective.registerTranslation("voting", <?= json_encode(
      include(__DIR__ . '/../../messages/en/voting.php')
    ) ?>);

    const element = document.querySelector('.currentVotingWidget'),
        vueEl = element.querySelector(".currentVoting"),
        votingInitJson = element.getAttribute('data-voting'),
        pollUrl = element.getAttribute('data-url-poll'),
        voteUrl = element.getAttribute('data-url-vote'),
        showAdminLink = element.getAttribute('data-show-admin-link');

    const widget = createApp({
        template: `
                <div class="currentVotings">
                <voting-block-widget v-for="voting in votings" :voting="voting" @vote="vote" @abstain="abstain" :showAdminLink="showAdminLink"></voting-block-widget>
                </div>`,
        data() {
            return {
                votings: JSON.parse(votingInitJson),
                pollingId: null,
                showAdminLink,
                onReloadedCbs: []
            };
        },
        methods: {
            _votePost: function (votingBlockId, postData) {
                const widget = this;
                $.ajax({
                    url: voteUrl.replace(/VOTINGBLOCKID/, votingBlockId),
                    type: "POST",
                    data: JSON.stringify(postData),
                    processData: false,
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    headers: {"X-CSRF-Token": document.querySelector('head meta[name=csrf-token]').getAttribute('content')},
                    success: data => {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data;
                        widget.onReloadedCbs.forEach(cb => {
                            cb(widget.votings);
                        });
                    }
                });
            },
            vote: function (votingBlockId, itemGroupSameVote, itemType, itemId, vote, votePublic) {
                this._votePost(votingBlockId, {
                    votes: [{
                        itemGroupSameVote,
                        itemType,
                        itemId,
                        vote,
                        "public": votePublic
                    }]
                });
            },
            abstain: function (votingBlockId, setAbstention, votePublic) {
                this._votePost(votingBlockId, {
                    abstention: {
                        abstain: setAbstention,
                        "public": votePublic,
                    }
                });
            },
            addReloadedCb: function (cb) {
                this.onReloadedCbs.push(cb);
            },
            reloadData: function () {
                if (pollUrl === null) {
                    return;
                }
                const widget = this;
                $.get(pollUrl, function (data) {
                    widget.votings = data;
                    widget.onReloadedCbs.forEach(cb => {
                        cb(widget.votings);
                    });
                }).catch(function (err) {
                    console.error("Could not load voting data from backend", err);
                });
            },
            startPolling: function () {
                const widget = this;
                this.pollingId = window.setInterval(function () {
                    widget.reloadData();
                }, 3000);
            }
        },
        beforeUnmount() {
            window.clearInterval(this.pollingId)
        },
        created() {
            this.startPolling()
        }
    });

    widget.directive('t', translateDirective);
    widget.mixin(commonsMixins);

    <?= $this->render('@app/views/voting/_voting_vote_list.vue.php'); ?>

    widget.component('voting-block-widget', votingBlockWidget);

    import HelloWorld from '/js/build/helloworld.js';
    widget.component('hello-world', HelloWorld);

    widget.config.compilerOptions.whitespace = 'condense';
    const widgetComponent = widget.mount(vueEl);

    const noneIndicator = document.querySelectorAll('.votingsNoneIndicator')
    widgetComponent.addReloadedCb(data => {
        if (data.length === 0) {
            noneIndicator.forEach(node => node.classList.remove('hidden'));
        } else {
            noneIndicator.forEach(node => node.classList.add('hidden'));
        }
    });
</script>
