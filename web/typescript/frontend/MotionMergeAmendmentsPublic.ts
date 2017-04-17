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

    private reload(showMsg: boolean) {
        $.get(this.updateUrl, (data: ReloadResult) => {
            if (!data.success) {
                alert(data.error);
                return;
            }
            this.$draftContent.html(data.html);
            this.$dateField.text(data.date);
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

        let $toggle = this.$updateWidget.find('#autoUpdateToggle');
        if (localStorage) {
            let state = localStorage.getItem('merging-draft-auto-update');
            if (state !== null) {
                $toggle.prop('checked', (state == '1'));
            }
        }
        $toggle.change(() => {
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

        this.$updateWidget.find('#updateBtn').click(this.reload.bind(this, true));
    }
}
