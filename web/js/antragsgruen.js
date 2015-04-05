/*global browser: true, regexp: true */
/*global $, jQuery, alert, console, CKEDITOR, document */
/*jslint regexp: true*/


(function ($) {
    "use strict";


// Von ckeditor/plugins/wordcount/plugin.js
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

        $el.parents("form").submit(function() {
            $el.parent().find("textarea").val(editor.getData());
        });
    }


    $.AntragsgruenCKEDITOR = {
        "init": ckeditorInit
    };
}(jQuery));

(function ($) {
    "use strict";


    var motionEditForm = function () {
        $(".wysiwyg-textarea").each(function () {
            var $holder = $(this),
                $textarea = $holder.find(".texteditor");
            $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
        });

        $("input[name=formToken]").each(function () {
            $(this).parents("form").append("<input name='" + $(this).val() + "' value='1' type='hidden'>");
            $(this).remove();
        });
    };


    var consultationEditForm = function () {
        var lang = $('html').attr('lang');

        $("#antrag_neu_kann_telefon").change(function () {
            if ($(this).prop("checked")) $("#antrag_neu_braucht_telefon_holder").show();
            else $("#antrag_neu_braucht_telefon_holder").hide();
        }).trigger("change");

        $('#deadlineAmendmentsHolder').datetimepicker({
            locale: lang
        });
        $('#deadlineMotionsHolder').datetimepicker({
            locale: lang
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
    };

    $.Antragsgruen = {
        "motionShow": motionShow,
        "motionEditForm": motionEditForm,
        "consultationEditForm": consultationEditForm,
        "contentPageEdit": contentPageEdit
    };

    $(".jsProtectionHint").remove();

}(jQuery));
