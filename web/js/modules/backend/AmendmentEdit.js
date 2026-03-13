// @ts-check

import { MotionSupporterEdit } from "./MotionSupporterEdit.js";
import { AntragsgruenEditor } from "../shared/AntragsgruenEditor.js";
import { AmendmentEditSinglePara } from "../shared/AmendmentEditSinglePara.js";

const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY_MOTION = 32;
const STATUS_OBSOLETED_BY_AMENDMENT = 22;

export class AmendmentEdit {
    /** @type {string} */
    lang;

    /** @type {JQuery} */
    $updateForm;

    /** @type {JQuery} */
    $status;

    /** @type {JQuery} */
    $editTextCaller;

    constructor() {
        this.lang = $("html").attr("lang");
        this.$updateForm = $("#amendmentUpdateForm");
        this.$status = $("#amendmentStatus");
        this.$editTextCaller = $("#amendmentTextEditCaller");

        $("#amendmentDateCreationHolder").datetimepicker({ locale: this.lang });
        $("#amendmentDateSubmissionHolder").datetimepicker({ locale: this.lang });
        $("#amendmentDateResolutionHolder").datetimepicker({ locale: this.lang });
        $('#resolutionDateHolder').datetimepicker({
            locale: $('#resolutionDate').data('locale'),
            format: 'L'
        });

        this.$editTextCaller.find("button").on("click", this.textEditCalled.bind(this));

        $('.wysiwyg-textarea .resetText').on("click", (ev) => {
            const $text = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));
            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
        });

        $(".amendmentDeleteForm").on("submit", function (ev, data) {
            if (data && typeof data.confirmed !== 'undefined' && data.confirmed === true) {
                return;
            }
            const $form = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "delAmendmentConfirm"), function (result) {
                if (result) {
                    $form.trigger("submit", { 'confirmed': true });
                }
            });
        });

        this.initVotingFunctions();
        this.initStatus();

        new MotionSupporterEdit($("#motionSupporterHolder"));
    }

    textEditCalledMultiPara() {
        $(".wysiwyg-textarea").each(function () {
            const $holder = $(this),
                $textarea = $holder.find(".texteditor");

            const antragsEditor = new AntragsgruenEditor($textarea.attr("id")),
                /** @type {CKEDITOR.editor} */
                ckeditor = antragsEditor.getEditor();

            $textarea.parents("form").on("submit", function () {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof ckeditor.plugins.lite !== 'undefined') {
                    ckeditor.plugins.lite.findPlugin(ckeditor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(ckeditor.getData());
                }
            });
        });
    }

    textEditCalled() {
        this.$editTextCaller.addClass("hidden");
        $("#amendmentTextEditHolder").removeClass("hidden");
        if (this.$editTextCaller.data("multiple-paragraphs")) {
            this.textEditCalledMultiPara();
        } else {
            new AmendmentEditSinglePara();
        }
        $("#amendmentUpdateForm").append("<input type='hidden' name='edittext' value='1'>");
    }

    initStatus() {
        const onChange = () => {
            const newStatus = parseInt(/** @type {HTMLSelectElement} */ (document.getElementById('amendmentStatus')).value, 10);
            if (newStatus === STATUS_OBSOLETED_BY_MOTION) {
                document.querySelector('.amendmentStatusString').classList.add('hidden');
                document.querySelector('.amendmentStatusMotion').classList.remove('hidden');
                document.querySelector('.amendmentStatusAmendment').classList.add('hidden');
            } else if (newStatus === STATUS_OBSOLETED_BY_AMENDMENT) {
                document.querySelector('.amendmentStatusString').classList.add('hidden');
                document.querySelector('.amendmentStatusMotion').classList.add('hidden');
                document.querySelector('.amendmentStatusAmendment').classList.remove('hidden');
            } else {
                document.querySelector('.amendmentStatusString').classList.remove('hidden');
                document.querySelector('.amendmentStatusMotion').classList.add('hidden');
                document.querySelector('.amendmentStatusAmendment').classList.add('hidden');
            }
        };
        document.getElementById('amendmentStatus').addEventListener('change', onChange);
        onChange();
    }

    initVotingFunctions() {
        const $classHolders = $(".contentVotingResultCaller, .votingDataHolder"),
            $closer = $(".votingDataCloser"),
            $opener = $(".votingDataOpener"),
            $votingBlockId = $('select[name=votingBlockId]');

        $opener.on("click", () => {
            $classHolders.addClass('explicitlyOpened');
        });
        $closer.on("click", () => {
            $classHolders.removeClass('explicitlyOpened');
        });

        this.$status.on('change', () => {
            if (parseInt(/** @type {string} */ (this.$status.val()), 10) === STATUS_VOTE) {
                $classHolders.addClass('hasVotingStatus');
            } else {
                $classHolders.removeClass('hasVotingStatus');
            }
        }).trigger('change');

        $(".votingItemBlockRow select").on('change', (ev) => {
            const $select = $(ev.currentTarget);
            if ($select.val()) {
                const selectedName = $select.find("option[value=" + $select.val() + "]").data("group-name");
                $(".votingItemBlockNameRow input").val(selectedName);
                $(".votingItemBlockNameRow").removeClass('hidden');
            } else {
                // Not grouped
                $(".votingItemBlockNameRow").addClass('hidden');
            }
        });

        $votingBlockId.on('change', () => {
            if ($votingBlockId.val() === 'NEW') {
                $(".votingBlockRow .newBlock").removeClass('hidden');
                $(".votingItemBlockRow").addClass('hidden');
                $(".votingItemBlockNameRow").addClass('hidden');
            } else {
                $(".votingBlockRow .newBlock").addClass('hidden');
                $(".votingItemBlockRow").addClass('hidden');
                const $votingItemBlockRow = $(".votingItemBlockRow" + $votingBlockId.val());
                $votingItemBlockRow.removeClass('hidden');
                if ($votingItemBlockRow.length > 0) {
                    $votingItemBlockRow.removeClass('hidden');
                    $votingItemBlockRow.find("select").trigger('change'); // to trigger group name listener
                } else {
                    $(".votingItemBlockNameRow").addClass('hidden');
                }
            }
        }).trigger('change');
    }
}
