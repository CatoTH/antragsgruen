/// <reference path="../typings/colresizable/index.d.ts" />

class MotionList {
    constructor() {
        this.initList();
        this.initExportRow();
    }

    private initList() {
        $(".markAll").click(function (ev) {
            $(".adminMotionTable").find("input.selectbox").prop("checked", true);
            ev.preventDefault();
        });
        $(".markNone").click(function (ev) {
            $(".adminMotionTable").find("input.selectbox").prop("checked", false);
            ev.preventDefault();
        });

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
                        matches.push({value: str});
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
        $exportRow.find(".exportMotionDd, .exportAmendmentDd").each(function () {
            let $dd = $(this),
                recalcLinks = function () {
                    let withdrawn = ($dd.find("input[name=withdrawn]").prop("checked") ? 1 : 0);
                    $dd.find(".exportLink a").each(function () {
                        let link = $(this).data("href-tpl");
                        link = link.replace("WITHDRAWN", withdrawn);
                        $(this).attr("href", link);
                    });
                };
            $dd.find("input[type=checkbox]").change(recalcLinks).trigger("change");
        });
    }
}

new MotionList();
