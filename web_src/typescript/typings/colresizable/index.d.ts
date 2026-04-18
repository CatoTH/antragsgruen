// Type definitions for coresizable
// Project: http://www.bacubacu.com/colresizable/
// Definitions by: Tobias Hößl <https://www.hoessl.eu/>
// Definitions:

/// <reference types="jquery" />


interface ColResizableSettings {
    liveDrag?: boolean;
    postbackSafe?: boolean;
    minWidth: number;
}

interface ColResizableStatic {

    /**
    * This method allows you to call nestedSortable without having to assign it to an element.
    */
    (settings: ColResizableSettings): any;

    /**
    * Default settings used for Colorbox calls
    */
    settings: ColResizableSettings;

}

interface ColResizable {
    (): JQuery;
    (settings: ColResizableSettings): JQuery;

}

interface JQueryStatic {
    colResizable: ColResizableStatic;
}

interface JQuery {
    colResizable(settings: ColResizableSettings): ColResizable;
}
