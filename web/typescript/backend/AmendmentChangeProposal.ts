const STATUS_REFERRED = 10;
const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY = 22;
const STATUS_CUSTOM_STRING = 23;

export class AmendmentChangeProposal {
    private $statusDetails: JQuery;
    private $visibilityInput: JQuery;
    private $votingStatusInput: JQuery;
    private $votingBlockId: JQuery;
    private saveUrl: string;
    private context: string;
    private csrf: string;

    constructor(private $widget: JQuery) {
        this.initElements();
        this.initStatusSetter();
        this.initCommentForm();
        this.initVotingBlock();
        this.initExplanation();
        $widget.submit(ev => ev.preventDefault());
    }

    private initElements() {
        this.$statusDetails = this.$widget.find('.statusDetails');
        this.$visibilityInput = this.$widget.find('input[name=proposalVisible]');
        this.$votingStatusInput = this.$widget.find('input[name=votingStatus]');
        this.$votingBlockId = this.$widget.find('input[name=votingBlockId]');
        this.context = this.$widget.data('context');
        this.saveUrl = this.$widget.attr('action');
        this.csrf = this.$widget.find('input[name=_csrf]').val();
    }

    private reinitAfterReload() {
        this.initElements();
        this.statusChanged();
        this.commentsScrollBottom();
        this.initExplanation();
        this.$widget.find('.newBlock').addClass('hidden');
        this.$widget.find('.selectlist').selectlist();
    }

    private performCallWithReload(data: object) {
        data['_csrf'] = this.csrf;

        $.post(this.saveUrl, data, (ret) => {
            this.$widget.addClass('showSaved').removeClass('isChanged');
            window.setTimeout(() => this.$widget.removeClass('showSaved'), 2000);
            if (ret['success']) {
                let $content = $(ret['html']);
                this.$widget.children().remove();
                this.$widget.append($content.children());
                this.reinitAfterReload();
            } else if (ret['error']) {
                alert(ret['error']);
            } else {
                alert('An error ocurred');
            }
        }).fail(() => {
            alert('Could not save');
        });
    }

    private notifyProposer() {
        this.performCallWithReload({
            'notifyProposer': '1'
        });
    }

    private saveStatus() {
        let newVal = this.$widget.find('.statusForm input[type=radio]:checked').val();
        let data = {
            setStatus: newVal,
            visible: (this.$visibilityInput.prop('checked') ? 1 : 0),
            votingBlockId: this.$votingBlockId.val(),
        };

        if (newVal == STATUS_REFERRED) {
            data['proposalComment'] = this.$widget.find('input[name=referredTo]').val();
        }
        if (newVal == STATUS_OBSOLETED_BY) {
            data['proposalComment'] = this.$widget.find('input[name=obsoletedByAmendment]').val();
        }
        if (newVal == STATUS_CUSTOM_STRING) {
            data['proposalComment'] = this.$widget.find('input[name=statusCustomStr]').val();
        }
        if (newVal == STATUS_VOTE) {
            data['votingStatus'] = this.$votingStatusInput.filter(':checked').val();
        }
        if (data.votingBlockId == 'NEW') {
            data['votingBlockTitle'] = this.$widget.find('input[name=newBlockTitle]').val();
        }

        if (this.$widget.find('input[name=setPublicExplanation]').prop('checked')) {
            data['proposalExplanation'] = this.$widget.find('textarea[name=proposalExplanation]').val();
        }

        this.performCallWithReload(data);
    }

    private statusChanged() {
        let newVal = this.$widget.find('.statusForm input[type=radio]:checked').val();
        this.$statusDetails.addClass('hidden');
        this.$statusDetails.filter('.status_' + newVal).removeClass('hidden');
        if (newVal == 0) {
            this.$widget.addClass('noStatus');
        } else {
            this.$widget.removeClass('noStatus');
        }
    }

    private initStatusSetter() {
        this.$widget.on('change', '.statusForm input[type=radio]', (ev, data) => {
            if (!$(ev.currentTarget).prop('checked')) {
                return;
            }
            this.statusChanged();
            if (data && data.init === true) {
                return;
            }
            this.$widget.addClass('isChanged');
        });
        this.$widget.find('.statusForm input[type=radio]').trigger('change', {'init': true});

        this.$widget.on('change keyup', 'input, textarea', () => {
            this.$widget.addClass('isChanged');
        });

        this.$widget.on('changed.fu.selectlist', '#obsoletedByAmendment', () => {
            this.$widget.addClass('isChanged');
        });

        this.$widget.on('click', '.saving button', this.saveStatus.bind(this));
        this.$widget.on('click', '.notifyProposer', this.notifyProposer.bind(this));
    }

    private initVotingBlock() {
        this.$widget.on('changed.fu.selectlist', '#votingBlockId', () => {
            this.$widget.addClass('isChanged');
            if (this.$votingBlockId.val() == 'NEW') {
                this.$widget.find(".newBlock").removeClass('hidden');
            } else {
                this.$widget.find(".newBlock").addClass('hidden');
            }
        });
        this.$widget.find('.newBlock').addClass('hidden');
    }

    private initExplanation() {
        this.$widget.find('input[name=setPublicExplanation]').change((ev) => {
           if ($(ev.target).prop('checked')) {
               this.$widget.find('section.publicExplanation').removeClass('hidden');
           } else {
               this.$widget.find('section.publicExplanation').addClass('hidden');
           }
        });
        if (this.$widget.find('input[name=setPublicExplanation]').prop('checked')) {
            this.$widget.find('section.publicExplanation').removeClass('hidden');
        } else {
            this.$widget.find('section.publicExplanation').addClass('hidden');
        }
    }

    private commentsScrollBottom() {
        let $commentList = this.$widget.find('.proposalCommentForm .commentList');
        $commentList[0].scrollTop = $commentList[0].scrollHeight;
    }

    private initCommentForm() {
        this.$widget.on('click', '.proposalCommentForm button', () => {
            let $commentWidget = this.$widget.find('.proposalCommentForm'),
                saving = false,
                $commentList = $commentWidget.find('.commentList'),
                text = $commentWidget.find('textarea').val();

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
                alert('Could not save');
                saving = false;
            });
        });
        this.commentsScrollBottom();
    }
}
