const STATUS_REFERRED = 10;
const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY = 22;
const STATUS_CUSTOM_STRING = 23;

export class ChangeProposedProcedure {
    private $openerBtn: JQuery;
    private $statusDetails: JQuery;
    private $visibilityInput: JQuery;
    private $votingStatusInput: JQuery;
    private $votingBlockId: JQuery;
    private saveUrl: string;
    private context: string;
    private csrf: string;
    private savingComment: boolean = false;

    constructor(private $widget: JQuery) {
        this.initElements();
        this.initOpener();
        this.initStatusSetter();
        this.initCommentForm();
        this.initVotingBlock();
        this.initExplanation();
        $widget.on("submit", ev => ev.preventDefault());
    }

    private initElements() {
        this.$statusDetails = this.$widget.find('.statusDetails');
        this.$visibilityInput = this.$widget.find('input[name=proposalVisible]');
        this.$votingStatusInput = this.$widget.find('input[name=votingStatus]');
        this.$votingBlockId = this.$widget.find('input[name=votingBlockId]');
        this.$openerBtn = $('.proposedChangesOpener button');
        this.context = this.$widget.data('context');
        this.saveUrl = this.$widget.attr('action');
        this.csrf = this.$widget.find('input[name=_csrf]').val() as string;
    }

    private initOpener() {
        this.$openerBtn.click(() => {
            this.$widget.removeClass('hidden');
            this.$openerBtn.addClass('hidden');
            localStorage.setItem('proposed_procedure_enabled', '1');
        });
        this.$widget.on('click', '.closeBtn', () => {
            this.$widget.addClass('hidden');
            this.$openerBtn.removeClass('hidden');
            localStorage.setItem('proposed_procedure_enabled', '0');
        });

        if (localStorage.getItem('proposed_procedure_enabled') === '1') {
            this.$widget.removeClass('hidden');
            this.$openerBtn.addClass('hidden');
        } else {
            this.$widget.addClass('hidden');
        }
    }

    private reinitAfterReload() {
        this.initElements();
        this.statusChanged();
        this.commentsScrollBottom();
        this.initExplanation();
        this.$widget.find('.newBlock').addClass('hidden');
        this.$widget.find('.selectlist').selectlist();
        this.$widget.find('.notifyProposerSection').addClass('hidden');
    }

    private setGlobalProposedStr(html: string) {
        $(".motionData .proposedStatusRow .str").html(html);
    }

    private performCallWithReload(data: object) {
        data['_csrf'] = this.csrf;

        $.post(this.saveUrl, data, (ret) => {
            if (ret['success']) {
                let $content = $(ret['html']);
                this.$widget.children().remove();
                this.$widget.append($content.children());
                this.reinitAfterReload();
                this.$widget.addClass('showSaved').removeClass('isChanged');
                if (ret['proposalStr']) {
                    this.setGlobalProposedStr(ret['proposalStr']);
                }
                window.setTimeout(() => this.$widget.removeClass('showSaved'), 2000);
            } else if (ret['error']) {
                alert(ret['error']);
            } else {
                alert('An error occurred');
            }
        }).fail(() => {
            alert('Could not save');
        });
    }

    private notifyProposer() {
        const text = this.$widget.find('textarea[name=proposalNotificationText]').val(),
            fromName = this.$widget.find('input[name=proposalNotificationFrom]').val(),
            replyTo = this.$widget.find('input[name=proposalNotificationReply]').val();
        this.performCallWithReload({
            'notifyProposer': '1',
            'text': text,
            'fromName': fromName,
            'replyTo': replyTo,
        });
    }

    private setPropserHasAccepted() {
        const confirm = this.$widget.find('.setConfirmation').data('msg');
        bootbox.confirm(confirm, (result) => {
            if (result) {
                this.performCallWithReload({
                    'setProposerHasAccepted': '1',
                });
            }
        });
    }

    private sendAgain() {
        const confirm = this.$widget.find('.sendAgain').data('msg');
        bootbox.confirm(confirm, (result) => {
            if (result) {
                this.performCallWithReload({
                    'sendAgain': '1',
                });
            }
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

        this.$widget.on('change keyup', 'input, textarea', (ev) => {
            if ($(ev.currentTarget).parents('.proposalCommentForm').length > 0) { // The comment textfield
                return;
            }
            this.$widget.addClass('isChanged');
        });

        this.$widget.on('changed.fu.selectlist', '#obsoletedByAmendment', () => {
            this.$widget.addClass('isChanged');
        });

        this.$widget.on('click', '.saving button', this.saveStatus.bind(this));

        this.$widget.on('click', '.notifyProposer', () => {
            this.$widget.find('.notifyProposerSection').removeClass('hidden');
        });
        this.$widget.on('click', '.setConfirmation', this.setPropserHasAccepted.bind(this));
        this.$widget.on('click', '.sendAgain', this.sendAgain.bind(this));
        this.$widget.on('click', 'button[name=notificationSubmit]', this.notifyProposer.bind(this));
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

    private doSaveComment() {
        let $commentWidget = this.$widget.find('.proposalCommentForm'),
            $commentList = $commentWidget.find('.commentList'),
            text = $commentWidget.find('textarea').val();

        if (text == '' || this.savingComment) {
            return;
        }

        this.savingComment = true;
        $.post(this.saveUrl, {
            writeComment: text,
            _csrf: this.csrf
        }, (ev) => {
            if (ev.success) {
                let delHtml = '';
                if (ev.comment.delLink) {
                    delHtml = '<button type="button" data-url="' + ev.comment.delLink + '" class="btn-link delComment">';
                    delHtml += '<span class="glyphicon glyphicon-trash"></span></button>';
                }
                let $comment = $('<li class="comment"><div class="header"><div class="date"></div>' + delHtml + '<div class="name"></div></div><div class="comment"></div></li>');
                $comment.find('.date').text(ev.comment.dateFormatted);
                $comment.find('.name').text(ev.comment.username);
                $comment.find('.comment').text(ev.comment.text);
                $comment.data('id', ev.comment.id);
                $commentList.append($comment);
                $commentWidget.find('textarea').val('');
                $commentList[0].scrollTop = $commentList[0].scrollHeight;
            } else {
                alert('Could not save: ' + JSON.stringify(ev));
            }
            this.savingComment = false;
        }).fail(() => {
            alert('Could not save');
            this.savingComment = false;
        });
    }

    private delComment($comment: JQuery) {
        $.post($comment.find(".delComment").data("url"), {
            "_csrf": this.csrf,
            "id": $comment.data("id"),
        }, (ret) => {
            if (ret['success']) {
                $comment.remove();
            } else {
                alert("Error: " + ret['error']);
            }
        });
    }

    private initCommentForm() {
        this.$widget.on('click', '.proposalCommentForm button', () => {
            this.doSaveComment();
        });
        this.commentsScrollBottom();

        this.$widget.on('keypress', '.proposalCommentForm textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                this.doSaveComment();
            }
        });

        this.$widget.on('click', '.delComment', (ev) => {
            this.delComment($(ev.currentTarget).parents(".comment").first());
        });
    }
}
