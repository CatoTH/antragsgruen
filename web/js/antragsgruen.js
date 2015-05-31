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
            allowedContent: 'b s i u;' +
            'ul ol li {list-style-type};' +
                //'table tr td th tbody thead caption [border] {margin,padding,width,height,border,border-spacing,border-collapse,align,cellspacing,cellpadding};' +
            'p blockquote {border,margin,padding,text-align};' +
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
                showWordCount: true,
                showCharCount: true,
                countHTML: false,
                countSpacesAsChars: true
            }
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
        }
        var editor = CKEDITOR.inline(id, ckeditorConfig);

        var $fieldset = $el.parents("fieldset.textarea").first();
        if ($fieldset.data("max_len") > 0) {
            var onChange = function () {
                if (ckeditor_charcount(editor.getData()) > $fieldset.data("max_len")) {
                    $el.parents("form").first().find("button[type=submit]").prop("disabled", true);
                    $fieldset.find(".max_len_hint .calm").hide();
                    $fieldset.find(".max_len_hint .alert").show();
                } else {
                    $el.parents("form").first().find("button[type=submit]").prop("disabled", false);
                    $fieldset.find(".max_len_hint .calm").show();
                    $fieldset.find(".max_len_hint .alert").hide();
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
                $editCaller.hide();
                $textHolder.attr('contenteditable', true);

                editor = CKEDITOR.inline($textHolder.attr('id'), {
                    scayt_sLang: 'de_DE'
                });

                $textHolder.focus();
                $textSaver.show();
            });
            $textSaver.hide();
            $textSaver.find('button').click(function (ev) {
                ev.preventDefault();

                $.post($form.attr('action'), {
                    'data': editor.getData(),
                    '_csrf': $form.find('> input[name=_csrf]').val()
                }, function (ret) {
                    if (ret == '1') {
                        $textSaver.hide();
                        editor.destroy();
                        $textHolder.attr('contenteditable', false);
                        $editCaller.show();
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
            $this.hide();
            $this.parent().find('.hider').show();
            $this.parents('.paragraph').first().find('.commentForm, .motionComment').show();
            ev.preventDefault();
        });

        $paragraphs.find('.comment .hider').click(function (ev) {
            var $this = $(this);
            $this.hide();
            $this.parent().find('.shower').show();

            $this.parents('.paragraph').first().find('.commentForm, .motionComment').hide();
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
            });
        });


        $('.tagAdderHolder').click(function (ev) {
            ev.preventDefault();
            $(this).hide();
            $('#tagAdderForm').show();
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
            $adderRow = $supporterData.find('.adderRow');

        $('#personTypeNatural, #personTypeOrga').on('click change', function () {
            if ($('#personTypeOrga').prop('checked')) {
                $('.initiatorData .organizationRow').show();
                $('.supporterData, .supporterDataHead').hide();
            } else {
                $('.initiatorData .organizationRow').hide();
                $('.supporterData, .supporterDataHead').show();
            }
        }).first().trigger('change');
        $adderRow.find('a').click(function (ev) {
            ev.preventDefault();
            var $newEl = $($('#newSupporterTemplate').data('html'));
            $adderRow.before($newEl);
        });

        $('.fullTextAdder a').click(function (ev) {
            ev.preventDefault();
            $(this).parent().hide();
            $('#fullTextHolder').show();
        });
        $('.fullTextAdd').click(function () {
            var lines = $fullTextHolder.find('textarea').val().split("\n"),
                template = $('#newSupporterTemplate').data('html');
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                var $newEl = $(template);
                if ($newEl.find('input.organization').length > 0) {
                    var parts = lines[i].split(';');
                    $newEl.find('input.name').val(parts[0].trim());
                    if (parts.length > 1) {
                        $newEl.find('input.organization').val(parts[1].trim());
                    }
                } else {
                    $newEl.find('input.name').val(lines[i]);
                }
                $adderRow.before($newEl);
                $fullTextHolder.find('textarea').select().focus();
            }
        });
        $supporterData.find('.supporterRow .rowDeleter').click(function (ev) {
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
                    $adderRow.before($newEl);
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
                $adderRow.prev().find('input.name, input.organization').last().focus();
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
