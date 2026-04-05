// @ts-check

export class MergeInit {
    /** @type { String } */ exportLinkTpl;
    /** @type { JQuery } */ $checkboxes;
    /** @type { JQuery } */ $allCheckbox;
    /** @type { JQuery } */ $widget;

    constructor(element) {
        this.$widget = $(element);
        this.$checkboxes = this.$widget.find('.toMergeAmendments .selectSingle');
        this.$allCheckbox = this.$widget.find('.selectAll');
        this.initExportBtn();
        this.initAllCheckbox();
    }

    recalcExportBtn() {
        let ids = [];
        this.$checkboxes.filter(":checked").each((idx, el) => {
            ids.push(parseInt(el.getAttribute('name').split('[')[1]));
        });
        let link = this.exportLinkTpl.replace(/IDS/, ids.join(','));
        this.$widget.find('.exportHolder a').attr('href', link);
    }

    initExportBtn() {
        this.exportLinkTpl = this.$widget.find('.exportHolder a').attr('href');
        if (!this.exportLinkTpl) {
            return;
        }

        this.$widget.on('change', '.toMergeAmendments input[type=checkbox]', () => {
            this.recalcExportBtn();
        });
        this.recalcExportBtn();
    }

    recalcAllCheckbox() {
        let allSelected = true;
        let noneSelected = true;
        this.$checkboxes.each((idx, el) => {
            if ($(el).prop("checked")) {
                noneSelected = false;
            } else {
                allSelected = false;
            }
        });
        if (noneSelected) {
            this.$allCheckbox.prop("checked", false).prop("indeterminate", false);
        } else if (allSelected) {
            this.$allCheckbox.prop("checked", true).prop("indeterminate", false);
        } else {
            this.$allCheckbox.prop("indeterminate", true);
        }
    }

    initAllCheckbox() {
        this.recalcAllCheckbox();
        this.$allCheckbox.on("change", () => {
            this.$checkboxes.prop("checked", this.$allCheckbox.prop("checked"));
        });
        this.$checkboxes.on("change", () => {
            this.recalcAllCheckbox();
        });
    }
}
