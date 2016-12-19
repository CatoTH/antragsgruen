import "./MotionSupporterEdit";
import {MotionSupporterEdit} from "./MotionSupporterEdit";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;
import {AmendmentEditSinglePara} from "../shared/AmendmentEditSinglePara";

class AmendmentEdit {
    private lang: string;

    private $editTextCaller: JQuery;

    private textEditCalledMultiPara() {
        $(".wysiwyg-textarea").each(function () {
            let $holder = $(this),
                $textarea = $holder.find(".texteditor");

            let editor: AntragsgruenEditor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor: editor = editor.getEditor();

            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof(ckeditor.plugins.lite) != 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });
        });
    }

    private textEditCalled() {
        this.$editTextCaller.addClass("hidden");
        $("#amendmentTextEditHolder").removeClass("hidden");
        if (this.$editTextCaller.data("multiple-paragraphs")) {
            this.textEditCalledMultiPara();
        } else {
            new AmendmentEditSinglePara();
        }
        $("#amendmentUpdateForm").append("<input type='hidden' name='edittext' value='1'>");
    };

    constructor() {
        this.lang = $("html").attr("lang");
        this.$editTextCaller = $("#amendmentTextEditCaller");

        $("#amendmentDateCreationHolder").datetimepicker({
            locale: this.lang
        });
        $("#amendmentDateResolutionHolder").datetimepicker({
            locale: this.lang
        });


        this.$editTextCaller.find("button").click(this.textEditCalled.bind(this));

        $(".amendmentDeleteForm").submit(function (ev, data) {
            if (data && typeof(data.confirmed) && data.confirmed === true) {
                return;
            }
            let $form = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "delAmendmentConfirm"), function (result) {
                if (result) {
                    $form.trigger("submit", {'confirmed': true});
                }
            });
        });

        new MotionSupporterEdit($("#motionSupporterHolder"));
    }
}

new AmendmentEdit();
