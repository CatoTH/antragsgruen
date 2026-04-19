// @ts-check

/**
 * @typedef {Object} ReloadResult
 * @property {boolean} success
 * @property {string}  [error]
 * @property {string}  [html]
 * @property {string}  [date]
 */

export class ProposedProcedureOverview {
    /** @type {JQuery} */ $widget;
    /** @type {JQuery} */ $updateWidget;
    /** @type {string} */ updateUrl;
    /** @type {JQuery} */ $proposalList;
    /** @type {JQuery} */ $dateField;
    /** @type {number|null} */ interval = null;

    /**
     * @param {HTMLElement} element
     */
    constructor(element) {
        this.$widget = $(element);
        this.initUpdateWidget();
    }

    reload() {
        $.get(this.updateUrl, (/** @type {ReloadResult} */ data) => {
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

    startInterval() {
        if (this.interval !== null) {
            return;
        }
        this.interval = window.setInterval(this.reload.bind(this), 10000);
    }

    stopInterval() {
        if (this.interval === null) {
            return;
        }
        window.clearInterval(this.interval);
        this.interval = null;
    }

    initUpdateWidget() {
        this.$updateWidget = this.$widget.find('.autoUpdateWidget');
        this.$proposalList = this.$widget.find('.reloadContent');
        this.$dateField    = this.$widget.find('.currentDate .date');
        this.updateUrl     = this.$widget.data('reload-url');

        const $toggle = this.$updateWidget.find('#autoUpdateToggle');
        $toggle.on("change", () => {
            const active = /** @type {boolean} */ ($toggle.prop('checked'));
            if (active) {
                this.reload();
                this.startInterval();
            } else {
                this.stopInterval();
            }
        }).trigger('change');
    }
}
