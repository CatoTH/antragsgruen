// @ts-check

import { AntragsgruenEditor } from "./AntragsgruenEditor.js";

export class AmendmentEditSinglePara {
    /** @type {JQuery} */
    $paragraphs;

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
            const $this = $(el),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).trigger('click');
            }
        });
    }

    /**
     * @param {Element} el
     */
    handleRegularTextField(el) {
        const $el = $(el),
            $textarea = $el.find(".texteditor");

        if ($el.hasClass("hidden")) {
            return;
        }

        const aedit = new AntragsgruenEditor($textarea.attr("id")),
            /** @type {CKEDITOR.editor} */
            editor = aedit.getEditor();

        $textarea.parents("form").on('submit', () => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof editor.plugins.lite !== 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
    }

    /**
     * @param {Element} para
     */
    revertChanges(para) {
        const $el = $(para),
            $para = $el.parents(".wysiwyg-textarea"),
            $textarea = $para.find(".texteditor"),
            id = $textarea.attr("id");

        $("#" + id).attr("contenteditable", "false");
        $textarea.html($para.data("original"));
        $para.removeClass("modified");
        this.setModifyable();
    }

    /**
     * @param {Element} para
     */
    startEditing(para) {
        const $para = $(para);
        if (!$para.hasClass('modifyable')) {
            return;
        }

        $para.addClass('modified');
        this.setModifyable();

        const $textarea = $para.find(".texteditor");
        /** @type {CKEDITOR.editor} */
        let editor;

        if (typeof CKEDITOR.instances[$textarea.attr("id")] !== "undefined") {
            editor = CKEDITOR.instances[$textarea.attr("id")];
        } else {
            const aedit = new AntragsgruenEditor($textarea.attr("id"));
            editor = aedit.getEditor();
        }

        $textarea.attr("contenteditable", "true");
        $textarea.parents("form").on('submit', () => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof editor.plugins.lite !== 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });
        $textarea.trigger("focus");
    }

    setModifyable() {
        const $modified = this.$paragraphs.filter(".modified");
        if ($modified.length === 0) {
            this.$paragraphs.addClass('modifyable');
        } else {
            this.$paragraphs.removeClass('modifyable');
            $('input[name=modifiedParagraphNo]').val($modified.data("paragraph-no"));
            $('input[name=modifiedSectionId]').val($modified.parents(".texteditorBox").data("section-id"));
        }
    }
}
