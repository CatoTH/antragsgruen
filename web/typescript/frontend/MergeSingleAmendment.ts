import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

class MergeSingleAmendment {
    $collissionHolder: JQuery;
    $form: JQuery;

    constructor() {
        this.$form = $("#amendmentMergeForm");
        this.$collissionHolder = $(".amendmentCollissionsHolder");

        $(".checkAmendmentCollissions").click((ev) => {
            ev.preventDefault();
            this.loadCollissions();
        });
        $(".affectedParagraphs .paragraph").each(function () {
            let $paragraph = $(this);
            $paragraph.find(".modifySelector input").change(function () {
                if ($paragraph.find(".modifySelector input:checked").val() == "1") {
                    $paragraph.addClass("modified").removeClass("unmodified");
                } else {
                    $paragraph.removeClass("modified").addClass("unmodified");
                }
            });
        });
        $(".affectedBlock > .texteditor").each(function () {
            new AntragsgruenEditor($(this).attr("id"));
        });
    }

    private loadCollissions() {
        let url = $(".checkAmendmentCollissions").data("url"),
            sections = [];

        $("#motionTextEditHolder").children().each(function () {
            let $this = $(this);
            if ($this.hasClass("wysiwyg-textarea")) {
                let sectionId = $this.attr("id").replace("section_holder_", "");
                sections[sectionId] = CKEDITOR.instances[$this.find(".texteditor").attr("id")].getData();
            }
        });
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
                $(".amendmentCollissionsHolder").scrollintoview({top_offset: -50});
            }

            $(".checkAmendmentCollissions").hide();
            $(".saveholder .save").prop("disabled", false).show();
    }
}

new MergeSingleAmendment();
