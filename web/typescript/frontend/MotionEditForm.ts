import {DraftSavingEngine} from "../shared/DraftSavingEngine";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

export class MotionEditForm {
    private hasChanged: boolean = false;

    constructor(private $form: JQuery) {
        $(".input-group.date").datetimepicker({
            locale: $("html").attr('lang'),
            format: 'L'
        });

        $(".wysiwyg-textarea").each(this.initWysiwyg.bind(this));
        $(".form-group.plain-text").each(this.initPlainTextFormGroup.bind(this));

        let $draftHint = $("#draftHint"),
            draftMotionType = $draftHint.data("motion-type"),
            draftMotionId = $draftHint.data("motion-id");

        new DraftSavingEngine($form, $draftHint, "motion_" + draftMotionType + "_" + draftMotionId);

        $form.on("submit", () => {
            $(window).off("beforeunload", MotionEditForm.onLeavePage);
        });
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    private initWysiwyg(i, el) {
        let $holder = $(el),
            $textarea = $holder.find(".texteditor"),
            editor = new AntragsgruenEditor($textarea.attr("id"));

        $textarea.parents("form").submit(function () {
            $textarea.parent().find("textarea").val(editor.getEditor().getData());
        });
        editor.getEditor().on('change', () => {
            if (!this.hasChanged) {
                this.hasChanged = true;
                if (!$("body").hasClass('testing')) {
                    $(window).on("beforeunload", MotionEditForm.onLeavePage);
                }
            }
        });
    }

    private initPlainTextFormGroup(i, el) {
        let $fieldset = $(el),
            $input = $fieldset.find("input.form-control");
        if ($fieldset.data("max-len") != 0) {
            let maxLen = $fieldset.data("max-len"),
                maxLenSoft = false,
                $warning = $fieldset.find('.maxLenTooLong'),
                $submit = $fieldset.parents("form").first().find("button[type=submit]"),
                $currCounter = $fieldset.find(".maxLenHint .counter");
            if (maxLen < 0) {
                maxLenSoft = true;
                maxLen = -1 * maxLen;
            }

            $input.on('keyup change', () => {
                let currLen = $input.val().length;
                $currCounter.text(currLen);
                if (currLen > maxLen) {
                    $warning.removeClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", true);
                    }
                } else {
                    $warning.addClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", false);
                    }
                }
            }).trigger('change');
        }
    }
}
