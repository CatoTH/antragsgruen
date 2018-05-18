"use strict";

$(function () {
    console.log("Hello world");

    requirejs([
        'npm/isotope.pkgd.min.js'
    ], function (Isotope) {
        var initialized = false;
        var grid;

        var initIsotope = function () {
            if (initialized) {
                return;
            }

            $(".motionListUnfiltered").addClass("hidden");
            $(".motionListFiltered").removeClass("hidden");

            grid = new Isotope('.motionListFilter .motionListFiltered', {
                itemSelector: '.motion',
                layoutMode: 'vertical',
                getSortData: {
                    created: '[data-created] parseInt',
                    comments: '[data-num-comments] parseInt'
                }
            });
            initialized = true;
        };

        $(".motionListFilter .motionFilters button, .motionListFilter .motionFilters a").click(function (ev) {
            ev.preventDefault();

            initIsotope();

            var filter = $(this).data("filter");
            if (!filter) {
                return;
            }
            grid.arrange({filter: filter});

            $(".motionListFilter .motionFilters button, .motionListFilter .motionFilters a").removeClass("active");
            $(this).addClass("active");
        });
        $(".motionListFilter .motionSort button").click(function () {
            initIsotope();

            var $this = $(this);
            var asc = ($this.data("order") !== 'desc');
            grid.arrange({sortBy: $(this).data("sort"), sortAscending: asc});
            $(".motionListFilter .motionSort button").removeClass("active");
            $(this).addClass("active");
        });
    });
});
