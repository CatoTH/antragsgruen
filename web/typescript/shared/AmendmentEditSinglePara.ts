import {AntragsgruenEditor} from "./AntragsgruenEditor";

export class AmendmentEditSinglePara {
    private $paragraphs: JQuery;

    constructor() {
        this.$paragraphs = $(".wysiwyg-textarea.single-paragraph");

        this.$paragraphs.on('click', (ev) => {
            this.startEditing(ev.delegateTarget);
        });
        this.$paragraphs.find(".modifiedActions .revert").on('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            this.revertChanges(ev.delegateTarget);
        });
        this.setModifyable();

        // Amendment Reason
        $(".wysiwyg-textarea").filter(":not(.single-paragraph)").each((i, el) => {
            this.handleRegularTextField(el);
        });

        $(".texteditorBox").each((i, el) => {
            let $this = $(el),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).trigger('click');
            }
        });
    }

    private handleRegularTextField(el: Element) {
        let $el = $(el),
            $textarea = $el.find(".texteditor");

        if ($el.hasClass("hidden")) {
            return;
        }
        let aedit = new AntragsgruenEditor($textarea.attr("id")),
            editor = aedit.getEditor();
        $textarea.parents("form").on('submit', () => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
    }

    private revertChanges(para: Element) {
        let $el = $(para),
            $para = $el.parents(".wysiwyg-textarea"),
            $textarea = $para.find(".texteditor"),
            id = $textarea.attr("id");
        $("#" + id).attr("contenteditable", "false");
        $textarea.html($para.data("original"));
        $para.removeClass("modified");
        this.setModifyable();
    }

    private startEditing(para: Element) {
        let $para = $(para);
        if (!$para.hasClass('modifyable')) {
            return;
        }

        $para.addClass('modified');
        this.setModifyable();

        let $textarea = $para.find(".texteditor"),
            editor;
        if (typeof(CKEDITOR.instances[$textarea.attr("id")]) !== "undefined") {
            editor = CKEDITOR.instances[$textarea.attr("id")];
        } else {
            let aedit = new AntragsgruenEditor($textarea.attr("id"));
            editor = aedit.getEditor();
        }
        $textarea.attr("contenteditable", "true");
        $textarea.parents("form").on('submit', () => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
        $textarea.trigger("focus");;
    }

    private setModifyable() {
        let $modified = this.$paragraphs.filter(".modified");
        if ($modified.length == 0) {
            this.$paragraphs.addClass('modifyable');
        } else {
            this.$paragraphs.removeClass('modifyable');
            $('input[name=modifiedParagraphNo]').val($modified.data("paragraph-no"));
            $('input[name=modifiedSectionId]').val($modified.parents(".texteditorBox").data("section-id"));
        }
    }
}
