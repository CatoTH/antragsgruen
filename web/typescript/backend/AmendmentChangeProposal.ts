const STATUS_REFERRED = 10;
const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY = 22;

export class AmendmentChangeProposal {
    private $statusDetails: JQuery;
    private $visibilityInput: JQuery;
    private $votingStatusInput: JQuery;
    private $votingBlockId: JQuery;
    private saveUrl: string;
    private csrf: string;

    constructor(private $widget: JQuery) {
        this.$statusDetails = $widget.find('.statusDetails');
        this.$visibilityInput = $widget.find('input[name=proposalVisible]');
        this.$votingStatusInput = $widget.find('input[name=votingStatus]');
        this.$votingBlockId = $widget.find('input[name=votingBlockId]');
        this.initStatusSetter();
        this.initCommentForm();
        this.initVotingBlock();
        this.saveUrl = $widget.attr('action');
        this.csrf = $widget.find('input[name=_csrf]').val();
        $widget.submit(ev => ev.preventDefault());
    }

    private saveStatus() {
        let newVal = this.$widget.find('.statusForm input[type=radio]:checked').val();
        let data = {
            setStatus: newVal,
            visible: (this.$visibilityInput.prop('checked') ? 1 : 0),
            votingBlockId: this.$votingBlockId.val(),
            _csrf: this.csrf
        };

        if (newVal == STATUS_REFERRED) {
            data['proposalComment'] = this.$widget.find('input[name=referredTo]').val();
        }
        if (newVal == STATUS_OBSOLETED_BY) {
            data['proposalComment'] = this.$widget.find('input[name=obsoletedByAmendment]').val();
        }
        if (newVal == STATUS_VOTE) {
            data['votingStatus'] = this.$votingStatusInput.filter(':checked').val();
        }
        if (data.votingBlockId == 'NEW') {
            data['votingBlockTitle'] = this.$widget.find('input[name=newBlockTitle]').val();
        }

        $.post(this.saveUrl, data, (ret) => {
            this.$widget.find('.saving').addClass('hidden');
            this.$widget.find('.saved').removeClass('hidden');
            window.setTimeout(() => this.$widget.find('.saved').addClass('hidden'), 2000);
            if (ret['needsReload']) {
                window.location.reload();
            }
        }).fail(() => {
            alert("Could not save");
        });
    }

    private initStatusSetter() {
        this.$widget.find(".statusForm input[type=radio]").change((ev) => {
            if (!$(ev.currentTarget).prop('checked')) {
                return;
            }
            let newVal = this.$widget.find('.statusForm input[type=radio]:checked').val();
            this.$statusDetails.addClass('hidden');
            this.$statusDetails.filter('.status_' + newVal).removeClass('hidden');
            this.$widget.find('.saving').removeClass('hidden');
        }).trigger('change');

        this.$widget.find("input[type=radio]").change(() => {
            this.$widget.find('.saving').removeClass('hidden');
        });

        this.$widget.find('input[name=referredTo]').on('keyup', () => {
            this.$widget.find('.saving').removeClass('hidden');
        });
        this.$widget.find('#obsoletedByAmendment').on('changed.fu.selectlist', () => {
            this.$widget.find('.saving').removeClass('hidden');
        });

        this.$widget.find('.saving').addClass('hidden');
        this.$widget.find('.saving button').click(this.saveStatus.bind(this));
    }

    private initVotingBlock() {
        this.$widget.find('#votingBlockId').on('changed.fu.selectlist', () => {
            this.$widget.find('.saving').removeClass('hidden');
            if (this.$votingBlockId.val() == 'NEW') {
                this.$widget.find(".newBlock").removeClass('hidden');
            } else {
                this.$widget.find(".newBlock").addClass('hidden');
            }
        });
        this.$widget.find(".newBlock").addClass('hidden');
    }

    private initCommentForm() {
        let $commentWidget = this.$widget.find('.proposalCommentForm'),
            saving = false,
            $commentList = $commentWidget.find('.commentList');

        $commentWidget.find('button').click(() => {
            let text = $commentWidget.find('textarea').val();
            if (text == '' || saving) {
                return;
            }

            saving = true;
            $.post(this.saveUrl, {
                writeComment: text,
                _csrf: this.csrf
            }, (ev) => {
                if (ev.success) {
                    let $comment = $('<li><div class="header"><div class="date"></div><div class="name"></div></div><div class="comment"></div></li>');
                    $comment.find('.date').text(ev.comment.dateFormatted);
                    $comment.find('.name').text(ev.comment.username);
                    $comment.find('.comment').text(ev.comment.text);
                    $commentList.append($comment);
                    $commentWidget.find('textarea').val('');
                    $commentList[0].scrollTop = $commentList[0].scrollHeight;
                } else {
                    alert('Could not save: ' + JSON.stringify(ev));
                }
                saving = false;
            }).fail(() => {
                alert("Could not save");
                saving = false;
            });
        });
        $commentList[0].scrollTop = $commentList[0].scrollHeight;
    }
}
