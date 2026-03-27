<?php

use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\policies\UserGroups;
use app\models\quorumType\IQuorumType;
use app\models\settings\Privileges;
use app\models\votings\AnswerTemplates;
use app\models\db\{Consultation, IMotion, User, VotingBlock};

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));

$CONSTANTS = include(__DIR__ . DIRECTORY_SEPARATOR . '_constants.php');

?>

<script type="module">
    import { createApp, h } from '/npm/vue.esm-browser.prod.js';

    const quorumCounter = <?= json_encode(Yii::t('voting', 'quorum_counter')) ?>;

    const CONSTANTS = <?= json_encode($CONSTANTS) ?>;

    import { getVotingCommonMixins } from "/js/vue/VotingCommonMixins.js";
    import translateDirective from "/js/vue/Translate.vue.js";
    import votingBlockWidget from "/js/vue/VotingBlockWidget.js";
    import voteList from "/js/vue/VotingList.js";
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

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(
                'div',
                { class: 'currentVotings' },
                this.votings.map(voting =>
                    h(votingBlockWidget, {
                        voting,
                        showAdminLink: this.showAdminLink,
                        onVote: this.vote,
                        onAbstain: this.abstain
                    })
                )
            );
        },
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
    widget.component('vote-list', voteList);

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
