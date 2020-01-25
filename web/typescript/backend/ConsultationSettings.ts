/// <reference path="../typings/fuelux/index.d.ts" />

declare let Sortable: any;

export class ConsultationSettings {
    constructor(private $form: JQuery) {
        this.initUrlPath();
        this.initTags();
        this.initOrganisations();
        this.initAdminMayEdit();
        this.initSingleMotionMode();

        $('[data-toggle="tooltip"]').tooltip();
    }

    private initUrlPath() {
        $('.urlPathHolder .shower a').on("click", (ev) => {
            ev.preventDefault();
            $('.urlPathHolder .shower').addClass('hidden');
            $('.urlPathHolder .holder').removeClass('hidden');
        });
    }

    private initSingleMotionMode() {
        $("#singleMotionMode").on("change", function () {
            if ($(this).prop("checked")) {
                $("#forceMotionRow").removeClass("hidden");
            } else {
                $("#forceMotionRow").addClass("hidden");
            }
        }).trigger("change");
    }

    private initAdminMayEdit() {
        let $adminsMayEdit = $("#adminsMayEdit"),
            $iniatorsMayEdit = $("#iniatorsMayEdit").parents("label").first().parent();
        $adminsMayEdit.on("change", function () {
            if ($(this).prop("checked")) {
                $iniatorsMayEdit.removeClass("hidden");
            } else {
                let confirmMessage = __t("admin", "adminMayEditConfirm");
                bootbox.confirm(confirmMessage, function (result) {
                    if (result) {
                        $iniatorsMayEdit.addClass("hidden");
                        $iniatorsMayEdit.find("input").prop("checked", false);
                    } else {
                        $adminsMayEdit.prop("checked", true);
                    }
                });
            }
        });
        if (!$adminsMayEdit.prop("checked")) $iniatorsMayEdit.addClass("hidden");
    }

    private htmlEntityDecode(html: string): string {
        const el: HTMLElement = document.createElement('div');
        el.innerHTML = html;
        return el.innerText;
    }

    private initTags() {
        this.$form.on("submit", () => {
            let items = $("#tagsList").pillbox('items'),
                tags = [],
                $node = $('<input type="hidden" name="tags">'),
                i;
            for (i = 0; i < items.length; i++) {
                const text = this.htmlEntityDecode(items[i].text);
                if (typeof (items[i].id) == 'undefined') {
                    tags.push({"id": 0, "name": text});
                } else {
                    tags.push({"id": items[i].id, "name": text});
                }
            }
            $node.attr("value", JSON.stringify(tags));
            this.$form.append($node);
        });

        Sortable.create(document.getElementById("tagsListUl"), {draggable: '.pill'});
    }

    private initOrganisations() {
        this.$form.on("submit", () => {
            let items = $("#organisationList").pillbox('items'),
                organisations = [],
                $node = $('<input type="hidden" name="organisations">'),
                i;
            for (i = 0; i < items.length; i++) {
                organisations.push(this.htmlEntityDecode(items[i].text));
            }
            $node.attr("value", JSON.stringify(organisations));
            this.$form.append($node);
        });

        Sortable.create(document.getElementById("organisationListUl"), {draggable: '.pill'});
    }
}
