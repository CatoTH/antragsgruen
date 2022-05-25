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
        this.active1 = false;
        this.active2 = false;
        var $this = this;

        editor.addCommand('toggleAdminTyped1', {
            requiredContent: 'li',
            exec: function (editor) { $this.toggleAdminTyped1(editor); }
        });
        editor.ui.addButton('ToggleAdminTyped1', {
            label: editor.lang.autocolorize.buttonTitle,
            command: 'toggleAdminTyped1',
            toolbar: 'autocolorize,100',
            icon: 'plugins/autocolorize/adminTyped1.png'
        });
        this.setActiveState1();

        editor.addCommand('toggleAdminTyped2', {
            requiredContent: 'li',
            exec: function (editor) { $this.toggleAdminTyped2(editor); }
        });
        editor.ui.addButton('ToggleAdminTyped2', {
            label: editor.lang.autocolorize.buttonTitle,
            command: 'toggleAdminTyped2',
            toolbar: 'autocolorize,100',
            icon: 'plugins/autocolorize/adminTyped2.png'
        });
        this.setActiveState2();

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

        needsInsertingNode: function (element, adminTypedNo) {
            if (!element) {
                // Root element => no insert/delete / adminTyped element found => inserting is necessary
                return true;
            }
            if (element.nodeType === 3) {
                // Text node
                return this.needsInsertingNode(element.parentElement, adminTypedNo);
            }
            if (element.nodeType !== 1) {
                console.warn('Unexpected node type', element);
                return false;
            }

            if (element.nodeName === 'SPAN' && element.classList.contains('adminTyped' + adminTypedNo)) {
                return false;
            }
            if (element.nodeName === 'INS' || element.nodeName === 'DEL') {
                return false;
            }
            if (element.classList.contains('inserted') || element.classList.contains('deleted')) {
                return false;
            }

            return this.needsInsertingNode(element.parentElement, adminTypedNo);
        },

        insertNodeIfNecessary: function (ranges, ev, adminTypeNo) {
            if (!this.needsInsertingNode(ranges[0].startContainer.$, adminTypeNo)) {
                return;
            }
            ev.cancel();
            ev.data.preventDefault();
            let code = ev.data.$.key;
            if (code === ' ') {
                code = '&nbsp;';
            } else {
                code = CKEDITOR.tools.htmlEncode(ev.data.$.key);
            }
            this.editor.insertHtml('<span class="adminTyped' + adminTypeNo + '">' + code + '</span>', 'html');
            //this.editor.fire( 'saveSnapshot' );
        },

        onKeyPress: function (ev) {
            //this.editor.fire( 'saveSnapshot' );
            if (!this.active1 && !this.active2) {
                return;
            }

            var ranges = this.editor.getSelection().getRanges();
            if (ranges.length !== 1) {
                console.warn("strange selection", ranges);
                return;
            }
            if (this.active1) {
                this.insertNodeIfNecessary(ranges, ev, '1');
            }
            if (this.active2) {
                this.insertNodeIfNecessary(ranges, ev, '2');
            }
        },

        onKeyUp: function (ev) {
        },

        close: function() {

		},

        toggleAdminTyped1: function(editor) {
            this.active1 = !this.active1;
            this.setActiveState1();
        },

        setActiveState1() {
            if (this.active1) {
                this.editor.getCommand( 'toggleAdminTyped1' ).setState( CKEDITOR.TRISTATE_ON );
                this.editor.getCommand( 'toggleAdminTyped2' ).setState( CKEDITOR.TRISTATE_OFF );
                this.active2 = false;
            } else {
                this.editor.getCommand( 'toggleAdminTyped1' ).setState( CKEDITOR.TRISTATE_OFF );
            }
        },

        toggleAdminTyped2: function(editor) {
            this.active2 = !this.active2;
            this.setActiveState2();
        },

        setActiveState2() {
            if (this.active2) {
                this.editor.getCommand( 'toggleAdminTyped2' ).setState( CKEDITOR.TRISTATE_ON );
                this.editor.getCommand( 'toggleAdminTyped1' ).setState( CKEDITOR.TRISTATE_OFF );
                this.active1 = false;
            } else {
                this.editor.getCommand( 'toggleAdminTyped2' ).setState( CKEDITOR.TRISTATE_OFF );
            }
        },
    };

    CKEDITOR.plugins.autocolorize = Autocolorize;
})();
