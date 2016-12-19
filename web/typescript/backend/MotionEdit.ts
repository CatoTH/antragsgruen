import {MotionSupporterEdit} from "./MotionSupporterEdit";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

class MotionEdit {
    constructor() {
        let lang = $("html").attr("lang");
        $("#motionDateCreationHolder").datetimepicker({
            locale: lang
        });
        $("#motionDateResolutionHolder").datetimepicker({
            locale: lang
        });
        $('#resolutionDateHolder').datetimepicker({
            locale: $('#resolutionDate').data('locale'),
            format: 'L'
        });
        $("#motionTextEditCaller").find("button").click(function () {
            $("#motionTextEditCaller").addClass("hidden");
            $("#motionTextEditHolder").removeClass("hidden");
            $(".wysiwyg-textarea").each(function () {
                let $holder = $(this),
                    $textarea = $holder.find(".texteditor"),
                    ckeditor = new AntragsgruenEditor($textarea.attr("id")),
                    editor = ckeditor.getEditor();

                $textarea.parents("form").submit(function () {
                    $textarea.parent().find("textarea").val(editor.getData());
                });
            });
            $("#motionUpdateForm").append("<input type='hidden' name='edittext' value='1'>");
        });

        $(".motionDeleteForm").submit(function (ev, data) {
            if (data && typeof(data.confirmed) && data.confirmed === true) {
                return;
            }
            let $form = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "delMotionConfirm"), function (result) {
                if (result) {
                    $form.trigger("submit", {'confirmed': true});
                }
            });
        });

        new MotionSupporterEdit($("#motionSupporterHolder"));
    }
}

new MotionEdit();
