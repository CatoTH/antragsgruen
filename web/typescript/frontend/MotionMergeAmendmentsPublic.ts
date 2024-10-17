interface ReloadResult {
    success: boolean;
    error?: string;
    html?: string;
    date?: string;
}

export class MotionMergeAmendmentsPublic {
    private $updateWidget: JQuery;
    private updateUrl: string;
    private $draftContent: JQuery;
    private $dateField: JQuery;
    private interval: number = null;

    constructor(public $widget: JQuery) {
        this.initUpdateWidget();
    }

    private showUpdated() {
        let $updated = this.$updateWidget.find('.updated');
        $updated.addClass('active');
        window.setTimeout(() => {
            $updated.removeClass('active');
        }, 2000);
    }

    private setContentEvents() {
        this.$draftContent.find(".appendHint").each((i, el) => {
            let $el = $(el),
                amendmentId = $el.data("amendment-id");
            if (amendmentId === undefined) {
                $el = $el.parents("[data-amendment-id]").first();
                amendmentId = $el.data("amendment-id");
            }
            if (amendmentId) {
                $el.addClass("amendmentAjaxTooltip");
                $el.data("initialized", "0");
                $el.data("url", $el.data("link") + "/ajax-diff");
                $el.data("placement", "bottom");
            }
        });
    }

    private reload(showMsg: boolean) {
        $.get(this.updateUrl, (data: ReloadResult) => {
            if (!data.success) {
                alert(data.error);
                return;
            }
            this.$dateField.text(data.date);
            this.$draftContent.html(data.html);
            this.setContentEvents();
            if (showMsg) {
                this.showUpdated();
            }
        });
    }

    private startInterval() {
        if (this.interval !== null) {
            return;
        }
        this.interval = window.setInterval(this.reload.bind(this, false), 5000);
    }

    private stopInterval() {
        if (this.interval === null) {
            return;
        }
        window.clearInterval(this.interval);
        this.interval = null;
    }

    private initUpdateWidget() {
        this.$updateWidget = this.$widget.find('.motionUpdateWidget');
        this.$draftContent = this.$widget.find('.draftContent');
        this.$dateField = this.$widget.find('.mergeDraftDate');
        this.updateUrl = this.$widget.data('reload-url');
        this.setContentEvents();

        let $toggle = this.$updateWidget.find('#autoUpdateToggle');
        if (localStorage) {
            let state = localStorage.getItem('merging-draft-auto-update');
            if (state !== null) {
                $toggle.prop('checked', (state == '1'));
            }
        }
        $toggle.on("change", () => {
            let active: boolean = $toggle.prop('checked');
            if (localStorage) {
                localStorage.setItem('merging-draft-auto-update', (active ? '1' : '0'));
            }
            if (active) {
                this.startInterval();
            } else {
                this.stopInterval();
            }
        }).trigger('change');

        this.$updateWidget.find('#updateBtn').on("click", this.reload.bind(this, true));
    }
}
