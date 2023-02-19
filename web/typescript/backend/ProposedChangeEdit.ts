import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import {MotionMergeChangeActions} from "../frontend/MotionMergeAmendments";

export class ProposedChangeEdit {
    private hasChanged: boolean = false;
    private $collisionIndicator: JQuery;

    public constructor(private $form: JQuery) {
        this.textEditCalled();
        this.initCollisionDetection();

        $form.on("submit", () => {
            $(window).off("beforeunload", ProposedChangeEdit.onLeavePage);
        });
    }

    private textEditCalled() {
        $(".wysiwyg-textarea:not(#sectionHolderEditorial)").each((i, el) => {
            let $holder = $(el),
                $textarea = $holder.find(".texteditor");

            let editor: AntragsgruenEditor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor: CKEDITOR.editor = editor.getEditor();

            $textarea.parents("form").on('submit', () => {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof(ckeditor.plugins.lite) != 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });

            // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
            $('#' + $textarea.attr('id')).on('keypress', this.onContentChanged.bind(this));
        });

        this.$form.find('.resetText').on('click', (ev) => {
            let $text: JQuery = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));

            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
        });
    }

    private initCollisionDetection() {
        if (!this.$form.data('collision-check-url')) {
            // Motions do not support collision detection yet
            return;
        }

        this.$collisionIndicator = this.$form.find('#collisionIndicator');
        let lastCheckedContent = null;

        window.setInterval(() => {
            let sectionData = this.getTextConsolidatedSections();
            if (JSON.stringify(sectionData) === lastCheckedContent) {
                return;
            }

            lastCheckedContent = JSON.stringify(sectionData);
            let url = this.$form.data('collision-check-url');
            $.post(url, {
                '_csrf': this.$form.find('> input[name=_csrf]').val(),
                'sections': sectionData
            }, (ret) => {
                if (ret['error']) {
                    this.$collisionIndicator.removeClass('hidden');
                    this.$collisionIndicator.find('.collisionList').html('<li>' + ret['error'] + '</li>');
                } else if (ret['collisions'].length == 0) {
                    this.$collisionIndicator.addClass('hidden');
                } else {
                    this.$collisionIndicator.removeClass('hidden');
                    let listHtml = '';
                    ret['collisions'].forEach((el) => {
                       listHtml += el['html'];
                    });
                    this.$collisionIndicator.find('.collisionList').html(listHtml);
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
                $(window).on("beforeunload", ProposedChangeEdit.onLeavePage);
            }
        }
    }
}
