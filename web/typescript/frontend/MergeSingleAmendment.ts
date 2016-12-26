import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;

class MergeSingleAmendment {
    private $collissionHolder: JQuery;
    private $form: JQuery;
    private $checkCollissions: JQuery;
    private $affectedParagraphs: JQuery;
    private editors: AntragsgruenEditor[] = [];

    constructor() {
        this.$form = $("#amendmentMergeForm");
        this.$collissionHolder = $(".amendmentCollissionsHolder");
        this.$checkCollissions = $(".checkAmendmentCollissions");
        this.$affectedParagraphs = $(".affectedParagraphs > .paragraph");

        this.$checkCollissions.click((ev) => {
            ev.preventDefault();
            this.loadCollissions();
        });
        this.$affectedParagraphs.each((i, el) => {
            this.initAffectedParagraph(el);
        });
        this.$form.submit(this.onSubmit.bind(this));
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
        let url = this.$checkCollissions.data("url"),
            sections = {};

        this.$affectedParagraphs.each((i, el) => {
            let $el = $(el),
                modified = $el.find(".modifySelector input:checked").val(),
                sectionId = $el.data("section-id"),
                paragraphNo = $el.data("paragraph-no"),
                text;

            if (modified) {
                console.log(sectionId + "_" + paragraphNo);
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

        /*
         $("#motionTextEditHolder").children().each(function () {
         let $this = $(this);
         if ($this.hasClass("wysiwyg-textarea")) {
         let sectionId = $this.attr("id").replace("section_holder_", "");
         sections[sectionId] = CKEDITOR.instances[$this.find(".texteditor").attr("id")].getData();
         }
         });
         */
        $.post(url, {
            'newSections': sections,
            '_csrf': this.$form.find('> input[name=_csrf]').val()
        }, this.collissionsLoaded.bind(this));
    }

    private collissionsLoaded(html) {
        console.log(html);

        this.$collissionHolder.html(html);
        let $texteditors = this.$collissionHolder.find(".amendmentOverrideBlock > .texteditor");

        if ($texteditors.length > 0) {
            $texteditors.each((i, el) => {
                new AntragsgruenEditor($(el).attr("id"));
            });
            this.$collissionHolder.scrollintoview({top_offset: -50});
        }

        this.$checkCollissions.hide();
        $(".saveholder .save").prop("disabled", false).show();
    }

    private onSubmit() {
        this.$affectedParagraphs.each((i, el) => {
            let $paragraph = $(el),
                $input = $paragraph.find(".modifiedText");

            if ($paragraph.find(".modifySelector input:checked").val() == "1") {
                let key = $paragraph.data("section-id") + "_" + $paragraph.data("paragraph-no");
                $input.val(this.editors[key].getEditor().getData());
            } else {
                $input.val($paragraph.data("unchanged-amendment"));
            }
        });
    }
}

new MergeSingleAmendment();
