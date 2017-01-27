/*global browser: true, regexp: true, localStorage */
/*global $, jQuery, alert, console, CKEDITOR, document, Intl, JSON, ANTRAGSGRUEN_STRINGS */
/*jslint regexp: true*/

function __t(category, str) {
    if (typeof(ANTRAGSGRUEN_STRINGS) == "undefined") {
        return '@TRANSLATION STRINGS NOT LOADED';
    }
    if (typeof(ANTRAGSGRUEN_STRINGS[category]) == "undefined") {
        return "@UNKNOWN CATEGORY: " + category
    }
    if (typeof(ANTRAGSGRUEN_STRINGS[category][str]) == "undefined") {
        return "@UNKNOWN STRING: " + category + " / " + str;
    }
    return ANTRAGSGRUEN_STRINGS[category][str];
}

(function ($) {
    "use strict";


// From ckeditor/plugins/wordcount/plugin.js
    function ckeditor_strip(html) {
        var tmp = document.createElement("div");
        tmp.innerHTML = html;

        if (tmp.textContent == '' && typeof tmp.innerText == 'undefined') {
            return '';
        }

        return tmp.textContent || tmp.innerText;
    }

    function ckeditor_charcount(text) {
        var normalizedText = text.replace(/(\r\n|\n|\r)/gm, "").replace(/^\s+|\s+$/g, "").replace("&nbsp;", "");
        normalizedText = ckeditor_strip(normalizedText).replace(/^([\s\t\r\n]*)$/, "");

        return normalizedText.length;
    }

    function ckeditorInit(id) {
        var $el = $("#" + id),
            initialized = $el.data("ckeditor_initialized"),
            allowedContent;
        if (typeof (initialized) != "undefined" && initialized) {
            return;
        }
        $el.data("ckeditor_initialized", "1");
        $el.attr("contenteditable", true);

        var ckeditorConfig = {
            coreStyles_strike: {
                element: 'span',
                attributes: {'class': 'strike'},
                overrides: 'strike'
            },
            coreStyles_underline: {
                element: 'span',
                attributes: {'class': 'underline'}
            },
            toolbarGroups: [
                {name: 'tools'},
                {name: 'document', groups: ['mode', 'document', 'doctools']},
                //{name: 'clipboard', groups: ['clipboard', 'undo']},
                //{name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                //{name: 'forms'},
                {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
                {name: 'links'},
                {name: 'insert'},
                {name: 'styles'},
                {name: 'colors'},
                {name: 'others'}
            ],
            removePlugins: 'stylescombo,save,showblocks,specialchar,about,preview,pastetext',
            extraPlugins: 'tabletools',
            scayt_sLang: 'de_DE',
            title: $el.attr("title")
        };

        var strikeEl = ($el.data("no-strike") == '1' ? '' : ' s'),
            strikeClass = ($el.data("no-strike") == '1' ? '' : ',strike');

        if ($el.data('track-changed') == '1' || $el.data('allow-diff-formattings') == '1') {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'h2 h3 h4;' +
                'ul ol li [data-*](ice-ins,ice-del,ice-cts,appendHint){list-style-type};' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'div [data-*](collidingParagraph,paragraphHolder,hasCollissions);' +
                'p blockquote [data-*](ice-ins,ice-del,ice-cts,appendHint,collidingParagraphHead){border,margin,padding};' +
                'span[data-*](ice-ins,ice-del,ice-cts,appendHint,underline' + strikeClass + ',subscript,superscript);' +
                'a[href,data-*](ice-ins,ice-del,ice-cts,appendHint);' +
                'br ins del[data-*](ice-ins,ice-del,ice-cts,appendHint);';
        } else {
            allowedContent = 'strong' + strikeEl + ' em u sub sup;' +
                'ul ol li {list-style-type};' +
                'h2 h3 h4;' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline' + strikeClass + ',subscript,superscript);' +
                'a[href];';
        }

        if ($el.data('track-changed') == '1') {
            ckeditorConfig.extraPlugins += ',lite';
            ckeditorConfig.lite = {tooltips: false};
        } else {
            ckeditorConfig.removePlugins += ',lite';
        }
        if ($el.data('enter-mode') == 'br') {
            ckeditorConfig.enterMode = CKEDITOR.ENTER_BR;
        } else {
            ckeditorConfig.enterMode = CKEDITOR.ENTER_P;
        }
        ckeditorConfig.allowedContent = allowedContent;
        // ckeditorConfig.pasteFilter = allowedContent; // Seems to break copy/pasting some <strong> formatting in 4.5.11

        var editor = CKEDITOR.inline(id, ckeditorConfig);

        var $fieldset = $el.parents(".wysiwyg-textarea").first();
        if ($fieldset.data("max-len") != 0) {
            var maxLen = $fieldset.data("max-len"),
                maxLenSoft = false,
                $warning = $fieldset.find('.maxLenTooLong'),
                $submit = $el.parents("form").first().find("button[type=submit]"),
                $currCounter = $fieldset.find(".maxLenHint .counter");
            if (maxLen < 0) {
                maxLenSoft = true;
                maxLen = -1 * maxLen;
            }

            var onChange = function () {
                var currLen = ckeditor_charcount(editor.getData());
                $currCounter.text(currLen);
                if (currLen > maxLen) {
                    $warning.removeClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", true);
                    }
                } else {
                    $warning.addClass('hidden');
                    if (!maxLenSoft) {
                        $submit.prop("disabled", false);
                    }
                }
            };
            editor.on('change', onChange);
            onChange();
        }

        return editor;
    }


    $.AntragsgruenCKEDITOR = {
        "init": ckeditorInit
    };
}(jQuery));

(function ($) {
    "use strict";

    var $html = $('html');

    var amendmentEditFormSinglePara = function () {
        var $paragraphs = $(".wysiwyg-textarea.single-paragraph");

        var setModifyable = function () {
            var $modified = $paragraphs.filter(".modified");
            if ($modified.length == 0) {
                $paragraphs.addClass('modifyable');
            } else {
                $paragraphs.removeClass('modifyable');
                $('input[name=modifiedParagraphNo]').val($modified.data("paragraph-no"));
                $('input[name=modifiedSectionId]').val($modified.parents(".texteditorBox").data("section-id"));
            }
        };
        $paragraphs.click(function () {
            var $para = $(this);
            if ($para.hasClass('modifyable')) {
                $para.addClass('modified');
                setModifyable();

                var $textarea = $para.find(".texteditor"),
                    editor;
                if (typeof(CKEDITOR.instances[$textarea.attr("id")]) !== "undefined") {
                    editor = CKEDITOR.instances[$textarea.attr("id")];
                } else {
                    editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
                }
                $textarea.attr("contenteditable", "true");
                $textarea.parents("form").submit(function () {
                    $textarea.parent().find("textarea.raw").val(editor.getData());
                    if (typeof(editor.plugins.lite) != 'undefined') {
                        editor.plugins.lite.findPlugin(editor).acceptAll();
                        $textarea.parent().find("textarea.consolidated").val(editor.getData());
                    }
                });
                $textarea.focus();
            }
        });
        $paragraphs.find(".modifiedActions .revert").click(function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var $para = $(this).parents(".wysiwyg-textarea"),
                $textarea = $para.find(".texteditor"),
                id = $textarea.attr("id");
            $("#" + id).attr("contenteditable", "false");
            $textarea.html($para.data("original"));
            $para.removeClass("modified");
            setModifyable();
        });
        setModifyable();

        // Amendment Reason
        $(".wysiwyg-textarea").filter(":not(.single-paragraph)").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor");
            if ($holder.hasClass("hidden")) {
                return;
            }
            var editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(editor.getData());
                if (typeof(editor.plugins.lite) != 'undefined') {
                    editor.plugins.lite.findPlugin(editor).acceptAll();
                    $textarea.parent().find("textarea.consolidated").val(editor.getData());
                }
            });
        });

        $(".texteditorBox").each(function () {
            var $this = $(this),
                sectionId = $this.data("section-id"),
                paraNo = $this.data("changed-para-no");
            if (paraNo > -1) {
                $("#section_holder_" + sectionId + "_" + paraNo).click();
            }
        });
    };

    var amendmentEditForm = function (multipleParagraphs) {
        var lang = $html.attr('lang'),
            $opener = $(".editorialChange .opener");

        $(".input-group.date").datetimepicker({
            locale: lang,
            format: 'L'
        });
        $opener.click(function (ev) {
            ev.preventDefault();
            var $holder = $(".editorialChange"),
                $textarea = $holder.find(".texteditor");
            $(this).addClass("hidden");
            $("#section_holder_editorial").removeClass("hidden");
            var editor = $.AntragsgruenCKEDITOR.init("amendmentEditorial_wysiwyg");
            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea.raw").val(editor.getData());
            });
        });

        if ($("#amendmentEditorial").val() != '') {
            $opener.click();
        }
        if (multipleParagraphs) {
            amendmentEditFormMultiPara();
        } else {
            amendmentEditFormSinglePara();
        }
    };



    var defaultInitiatorForm = function () {
        var $fullTextHolder = $('#fullTextHolder'),
            $supporterData = $('.supporterData'),
            $supporterAdderRow = $supporterData.find('.adderRow'),
            $initiatorData = $('.initiatorData'),
            $initiatorAdderRow = $initiatorData.find('.adderRow');

        $('#personTypeNatural, #personTypeOrga').on('click change', function () {
            if ($('#personTypeOrga').prop('checked')) {
                $initiatorData.find('.organizationRow').addClass("hidden");
                $initiatorData.find('.contactNameRow').removeClass("hidden");
                $initiatorData.find('.resolutionRow').removeClass("hidden");
                $initiatorData.find('.adderRow').addClass("hidden");
                $('.supporterData, .supporterDataHead').addClass("hidden");
            } else {
                $initiatorData.find('.organizationRow').removeClass("hidden");
                $initiatorData.find('.contactNameRow').addClass("hidden");
                $initiatorData.find('.resolutionRow').addClass("hidden");
                $initiatorData.find('.adderRow').removeClass("hidden");
                $('.supporterData, .supporterDataHead').removeClass("hidden");
            }
        }).first().trigger('change');

        $initiatorAdderRow.find('a').click(function (ev) {
            ev.preventDefault();
            var $newEl = $($('#newInitiatorTemplate').data('html'));
            $initiatorAdderRow.before($newEl);
        });
        $initiatorData.on('click', '.initiatorRow .rowDeleter', function (ev) {
            ev.preventDefault();
            $(this).parents('.initiatorRow').remove();
        });


        $supporterAdderRow.find('a').click(function (ev) {
            ev.preventDefault();
            var $newEl = $($('#newSupporterTemplate').data('html'));
            $supporterAdderRow.before($newEl);
        });

        $('.fullTextAdder a').click(function (ev) {
            ev.preventDefault();
            $(this).parent().addClass("hidden");
            $('#fullTextHolder').removeClass("hidden");
        });
        $('.fullTextAdd').click(function () {
            var lines = $fullTextHolder.find('textarea').val().split(";"),
                template = $('#newSupporterTemplate').data('html'),
                getNewElement = function () {
                    var $rows = $supporterData.find(".supporterRow");
                    for (var i = 0; i < $rows.length; i++) {
                        var $row = $rows.eq(i);
                        if ($row.find(".name").val() == '' && $row.find(".organization").val() == '') return $row;
                    }
                    // No empty row found
                    var $newEl = $(template);
                    if ($supporterAdderRow.length > 0) {
                        $supporterAdderRow.before($newEl);
                    } else {
                        $('.fullTextAdder').before($newEl);
                    }
                    return $newEl;
                };
            var $firstAffectedRow = null;
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                var $newEl = getNewElement();
                if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
                if ($newEl.find('input.organization').length > 0) {
                    var parts = lines[i].split(',');
                    $newEl.find('input.name').val(parts[0].trim());
                    if (parts.length > 1) {
                        $newEl.find('input.organization').val(parts[1].trim());
                    }
                } else {
                    $newEl.find('input.name').val(lines[i]);
                }
            }
            $fullTextHolder.find('textarea').select().focus();
            $firstAffectedRow.scrollintoview();
        });
        $supporterData.on('click', '.supporterRow .rowDeleter', function (ev) {
            ev.preventDefault();
            $(this).parents('.supporterRow').remove();
        });
        $supporterData.on('keydown', ' .supporterRow input[type=text]', function (ev) {
            var $row;
            if (ev.keyCode == 13) { // Enter
                ev.preventDefault();
                ev.stopPropagation();
                $row = $(this).parents('.supporterRow');
                if ($row.next().hasClass('adderRow')) {
                    var $newEl = $($('#newSupporterTemplate').data('html'));
                    $supporterAdderRow.before($newEl);
                    $newEl.find('input[type=text]').first().focus();
                } else {
                    $row.next().find('input[type=text]').first().focus();
                }
            } else if (ev.keyCode == 8) { // Backspace
                $row = $(this).parents('.supporterRow');
                if ($row.find('input.name').val() != '') {
                    return;
                }
                if ($row.find('input.organization').val() != '') {
                    return;
                }
                $row.remove();
                $supporterAdderRow.prev().find('input.name, input.organization').last().focus();
            }
        });

        var $editforms = $('#motionEditForm, #amendmentEditForm');

        if ($supporterData.length > 0 && $supporterData.data('min-supporters') > 0) {
            $editforms.submit(function (ev) {
                if ($('#personTypeOrga').prop('checked')) {
                    return;
                }
                var found = 0;
                $supporterData.find('.supporterRow').each(function () {
                    if ($(this).find('input.name').val().trim() != '') {
                        found++;
                    }
                });
                if (found < $supporterData.data('min-supporters')) {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "min_x_supporter").replace(/%NUM%/, $supporterData.data('min-supporters')));
                }
            });
        }

        $editforms.submit(function (ev) {
            if ($('#personTypeOrga').prop('checked')) {
                if ($('#resolutionDate').val() == '') {
                    ev.preventDefault();
                    bootbox.alert(__t("std", "missing_resolution_date"));
                }
            }
        });
    };

    // Needs to be synchronized with CunsultationAgendaItem:getSortedFromConsultation
    var recalcAgendaCodes = function () {
        var recalcAgendaNode = function ($ol) {
                var currNumber = '0.',
                    $lis = $ol.find('> li.agendaItem');
                $lis.each(function () {
                    var $li = $(this),
                        code = $li.data('code'),
                        currStr = '',
                        $subitems = $li.find('> ol');
                    if (code == '#') {
                        var parts = currNumber.split('.'),
                            matches = parts[0].match(/^(.*[^0-9])?([0-9]*)$/),
                            nonNumeric = (typeof(matches[1]) == 'undefined' ? '' : matches[1]),
                            numeric = (matches[2] == '' ? 1 : matches[2]);
                        parts[0] = nonNumeric + ++numeric;
                        currNumber = currStr = parts.join('.');
                    } else {
                        currStr = currNumber = code;
                    }

                    $li.find('> div > h3 .code').text(currStr);
                    if ($subitems.length > 0) {
                        recalcAgendaNode($subitems);
                    }
                });
            },
            $root = $('ol.motionListAgenda');
        recalcAgendaNode($root);
    };

    $.Antragsgruen = {
        'amendmentEditForm': amendmentEditForm,
        'amendmentEditFormSinglePara': amendmentEditFormSinglePara,
        'defaultInitiatorForm': defaultInitiatorForm,
        'recalcAgendaCodes': recalcAgendaCodes
    };
}(jQuery));
