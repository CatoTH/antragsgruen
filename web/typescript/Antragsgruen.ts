declare let requirejs: any;
declare let ANTRAGSGRUEN_STRINGS: string[][];

(function ($: JQueryStatic) {
    $("[data-antragsgruen-load-class]").each(function () {
        let loadModule = $(this).data("antragsgruen-load-class");
        requirejs([loadModule]);
    });

    $("[data-antragsgruen-widget]").each(function () {
        let $element = $(this),
            loadModule = $element.data("antragsgruen-widget");
        requirejs([loadModule], function (imports) {
            let className = loadModule.split('/');
            new imports[className[className.length - 1]]($element);
        });
    });

    $(".jsProtectionHint").each(function () {
        let $hint = $(this);
        $('<input type="hidden" name="jsprotection">').attr("value", $hint.data("value")).appendTo($hint.parent());
        $hint.remove();
    });

    bootbox.setLocale($("html").attr("lang").split("_")[0]);

    $(document).on('click', '.amendmentAjaxTooltip', function (ev) {
        let $el = $(ev.currentTarget);
        if ($el.data('initialized') == '0') {
            $el.data('initialized', '1');
            $el.popover({
                html: true,
                trigger: 'manual',
                container: 'body',
                template: '<div class="popover popover-amendment-ajax" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                content: function () {
                    let id = 'pop_' + (new Date()).getTime(),
                        content = '<div id="' + id + '">Loading...</div>',
                        url = $el.data('url');
                    $.get(url, function (ret) {
                        $('#' + id).html(ret);
                    });
                    return content;
                }
            });
        }
        $('.amendmentAjaxTooltip').not($el).popover('hide');
        $el.popover('toggle');
    });
    $(document).on('click', function (ev) {
        let $target = $(ev.target);
        if (
            !$target.hasClass('amendmentAjaxTooltip') && !$target.hasClass('popover') &&
            $target.parents('.amendmentAjaxTooltip').length == 0 && $target.parents('.popover').length == 0
        ) {
            $('.amendmentAjaxTooltip').popover('hide');
        }
    });

    // Needs to be synchronized with ConsultationAgendaItem:getSortedFromConsultation
    let recalcAgendaNode = function ($ol) {
        let currNumber = '0.',
            $lis = $ol.find('> li.agendaItem');
        $lis.each(function () {
            let $li = $(this),
                code = $li.data('code'),
                currStr = '',
                $subitems = $li.find('> ol');
            if (code == '#') {
                let parts = currNumber.split('.');
                if (parts[0].match(/^[a-y]$/i)) { // Single alphabetical characters
                    parts[0] = String.fromCharCode(parts[0].charCodeAt(0) + 1);
                } else { // Numbers or mixtures of alphabetical characters and numbers
                    let matches = parts[0].match(/^(.*[^0-9])?([0-9]*)$/),
                        nonNumeric = (typeof(matches[1]) == 'undefined' ? '' : matches[1]),
                        numeric = parseInt(matches[2] == '' ? '1' : matches[2]);
                    parts[0] = nonNumeric + ++numeric;
                }
                currNumber = currStr = parts.join('.');
            } else {
                currStr = currNumber = code + ''; // currNumber needs to be a string, always.
            }

            $li.find('> div > h3 .code').text(currStr);
            if ($subitems.length > 0) {
                recalcAgendaNode($subitems);
            }
        });
    };
    $('ol.motionListAgenda').on("antragsgruen:agenda-change", function () {
        recalcAgendaNode($(this));
    }).trigger("antragsgruen:agenda-change");

    window['__t'] = function (category, str) {
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
    };
}(jQuery));
