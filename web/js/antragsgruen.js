/*global browser: true, regexp: true */
/*global $, jQuery, alert, console, CKEDITOR, document */
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
        if (typeof (initialized) != "undefined" && initialized) return;
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
            allowedContent: 'strong s em u;' +
            'ul ol li {list-style-type};' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
            'p blockquote {border,margin,padding,text-align};' +
            'span(underline,strike,subscript,superscript);' +
            'a[href];',
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
            /*,
             on: {
             instanceReady: function(ev) {
             // Resize the window
             window.setTimeout(function() {
             ev.editor.fire('contentDom');
             }, 1);
             }
             }
             */
        };

        if ($el.data('track-changed') == '1') {
            ckeditorConfig['extraPlugins'] += ',lite';
        } else {
            ckeditorConfig['removePlugins'] += ',lite';
        }
        var editor = CKEDITOR.inline(id, ckeditorConfig);

        /* @TODO
         var $fieldset = $el.parents("fieldset.textarea").first();
        if ($fieldset.data("max_len") > 0) {
            var onChange = function () {
                if (ckeditor_charcount(editor.getData()) > $fieldset.data("max_len")) {
                    $el.parents("form").first().find("button[type=submit]").prop("disabled", true);
                    $fieldset.find(".max_len_hint .calm").addClass("hidden");
                    $fieldset.find(".max_len_hint .alert").removeClass("hidden");
                } else {
                    $el.parents("form").first().find("button[type=submit]").prop("disabled", false);
                    $fieldset.find(".max_len_hint .calm").removeClass("hidden");
                    $fieldset.find(".max_len_hint .alert").addClass("hidden");
                }
            };
            editor.on('change', onChange);
            onChange();
        }
        */

        return editor;
    }


    $.AntragsgruenCKEDITOR = {
        "init": ckeditorInit
    };
}(jQuery));

(function ($) {
    "use strict";


    var motionEditForm = function () {
        // @TODO Prevent accidental leaving of page once something is entered

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
    };

    var amendmentEditForm = function () {
        // @TODO Prevent accidental leaving of page once something is entered

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
            var $this = $(this);
            $this.addClass("hidden");
            $this.parent().find('.hider').removeClass("hidden");
            $this.parents('.paragraph').first().find('.commentForm, .motionComment').removeClass("hidden");
            ev.preventDefault();
        });

        $paragraphs.find('.comment .hider').click(function (ev) {
            var $this = $(this);
            $this.addClass("hidden");
            $this.parent().find('.shower').removeClass("hidden");

            $this.parents('.paragraph').first().find('.commentForm, .motionComment').addClass("hidden")
            ev.preventDefault();
        });

        $paragraphs.filter('.commentsOpened').find('.comment .shower').click();
        $paragraphs.filter(':not(.commentsOpened)').find('.comment .hider').click();

        $paragraphs.each(function () {
            var $paragraph = $(this);
            $paragraph.find('ul.bookmarks li.amendment').each(function () {
                var $amendment = $(this),
                    marker_offset = $amendment.offset().top,
                    first_line = $amendment.data("first-line"),
                    $lineel = $paragraph.find(".lineNumber[data-line-number=" + first_line + "]");
                if ($lineel.length == 0) {
                    // Ergänzung am Ende des Absatzes
                    $lineel = $paragraph.find(".lineNumber").last();
                }
                var lineel_offset = $lineel.offset().top;
                if ((marker_offset + 10) < lineel_offset) {
                    $amendment.css('margin-top', (lineel_offset - (marker_offset + 10)) + "px");
                }

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
                $initiatorData.find('.organizationRow').removeClass("hidden");
                $initiatorData.find('.adderRow').addClass("hidden")
                $('.supporterData, .supporterDataHead').addClass("hidden");
            } else {
                $initiatorData.find('.organizationRow').addClass("hidden");
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
            var lines = $fullTextHolder.find('textarea').val().split("\n"),
                template = $('#newSupporterTemplate').data('html'),
                getNewElement = function() {
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
                    var parts = lines[i].split(';');
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
            $('#motionEditForm').submit(function (ev) {
                var found = 0;
                $supporterData.find('.supporterRow').each(function () {
                    if ($(this).find('input.name').val().trim() != '') {
                        found++;
                    }
                });
                if (found < $supporterData.data('min-supporters')) {
                    ev.preventDefault();
                    bootbox.alert('Es müssen mindestens %num% UnterstützerInnen angegeben werden'.replace(/%num%/, $supporterData.data('min-supporters')));
                }
            });
        }
    };

    $.Antragsgruen = {
        'motionShow': motionShow,
        'amendmentShow': amendmentShow,
        'motionEditForm': motionEditForm,
        'amendmentEditForm': amendmentEditForm,
        'contentPageEdit': contentPageEdit,
        'defaultInitiatorForm': defaultInitiatorForm
    };

    $(".jsProtectionHint").remove();

}(jQuery));
