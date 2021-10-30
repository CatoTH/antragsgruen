import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;
    private element: HTMLElement;

    constructor($element: JQuery) {
        this.element = $element[0];
        this.createVueWidget();
        this.initVotingCreater();
    }

    private createVueWidget() {
        const vueEl = this.element.querySelector(".votingAdmin");
        const voteSettingsUrl = this.element.getAttribute('data-url-vote-settings');
        const voteCreateUrl = this.element.getAttribute('data-vote-create');
        const addableMotions = JSON.parse(this.element.getAttribute('data-addable-motions'));
        const pollUrl = this.element.getAttribute('data-url-poll');
        const votingInitJson = this.element.getAttribute('data-voting');

        this.widget = new Vue({
            el: vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     :addableMotions="addableMotions"
                                     :alreadyAddedItems="alreadyAddedItems"
                                     @set-status="setStatus"
                                     @save-settings="saveSettings"
                                     @remove-item="removeItem"
                                     @delete-voting="deleteVoting"
                                     @add-item="addItem"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    votingsJson: null,
                    votings: null,
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
                saveSettings(votingBlockId, title, majorityType, resultsPublic, votesPublic, assignedMotion) {
                    this._performOperation(votingBlockId, {
                        op: 'save-settings',
                        title,
                        majorityType,
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
                createVoting: function (title, assignedMotion) {
                    let postData = {
                        _csrf: this.csrf,
                        title,
                        assignedMotion
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
                addItem(votingBlockId, itemDefinition) {
                    this._performOperation(votingBlockId, {
                        op: 'add-item',
                        itemDefinition
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
    }

    private initVotingCreater() {
        const opener = this.element.querySelector('.createVotingOpener'),
            form = this.element.querySelector('.createVotingHolder');
        opener.addEventListener('click', () => {
            form.classList.remove('hidden');
            opener.classList.add('hidden');
        });
        form.querySelector('form').addEventListener('submit', (ev) => {
            ev.stopPropagation();
            ev.preventDefault();
            const title = form.querySelector('.settingsTitle') as HTMLInputElement;
            const assigned = form.querySelector('.settingsAssignedMotion') as HTMLSelectElement;
            this.widget.createVoting(title.value, assigned.value);

            form.classList.add('hidden');
            opener.classList.remove('hidden');
        });
    }
}
