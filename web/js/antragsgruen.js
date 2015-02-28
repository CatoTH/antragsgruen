/*global browser: true, regexp: true */
/*global $, jQuery, alert, console, CKEDITOR */
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

        return tmp.textContent || tmp.innerText
    }

    function ckeditor_charcount(text) {
        var normalizedText = text.
            replace(/(\r\n|\n|\r)/gm, "").
            replace(/^\s+|\s+$/g, "").
            replace("&nbsp;", "");
        normalizedText = ckeditor_strip(normalizedText).replace(/^([\s\t\r\n]*)$/, "");

        return normalizedText.length;
    }


    function ckeditor_bbcode(id, height) {

        var $el = $("#" + id),
            initialized = $el.data("ckeditor_initialized");
        if (typeof(initialized) != "undefined" && initialized) return;
        $el.data("ckeditor_initialized", "1");
        var opts = {
            allowedContent: 'b s i u p blockquote ul ol li;',
            removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,magicline' + ',sourcearea',
            extraPlugins: 'autogrow,wordcount,bbcode',
            scayt_sLang: 'de_DE',
            autoGrow_bottomSpace: 20,
            // Width and height are not supported in the BBCode format, so object resizing is disabled.
            disableObjectResizing: true,
            wordcount: {
                showWordCount: true,
                showCharCount: true,
                countHTML: false,
                countSpacesAsChars: true
            },
            toolbar: [
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote']},
                {name: 'links', items: ['Link', 'Unlink']},
                {name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt']},
                {name: 'tools', items: ['Maximize']}
            ]

        };
        if (typeof(height) != "undefined" && height > 0) opts["height"] = height;
        var editor = CKEDITOR.replace(id, opts);

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
    }


    function ckeditorInit(id) {

        var $el = $("#" + id),
            initialized = $el.data("ckeditor_initialized");
        if (typeof(initialized) != "undefined" && initialized) return;
        $el.data("ckeditor_initialized", "1");

        CKEDITOR.replace(id, {
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
        });

    }


    $.AntragsgruenCKEDITOR = {
        "init": ckeditorInit
    };
}(jQuery));

(function ($) {
    "use strict";


    var motionEditForm = function () {
        //ckeditor_bbcode("Antrag_text");
        $(".wysiwyg-textarea").each(function() {
            var $holder = $(this),
                $textarea = $holder.find("textarea");
            $.AntragsgruenCKEDITOR.init($textarea.attr("id"));
        });

        $(".jsProtectionHint").remove();
        $("input[name=formToken]").each(function () {
            $(this).parents("form").append("<input name='" + $(this).val() + "' value='1' type='hidden'>");
            $(this).remove();
        });
    };

    $.Antragsgruen = {
        "motionEditForm": motionEditForm
    };

}(jQuery));
