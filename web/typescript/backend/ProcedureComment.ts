export class ProcedureComment {
    private csrf: string;
    private $form: JQuery;

    constructor(private $widget: JQuery) {
        this.csrf = this.$widget.find('input[name=_csrf]').val();
        this.$form = this.$widget.find('form');
        this.$widget.find('.writingOpener').click(this.openWriting.bind(this));
        this.$form.submit(this.submitComment.bind(this));

        this.$widget.find('textarea').keypress((ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                this.submitComment(ev);
            }
        })
    }

    private openWriting() {
        this.$widget.addClass('writing');
        this.$widget.find('textarea').focus();
    }

    private submitComment(ev) {
        ev.preventDefault();
        let data = {
            '_csrf': this.csrf,
            'comment': this.$widget.find('textarea').val(),
            'id': this.$form.data('id'),
        };
        $.post(this.$form.attr('action'), data, (ret) => {
            if (!ret['success']) {
                alert(ret['message']);
                return;
            }
            let $comment = this.$widget.find('.currentComment');
            $comment.find('.date').text(ret['date_str']);
            $comment.find('.name').text(ret['user_str']);
            $comment.find('.comment').html(ret['text']);
            $comment.removeClass('empty');

            this.$widget.find('textarea').val('');
            this.$widget.removeClass('writing');
        });
    }
}
