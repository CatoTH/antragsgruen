'use strict';

(function () {
    CKEDITOR.plugins.add( 'autocolorize', {
        lang: 'de,en',
        init: function(editor) {
            this.initColor1(editor);
            this.initColor2(editor);
        },

        initColor1: function(editor) {
            var style = new CKEDITOR.style( {
                element: 'span',
                attributes: { 'class': 'adminTyped1' },
            } );

            // Listen to contextual style activation.
            editor.attachStyleStateChange( style, function( state ) {
                !editor.readOnly && editor.getCommand( 'toggleAdminTyped1' ).setState( state );
            } );

            // Create the command that can be used to apply the style.
            editor.addCommand( 'toggleAdminTyped1', new CKEDITOR.styleCommand( style) );

            editor.ui.addButton( 'ToggleAdminTyped1', {
                isToggle: true,
                label: editor.lang.autocolorize.buttonTitle,
                command: 'toggleAdminTyped1',
                toolbar: 'autocolorize,100',
                icon: 'plugins/autocolorize/adminTyped1.png'
            } );
        },

        initColor2: function(editor) {
            var style = new CKEDITOR.style({
                element: 'span',
                attributes: {'class': 'adminTyped2'},
            });

            // Listen to contextual style activation.
            editor.attachStyleStateChange(style, function (state) {
                !editor.readOnly && editor.getCommand('toggleAdminTyped2').setState(state);
            });

            // Create the command that can be used to apply the style.
            editor.addCommand('toggleAdminTyped2', new CKEDITOR.styleCommand(style));

            editor.ui.addButton('ToggleAdminTyped2', {
                isToggle: true,
                label: editor.lang.autocolorize.buttonTitle,
                command: 'toggleAdminTyped2',
                toolbar: 'autocolorize,100',
                icon: 'plugins/autocolorize/adminTyped2.png'
            });
        }
    } );
})();
