/*global browser: true, regexp: true */
/*global $, jQuery, console, document, Sortable, bootbox, JSON */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var motionListAll = function () {
        $(".markAll").click(function (ev) {
            $(".adminMotionTable").find("input.selectbox").prop("checked", true);
            ev.preventDefault();
        });
        $(".markNone").click(function (ev) {
            $(".adminMotionTable").find("input.selectbox").prop("checked", false);
            ev.preventDefault();
        });

        var $select = $("#initiatorSelect"),
            initiatorValues = $select.data("values"),
            matcher = function findMatches(q, cb) {
                var matches, substrRegex;

                // an array that will be populated with substring matches
                matches = [];

                // regex used to determine if a string contains the substring `q`
                substrRegex = new RegExp(q, "i");

                // iterate through the pool of strings and for any string that
                // contains the substring `q`, add it to the `matches` array
                $.each(initiatorValues, function (i, str) {
                    if (substrRegex.test(str)) {
                        // the typeahead jQuery plugin expects suggestions to a
                        // JavaScript object, refer to typeahead docs for more info
                        matches.push({value: str});
                    }
                });
                cb(matches);
            };
        $select.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: "supporter",
            displayKey: "value",
            source: matcher
        });

        $('.adminMotionTable').colResizable({
            'liveDrag': true,
            'postbackSafe': true,
            'minWidth': 30
        });
    };

    var exportRowInit = function () {
        var $exportRow = $(".motionListExportRow");
        $exportRow.find("li.checkbox").on("click", function (ev) {
            ev.stopPropagation();
        });
        $exportRow.find(".exportMotionDd, .exportAmendmentDd").each(function () {
            var $dd = $(this),
                recalcLinks = function () {
                    var withdrawn = ($dd.find("input[name=withdrawn]").prop("checked") ? 1 : 0);
                    $dd.find(".exportLink a").each(function () {
                        var link = $(this).data("href-tpl");
                        link = link.replace("WITHDRAWN", withdrawn);
                        $(this).attr("href", link);
                    });
                };
            $dd.find("input[type=checkbox]").change(recalcLinks).trigger("change");
        });
    };

    var siteAccessInit = function () {
        var $siteForm = $("#siteSettingsForm");
        $siteForm.find(".loginMethods .namespaced input").change(function () {
            if ($(this).prop("checked")) {
                $("#accountsForm").removeClass("hidden");
            } else {
                $("#accountsForm").addClass("hidden");
            }
        }).trigger("change");


        $(".removeAdmin").click(function () {
            var $button = $(this),
                $form = $(this).parents("form").first();
            bootbox.confirm(__t("admin", "removeAdminConfirm"), function (result) {
                if (result) {
                    var id = $button.data("id");
                    $form.append('<input type="hidden" name="removeAdmin" value="' + id + '">');
                    $form.submit();
                }
            });
        });

        $(".managedUserAccounts input").change(function () {
            if ($(this).prop("checked")) {
                $(".showManagedUsers").show();
            } else {
                $(".showManagedUsers").hide();
            }
        }).trigger("change");
    };

    var siteAccessUsersInit = function () {
        $("#accountsCreateForm").submit(function (ev) {
            var text = $("#emailText").val();
            if (text.indexOf("%ACCOUNT%") == -1) {
                bootbox.alert(__t("admin", "emailMissingCode"));
                ev.preventDefault();
            }
            if (text.indexOf("%LINK%") == -1) {
                bootbox.alert(__t("admin", "emailMissingLink"));
                ev.preventDefault();
            }

            var emails = $("#emailAddresses").val().split("\n"),
                names = $("#names").val().split("\n");
            if (emails.length == 1 && emails[0] == "") {
                ev.preventDefault();
                bootbox.alert(__t("admin", "emailMissingTo"));
            }
            if (emails.length != names.length) {
                bootbox.alert(__t("admin", "emailNumberMismatch"));
                ev.preventDefault();
            }
        });

        $('.accountListTable .accessViewCol input[type=checkbox]').change(function () {
            if (!$(this).prop("checked")) {
                $(this).parents('tr').first().find('.accessCreateCol input[type=checkbox]').prop('checked', false);
            }
        });
        $('.accountListTable .accessCreateCol input[type=checkbox]').change(function () {
            if ($(this).prop("checked")) {
                $(this).parents('tr').first().find('.accessViewCol input[type=checkbox]').prop('checked', true);
            }
        });
    };

    var motionSupporterEdit = function () {
        var $supporterHolder = $("#motionSupporterHolder"),
            $sortable = $supporterHolder.find("> ul");
        Sortable.create($sortable[0], {draggable: 'li'});

        $(".supporterRowAdder").click(function (ev) {
            $sortable.append($(this).data("content"));
            ev.preventDefault();
        });
        $sortable.on("click", ".delSupporter", function (ev) {
            ev.preventDefault();
            $(this).parents("li").first().remove();
        });

        var $fullTextHolder = $("#fullTextHolder");
        $supporterHolder.find(".fullTextAdd").click(function () {
            var lines = $fullTextHolder.find('textarea').val().split(";"),
                template = $(".supporterRowAdder").data("content"),
                getNewElement = function () {
                    var $rows = $sortable.find("> li");
                    for (var i = 0; i < $rows.length; i++) {
                        var $row = $rows.eq(i);
                        if ($row.find(".supporterName").val() == '' && $row.find(".supporterOrga").val() == '') return $row;
                    }
                    // No empty row found
                    var $newEl = $(template);
                    $sortable.append($newEl);
                    return $newEl;
                };
            var $firstAffectedRow = null;
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                var $newEl = getNewElement();
                if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
                if ($newEl.find('input.supporterOrga').length > 0) {
                    var parts = lines[i].split(',');
                    $newEl.find('input.supporterName').val(parts[0].trim());
                    if (parts.length > 1) {
                        $newEl.find('input.supporterOrga').val(parts[1].trim());
                    }
                } else {
                    $newEl.find('input.supporterName').val(lines[i]);
                }
            }
            $fullTextHolder.find('textarea').select().focus();
            $firstAffectedRow.scrollintoview();
        });
    };

    var motionEditInit = function () {
        var lang = $("html").attr("lang");
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
        $("#motionTextEditCaller").find("button").click(function () {
            $("#motionTextEditCaller").addClass("hidden");
            $("#motionTextEditHolder").removeClass("hidden");
            $(".wysiwyg-textarea").each(function () {
                var $holder = $(this),
                    $textarea = $holder.find(".texteditor"),
                    editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));

                $textarea.parents("form").submit(function () {
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
        });


        var loadAmendmentCollissions = function () {
            var url = $(".checkAmendmentCollissions").data("url"),
                sections = [],
                $holder = $(".amendmentCollissionsHolder");

            $("#motionTextEditHolder").children().each(function () {
                var $this = $(this);
                if ($this.hasClass("wysiwyg-textarea")) {
                    var sectionId = $this.attr("id").replace("section_holder_", "");
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
                        $.AntragsgruenCKEDITOR.init($(this).attr("id"))
                    });
                    $(".amendmentCollissionsHolder").scrollintoview({top_offset: -50});
                }

                $(".checkAmendmentCollissions").hide();
                $(".saveholder .save").prop("disabled", false).show();

            });
        };
        $(".checkAmendmentCollissions").click(function (ev) {
            ev.preventDefault();
            loadAmendmentCollissions();
        });

        $("#motionUpdateForm").submit(function() {
            $(".amendmentCollissionsHolder .amendment-override-block > .texteditor").each(function() {
                var text = CKEDITOR.instances[$(this).attr("id")].getData();
                $(this).parents(".amendment-override-block").find("> textarea").val(text);
            });
        });

        $(".motionDeleteForm").submit(function (ev, data) {
            if (data && typeof(data.confirmed) && data.confirmed === true) {
                return;
            }
            var $form = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "delMotionConfirm"), function (result) {
                if (result) {
                    $form.trigger("submit", {'confirmed': true});
                }
            });
        });

        motionSupporterEdit();
    };


    $.AntragsgruenAdmin = {
        'motionListAll': motionListAll,
        'exportRowInit': exportRowInit,
        'siteAccessInit': siteAccessInit,
        'siteAccessUsersInit': siteAccessUsersInit,
        'motionEditInit': motionEditInit
    };

}(jQuery));
