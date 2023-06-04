import {MotionSupporterEdit} from "./MotionSupporterEdit";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

const STATUS_VOTE = 11;
const STATUS_OBSOLETED_BY_MOTION = 32;
const STATUS_OBSOLETED_BY_AMENDMENT = 22;

export class MotionEdit {
    private $updateForm: JQuery;
    private $status: JQuery;

    constructor() {
        let lang = $("html").attr("lang");
        this.$updateForm = $("#motionUpdateForm");
        this.$status = $("#motionStatus");
        $("#motionDateCreationHolder").datetimepicker({
            locale: lang
        });
        $("#motionDatePublicationHolder").datetimepicker({
            locale: lang
        });
        $("#motionDateResolutionHolder").datetimepicker({
            locale: lang
        });
        $('#resolutionDateHolder').datetimepicker({
            locale: $('#resolutionDate').data('locale'),
            format: 'L'
        });
        $("#motionTextEditCaller").find("button").on('click', () => {
            this.initMotionTextEdit();
        });

        $(".checkAmendmentCollisions").on('click', ev => {
            ev.preventDefault();
            this.loadAmendmentCollisions();
        });

        this.$updateForm.on("submit", function () {
            $(".amendmentCollisionsHolder .amendmentOverrideBlock > .texteditor").each(function () {
                let text = CKEDITOR.instances[$(this).attr("id")].getData();
                $(this).parents(".amendmentOverrideBlock").find("> textarea").val(text);
            });
        });

        $(".motionDeleteForm").on("submit", (ev, data) => {
            this.onSubmitDeleteForm(ev, data);
        });

        this.initProtocolFunctions();
        this.initVotingFunctions();
        this.initSlug();
        this.initStatus();

        new MotionSupporterEdit($("#motionSupporterHolder"));
    }

    private initSlug() {
        $('.urlSlugHolder .shower button').on("click", (ev) => {
            ev.preventDefault();
            $('.urlSlugHolder .shower').addClass('hidden');
            $('.urlSlugHolder .holder').removeClass('hidden');
        });
    }
    private initStatus() {
        const onChange = () => {
            const newStatus = parseInt((document.getElementById('motionStatus') as HTMLSelectElement).value, 10);
            console.log(newStatus);
            if (newStatus === STATUS_OBSOLETED_BY_MOTION) {
                document.querySelector('.motionStatusString').classList.add('hidden');
                document.querySelector('.motionStatusMotion').classList.remove('hidden');
                document.querySelector('.motionStatusAmendment').classList.add('hidden');
            } else if (newStatus === STATUS_OBSOLETED_BY_AMENDMENT) {
                document.querySelector('.motionStatusString').classList.add('hidden');
                document.querySelector('.motionStatusMotion').classList.add('hidden');
                document.querySelector('.motionStatusAmendment').classList.remove('hidden');
            } else {
                document.querySelector('.motionStatusString').classList.remove('hidden');
                document.querySelector('.motionStatusMotion').classList.add('hidden');
                document.querySelector('.motionStatusAmendment').classList.add('hidden');
            }
        };
        document.getElementById('motionStatus').addEventListener('change', onChange);
        onChange();
    }
    private initProtocolFunctions() {
        const $classHolders = $(".contentProtocolCaller, .protocolHolder"),
            $closer = $(".protocolCloser"),
            $opener = $(".protocolOpener");

        $opener.on("click", () => {
            $classHolders.addClass('explicitlyOpened');
        });
        $closer.on("click", () => {
            $classHolders.removeClass('explicitlyOpened');
        });

        const $textarea = $("#protocol_text_wysiwyg");
        $textarea.attr("contenteditable", "true");
        const ckeditor = new AntragsgruenEditor($textarea.attr("id"));
        const editor = ckeditor.getEditor();

        $textarea.parents("form").on("submit", () => {
            $textarea.parent().find("textarea").val(editor.getData());
        });
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

    private onSubmitDeleteForm(ev, data) {
        if (data && typeof(data.confirmed) && data.confirmed === true) {
            return;
        }
        ev.preventDefault();
        bootbox.confirm(__t("admin", "delMotionConfirm"), function (result) {
            if (result) {
                $(".motionDeleteForm").trigger("submit", {'confirmed': true});
            }
        });
    }

    private initMotionTextEdit() {
        $("#motionTextEditCaller").addClass("hidden");
        $("#motionTextEditHolder").removeClass("hidden");
        $(".wysiwyg-textarea").each(function () {
            let $holder = $(this),
                $textarea = $holder.find(".texteditor"),
                ckeditor = new AntragsgruenEditor($textarea.attr("id")),
                editor = ckeditor.getEditor();

            $textarea.parents("form").on("submit", () => {
                $textarea.parent().find("textarea").val(editor.getData());
            });
        });
        this.$updateForm.append("<input type='hidden' name='edittext' value='1'>");

        if ($(".checkAmendmentCollisions").length > 0) {
            $(".wysiwyg-textarea .texteditor").on("focus", function () {
                $(".checkAmendmentCollisions").show();
                $(".saveholder .save").prop("disabled", true).hide();
            });
            $(".checkAmendmentCollisions").show();
            $(".saveholder .save").prop("disabled", true).hide();
        }
    }

    private loadAmendmentCollisions() {
        let url = $(".checkAmendmentCollisions").data("url"),
            sections = {},
            $holder = $(".amendmentCollisionsHolder");

        $("#motionTextEditHolder").children().each(function () {
            let $this = $(this);
            if ($this.hasClass("wysiwyg-textarea")) {
                let sectionId = $this.attr("id").replace("section_holder_", "");
                sections[sectionId] = CKEDITOR.instances[$this.find(".texteditor").attr("id")].getData();
            }
        });
        $.post(url, {
            'newSections': sections,
            '_csrf': this.$updateForm.find('> input[name=_csrf]').val()
        }, function (html) {
            $holder.html(html);

            if ($holder.find(".amendmentOverrideBlock > .texteditor").length > 0) {
                $holder.find(".amendmentOverrideBlock > .texteditor").each(function () {
                    new AntragsgruenEditor($(this).attr("id"));
                });
                $(".amendmentCollisionsHolder").scrollintoview({top_offset: -50});
            }

            $(".checkAmendmentCollisions").hide();
            $(".saveholder .save").prop("disabled", false).show();

        });
    }
}
