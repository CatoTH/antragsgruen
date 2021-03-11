interface ReloadResult {
    success: boolean;
    error?: string;
    html?: string;
    date?: string;
}

export class ProposedProcedureOverview {
    private $updateWidget: JQuery;
    private updateUrl: string;
    private $proposalList: JQuery;
    private $dateField: JQuery;
    private interval: number = null;

    constructor(public $widget: JQuery) {
        this.initUpdateWidget();
    }

    private reload() {
        $.get(this.updateUrl, (data: ReloadResult) => {
            if (!data.success) {
                if (data.error) {
                    alert(data.error);
                }
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
        this.interval = window.setInterval(this.reload.bind(this), 10000);
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
        $toggle.on("change", () => {
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
