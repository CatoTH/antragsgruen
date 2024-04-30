declare let requirejs: any;
declare let ANTRAGSGRUEN_STRINGS: string[][];

(function ($: JQueryStatic) {
    const $myScriptTag = $("#antragsgruenScript");
    const reqOne = requirejs.config({
        baseUrl: $myScriptTag.data("resource-base") + "js/build/"
    });

    $("[data-antragsgruen-load-class]").each(function () {
        const loadModule = $(this).data("antragsgruen-load-class");
        reqOne([loadModule]);
    });

    $("[data-antragsgruen-widget]").each(function () {
        const $element = $(this),
            element = this,
            loadModule = $element.data("antragsgruen-widget");
        reqOne([loadModule], function (imports) {
            const className = loadModule.split('/');
            new imports[className[className.length - 1]]($element, element);
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
            let classes = "popover popover-amendment-ajax " + $el.data('tooltip-extra-class');
            let placement: "auto" | "left" | "right" | "top" | "bottom" = 'right';
            if ($el.data('placement')) {
                placement = $el.data('placement');
            }
            $el.popover({
                html: true,
                trigger: 'manual',
                container: 'body',
                template: '<div class="' + classes + '" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                placement: placement,
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
        $('.ajaxAmendment').parents('.popover').remove(); // Workardound for cases where the holder element vanishes while the popover is opened
        $el.popover('toggle');
    });
    $(document).on('click', function (ev) {
        let $target = $(ev.target);
        if (
            !$target.hasClass('amendmentAjaxTooltip') && !$target.hasClass('popover') &&
            $target.parents('.amendmentAjaxTooltip').length == 0 && $target.parents('.popover').length == 0
        ) {
            $('.amendmentAjaxTooltip').popover('hide');
            $('.ajaxAmendment').parents('.popover').remove(); // Workardound for cases where the holder element vanishes while the popover is opened
        }
    });

    $(document).on('click', '.pseudoLink', ev => {
        window.location.href = $(ev.currentTarget).data("href");
    });

    // Used to only show outlines on links when keyboard is used
    document.body.addEventListener('mousedown', () => {
        document.body.classList.add('usingMouse');
    });
    document.body.addEventListener('keydown', () => {
        document.body.classList.remove('usingMouse');
    });


    // Needs to be synchronized with ConsultationAgendaItem:getSortedFromConsultation
    const recalcAgendaNode = ($ol: JQuery, prefix: string) => {
        const separator = '.';
        const prevCode = (prefix === '' || prefix[prefix.length - 1] === separator ? prefix : prefix + separator);
        let currNumber = '0' + separator,
            $lis = $ol.find('> li.agendaItem');
        $lis.each(function () {
            let $li = $(this),
                currStr,
                $subitems = $li.find('> ol');
            if ($li.hasClass('agendaItemDate')) {
                currStr = prefix;
            } else {
                const code = $li.data('code');
                if (code == '#') {
                    let parts = currNumber.split(separator);
                    if (parts[0].match(/^[a-y]$/i)) { // Single alphabetical characters
                        parts[0] = String.fromCharCode(parts[0].charCodeAt(0) + 1);
                    } else { // Numbers or mixtures of alphabetical characters and numbers
                        let matches = parts[0].match(/^(.*[^0-9])?([0-9]*)$/),
                            nonNumeric = (typeof (matches[1]) == 'undefined' ? '' : matches[1]),
                            numeric = parseInt(matches[2] == '' ? '1' : matches[2]);
                        parts[0] = nonNumeric + ++numeric;
                    }
                    currNumber = currStr = parts.join(separator);
                } else {
                    currStr = currNumber = (code + '').trim(); // currNumber needs to be a string, always.
                }
                if (currStr !== '') {
                    currStr = prevCode + currStr;
                }

                $li.find('> div > h3 .code').text(currStr);
            }
            if ($subitems.length > 0) {
                recalcAgendaNode($subitems, currStr);
            }
        });
    };
    $('ol.motionListWithinAgenda').on("antragsgruen:agenda-change", function () {
        recalcAgendaNode($(this), '');
    }).trigger("antragsgruen:agenda-change");

    $('.motionList .amendmentsToggler').each((i, el) => {
        const $el = $(el);
        $el.find("button").on("click", () => {
            $el.toggleClass("closed");
            $el.toggleClass("opened");
            $el.next("ul.amendments").toggleClass("closed");
        });
    });

    // Hint: this is only executed for high-load consultations (with enabled viewCacheFilePath)
    const todoLoader = document.querySelector("#adminTodoLoader");
    if (todoLoader) {
        return fetch(todoLoader.getAttribute("data-url"))
            .then(response => response.json())
            .then(json => {
                const todoEl = document.querySelector("#adminTodo");
                const label = todoEl.innerHTML.replace(/###COUNT###/, json['count']);
                todoEl.innerHTML = label;
                todoEl.setAttribute("aria-label", label);
                if (json['count'] > 0) {
                    todoLoader.classList.remove("hidden");
                }
            });
    }

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

// Components defined in HTML templates should be registered here, so the actual Vue app can initialize it on initialization
const VUE_TO_REGISTER_COMPONENTS = {
    "fullscreen": [],
    "merging": [],
    "speech": [],
    "users": [],
    "voting": [],
};

window['__setVueComponent'] = function(appId, componentType, componentName, componentData) {
    VUE_TO_REGISTER_COMPONENTS[appId].push({componentType, componentName, componentData});
}

window['__initVueComponents'] = function(vueApp: any, appId: string) {
    VUE_TO_REGISTER_COMPONENTS[appId].forEach(component => {
        if (component.componentType === 'component') {
            vueApp.component(component.componentName, component.componentData);
        } else if (component.componentType === 'directive') {
            vueApp.directive(component.componentName, component.componentData)
        } else {
            console.warn('Unknown component Type: ' + component.componentType);
        }
    })
}
