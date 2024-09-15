import {DraftSavingEngine} from "../shared/DraftSavingEngine";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import SubmitEvent = JQuery.SubmitEvent;

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

        $form.on("submit", (ev) => {
            this.onSubmitCheck(ev, $form)
        });
    }

    private onSubmitCheck(ev: SubmitEvent, $form: JQuery): void {
        let error: boolean = false;
        if (this.checkMultipleTagsError()) {
            error = true;
        }

        if (error) {
            ev.preventDefault();
        } else {
            const encouragement = this.getEncouragedFieldError($form);
            if (encouragement) {
                ev.preventDefault();

                bootbox.dialog({
                    title: encouragement.field.data('encouraged-title'),
                    message: encouragement.field.data('encouraged-str'),
                    buttons: {
                        submit: {
                            label: encouragement.field.data('encouraged-submit'),
                            callback: function() {
                                encouragement.field.data("skip-encouraged-fields", true);
                                $form.append('<input type="hidden" name="save" value="1">');
                                $form.trigger("submit");
                            }
                        },
                        fill: {
                            label: encouragement.field.data('encouraged-fill'),
                            callback: function() {
                                encouragement.field.scrollintoview({top_offset: -50});
                            }
                        }
                    }
                });
            } else {
                $(window).off("beforeunload", MotionEditForm.onLeavePage);
            }
        }
    }

    private getEncouragedFieldError($form: JQuery): {field: JQuery, error: string}|null {
        let result: {field: JQuery, error: string}|null = null;
        $form.find('.wysiwyg-textarea').each(function() {
            const $field = $(this),
                $label = $field.find("label.encouraged");

            if ($label.length === 0) {
                return;
            }

            const text = $field.find("textarea").val().trim();
            if (text && text !== '<p></p>' && text !== '&nbsp;') {
                return;
            }

            if ($label.data("skip-encouraged-fields")) {
                return;
            }

            result = {field: $label, error: $label.data("encouraged-str")}
        });

        return result;
    }

    private checkMultipleTagsError(): boolean {
        let $group: JQuery = this.$form.find('.multipleTagsGroup');
        if ($group.length === 0) {
            return false;
        }

        if (this.$form.find('.multipleTagsGroup input[type=checkbox]').length) {
            // Checkboxes: multiple tags are allowed, but also none
            $group.removeClass('has-error');
            return false;
        }

        // From here on: radios
        if ($group.find('input:checked').length > 0) {
            $group.removeClass('has-error');
            return false;
        } else {
            $group.addClass('has-error');
            $group.scrollintoview({top_offset: -50});
            return true;
        }
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    private initWysiwyg(i, el) {
        let $holder = $(el),
            $textarea = $holder.find(".texteditor"),
            editor = new AntragsgruenEditor($textarea.attr("id"));

        $textarea.parents("form").on("submit", () => {
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
                let currLen = ($input.val() as string).length;
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
