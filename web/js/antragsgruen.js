/*global browser: true, regexp: true, localStorage */
/*global $, jQuery, alert, console, CKEDITOR, document, Intl */
/*jslint regexp: true*/

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
        var normalizedText = text.
            replace(/(\r\n|\n|\r)/gm, "").
            replace(/^\s+|\s+$/g, "").
            replace("&nbsp;", "");
        normalizedText = ckeditor_strip(normalizedText).replace(/^([\s\t\r\n]*)$/, "");

        return normalizedText.length;
    }

    function ckeditorInit(id) {

        var $el = $("#" + id),
            initialized = $el.data("ckeditor_initialized");
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
            removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,bbcode',
            extraPlugins: 'autogrow,wordcount,tabletools',
            scayt_sLang: 'de_DE',
            autoGrow_bottomSpace: 20,
            // Whether or not you want to show the Word Count
            wordcount: {
                showWordCount: false,
                showCharCount: false,
                showParagraphs: false,
                countHTML: false,
                countSpacesAsChars: true
            },
            title: $el.attr("title")
        };

        if ($el.data('track-changed') == '1') {
            ckeditorConfig['extraPlugins'] += ',lite';
            ckeditorConfig['allowedContent'] = 'strong s em u sub sup;' +
                'ul ol li [data-*](ice-ins,ice-del,ice-cts){list-style-type};' +
                    //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote [data-*](ice-ins,ice-del,ice-cts){border,margin,padding};' +
                'span[data-*](ice-ins,ice-del,ice-cts,underline,strike,subscript,superscript);' +
                'a[href,data-*](ice-ins,ice-del,ice-cts);' +
                'br ins del[data-*](ice-ins,ice-del,ice-cts);';
        } else {
            ckeditorConfig['removePlugins'] += ',lite';
            ckeditorConfig['allowedContent'] = 'strong s em u sub sup;' +
                'ul ol li {list-style-type};' +
                    //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
                'p blockquote {border,margin,padding};' +
                'span(underline,strike,subscript,superscript);' +
                'a[href];';
        }
        var editor = CKEDITOR.inline(id, ckeditorConfig);

        var $fieldset = $el.parents(".wysiwyg-textarea").first();
        if ($fieldset.data("maxlen") != 0) {
            var maxLen = $fieldset.data("maxlen"),
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

    var draftSavingEngine = function (keyBase) {
        if (!$('html').hasClass("localstorage")) {
            return;
        }

        var $draftHint = $("#draftHint"),
            $form = $("form.draftForm"),
            localKey = keyBase + "_" + Math.floor(Math.random() * 1000000),
            key;

        $form.append('<input type="hidden" name="draftId" value="' + localKey + '">');

        for (key in localStorage) if (localStorage.hasOwnProperty(key)) {
            if (key.indexOf(keyBase + "_") == 0) {
                var data = JSON.parse(localStorage.getItem(key)),
                    lastEdit = new Date(data['lastEdit']),
                    $link = $("<li><a href='#' class='restore'></a> <a href='#' class='delete glyphicon glyphicon-trash'></a></li>");


                $link.data("key", key);
                var dateStr = new Intl.DateTimeFormat($("html").attr("lang"), {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    hour: 'numeric', minute: 'numeric'
                }).format(lastEdit);
                $link.find('.restore').text('Entwurf vom: ' + dateStr).click(function (ev) {
                    ev.preventDefault();
                    if (!confirm("Diesen Entwurf wiederherstellen?")) {
                        return;
                    }
                    var inst,
                        restoreKey = $(this).parents("li").first().data("key"),
                        data = JSON.parse(localStorage.getItem(restoreKey));
                    for (inst in CKEDITOR.instances) {
                        if (CKEDITOR.instances.hasOwnProperty(inst)) {
                            if (typeof(data[inst]) != "undefined") {
                                CKEDITOR.instances[inst].setData(data[inst]);
                            }
                        }
                    }
                    $(".form-group.plain-text").each(function () {
                        var $input = $(this).find("input[type=text]");
                        if (typeof(data[$input.attr("id")]) != "undefined") {
                            $input.val(data[$input.attr("id")]);
                        }
                    });
                    $form.find("input[name=draftId]").remove();
                    $form.append('<input type="hidden" name="draftId" value="' + restoreKey + '">');

                    localKey = restoreKey;
                    $(this).parents("li").first().remove();
                    if ($draftHint.find("ul").children().length == 0) {
                        $draftHint.addClass("hidden");
                    }
                });
                $link.find('.delete').click(function (ev) {
                    ev.preventDefault();
                    if (confirm("Entwurf wirklich löschen?")) {
                        localStorage.removeItem($(this).parents("li").first().data("key"));
                        $(this).parents("li").first().remove();
                        if ($draftHint.find("ul").children().length == 0) {
                            $draftHint.addClass("hidden");
                        }
                    }
                });
                $draftHint.find("ul").append($link);
                $draftHint.removeClass("hidden");
            }
        }

        window.setTimeout(function () {
            for (var inst in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(inst)) {
                    $("#" + inst).data("original", CKEDITOR.instances[inst].getData());
                }
            }
            $(".form-group.plain-text").each(function () {
                var $input = $(this).find("input[type=text]");
                $input.data("original", $input.val());
            });
        }, 2000);

        window.setInterval(function () {
            var data = {},
                foundChanged = false,
                inst;

            for (inst in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(inst)) {
                    var dat = CKEDITOR.instances[inst].getData();
                    data[inst] = dat;
                    if (dat != $("#" + inst).data("original")) {
                        foundChanged = true;
                    }
                }
            }
            $(".form-group.plain-text").each(function () {
                var $input = $(this).find("input[type=text]");
                data[$input.attr("id")] = $input.val();
                if ($input.val() != $input.data("original")) {
                    foundChanged = true;
                }
            });

            if (foundChanged) {
                data['lastEdit'] = new Date().getTime();
                localStorage.setItem(localKey, JSON.stringify(data));
            } else {
                localStorage.removeItem(localKey);
            }
        }, 3000);
    };

    var motionEditForm = function () {
        var lang = $('html').attr('lang');
        $(".input-group.date").datetimepicker({
            locale: lang,
            format: 'L'
        });
        $(".wysiwyg-textarea").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor"),
                editor = $.AntragsgruenCKEDITOR.init($textarea.attr("id"));

            $textarea.parents("form").submit(function () {
                $textarea.parent().find("textarea").val(editor.getData());
            });
        });
        $(".form-group.plain-text").each(function () {
            var $fieldset = $(this),
                $input = $fieldset.find("input.form-control");
            if ($fieldset.data("maxlen") != 0) {
                var maxLen = $fieldset.data("maxlen"),
                    maxLenSoft = false,
                    $warning = $fieldset.find('.maxLenTooLong'),
                    $submit = $fieldset.parents("form").first().find("button[type=submit]"),
                    $currCounter = $fieldset.find(".maxLenHint .counter");
                if (maxLen < 0) {
                    maxLenSoft = true;
                    maxLen = -1 * maxLen;
                }

                var onChange = function () {
                    var currLen = $input.val().length;
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
                $input.on('keyup change', onChange);
                onChange();
            }
        });

        var $draftHint = $("#draftHint"),
            draftMotionType = $draftHint.data("motion-type"),
            draftMotionId = $draftHint.data("motion-id");
        draftSavingEngine("motion_" + draftMotionType + "_" + draftMotionId);
    };

    var amendmentEditForm = function () {
        var lang = $('html').attr('lang');
        $(".input-group.date").datetimepicker({
            locale: lang,
            format: 'L'
        });
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

        var $draftHint = $("#draftHint"),
            draftMotionId = $draftHint.data("motion-id"),
            draftAmendmentId = $draftHint.data("amendment-id");
        draftSavingEngine("amendment_" + draftMotionId + "_" + draftAmendmentId);
    };


    var contentPageEdit = function () {
        $('.contentPage').each(function () {
            var $this = $(this),
                $form = $this.find('> form'),
                $editCaller = $this.find('> .editCaller'),
                $textHolder = $form.find('> .textHolder'),
                $textSaver = $form.find('> .textSaver'),
                editor = null;

            $editCaller.click(function (ev) {
                ev.preventDefault();
                $editCaller.addClass("hidden");
                $textHolder.attr('contenteditable', true);

                editor = CKEDITOR.inline($textHolder.attr('id'), {
                    scayt_sLang: 'de_DE'
                });

                $textHolder.focus();
                $textSaver.removeClass("hidden");
            });
            $textSaver.addClass("hidden");
            $textSaver.find('button').click(function (ev) {
                ev.preventDefault();

                $.post($form.attr('action'), {
                    'data': editor.getData(),
                    '_csrf': $form.find('> input[name=_csrf]').val()
                }, function (ret) {
                    if (ret == '1') {
                        $textSaver.addClass("hidden");
                        editor.destroy();
                        $textHolder.attr('contenteditable', false);
                        $editCaller.removeClass("hidden");
                    } else {
                        alert('Something went wrong...');
                    }
                })
            });
        });
    };

    var motionShow = function () {
        var $paragraphs = $('.motionTextHolder .paragraph');
        $paragraphs.find('.comment .shower').click(function (ev) {
            ev.preventDefault();
            var $this = $(this),
                $commentHolder = $this.parents('.paragraph').first().find('.commentHolder');
            $this.addClass("hidden");
            $this.parent().find('.hider').removeClass("hidden");
            $commentHolder.removeClass("hidden");
            if (!$commentHolder.isOnScreen(0.1, 0.1)) {
                $commentHolder.scrollintoview({top_offset: -100});
            }
        });

        $paragraphs.find('.comment .hider').click(function (ev) {
            var $this = $(this);
            $this.addClass("hidden");
            $this.parent().find('.shower').removeClass("hidden");

            $this.parents('.paragraph').first().find('.commentHolder').addClass("hidden");
            ev.preventDefault();
        });

        $paragraphs.filter('.commentsOpened').find('.comment .shower').click();
        $paragraphs.filter(':not(.commentsOpened)').find('.comment .hider').click();

        $paragraphs.each(function () {
            var $paragraph = $(this),
                $paraFirstLine = $paragraph.find(".lineNumber").first(),
                lineHeight = $paraFirstLine.height();

            var amends = $paragraph.find(".bookmarks > .amendment");
            amends = amends.sort(function (el1, el2) {
                return $(el1).data("first-line") - $(el2).data("first-line");
            });
            $paragraph.find(".bookmarks").append(amends);

            $paragraph.find('ul.bookmarks li.amendment').each(function () {
                var $amendment = $(this),
                    firstLine = $amendment.data("first-line"),
                    targetOffset = (firstLine - $paraFirstLine.data("line-number")) * lineHeight,
                    $prevBookmark = $amendment.prev(),
                    delta = 0;
                if ($prevBookmark.length > 0) {
                    delta = targetOffset - ($prevBookmark.height() + 7);
                    if (delta < 0) delta = 0;
                }
                $amendment.css('margin-top', delta + "px");

                $amendment.mouseover(function () {
                    $paragraph.find("> .textOrig").addClass("hidden");
                    $paragraph.find("> .textAmendment").addClass("hidden");
                    $paragraph.find("> .textAmendment.amendment" + $amendment.find("a").data("id")).removeClass("hidden");
                }).mouseout(function () {
                    $paragraph.find("> .textOrig").removeClass("hidden");
                    $paragraph.find("> .textAmendment").addClass("hidden");
                });
            });
        });


        $('.tagAdderHolder').click(function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $('#tagAdderForm').removeClass("hidden");
        });

        var s = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        $("form.delLink").submit(function (ev) {
            ev.preventDefault();
            var form = this;
            bootbox.confirm("Wirklich löschen?", function (result) {
                if (result) {
                    form.submit();
                }
            });
        });
    };

    var amendmentShow = function () {
        var s = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        $("form.delLink").submit(function (ev) {
            ev.preventDefault();
            var form = this;
            bootbox.confirm("Wirklich löschen?", function (result) {
                if (result) {
                    form.submit();
                }
            });
        });
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
                $initiatorData.find('.resolutionRow').removeClass("hidden");
                $initiatorData.find('.adderRow').addClass("hidden");
                $('.supporterData, .supporterDataHead').addClass("hidden");
            } else {
                $initiatorData.find('.organizationRow').removeClass("hidden");
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
                    $supporterAdderRow.before($newEl);
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

        if ($supporterData.length > 0 && $supporterData.data('min-supporters') > 0) {
            $('#motionEditForm, #amendmentEditForm').submit(function (ev) {
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
                    bootbox.alert('Es müssen mindestens %num% UnterstützerInnen angegeben werden.'.replace(/%num%/, $supporterData.data('min-supporters')));
                }
            });
        }

        $('#motionEditForm, #amendmentEditForm').submit(function (ev) {
            if ($('#personTypeOrga').prop('checked')) {
                if ($('#resolutionDate').val() == '') {
                    ev.preventDefault();
                    bootbox.alert('Es muss ein Beschlussdatum angegeben werden.');
                }
            }
        });
    };

    var loginForm = function () {
        var $form = $("#usernamePasswordForm"),
            pwMinLen = $("#passwordInput").data("min-len");
        $form.find("input[name=createAccount]").change(function () {
            if ($(this).prop("checked")) {
                $("#pwdConfirm").removeClass('hidden');
                $("#regName").removeClass('hidden').find("input").attr("required", "required");
                $("#passwordInput").attr("placeholder", "Min. " + pwMinLen + " Zeichen");
                $("#create_str").removeClass('hidden');
                $("#login_str").addClass('hidden');
            } else {
                $("#pwdConfirm").addClass('hidden');
                $("#regName").addClass('hidden').find("input").removeAttr("required");
                $("#passwordInput").attr("placeholder", "");
                $("#createStr").addClass('hidden');
                $("#loginStr").removeClass('hidden');
            }
        }).trigger("change");
        $form.submit(function (ev) {
            var pwd = $("#passwordInput").val();
            if (pwd.length < pwMinLen) {
                ev.preventDefault();
                bootbox.alert('Das Passwort muss mindestens ' + pwMinLen + ' Buchstaben haben.');
            }
            if ($form.find("input[name=createAccount]").prop("checked")) {
                if (pwd != $("#passwordConfirm").val()) {
                    ev.preventDefault();
                    bootbox.alert('Die beiden Passwörter stimmen nicht überein.');
                }
            }
        });
    };

    var accountEdit = function () {
        var pwMinLen = $("#userPwd").data("min-len");

        $('.accountDeleteForm input[name=accountDeleteConfirm]').change(function () {
            if ($(this).prop("checked")) {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", false);
            } else {
                $(".accountDeleteForm button[name=accountDelete]").prop("disabled", true);
            }
        }).trigger('change');

        $('.userAccountForm').submit(function (ev) {
            if ($("#userPwd").val() != '' || $("#userPwd2").val() != '') {
                if ($("#userPwd").val().length < pwMinLen) {
                    ev.preventDefault();
                    bootbox.alert('Das Passwort muss mindestens ' + pwMinLen + ' Buchstaben haben.');
                } else if ($("#userPwd").val() != $("#userPwd2").val()) {
                    ev.preventDefault();
                    bootbox.alert('Die beiden Passwörter stimmen nicht überein.');
                }
            }
        });
    };

    $.Antragsgruen = {
        'loginForm': loginForm,
        'motionShow': motionShow,
        'amendmentShow': amendmentShow,
        'motionEditForm': motionEditForm,
        'amendmentEditForm': amendmentEditForm,
        'contentPageEdit': contentPageEdit,
        'defaultInitiatorForm': defaultInitiatorForm,
        'accountEdit': accountEdit
    };

    $(".jsProtectionHint").remove();

}(jQuery));
