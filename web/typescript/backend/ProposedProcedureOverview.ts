import { ResponsibilitySetter } from './ResponsibilitySetter';

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
    private plannedInterval: number = null;

    constructor(private $widget: JQuery) {
        this.csrf = this.$widget.find('input[name=_csrf]').val() as string;
        this.$widget.on('change', 'input[name=visible]', this.onVisibleChanged.bind(this));
        this.initComments();
        this.initTagsEdit();
        this.initUpdateWidget();
        this.onContentUpdated();

        new ResponsibilitySetter($('.proposedProcedureReloadHolder'));

        this.$widget.on('click', '.contactShow', (ev) => {
            ev.preventDefault();
            $(ev.currentTarget).next('.contactDetails').removeClass('hidden');
        });
    }

    private onContentUpdated() {
        this.$widget.find(".commentList").each((i, el) => {
            el.scrollTop = el.scrollHeight;
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
                if (ret['error']) {
                    alert(ret['error']);
                }
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

        this.$widget.on('click', '.cancelWriting', (ev) => {
            ev.preventDefault();
            $(ev.currentTarget).parents('td').first().removeClass('writing');
        });

        this.$widget.on('keypress', 'textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                let $textarea = $(ev.currentTarget);
                this.submitComment($textarea.parents('td').first());
            }
        });
    }

    private initTagsEdit() {
        this.$widget.on('click', '.tagEditOpener', (ev) => {
            const $col = $(ev.currentTarget).parents(".procedure").first();
            $col.find('.noTags, .tagNames').addClass('hidden');
            $col.find('.tagsSelector').removeClass('hidden').addClass('editing');

            const $tagsSelect = $col.find('.tagsSelector select') as any;
            $tagsSelect.selectize({
                create: true,
                plugins: ["remove_button"],
                render: {
                    option_create: (data, escape) => {
                        return '<div class="create">' + __t('std', 'add_tag') + ': <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        });

        this.$widget.on('click', '.tagsSaver', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const $btn = $(ev.currentTarget),
                saveUrl = $btn.data('save-url'),
                $col = $btn.parents(".procedure").first(),
                select = $col.find(".tagsSelector select")[0] as any;

            $.ajax({
                url: saveUrl,
                type: "POST",
                data: JSON.stringify({tags: select.selectize.items}),
                processData: false,
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                headers: {"X-CSRF-Token": this.csrf},
                success: data => {
                    if (data.msg_error) {
                        bootbox.alert(data.msg_error);
                    } else {
                        $col.find('.tagsSelector').addClass('hidden').removeClass('editing');
                        this.reload(() => {});
                    }
                }
            }).catch(function (err) {
                alert(err.responseText);
            })
        });
    }

    private openWriting(ev) {
        ev.preventDefault();
        let $btn = $(ev.currentTarget),
            $td = $btn.parents('td').first();

        $td.addClass('writing');
        $td.find('textarea').trigger("focus");;
    }

    private submitComment($commentTd: JQuery) {
        let data = {
            '_csrf': this.csrf,
            'comment': $commentTd.find('textarea').val(),
            'id': $commentTd.parents('.item').data('id'),
        };
        $.post($commentTd.data('post-url'), data, (ret) => {
            if (!ret['success']) {
                if (ret['error']) {
                    alert(ret['error']);
                }
                return;
            }
            let $comment = $commentTd.find('.template').clone();
            $comment.find('.date').text(ret['date_str']);
            $comment.find('.name').text(ret['user_str']);
            $comment.find('.comment').html(ret['text']);
            $comment.removeClass('template');
            $comment.insertBefore($commentTd.find('.template'));
            window.setTimeout(() => {
                $commentTd.find(".commentList")[0].scrollTop = $commentTd.find(".commentList")[0].scrollHeight;
            }, 1);

            $commentTd.find('textarea').val('');
            $commentTd.removeClass('writing');
        });
    }


    private skipReload(): boolean {
        if (this.$widget.find('.respHolder.dropdown.open').length > 0) {
            return true;
        } else if (this.$widget.find('.comments.writing').length > 0) {
            return true;
        } else if (this.$widget.find('.tagsSelector.editing').length > 0) {
            return true;
        } else {
            return false;
        }
    }

    private reload(cb) {
        if (this.skipReload()) {
            console.log('No reload, as comment writing is active');
            cb();
            return;
        }
        $.ajax({
            type: "GET",
            url: this.updateUrl,
            success: (data: ReloadResult) => {
                if (!data.success) {
                    if (data.error) {
                        alert(data.error);
                    }
                    return;
                }
                this.$dateField.text(data.date);
                this.$proposalList.html(data.html);
                this.onContentUpdated();

                cb();
            },
            error: () => {
                cb();
            }
        })
    }

    private executeInterval() {
        this.reload(() => {
            this.plannedInterval = window.setTimeout(this.executeInterval.bind(this), 5000);
        });
    }

    private startInterval() {
        if (this.plannedInterval !== null) {
            return;
        }
        this.plannedInterval = window.setTimeout(this.executeInterval.bind(this), 5000);
    }

    private stopInterval() {
        if (this.plannedInterval === null) {
            return;
        }
        window.clearTimeout(this.plannedInterval);
        this.plannedInterval = null;
    }

    private initUpdateWidget() {
        this.$updateWidget = this.$widget.find('.autoUpdateWidget');
        this.$proposalList = this.$widget.find('.reloadContent');
        this.$dateField = this.$widget.find('.currentDate .date');
        this.updateUrl = this.$widget.data('reload-url');

        let $toggle = this.$updateWidget.find('#autoUpdateToggle');
        $toggle.on("change", () => {
            let active: boolean = $toggle.prop('checked');
            if (active) {
                this.reload(() => {});
                this.startInterval();
            } else {
                this.stopInterval();
            }
        }).trigger('change');
    }
}
