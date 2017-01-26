// Typings for jQuery-Plugins
// Project: Antragsgrün
// Definitions by: Tobias Hößl <https://www.hoessl.eu/>
// Definitions:

/// <reference types="jquery" />

interface JQuery {
    isOnScreen(x: number, y: number): boolean;
    sort(callback): JQuery;
}
