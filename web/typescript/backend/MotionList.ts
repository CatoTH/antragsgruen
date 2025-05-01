import { ResponsibilitySetter } from './ResponsibilitySetter';

export class MotionList {
    constructor() {
        this.initList();
        this.initExportRow();
        new ResponsibilitySetter($(".adminMotionTable"));
    }

    private initList() {
        $(".markAll").on("click", (ev) => {
            $(".adminMotionTable").find("input.selectbox").prop("checked", true);
            ev.preventDefault();
        });
        $(".markNone").on("click", (ev) => {
            $(".adminMotionTable").find("input.selectbox").prop("checked", false);
            ev.preventDefault();
        });
        $(".deleteMarkedBtn").on("click", this.onDeleteClicked.bind(this));

        let $select = $("#initiatorSelect"),
            initiatorValues = $select.data("values"),
            matcher = function findMatches(q, cb) {
                let matches, substrRegex;

                // an array that will be populated with substring matches
                matches = [];

                // regex used to determine if a string contains the substring `q`
                substrRegex = new RegExp(q, "i");

                // iterate through the pool of strings and for any string that
                // contains the substring `q`, add it to the `matches` array
                $.each(initiatorValues, function (i, str) {
                    if (substrRegex.test(str)) {
                        // the typeahead jQuery plugin expects suggestions to a
                        // JavaScript object, refer to typeahead docs for more info
                        matches.push(str);
                    }
                });

                cb(matches);
            };

        let options: Twitter.Typeahead.Options = {
            hint: true,
            highlight: true,
            minLength: 1
        };
        let datasets:Twitter.Typeahead.Dataset<any>[] = [{
            name: "supporter",
            source: matcher
        }];

        $select.typeahead<any>(options, datasets);

        $('.adminMotionTable').colResizable({
            'liveDrag': true,
            'postbackSafe': true,
            'minWidth': 30
        });
    }

    private initExportRow() {
        let $exportRow = $(".motionListExportRow");
        $exportRow.find("li.checkbox").on("click", function (ev) {
            ev.stopPropagation();
        });
        $exportRow.on("click", "a.disabled", function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        });
        $exportRow.find(".exportMotionDd, .exportAmendmentDd").each(function () {
            let $dd = $(this),
                recalcLinks = function () {
                    const inactive = ($dd.find("input[name=inactive]").prop("checked") ? 1 : 0);
                    const motionTypes = [];
                    $dd.find("input[name=motionType]:checked").each(function () {
                        motionTypes.push($(this).val());
                    });
                    $dd.find(".exportLink a").each(function () {
                        let link = $(this).data("href-tpl");

                        if (motionTypes.length === 0 && link.indexOf("MOTIONTYPES") !== -1) {
                            this.classList.add("disabled");
                        } else {
                            this.classList.remove("disabled");
                        }

                        link = link.replace("INACTIVE", inactive);
                        link = link.replace("MOTIONTYPES", motionTypes.join(","));
                        $(this).attr("href", link);
                    });
                };
            $dd.find("input[type=checkbox]").on("change", recalcLinks).trigger("change");
        });

        $exportRow.find('[data-toggle="tooltip"]').tooltip();
    }

    private onDeleteClicked(ev) {
        ev.preventDefault();
        let $button = $(ev.target),
            $form = $button.parents("form");
        bootbox.confirm(__t("std", "del_confirm"), function (result) {
            if (result) {
                let $input = $('<input type="hidden">').attr("name", $button.attr("name")).attr("value", $button.attr("value"));
                $form.append($input);
                $form.trigger("submit");
            }
        });
    }
}
