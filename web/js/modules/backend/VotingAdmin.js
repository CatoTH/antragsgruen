// @ts-check

import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";
import translateDirective from "/js/vue/Translate.vue.js";
import votingAdmin from "/js/vue/voting/VotingAdmin.js";
import votingSort from "/js/vue/voting/VotingAdminSort.js";
import voteList from "/js/vue/voting/VotingList.js";
import policySelect from "/js/vue/PolicySelect.js";
import selectize from "/js/vue/Selectize.js";
import tooltipDirective from "/js/vue/Tooltip.vue.js";
import vuedraggable from "/npm/vuedraggable.js";

export class VotingAdmin {
    /** @type {HTMLElement} */
    element;

    /** @type object */
    CONSTANTS;

    widgetComponent;

    constructor(el, CONSTANTS) {
        this.element = el;
        this.CONSTANTS = CONSTANTS;

        const votingInitJson = this.element.getAttribute('data-voting');
        this.createVueWidget(votingInitJson, CONSTANTS);
        this.initVotingCreater();
        window.component = this.widgetComponent;
        this.initVotingSorter(votingInitJson);
    }

    createVueWidget(votingInitJson, CONSTANTS) {
        const commonsMixins = getVotingCommonMixins(CONSTANTS);

        const vueEl = this.element.querySelector(".votingAdmin");
        const voteDownloadUrl = this.element.getAttribute('data-url-vote-download');
        const addableMotions = JSON.parse(this.element.getAttribute('data-addable-motions'));
        const pollUrl = this.element.getAttribute('data-url-poll');
        const initUserGroups = JSON.parse(this.element.getAttribute('data-user-groups'));
        const sortUrl = this.element.getAttribute('data-url-sort');
        const voteSettingsUrl = this.element.getAttribute('data-url-vote-settings');
        const voteCreateUrl = this.element.getAttribute('data-vote-create');

        /** @type {import('vue').App} */
        const widget = createApp({
            render() {
                const VotingSortWidget = resolveComponent('voting-sort-widget');
                const VotingAdminWidget = resolveComponent('voting-admin-widget');

                return h('div', {class: 'adminVotings'}, [
                    this.isSorting
                        ? h(VotingSortWidget, {
                            votings: this.votings,
                            ref: 'voting-sort-widget',
                            onSorted: (sortedIds) => this.onSorted(sortedIds),
                        })
                        : null,

                    ...(!this.isSorting
                            ? this.votings.map(voting =>
                                h(VotingAdminWidget, {
                                    key: voting.id,
                                    voting: voting,
                                    addableMotions: this.addableMotions,
                                    alreadyAddedItems: this.alreadyAddedItems,
                                    userGroups: this.userGroups,
                                    voteDownloadUrl: this.voteDownloadUrl,
                                    onSetStatus: (votingBlockId, newStatus) => this.setStatus(votingBlockId, newStatus),
                                    onSaveSettings: (votingBlockId, title, answerTemplate, majorityType, quorumType, hasGeneralAbstention, votePolicy, maxVotesByGroup, resultsPublic, votesPublic, votingTime, assignedMotion, votesNames) =>
                                        this.saveSettings(votingBlockId, title, answerTemplate, majorityType, quorumType, hasGeneralAbstention, votePolicy, maxVotesByGroup, resultsPublic, votesPublic, votingTime, assignedMotion, votesNames),
                                    onRemoveItem: (votingBlockId, itemType, itemId) => this.removeItem(votingBlockId, itemType, itemId),
                                    onDeleteVoting: (votingBlockId) => this.deleteVoting(votingBlockId),
                                    onAddImotion: (votingBlockId, itemDefinition) => this.addIMotion(votingBlockId, itemDefinition),
                                    onAddQuestion: (votingBlockId, question) => this.addQuestion(votingBlockId, question),
                                    onSetVotersToUserGroup: (votingBlockId, userIds, newUserGroup) => this.setVotersToUserGroup(votingBlockId, userIds, newUserGroup),
                                    ref_for: true,
                                    ref: 'voting-admin-widget',
                                })
                            )
                            : []
                    ),
                ]);
            },
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

        widget.mixin(commonsMixins);
        widget.component('voting-admin-widget', votingAdmin);
        widget.component('voting-sort-widget', votingSort)
        widget.component('draggable', vuedraggable);
        widget.component('policy-select', policySelect);
        widget.component('v-selectize', selectize);
        widget.component('vote-list', voteList);

        widget.directive('t', translateDirective);
        widget.directive('tooltip', tooltipDirective);

        this.widgetComponent = widget.mount(vueEl);

        // Used by tests to control vue-select
        window['votingAdminWidget'] = this.widgetComponent;
    }

    initVotingSorter(votingInitJson) {
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

    initVotingCreater() {
        const opener = this.element.querySelector('.createVotingOpener'),
            form = this.element.querySelector('.createVotingHolder'),
            specificQuestion = this.element.querySelector('.specificQuestion'),
            majorityType = this.element.querySelector('.majorityTypeSettings');
        opener.addEventListener('click', () => {
            form.classList.remove('hidden');
            opener.classList.add('hidden');
        });

        const getRadioListValue = (selector, defaultValue) => {
            let val = defaultValue;
            form.querySelectorAll(selector).forEach(el => {
                if (el.checked) {
                    val = el.value;
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
            if (selectedAnswerType === this.CONSTANTS.ANSWER_TEMPLATE_PRESENT || selectedAnswerType === this.CONSTANTS.ANSWER_TEMPLATE_YES) {
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
            const title = form.querySelector('.settingsTitle');
            const specificQuestion = form.querySelector('.settingsQuestion');
            const assigned = form.querySelector('.settingsAssignedMotion');
            const majorityType = parseInt(getRadioListValue('.majorityTypeSettings input', '1'), 10); // Default: simple majority
            const resultsPublic = parseInt(getRadioListValue('.resultsPublicSettings input', '1'), 10); // Default: everyone
            const votesPublic = parseInt(getRadioListValue('.votesPublicSettings input', '0'), 10); // Default: nobody
            const votePolicy = parseInt(form.querySelector('.policySelect').value, 10);
            const votesNames = parseInt(getRadioListValue('.votesNamesSettings input', '0'), 10);
            let userGroups;
            if (votePolicy === this.CONSTANTS.POLICY_USER_GROUPS) {
                userGroups = form.querySelector('.userGroupSelectList').selectize.items.map(item => parseInt(item, 10));
            } else {
                userGroups = [];
            }
            this.widgetComponent.createVoting(type, answers, title.value, specificQuestion.value, assigned.value, majorityType, votePolicy, userGroups, resultsPublic, votesPublic, votesNames);

            form.classList.add('hidden');
            opener.classList.remove('hidden');
        });

        $(form).find('[data-toggle="tooltip"]').tooltip();
    }

    initPolicyWidget() {
        const $widget = $(this.element);
        const $select = $widget.find('.userGroupSelect'),
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
                        return cb(res);
                    });
                },
                render: {
                    option_create: (data, escape) => {
                        const addTag = translateDirective.getTranslation("motion", "add_tag")
                            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        return '<div class="create">' + addTag + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        }
        $select.find("select").selectize(selectizeOption);

        const $policySelect = $widget.find(".policySelect");
        $policySelect.on("change", () => {
            if (parseInt($policySelect.val(), 10) === this.CONSTANTS.POLICY_USER_GROUPS) {
                $select.removeClass("hidden");
            } else {
                $select.addClass("hidden");
            }
        }).trigger("change");
    }
}

