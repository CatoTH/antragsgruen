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

    var currTagFilter = '*',
        currPhaseFilter = '*',
        currSort = 'phase',
        currSortAsc = true;

    var setFilters = function () {
        initIsotope();

        var filter = '';
        if (currTagFilter !== '*') {
            filter += currTagFilter;
        }
        if (currPhaseFilter !== '*') {
            filter += currPhaseFilter;
        }
        if (currSort !== 'phase') {
            filter += '.motion';
        }
        if (filter === '') {
            filter = '*'
        }

        console.log({
            filter: filter,
            sortBy: currSort,
            sortAscending: currSortAsc
        });

        grid.arrange({
            filter: filter,
            sortBy: currSort,
            sortAscending: currSortAsc
        });
    };

    $(".motionPhaseFilters button").click(function (ev) {
        ev.preventDefault();

        var filter = $(this).data("filter");
        if (!filter) {
            return;
        }
        currPhaseFilter = filter;
        setFilters();

        $(".motionPhaseFilters button").removeClass("active");
        $(this).addClass("active");
    });

    $(".tagList a").click(function (ev) {
        ev.preventDefault();
        initIsotope();

        var filter = $(this).data("filter");
        if (!filter) {
            return;
        }

        currTagFilter = filter;
        setFilters();

        $(".tagList a").removeClass("active");
        $(this).addClass("active");
    });

    $(".motionListFilter .motionSort button").click(function () {
        initIsotope();

        var $this = $(this);
        currSort = $(this).data("sort");
        currSortAsc = ($this.data("order") !== 'desc');
        setFilters();

        $(".motionListFilter .motionSort button").removeClass("active");
        $(this).addClass("active");

    });
};

$(function () {
    requirejs([
        'npm/isotope.pkgd.min.js'
    ], onInitIsotope);
});
