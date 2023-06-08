import "./MotionSupporterEdit";
import { MotionSupporterEdit } from "./MotionSupporterEdit";
import { AntragsgruenEditor } from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;
import { AmendmentEditSinglePara } from "../shared/AmendmentEditSinglePara";

const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY_MOTION = 32;
const STATUS_OBSOLETED_BY_AMENDMENT = 22;

export class AmendmentEdit {
    private lang: string;

    private $updateForm: JQuery;
    private $status: JQuery;
    private $editTextCaller: JQuery;

    constructor() {
        this.lang = $("html").attr("lang");
        this.$updateForm = $("#amendmentUpdateForm");
        this.$status = $("#amendmentStatus");
        this.$editTextCaller = $("#amendmentTextEditCaller");

        $("#amendmentDateCreationHolder").datetimepicker({
            locale: this.lang
        });
        $("#amendmentDateResolutionHolder").datetimepicker({
            locale: this.lang
        });
        $('#resolutionDateHolder').datetimepicker({
            locale: $('#resolutionDate').data('locale'),
            format: 'L'
        });

        this.$editTextCaller.find("button").on("click", this.textEditCalled.bind(this));

        $('.wysiwyg-textarea .resetText').on("click", (ev) => {
            let $text: JQuery = $(ev.currentTarget).parents('.wysiwyg-textarea').find('.texteditor');
            window['CKEDITOR']['instances'][$text.attr('id')].setData($text.data('original-html'));

            $(ev.currentTarget).parents('.modifiedActions').addClass('hidden');
        });

        $(".amendmentDeleteForm").on("submit", function (ev, data) {
            if (data && typeof (data.confirmed) && data.confirmed === true) {
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

        this.initVotingFunctions();
        this.initStatus();

        new MotionSupporterEdit($("#motionSupporterHolder"));
    }


    private textEditCalledMultiPara() {
        $(".wysiwyg-textarea").each(function () {
            let $holder = $(this),
                $textarea = $holder.find(".texteditor");

            let editor: AntragsgruenEditor = new AntragsgruenEditor($textarea.attr("id")),
                ckeditor: editor = editor.getEditor();

            $textarea.parents("form").on("submit", function () {
                $textarea.parent().find("textarea.raw").val(ckeditor.getData());
                if (typeof (ckeditor.plugins.lite) != 'undefined') {
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
    }

    private initStatus() {
        const onChange = () => {
            const newStatus = parseInt((document.getElementById('amendmentStatus') as HTMLSelectElement).value, 10);
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

    private initVotingFunctions() {
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
            if (parseInt(this.$status.val() as string, 10) === STATUS_VOTE) {
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
