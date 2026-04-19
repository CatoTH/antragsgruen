// @ts-check

export class ProposedProcedureExport {
    /** @type {JQuery} */ $widget;

    constructor(element) {
        this.$widget = $(element);
        this.initExportRow();
    }

    recalcLinks() {
        let withdrawn = (this.$widget.find("input[name=comments]").prop("checked") ? 1 : 0);
        let onlyPublic = (this.$widget.find("input[name=onlypublic]").prop("checked") ? 1 : 0);
        this.$widget.find("a.odsLink").each((ev, el) => {
            let link = $(el).data("href-tpl");
            link = link.replace("COMMENTS", withdrawn);
            link = link.replace("ONLYPUBLIC", onlyPublic);
            $(el).attr("href", link);
        });
    }

    initExportRow() {
        this.$widget.find("li.checkbox").on("click", function (ev) {
            ev.stopPropagation();
        });
        this.$widget.find("input[type=checkbox]").on("change", this.recalcLinks.bind(this)).trigger("change");
    }
}
