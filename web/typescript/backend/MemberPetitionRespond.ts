import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

export class MemberPetitionRespond {
    constructor(private $widget: JQuery) {
        this.initMotionTextEdit();
    }

    private initMotionTextEdit() {
        $(".wysiwyg-textarea").each(function () {
            let $holder = $(this),
                $textarea = $holder.find(".texteditor"),
                ckeditor = new AntragsgruenEditor($textarea.attr("id")),
                editor = ckeditor.getEditor();

            $textarea.parents("form").on("submit", () => {
                $textarea.parent().find("textarea").val(editor.getData());
            });
        });
    }
}