import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import {MotionMergeChangeActions} from "../frontend/MotionMergeAmendments";

export class AmendmentEditProposedChange {
    private hasChanged: boolean = false;
    private $collissionIndicator: JQuery;

    public constructor(private $form: JQuery) {
        this.textEditCalled();
        this.initCollissionDetection();

        $form.on("submit", () => {
            $(window).off("beforeunload", AmendmentEditProposedChange.onLeavePage);
        });
    }

    private textEditCalled() {
        $(".wysiwyg-textarea:not(#sectionHolderEditorial)").each((i, el) => {
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

            // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
            $('#' + $textarea.attr('id')).on('keypress', this.onContentChanged.bind(this));
        });

        this.$form.find('.resetText').click((ev) => {
            let $text: JQuery = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));

            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
        });
    }

    private initCollissionDetection() {
        this.$collissionIndicator = this.$form.find('#collissionIndicator');

        window.setInterval(() => {
            let sectionData = this.getTextConsolidatedSections();
            let url = this.$form.data('collission-check-url');
            $.post(url, {
                '_csrf': this.$form.find('> input[name=_csrf]').val(),
                'sections': sectionData
            }, (ret) => {
                if (ret['collissions'].length == 0) {
                    this.$collissionIndicator.addClass('hidden');
                } else {
                    this.$collissionIndicator.removeClass('hidden');
                    let listHtml = '';
                    ret['collissions'].forEach((el) => {
                       listHtml += el['html'];
                    });
                    this.$collissionIndicator.find('.collissionList').html(listHtml);
                }
            });

        }, 5000);
    }

    private getTextConsolidatedSections() {
        let sections = {};
        $('.proposedVersion .wysiwyg-textarea:not(#sectionHolderEditorial)').each((i, el) => {
            let $holder = $(el),
                $textarea = $holder.find('.texteditor'),
                sectionId = $holder.parents('.proposedVersion').data('section-id');

            let $cloned = $textarea.clone(false);
            $cloned.find('.ice-ins').each((i, el) => {
                MotionMergeChangeActions.insertAccept(el);
            });
            $cloned.find('.ice-del').each((i, el) => {
                MotionMergeChangeActions.deleteAccept(el);
            });

            sections[sectionId] = $cloned.html();
        });
        return sections;
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    public onContentChanged() {
        if (!this.hasChanged) {
            this.hasChanged = true;
            if (!$("body").hasClass('testing')) {
                $(window).on("beforeunload", AmendmentEditProposedChange.onLeavePage);
            }
        }
    }
}
