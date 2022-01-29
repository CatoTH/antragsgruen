import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

const POLICY_USER_GROUPS  = 6;

export class VotingAdmin {
    private widget;
    private element: HTMLElement;

    constructor($element: JQuery) {
        this.element = $element[0];
        this.createVueWidget();
        this.initVotingCreater();

        $('[data-toggle="tooltip"]').tooltip();
    }

    private createVueWidget() {
        const vueEl = this.element.querySelector(".votingAdmin");
        const voteSettingsUrl = this.element.getAttribute('data-url-vote-settings');
        const voteCreateUrl = this.element.getAttribute('data-vote-create');
        const addableMotions = JSON.parse(this.element.getAttribute('data-addable-motions'));
        const pollUrl = this.element.getAttribute('data-url-poll');
        const votingInitJson = this.element.getAttribute('data-voting');
        const initUserGroups = JSON.parse(this.element.getAttribute('data-user-groups'));

        this.widget = new Vue({
            el: vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     :addableMotions="addableMotions"
                                     :alreadyAddedItems="alreadyAddedItems"
                                     :userGroups="userGroups"
                                     @set-status="setStatus"
                                     @save-settings="saveSettings"
                                     @remove-item="removeItem"
                                     @delete-voting="deleteVoting"
                                     @add-imotion="addIMotion"
                                     @add-question="addQuestion"
                                     ref="voting-admin-widget"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    votingsJson: null,
                    votings: null,
                    userGroups: initUserGroups,
                    addableMotions,
                    csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                    pollingId: null
                };
            },
            computed: {
                alreadyAddedItems: function () {
                    const motions = [];
                    const amendments = [];
                    this.votings.forEach(voting => {
                        voting.items.forEach(item => {
                            if (item.type === 'motion') {
                                motions.push(item.id);
                            }
                            if (item.type === 'amendment') {
                                amendments.push(item.id);
                            }
                        });
                    });
                    return {motions, amendments};
                }
            },
            methods: {
                _performOperation: function (votingBlockId, additionalProps) {
                    let postData = {
                        _csrf: this.csrf,
                    };
                    if (additionalProps) {
                        postData = Object.assign(postData, additionalProps);
                    }
                    const widget = this;
                    const url = voteSettingsUrl.replace(/VOTINGBLOCKID/, votingBlockId);
                    $.post(url, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data;
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                setVotingFromJson(data) {
                    if (data === this.votingsJson) {
                        return;
                    }
                    this.votings = JSON.parse(data);
                    this.votingsJson = data;
                },
                setVotingFromObject(data) {
                    this.votings = data;
                    this.votingsJson = null;
                },
                setStatus(votingBlockId, newStatus, organizations) {
                    this._performOperation(votingBlockId, {
                        op: 'update-status',
                        status: newStatus,
                        organizations: organizations.map(orga => { return {
                            id: orga.id,
                            members_present: orga.members_present,
                        }}),
                    });
                },
                saveSettings(votingBlockId, title, answerTemplate, majorityType, votePolicy, resultsPublic, votesPublic, assignedMotion) {
                    this._performOperation(votingBlockId, {
                        op: 'save-settings',
                        title,
                        answerTemplate,
                        majorityType,
                        votePolicy,
                        resultsPublic,
                        votesPublic,
                        assignedMotion,
                    });
                },
                deleteVoting(votingBlockId) {
                    this._performOperation(votingBlockId, {
                        op: 'delete-voting',
                    });
                },
                createVoting: function (type, answers, title, specificQuestion, assignedMotion, majorityType, votePolicy, userGroups, resultsPublic, votesPublic) {
                    let postData = {
                        _csrf: this.csrf,
                        type,
                        answers,
                        title,
                        specificQuestion,
                        assignedMotion,
                        majorityType,
                        votePolicy,
                        userGroups,
                        resultsPublic,
                        votesPublic
                    };
                    const widget = this;
                    $.post(voteCreateUrl, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data['votings'];

                        window.setTimeout(() => {
                            $("#voting" + data['created_voting']).scrollintoview({top_offset: -100});
                        }, 200);
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                removeItem(votingBlockId, itemType, itemId) {
                    this._performOperation(votingBlockId, {
                        op: 'remove-item',
                        itemType,
                        itemId
                    });
                },
                addIMotion(votingBlockId, itemDefinition) {
                    this._performOperation(votingBlockId, {
                        op: 'add-imotion',
                        itemDefinition
                    });
                },
                addQuestion(votingBlockId, question) {
                    this._performOperation(votingBlockId, {
                        op: 'add-question',
                        question
                    });
                },
                reloadData: function () {
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.setVotingFromJson(data);
                    }, 'text').catch(function (err) {
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
            beforeDestroy() {
                window.clearInterval(this.pollingId)
            },
            created() {
                this.setVotingFromJson(votingInitJson);
                this.startPolling()
            }
        });

        // Used by tests to control vue-select
        window['votingAdminWidget'] = this.widget;
    }

    private initPolicyWidget() {
        const $widget = $(this.element);
        const $select: any = $widget.find('.userGroupSelect');
        $select.find("select").selectize({});

        const $policySelect = $widget.find(".policySelect");
        $policySelect.on("change", () => {
            if (parseInt($policySelect.val() as string, 10) === POLICY_USER_GROUPS) {
                $select.removeClass("hidden");
            } else {
                $select.addClass("hidden");
            }
        }).trigger("change");
    }

    private initVotingCreater() {
        const opener = this.element.querySelector('.createVotingOpener'),
            form = this.element.querySelector('.createVotingHolder'),
            specificQuestion = this.element.querySelector('.specificQuestion'),
            majorityType = this.element.querySelector('.majorityTypeSettings');
        opener.addEventListener('click', () => {
            form.classList.remove('hidden');
            opener.classList.add('hidden');
        });

        const getRadioListValue = (selector: string, defaultValue: string) => {
            let val = defaultValue;
            form.querySelectorAll(selector).forEach(el => {
                const input = el as HTMLInputElement;
                if (input.checked) {
                    val = input.value;
                }
            });
            return val;
        };

        const recalcQuestionListener = () => {
            if (getRadioListValue('.votingType input', 'question') === 'question') {
                specificQuestion.classList.remove('hidden');
            } else {
                specificQuestion.classList.add('hidden');
            }
        };
        form.querySelectorAll('.votingType input').forEach(el => {
            el.addEventListener('change', recalcQuestionListener);
        });
        recalcQuestionListener();

        const recalcAnswerTypeListener = () => {
            if (getRadioListValue('.answerTemplate input', '0') === '2') {
                majorityType.classList.add('hidden');
            } else {
                majorityType.classList.remove('hidden');

            }
        };
        form.querySelectorAll('.answerTemplate input').forEach(el => {
            el.addEventListener('change', recalcAnswerTypeListener);
        });
        recalcAnswerTypeListener();

        this.initPolicyWidget();

        form.querySelector('form').addEventListener('submit', (ev) => {
            ev.stopPropagation();
            ev.preventDefault();
            const type = getRadioListValue('.votingType input', 'question');
            const answers = parseInt(getRadioListValue('.answerTemplate input', '0'), 10); // Default
            const title = form.querySelector('.settingsTitle') as HTMLInputElement;
            const specificQuestion = form.querySelector('.settingsQuestion') as HTMLInputElement;
            const assigned = form.querySelector('.settingsAssignedMotion') as HTMLSelectElement;
            const majorityType = parseInt(getRadioListValue('.majorityTypeSettings input', '1'), 10); // Default: simple majority
            const resultsPublic = parseInt(getRadioListValue('.resultsPublicSettings input', '1'), 10); // Default: everyone
            const votesPublic = parseInt(getRadioListValue('.votesPublicSettings input', '0'), 10); // Default: nobody
            const votePolicy = parseInt((form.querySelector('.policySelect') as HTMLSelectElement).value, 10);
            let userGroups;
            if (votePolicy === POLICY_USER_GROUPS) {
                userGroups = (form.querySelector('.userGroupSelectList') as any).selectize.items.map(item => parseInt(item, 10));
            } else {
                userGroups = [];
            }
            this.widget.createVoting(type, answers, title.value, specificQuestion.value, assigned.value, majorityType, votePolicy, userGroups, resultsPublic, votesPublic);

            form.classList.add('hidden');
            opener.classList.remove('hidden');
        });
    }
}
