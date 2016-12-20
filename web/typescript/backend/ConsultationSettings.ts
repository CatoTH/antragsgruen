/// <reference path="../typings/fuelux/index.d.ts" />

declare let Sortable: any;

export class ConsultationSettings {
    constructor() {
        let $form = $("#consultationSettingsForm");

        $('.urlPathHolder .shower a').click(function (ev) {
            ev.preventDefault();
            $('.urlPathHolder .shower').addClass('hidden');
            $('.urlPathHolder .holder').removeClass('hidden');
        });

        $form.submit(function () {
            let items = $("#tagsList").pillbox('items'),
                tags = [],
                $node = $('<input type="hidden" name="tags">'),
                i;
            for (i = 0; i < items.length; i++) {
                if (typeof(items[i].id) == 'undefined') {
                    tags.push({"id": 0, "name": items[i].text});
                } else {
                    tags.push({"id": items[i].id, "name": items[i].text});
                }
            }
            $node.attr("value", JSON.stringify(tags));
            $form.append($node);
        });

        Sortable.create(document.getElementById("tagsListUl"), {draggable: '.pill'});

        let $adminsMayEdit = $("#adminsMayEdit"),
            $iniatorsMayEdit = $("#iniatorsMayEdit").parents("label").first().parent();
        $adminsMayEdit.change(function () {
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

        $("#singleMotionMode").change(function () {
            if ($(this).prop("checked")) {
                $("#forceMotionRow").removeClass("hidden");
            } else {
                $("#forceMotionRow").addClass("hidden");
            }
        }).change();

        console.log("!1");
    }
}


new ConsultationSettings();