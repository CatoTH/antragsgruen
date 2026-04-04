// @ts-check

import {AntragsgruenEditor} from "../shared/AntragsgruenEditor.js";
import {MotionMergeChangeActions} from "../shared/MotionMergeChangeActions.js";

export class ProposedChangeEdit {
    hasChanged = false;

    /** @type { JQuery } */
    $collisionIndicator;

    /** @type { JQuery } */
    $form;

    constructor(form) {
        this.$form = $(form);
        this.textEditCalled();
        this.initCollisionDetection();

        this.$form.on("submit", () => {
            $(window).off("beforeunload", ProposedChangeEdit.onLeavePage);
        });
    }

    textEditCalled() {
        $(".wysiwyg-textarea:not(#sectionHolderEditorial)").each((i, el) => {
            let $holder = $(el),
                $textarea = $holder.find(".texteditor");

            let editor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor = editor.getEditor();

            $textarea.parents("form").on('submit', () => {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof (ckeditor.plugins.lite) != 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });

            // The editor doesn't trigger change-events when tracking changes is enabled, therefore this work-around
            $('#' + $textarea.attr('id')).on('keypress', this.onContentChanged.bind(this));
        });

        this.$form.find('.resetText').on('click', (ev) => {
            let $text = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));

            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
        });
    }

    initCollisionDetection() {
        if (!this.$form.data('collision-check-url')) {
            // Motions do not support collision detection yet
            return;
        }

        this.$collisionIndicator = this.$form.find('#collisionIndicator');
        let lastCheckedContent = null;

        window.setInterval(() => {
            this.$form.find('.wysiwyg-textarea').find('.texteditor').each(function () {
                const $text = $(this),
                    // WYSWYG-Editor adds some linebreaks, so let's just ignore them.
                    currentText = window['CKEDITOR']['instances'][$text.attr('id')].getData().replace(/\n/g, ""),
                    originalText = $text.data('original-html').replace(/\n/g, "", "");

                if (currentText === originalText) {
                    $text.parents('.wysiwyg-textarea').find('.modifiedActions').addClass('hidden');
                } else {
                    $text.parents('.wysiwyg-textarea').find('.modifiedActions').removeClass('hidden');
                }
            });

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

    getTextConsolidatedSections() {
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
                MotionMergeChangeActions.deleteAccept(el, false);
            });

            sections[sectionId] = $cloned.html();
        });
        return sections;
    }

    static onLeavePage() {
        return __t("std", "leave_changed_page");
    }

    onContentChanged() {
        if (!this.hasChanged) {
            this.hasChanged = true;
            if (!$("body").hasClass('testing')) {
                $(window).on("beforeunload", ProposedChangeEdit.onLeavePage);
            }
        }
    }
}
