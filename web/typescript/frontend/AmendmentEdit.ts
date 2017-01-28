import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

export class AmendmentEdit {
    private lang: string;
    private $spmParagraphs: JQuery;

    constructor(private $form: JQuery) {
        let multiParagraphMode = $form.data("multi-paragraph-mode");
        if (typeof(multiParagraphMode) == "undefined") {
            throw "data-multi-paragraph-mode needs to be set";
        }

        this.lang = $("html").attr('lang');
        this.initEditorialOpener();

        if (multiParagraphMode) {
            this.initMultiParagraphMode();
        } else {
            this.spmInit();
        }
    }

    private initEditorialOpener() {
        let $opener = $(".editorialChange .opener");

        $(".input-group.date").datetimepicker({
            locale: this.lang,
            format: 'L'
        });
        $opener.click((ev) => {
            ev.preventDefault();
            let $holder = $(".editorialChange"),
                $textarea = $holder.find(".texteditor");
            $(ev.target).addClass("hidden");
            $("#section_holder_editorial").removeClass("hidden");
            let editor: AntragsgruenEditor = new AntragsgruenEditor("amendmentEditorial_wysiwyg");
            $textarea.parents("form").submit(() => {
                $textarea.parent().find("textarea.raw").val(editor.getEditor().getData());
            });
        });

        if ($("#amendmentEditorial").val() != '') {
            $opener.click();
        }
    }

    /* Multi paragraph mode */

    private initMultiParagraphMode() {
        $(".wysiwyg-textarea:not(#section_holder_editorial)").each((i, el) => {
            let $holder = $(el),
                $textarea = $holder.find(".texteditor");

            let editor: AntragsgruenEditor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor: editor = editor.getEditor();

            $textarea.parents("form").submit(() => {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof(ckeditor.plugins.lite) != 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });
        });
    }

    /* Single paragraph mode */

    private spmSetModifyable() {
        let $modified = this.$spmParagraphs.filter(".modified");
        if ($modified.length == 0) {
            this.$spmParagraphs.addClass('modifyable');
        } else {
            this.$spmParagraphs.removeClass('modifyable');
            $('input[name=modifiedParagraphNo]').val($modified.data("paragraph-no"));
            $('input[name=modifiedSectionId]').val($modified.parents(".texteditorBox").data("section-id"));
        }
    }

    private spmOnParaClick(ev) {
        let $para = $(ev.currentTarget);
        if (!$para.hasClass('modifyable')) {
            return;
        }
        $para.addClass('modified');
        this.spmSetModifyable();

        let $textarea = $para.find(".texteditor"),
            editor;
        if (typeof(CKEDITOR.instances[$textarea.attr("id")]) !== "undefined") {
            editor = CKEDITOR.instances[$textarea.attr("id")];
        } else {
            editor = (new AntragsgruenEditor($textarea.attr("id"))).getEditor();
        }
        $textarea.attr("contenteditable", "true");
        $textarea.parents("form").submit(() => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
        $textarea.focus();
    }

    private spmRevert(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        let $para = $(ev.target).parents(".wysiwyg-textarea"),
            $textarea = $para.find(".texteditor"),
            id = $textarea.attr("id");
        $("#" + id).attr("contenteditable", "false");
        $textarea.html($para.data("original"));
        $para.removeClass("modified");
        this.spmSetModifyable();
    }

    private spmInitNonSingleParas(i, el) {
        let $holder = $(el),
            $textarea = $holder.find(".texteditor");
        if ($holder.hasClass("hidden")) {
            return;
        }
        let editor: editor = (new AntragsgruenEditor($textarea.attr("id"))).getEditor();
        $textarea.parents("form").submit(() => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
    }

    private spmInit() {
        this.$spmParagraphs = $(".wysiwyg-textarea.single-paragraph");
        this.$spmParagraphs.click(this.spmOnParaClick.bind(this));
        this.$spmParagraphs.find(".modifiedActions .revert").click(this.spmRevert.bind(this));
        this.spmSetModifyable();

        // Amendment Reason
        $(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(this.spmInitNonSingleParas.bind(this));

        $(".texteditorBox").each((i, el) => {
            let $this = $(el),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).click();
            }
        });
    }
}
