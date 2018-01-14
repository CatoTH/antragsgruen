export class ProposedProcedureOverview {
    private csrf: string;

    constructor(private $widget: JQuery) {
        this.csrf = this.$widget.find('input[name=_csrf]').val();
        this.$widget.on('change', 'input[name=visible]', this.onVisibleChanged.bind(this));
        this.initComments();
    }

    private onVisibleChanged(ev) {
        let $checkbox = $(ev.currentTarget);

        let data = {
            '_csrf': this.csrf,
            'visible': ($checkbox.prop('checked') ? 1 : 0),
            'id': $checkbox.parents('.item').first().data('id'),
        };
        $.post($checkbox.data('save-url'), data, (ret) => {
            if (!ret['success']) {
                alert(ret['error']);
                return;
            }
            console.log('Saved');
        });
    }

    private initComments() {
        this.$widget.on('click', '.writingOpener', this.openWriting.bind(this));

        this.$widget.on('click', '.submitComment', (ev) => {
            ev.preventDefault();
            let $btn = $(ev.currentTarget);
            this.submitComment($btn.parents('td').first());
        });

        this.$widget.on('keypress', 'textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                let $textarea = $(ev.currentTarget);
                this.submitComment($textarea.parents('td').first());
            }
        });
    }

    private openWriting(ev) {
        ev.preventDefault();
        let $btn = $(ev.currentTarget),
            $td = $btn.parents('td').first();

        $td.addClass('writing');
        $td.find('textarea').focus();
    }

    private submitComment($commentTd: JQuery) {
        let data = {
            '_csrf': this.csrf,
            'comment': $commentTd.find('textarea').val(),
            'id': $commentTd.parents('.item').data('id'),
        };
        $.post($commentTd.data('post-url'), data, (ret) => {
            if (!ret['success']) {
                alert(ret['error']);
                return;
            }
            let $comment = $commentTd.find('.currentComment');
            $comment.find('.date').text(ret['date_str']);
            $comment.find('.name').text(ret['user_str']);
            $comment.find('.comment').html(ret['text']);
            $comment.removeClass('empty');

            $commentTd.find('textarea').val('');
            $commentTd.removeClass('writing');
        });
    }
}