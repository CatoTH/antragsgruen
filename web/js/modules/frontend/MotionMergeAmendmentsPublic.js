// @ts-check

/**
 * @typedef {Object} ReloadResult
 * @property {boolean} success
 * @property {string}  [error]
 * @property {string}  [html]
 * @property {string}  [date]
 */

export class MotionMergeAmendmentsPublic {
    /** @type {JQuery} */ $widget;
    /** @type {JQuery} */ $updateWidget;
    /** @type {string} */ updateUrl;
    /** @type {JQuery} */ $draftContent;
    /** @type {JQuery} */ $dateField;
    /** @type {number|null} */ interval = null;

    /**
     * @param {HTMLElement} element
     */
    constructor(element) {
        this.$widget = $(element);
        this.initUpdateWidget();
    }

    showUpdated() {
        const $updated = this.$updateWidget.find('.updated');
        $updated.addClass('active');
        window.setTimeout(() => {
            $updated.removeClass('active');
        }, 2000);
    }

    setContentEvents() {
        this.$draftContent.find(".appendHint").each((i, el) => {
            let $el = $(el);
            let amendmentId = $el.data("amendment-id");
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

    /**
     * @param {boolean} showMsg
     */
    reload(showMsg) {
        $.get(this.updateUrl, (/** @type {ReloadResult} */ data) => {
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

    startInterval() {
        if (this.interval !== null) {
            return;
        }
        this.interval = window.setInterval(this.reload.bind(this, false), 5000);
    }

    stopInterval() {
        if (this.interval === null) {
            return;
        }
        window.clearInterval(this.interval);
        this.interval = null;
    }

    initUpdateWidget() {
        this.$updateWidget = this.$widget.find('.motionUpdateWidget');
        this.$draftContent = this.$widget.find('.draftContent');
        this.$dateField    = this.$widget.find('.mergeDraftDate');
        this.updateUrl     = this.$widget.data('reload-url');
        this.setContentEvents();

        const $toggle = this.$updateWidget.find('#autoUpdateToggle');
        if (localStorage) {
            const state = localStorage.getItem('merging-draft-auto-update');
            if (state !== null) {
                $toggle.prop('checked', (state === '1'));
            }
        }
        $toggle.on("change", () => {
            const active = /** @type {boolean} */ ($toggle.prop('checked'));
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
