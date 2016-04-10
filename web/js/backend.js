/*global browser: true, regexp: true */
/*global $, jQuery, console, document, Sortable, bootbox, JSON */
/*jslint regexp: true*/


(function ($) {
    "use strict";

    var consultationSettingsForm = function () {
        var $form = $("#consultationSettingsForm");

        $('.urlPathHolder .shower a').click(function (ev) {
            ev.preventDefault();
            $('.urlPathHolder .shower').addClass('hidden');
            $('.urlPathHolder .holder').removeClass('hidden');
        });

        $form.submit(function () {
            var items = $("#tagsList").pillbox('items'),
                tags = [],
                $node = $('<input type="hidden" name="tags">'),
                i;
            for (i = 0; i < items.length; i++) {
                if (typeof(items[i].id) == 'undefined') {
                    tags.push({"id": 0, "name": items[i].text});
                } else {
                    tags.push({"id": items[i].id, "name": items[i].text});
                }
            }
            $node.attr("value", JSON.stringify(tags));
            $form.append($node);
        });

        Sortable.create(document.getElementById("tagsListUl"), {draggable: '.pill'});

        var $adminsMayEdit = $("#adminsMayEdit"),
            $iniatorsMayEdit = $("#iniatorsMayEdit").parents("label").first().parent();
        $adminsMayEdit.change(function () {
            if ($(this).prop("checked")) {
                $iniatorsMayEdit.removeClass("hidden");
            } else {
                var confirmMessage = __t("admin", "adminMayEditConfirm");
                bootbox.confirm(confirmMessage, function (result) {
                    if (result) {
                        $iniatorsMayEdit.addClass("hidden");
                        $iniatorsMayEdit.find("input").prop("checked", false);
                    } else {
                        $adminsMayEdit.prop("checked", true);
                    }
                });
            }
        });
        if (!$adminsMayEdit.prop("checked")) $iniatorsMayEdit.addClass("hidden");
    };


    var motionTypeEdit = function () {
        var $list = $('#sectionsList'),
            newCounter = 0;

        $list.data("sortable", Sortable.create($list[0], {
            handle: '.drag-handle',
            animation: 150
        }));
        $list.on('click', 'a.remover', function (ev) {
            ev.preventDefault();
            var $sectionHolder = $(this).parents('li').first(),
                delId = $sectionHolder.data('id');
            $('.adminTypeForm').append('<input type="hidden" name="sectionsTodelete[]" value="' + delId + '">');
            $sectionHolder.remove();
        });
        $list.on('change', '.sectionType', function () {
            var $li = $(this).parents('li').first(),
                val = parseInt($(this).val());
            $li.removeClass('title textHtml textSimple image tabularData');
            if (val === 0) {
                $li.addClass('title');
            } else if (val === 1) {
                $li.addClass('textSimple');
            } else if (val === 2) {
                $li.addClass('textHtml');
            } else if (val === 3) {
                $li.addClass('image');
            } else if (val === 4) {
                $li.addClass('tabularData');
                if ($li.find('.tabularDataRow ul > li').length == 0) {
                    $li.find('.tabularDataRow .addRow').click().click().click();
                }
            }
        });
        $list.find('.sectionType').trigger('change');
        $list.on('change', '.maxLenSet', function () {
            var $li = $(this).parents('li').first();
            if ($(this).prop('checked')) {
                $li.addClass('maxLenSet').removeClass('no-maxLenSet');
            } else {
                $li.addClass('no-maxLenSet').removeClass('maxLenSet');
            }
        });
        $list.find('.maxLenSet').trigger('change');

        $('.sectionAdder').on('click', function (ev) {
            ev.preventDefault();
            var newStr = $('#sectionTemplate').html();
            newStr = newStr.replace(/#NEW#/g, 'new' + newCounter);
            var $newObj = $(newStr);
            $list.append($newObj);
            newCounter = newCounter + 1;

            $list.find('.sectionType').trigger('change');
            $list.find('.maxLenSet').trigger('change');

            var $tab = $newObj.find('.tabularDataRow ul');
            $tab.data("sortable", Sortable.create($tab[0], {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });

        var dataNewCounter = 0;
        $list.on('click', '.tabularDataRow .addRow', function (ev) {
            console.log(dataNewCounter);
            ev.preventDefault();
            var $this = $(this),
                $ul = $this.parent().find("ul"),
                $row = $($this.data('template').replace(/#NEWDATA#/g, 'new' + dataNewCounter));
            dataNewCounter = dataNewCounter + 1;
            $row.removeClass('no0').addClass('no' + $ul.children().length);
            $ul.append($row);
            $row.find('input').focus();
        });

        $list.on('click', '.tabularDataRow .delRow', function (ev) {
            var $this = $(this);
            ev.preventDefault();
            bootbox.confirm(__t('admin', 'deleteDataConfirm'), function (result) {
                if (result) {
                    $this.parents("li").first().remove();
                }
            });
        });

        $list.find('.tabularDataRow ul').each(function () {
            var $this = $(this);
            $this.data("sortable", Sortable.create(this, {
                handle: '.drag-data-handle',
                animation: 150
            }));
        });


        $('#typeDeadlineMotionsHolder').datetimepicker({
            locale: $('#typeDeadlineMotions').data('locale')
        });
        $('#typeDeadlineAmendmentsHolder').datetimepicker({
            locale: $('#typeDeadlineAmendments').data('locale')
        });
        $('#typeSupportType').on('change', function () {
            var hasSupporters = $(this).find("option:selected").data("has-supporters");
            if (hasSupporters) {
                $('#typeMinSupportersRow').removeClass("hidden");
                $('#typeAllowMoreSupporters').removeClass("hidden");
            } else {
                $('#typeMinSupportersRow').addClass("hidden");
                $('#typeAllowMoreSupporters').addClass("hidden");
            }
        }).change();

        $('.deleteTypeOpener a').on('click', function (ev) {
            ev.preventDefault();
            $('.deleteTypeForm').removeClass('hidden');
            $('.deleteTypeOpener').addClass('hidden');
        });
    };


    var agendaEdit = function () {
        var adderClasses = 'agendaItemAdder mjs-nestedSortable-no-nesting mjs-nestedSortable-disabled',
            adder = '<li class="' + adderClasses + '"><a href="#"><span class="glyphicon glyphicon-plus-sign"></span> ' +
                __t('admin', 'agendaAddEntry') + '</a></li>',
            prepareAgendaItem = function ($item) {
                $item.find('> div').prepend('<span class="glyphicon glyphicon-resize-vertical moveHandle"></span>');
                $item.find('> div > h3').append('<a href="#" class="editAgendaItem"><span class="glyphicon glyphicon-pencil"></span></a>');
                $item.find('> div > h3').append('<a href="#" class="delAgendaItem"><span class="glyphicon glyphicon-minus-sign"></span></a>');
            },
            prepareAgendaList = function ($list) {
                $list.append(adder);
            },
            showSaver = function () {
                $('#agendaEditSavingHolder').removeClass("hidden");
            },
            buildAgendaStruct = function ($ol) {
                var items = [];
                $ol.children('.agendaItem').each(function () {
                    var $li = $(this),
                        id = $li.attr('id').split('_'),
                        item = {
                            'id': id[1],
                            'code': $li.find('> div > .agendaItemEditForm input[name=code]').val(),
                            'title': $li.find('> div > .agendaItemEditForm input[name=title]').val(),
                            'motionTypeId': $li.find('> div > .agendaItemEditForm select[name=motionType]').val()
                        };
                    item.children = buildAgendaStruct($li.find('> ol'));
                    items.push(item);
                });
                return items;
            },
            $agenda = $('.motionListAgenda');

        $agenda.addClass('agendaListEditing');
        $agenda.nestedSortable({
            handle: '.moveHandle',
            items: 'li.agendaItem',
            toleranceElement: '> div',
            placeholder: 'movePlaceholder',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            helper: 'clone',
            axis: 'y',
            update: function () {
                showSaver();
                $.Antragsgruen.recalcAgendaCodes();
            }
        });
        $agenda.find('.agendaItem').each(function () {
            prepareAgendaItem($(this));
        });
        prepareAgendaList($agenda);
        $agenda.find('ol.agenda').each(function () {
            prepareAgendaList($(this));
        });

        $agenda.on('click', '.agendaItemAdder a', function (ev) {
            ev.preventDefault();
            var $newElement = $($('#agendaNewElementTemplate').val()),
                $adder = $(this).parents('.agendaItemAdder').first();
            $adder.before($newElement);
            prepareAgendaItem($newElement);
            prepareAgendaList($newElement.find('ol.agenda'));
            $newElement.find('.editAgendaItem').trigger('click');
            $newElement.find('.agendaItemEditForm input.code').focus();
            showSaver();
        });

        $agenda.on('click', '.delAgendaItem', function (ev) {
            var $this = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "agendaDelEntryConfirm"), function (result) {
                if (result) {
                    showSaver();
                    $this.parents('li.agendaItem').first().remove();
                    $.Antragsgruen.recalcAgendaCodes();
                }
            });
        });

        $agenda.on('click', '.editAgendaItem', function (ev) {
            showSaver();
            ev.preventDefault();
            var $li = $(this).parents('li.agendaItem').first();
            $li.addClass('editing');
            $li.find('> div > .agendaItemEditForm input[name=code]').focus().select();
        });

        $agenda.on('submit', '.agendaItemEditForm', function (ev) {
            showSaver();
            var $li = $(this).parents('li.agendaItem').first(),
                $form = $(this),
                newTitle = $form.find('input[name=title]').val(),
                newCode = $form.find('input[name=code]').val();
            ev.preventDefault();
            $li.removeClass('editing');
            $li.data('code', newCode);
            $li.find('> div > h3 .code').text(newCode);
            $li.find('> div > h3 .title').text(newTitle);
            $.Antragsgruen.recalcAgendaCodes();
        });

        $('#agendaEditSavingHolder').submit(function () {
            var data = buildAgendaStruct($('.motionListAgenda'));
            $(this).find('input[name=data]').val(JSON.stringify(data));
        });
    };

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
                        console.log(link);
                        $(this).attr("href", link);
                    });
                };
            $dd.find("input[type=checkbox]").change(recalcLinks)
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

    var amendmentEditInit = function () {
        var lang = $("html").attr("lang");
        $("#amendmentDateCreationHolder").datetimepicker({
            locale: lang
        });
        $("#amendmentDateResolutionHolder").datetimepicker({
            locale: lang
        });


        $("#amendmentTextEditCaller").find("button").click(function () {
            $("#amendmentTextEditCaller").addClass("hidden");
            $("#amendmentTextEditHolder").removeClass("hidden");
            $(".wysiwyg-textarea").each(function () {
                var $holder = $(this),
                    $textarea = $holder.find(".texteditor"),
                    editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
                $textarea.parents("form").submit(function () {
                    $textarea.parent().find("textarea.raw").val(editor.getData());
                    if (typeof(editor.plugins.lite) != 'undefined') {
                        editor.plugins.lite.findPlugin(editor).acceptAll();
                        $textarea.parent().find("textarea.consolidated").val(editor.getData());
                    }
                });
            });
            $("#amendmentUpdateForm").append("<input type='hidden' name='edittext' value='1'>");
        });

        $(".amendmentDeleteForm").submit(function (ev, data) {
            if (data && typeof(data.confirmed) && data.confirmed === true) {
                return;
            }
            var $form = $(this);
            ev.preventDefault();
            bootbox.confirm(__t("admin", "delAmendmentConfirm"), function (result) {
                if (result) {
                    $form.trigger("submit", {'confirmed': true});
                }
            });
        });

        motionSupporterEdit();
    };

    $.AntragsgruenAdmin = {
        'consultationSettingsForm': consultationSettingsForm,
        'motionTypeEdit': motionTypeEdit,
        'agendaEdit': agendaEdit,
        'motionListAll': motionListAll,
        'exportRowInit': exportRowInit,
        'siteAccessInit': siteAccessInit,
        'siteAccessUsersInit': siteAccessUsersInit,
        'motionEditInit': motionEditInit,
        'amendmentEditInit': amendmentEditInit
    };

}(jQuery));
