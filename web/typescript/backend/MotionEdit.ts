/// <reference path="../typings/scrollintoview/index.d.ts" />

import {MotionSupporterEdit} from "./MotionSupporterEdit";
import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";

class MotionEdit {
    constructor() {
        let lang = $("html").attr("lang");
        $("#motionDateCreationHolder").datetimepicker({
            locale: lang
        });
        $("#motionDateResolutionHolder").datetimepicker({
            locale: lang
        });
        $('#resolutionDateHolder').datetimepicker({
            locale: $('#resolutionDate').data('locale'),
            format: 'L'
        });
        $("#motionTextEditCaller").find("button").click(() => {
            this.initMotionTextEdit();
        });

        $(".checkAmendmentCollissions").click((ev) => {
            ev.preventDefault();
            this.loadAmendmentCollissions();
        });

        $("#motionUpdateForm").submit(function () {
            $(".amendmentCollissionsHolder .amendmentOverrideBlock > .texteditor").each(function () {
                let text = CKEDITOR.instances[$(this).attr("id")].getData();
                $(this).parents(".amendmentOverrideBlock").find("> textarea").val(text);
            });
        });

        $(".motionDeleteForm").submit((ev, data) => {
            this.onSubmitDeleteForm(ev, data);
        });

        new MotionSupporterEdit($("#motionSupporterHolder"));
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

            $textarea.parents("form").submit(() => {
                $textarea.parent().find("textarea").val(editor.getData());
            });
        });
        $("#motionUpdateForm").append("<input type='hidden' name='edittext' value='1'>");

        if ($(".checkAmendmentCollissions").length > 0) {
            $(".wysiwyg-textarea .texteditor").on("focus", function () {
                $(".checkAmendmentCollissions").show();
                $(".saveholder .save").prop("disabled", true).hide();
            });
            $(".checkAmendmentCollissions").show();
            $(".saveholder .save").prop("disabled", true).hide();
        }
    }

    private loadAmendmentCollissions() {
        let url = $(".checkAmendmentCollissions").data("url"),
            sections = {},
            $holder = $(".amendmentCollissionsHolder");

        $("#motionTextEditHolder").children().each(function () {
            let $this = $(this);
            if ($this.hasClass("wysiwyg-textarea")) {
                let sectionId = $this.attr("id").replace("section_holder_", "");
                sections[sectionId] = CKEDITOR.instances[$this.find(".texteditor").attr("id")].getData();
            }
        });
        $.post(url, {
            'newSections': sections,
            '_csrf': $("#motionUpdateForm").find('> input[name=_csrf]').val()
        }, function (html) {
            $holder.html(html);

            if ($holder.find(".amendmentOverrideBlock > .texteditor").length > 0) {
                $holder.find(".amendmentOverrideBlock > .texteditor").each(function () {
                    new AntragsgruenEditor($(this).attr("id"));
                });
                $(".amendmentCollissionsHolder").scrollintoview({top_offset: -50});
            }

            $(".checkAmendmentCollissions").hide();
            $(".saveholder .save").prop("disabled", false).show();

        });
    }
}

new MotionEdit();
