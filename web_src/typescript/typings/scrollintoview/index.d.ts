// Type definitions for scrollIntoView
// Project:
// Definitions by: Tobias Hößl <https://www.hoessl.eu/>
// Definitions:

/// <reference types="jquery" />


interface ScrollIntoViewSettings {
    top_offset?: number;
}

interface ScrollIntoView {
    (): JQuery;
    (settings: ScrollIntoViewSettings): JQuery;

}

interface JQuery {
    scrollintoview(settings: ScrollIntoViewSettings): ScrollIntoView;
}
