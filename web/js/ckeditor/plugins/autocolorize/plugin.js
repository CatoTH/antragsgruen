'use strict';

(function () {
    CKEDITOR.plugins.add( 'autocolorize', {
        lang: 'de,en',
        init: function(editor) {
            new Autocolorize(editor);
		},
    } );

    function Autocolorize( editor, callback, throttle ) {
        this.editor = editor;
        this.active = false;
        var $this = this;

        editor.addCommand('toggleAdminTyped', {
            requiredContent: 'li',
            exec: function (editor) { $this.toggleAdminTyped(editor); }
        });
        editor.ui.addButton('ToggleAdminTyped', {
            label: editor.lang.autocolorize.buttonTitle,
            command: 'toggleAdminTyped',
            toolbar: 'autocolorize,100',
            icon: 'plugins/autocolorize/adminTyped.png'
        });
        this.setActiveState();

        if (this.editor.status === 'ready') {
            this.attach();
        } else {
            this.editor.on('instanceReady', function () {
                this.attach();
            }, this);
        }
    }

    Autocolorize.prototype = {
        attach: function() {
            var $this = this;
            this.editable = this.editor.editable();
            // this.editor.on("insertHtml", function (ev) { $this.onInsertHtml(ev); });
            // this.editor.on("insertText", function (ev) { $this.onInsertText(ev); });
            this.editable.on("keypress", function (ev) { $this.onKeyPress(ev); });
            // this.editable.on("keyup", function (ev) { $this.onKeyUp(ev); });
        },

        onInsertText: function (ev) {
            // console.log("onInsertText", ev.data);
        },

        onInsertHtml: function (ev) {
            // console.log("onInsertHtml", ev.data);
        },

        needsInsertingNode: function (element) {
            if (!element) {
                // Root element => no insert/delete / adminTyped element found => inserting is necessary
                return true;
            }
            if (element.nodeType === 3) {
                // Text node
                return this.needsInsertingNode(element.parentElement);
            }
            if (element.nodeType !== 1) {
                console.warn('Unexpected node type', element);
                return false;
            }

            if (element.nodeName === 'SPAN' && element.classList.contains('adminTyped')) {
                return false;
            }
            if (element.nodeName === 'INS' || element.nodeName === 'DEL') {
                return false;
            }
            if (element.classList.contains('inserted') || element.classList.contains('deleted')) {
                return false;
            }

            return this.needsInsertingNode(element.parentElement);
        },

        onKeyPress: function (ev) {
            //this.editor.fire( 'saveSnapshot' );
            if (!this.active) {
                return;
            }

            var ranges = this.editor.getSelection().getRanges();
            if (ranges.length !== 1) {
                console.warn("strange selection", ranges);
                return;
            }
            if (this.needsInsertingNode(ranges[0].startContainer.$)) {
                ev.cancel();
                ev.data.preventDefault();
                let code = ev.data.$.key;
                if (code === ' ') {
                    code = '&nbsp;';
                } else {
                    code = CKEDITOR.tools.htmlEncode(ev.data.$.key);
                }
                this.editor.insertHtml('<span class="adminTyped">' + code + '</span>', 'html');
                //this.editor.fire( 'saveSnapshot' );
            }
        },

        onKeyUp: function (ev) {
        },

        close: function() {

		},

        toggleAdminTyped: function(editor) {
            console.log("toggleAdminTyped", this);
            this.active = !this.active;
            this.setActiveState();
        },

        setActiveState() {
            if (this.active) {
                this.editor.getCommand( 'toggleAdminTyped' ).setState( CKEDITOR.TRISTATE_ON );
            } else {
                this.editor.getCommand( 'toggleAdminTyped' ).setState( CKEDITOR.TRISTATE_OFF );
            }
        },
    };

    CKEDITOR.plugins.autocolorize = Autocolorize;
})();
