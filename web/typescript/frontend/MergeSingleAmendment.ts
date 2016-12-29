import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;

class MergeSingleAmendment {
    private $collissionHolder: JQuery;
    private $form: JQuery;
    private $checkCollissions: JQuery;
    private $affectedParagraphs: JQuery;
    private $stepWizardHolder: JQuery;
    private $steps: {[no: string]: JQuery};
    private editors: AntragsgruenEditor[] = [];
    private collissionEditors: {[id: string]: AntragsgruenEditor} = {};
    private $otherStatsFields: JQuery;

    constructor() {
        this.$form = $("#amendmentMergeForm");
        this.$collissionHolder = $(".amendmentCollissionsHolder");
        this.$checkCollissions = $(".checkAmendmentCollissions");
        this.$affectedParagraphs = $(".affectedParagraphs > .paragraph");
        this.$otherStatsFields = $(".otherAmendmentStatus input");

        this.$stepWizardHolder = $("#MergeSingleWizard").find(".steps");
        this.$steps = {
            "1": this.$form.find("> .step_1"),
            "2": this.$form.find("> .step_2"),
            "3": this.$form.find("> .step_3")
        };

        this.$checkCollissions.click((ev) => {
            ev.preventDefault();
            this.loadCollissions();
        });
        this.$steps["1"].find(".goto_2").click((ev) => {
            ev.preventDefault();
            this.gotoStep("2");
        });
        this.$affectedParagraphs.each((i, el) => {
            this.initAffectedParagraph(el);
        });
        this.$form.submit(this.onSubmit.bind(this));

        this.gotoStep("1");
    }

    private gotoStep(no: string) {
        for (let n in this.$steps) {
            if (n == no) {
                this.$steps[n].removeClass("hidden");
            } else {
                this.$steps[n].addClass("hidden");
            }
        }
        this.$stepWizardHolder.children().removeClass("active");
        this.$stepWizardHolder.find(".goto_step" + no).addClass("active");
        this.$stepWizardHolder.scrollintoview({top_offset: -50});
    }

    private initAffectedParagraph(el) {
        let $paragraph = $(el);

        $paragraph.find(".modifySelector input").change(function () {
            if ($paragraph.find(".modifySelector input:checked").val() == "1") {
                $paragraph.addClass("modified").removeClass("unmodified");
            } else {
                $paragraph.removeClass("modified").addClass("unmodified");
            }
        }).trigger("change");

        let key = $paragraph.data("section-id") + "_" + $paragraph.data("paragraph-no");
        this.editors[key] = new AntragsgruenEditor($paragraph.find(".affectedBlock > .texteditor").attr("id"));
    }

    private loadCollissions() {
        this.gotoStep("3");

        let url = this.$checkCollissions.data("url"),
            sections = {},
            otherAmendmentsStatus = {};

        this.$affectedParagraphs.each((i, el) => {
            let $el = $(el),
                modified = $el.find(".modifySelector input:checked").val(),
                sectionId = $el.data("section-id"),
                paragraphNo = $el.data("paragraph-no"),
                text;

            if (modified) {
                let editor: editor = this.editors[sectionId + "_" + paragraphNo].getEditor(),
                    dataOrig = editor.getData();
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                    text = editor.getData();
                    editor.setData(dataOrig);
                } else {
                    text = editor.getData();
                }
            } else {
                text = $el.data("unchanged-amendment");
            }

            if (sections[$el.data("section-id")] === undefined) {
                sections[$el.data("section-id")] = {};
            }
            sections[$el.data("section-id")][$el.data("paragraph-no")] = text;
        });

        this.$otherStatsFields.each((i, el) => {
            let $input:JQuery = $(el);
            otherAmendmentsStatus[$input.parents(".selectlist").data("amendment-id")] = $input.val();
        });

        $.post(url, {
            'newSections': sections,
            'otherAmendmentsStatus': otherAmendmentsStatus,
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        }, this.collissionsLoaded.bind(this));
    }

    private collissionsLoaded(html) {
        this.collissionEditors = {};
        this.$collissionHolder.html(html);
        let $texteditors = this.$collissionHolder.find(".amendmentOverrideBlock > .texteditor");

        if ($texteditors.length > 0) {
            $texteditors.each((i, el) => {
                let id = $(el).attr("id");
                this.collissionEditors[id] = new AntragsgruenEditor(id);
            });
        }
    }

    private onSubmit() {
        this.$affectedParagraphs.each((i, el) => {
            let $paragraph = $(el),
                $input = $paragraph.find(".modifiedText");

            if ($paragraph.find(".modifySelector input:checked").val() == "1") {
                let key = $paragraph.data("section-id") + "_" + $paragraph.data("paragraph-no"),
                    editor: editor = this.editors[key].getEditor();
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                }
                $input.val(editor.getData());
            } else {
                $input.val($paragraph.data("unchanged-amendment"));
            }
        });
        for (let id in this.collissionEditors) {
            let editor: editor = this.collissionEditors[id].getEditor();
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
            }
            let html = editor.getData();

            $("#" + id).parents(".amendmentOverrideBlock").first().find("> textarea").val(html);
        }
    }
}

new MergeSingleAmendment();
