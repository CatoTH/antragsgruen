export class AmendmentChangeProposal {
    private $statusDetails: JQuery;

    constructor(private $widget: JQuery) {
        this.$statusDetails = $widget.find('.proposalStatusDetails .statusDetails');
        this.initStatusSetter($widget.find('.statusForm'));
        this.initCommentForm($widget.find('.proposalCommentForm'));
    }

    private initStatusSetter($form: JQuery) {
        $form.find("input[type=radio]").change((ev, data: any) => {
            if (!$(ev.currentTarget).prop('checked')) {
                return;
            }
            let newVal = $(ev.currentTarget).val();
            this.$statusDetails.addClass('hidden');
            this.$statusDetails.filter('.status_' + newVal).removeClass('hidden');

            if (data && data.nosave) {
                return;
            }
            $.post($form.attr('action'), {
                setStatus: newVal,
                _csrf: $form.find('input[name=_csrf]').val()
            }, (ev) => {
                console.log(ev);
            }).fail(() => {
                alert("Could not save");
            });
        }).trigger('change', {nosave: 1});
    }

    private initCommentForm($form: JQuery) {
        let saving = false,
            $commentList = $form.find('.commentList');

        $form.submit((ev) => {
            let text = $form.find("textarea").val();
            ev.preventDefault();
            if (text == '' || saving) {
                return;
            }

            saving = true;
            $.post($form.attr('action'), {
                writeComment: text,
                _csrf: $form.find('input[name=_csrf]').val()
            }, (ev) => {
                if (ev.success) {
                    let $comment = $('<li><div class="header"><div class="date"></div><div class="name"></div></div><div class="comment"></div></li>');
                    $comment.find('.date').text(ev.comment.dateFormatted);
                    $comment.find('.name').text(ev.comment.username);
                    $comment.find('.comment').text(ev.comment.text);
                    $commentList.append($comment);
                    $form.find("textarea").val('');
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
