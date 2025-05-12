declare let Vue: any;

const POLICY_USER_GROUPS: number = 6;

const ANSWER_TEMPLATE_YES_NO_ABSTENTION: number = 0;
const ANSWER_TEMPLATE_YES_NO: number = 1;
const ANSWER_TEMPLATE_YES: number = 3;
const ANSWER_TEMPLATE_PRESENT: number = 2;

export class VotingAdmin {
    private widget: any;
    private widgetComponent: any;
    private element: HTMLElement;

    constructor($element: JQuery) {
        this.element = $element[0];

        const votingInitJson = this.element.getAttribute('data-voting');
        this.createVueWidget(votingInitJson);
        this.initVotingCreater();
        this.initVotingSorter(votingInitJson);

        $('[data-toggle="tooltip"]').tooltip();
    }

    private createVueWidget(votingInitJson) {
        const vueEl = this.element.querySelector(".votingAdmin");
        const voteSettingsUrl = this.element.getAttribute('data-url-vote-settings');
        const voteCreateUrl = this.element.getAttribute('data-vote-create');
        const voteDownloadUrl = this.element.getAttribute('data-url-vote-download');
        const addableMotions = JSON.parse(this.element.getAttribute('data-addable-motions'));
        const pollUrl = this.element.getAttribute('data-url-poll');
        const initUserGroups = JSON.parse(this.element.getAttribute('data-user-groups'));
        const sortUrl = this.element.getAttribute('data-url-sort');

        this.widget = Vue.createApp({
            template: `<div class="adminVotings">
                <voting-sort-widget
                    v-if="isSorting"
                    :votings="votings"
                    ref="voting-sort-widget"
                    @sorted="onSorted"></voting-sort-widget>
                <voting-admin-widget
                    v-if="!isSorting"
                    v-for="voting in votings"
                    :key="voting.id"
                    :voting="voting"
                    :addableMotions="addableMotions"
                    :alreadyAddedItems="alreadyAddedItems"
                    :userGroups="userGroups"
                    :voteDownloadUrl="voteDownloadUrl"
                    @set-status="setStatus"
                    @save-settings="saveSettings"
                    @remove-item="removeItem"
                    @delete-voting="deleteVoting"
                    @add-imotion="addIMotion"
                    @add-question="addQuestion"
                    @set-voters-to-user-group="setVotersToUserGroup"
                    ref="voting-admin-widget"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    isSorting: false,
                    votingsJson: null,
                    votings: null,
                    userGroups: initUserGroups,
                    voteDownloadUrl,
                    addableMotions,
                    csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                    pollingId: null,
                    onReloadedCbs: []
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
                toggleSorting() {
                    this.isSorting = !this.isSorting;
                },
                setStatus(votingBlockId, newStatus) {
                    this._performOperation(votingBlockId, {
                        op: 'update-status',
                        status: newStatus,
                    });
                },
                saveSettings(votingBlockId, title, answerTemplate, majorityType, quorumType, hasGeneralAbstention, votePolicy, maxVotesByGroup, resultsPublic, votesPublic, votingTime, assignedMotion, votesNames) {
                    this._performOperation(votingBlockId, {
                        op: 'save-settings',
                        title,
                        answerTemplate,
                        majorityType,
                        quorumType,
                        hasGeneralAbstention: (hasGeneralAbstention ? 1 : 0),
                        votePolicy,
                        maxVotesByGroup,
                        resultsPublic,
                        votesPublic,
                        votingTime,
                        assignedMotion,
                        votesNames,
                    });
                },
                onSorted(sortedIds) {
                    let postData = {
                        _csrf: this.csrf,
                        votingIds: sortedIds
                    };
                    const widget = this;
                    $.post(sortUrl, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data;
                        widget.isSorting = false;
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                deleteVoting(votingBlockId) {
                    this._performOperation(votingBlockId, {
                        op: 'delete-voting',
                    });
                },
                createVoting: function (type, answers, title, specificQuestion, assignedMotion, majorityType, votePolicy, userGroups, resultsPublic, votesPublic, votesNames) {
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
                        votesPublic,
                        votesNames
                    };

                    const widget = this;
                    $.post(voteCreateUrl, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data['votings'];
                        widget.onReloadedCbs.forEach(cb => {
                            cb(widget.votings);
                        });

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
                setVotersToUserGroup(votingBlockId, userIds, newUserGroup) {
                    this._performOperation(votingBlockId, {
                        op: 'set-voters-to-user-group',
                        userIds,
                        newUserGroup
                    });
                },
                addReloadedCb: function (cb) {
                    this.onReloadedCbs.push(cb);
                },
                reloadData: function () {
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.setVotingFromJson(data);
                        widget.onReloadedCbs.forEach(cb => {
                            cb(widget.votings);
                        });
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
            beforeUnmount() {
                window.clearInterval(this.pollingId)
            },
            created() {
                this.setVotingFromJson(votingInitJson);
                this.startPolling()
            }
        });

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'voting');

        this.widgetComponent = this.widget.mount(vueEl);

        // Used by tests to control vue-select
        window['votingAdminWidget'] = this.widgetComponent;
    }

    private initPolicyWidget() {
        const $widget = $(this.element);
        const $select: any = $widget.find('.userGroupSelect'),
            loadUrl = $select.data('load-url');
        let selectizeOption = {};
        if (loadUrl) {
            selectizeOption = Object.assign(selectizeOption, {
                loadThrottle: null,
                valueField: 'id',
                labelField: 'label',
                searchField: 'label',
                load: function (query, cb) {
                    if (!query) return cb();
                    return $.get(loadUrl, {query}).then(res => {
                        cb(res);
                    });
                },
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        }
        $select.find("select").selectize(selectizeOption);

        const $policySelect = $widget.find(".policySelect");
        $policySelect.on("change", () => {
            if (parseInt($policySelect.val() as string, 10) === POLICY_USER_GROUPS) {
                $select.removeClass("hidden");
            } else {
                $select.addClass("hidden");
            }
        }).trigger("change");
    }

    private initVotingSorter(votingInitJson) {
        const sortToggle = this.element.querySelector('.sortVotings');
        sortToggle.addEventListener('click', () => {
            this.widgetComponent.toggleSorting();
        });
        if (JSON.parse(votingInitJson).length > 1) {
            sortToggle.classList.remove('hidden');
        }

        this.widgetComponent.addReloadedCb(data => {
            if (data.length > 1) {
                sortToggle.classList.remove('hidden');
            } else {
                sortToggle.classList.add('hidden');
            }
        });
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
            const selectedAnswerType = parseInt(getRadioListValue('.answerTemplate input', '0'), 10);
            if (selectedAnswerType === ANSWER_TEMPLATE_PRESENT || selectedAnswerType === ANSWER_TEMPLATE_YES) {
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
            const votesNames = parseInt(getRadioListValue('.votesNamesSettings input', '0'), 10);
            let userGroups;
            if (votePolicy === POLICY_USER_GROUPS) {
                userGroups = (form.querySelector('.userGroupSelectList') as any).selectize.items.map(item => parseInt(item, 10));
            } else {
                userGroups = [];
            }
            this.widgetComponent.createVoting(type, answers, title.value, specificQuestion.value, assigned.value, majorityType, votePolicy, userGroups, resultsPublic, votesPublic, votesNames);

            form.classList.add('hidden');
            opener.classList.remove('hidden');
        });
    }
}
