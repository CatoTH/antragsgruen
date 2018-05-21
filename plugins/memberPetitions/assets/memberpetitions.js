"use strict";

var onInitIsotope = function (Isotope) {
    var initialized = false;
    var grid;

    var initIsotope = function () {
        if (initialized) {
            return;
        }

        $(".motionListUnfiltered").addClass("hidden");
        $(".motionListFiltered").removeClass("hidden");

        grid = new Isotope('.motionListFilter .motionListFiltered', {
            itemSelector: '.sortitem',
            layoutMode: 'vertical',
            getSortData: {
                created: '[data-created] parseInt',
                comments: '[data-num-comments] parseInt',
                amendments: '[data-num-amendments] parseInt',
                phase: '[data-phase] parseInt'
            }
        });
        initialized = true;
    };

    var currFilter = '*',
        currSort = 'phase';

    $(".motionListFilter .motionFilters button, .motionListFilter .motionFilters a").click(function (ev) {
        ev.preventDefault();

        initIsotope();

        var filter = $(this).data("filter");
        if (!filter) {
            return;
        }
        if (filter === 'filter' && currSort !== 'phase') {
            filter = '.motion';
        }
        grid.arrange({filter: filter});

        $(".motionListFilter .motionFilters button, .motionListFilter .motionFilters a").removeClass("active");
        $(this).addClass("active");
        currFilter = filter;
    });
    $(".motionListFilter .motionSort button").click(function () {
        initIsotope();

        var $this = $(this),
            sort = $(this).data("sort"),
            asc = ($this.data("order") !== 'desc');
        var opts = {sortBy: sort, sortAscending: asc};
        if (sort !== 'phase') {
            if (currFilter === '*') {
                opts['filter'] = '.motion';
            } else {
                opts['filter'] = currFilter;
            }
        }
        
        grid.arrange(opts);
        $(".motionListFilter .motionSort button").removeClass("active");
        $(this).addClass("active");
        currSort = sort;
    });
};

$(function () {
    requirejs([
        'npm/isotope.pkgd.min.js'
    ], onInitIsotope);
});
