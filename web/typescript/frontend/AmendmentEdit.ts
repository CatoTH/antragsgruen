import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import {DraftSavingEngine} from "../shared/DraftSavingEngine";

// Keep in sync with ConsultationMotionType.php
const AMEND_PARAGRAPHS_MULTIPLE = 1;
const AMEND_PARAGRAPHS_SINGLE_PARAGRAPH = 0;
const AMEND_PARAGRAPHS_SINGLE_CHANGE = -1;

export class AmendmentEdit {
    private lang: string;
    private $spmParagraphs: JQuery;
    private hasChanged: boolean = false;
    private isSingleLocationMode = false;

    constructor(private $form: JQuery) {
        let multiParagraphMode = parseInt($form.data("multi-paragraph-mode"), 10);
        if (typeof(multiParagraphMode) == "undefined") {
            throw "data-multi-paragraph-mode needs to be set";
        }

        this.lang = $("html").attr('lang');

        this.$form.find(".editorialChange input").on("change", this.editorialOpenerClicked.bind(this)).trigger("change");
        this.initGlobalAlternative();

        $(".input-group.date").datetimepicker({
            locale: this.lang,
            format: 'L'
        });

        if (multiParagraphMode === AMEND_PARAGRAPHS_MULTIPLE) {
            this.initMultiParagraphMode();
        } else {
            this.isSingleLocationMode = multiParagraphMode === AMEND_PARAGRAPHS_SINGLE_CHANGE;
            this.spmInit();
        }

        let $draftHint = $("#draftHint"),
            draftMotionId = $draftHint.data("motion-id"),
            draftAmendmentId = $draftHint.data("amendment-id");

        new DraftSavingEngine($form, $draftHint, "motion_" + draftMotionId + "_" + draftAmendmentId);

        $form.on("submit", () => {
            $(window).off("beforeunload", AmendmentEdit.onLeavePage);
        });
    }

    private initGlobalAlternative() {

    }

    private editorialOpenerClicked() {
        let $holder = this.$form.find("#sectionHolderEditorial"),
            $textarea = $holder.find(".texteditor"),
            active = this.$form.find(".editorialChange input").prop("checked");

        if (active) {
            $holder.removeClass("hidden");
            if (CKEDITOR.instances['amendmentEditorial_wysiwyg'] === undefined) {
                let editor: AntragsgruenEditor = new AntragsgruenEditor("amendmentEditorial_wysiwyg");
                $textarea.parents("form").on("submit", () => {
                    if (this.$form.find(".editorialChange input").prop("checked")) {
                        $textarea.parent().find("textarea.raw").val(editor.getEditor().getData());
                    } else {
                        $textarea.parent().find("textarea.raw").val("");
                    }
                });
                $("#" + $textarea.attr("id")).on('keypress', this.onContentChanged.bind(this));
            }
        } else {
            $holder.addClass("hidden");
        }
    }

    /* Multi paragraph mode */

    private initMultiParagraphMode() {
        $(".wysiwyg-textarea:not(#sectionHolderEditorial)").each((i, el) => {
            let $holder = $(el),
                $textarea = $holder.find(".texteditor");

            let editor: AntragsgruenEditor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor: CKEDITOR.editor = editor.getEditor();

            $textarea.parents("form").on("submit", () => {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof(ckeditor.plugins.lite) != 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });

            // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
            $('#' + $textarea.attr('id')).on('keypress', this.onContentChanged.bind(this));
        });

        this.$form.find('.resetText').on("click", (ev) => {
            let $text: JQuery = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));

            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
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
        $textarea.parents("form").on("submit", (ev) => {
            if (this.isSingleLocationMode && hasMultipleChanges()) {
                ev.preventDefault();
                ev.stopPropagation();
                $para.find('.oneChangeHint').removeClass('hidden');
                $para.scrollintoview({top_offset: -100});
                return;
            }

            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });

        const hasMultipleChanges = (): boolean => {
            // Undo / Cmd/Ctrl-Z leaves behind strange characters, that's why we need to take care of some special cases
            let countIns = 0,
                countDel = 0;
            $textarea.find(".ice-ins").each(function() {
                if ($(this)[0].innerText.length > 0 && $(this)[0].innerText !== "\ufeff") {
                    countIns++;
                }
            });
            $textarea.find(".ice-del").each(function() {
                if ($(this)[0].innerText.length > 0 && $(this)[0].innerText !== "\ufeff") {
                    countDel++;
                }
            });
            return (countIns > 1 || countDel > 1);
        };

        const setSingleLocWarning = () => {
            if (!this.isSingleLocationMode) {
                return;
            }

            if (hasMultipleChanges()) {
                $para.find('.oneChangeHint').removeClass('hidden');
            } else {
                $para.find('.oneChangeHint').addClass('hidden');
            }
        };

        // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
        $("#" + $textarea.attr("id"))
            .on('keypress', this.onContentChanged.bind(this))
            .on('keyup', setSingleLocWarning.bind(this));

        $textarea.trigger("focus");
    }

    private spmRevert(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        let $para = $(ev.target).parents(".wysiwyg-textarea"),
            $textarea = $para.find(".texteditor");

        if (typeof(CKEDITOR.instances[$textarea.attr("id")]) !== "undefined") {
            AntragsgruenEditor.destroyInstanceById($textarea.attr("id"));
        }

        $textarea.html($para.data("original"));
        $para.removeClass("modified");
        $para.find('.oneChangeHint').addClass('hidden');
        this.spmSetModifyable();
    }

    private spmInitNonSingleParas(i, el) {
        let $holder = $(el),
            $textarea = $holder.find(".texteditor");
        if ($holder.hasClass("hidden")) {
            return;
        }
        let editor: CKEDITOR.editor = (new AntragsgruenEditor($textarea.attr("id"))).getEditor();
        $textarea.parents("form").on("submit", () => {
            $textarea.parent().find("textarea.raw").val(editor.getData());
            if (typeof(editor.plugins.lite) != 'undefined') {
                editor.plugins.lite.findPlugin(editor).acceptAll();
                $textarea.parent().find("textarea.consolidated").val(editor.getData());
            }
        });

        // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
        $("#" + $textarea.attr("id")).on('keypress', this.onContentChanged.bind(this));
    }

    private spmInit() {
        this.$spmParagraphs = $(".wysiwyg-textarea.single-paragraph");
        this.$spmParagraphs.on("click", this.spmOnParaClick.bind(this));
        this.$spmParagraphs.find(".modifiedActions .revert").on("click", this.spmRevert.bind(this));
        this.spmSetModifyable();

        // Amendment Reason
        $(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(this.spmInitNonSingleParas.bind(this));

        $(".texteditorBox").each((i, el) => {
            let $this = $(el),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).trigger("click");
            }
        });

        if (this.$form.data('init-section-id')) {
            const $holder = $("#section_holder_" + this.$form.data('init-section-id') + "_" + this.$form.data('init-paragraph-no'));
            $holder.trigger("click");
            $holder.scrollintoview({top_offset: -100});
        }
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    public onContentChanged() {

        if (!this.hasChanged) {
            this.hasChanged = true;
            if (!$("body").hasClass('testing')) {
                $(window).on("beforeunload", AmendmentEdit.onLeavePage);
            }
        }
    }
}
