interface ReloadResult {
    success: boolean;
    error?: string;
    html?: string;
    date?: string;
}

export class ProposedProcedureOverview {
    private csrf: string;
    private updateUrl: string;
    private $updateWidget: JQuery;
    private $proposalList: JQuery;
    private $dateField: JQuery;
    private interval: number = null;

    constructor(private $widget: JQuery) {
        this.csrf = this.$widget.find('input[name=_csrf]').val();
        this.$widget.on('change', 'input[name=visible]', this.onVisibleChanged.bind(this));
        this.initComments();
        this.initUpdateWidget();

        this.$widget.on('click', '.contactShow', (ev) => {
            ev.preventDefault();
            $(ev.currentTarget).next('.contactDetails').removeClass('hidden');
        });
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


    private skipReload(): boolean {
        if (this.$widget.find('.comment.writing').length > 0) {
            return true;
        } else {
            return false;
        }
    }

    private reload() {
        if (this.skipReload()) {
            console.log('No reload, as comment writing is active')
            return;
        }
        $.get(this.updateUrl, (data: ReloadResult) => {
            if (!data.success) {
                alert(data.error);
                return;
            }
            this.$dateField.text(data.date);
            this.$proposalList.html(data.html);
        });
    }

    private startInterval() {
        if (this.interval !== null) {
            return;
        }
        this.interval = window.setInterval(this.reload.bind(this), 5000);
    }

    private stopInterval() {
        if (this.interval === null) {
            return;
        }
        window.clearInterval(this.interval);
        this.interval = null;
    }

    private initUpdateWidget() {
        this.$updateWidget = this.$widget.find('.autoUpdateWidget');
        this.$proposalList = this.$widget.find('.reloadContent');
        this.$dateField = this.$widget.find('.currentDate .date');
        this.updateUrl = this.$widget.data('reload-url');

        let $toggle = this.$updateWidget.find('#autoUpdateToggle');
        $toggle.change(() => {
            let active: boolean = $toggle.prop('checked');
            if (active) {
                this.reload();
                this.startInterval();
            } else {
                this.stopInterval();
            }
        }).trigger('change');
    }
}
