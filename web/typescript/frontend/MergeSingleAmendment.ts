import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;

class MergeSingleAmendment {
    private $collisionHolder: JQuery;
    private $form: JQuery;
    private $checkCollisions: JQuery;
    private $affectedParagraphs: JQuery;
    private $stepWizardHolder: JQuery;
    private $steps: {[no: string]: JQuery};
    private editors: AntragsgruenEditor[] = [];
    private collisionEditors: {[id: string]: AntragsgruenEditor} = {};
    private $otherStatsFields: JQuery;

    constructor() {
        this.$form = $("#amendmentMergeForm");
        this.$collisionHolder = $(".amendmentCollisionsHolder");
        this.$checkCollisions = $(".checkAmendmentCollisions");
        this.$affectedParagraphs = $(".affectedParagraphs > .paragraph");
        this.$otherStatsFields = $(".otherAmendmentStatus select");

        this.$stepWizardHolder = $("#MergeSingleWizard").find(".steps");
        this.$steps = {
            "1": this.$form.find("> .step_1"),
            "2": this.$form.find("> .step_2"),
            "3": this.$form.find("> .step_3")
        };

        this.$checkCollisions.on("click", (ev) => {
            ev.preventDefault();
            this.loadCollisions();
        });
        this.$steps["1"].find(".goto_2").on("click", (ev) => {
            ev.preventDefault();
            this.gotoStep("2");
        });
        this.$affectedParagraphs.each((i, el) => {
            this.initAffectedParagraph(el);
        });
        this.$form.on("submit", this.onSubmit.bind(this));

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

        $paragraph.find(".versionSelector input").on("change", () => {
            if ($paragraph.find(".versionSelector input:checked").val() == "modified") {
                $paragraph.removeClass("originalVersion").addClass("modifiedVersion");
            } else {
                $paragraph.addClass("originalVersion").removeClass("modifiedVersion");
            }
        }).trigger("change");
        $paragraph.find(".modifySelector input").on("change", () => {
            if ($paragraph.find(".modifySelector input").prop("checked")) {
                $paragraph.addClass("changed").removeClass("unchanged");
            } else {
                $paragraph.removeClass("changed").addClass("unchanged");
            }
        }).trigger("change");

        let key = $paragraph.data("section-id") + "_" + $paragraph.data("paragraph-no");
        if ($paragraph.find(".originalVersion.modifyText").length > 0) {
            this.editors[key + '_original'] = new AntragsgruenEditor($paragraph.find(".originalVersion.modifyText > .texteditor").attr("id"));
        }
        if ($paragraph.find(".modifiedVersion.modifyText").length > 0) {
            this.editors[key + '_modified'] = new AntragsgruenEditor($paragraph.find(".modifiedVersion.modifyText > .texteditor").attr("id"));
        }
    }

    private loadCollisions() {
        this.gotoStep("3");

        let url = this.$checkCollisions.data("url"),
            sections = {},
            otherAmendmentsStatus = {};

        this.$affectedParagraphs.each((i, el) => {
            let $el = $(el),
                version = $el.find(".versionSelector input:checked").val(),
                changed = $el.find(".modifySelector input").prop("checked"),
                sectionId = $el.data("section-id"),
                paragraphNo = $el.data("paragraph-no"),
                text;

            if (changed) {
                const srcId = sectionId + "_" + paragraphNo + (version === 'modified' ? '_modified' : '_original');
                let editor: editor = this.editors[srcId].getEditor(),
                    dataOrig = editor.getData();
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                    text = editor.getData();
                    editor.setData(dataOrig);
                } else {
                    text = editor.getData();
                }
            } else {
                if (version === 'modified') {
                    text = $el.data("modified-amendment");
                } else {
                    text = $el.data("unchanged-amendment");
                }
            }

            if (sections[$el.data("section-id")] === undefined) {
                sections[$el.data("section-id")] = {};
            }
            sections[$el.data("section-id")][$el.data("paragraph-no")] = text;
        });

        this.$otherStatsFields.each((i, el) => {
            let $input:JQuery = $(el);
            otherAmendmentsStatus[$input.data("amendment-id")] = $input.val();
        });

        $.post(url, {
            'newSections': sections,
            'otherAmendmentsStatus': otherAmendmentsStatus,
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        }, this.collisionsLoaded.bind(this));
    }

    private collisionsLoaded(html) {
        this.collisionEditors = {};
        this.$collisionHolder.html(html);
        let $texteditors = this.$collisionHolder.find(".amendmentOverrideBlock > .texteditor");

        if ($texteditors.length > 0) {
            $texteditors.each((i, el) => {
                let id = $(el).attr("id");
                this.collisionEditors[id] = new AntragsgruenEditor(id);
            });
        }
    }

    private onSubmit() {
        this.$affectedParagraphs.each((i, el) => {
            let $paragraph = $(el),
                version = $paragraph.find(".versionSelector input:checked").val(),
                changed = $paragraph.find(".modifySelector input").prop("checked"),
                $input = $paragraph.find(".modifiedText");

            if (changed) {
                let key = $paragraph.data("section-id") + "_" + $paragraph.data("paragraph-no") + (version === 'modified' ? '_modified' : '_original'),
                    editor: editor = this.editors[key].getEditor();
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                }
                $input.val(editor.getData());
            } else {
                if (version === 'modified') {
                    $input.val($paragraph.data("modified-amendment"));
                } else {
                    $input.val($paragraph.data("unchanged-amendment"));
                }
            }
        });
        for (let id in this.collisionEditors) {
            let editor: editor = this.collisionEditors[id].getEditor();
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
            }
            let html = editor.getData();

            $("#" + id).parents(".amendmentOverrideBlock").first().find("> textarea").val(html);
        }
    }
}

new MergeSingleAmendment();
